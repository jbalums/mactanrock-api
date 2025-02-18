<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisitionRequest extends FormRequest
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
            'project_code' => ['required', 'string', 'max:255'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'inventory_id' => ['required', 'array'],
            'purpose' => ['required', 'string'],
            'inventory_id.*' => ['required', Rule::exists('inventory_locations', 'id') /*->where(fn($q) => $q->where('branch_id','!=',$this->user()->branch_id))*/],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'date_needed' => ['required', 'date'],
        ];
    }
}
