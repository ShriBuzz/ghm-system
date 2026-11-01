<?php

namespace App\Filament\Resources\Grievances\Schemas;

use App\Enums\Channel;
use App\Enums\ContactMethod;
use App\Enums\Recurrence;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Offline entry (A6). The fields mirror the public form; validation comes
 * from StoreGrievanceRequest::caseRules() in CreateGrievance, so the asterisks
 * here only mark what those rules require.
 */
class GrievanceForm
{
    public static function configure(Schema $schema): Schema
    {
        $named = fn (Get $get): bool => ! $get('is_anonymous');

        return $schema
            ->columns(1)
            ->components([
                Section::make('How it was received')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Select::make('channel')
                            ->options(collect(Channel::cases())->reject(fn (Channel $channel) => $channel === Channel::Web)->mapWithKeys(fn (Channel $channel) => [$channel->value => $channel->getLabel()]))
                            ->markAsRequired(),
                        DateTimePicker::make('received_at')->label('Received')->default(now())->seconds(false)->markAsRequired(),
                    ]),
                Section::make('Grievance')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Select::make('category_id')->label('Category')
                            ->relationship('category', 'name_en', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort'))
                            ->markAsRequired(),
                        Select::make('location_id')->label('Location')
                            ->relationship('location', 'name_en', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort'))
                            ->markAsRequired(),
                        DatePicker::make('incident_date')->label('When it happened')->maxDate(now())->markAsRequired(),
                        Select::make('recurrence')->label('How often')->options(Recurrence::class)->markAsRequired(),
                        Textarea::make('description')->rows(6)->columnSpanFull()->markAsRequired(),
                    ]),
                Section::make('Complainant')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Toggle::make('is_anonymous')->label('Anonymous: no name or contact details')->live()->columnSpanFull(),
                        TextInput::make('name')->visible($named)->markAsRequired(),
                        TextInput::make('phone')->tel()->visible($named)->helperText('A phone number or an email is needed.'),
                        TextInput::make('email')->email()->visible($named),
                        Select::make('preferred_contact')->label('Preferred contact')->options(ContactMethod::class)->visible($named)->markAsRequired(),
                    ]),
            ]);
    }
}
