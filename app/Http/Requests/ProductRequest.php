<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        return [
            'name' => ['required','string','max:255'],
            'code' => ['required','string', Rule::unique('products','code')->ignore($this->product)],
            'unit_measurement' => ['required','string','max:255'],
            'description' => ['required','string','max:7000'],
            'unit_value' => ['required','numeric'],
            'stock_low_level' => ['required','integer','min:1'],
            'reorder_point' => ['required','integer','min:1'],
            'price' => ['required','numeric','min:1'],

        ];
    }
}
