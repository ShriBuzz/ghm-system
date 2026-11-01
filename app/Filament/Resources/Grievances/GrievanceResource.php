<?php

namespace App\Filament\Resources\Grievances;

use App\Filament\Resources\Grievances\Pages\CreateGrievance;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Filament\Resources\Grievances\Pages\RecordedCase;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Filament\Resources\Grievances\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Grievances\Schemas\GrievanceForm;
use App\Filament\Resources\Grievances\Schemas\GrievanceInfolist;
use App\Filament\Resources\Grievances\Tables\GrievancesTable;
use App\Models\Grievance;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GrievanceResource extends Resource
{
    protected static ?string $model = Grievance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $modelLabel = 'case';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    /**
     * Every list, count, search and record address starts from the one visibility rule (A1, A5).
     *
     * @return Builder<Grievance>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return Grievance::visibleTo($user);
    }

    public static function form(Schema $schema): Schema
    {
        return GrievanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GrievanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrievancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
            NotesRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrievances::route('/'),
            'create' => CreateGrievance::route('/create'),
            // Before the {record} route, or "recorded" would be read as a case id.
            'recorded' => RecordedCase::route('/recorded'),
            'view' => ViewGrievance::route('/{record}'),
        ];
    }
}
