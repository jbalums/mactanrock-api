<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserLogsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function toArray($request)
    {
        $model = null;

        if (class_exists($this->model_type)) {
            $model = $this->model_type::find($this->model_id);
        }
        return [
            'id' => $this->id,
            'message' => $this->message,
            'meta' => $this->meta,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'performed_at' => $this->performed_at,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'model' => $model
        ];
    }
}
