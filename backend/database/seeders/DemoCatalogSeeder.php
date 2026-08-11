<?php

namespace Database\Seeders;

use App\Enums\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Seed a small, legal, public-domain demo catalog.
     */
    public function run(): void
    {
        $librarian = User::factory()->create([
            'display_name' => 'Demo Librarian',
            'email' => 'librarian@example.com',
            'role' => 'librarian',
        ]);

        $publicDomain = LicenseType::firstOrCreate(
            ['code' => 'public_domain'],
            ['name' => 'Public Domain', 'description' => 'Works whose intellectual property rights have expired or been forfeited.'],
        );

        $authors = [
            'Mary Shelley',
            'Jane Austen',
            'Charles Dickens',
            'Leo Tolstoy',
            'Oscar Wilde',
        ];

        $authorModels = collect($authors)->map(fn (string $name) => Author::create(['name' => $name]));

        $fiction = Category::create(['name' => 'Fiction', 'slug' => 'fiction']);
        $classics = Category::create(['name' => 'Classics', 'slug' => 'classics', 'parent_id' => $fiction->id]);
        $gothic = Category::create(['name' => 'Gothic', 'slug' => 'gothic', 'parent_id' => $fiction->id]);

        $books = [
            ['title' => 'Frankenstein; or, The Modern Prometheus', 'authors' => [0], 'categories' => [2], 'synopsis' => 'A scientist creates a sapient creature in an unorthodox scientific experiment.'],
            ['title' => 'Pride and Prejudice', 'authors' => [1], 'categories' => [1], 'synopsis' => 'Elizabeth Bennet navigates manners, morality, and marriage in Georgian England.'],
            ['title' => 'A Tale of Two Cities', 'authors' => [2], 'categories' => [1], 'synopsis' => 'A story of resurrection and sacrifice set in London and Paris before the French Revolution.'],
            ['title' => 'War and Peace', 'authors' => [3], 'categories' => [1], 'synopsis' => 'An epic novel of Russian society during the Napoleonic era.'],
            ['title' => 'The Picture of Dorian Gray', 'authors' => [4], 'categories' => [2], 'synopsis' => 'A portrait ages while its subject stays forever young.'],
        ];

        foreach ($books as $index => $spec) {
            $book = Book::create([
                'title' => $spec['title'],
                'slug' => Str::slug($spec['title']),
                'synopsis' => $spec['synopsis'],
                'language' => 'en',
                'page_count' => fake()->numberBetween(150, 1200),
                'word_count' => fake()->numberBetween(40000, 600000),
                'cover_image_url' => null,
                'license_type_id' => $publicDomain->id,
                'status' => BookStatus::Active,
                'view_count' => fake()->numberBetween(0, 10000),
                'created_by' => $librarian->id,
                'published_at' => now()->subDays($index + 1),
            ]);

            $book->authors()->sync(collect($spec['authors'])->map(fn (int $i) => $authorModels[$i]->id));
            $book->categories()->sync(collect($spec['categories'])->map(fn (int $i) => [$classics, $gothic, $fiction][$i]->id));
        }
    }
}
