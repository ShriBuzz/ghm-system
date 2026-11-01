<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Who wrote a message in the thread the complainant sees on the status page (A3, P8).
 */
enum MessageDirection: string implements HasLabel
{
    case Complainant = 'complainant';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return __("message.{$this->value}");
    }
}
