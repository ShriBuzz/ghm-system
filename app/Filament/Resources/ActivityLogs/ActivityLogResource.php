<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Enums\Role;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceNote;
use App\Models\Holiday;
use App\Models\User;
use App\Support\BsDate;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * The activity log (S6), read only and for the Administrator alone: who
 * changed, viewed or added what on each case, and sign-ins. Entries hold codes
 * and IDs, never case text, and cannot be edited or deleted here.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activity-log';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === Role::Administrator;
    }

    public static function table(Table $table): Table
    {
        $staff = User::pluck('name', 'id');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['subject', 'causer']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->formatStateUsing(fn (CarbonInterface $state): string => BsDate::forStaff($state, time: true)),
                TextColumn::make('event')->label('What')->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                TextColumn::make('subject_id')->label('Case or setting')->formatStateUsing(fn (Activity $record): ?string => match (true) {
                    $record->subject instanceof Grievance => $record->subject->reference,
                    $record->subject instanceof Category => "Category: {$record->subject->name_en}",
                    $record->subject instanceof Holiday => "Holiday: {$record->subject->name}",
                    default => null,
                }),
                // After what and which case, so a narrow screen shows those first.
                TextColumn::make('causer.name')->label('Who')->placeholder('System or complainant'),
                TextColumn::make('properties')->label('Details')->state(fn (Activity $record): string => self::details($record, $staff))->wrap(),
            ])
            ->filters([
                SelectFilter::make('event')->options(fn () => Activity::distinct()->orderBy('event')->pluck('event', 'event')->map(fn (string $event) => str($event)->headline())),
                SelectFilter::make('causer_id')->label('Who')->options($staff->all())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, $id) => $q->where('causer_type', (new User)->getMorphClass())->where('causer_id', $id))),
                Filter::make('reference')
                    ->schema([TextInput::make('reference')->label('Case reference')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['reference'] ?? null, fn (Builder $q, string $reference) => $q->whereHasMorph('subject', [Grievance::class], fn (Builder $c) => $c->where('reference', 'like', "%{$reference}%")))),
                Filter::make('date')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ]);
    }

    /**
     * "old → new" for changes, assignees by name; other properties as key: value.
     *
     * @param  Collection<int, string>  $staff
     */
    private static function details(Activity $activity, $staff): string
    {
        $properties = $activity->properties ?? collect();
        $show = fn (mixed $value): string => match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'yes' : 'no',
            $activity->event === 'assigned' => (string) ($staff[$value] ?? "user {$value}"),
            default => str((string) $value)->headline()->toString(),
        };

        if ($properties->has('old') || $properties->has('new')) {
            return $show($properties->get('old')).' → '.$show($properties->get('new')).($properties->has('reason') ? " ({$properties->get('reason')})" : '')
                // A removed restriction's reason is an encrypted note; the log holds only its ID (U5).
                .($properties->has('note_id') ? '. Reason: '.GrievanceNote::whereKey($properties->get('note_id'))->first()?->body : '');
        }

        return $properties->map(fn (mixed $value, string $key): string => $key.': '.(is_bool($value) ? ($value ? 'yes' : 'no') : $value))->implode(', ');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }
}
