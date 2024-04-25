<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'quantity' => $this->quantity,
            'price' => $this->price,
            'batch' => $this->batch,
            'expired_at' => $this->expired_at,
            'description' => $this->description,
            'action' => $this->action,
            'sellable' => $this->sellable,
            'from_branch_id' => $this->from_branch_id,
            'from_supplier_id' => $this->from_supplier_id,
            'from_request_id' => $this->from_request_id,
            'receive_id' => $this->receive_id,
            'product_id' => $this->product_id,
            'inventory_location_id' => $this->inventory_location_id,
            'account_code' => $this->account_code,
            'receives' => ReceiveResource::make($this->whenLoaded('receives')),
            'product' => $this->product,
        ];
    }
}
