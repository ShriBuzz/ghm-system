<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Grievance;
use App\Models\User;

/**
 * Case access by role (A1, A5). Visibility comes from Grievance::visibleTo(),
 * so the policy and every list agree. No one may delete a case.
 */
class GrievancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Grievance $grievance): bool
    {
        return $user->canSee($grievance);
    }

    /**
     * Every role enters cases received offline (A6).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Status, priority, assignment, notes, replies and uploads, on any case the user can see.
     */
    public function update(User $user, Grievance $grievance): bool
    {
        return $this->view($user, $grievance);
    }

    public function removeRestriction(User $user, Grievance $grievance): bool
    {
        return $user->role === Role::Administrator;
    }
}
