<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorTest extends TestCase
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

    public function test_authors_are_listed_with_search_and_pagination(): void
    {
        Author::factory()->create(['name' => 'Mark Twain']);
        Author::factory()->create(['name' => 'Emily Dickinson']);
        Author::factory()->count(25)->create();

        $this->getJson('/api/v1/authors?limit=5')
            ->assertOk()
            ->assertJsonPath('meta.total', 27)
            ->assertJsonPath('meta.limit', 5)
            ->assertJsonCount(5, 'data');

        $this->getJson('/api/v1/authors?q=twain')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Mark Twain');
    }

    public function test_creating_an_author_requires_librarian(): void
    {
        $this->withToken($this->readerToken())
            ->postJson('/api/v1/authors', ['name' => 'Anonymous'])
            ->assertStatus(403);
    }

    public function test_librarian_can_create_an_author(): void
    {
        $this->withToken($this->librarianToken())
            ->postJson('/api/v1/authors', [
                'name' => 'Virginia Woolf',
                'bio' => 'English modernist writer.',
                'birth_year' => 1882,
                'death_year' => 1941,
            ])
            ->assertCreated()
            ->assertJsonPath('data.author.name', 'Virginia Woolf')
            ->assertJsonPath('data.author.birth_year', 1882);
    }

    public function test_creating_an_author_validates_input(): void
    {
        $this->withToken($this->librarianToken())
            ->postJson('/api/v1/authors', ['name' => '', 'birth_year' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'birth_year']);
    }

    public function test_librarian_can_update_an_author(): void
    {
        $author = Author::factory()->create(['name' => 'Old Name']);

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/authors/{$author->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.author.name', 'New Name');
    }

    public function test_author_death_year_cannot_precede_birth_year(): void
    {
        $author = Author::factory()->create(['birth_year' => 1900, 'death_year' => 1970]);

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/authors/{$author->id}", ['death_year' => 1800])
            ->assertStatus(422)
            ->assertJsonValidationErrors('death_year');
    }

    public function test_author_with_books_cannot_be_deleted(): void
    {
        $author = Author::factory()->create();
        Book::factory()->create()->authors()->attach($author);

        $this->withToken($this->librarianToken())
            ->deleteJson("/api/v1/authors/{$author->id}")
            ->assertStatus(422);
    }

    public function test_librarian_can_delete_an_unused_author(): void
    {
        $author = Author::factory()->create();

        $this->withToken($this->librarianToken())
            ->deleteJson("/api/v1/authors/{$author->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
    }
}
