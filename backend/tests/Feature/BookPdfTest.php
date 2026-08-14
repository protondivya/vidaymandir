<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\BookPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function librarianToken(): string
    {
        return User::factory()->create(['role' => 'librarian'])->createAccessToken()['token'];
    }

    private function readerToken(): string
    {
        return User::factory()->create(['role' => 'reader'])->createAccessToken()['token'];
    }

    /**
     * Authenticate as a fresh token, forgetting previously resolved guards so
     * in-process requests are not served the previous user.
     */
    private function asToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    /**
     * Create an active book backed by a real generated PDF on the local disk.
     */
    private function readableBook(array $attributes = []): Book
    {
        $book = Book::factory()->active()->create($attributes);
        app(BookPdfService::class)->generateDemoPdf($book->title, 'books/'.$book->slug.'.pdf');
        $book->forceFill(['pdf_file' => 'books/'.$book->slug.'.pdf'])->save();

        return $book;
    }

    public function test_viewing_a_pdf_requires_authentication(): void
    {
        $book = $this->readableBook();

        $this->getJson("/api/v1/books/{$book->id}/pdf")
            ->assertUnauthorized();
    }

    public function test_reader_can_view_an_active_books_pdf(): void
    {
        $book = $this->readableBook();

        $response = $this->withToken($this->readerToken())
            ->get("/api/v1/books/{$book->id}/pdf");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$book->slug.'.pdf"');

        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_viewing_increments_the_view_count(): void
    {
        $book = $this->readableBook();

        $this->withToken($this->readerToken())
            ->get("/api/v1/books/{$book->id}/pdf")
            ->assertOk();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'view_count' => 1]);
    }

    public function test_viewing_a_draft_books_pdf_returns_404(): void
    {
        $book = Book::factory()->draft()->create();
        app(BookPdfService::class)->generateDemoPdf($book->title, 'books/'.$book->slug.'.pdf');
        $book->forceFill(['pdf_file' => 'books/'.$book->slug.'.pdf'])->save();

        $this->withToken($this->readerToken())
            ->get("/api/v1/books/{$book->id}/pdf")
            ->assertNotFound();
    }

    public function test_viewing_a_book_without_a_pdf_returns_404(): void
    {
        $book = Book::factory()->active()->create();

        $this->withToken($this->readerToken())
            ->get("/api/v1/books/{$book->id}/pdf")
            ->assertNotFound();
    }

    public function test_downloading_a_pdf_requires_authentication(): void
    {
        $book = $this->readableBook();

        $this->getJson("/api/v1/books/{$book->id}/download")
            ->assertUnauthorized();
    }

    public function test_reader_can_download_a_watermarked_pdf(): void
    {
        $book = $this->readableBook();

        $response = $this->withToken($this->readerToken())
            ->get("/api/v1/books/{$book->id}/download");

        $response->assertOk()
            ->assertDownload($book->slug.'-download.pdf');

        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_download_is_blocked_for_non_downloadable_books(): void
    {
        $book = $this->readableBook(['is_downloadable' => false]);

        $this->withToken($this->readerToken())
            ->getJson("/api/v1/books/{$book->id}/download")
            ->assertForbidden();
    }

    public function test_uploading_a_pdf_requires_librarian(): void
    {
        $book = Book::factory()->create();
        $file = UploadedFile::fake()->create('book.pdf', 10, 'application/pdf');

        $this->asToken($this->readerToken())
            ->post("/api/v1/books/{$book->id}/pdf", ['pdf' => $file])
            ->assertForbidden();

        $this->asToken($this->librarianToken())
            ->post("/api/v1/books/{$book->id}/pdf", ['pdf' => $file])
            ->assertOk();
    }

    public function test_librarian_can_upload_a_pdf(): void
    {
        $book = Book::factory()->create(['page_count' => null]);
        $source = 'books/upload-source-'.$book->slug.'.pdf';
        app(BookPdfService::class)->generateDemoPdf($book->title, $source);
        $file = new UploadedFile(
            Storage::disk('local')->path($source),
            'book.pdf',
            'application/pdf',
            null,
            true,
        );

        $response = $this->withToken($this->librarianToken())
            ->post("/api/v1/books/{$book->id}/pdf", ['pdf' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.book.has_pdf', true);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'page_count' => app(BookPdfService::class)->pageCount(Storage::disk('local')->path($source)),
        ]);
    }

    public function test_upload_rejects_non_pdf_files(): void
    {
        $book = Book::factory()->create();

        $this->withToken($this->librarianToken())
            ->post("/api/v1/books/{$book->id}/pdf", [
                'pdf' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pdf');
    }

    public function test_progress_requires_authentication(): void
    {
        $book = $this->readableBook();

        $this->getJson("/api/v1/books/{$book->id}/progress")
            ->assertUnauthorized();

        $this->putJson("/api/v1/books/{$book->id}/progress", ['current_page' => 3])
            ->assertUnauthorized();
    }

    public function test_reader_can_store_and_fetch_progress(): void
    {
        $book = $this->readableBook();
        $token = $this->readerToken();

        $this->withToken($token)
            ->putJson("/api/v1/books/{$book->id}/progress", ['current_page' => 12, 'percent' => 40.5])
            ->assertOk()
            ->assertJsonPath('data.progress.current_page', 12)
            ->assertJsonPath('data.progress.percent', 40.5);

        $this->withToken($token)
            ->getJson("/api/v1/books/{$book->id}/progress")
            ->assertOk()
            ->assertJsonPath('data.progress.current_page', 12)
            ->assertJsonPath('data.progress.percent', 40.5);
    }

    public function test_progress_is_upserted_per_user_and_book(): void
    {
        $book = $this->readableBook();
        $token = $this->readerToken();

        $this->withToken($token)
            ->putJson("/api/v1/books/{$book->id}/progress", ['current_page' => 5])
            ->assertOk();

        $this->withToken($token)
            ->putJson("/api/v1/books/{$book->id}/progress", ['current_page' => 9])
            ->assertOk()
            ->assertJsonPath('data.progress.current_page', 9);

        $this->assertDatabaseCount('reading_progress', 1);
    }

    public function test_progress_is_isolated_between_readers(): void
    {
        $book = $this->readableBook();
        $first = $this->readerToken();
        $second = $this->readerToken();

        $this->asToken($first)
            ->putJson("/api/v1/books/{$book->id}/progress", ['current_page' => 7])
            ->assertOk();

        $this->asToken($second)
            ->getJson("/api/v1/books/{$book->id}/progress")
            ->assertOk()
            ->assertJsonPath('data.progress', null);
    }

    public function test_progress_validates_input(): void
    {
        $book = $this->readableBook();

        $this->withToken($this->readerToken())
            ->putJson("/api/v1/books/{$book->id}/progress", ['percent' => 150])
            ->assertStatus(422)
            ->assertJsonValidationErrors('percent');

        $this->withToken($this->readerToken())
            ->putJson("/api/v1/books/{$book->id}/progress", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_page');
    }

    public function test_progress_for_a_draft_book_returns_404(): void
    {
        $book = Book::factory()->draft()->create();

        $this->withToken($this->readerToken())
            ->getJson("/api/v1/books/{$book->id}/progress")
            ->assertNotFound();
    }
}
