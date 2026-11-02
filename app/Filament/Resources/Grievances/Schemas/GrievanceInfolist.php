<?php

namespace App\Filament\Resources\Grievances\Schemas;

use App\Models\Grievance;
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
                        TextEntry::make('status')->badge(),
                        TextEntry::make('priority')->badge()->placeholder('Not set'),
                        TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Nobody'),
                        TextEntry::make('received_at')->label('Received')->dateTime(),
                        TextEntry::make('working_days_open')->label('Working days open'),
                        TextEntry::make('closed_at')->label('Closed')->dateTime()->placeholder('Not closed'),
                        TextEntry::make('channel'),
                        IconEntry::make('is_restricted')->label('Restricted')->boolean(),
                    ]),
                Section::make('Grievance')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextEntry::make('category.name_en')->label('Category'),
                        TextEntry::make('location.name_en')->label('Location'),
                        TextEntry::make('incident_date')->label('When it happened')
                            ->formatStateUsing(fn (Grievance $record): string => $record->incident_date_exact
                                ? $record->incident_date->format('j M Y')
                                : $record->incident_date->format('F Y').', exact day not remembered'),
                        TextEntry::make('recurrence')->label('How often'),
                        TextEntry::make('description')->columnSpanFull(),
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
}
