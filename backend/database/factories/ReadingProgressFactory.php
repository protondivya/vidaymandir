<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingProgress>
 */
class ReadingProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'current_page' => fake()->numberBetween(1, 100),
            'percent' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
