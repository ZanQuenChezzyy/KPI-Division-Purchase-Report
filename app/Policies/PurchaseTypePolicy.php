<?php

namespace App\Policies;

use App\Models\PurchaseType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('Read Purchase Types');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseType $purchaseType): bool
    {
        return $user->can('Read Purchase Types');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Purchase Types');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseType $purchaseType): bool
    {
        return $user->can('Update Purchase Types');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseType $purchaseType): bool
    {
        return $user->can('Delete Purchase Types');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseType $purchaseType): bool
    {
        return $user->can('Delete Purchase Types');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseType $purchaseType): bool
    {
        return $user->can('Delete Purchase Types');
    }
}
