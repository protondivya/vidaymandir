<?php

namespace Tests\Feature;

use App\Enums\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private function librarianToken(): string
    {
        return User::factory()->create(['role' => 'librarian'])->createAccessToken()['token'];
    }

    private function readerToken(): string
    {
        return User::factory()->create(['role' => 'reader'])->createAccessToken()['token'];
    }

    public function test_catalog_lists_only_active_books(): void
    {
        Book::factory()->active()->count(3)->create();
        Book::factory()->draft()->create(['title' => 'Hidden Draft']);
        Book::factory()->deactivated()->create(['title' => 'Hidden Deactivated']);

        $response = $this->getJson('/api/v1/books?limit=100');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonMissing(['title' => 'Hidden Draft'])
            ->assertJsonMissing(['title' => 'Hidden Deactivated'])
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'authors', 'categories', 'status']],
                'meta' => ['total', 'page', 'limit', 'last_page'],
            ]);
    }

    public function test_catalog_is_paginated(): void
    {
        Book::factory()->active()->count(25)->create();

        $response = $this->getJson('/api/v1/books?page=2&limit=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.limit', 10)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonCount(10, 'data');
    }

    public function test_catalog_limit_is_capped_at_100(): void
    {
        Book::factory()->active()->count(120)->create();

        $this->getJson('/api/v1/books?limit=500')
            ->assertOk()
            ->assertJsonPath('meta.limit', 100);
    }

    public function test_catalog_searches_by_title(): void
    {
        Book::factory()->active()->create(['title' => 'Moby Dick']);
        Book::factory()->active()->create(['title' => 'The Odyssey']);

        $response = $this->getJson('/api/v1/books?q=moby');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Moby Dick');
    }

    public function test_catalog_searches_by_author_name(): void
    {
        $author = Author::factory()->create(['name' => 'Herman Melville']);
        $book = Book::factory()->active()->create(['title' => 'Typee']);
        $book->authors()->attach($author);

        $this->getJson('/api/v1/books?q=melville')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $book->id);
    }

    public function test_catalog_can_filter_by_category(): void
    {
        $category = Category::factory()->create();
        $matched = Book::factory()->active()->create();
        $other = Book::factory()->active()->create();
        $matched->categories()->attach($category);

        $this->getJson("/api/v1/books?category={$category->slug}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matched->id)
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_catalog_can_sort_by_title(): void
    {
        Book::factory()->active()->create(['title' => 'Zebra']);
        Book::factory()->active()->create(['title' => 'Apple']);

        $this->getJson('/api/v1/books?sort=title_asc')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Apple')
            ->assertJsonPath('data.1.title', 'Zebra');
    }

    public function test_book_can_be_fetched_by_slug(): void
    {
        $book = Book::factory()->active()->create(['title' => 'A Christmas Carol']);

        $this->getJson("/api/v1/books/{$book->slug}")
            ->assertOk()
            ->assertJsonPath('data.book.id', $book->id)
            ->assertJsonPath('data.book.title', 'A Christmas Carol')
            ->assertJsonStructure([
                'data' => ['book' => ['id', 'title', 'slug', 'synopsis', 'license_type', 'authors', 'categories']],
            ]);
    }

    public function test_inactive_book_returns_404(): void
    {
        $book = Book::factory()->draft()->create();

        $this->getJson("/api/v1/books/{$book->slug}")->assertStatus(404);
    }

    public function test_creating_a_book_requires_librarian(): void
    {
        $payload = [
            'title' => 'Unauthorized Book',
            'language' => 'en',
            'license_type_id' => LicenseType::factory()->create()->id,
            'authors' => [Author::factory()->create()->id],
        ];

        $this->getJson('/api/v1/books')->assertOk();

        $this->withToken($this->readerToken())
            ->postJson('/api/v1/books', $payload)
            ->assertStatus(403);
    }

    public function test_librarian_can_create_a_book(): void
    {
        $author = Author::factory()->create();
        $category = Category::factory()->create();
        $license = LicenseType::factory()->create();

        $response = $this->withToken($this->librarianToken())
            ->postJson('/api/v1/books', [
                'title' => 'The Great Gatsby',
                'synopsis' => 'A story of the Jazz Age.',
                'language' => 'en',
                'page_count' => 180,
                'license_type_id' => $license->id,
                'authors' => [$author->id],
                'categories' => [$category->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.book.status', 'draft')
            ->assertJsonPath('data.book.slug', 'the-great-gatsby')
            ->assertJsonPath('data.book.authors.0.id', $author->id)
            ->assertJsonPath('data.book.categories.0.id', $category->id);

        $this->assertDatabaseHas('books', [
            'title' => 'The Great Gatsby',
            'slug' => 'the-great-gatsby',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('book_authors', ['author_id' => $author->id]);
        $this->assertDatabaseHas('book_categories', ['category_id' => $category->id]);
    }

    public function test_librarian_can_create_a_published_book(): void
    {
        $license = LicenseType::factory()->create();
        $author = Author::factory()->create();

        $response = $this->withToken($this->librarianToken())
            ->postJson('/api/v1/books', [
                'title' => 'Published Immediately',
                'language' => 'en',
                'license_type_id' => $license->id,
                'authors' => [$author->id],
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.book.status', 'active');

        $this->assertNotNull($response->json('data.book.published_at'));
    }

    public function test_creating_a_book_validates_input(): void
    {
        $this->withToken($this->librarianToken())
            ->postJson('/api/v1/books', [
                'title' => '',
                'language' => 'english',
                'license_type_id' => 9999,
                'authors' => ['not-an-id'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'language', 'license_type_id', 'authors.0']);
    }

    public function test_creating_a_book_requires_at_least_one_author(): void
    {
        $this->withToken($this->librarianToken())
            ->postJson('/api/v1/books', [
                'title' => 'No Authors',
                'language' => 'en',
                'license_type_id' => LicenseType::factory()->create()->id,
                'authors' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('authors');
    }

    public function test_librarian_can_update_a_book(): void
    {
        $book = Book::factory()->create(['title' => 'Original Title']);
        $newAuthor = Author::factory()->create();

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/books/{$book->id}", [
                'title' => 'Updated Title',
                'authors' => [$newAuthor->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.book.title', 'Updated Title')
            ->assertJsonPath('data.book.slug', 'updated-title')
            ->assertJsonPath('data.book.authors.0.id', $newAuthor->id);

        $this->assertDatabaseHas('book_authors', ['book_id' => $book->id, 'author_id' => $newAuthor->id]);
        $this->assertDatabaseCount('book_authors', 1);
    }

    public function test_librarian_can_publish_a_draft_via_update(): void
    {
        $book = Book::factory()->draft()->create();

        $response = $this->withToken($this->librarianToken())
            ->putJson("/api/v1/books/{$book->id}", ['status' => 'active']);

        $response->assertOk()
            ->assertJsonPath('data.book.status', 'active');

        $this->assertNotNull($response->json('data.book.published_at'));
    }

    public function test_updating_a_book_requires_librarian(): void
    {
        $book = Book::factory()->create();

        $this->withToken($this->readerToken())
            ->putJson("/api/v1/books/{$book->id}", ['title' => 'Nope'])
            ->assertStatus(403);
    }

    public function test_deleting_a_book_deactivates_it(): void
    {
        $book = Book::factory()->active()->create();

        $this->withToken($this->librarianToken())
            ->deleteJson("/api/v1/books/{$book->id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'status' => BookStatus::Deactivated->value,
        ]);

        $this->getJson('/api/v1/books?limit=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }
}
