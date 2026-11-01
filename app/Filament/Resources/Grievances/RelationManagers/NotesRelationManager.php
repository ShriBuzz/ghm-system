<?php

namespace App\Filament\Resources\Grievances\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Internal notes: dated, with author, never shown to the complainant, and
 * never edited or deleted (A3).
 */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    // Short, so all three tabs fit a 360 px screen; the table heading says they are internal.
    protected static ?string $title = 'Notes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Note')->required()->maxLength(5000)->rows(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Internal notes')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime(),
                TextColumn::make('user.name')->label('Author'),
                TextColumn::make('body')->label('Note')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add note')
                    ->modalHeading('Add internal note')
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id()]),
            ]);
    }
}
