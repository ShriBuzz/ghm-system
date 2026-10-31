<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Grievance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
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
            'stored_name' => Str::random(40),
            'original_name' => fake()->word().'.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
        ];
    }
}
