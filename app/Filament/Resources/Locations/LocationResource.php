<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Places a grievance can be about (A7).
 */
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')->label('Name in English')->required()->maxLength(255),
                TextInput::make('name_ne')->label('Name in Nepali')->required()->maxLength(255),
                Toggle::make('is_active')->label('Offered on the forms')->default(true),
                TextInput::make('sort')->label('Order')->integer()->minValue(0)->default(0)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name_en')->label('Name in English')->searchable(),
                TextColumn::make('name_ne')->label('Name in Nepali')->searchable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sort')->label('Order')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocations::route('/'),
        ];
    }
}
