<?php

namespace App\Filament\Resources\Grievances\RelationManagers;

use App\Enums\MessageDirection;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The thread with the complainant: their messages and staff replies, which
 * they read on the status page (A3, P8). Nothing is edited or deleted.
 */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages with the complainant';

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
            Textarea::make('body')
                ->label('Reply')
                ->helperText('The complainant reads this on the status page.')
                ->required()
                ->maxLength(5000)
                ->rows(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime(),
                TextColumn::make('direction')->label('From')->badge(),
                TextColumn::make('author.name')->label('Staff member')->placeholder('—'),
                TextColumn::make('body')->label('Message')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Send reply')
                    ->modalHeading('Reply to the complainant')
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'direction' => MessageDirection::Staff, 'author_id' => auth()->id()]),
            ]);
    }
}
