<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $quantity = (int) $this->quantity;
        $totalQuantity = (int) $this->total_quantity;
        $price = (float) $this->price;

        return [
            'id' => $this->id,
            'type' => 'inventory_location',
            'product_id' => $this->product_id,
            'branch_id' => $this->branch_id,
            'business_unit' => $this->business_unit,
            'quantity' => $quantity,
            'total_quantity' => $totalQuantity,
            'available_quantity' => $quantity,
            'price' => $price,
            'inventory_value' => round($quantity * $price, 2),
            'stock_low_level' => (int) $this->stock_low_level,
            'reorder_point' => (int) $this->reorder_point,
            'beginning_balance' => $this->begining_balance === null ? null : (int) $this->begining_balance,
            'stock_status' => $this->stockStatus(),
            'is_manageable' => $this->isManageable($request),
            'product' => $this->whenLoaded('product', fn () => $this->productPayload()),
            'branch' => $this->whenLoaded('branch', fn () => $this->branchPayload()),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }

    private function productPayload(): ?array
    {
        if (!$this->product) {
            return null;
        }

        return [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'code' => $this->product->code,
            'account_code' => $this->product->account_code,
            'brand' => $this->product->brand,
            'description' => $this->product->description,
            'unit_measurement' => $this->product->unit_measurement,
            'unit_value' => $this->product->unit_value,
            'category_id' => $this->product->category_id,
            'category' => $this->product->relationLoaded('category') && $this->product->category
                ? [
                    'id' => $this->product->category->id,
                    'name' => $this->product->category->name,
                ]
                : null,
        ];
    }

    private function branchPayload(): ?array
    {
        if (!$this->branch) {
            return null;
        }

        return [
            'id' => $this->branch->id,
            'name' => $this->branch->name,
            'code' => $this->branch->code,
        ];
    }

    private function stockStatus(): string
    {
        $quantity = (int) $this->quantity;
        $reorderPoint = (int) $this->reorder_point;
        $stockLowLevel = (int) $this->stock_low_level;

        if ($quantity <= 0) {
            return 'out';
        }

        if ($reorderPoint > 0 && $quantity <= $reorderPoint) {
            return 'reorder';
        }

        if ($stockLowLevel > 0 && $quantity <= $stockLowLevel) {
            return 'low';
        }

        return 'in_stock';
    }

    private function isManageable($request): bool
    {
        $user = $request->user();

        return (int) $user->branch_id === 1 || (int) $this->branch_id === (int) $user->branch_id;
    }
}
