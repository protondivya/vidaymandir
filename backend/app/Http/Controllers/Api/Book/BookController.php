<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Book;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookResource;
use App\Http\Responses\ApiResponse;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookController extends Controller
{
    /**
     * List publicly available books with search, filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::query()
            ->with(['authors', 'categories'])
            ->active();

        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('synopsis', 'like', "%{$q}%")
                    ->orWhereHas('authors', fn ($author) => $author->where('name', 'like', "%{$q}%"));
            });
        }

        if (($categorySlug = (string) $request->query('category', '')) !== '') {
            $category = Category::where('slug', $categorySlug)->first();

            if ($category !== null) {
                $query->whereIn('id', $this->booksInCategories($category));
            }
        }

        if (($language = (string) $request->query('language', '')) !== '') {
            $query->where('language', $language);
        }

        $query->orderBy(...$this->sortClause((string) $request->query('sort', 'newest')));

        $paginator = $query->paginate($this->limit($request))->withQueryString();

        return ApiResponse::success(
            BookListResource::collection($paginator->items()),
            meta: $this->paginateMeta($paginator),
        );
    }

    /**
     * List every book regardless of status (librarian management view).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Book::query()->with(['authors', 'categories']);

        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhereHas('authors', fn ($author) => $author->where('name', 'like', "%{$q}%"));
            });
        }

        if (($status = (string) $request->query('status', '')) !== '') {
            $query->where('status', $status);
        }

        $query->latest('id');

        $paginator = $query->paginate($this->limit($request))->withQueryString();

        return ApiResponse::success(
            BookListResource::collection($paginator->items()),
            meta: $this->paginateMeta($paginator),
        );
    }

    /**
     * Show a single publicly visible book by id or slug.
     */
    public function show(Book $book): JsonResponse
    {
        if ($book->status !== BookStatus::Active) {
            return ApiResponse::error('Book not found.', 404);
        }

        $book->load(['authors', 'categories', 'licenseType']);

        return ApiResponse::success([
            'book' => new BookResource($book),
        ]);
    }

    /**
     * Create a new book (status defaults to draft).
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = BookStatus::tryFrom($data['status'] ?? BookStatus::Draft->value) ?? BookStatus::Draft;

        $book = Book::create([
            'title' => $data['title'],
            'slug' => $data['slug'] ?? Book::uniqueSlug($data['title']),
            'synopsis' => $data['synopsis'] ?? null,
            'language' => $data['language'],
            'page_count' => $data['page_count'] ?? null,
            'word_count' => $data['word_count'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'is_downloadable' => $data['is_downloadable'] ?? true,
            'license_type_id' => $data['license_type_id'],
            'rights_source' => $data['rights_source'] ?? null,
            'status' => $status,
            'view_count' => 0,
            'created_by' => $request->user()->id,
            'published_at' => $status === BookStatus::Active ? now() : null,
        ]);

        $book->authors()->sync($data['authors']);
        $book->categories()->sync($data['categories'] ?? []);

        $book->load(['authors', 'categories', 'licenseType']);

        return ApiResponse::success([
            'book' => new BookResource($book),
        ], 201);
    }

    /**
     * Update an existing book.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('status', $data)) {
            $status = BookStatus::tryFrom($data['status']) ?? $book->status;

            $data['status'] = $status;
            $data['published_at'] = match (true) {
                $status === BookStatus::Active && $book->published_at === null => now(),
                $status !== BookStatus::Active => null,
                default => $book->published_at,
            };
        }

        if (! array_key_exists('slug', $data) && array_key_exists('title', $data) && $data['title'] !== $book->title) {
            $data['slug'] = Book::uniqueSlug($data['title']);
        }

        $book->fill($data)->save();

        if (array_key_exists('authors', $data)) {
            $book->authors()->sync($data['authors']);
        }

        if (array_key_exists('categories', $data)) {
            $book->categories()->sync($data['categories']);
        }

        $book->load(['authors', 'categories', 'licenseType']);

        return ApiResponse::success([
            'book' => new BookResource($book),
        ]);
    }

    /**
     * Soft-delete a book by deactivating it.
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->forceFill([
            'status' => BookStatus::Deactivated,
            'published_at' => null,
        ])->save();

        return response()->json(null, 204);
    }

    /**
     * Resolve the sort column and direction for the catalog listing.
     *
     * @return array{0: string, 1: string}
     */
    private function sortClause(string $sort): array
    {
        return match ($sort) {
            'popular' => ['view_count', 'desc'],
            'title_asc' => ['title', 'asc'],
            'title_desc' => ['title', 'desc'],
            'oldest' => ['published_at', 'asc'],
            default => ['published_at', 'desc'],
        };
    }

    /**
     * The number of results per page (default 20, capped at 100).
     */
    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 20), 1), 100);
    }

    /**
     * Build the pagination meta payload.
     *
     * @return array<string, int>
     */
    private function paginateMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * The ids of every book belonging to the category or any of its descendants.
     *
     * @return list<int>
     */
    private function booksInCategories(Category $category): array
    {
        $categoryIds = $category->descendantIds();

        return Book::whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->pluck('id')
            ->all();
    }
}
