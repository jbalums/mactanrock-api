<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'brand' => $this->brand ?? "",
            'description' =>  $this->description,
            'unit_value' => $this->unit_value,
            'unit_measurement' => $this->unit_measurement,
            'category_id' => $this->category_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            $this->mergeWhen(!is_null($this->total_quantity), fn() => [
                'location' => BranchResource::make($this->whenLoaded('location')),
                'total_quantity' => $this->total_quantity,
                'quantity' => $this->quantity,
                'price' => $this->price,
                'product_id' => $this->productId,
                'stock_low_level' => $this->stock_low_level ?: 0,
                'reorder_point' => $this->reorder_point ?: 0,
                'business_unit' => getUnit($this->business_unit),
                'unit_code' => $this->business_unit ?? "",
                'stocks' => $this->quantity ? ($this->quantity <= $this->reorder_point ? "reorder" : ($this->quantity <= $this->stock_low_level ? "low" : "")):"out",
            ])
        ];
    }
}
