<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function librarianToken(): string
    {
        return User::factory()->create(['role' => 'librarian'])->createAccessToken()['token'];
    }

    private function adminToken(): string
    {
        return User::factory()->create(['role' => 'admin'])->createAccessToken()['token'];
    }

    private function readerToken(): string
    {
        return User::factory()->create(['role' => 'reader'])->createAccessToken()['token'];
    }

    public function test_category_tree_returns_nested_children_with_counts(): void
    {
        $parent = Category::factory()->create(['name' => 'Fiction']);
        $child = Category::factory()->create(['name' => 'Classics', 'parent_id' => $parent->id]);
        Book::factory()->active()->create()->categories()->attach($child);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fiction')
            ->assertJsonPath('data.0.children.0.name', 'Classics')
            ->assertJsonPath('data.0.children.0.books_count', 1);
    }

    public function test_category_books_lists_active_books(): void
    {
        $category = Category::factory()->create();
        $active = Book::factory()->active()->create();
        $active->categories()->attach($category);
        $draft = Book::factory()->draft()->create();
        $draft->categories()->attach($category);

        $this->getJson("/api/v1/categories/{$category->slug}/books")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_category_books_includes_descendant_books(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $book = Book::factory()->active()->create();
        $book->categories()->attach($child);

        $this->getJson("/api/v1/categories/{$parent->slug}/books")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $book->id);
    }

    public function test_creating_a_category_requires_librarian(): void
    {
        $this->withToken($this->readerToken())
            ->postJson('/api/v1/categories', ['name' => 'Science'])
            ->assertStatus(403);
    }

    public function test_librarian_can_create_a_category(): void
    {
        $this->withToken($this->librarianToken())
            ->postJson('/api/v1/categories', ['name' => 'Science Fiction'])
            ->assertCreated()
            ->assertJsonPath('data.category.slug', 'science-fiction');
    }

    public function test_librarian_can_update_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/categories/{$category->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.category.name', 'New Name')
            ->assertJsonPath('data.category.slug', 'new-name');
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/categories/{$category->id}", ['parent_id' => $category->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_category_cannot_be_moved_under_a_descendant(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->withToken($this->librarianToken())
            ->putJson("/api/v1/categories/{$parent->id}", ['parent_id' => $child->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_deleting_a_category_requires_admin(): void
    {
        $category = Category::factory()->create();

        $this->withToken($this->librarianToken())
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(403);
    }

    public function test_category_with_books_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Book::factory()->create()->categories()->attach($category);

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_delete_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
