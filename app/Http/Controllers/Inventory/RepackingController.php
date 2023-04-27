<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;

class RepackingController extends Controller
{
    
    public function index(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name','description','quantity','code','brand'])],
            'direction' => ['nullable', Rule::in(['asc','desc'])]
        ]);
        return ProductResource::collection($services->getList());
    }
  
}
