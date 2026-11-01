<?php

namespace App\Filament\Resources\Grievances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GrievanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // The full submission and the case actions come in WP2 step 6.
                TextEntry::make('reference'),
                TextEntry::make('status')->badge(),
            ]);
    }
}
