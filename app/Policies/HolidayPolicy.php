<?php

namespace App\Policies;

use App\Models\User;

/**
 * Like other settings, except a holiday entered on the wrong date can be removed.
 */
class HolidayPolicy extends SettingsPolicy
{
    public function delete(User $user): bool
    {
        return $this->viewAny($user);
    }
}
