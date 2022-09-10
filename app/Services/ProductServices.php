<?php

namespace App\Services;

use App\Enums\InventoryActionType;
use App\Models\Product;

class ProductServices
{

    public function create()
    {
        $request = request();
        $product = new Product();
        $product->name = $request->get('name');
        $product->description = $request->get('description');
        $product->category_id = $request->get('category_id');
        $product->unit_measurement = $request->get('unit_measurement');
        $product->unit_value = $request->get('unit_value');
        $product->price = $request->get('price');
        $product->branch_id = $request->get('branch_id');
        $product->save();

        if($request->has('initial_value') && $request->get('initial_value') > 0){
            $inventory = new InventoryServices();
            $inventory->in($product,$request->get('initial_value'),[
                'from' => 'from_branch_id',
                'from_value' =>  $request->get('branch_id'),
                'description' => "Initial Inventory",
                'action' => InventoryActionType::Manual,
                'price' => $request->get('price'),
                'expired_at' => $request->get('expired_at')
            ]);

            $product->refresh();
        }

        return $product;
    }
}