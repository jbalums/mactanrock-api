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
            'description' =>  $this->description,
            'stock' => $this->quantity ? ($this->quantity <= $this->reorder_point ? "reorder" : ($this->quantity <= $this->stock_low_level ? "low" : "")):"out",
            'price' => $this->price,
            'price_formatted' => number_format($this->price, 2, '.', ','),
            'quantity' => $this->quantity ?: 0,
            'unit_value' => $this->unit_value,
            'unit_measurement' => $this->unit_measurement,
            'stock_low_level' => $this->stock_low_level ?: 0,
            'reorder_point' => $this->reorder_point ?: 0,
            'location' => BranchResource::make($this->whenLoaded('location'))

        ];
    }
}
