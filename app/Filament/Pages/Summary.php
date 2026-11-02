<?php

namespace App\Filament\Pages;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Cases received in a date range, counted by status and by category (N3).
 * Counts only what the signed-in user may see, through the one visibility rule.
 *
 * @property-read Schema $form
 */
class Summary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(['from' => today()->startOfMonth()->toDateString(), 'until' => today()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        $check = fn () => $this->form->validate();

        return $schema->statePath('data')->columns(['sm' => 2])->components([
            DatePicker::make('from')->label('Received from')->required()->live()->afterStateUpdated($check),
            DatePicker::make('until')->label('Received until')->required()->afterOrEqual('from')->live()->afterStateUpdated($check),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            Grid::make(['lg' => 2])->hidden(fn () => $this->getErrorBag()->isNotEmpty())->schema([
                Text::make(fn () => trans_choice(':count case received in this period.|:count cases received in this period.', $this->summary()['total']))
                    ->columnSpanFull(),
                Section::make('Cases by status')->schema([
                    KeyValueEntry::make('statuses')->hiddenLabel()->keyLabel('Status')->valueLabel('Cases')
                        ->state(function (): array {
                            $counts = $this->summary()['statuses'];

                            return collect(Status::cases())->mapWithKeys(fn (Status $status) => [$status->getLabel() => (string) $counts[$status->value]])->all();
                        }),
                ]),
                Section::make('Cases by category')->schema([
                    KeyValueEntry::make('categories')->hiddenLabel()->keyLabel('Category')->valueLabel('Cases')
                        ->placeholder('No cases in this period.')
                        ->state(fn () => array_map(strval(...), $this->summary()['categories'])),
                ]),
            ]),
        ]);
    }

    /**
     * @return array{total: int, statuses: array<string, int>, categories: array<string, int>}
     */
    public function summary(): array
    {
        $user = auth()->user();
        assert($user instanceof User);

        $cases = fn () => Grievance::visibleTo($user)->whereBetween('received_at', [
            Carbon::parse(Arr::string($this->data, 'from'))->startOfDay(),
            Carbon::parse(Arr::string($this->data, 'until'))->endOfDay(),
        ]);

        $byStatus = $cases()->groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status');
        $byCategory = $cases()->groupBy('category_id')->selectRaw('category_id, count(*) as total')->pluck('total', 'category_id');

        return [
            'total' => (int) $byStatus->sum(),
            'statuses' => collect(Status::cases())->mapWithKeys(fn (Status $status) => [$status->value => (int) $byStatus->get($status->value, 0)])->all(),
            'categories' => Category::whereKey($byCategory->keys())->orderBy('sort')->orderBy('name_en')->get()
                ->mapWithKeys(fn (Category $category) => [$category->name_en => (int) $byCategory->get($category->id)])->all(),
        ];
    }
}
