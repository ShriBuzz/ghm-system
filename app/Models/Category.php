<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
                Grievance::where('category_id', $category->id)->update([
                    'is_restricted' => $category->is_restricted ? true : DB::raw('manually_restricted'),
                ]);
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
