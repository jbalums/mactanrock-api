<?php

namespace App\Http\Controllers\Managements;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductServices;

class ProductsController extends Controller
{

    public function index(ProductServices $services)
    {
        return ProductResource::collection($services->get());
    }

    public function store(ProductRequest $request, ProductServices $services)
    {
       return ProductResource::make($services->create($request));
    }
    public function update(ProductRequest $request, ProductServices $services, int $id)
    {
        return ProductResource::make($services->update($request,$id));
    }
}