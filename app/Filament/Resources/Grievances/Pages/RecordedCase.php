<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Filament\Resources\Grievances\GrievanceResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * The slip after an offline entry (A6): reference and PIN, shown once. It reads
 * only the one-time session message, never the case, so a focal person who
 * entered a restricted case can still hand the slip over.
 */
class RecordedCase extends Page
{
    protected static string $resource = GrievanceResource::class;

    protected static ?string $title = 'Offline case recorded';

    public string $reference;

    public string $pin;

    public string $receivedAt;

    public function mount(): void
    {
        $slip = session('recorded_case');

        if (! is_array($slip)) {
            $this->redirect(ListGrievances::getUrl());

            return;
        }

        $this->reference = Arr::string($slip, 'reference');
        $this->pin = Arr::string($slip, 'pin');
        $this->receivedAt = Arr::string($slip, 'received_at');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'sm' => 3])
                ->schema([
                    TextEntry::make('reference')->state($this->reference)->size(TextSize::Large)->weight(FontWeight::Bold),
                    TextEntry::make('pin')->label('PIN')->state($this->pin)->size(TextSize::Large)->weight(FontWeight::Bold),
                    TextEntry::make('received')->state(Carbon::parse($this->receivedAt)->format('j M Y, H:i')),
                    Text::make('Give the reference and PIN to the complainant now. With both, they can check the case and read replies on the status page. The PIN is shown only once and cannot be looked up later.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('Print slip')->icon('heroicon-o-printer')->alpineClickHandler('window.print()'),
            Action::make('back')->label('Back to cases')->color('gray')->url(ListGrievances::getUrl()),
        ];
    }
}
