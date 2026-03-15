<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'transacted_by_id' => $this->transacted_by_id,
            'accepted_by_id' => $this->accepted_by_id,
            'movement' => $this->movement,
            'to_branch_id' => $this->to_branch_id,
            'to_client_id' => $this->to_client_id,
            'to_assembly_id' => $this->to_assembly_id,
            'from_branch_id' => $this->from_branch_id,
            'from_supplier_id' => $this->from_supplier_id,
            'from_request_id' => $this->from_request_id,
            'receive_id' => $this->receive_id,
            'details' => $this->details,
            'action' => $this->action,
            'inventory_id' => $this->inventory_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'quantity_balance' => $this->quantity_balance ?? 0,
            'inventory' => InventoryResource::make($this->whenLoaded('inventory')),
            'inventory.receives' => InventoryResource::make($this->whenLoaded('inventory.receives')),
            'receive' => ReceiveResource::make($this->whenLoaded('receive')),
            'request' => RequisitionResource::make($this->whenLoaded('request'))
        ];
    }
}
