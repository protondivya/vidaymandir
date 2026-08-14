<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookResource extends JsonResource
{
    /**
     * The full detail shape for a single book.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'synopsis' => $this->synopsis,
            'language' => $this->language,
            'page_count' => $this->page_count,
            'word_count' => $this->word_count,
            'cover_image_url' => $this->cover_image_url,
            'has_pdf' => $this->hasPdf(),
            'is_downloadable' => $this->is_downloadable,
            'rights_source' => $this->rights_source,
            'status' => $this->status->value,
            'view_count' => $this->view_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'license_type' => $this->whenLoaded('licenseType', fn () => [
                'id' => $this->licenseType->id,
                'code' => $this->licenseType->code,
                'name' => $this->licenseType->name,
            ]),
            'created_by' => $this->created_by,
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'bio' => $author->bio,
            ])),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])),
        ];
    }
}
