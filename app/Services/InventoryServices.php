<?php

namespace App\Services;

use App\Enums\InventoryActionType;
use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Builder;

class InventoryServices
{
    public function getList()
    {
        return InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name', 'products.account_code',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])
            ->when(
                request('location_id'),
                fn (Builder $builder) => $builder->where('branch_id', request('location_id'))
            )
            ->when(
                request('purpose') == 'production',
                fn (Builder $builder) => $builder->whereIn('branch_id', [1, 2])
            )
            ->when(
                request('purpose') != 'production' && request('purpose') != 'internal_use',
                fn (Builder $builder) => $builder->where('branch_id', request('location_id') | 1)
            )
            ->when(
                request('purpose') == 'internal_use',
                fn (Builder $builder) => $builder->where('branch_id', request()->user()->branch->id)
            )
            ->when(
                request('by_unit'),
                fn (Builder $builder) => $builder->where('business_unit', request('by_unit'))
            )
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',name,code,brand) like '%{$keyword}%' ");
                }
            )
            ->when(
                request('column') && request('direction'),
                fn (Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(is_integer(request()->get('paginate')) ?? 0);
    }
    public function getItemCosting()
    {
        $user = request()->user();
        return InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name', 'products.account_code',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id', 'price',
                "products.id as productId"
            ])
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',name,code,brand) like '%{$keyword}%' ");
                }
            )
            ->where('branch_id', $user->branch_id)
            ->when(
                request('column') && request('direction'),
                fn (Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(is_integer(request()->get('paginate')) ?? 0);
    }

    public function getBranchInventory()
    {
        return InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])
            ->when(
                request('location_id'),
                fn (Builder $builder) => $builder->where('branch_id', request('location_id'))
            )
            ->when(
                request('by_unit'),
                fn (Builder $builder) => $builder->where('business_unit', request('by_unit'))
            )
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',name,code,brand) like '%{$keyword}%' ");
                }
            )
            ->when(
                request('column') && request('direction'),
                fn (Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(request('paginate') ?: 12);
    }


    public function getHistories($id)
    {
        return Inventory::query()->with(['receives'])->where('inventory_location_id', $id)->get();
    }

    public function getLowStock()
    {
        return InventoryLocation::query()
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])->whereRaw('inventory_locations.quantity <= inventory_locations.stock_low_level')->get();
    }

    public function getEmptyStock()
    {
        return InventoryLocation::query()
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])->where('inventory_locations.quantity', '0')->get();
    }

    public function updateTriggers(int $id)
    {
        $inventory = InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])
            ->findOrFail($id);
        $inventory->stock_low_level = request()->get('stock_low_level');
        $inventory->reorder_point = request()->get('reorder_point');
        $inventory->save();

        return $inventory;
    }
    public function updatePrice(int $id)
    {
        $inventory = InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*', 'products.name',
                'products.code', 'products.description', 'products.unit_measurement',
                'products.unit_value', 'products.brand', 'products.category_id',
                "products.id as productId"
            ])
            ->findOrFail($id);
        $inventory->price = request()->get('price');
        $inventory->save();

        return $inventory;
    }
    public function in(int|Product $product, int $quantity, array $data = [])
    {
        $inventoryLocation = $this->resolveProduct($product, $data['branch_id']);
        $inventoryLocation->total_quantity = $inventoryLocation->total_quantity + $quantity;
        $inventoryLocation->quantity = $inventoryLocation->quantity + $quantity;
        $inventoryLocation->price = $data['price'];
        $inventoryLocation->save();

        $inventory = new Inventory();
        $inventory->product_id = $inventoryLocation->product_id;
        $inventory->inventory_location_id =  $inventoryLocation->id;
        $inventory->quantity = $quantity;
        $inventory->receive_id = $data['receive_id'] ?? null;
        $inventory->from_request_id = $data['from_request_id'] ?? null;
        $inventory->description = $data['description'] ?? null;
        $inventory->expired_at = $data['expired_at'] ?? null;
        $inventory->action = $data['action'] ?? InventoryActionType::Auto;
        $inventory->batch = $this->batchGenerator($inventoryLocation->id);
        $inventory->price = $data['price'];
        $inventory->save();

        $this->transaction($inventory->id, [
            'quantity' => $quantity,
            'branch_id' => $inventoryLocation->branch_id,
            'transacted_by_id' => $data['user_id'],
            'accepted_by_id' => $data['user_id'],
            'to_branch_id' => $inventoryLocation->branch_id,
            'movement' => InventoryMovementType::In,
            'action' => $inventory->action,
            'details' => $data['description'] ?? null,
            'receive_id' => $data['receive_id'] ?? null,
        ]);

        return $product;
    }


    private function batchGenerator(int $inventoryLocation): int
    {
        return Inventory::query()->where('inventory_location_id', $inventoryLocation)->count() + 1;
    }


    public function out(int|Product $product, int $amount, array $data = [])
    {
        $inventoryLocation = $this->resolveProduct($product, $data['branch_id']);
        if ($inventoryLocation->total_quantity > 0) {
            $stock = $this->getNonEmptyStock($inventoryLocation->id);
            if (!$stock) {
                $stock->decrement('quantity', $amount);
            }
            $amount = $amount;
            $this->transaction($stock->id, [
                'quantity' => $amount,
                'branch_id' => $inventoryLocation->branch_id,
                'transacted_by_id' => $data['user_id'],
                'accepted_by_id' => $data['user_id'],
                'from_branch_id' => $inventoryLocation->branch_id,
                'to_branch_id' => $data['branch_id'],
                'movement' => InventoryMovementType::Out,
                'action' => $data['action'] ?? InventoryActionType::Auto,
                'details' => $data['description'] ?? ''
            ]);
            /*  if($amount > 0){
                 $this->out($inventoryLocation,$amount);
             } */
        }
        $inventoryLocation->decrement('total_quantity', $amount);

        return $product;
    }

    public function getNonEmptyStock(int $inventory_location_id)
    {
        return Inventory::query()
            ->where('inventory_location_id', $inventory_location_id)
            ->where('quantity', '>', 0)
            // ->where('sellable',1)
            ->orderBy('batch', 'asc')
            ->first();
    }
    public function resolveStockInventory(int|InventoryLocation $inventory_location)
    {
        $user = request()->user();

        return Inventory::query()->firstOrCreate([
            'inventory_location_id' => $inventory_location->id,
        ], [
            'quantity' => $inventory_location->quantity,
            'batch' => $user->branch_id,
            'receive_id' => $user->id,
            'product_id' => $inventory_location->product_id,
        ]);
    }


    public function transaction(int $inventory_id, array $data)
    {
        $transaction = new InventoryTransaction();
        $transaction->quantity = $data['quantity'];
        $transaction->branch_id = $data['branch_id'];
        $transaction->transacted_by_id = $data['transacted_by_id'];
        $transaction->accepted_by_id = $data['accepted_by_id'];
        $transaction->to_branch_id = $data['to_branch_id'] ?? null;
        $transaction->to_assembly_id = $data['to_assembly_id'] ?? null;
        $transaction->to_client_id = $data['to_client_id'] ?? null;
        $transaction->from_branch_id = $data['from_branch_id'] ?? null;
        $transaction->from_supplier_id = $data['from_supplier_id'] ?? null;
        $transaction->from_request_id = $data['from_request_id'] ?? null;
        $transaction->receive_id = $data['receive_id'] ?? null;
        $transaction->details = $data['details'] ?? '';
        $transaction->action = $data['action'];
        $transaction->movement = $data['movement'];
        $transaction->inventory_id = $inventory_id;
        $transaction->save();
    }



    private function resolveProduct(int|Product $product, int $branch_id): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|Product|int|array|null
    {
        if (!($product instanceof  Product)) {
            $product = Product::query()->findOrFail($product);
        }

        $user = request()->user();
        $business_unit = $user->business_unit ?: request()->get('business_unit') ?: request()->header('business-unit') ?: null;

        return InventoryLocation::query()->firstOrCreate([
            'product_id' => $product->id,
            'branch_id' => $branch_id == 0 ? $user->branch_id : $branch_id,
            // 'business_unit' => $business_unit
        ]);
    }


    public function stockIn(int|Product $product, int $amount, array $data = [], int $branch_id = 0)
    {
        try {
            $inventoryLocation = $this->resolveProduct($product, $branch_id);

            $stock = $this->resolveStockInventory($inventoryLocation);
            $amount = $amount;
            $stock->increment('quantity', $amount);
            $this->transaction($stock->id, [
                'quantity' => $amount,
                'branch_id' => $inventoryLocation->branch_id,
                'transacted_by_id' => $data['transacted_by_id'],
                'accepted_by_id' => $data['accepted_by_id'],
                'from_request_id' => $data['from_request_id'] ?? null,
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'movement' => InventoryMovementType::In,
                'action' => $data['action'] ?? InventoryActionType::Auto,
                'details' => $data['description'] ?? ''
            ]);
            $inventoryLocation->increment('total_quantity', $amount);

            return $inventoryLocation;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function stockOut(int|Product $product, int $amount, array $data = [], int $branch_id = 0)
    {
        try {
            DB::beginTransaction();
            $inventoryLocation = $this->resolveProduct($product, $branch_id);
            if ($inventoryLocation->total_quantity > 0) {
                $stock = $this->resolveStockInventory($inventoryLocation);
                $amount = $amount;
                $stock->decrement('quantity', $amount);
                $this->transaction($stock->id, [
                    'quantity' => $amount,
                    'branch_id' => $inventoryLocation->branch_id,
                    'transacted_by_id' => $data['transacted_by_id'],
                    'accepted_by_id' => $data['accepted_by_id'],
                    'from_branch_id' => $data['from_branch_id'],
                    'from_request_id' => $data['from_request_id'] ?? null,
                    'to_branch_id' => $data['to_branch_id'],
                    'movement' => InventoryMovementType::Out,
                    'action' => $data['action'] ?? InventoryActionType::Auto,
                    'details' => $data['description'] ?? ''
                ]);
            }
            $inventoryLocation->decrement('total_quantity', $amount);
            DB::commit();
            return  $inventoryLocation;
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
}
