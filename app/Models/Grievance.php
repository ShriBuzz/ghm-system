<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\ContactMethod;
use App\Enums\Priority;
use App\Enums\Recurrence;
use App\Enums\Role;
use App\Enums\Status;
use App\Observers\GrievanceObserver;
use Database\Factories\GrievanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['status', 'priority', 'assigned_to', 'manually_restricted'])]
#[Hidden(['pin_hash'])]
#[ObservedBy(GrievanceObserver::class)]
class Grievance extends Model
{
    /** @use HasFactory<GrievanceFactory> */
    use HasFactory;

    /**
     * The one rule for which cases a staff member may see (A1, A5). Every
     * list, page, file, summary, export and alert goes through it.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        // The category is checked too, so a save racing a category change cannot leak a case.
        $restricted = fn (Builder $q) => $q->where('is_restricted', true)
            ->orWhereHas('category', fn (Builder $c) => $c->where('is_restricted', true));

        match ($user->role) {
            Role::Administrator => null,
            Role::FocalPerson => $query->whereNot($restricted),
            Role::RestrictedOfficer => $query->where($restricted),
        };
    }

    /**
     * Active staff who may see this case, the only people it can be assigned to (A3, A5).
     *
     * shortcut: one visibility query per active user, fine for the RFQ's 3 to 5 staff.
     *
     * @return Collection<int, User>
     */
    public function assignableUsers(): Collection
    {
        return User::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (User $user): bool => $user->canSee($this))
            ->values();
    }

    /**
     * Clear the assignee once they can no longer see the case, after it became restricted (A5).
     */
    public function dropUnseeingAssignee(): void
    {
        // Fresh from the ID: a relation loaded earlier may still hold the previous assignee.
        $assignee = $this->assignee()->first();

        if ($assignee && ! $assignee->canSee($this)) {
            $this->forceFill(['assigned_to' => null])->saveQuietly();
        }
    }

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
            'channel' => Channel::class,
            'is_anonymous' => 'boolean',
            'name' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'preferred_contact' => ContactMethod::class,
            'incident_date' => 'date',
            'recurrence' => Recurrence::class,
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
            'working_days_open' => 'integer',
        ];
    }
}
