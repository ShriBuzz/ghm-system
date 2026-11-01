<?php

namespace Database\Factories;

use App\Enums\MessageDirection;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrievanceMessage>
 */
class GrievanceMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grievance_id' => Grievance::factory(),
            'direction' => MessageDirection::Complainant,
            'body' => fake()->paragraph(),
        ];
    }
}
