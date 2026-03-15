<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Requisition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectionRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if (!$this->filled('id') && $this->filled('request_account_code')) {
            $this->merge([
                'id' => $this->input('request_account_code'),
            ]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => ['required', 'integer', Rule::exists('requisitions', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'qty' => ['required', 'integer', 'min:1'],
            'movement' => ['required', Rule::in(['in', 'out'])],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $requisition = Requisition::query()->find($this->integer('id'));

            if (!$requisition) {
                return;
            }

            $inventoryLocation = InventoryLocation::query()
                ->where('branch_id', $requisition->branch_id)
                ->where('product_id', $this->integer('product_id'))
                ->first();

            if (!$inventoryLocation) {
                $validator->errors()->add('product_id', 'No inventory record exists for this product at the requisition branch.');
                return;
            }

            $inventory = Inventory::query()
                ->where('inventory_location_id', $inventoryLocation->id)
                ->where('product_id', $this->integer('product_id'))
                ->orderByDesc('id')
                ->first();

            if (!$inventory) {
                $validator->errors()->add('product_id', 'No inventory batch exists for this product at the requisition branch.');
                return;
            }

            if (
                $this->input('movement') === 'out' &&
                (int) $this->input('qty') > (int) $inventoryLocation->quantity
            ) {
                $validator->errors()->add('qty', 'Correction quantity exceeds available stock for the requisition branch.');
            }
        });
    }
}
