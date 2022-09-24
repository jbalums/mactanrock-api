<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductServices
{

    public function get()
    {
        return Product::query()
            ->latest()
            ->get();
    }
    public function create(Request $request)
    {
        $product = new Product();
        $this->itemInformation($request, $product);
        $product->branch_id = $request->get('branch_id', 1);

        $product->save();

        return $product;
    }

    public function update(Request $request , int $id)
    {
        $product = Product::query()->where('branch_id', $request->get('branch_id', 1))->findOrFail($id);
        $this->itemInformation($request, $product);
        $product->save();

        return $product;
    }

    /**
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null $product
     * @return void
     */
    public function itemInformation(Request $request, \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null $product): void
    {
        $product->name = $request->get('name');
        $product->code = $request->get('code');
        $product->description = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $request->get('description', ''));
        $product->category_id = $request->get('category_id');
        $product->unit_measurement = $request->get('unit_measurement');
        $product->unit_value = $request->get('unit_value');
        $product->price = $request->get('price');
        $product->stock_low_level = $request->get('stock_low_level');
        $product->reorder_point = $request->get('reorder_point');

    }
}