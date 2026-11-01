<?php

namespace App\Models;

use App\Policies\SettingsPolicy;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['name_en', 'name_ne', 'is_restricted', 'is_active', 'sort'])]
#[UsePolicy(SettingsPolicy::class)]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * Turning a category's restricted flag on or off re-flags its existing cases (A5, A7).
     */
    protected static function booted(): void
    {
        static::updated(function (Category $category): void {
            if ($category->wasChanged('is_restricted')) {
                activity()->performedOn($category)->event('category_restriction_changed')
                    ->withProperties(['old' => (bool) $category->getOriginal('is_restricted'), 'new' => $category->is_restricted])
                    ->log('Category restriction changed');

                Grievance::where('category_id', $category->id)->update([
                    'is_restricted' => $category->is_restricted ? true : DB::raw('manually_restricted'),
                ]);

                Grievance::where('category_id', $category->id)->whereNotNull('assigned_to')
                    ->lazyById()->each(fn (Grievance $grievance) => $grievance->dropUnseeingAssignee());
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_restricted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
