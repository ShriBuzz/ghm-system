<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->unique()->words(2, true),
            'name_ne' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Indicate that cases in the category are restricted.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_restricted' => true,
        ]);
    }
}
