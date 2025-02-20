<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'vendor_id',
        'buyer',
        'is_confirmed',
        'is_received',
        'is_closed',
        'confirmed_at',
        'received_at',
        'closed_at',
    ];

    public function Vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id', 'id');
    }

    public function PurchaseRequisition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseRequisition::class, 'purchase_requisition_id', 'id');
    }

    public function UserDepartment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\UserDepartment::class, 'buyer', 'id');
    }

    public function purchaseOrderLines(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\PurchaseOrderLine::class);
}

}