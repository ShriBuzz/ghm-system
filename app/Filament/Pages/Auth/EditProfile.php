<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;

/**
 * Lets staff change their own password and manage MFA. Changing the password
 * or email asks for the current password.
 */
class EditProfile extends BaseEditProfile
{
    /**
     * Filament's save throttle, keyed on the signed-in user instead of the IP address.
     *
     * @param  string  $method
     * @param  class-string|null  $component
     */
    protected function getRateLimitKey($method, $component = null): string
    {
        return 'profile-throttle:'.Filament::auth()->id();
    }
}
