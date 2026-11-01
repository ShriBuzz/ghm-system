<?php

namespace App\Filament\Resources\Grievances\Tables;

use App\Enums\Status;
use App\Models\Grievance;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

/**
 * The case list (A2). Only the reference is searchable: the other text is encrypted (S3).
 */
class GrievancesTable
{
    public static function configure(Table $table): Table
    {
        $slaDays = Config::integer('ghm.sla_days');
        $overdue = fn (Grievance $case): bool => $case->status->isOpen() && $case->working_days_open > $slaDays;

        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('received_at')->label('Received')->date()->sortable(),
                // Next to the date, so the overdue badge is never scrolled out of view.
                TextColumn::make('working_days_open')
                    ->label('Days open')
                    ->sortable()
                    ->badge()
                    ->color(fn (Grievance $record): string => $overdue($record) ? 'danger' : 'gray')
                    ->tooltip(fn (Grievance $record): ?string => $overdue($record) ? "Open more than {$slaDays} working days" : null),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge()->placeholder('Not set'),
                TextColumn::make('category.name_en')->label('Category'),
                TextColumn::make('location.name_en')->label('Location'),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('Nobody'),
            ])
            ->filters([
                SelectFilter::make('status')->options(Status::class)->multiple(),
                SelectFilter::make('category')->relationship('category', 'name_en'),
                SelectFilter::make('location')->relationship('location', 'name_en'),
                Filter::make('received')
                    ->schema([
                        DatePicker::make('from')->label('Received from'),
                        DatePicker::make('until')->label('Received until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('received_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('received_at', '<=', $date))),
                Filter::make('overdue')
                    ->label("Overdue only (over {$slaDays} working days)")
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('working_days_open', '>', $slaDays)->whereIn('status', Status::open())),
            ]);
    }
}
