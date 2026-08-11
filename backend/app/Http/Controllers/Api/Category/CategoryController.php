<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\BookListResource;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List the category tree with per-node book counts.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return ApiResponse::success($this->buildTree($categories));
    }

    /**
     * List active books in a category (including its descendants).
     */
    public function books(Category $category, Request $request): JsonResponse
    {
        $query = Book::query()
            ->with(['authors', 'categories'])
            ->active()
            ->whereHas('categories', fn ($builder) => $builder->whereIn('categories.id', $category->descendantIds()))
            ->orderByDesc('published_at');

        $paginator = $query->paginate($this->limit($request))->withQueryString();

        return ApiResponse::success(
            BookListResource::collection($paginator->items()),
            meta: $this->paginateMeta($paginator),
        );
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return ApiResponse::success([
            'category' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (! array_key_exists('slug', $data) && array_key_exists('name', $data) && $data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name']);
        }

        $category->fill($data)->save();

        return ApiResponse::success([
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Delete a category. Blocked while children or books reference it.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return ApiResponse::error('This category has child categories and cannot be deleted.', 422);
        }

        if ($category->books()->exists()) {
            return ApiResponse::error('This category still contains books and cannot be deleted.', 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Build a nested category tree from a flat collection.
     *
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function buildTree(Collection $categories, ?int $parentId = null): array
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'books_count' => (int) $category->books_count,
                'children' => $this->buildTree($categories, $category->id),
            ])
            ->all();
    }

    /**
     * Generate a unique slug for the given name.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
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
