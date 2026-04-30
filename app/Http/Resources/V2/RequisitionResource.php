<?php

namespace App\Http\Resources\V2;

use App\Http\Resources\BranchResource;
use App\Http\Resources\RequisitionDetailsResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->account_code,
            'project_code' => $this->project_code,
            'project_name' => $this->project_name,
            'account_code' => $this->account_code,
            'branch_id' => $this->branch_id,
            'requester' => UserResource::make($this->whenLoaded('requester')),
            'details' => RequisitionDetailsResource::collection($this->whenLoaded('details')),
            'location' => BranchResource::make($this->whenLoaded('location')),
            'created_at' => $this->created_at?->format('M d, Y') ?: '',
            'date_needed' => $this->needed_at?->format('M d, Y') ?: '',
            'date_approved' => $this->date_approved?->format('M d, Y') ?: '',
            'date_declined' => $this->date_declined?->format('M d, Y') ?: '',
            'status' => $this->status,
            'remarks' => $this->remarks,
            'issuance_status' => $this->issuance_status,
            'purpose' => $this->purpose,
            'has_inventory_transactions' => ((int) ($this->inventory_transactions_count ?? 0)) > 0,
            'accepted_by' => UserResource::make($this->whenLoaded('acceptor')),
            'declined_by' => $this->when(
                $this->relationLoaded('declinedBy') && $this->declinedBy,
                fn() => UserResource::make($this->declinedBy)
            ),
        ];
    }
}
