<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Resources\BookResource\Pages\CreateBook;
use App\Filament\Resources\BookResource\Pages\EditBook;
use App\Filament\Resources\BookResource\Pages\ListBooks;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Book;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function librarian(): User
    {
        return User::factory()->create(['role' => UserRole::Librarian]);
    }

    private function reader(): User
    {
        return User::factory()->create(['role' => UserRole::Reader]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/reports')->assertRedirect('/admin/login');
    }

    public function test_reader_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->reader())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_librarian_can_access_panel(): void
    {
        $this->actingAs($this->librarian())
            ->get('/admin')
            ->assertOk();

        $this->get('/admin/books')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/reports')->assertOk();
    }

    public function test_librarian_cannot_manage_users(): void
    {
        $this->actingAs($this->librarian())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_dashboard_renders_with_widgets(): void
    {
        Book::factory()->count(3)->create();

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSuccessful()
            ->assertSet('activeTab', null);
    }

    public function test_books_list_page_renders(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListBooks::class)
            ->assertSuccessful();
    }

    public function test_categories_list_page_renders(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListCategories::class)
            ->assertSuccessful();
    }

    public function test_users_list_page_renders(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListUsers::class)
            ->assertSuccessful();
    }

    public function test_reports_page_renders(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSuccessful();
    }

    public function test_create_and_edit_forms_render(): void
    {
        $admin = $this->admin();
        $book = Book::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertSuccessful();

        Livewire::actingAs($admin)
            ->test(CreateBook::class)
            ->assertSuccessful();

        Livewire::actingAs($admin)
            ->test(EditBook::class, ['record' => $book->getKey()])
            ->assertSuccessful();
    }
}
