<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $m_i = substr($this->middlename ?? "", 0, 1);
        $middle = strlen($m_i) > 0 ? $m_i . '.' : '';
        $name = "{$this->firstname} {$middle} {$this->lastname}";

        return array_merge(parent::toArray($request), [
            'business_unit' => $this->business_unit,
            'unit_code' => $this->business_unit ?? "",
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'avatar' => $this->avatar ? Storage::url($this->avatar) : "",
            'name' => trim($name),
            'operations' => $this->operations,
        ]);
    }
}
