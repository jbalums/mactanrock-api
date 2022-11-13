<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionDetailsResource extends JsonResource
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
            'location' => BranchResource::make($this->whenLoaded('location')),
            'status' => $this->status,
            'items' => RequestItemResource::collection($this->whenLoaded('items')),
            'requisition' => RequisitionResource::make($this->whenLoaded('requisition')),
        ];
    }
}
