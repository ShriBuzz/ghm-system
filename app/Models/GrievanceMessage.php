<?php

namespace App\Models;

use App\Enums\MessageDirection;
use Database\Factories\GrievanceMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['direction', 'author_id', 'body'])]
class GrievanceMessage extends Model
{
    /** @use HasFactory<GrievanceMessageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (GrievanceMessage $message): void {
            $staff = $message->direction === MessageDirection::Staff;

            activity()->performedOn($message->grievance()->firstOrFail())->event($staff ? 'reply_sent' : 'message_received')
                ->withProperties(['message_id' => $message->id])->log($staff ? 'Reply sent' : 'Message received');
        });
    }

    /**
     * @return BelongsTo<Grievance, $this>
     */
    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'body' => 'encrypted',
        ];
    }
}
