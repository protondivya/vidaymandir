<?php

namespace Database\Factories;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'synopsis' => fake()->paragraphs(2, true),
            'language' => 'en',
            'page_count' => fake()->numberBetween(100, 900),
            'word_count' => fake()->numberBetween(10000, 200000),
            'cover_image_url' => null,
            'license_type_id' => LicenseType::factory(),
            'rights_source' => null,
            'status' => BookStatus::Draft,
            'view_count' => 0,
            'created_by' => User::factory(),
            'published_at' => null,
        ];
    }

    /**
     * Mark the book as publicly active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookStatus::Active,
            'published_at' => now()->subDays(rand(1, 365)),
        ]);
    }

    /**
     * Explicitly mark the book as a draft (the default state).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Mark the book as deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookStatus::Deactivated,
        ]);
    }
}
