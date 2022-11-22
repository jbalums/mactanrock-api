<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\InventoryServices;

class TriggersController extends Controller
{

    public function update(InventoryServices $services, int $id)
    {
        request()->validate([
            'stock_level' => ['required','integer','min:0'],
            'reorder_point'=> ['required','integer','min:0'],
        ]);

        return ProductResource::make($services->updateTriggers($id));



    }
}