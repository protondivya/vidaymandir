<?php

namespace Database\Factories;

use App\Models\LicenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseType>
 */
class LicenseTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('license-????'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ];
    }
}
