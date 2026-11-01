<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How often the problem happened (P5).
 */
enum Recurrence: string implements HasLabel
{
    case Once = 'once';
    case Repeated = 'repeated';
    case Ongoing = 'ongoing';

    public function getLabel(): string
    {
        return __("recurrence.{$this->value}");
    }
}
