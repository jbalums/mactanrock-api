<?php

namespace App\Http\Requests;

use App\Enums\ReceivingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ReceiveRequest extends FormRequest
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
            'purchase_order' => ['required','string','max:255', Rule::unique('receives','purchase_order')],
            'supplier_id' => ['nullable', Rule::exists('suppliers','id')],
            'project_name' => ['required','string','max:255'],
            'status' => ['nullable', new Enum(ReceivingStatus::class)],
            'products' => ['required','array'],
            'products.*'  => ['required',Rule::exists('products','id')],
            'quantity' => ['required','array'],
            'quantity.*' => ['required','integer','min:1'],
            'expired_at' => ['nullable','array'],
            'expired_at.*' => ['nullable','date','after:tomorrow'],
            'date_receive' => ['required', 'date','before:tomorrow']
        ];
    }
}
