<?php

namespace App\Models;

use App\Enums\ReceivingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Panoscape\History\HasHistories;

class Receive extends Model
{
    use HasFactory;
    use HasHistories;

    public function getModelLabel()
    {
        return $this->display_name;
    }

    protected $casts = [
        'status' => ReceivingStatus::class,
    ];

    protected $dates = ['date_receive'];

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReceiveDetail::class, 'receive_id');
    }
}
