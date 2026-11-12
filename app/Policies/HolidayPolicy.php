<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * The holiday list (A2): the focal person enters each year's holidays and the Administrator
 * checks them in the activity log, as the company asked. Only the Administrator removes one.
 */
class HolidayPolicy extends SettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Administrator, Role::FocalPerson], true);
    }

    public function delete(User $user): bool
    {
        return $user->role === Role::Administrator;
    }
}
