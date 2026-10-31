<?php

namespace App\Models;

use Database\Factories\GrievanceNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceNote extends Model
{
    /** @use HasFactory<GrievanceNoteFactory> */
    use HasFactory;

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
        ];
    }
}
