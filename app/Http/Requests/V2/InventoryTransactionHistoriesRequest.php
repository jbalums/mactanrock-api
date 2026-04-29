<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionHistoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sortableColumns = [
            'id',
            'created_at',
            'updated_at',
            'quantity',
            'movement',
            'action',
        ];

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'paginate' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', Rule::in($sortableColumns)],
            'sort' => ['nullable', Rule::in($sortableColumns)],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
