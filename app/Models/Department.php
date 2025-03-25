<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseRequisition::class);
    }

    public function Users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
