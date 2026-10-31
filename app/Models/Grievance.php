<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\Status;
use Database\Factories\GrievanceFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(['pin_hash'])]
class Grievance extends Model
{
    /** @use HasFactory<GrievanceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<GrievanceMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(GrievanceMessage::class);
    }

    /**
     * @return HasMany<GrievanceNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(GrievanceNote::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * Personal fields are encrypted (S3), the demographic fields too.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'name' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'incident_date' => 'date',
            'description' => 'encrypted',
            'complainant_type' => 'encrypted',
            'sex' => 'encrypted',
            'age_group' => 'encrypted',
            'ethnicity' => 'encrypted',
            'disability' => 'encrypted',
            'status' => Status::class,
            'priority' => Priority::class,
            'manually_restricted' => 'boolean',
            'is_restricted' => 'boolean',
            'received_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
