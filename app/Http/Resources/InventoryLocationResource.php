<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLocationResource extends JsonResource
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
            'product_id' => $this->product_id,
            'branch_id' => $this->branch_id,
            'price' => $this->price,
            'total_quantity' => $this->total_quantity,
            'quantity' => $this->quantity,
            'business_unit' => $this->business_unit,
            'stock_low_level' => $this->stock_low_level,
            'reorder_point' => $this->reorder_point,
            'begining_balance' => $this->begining_balance,
            'product' => $this->product ? $this->product : null,
            'branch' => $this->branch ? $this->branch : null,
            'inventory' => $this->inventory ? $this->inventory : null,
            'is_manageable' => $this->branch_id == request()->user()->branch_id,
        ];
    }
}
