<?php

namespace App\Http\Requests;

use App\Models\InventoryLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCorrectionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'correction_amount' => ['required', 'integer', 'not_in:0'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'correction_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $amount = (int) $this->input('correction_amount');

            if ($amount >= 0) {
                return;
            }

            $branchId = (int) ($this->input('branch_id') ?: $this->user()->branch_id);
            $availableQuantity = (int) (InventoryLocation::query()
                ->where('product_id', $this->integer('product_id'))
                ->where('branch_id', $branchId)
                ->value('quantity') ?? 0);

            if (abs($amount) > $availableQuantity) {
                $validator->errors()->add('correction_amount', 'Correction amount exceeds available stock for the selected branch.');
            }
        });
    }
}
