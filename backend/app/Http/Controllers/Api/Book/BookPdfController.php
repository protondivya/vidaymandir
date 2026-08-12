<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Book;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\UploadBookPdfRequest;
use App\Http\Resources\BookResource;
use App\Http\Responses\ApiResponse;
use App\Models\Book;
use App\Services\BookPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class BookPdfController extends Controller
{
    public function __construct(
        private readonly BookPdfService $pdfs,
    ) {}

    /**
     * Stream the original PDF inline for online reading.
     */
    public function view(Request $request, Book $book): BinaryFileResponse
    {
        $this->assertReadable($book);

        $book->increment('view_count');

        return response()->file($book->pdfPath(), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$book->slug.'.pdf"',
        ]);
    }

    /**
     * Stream a watermarked copy of the PDF as a download.
     */
    public function download(Request $request, Book $book): BinaryFileResponse|JsonResponse
    {
        $this->assertReadable($book);

        if (! $book->is_downloadable) {
            return ApiResponse::error('Downloading this book is disabled by the publisher.', 403);
        }

        try {
            $mark = sprintf(
                '%s <%s> - %s',
                $request->user()->display_name,
                $request->user()->email,
                now()->format('Y-m-d H:i'),
            );

            $watermarkedPath = $this->pdfs->watermark($book, $mark);
        } catch (Throwable $e) {
            return ApiResponse::error('The PDF could not be processed for download.', 422);
        }

        return response()
            ->download($watermarkedPath, $book->slug.'-download.pdf', [
                'Content-Type' => 'application/pdf',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Upload or replace the PDF file for a book.
     */
    public function upload(UploadBookPdfRequest $request, Book $book): JsonResponse
    {
        $this->pdfs->store($book, $request->file('pdf'));

        if ($book->page_count === null) {
            try {
                $book->forceFill([
                    'page_count' => $this->pdfs->pageCount($book->pdfPath()),
                ])->save();
            } catch (Throwable) {
                // Keep page_count null when the uploaded PDF cannot be parsed.
            }
        }

        $book->load(['authors', 'categories', 'licenseType']);

        return ApiResponse::success([
            'book' => new BookResource($book),
        ]);
    }

    /**
     * Ensure the book is publicly readable and has a stored PDF.
     */
    private function assertReadable(Book $book): void
    {
        if ($book->status !== BookStatus::Active || ! $book->hasPdf()) {
            throw new NotFoundHttpException('Book PDF not found.');
        }
    }
}
