<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Filament\Resources\Grievances\GrievanceResource;
use App\Models\Grievance;
use App\Models\User;
use App\Support\CaseExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListGrievances extends ListRecords
{
    protected static string $resource = GrievanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Enter offline case'),
            Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalSubmitActionLabel('Download')
                ->schema([
                    DatePicker::make('from')->label('Received from')->required()->default(today()->startOfMonth()),
                    DatePicker::make('until')->label('Received until')->required()->afterOrEqual('from')->default(today()),
                    Radio::make('format')->options(['csv' => 'CSV', 'xlsx' => 'Excel (XLSX)'])->default('csv')->required(),
                    Toggle::make('personal')
                        ->label('Include names and contact details')
                        ->visible(fn (): bool => (bool) auth()->user()?->can('exportPersonalData', Grievance::class)),
                ])
                ->action(function (array $data): StreamedResponse {
                    $user = auth()->user();
                    assert($user instanceof User);

                    return CaseExport::download($user, Carbon::parse($data['from']), Carbon::parse($data['until']), $data['format'], (bool) ($data['personal'] ?? false));
                }),
        ];
    }
}
