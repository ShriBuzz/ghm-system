<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Settings (users, categories, locations, holidays) belong to the Administrator
 * (A7). Nothing is deleted: a user, category or location is deactivated
 * instead, so past cases keep their names.
 */
class SettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === Role::Administrator;
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
