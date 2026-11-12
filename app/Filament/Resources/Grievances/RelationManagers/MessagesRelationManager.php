<?php

namespace App\Filament\Resources\Grievances\RelationManagers;

use App\Enums\MessageDirection;
use App\Support\BsDate;
use Carbon\CarbonInterface;
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

    // Short, so all three tabs fit a 360 px screen; the table heading says who reads them.
    protected static ?string $title = 'Messages';

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
            ->heading('Messages with the complainant')
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('created_at')->label('Date')->formatStateUsing(fn (CarbonInterface $state): string => BsDate::forStaff($state, time: true)),
                TextColumn::make('direction')->label('From')->badge(),
                TextColumn::make('author.name')->label('Staff member')->placeholder('—'),
                // The complainant's own words, line breaks kept (U16): escaped first, so typed HTML stays text.
                TextColumn::make('body')->label('Message')->wrap()->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Send reply')
                    ->modalHeading('Reply to the complainant')
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'direction' => MessageDirection::Staff, 'author_id' => auth()->id()]),
            ]);
    }
}
