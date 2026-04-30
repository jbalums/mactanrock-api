<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

class RequisitionDiscrepancyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'paginate' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
