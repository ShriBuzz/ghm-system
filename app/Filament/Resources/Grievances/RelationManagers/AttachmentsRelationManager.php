<?php

namespace App\Filament\Resources\Grievances\RelationManagers;

use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\User;
use App\Support\AttachmentStore;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Documents on the case: the complainant's attachment and staff uploads (A3).
 * Files are stored and opened only through AttachmentStore and the case-checked
 * download route (S4).
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->url(fn (Attachment $record): string => route('filament.admin.attachments.download', $record)),
                TextColumn::make('size')->formatStateUsing(fn (int $state): string => number_format($state / 1024).' KB'),
                TextColumn::make('uploader.name')->label('Uploaded by')->placeholder('Complainant'),
                TextColumn::make('created_at')->label('Date')->dateTime(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload document')
                    ->schema([
                        // Stored by AttachmentStore, not by Filament, so every upload is checked and cleaned the same way.
                        FileUpload::make('file')
                            ->label('Photo or PDF, up to 5 MB')
                            ->storeFiles(false)
                            ->rules([AttachmentStore::rule()])
                            ->required(),
                    ])
                    ->action(function (array $data, Action $action): void {
                        $user = auth()->user();
                        $case = $this->getOwnerRecord();
                        $upload = $data['file'];
                        abort_unless($user instanceof User && $case instanceof Grievance && $upload instanceof TemporaryUploadedFile, 403);

                        AttachmentStore::store($upload, $case, $user, field: "mountedActions.{$action->getNestingIndex()}.data.file");

                        // Livewire's copy is the uncleaned original, and its .json holds the file name; both would stay for a day (S5).
                        $upload->delete();
                        FileUploadConfiguration::storage()->delete(FileUploadConfiguration::path($upload->getFilename().'.json'));
                    }),
            ]);
    }
}
