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
        'eta',
        'mar_no',
        'is_confirmed',
        'is_received',
        'is_closed',
        'confirmed_at',
        'received_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::updated(function ($purchaseOrder) {
            // Logika ketika is_received berubah
            if ($purchaseOrder->isDirty('is_received')) {
                // Tambahkan logika lain jika perlu
                // Contoh: Logging atau trigger action
                logger("Purchase Order {$purchaseOrder->id} status updated to: " . ($purchaseOrder->is_received ? 'Received' : 'Not Received'));
            }
        });
    }

    public function Vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id', 'id');
    }

    public function PurchaseRequisition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseRequisition::class, 'purchase_requisition_id', 'id');
    }

    public function User(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer', 'id');
    }

    public function purchaseOrderLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PurchaseOrderLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
