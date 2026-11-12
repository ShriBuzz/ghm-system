<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The notice above the public form, on confidentiality, no retaliation and what happens next (P3).
 * The Administrator changes it without a developer (A7); NBI provides the final wording.
 *
 * @property-read Schema $form
 */
class PublicNotice extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Public notice';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === Role::Administrator;
    }

    public function mount(): void
    {
        $this->form->fill(['ne' => Setting::value('notice.ne'), 'en' => Setting::value('notice.en')]);
    }

    public function form(Schema $schema): Schema
    {
        $hint = 'Shown above the grievance form. Plain text: the first paragraph is the heading, leave an empty line between paragraphs, and start each point of a list with "- " on its own line.';

        return $schema->statePath('data')->components([
            Textarea::make('ne')->label('Nepali')->required()->maxLength(3000)->rows(8)->helperText($hint)
                ->placeholder(fn (): string => __('notice.default', locale: 'ne')),
            Textarea::make('en')->label('English')->required()->maxLength(3000)->rows(8)->helperText($hint)
                ->placeholder(fn (): string => __('notice.default', locale: 'en')),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('Save')->submit('save')])]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::put('notice.ne', $data['ne']);
        Setting::put('notice.en', $data['en']);

        Notification::make()->success()->title('Notice saved')->send();
    }
}
