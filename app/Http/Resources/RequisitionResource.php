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
            'created_at' => $this->created_at->format('M d, Y'),
            'date_needed' => $this->date_needed->format('M d, Y'),
        ];
    }
}
