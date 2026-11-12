<?php

namespace App\Filament\Resources\Grievances\Schemas;

use App\Enums\Status;
use App\Models\Grievance;
use App\Support\BsDate;
use Carbon\CarbonInterface;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The submission as staff read it on the case page (A3). Encrypted fields are
 * decrypted by the model casts; nothing here is shown to the complainant.
 */
class GrievanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Case')
                    ->columns(['default' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('reference'),
                        // With the Nepali the complainant reads on the status page (U16).
                        TextEntry::make('status')->badge()->formatStateUsing(fn (Status $state): string => $state->withNepali()),
                        TextEntry::make('priority')->badge()->placeholder('Not set'),
                        TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Nobody'),
                        TextEntry::make('received_at')->label('Received')->formatStateUsing(fn (CarbonInterface $state): string => BsDate::forStaff($state, time: true)),
                        TextEntry::make('working_days_open')->label('Working days open'),
                        TextEntry::make('closed_at')->label('Closed')->formatStateUsing(fn (CarbonInterface $state): string => BsDate::forStaff($state, time: true))->placeholder('Not closed'),
                        // Working days count from here once a stopped case is reopened (U3).
                        TextEntry::make('reopened_at')->label('Reopened on')->formatStateUsing(fn (CarbonInterface $state): string => BsDate::forStaff($state, time: true))
                            ->visible(fn (Grievance $record): bool => $record->reopened_at !== null),
                        TextEntry::make('channel'),
                        IconEntry::make('is_restricted')->label('Restricted')->boolean(),
                    ]),
                Section::make('Grievance')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextEntry::make('category.name_en')->label('Category')
                            ->formatStateUsing(fn (Grievance $record): string => "{$record->category?->name_en} ({$record->category?->name_ne})"),
                        TextEntry::make('location.name_en')->label('Location'),
                        TextEntry::make('incident_date')->label('When it happened')
                            ->formatStateUsing(fn (Grievance $record): string => match (true) {
                                $record->incident_date_exact => BsDate::forStaff($record->incident_date),
                                // A BS month straddles two AD months, so it is shown as the month the complainant chose (U9).
                                $record->incident_date_calendar === 'bs' => self::bsMonth($record->incident_date).', exact day not remembered',
                                default => $record->incident_date->format('F Y').', exact day not remembered',
                            }),
                        TextEntry::make('recurrence')->label('How often'),
                        // The complainant's own words, line breaks kept (U16): escaped first, so typed HTML stays text.
                        TextEntry::make('description')->columnSpanFull()->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                    ]),
                Section::make('Complainant')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextEntry::make('is_anonymous')
                            ->label('Identity')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Anonymous' : 'Named')
                            ->columnSpanFull(),
                        TextEntry::make('name')->placeholder('Not given'),
                        TextEntry::make('phone')->placeholder('Not given'),
                        TextEntry::make('email')->placeholder('Not given'),
                        TextEntry::make('preferred_contact')->label('Preferred contact')->placeholder('Not given'),
                    ])
                    ->visible(fn (Grievance $record): bool => ! $record->is_anonymous),
                Section::make('Complainant')
                    ->schema([TextEntry::make('anonymous')->hiddenLabel()->default('Anonymous: no contact details were given.')])
                    ->visible(fn (Grievance $record): bool => $record->is_anonymous),
                Section::make('About the complainant (optional answers)')
                    ->columns(['default' => 1, 'lg' => 3])
                    ->schema([
                        TextEntry::make('complainant_type')->label('Complainant type')->state(fn (Grievance $record) => $record->answerLabel('complainant_type'))->placeholder('Not given'),
                        TextEntry::make('sex')->state(fn (Grievance $record) => $record->answerLabel('sex'))->placeholder('Not given'),
                        TextEntry::make('age_group')->label('Age group')->state(fn (Grievance $record) => $record->answerLabel('age_group'))->placeholder('Not given'),
                        TextEntry::make('ethnicity')->state(fn (Grievance $record) => $record->answerLabel('ethnicity'))->placeholder('Not given'),
                        TextEntry::make('disability')->state(fn (Grievance $record) => $record->answerLabel('disability'))->placeholder('Not given'),
                    ]),
            ]);
    }

    /**
     * A BS month and year, such as "Kartik 2083".
     */
    private static function bsMonth(CarbonInterface $date): string
    {
        [$year, $month] = BsDate::fromAd($date);

        return __("bs_month.{$month}")." {$year}";
    }
}
