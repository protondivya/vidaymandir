<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreAuthorRequest;
use App\Http\Requests\Author\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Http\Responses\ApiResponse;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorController extends Controller
{
    /**
     * List authors with optional name search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Author::query()->withCount('books');

        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $query->orderBy('name');

        $paginator = $query->paginate($this->limit($request))->withQueryString();

        return ApiResponse::success(
            AuthorResource::collection($paginator->items()),
            meta: $this->paginateMeta($paginator),
        );
    }

    /**
     * Create a new author.
     */
    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = Author::create($request->validated());

        return ApiResponse::success([
            'author' => new AuthorResource($author),
        ], 201);
    }

    /**
     * Update an existing author.
     */
    public function update(UpdateAuthorRequest $request, Author $author): JsonResponse
    {
        $author->fill($request->validated())->save();

        return ApiResponse::success([
            'author' => new AuthorResource($author),
        ]);
    }

    /**
     * Delete an author. Blocked while books still reference them.
     */
    public function destroy(Author $author): JsonResponse
    {
        if ($author->books()->exists()) {
            return ApiResponse::error('This author still has books in the catalog and cannot be deleted.', 422);
        }

        $author->delete();

        return response()->json(null, 204);
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
}
