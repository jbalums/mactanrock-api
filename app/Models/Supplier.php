<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function banks()
    {
        return $this->hasMany(SupplierBank::class);
    }

    public function contacts()
    {
        return $this->hasMany(SupplierContact::class);

    }
}
