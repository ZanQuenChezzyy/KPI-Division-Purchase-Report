<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseRequisitionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('Read Purchase Requisitions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('Read Purchase Requisitions');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Purchase Requisitions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('Update Purchase Requisitions');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('Delete Purchase Requisitions');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('Delete Purchase Requisitions');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('Delete Purchase Requisitions');
    }
}
