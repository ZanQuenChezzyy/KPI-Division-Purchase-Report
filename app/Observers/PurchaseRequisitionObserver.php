<?php

namespace App\Observers;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PurchaseRequisitionObserver
{
    /**
     * Handle the PurchaseRequisition "created" event.
     */
    public function created(PurchaseRequisition $purchaseRequisition): void
    {
        if (Auth::check()) {
            $recepient = Auth::user();
            $userFullName = $recepient->name;

            Notification::make()
                ->title($userFullName . ' has Added a new Purchase Requisition: Number - ' . $purchaseRequisition->number)
                ->body('Details of the new Purchase Requisition have been added successfully. Please review the requisition for approval or further actions.')
                ->sendToDatabase($recepient);
        }
    }

    /**
     * Handle the PurchaseRequisition "updated" event.
     */
    public function updated(PurchaseRequisition $purchaseRequisition): void
    {
        if (Auth::check()) {
            $recepient = Auth::user();
            $userFullName = $recepient->name;

            Notification::make()
                ->title($userFullName . ' has Updated Purchase Requisition: Number - ' . $purchaseRequisition->number)
                ->body('The Purchase Requisition number ' . $purchaseRequisition->number . ' has been updated. Please review the latest changes made to the requisition.')
                ->sendToDatabase($recepient);
        }
    }

    /**
     * Handle the PurchaseRequisition "deleted" event.
     */
    public function deleted(PurchaseRequisition $purchaseRequisition): void
    {
        if (Auth::check()) {
            $recepient = Auth::user();
            $userFullName = $recepient->name;

            Notification::make()
                ->title($userFullName . ' has Deleted Purchase Requisition: Number - ' . $purchaseRequisition->number)
                ->body('The Purchase Requisition with number ' . $purchaseRequisition->number . ' has been deleted. If this was a mistake, please restore the requisition as needed.')
                ->sendToDatabase($recepient);
        }
    }

    /**
     * Handle the PurchaseRequisition "restored" event.
     */
    public function restored(PurchaseRequisition $purchaseRequisition): void
    {
        //
    }

    /**
     * Handle the PurchaseRequisition "force deleted" event.
     */
    public function forceDeleted(PurchaseRequisition $purchaseRequisition): void
    {
        //
    }
}
