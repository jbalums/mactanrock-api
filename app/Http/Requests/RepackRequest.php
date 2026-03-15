<?php

namespace App\Http\Requests;

use App\Models\InventoryLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepackRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'qty' => ['required', 'integer', 'min:1'],
            'output_product_id' => ['required', Rule::exists('products', 'id'), 'different:product_id'],
            'output_qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $availableQuantity = (int) (InventoryLocation::query()
                ->where('product_id', $this->integer('product_id'))
                ->where('branch_id', $this->user()->branch_id)
                ->value('quantity') ?? 0);

            if ((int) $this->input('qty') > $availableQuantity) {
                $validator->errors()->add('qty', 'Repack quantity exceeds available stock for the selected product.');
            }
        });
    }
}
