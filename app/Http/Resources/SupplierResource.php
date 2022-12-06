<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'address' => $this->address,
            'street' => $this->street,
            'tin' => $this->tin,
            'code' => $this->code,
            'owner' => $this->owner,
            'contacts' => $this->whenLoaded('contacts'),
            'banks' => $this->whenLoaded('banks'),
        ];
    }
}
