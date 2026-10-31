<?php

namespace App\Models;

use Database\Factories\GrievanceMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceMessage extends Model
{
    /** @use HasFactory<GrievanceMessageFactory> */
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
            'body' => 'encrypted',
        ];
    }
}
