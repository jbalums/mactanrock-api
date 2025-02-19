<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Panoscape\History\HasHistories;

class Requisition extends Model
{
    use HasFactory;
    use HasHistories;

    public function getModelLabel()
    {
        return $this->display_name;
    }

    protected $dates = ['needed_at', 'date_approved', 'date_declined'];


    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequisitionDetail::class);
    }

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function acceptor()
    {
        return $this->belongsTo(User::class, 'accepted_by_id');
    }
    public function declinedBy()
    {
        return $this->belongsTo(User::class, 'declined_by_id');
    }

    public function location()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
