<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseType extends Model
{
    use HasFactory;

    public $incrementing = false; // Nonaktifkan auto-increment pada ID
    protected $keyType = 'int'; // Pastikan ID tetap integer

    protected $fillable = ['id', 'name'];

    public function purchaseRequisitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseRequisition::class);
    }
}
