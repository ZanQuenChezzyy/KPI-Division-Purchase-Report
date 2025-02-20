<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $purchaseOrder): void
    {
        // Cek apakah Purchase Order sudah dikonfirmasi
        if (!$purchaseOrder->is_confirmed) {
            return; // Jika belum dikonfirmasi, hentikan proses
        }

        // Ambil semua item dari Purchase Requisition terkait
        $requisitionItems = $purchaseOrder->PurchaseRequisition->purchaseRequisitionItems;

        foreach ($requisitionItems as $item) {
            PurchaseOrderLine::create([
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_requisition_item_id' => $item->id,
                'item_id' => $item->item_id,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'total_price' => $item->qty * $item->unit_price,
                'status' => '0', // Default status
            ]);
        }
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->is_confirmed && $purchaseOrder->getOriginal('is_confirmed') == false) {
            $requisitionItems = $purchaseOrder->PurchaseRequisition->purchaseRequisitionItems;

            foreach ($requisitionItems as $item) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_requisition_item_id' => $item->id,
                    'item_id' => $item->item_id,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->qty * $item->unit_price,
                    'status' => '0',
                ]);
            }
        }
    }

    /**
     * Handle the PurchaseOrder "deleted" event.
     */
    public function deleted(PurchaseOrder $purchaseOrder): void
    {
        //
    }

    /**
     * Handle the PurchaseOrder "restored" event.
     */
    public function restored(PurchaseOrder $purchaseOrder): void
    {
        //
    }

    /**
     * Handle the PurchaseOrder "force deleted" event.
     */
    public function forceDeleted(PurchaseOrder $purchaseOrder): void
    {
        //
    }
}
