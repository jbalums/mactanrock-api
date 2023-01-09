<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
            'name' => ['required','string','max:100'],
            'address' => ['required','string','max:225'],
            'street' => ['required','string','max:225'],
            'owner' => ['required','string','max:100'],
            'tin' => ['required','string','max:100'],
            'code' => ['required','string','max:225', Rule::unique('suppliers','code')->ignore($this->id)],
            'contacts' => ['required','array'],
            'contacts.*.name' => ['required','string'],
            'contacts.*.number' => ['required','string'],
            'contacts.*.position' => ['required','string'],
            'contacts.*.email' => ['required','string','email','max:100'],
            'banks' => ['required','array'],
            'banks.*.name' => ['required','string'],
            'banks.*.account_name' => ['required','string'],
            'banks.*.account_number' => ['required','string'],
            'banks.*.location' => ['required','string'],
            'gl_account' => ['required','string','max:225']

        ];
    }
}
