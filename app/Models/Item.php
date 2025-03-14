<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'unit',
        'unit_price',
        'description',
    ];

    public function purchaseRequisitionItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseRequisitionItem::class);
    }

    public function purchaseOrderLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseOrderLine::class);
    }

}
