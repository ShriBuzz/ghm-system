<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Grievance>
 */
class GrievanceFactory extends Factory
{
    /**
     * The hashed PIN shared by factory cases.
     */
    protected static ?string $pinHash;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 1000 to 8999: tests name references below 1000 and from 9998 themselves, and the app's own counter
            // starts at 0001 when a test sends a form, so a factory case never takes one of theirs.
            'reference' => sprintf('GHM-%d-%04d', now()->year, fake()->unique()->numberBetween(1000, 8999)),
            'pin_hash' => static::$pinHash ??= Hash::make('123456'),
            'channel' => 'phone',
            'is_anonymous' => false,
            'name' => fake()->name(),
            'phone' => fake()->numerify('98########'),
            'email' => fake()->safeEmail(),
            'preferred_contact' => 'phone',
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'incident_date' => fake()->dateTimeBetween('-1 month'),
            'recurrence' => 'once',
            'description' => fake()->paragraph(),
            'status' => Status::Received,
            'locale' => 'ne',
            'received_at' => now(),
        ];
    }
}
