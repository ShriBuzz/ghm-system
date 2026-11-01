<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Filament\Resources\Grievances\GrievanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGrievances extends ListRecords
{
    protected static string $resource = GrievanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Enter offline case'),
        ];
    }
}
