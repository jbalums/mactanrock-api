<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeginningBalanceRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
        ];
    }
}
