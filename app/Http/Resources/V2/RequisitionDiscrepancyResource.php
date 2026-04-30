<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionDiscrepancyResource extends JsonResource
{
    public function toArray($request): array
    {
        $requestQuantity = (int) $this->request_quantity;
        $fulfilledQuantity = (int) $this->full_filled_quantity;
        $issuedOutQuantity = (int) $this->issued_out_quantity;
        $discrepancyTypes = $this->discrepancyTypes(
            $requestQuantity,
            $fulfilledQuantity,
            $issuedOutQuantity
        );
        $discrepancyLabels = $this->discrepancyLabels($discrepancyTypes);

        return [
            'requisition_id' => (int) $this->requisition_id,
            'requisition_detail_id' => (int) $this->requisition_detail_id,
            'requisition_item_id' => (int) $this->requisition_item_id,
            'requisition' => [
                'id' => (int) $this->requisition_id,
                'project_code' => $this->project_code,
                'project_name' => $this->project_name,
                'account_code' => $this->account_code,
                'status' => $this->requisition_status,
                'issuance_status' => $this->issuance_status,
                'purpose' => $this->purpose,
                'created_at' => $this->requisition_created_at,
                'branch_id' => (int) $this->destination_branch_id,
                'discrepancy_types' => $discrepancyTypes,
                'discrepancy_labels' => $discrepancyLabels,
            ],
            'product' => [
                'id' => (int) $this->product_id,
                'name' => $this->product_name,
                'code' => $this->product_code,
                'description' => $this->product_description,
            ],
            'source_inventory' => [
                'exists' => !is_null($this->source_inventory_location_id),
                'inventory_location_id' => $this->source_inventory_location_id ? (int) $this->source_inventory_location_id : null,
                'branch_id' => (int) $this->source_branch_id,
                'quantity' => is_null($this->source_inventory_quantity) ? null : (int) $this->source_inventory_quantity,
                'total_quantity' => is_null($this->source_inventory_total_quantity) ? null : (int) $this->source_inventory_total_quantity,
                'price' => is_null($this->source_inventory_price) ? null : (float) $this->source_inventory_price,
            ],
            'quantities' => [
                'requested' => $requestQuantity,
                'fulfilled' => $fulfilledQuantity,
                'issued_out' => $issuedOutQuantity,
            ],
            'discrepancy_types' => $discrepancyTypes,
            'discrepancy_labels' => $discrepancyLabels,
            'has_discrepancy' => true,
        ];
    }

    private function discrepancyTypes(
        int $requestQuantity,
        int $fulfilledQuantity,
        int $issuedOutQuantity
    ): array {
        $types = [];

        if (is_null($this->source_inventory_location_id)) {
            $types[] = 'missing_inventory_location';
        }

        if ($fulfilledQuantity > $requestQuantity) {
            $types[] = 'fulfilled_gt_requested';
        }

        if ($fulfilledQuantity > 0 && $issuedOutQuantity !== $requestQuantity) {
            $types[] = 'issued_qty_mismatch';
        }

        if ($fulfilledQuantity > 0 && $issuedOutQuantity === 0) {
            $types[] = 'missing_transactions';
        }

        if (
            in_array($this->requisition_status, ['completed'], true)
            || in_array($this->issuance_status, ['completed'], true)
        ) {
            if ($fulfilledQuantity !== $requestQuantity) {
                $types[] = 'fulfilled_qty_mismatch';
            }
        }

        return array_values(array_unique($types));
    }

    private function discrepancyLabels(array $types): array
    {
        $labels = [
            'missing_inventory_location' => 'Missing inventory location',
            'fulfilled_gt_requested' => 'Fulfilled quantity is greater than requested quantity',
            'fulfilled_qty_mismatch' => 'Fulfilled quantity does not match requested quantity',
            'issued_qty_mismatch' => 'Issued quantity does not match requested quantity',
            'missing_transactions' => 'Missing inventory transactions',
        ];

        return array_values(array_map(
            fn(string $type) => $labels[$type] ?? $type,
            $types
        ));
    }
}
