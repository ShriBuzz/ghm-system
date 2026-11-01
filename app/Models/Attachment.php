<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'uploaded_by', 'stored_name', 'original_name', 'mime', 'size'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(fn (Attachment $attachment) => activity()->performedOn($attachment->grievance()->firstOrFail())->event('attachment_uploaded')
            ->withProperties(['attachment_id' => $attachment->id])->log('Document uploaded'));
    }

    /**
     * @return BelongsTo<Grievance, $this>
     */
    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class);
    }

    /**
     * @return BelongsTo<GrievanceMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(GrievanceMessage::class, 'message_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_name' => 'encrypted',
            'size' => 'integer',
        ];
    }
}
