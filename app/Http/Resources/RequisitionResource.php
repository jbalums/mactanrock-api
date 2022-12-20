<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
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
            'project_code' => $this->project_code,
            'requester' => UserResource::make($this->whenLoaded('requester')),
            'details' => RequisitionDetailsResource::collection($this->whenLoaded('details')),
            'location' => BranchResource::make($this->whenLoaded('location')),
            'created_at' => $this->created_at?->format('M d, Y') ?: "",
            'date_needed' => $this->needed_at?->format('M d, Y') ?: "",
            'date_approved' => $this->date_approved?->format('M d, Y') ?: "",
            "status" => $this->status,
            'accepted_by' => UserResource::make($this->whenLoaded('acceptor')),

        ];
    }
}
