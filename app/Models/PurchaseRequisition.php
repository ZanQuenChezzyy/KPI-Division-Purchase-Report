<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'purchase_type_id',
        'description',
        'requested_by',
        'department_id',
        'status',
        'cancelled_at',
        'approved_at',
    ];

    public function Department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id', 'id');
    }

    public function PurchaseType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseType::class, 'purchase_type_id', 'id');
    }

    public function UserDepartment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\UserDepartment::class, 'requested_by', 'id');
    }

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseOrder::class);
    }


    public function purchaseRequisitionItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseRequisitionItem::class);
    }
}
