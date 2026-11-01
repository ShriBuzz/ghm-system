<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\Grievances\GrievanceResource;
use App\Models\Grievance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * The case page and the actions staff take on it (A3, A4, A5).
 */
class ViewGrievance extends ViewRecord
{
    protected static string $resource = GrievanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('status')
                ->label('Set status')
                ->authorize('update')
                ->schema([Select::make('status')->options(Status::class)->required()])
                ->fillForm(fn (Grievance $record): array => ['status' => $record->status])
                ->action(fn (Grievance $record, array $data) => $record->update($data)),

            Action::make('priority')
                ->label('Set priority')
                ->authorize('update')
                ->schema([Select::make('priority')->options(Priority::class)->required()])
                ->fillForm(fn (Grievance $record): array => ['priority' => $record->priority])
                ->action(fn (Grievance $record, array $data) => $record->update($data)),

            Action::make('assign')
                ->authorize('update')
                ->schema([
                    Select::make('assigned_to')
                        ->label('Assign to')
                        ->options(fn (Grievance $record) => $record->assignableUsers()->pluck('name', 'id'))
                        ->placeholder('Nobody'),
                ])
                ->fillForm(fn (Grievance $record): array => ['assigned_to' => $record->assigned_to])
                ->action(fn (Grievance $record, array $data) => $record->update($data)),

            Action::make('restrict')
                ->label('Mark restricted')
                ->color('danger')
                ->authorize('update')
                ->visible(fn (Grievance $record): bool => ! $record->is_restricted)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => $this->user()->role === Role::FocalPerson
                    ? 'Only restricted-case officers and the Administrator will see this case. You will lose access to it.'
                    : 'Only restricted-case officers and the Administrator will see this case.')
                ->action(function (Grievance $record) {
                    $record->update(['manually_restricted' => true]);

                    if (! $this->user()->canSee($record)) {
                        Notification::make()->success()->title("{$record->reference} is now restricted")->send();

                        return redirect(ListGrievances::getUrl());
                    }
                }),

            Action::make('unrestrict')
                ->label('Remove restricted mark')
                ->authorize('removeRestriction')
                ->visible(fn (Grievance $record): bool => $record->manually_restricted)
                ->requiresConfirmation()
                ->modalDescription(fn (Grievance $record): string => $record->category?->is_restricted
                    ? 'The case stays restricted, because its category is restricted.'
                    : 'Focal persons will see this case again.')
                ->action(fn (Grievance $record) => $record->update(['manually_restricted' => false])),
        ];
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
