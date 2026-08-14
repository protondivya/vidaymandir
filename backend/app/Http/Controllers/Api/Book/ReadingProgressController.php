<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Book;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\UpdateReadingProgressRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Book;
use App\Models\ReadingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReadingProgressController extends Controller
{
    /**
     * Return the reading progress of the authenticated user for a book.
     */
    public function show(Request $request, Book $book): JsonResponse
    {
        $this->assertReadable($book);

        $progress = ReadingProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->first();

        return ApiResponse::success([
            'progress' => $progress ? $this->payload($progress) : null,
        ]);
    }

    /**
     * Store or update the reading progress of the authenticated user for a book.
     */
    public function update(UpdateReadingProgressRequest $request, Book $book): JsonResponse
    {
        $this->assertReadable($book);

        $data = array_filter($request->validated(), fn ($value) => $value !== null);

        $progress = ReadingProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            $data,
        );

        return ApiResponse::success([
            'progress' => $this->payload($progress),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ReadingProgress $progress): array
    {
        return [
            'book_id' => $progress->book_id,
            'current_page' => $progress->current_page,
            'percent' => (float) $progress->percent,
            'updated_at' => $progress->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Progress is only tracked for publicly readable books.
     */
    private function assertReadable(Book $book): void
    {
        if ($book->status !== BookStatus::Active) {
            throw new NotFoundHttpException('Book not found.');
        }
    }
}
