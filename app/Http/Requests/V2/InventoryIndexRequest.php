<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sortableColumns = [
            'name',
            'code',
            'brand',
            'quantity',
            'total_quantity',
            'price',
            'updated_at',
        ];

        return [
            'location_id' => ['nullable', 'integer', 'exists:branches,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'business_unit' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'stock_status' => ['nullable', Rule::in(['in_stock', 'low', 'reorder', 'out'])],
            'sort' => ['nullable', Rule::in($sortableColumns)],
            'column' => ['nullable', Rule::in($sortableColumns)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'paginate' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
