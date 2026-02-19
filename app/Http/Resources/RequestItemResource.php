<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequestItemResource extends JsonResource
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
            'product' => ProductResource::make($this->whenLoaded('product')),
            'inventory' => $this->whenLoaded('inventory') ? $this->inventory : null,
            'request_quantity' => $this->request_quantity,
            'full_filled_quantity' => $this->full_filled_quantity,
            'status' => $this->status,
            'used_qty' => $this->used_qty,
            'returned_qty' => $this->returned_qty,
        ];
    }
}
