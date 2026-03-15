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
use App\Events\RequestOrderEvents;
use App\Http\Resources\InventoryLocationResource;
use Illuminate\Validation\ValidationException;

class InventoryServices
{
    public function getBranchInventoryList()
    {
        event(new RequestOrderEvents('test inventory'));

        return InventoryLocation::query()
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*',
                'products.name as name',
                'products.account_code as account_code',
                'products.code as code',
                'products.description as description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand as brand',
                'products.category_id',
                "products.id as productId"
            ])
            ->with([
                'location',
                'inventory',
                'product',
                'branch'
            ])
            ->when(
                request('location_id'),
                function (Builder $builder) {
                    if ((request('location_id') == 1 || request('location_id') == 2) && request('request_order') == 'yes') {
                        return $builder
                            ->where('branch_id', request('location_id'))
                            ->where('code', 'like', request('location_id') == 1 ? '%WHSE%' : '%BULA%');
                    } else {
                        return $builder->where('branch_id', request('location_id'));
                    }
                }
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
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(request('paginate', 10));
    }

    public function getList()
    {
        event(new RequestOrderEvents('test inventory'));

        return InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*',
                'products.name as name',
                'products.account_code as account_code',
                'products.code as code',
                'products.description as description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand as brand',
                'products.category_id',
                "products.id as productId"
            ])
            ->when(
                request('location_id'),
                fn(Builder $builder) => $builder->where('branch_id', request('location_id'))
            )
            ->when(
                request('purpose') == 'production',
                fn(Builder $builder) => $builder->whereIn('branch_id', [1])
            )
            ->when(
                request('purpose') != 'production' && request('purpose') != 'internal_use',
                fn(Builder $builder) => $builder->where('branch_id', request('location_id') | 1)
            )
            ->when(
                request('purpose') == 'internal_use',
                fn(Builder $builder) => $builder->where('branch_id', request()->user()->branch->id)
            )
            ->when(
                request('by_unit'),
                fn(Builder $builder) => $builder->where('business_unit', request('by_unit'))
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
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(request('paginate', 10));
    }
    public function getItemCosting(?int $branchId = null)
    {
        return InventoryLocation::query()
            // ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            // ->select([
            //     'inventory_locations.*', 'products.name', 'products.account_code',
            //     'products.code', 'products.description', 'products.unit_measurement',
            //     'products.unit_value', 'products.brand', 'products.category_id', 'price',
            //     "products.id as productId"
            // ])
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',name,code,brand) like '%{$keyword}%' ");
                }
            )
            ->when(
                request('category_id'),
                function (Builder $q) {
                    $category_id = request('category_id');
                    return $q->where('products.category_id', $category_id);
                }
            )
            ->when(
                $branchId !== null,
                fn(Builder $q) => $q->where('inventory_locations.branch_id', $branchId)
            )
            ->when(
                request('column') && request('direction'),
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )->get();
        // ->paginate(is_integer(request('paginate')) ?  request('paginate') : -1);
    }

    public function getBranchInventory()
    {
        return InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*',
                'products.name',
                'products.code',
                'products.description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand',
                'products.category_id',
                "products.id as productId"
            ])
            ->when(
                request('location_id'),
                fn(Builder $builder) => $builder->where('branch_id', request('location_id'))
            )
            ->when(
                request('by_unit'),
                fn(Builder $builder) => $builder->where('business_unit', request('by_unit'))
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
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->paginate(request('paginate', 10));
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
                'inventory_locations.*',
                'products.name',
                'products.code',
                'products.description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand',
                'products.category_id',
                "products.id as productId"
            ])->whereRaw('inventory_locations.quantity <= inventory_locations.stock_low_level')
            ->when(
                request('column') && request('direction'),
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )->get();
    }
    public function getLowStockCount()
    {
        return InventoryLocation::query()
            ->whereRaw('inventory_locations.quantity <= inventory_locations.stock_low_level')
            ->where('inventory_locations.quantity', '>', 0)
            ->count();
    }

    public function getEmptyStock()
    {
        return InventoryLocation::query()
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*',
                'products.name',
                'products.code',
                'products.description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand',
                'products.category_id',
                "products.id as productId"
            ])->where('inventory_locations.quantity', '0')
            ->when(
                request('column') && request('direction'),
                fn(Builder $builder) => $builder->orderBy(request('column'), request('direction'))
            )
            ->get();
    }

    public function getEmptyStockCount()
    {
        return InventoryLocation::query()
            ->where('inventory_locations.quantity', '0')
            ->count();
    }

    public function updateTriggers(int $id)
    {
        $inventory = InventoryLocation::query()
            ->with(['location'])
            ->join('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select([
                'inventory_locations.*',
                'products.name',
                'products.code',
                'products.description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand',
                'products.category_id',
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
                'inventory_locations.*',
                'products.name',
                'products.code',
                'products.description',
                'products.unit_measurement',
                'products.unit_value',
                'products.brand',
                'products.category_id',
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
            'product_id' => $product instanceof  Product ? $product->id : $product,
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
                'details' => $data['description'] ?? '',
                'product_id' => $product instanceof  Product ? $product->id : $product,
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
            'quantity' => $inventory_location?->quantity || 0,
            'batch' => $this->batchGenerator($inventory_location->id),
            'receive_id' => $user->id,
            'product_id' => $inventory_location->product_id,
            'owner_branch_id' => $user->branch_id
        ]);
    }


    public function transaction(int $inventory_id, array $data)
    {
        $transaction = new InventoryTransaction();
        $transaction->quantity = $data['quantity'];
        $transaction->branch_id = $data['branch_id'];
        $transaction->product_id = $data['product_id'];
        $transaction->returned_by_user_id = $data['returned_by_user_id'] ?? null;
        $transaction->returned_by_branch_id = $data['returned_by_branch_id'] ?? null;
        $transaction->transacted_by_id = $data['transacted_by_id'] ?? request()->user()->id;
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
        $transaction->price = $data['price'] ?? 0;
        $transaction->movement = $data['movement'];
        $transaction->inventory_id = $inventory_id;
        $transaction->save();
    }



    public function resolveProduct(int|Product $product, int $branch_id = null)
    {
        if (!($product instanceof  Product)) {
            $product = Product::query()->findOrFail($product);
        }

        $user = request()->user();
        // $business_unit = $user->business_unit ?: request()->get('business_unit') ?: request()->header('business-unit') ?: null;

        return InventoryLocation::query()->firstOrCreate([
            'product_id' => $product->id,
            'branch_id' => $branch_id ?? $user->branch_id,
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
                'price' => $data['price'] ?? $stock->price,
                'movement' => InventoryMovementType::In,
                'action' => $data['action'] ?? InventoryActionType::Auto,
                'details' => $data['description'] ?? '',
                'product_id' => $product instanceof  Product ? $product->id : $product,
            ]);
            $inventoryLocation->increment('total_quantity', $amount);
            $inventoryLocation->increment('quantity', $amount);
            if ($data['description'] == 'updated beginning balance') {
                $inventoryLocation->begining_balance = $amount;
                $inventoryLocation->price =  $data['price'];
                $inventoryLocation->save();
            }

            return InventoryLocationResource::make($inventoryLocation);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    public function stockAdjustments(
        int|Product $product,
        int $amount, //correction_amount
        array $data = [],
        int $branch_id = 0
    ) {
        try {
            if ($amount === 0) {
                throw ValidationException::withMessages([
                    'correction_amount' => ['Correction amount must not be zero.'],
                ]);
            }

            $inventoryLocation = $this->resolveProduct($product, $branch_id);

            $stock = $this->resolveStockInventory($inventoryLocation);
            $delta = $amount;

            $this->adjustInventoryQuantity($stock, $delta);
            $this->transaction($stock->id, [
                'quantity' => abs($delta),
                'branch_id' => $inventoryLocation->branch_id,
                'transacted_by_id' => $data['transacted_by_id'],
                'accepted_by_id' => $data['accepted_by_id'],
                'from_request_id' => $data['from_request_id'] ?? null,
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'price' => $stock->price,
                'movement' => $delta > 0 ? InventoryMovementType::In : InventoryMovementType::Out,
                'action' => InventoryActionType::Manual,
                'details' => $data['correction_reason'],
                'product_id' => $product instanceof  Product ? $product->id : $product,
            ]);
            $this->adjustInventoryLocationQuantities($inventoryLocation, $delta);

            return InventoryLocationResource::make($inventoryLocation->fresh());
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function stockOut(int|Product $product, int $amount /*quantity*/, array $data = [], int $branch_id = 0)
    {
        try {
            DB::beginTransaction();
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Quantity must be greater than zero.'],
                ]);
            }

            $inventoryLocation = $this->resolveProduct($product, $branch_id);

            $stock = $this->resolveStockInventory($inventoryLocation);

            $this->adjustInventoryQuantity($stock, -$amount);
            $transactionData = [
                'quantity' => $amount,
                'branch_id' => $inventoryLocation->branch_id,
                'transacted_by_id' => $data['transacted_by_id'],
                'accepted_by_id' => $data['accepted_by_id'],
                'from_branch_id' => $data['from_branch_id'],
                'price' => $data['price'] ?? $inventoryLocation->price,
                'from_request_id' => $data['from_request_id'] ?? null,
                'to_branch_id' => $data['to_branch_id'],
                'movement' => InventoryMovementType::Out,
                'action' => $data['action'] ?? InventoryActionType::Auto,
                'details' => $data['description'] ?? '',
                'product_id' => $stock->product_id,
            ];
            $this->transaction($stock->id, $transactionData);
            $this->adjustInventoryLocationQuantities($inventoryLocation, -$amount);
            DB::commit();
            return  ['$transactionData' => $transactionData];
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    private function adjustInventoryQuantity(Inventory $inventory, int $delta): void
    {
        $nextQuantity = (int) $inventory->quantity + $delta;

        if ($nextQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient stock quantity for this operation.'],
            ]);
        }

        $inventory->quantity = $nextQuantity;
        $inventory->save();
    }

    private function adjustInventoryLocationQuantities(InventoryLocation $inventoryLocation, int $delta): void
    {
        $nextTotalQuantity = (int) $inventoryLocation->total_quantity + $delta;
        $nextQuantity = (int) $inventoryLocation->quantity + $delta;

        if ($nextTotalQuantity < 0 || $nextQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient stock quantity for this operation.'],
            ]);
        }

        $inventoryLocation->total_quantity = $nextTotalQuantity;
        $inventoryLocation->quantity = $nextQuantity;
        $inventoryLocation->save();
    }

    public function countItemWithNoInventoryRecords($branch_id = 0)
    {
        $user = request()->user();

        $branchId = $branch_id > 1 ? $branch_id : $user->branch_id;

        // $product_ids_with_location = InventoryLocation::query()
        //     ->where('branch_id', $branch_id > 1 ? $branch_id : $user->branch_id)
        //     ->pluck('product_id');
        $product_ids = Product::query()->whereNotIn('id', function ($query) use ($branchId) {
            $query->select('product_id')
                ->from('inventory_locations')
                ->where('branch_id', $branchId);
        })->limit(500)->pluck('id');

        return $product_ids;
    }

    public function getCountItemWithNoInventoryRecords($branch_id = 0)
    {
        $user = request()->user();

        $branchId = $branch_id > 1 ? $branch_id : $user->branch_id;

        // $product_ids_with_location = InventoryLocation::query()
        //     ->where('branch_id', $branch_id > 1 ? $branch_id : $user->branch_id)
        //     ->pluck('product_id');
        // $count = Product::query()->whereNotIn('id', $product_ids_with_location)->pluck('id')->count();


        $count =  Product::query()
            ->whereNotIn('id', function ($query) use ($branchId) {
                $query->select('product_id')
                    ->from('inventory_locations')
                    ->where('branch_id', $branchId);
            })
            ->count();

        return $count;
    }

    public function populateInventories()
    {

        try {
            DB::beginTransaction();
            $user = request()->user();
            $branch_id = request()->location_id ? request()->location_id : $user->branch_id;
            $ids = $this->countItemWithNoInventoryRecords($branch_id);

            $data = [];
            foreach ($ids as $id) {
                $data[] = ['product_id' => $id, 'branch_id' => $branch_id];
            }
            $locations_data = [];
            foreach (array_chunk($data, 150) as $dataToInsert) {
                foreach ($dataToInsert as $data_) {
                    $locations_data[] = InventoryLocation::firstOrCreate($data_, $data_);
                }
            }
            // $data[] = ['product_id' => $ids[0][0], 'branch_id' => $branch_id];


            $inventories = [];

            foreach (array_chunk($locations_data, 150) as $locations_) {
                foreach ($locations_ as $location) {
                    $i_data = [
                        'inventory_location_id' => $location->id,
                        'quantity' => $location->quantity ?? 0,
                        'receive_id' => $user->id,
                        'branch_id' => $branch_id,
                        'product_id' => $location->product_id
                    ];
                    $inventories[] =  Inventory::firstOrCreate([
                        'inventory_location_id' => $location->id,
                        'product_id' => $location->product_id
                    ], $i_data, $i_data);
                }
            }

            DB::commit();

            return  ['locations' => $locations_data, 'inventories' => $inventories];
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
}
