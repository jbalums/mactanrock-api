<?php

namespace App\Http\Requests;

use App\Models\InventoryLocation;
use App\Models\RequisitionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsumeItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'requisition_items_id' => array_values($this->input('requisition_items_id', [])),
            'product_id' => array_values($this->input('product_id', [])),
            'qty' => array_values(array_map(
                fn ($qty) => $this->normalizeQuantity($qty),
                $this->input('qty', [])
            )),
        ]);
    }

    public function rules()
    {
        return [
            'requisition_items_id' => ['required', 'array', 'min:1'],
            'requisition_items_id.*' => ['required', 'integer', 'distinct', Rule::exists('requisition_items', 'id')],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'integer', Rule::exists('products', 'id')],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $itemIds = $this->input('requisition_items_id', []);
            $productIds = $this->input('product_id', []);
            $quantities = array_map('intval', $this->input('qty', []));

            if (!$this->arraysMatch($itemIds, $productIds, $quantities)) {
                $validator->errors()->add('qty', 'Line item payloads must have matching array lengths.');
                return;
            }

            if (!collect($quantities)->contains(fn ($qty) => $qty > 0)) {
                $validator->errors()->add('qty', 'At least one item quantity must be greater than zero.');
                return;
            }

            $items = RequisitionItem::query()
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            $requestedByProduct = [];
            $productIndexes = [];

            foreach ($itemIds as $index => $itemId) {
                $item = $items->get((int) $itemId);

                if (!$item) {
                    continue;
                }

                $productId = (int) $productIds[$index];
                $qty = (int) $quantities[$index];

                if ($item->product_id !== $productId) {
                    $validator->errors()->add("product_id.$index", 'Selected product does not match the requisition item.');
                }

                $remainingQuantity = max(
                    0,
                    (int) $item->request_quantity - (int) $item->used_qty - (int) $item->returned_qty
                );

                if ($qty > $remainingQuantity) {
                    $validator->errors()->add("qty.$index", 'Consumed quantity exceeds the remaining requested quantity.');
                }

                if ($qty > 0) {
                    $requestedByProduct[$productId] = ($requestedByProduct[$productId] ?? 0) + $qty;
                    $productIndexes[$productId][] = $index;
                }
            }

            $this->validateStockAvailability($validator, $requestedByProduct, $productIndexes);
        });
    }

    private function validateStockAvailability($validator, array $requestedByProduct, array $productIndexes): void
    {
        if ($requestedByProduct === []) {
            return;
        }

        $availableStocks = InventoryLocation::query()
            ->where('branch_id', $this->user()->branch_id)
            ->whereIn('product_id', array_keys($requestedByProduct))
            ->pluck('quantity', 'product_id');

        foreach ($requestedByProduct as $productId => $qty) {
            $availableQuantity = (int) ($availableStocks[$productId] ?? 0);

            if ($qty > $availableQuantity) {
                foreach ($productIndexes[$productId] ?? [] as $index) {
                    $validator->errors()->add("qty.$index", 'Consumed quantity exceeds available stock for this product.');
                }
            }
        }
    }

    private function arraysMatch(array ...$arrays): bool
    {
        return count(array_unique(array_map('count', $arrays))) === 1;
    }

    private function normalizeQuantity($qty)
    {
        if ($qty === null || $qty === '' || $qty === 'undefined') {
            return 0;
        }

        return $qty;
    }
}
