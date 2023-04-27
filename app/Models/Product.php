<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'name',
        'code',
        'description',
        'unit_measurement',
        'unit_value',
        'stock_low_level',
        'reorder_point',
        'brand',
        'category_id',
        'account_code',
    ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

}
