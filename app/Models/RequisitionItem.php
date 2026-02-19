<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Panoscape\History\HasHistories;

class RequisitionItem extends Model
{
    use HasFactory;
    use HasHistories;

    public function getModelLabel()
    {
        return $this->display_name;
    }

    protected $guarded = [];

    public function requisitionDetail()
    {
        return $this->hasOne(RequisitionDetail::class, 'id', 'requisition_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function inventory()
    {
        return $this->hasOne(InventoryLocation::class, 'product_id', 'product_id')
            ->when(
                $this->requisitionDetail?->location_id,
                fn($q, $locationId) => $q->where('location_id', $locationId)
            );
    }
}
