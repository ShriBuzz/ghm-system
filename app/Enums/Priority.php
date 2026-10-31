<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Case priorities exactly as RFQ A4 lists them. Labels live in lang/*.json.
 */
enum Priority: string implements HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return __("priority.{$this->value}");
    }
}
