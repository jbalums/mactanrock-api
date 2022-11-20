<?php

namespace App\Services;

use App\Enums\InventoryActionType;
use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Builder;

class InventoryServices
{

    public function getList()
    {
        return InventoryLocation::query()
            ->with(['location'])
            ->join('products','inventory_locations.product_id','=','products.id')
            ->select(['inventory_locations.*','products.*', "products.id as productId"])
            ->when( request('location_id'), fn(Builder $builder) => $builder->where('branch_id',request('location_id') ))
            ->paginate( request('paginate') ?:12 );
    }
    public function in(int|Product $product, int $quantity, array $data = [])
    {
        $inventoryLocation = $this->resolveProduct($product);
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

        $this->transaction($inventory->id,[
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
        return Inventory::query()->where('inventory_location_id',$inventoryLocation)->count() + 1;
    }


    public function out(int|InventoryLocation $product, int $amount, array $data = [])
    {
        $inventoryLocation = $this->resolveProduct($product);
        if($inventoryLocation->total_remaining > 0){
            $stock = $this->getNonEmptyStock($product->id);
            if(!$stock)
                return $product;

            if($amount > $stock->amount){
                $stock->amount->decrement($stock->amount);
                $amount = $amount - $stock->amount;
            }else{
                $stock->amount->decrement($amount);
                $amount = 0;
            }
            $this->transaction($stock->id,[
                'amount' => $amount,
                'branch_id' => $inventoryLocation->branch_id,
                'transacted_by_id' => $data['user_id'],
                'from_branch_id' => $inventoryLocation->branch_id,
                'to_branch_id' => $data['branch_id'],
                'movement' => InventoryMovementType::Out,
                'action' => $data['action'] ?? InventoryActionType::Auto,
                'details' => $data['description']
            ]);
            if($amount > 0){
                $this->out($inventoryLocation,$amount);
            }
        }
        $product->total_remaining->decrement($amount);

        return $product;
    }

    public function getNonEmptyStock(int $product_id)
    {
        return Inventory::query()
            ->where('product_id', $product_id)
            ->where('amount','>',0)
            ->where('sellable',1)
            ->orderBy('batch','asc')
            ->first();
    }


    private function transaction(int $inventory_id, array $data)
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
        $transaction->from_request_id= $data['from_request_id'] ?? null;
        $transaction->receive_id= $data['receive_id'] ?? null;
        $transaction->details = $data['details'] ?? '';
        $transaction->action = $data['action'];
        $transaction->movement = $data['movement'];
        $transaction->inventory_id = $inventory_id;
        $transaction->save();
    }



    private function resolveProduct(int|Product $product): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|Product|int|array|null
    {
        if(!($product instanceof  Product)){
            $product = Product::query()->findOrFail($product);
        }

        $user = request()->user();

        return InventoryLocation::query()->firstOrCreate([
            'product_id' => $product->id,
            'branch_id' => $user->branch_id
        ]);



    }
}