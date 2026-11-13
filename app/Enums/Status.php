<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Case statuses the company uses. Labels live in lang/*.json.
 */
enum Status: string implements HasLabel
{
    case Received = 'received';
    case UnderAssessment = 'under_assessment';
    case UnderInvestigation = 'under_investigation';
    case ReferredOutOfScope = 'referred_out_of_scope';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    /**
     * Whether the 15-working-day clock runs in this status.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Received, self::UnderAssessment, self::UnderInvestigation, self::Reopened];
    }

    public function getLabel(): string
    {
        return __("status.{$this->value}");
    }

    /**
     * The label with the Nepali the complainant reads, for staff: "Closed (बन्द गरिएको)".
     */
    public function withNepali(): string
    {
        return $this->getLabel().' ('.__("status.{$this->value}", [], 'ne').')';
    }
}
