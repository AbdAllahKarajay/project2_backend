<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own service requests
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the status.
     */
    public function updateStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->user_id || $user->isAdmin();
    }
}
