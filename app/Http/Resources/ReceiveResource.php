<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReceiveResource extends JsonResource
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
            'purchase_order' => $this->purchase_order,
            'status' => $this->status,
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'details' => ReceiveDetailResource::collection($this->whenLoaded('details')),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'created_at' => $this->created_at->format('M d, Y'),
            'date_receive' => $this->date_receive?->format('M d, Y') ?? ""
        ];
    }
}
