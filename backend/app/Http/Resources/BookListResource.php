<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookListResource extends JsonResource
{
    /**
     * The compact shape used for catalog listings.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'language' => $this->language,
            'cover_image_url' => $this->cover_image_url,
            'has_pdf' => $this->hasPdf(),
            'is_downloadable' => $this->is_downloadable,
            'status' => $this->status->value,
            'view_count' => $this->view_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'authors' => $this->authors->map(fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
            ]),
            'categories' => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]),
        ];
    }
}
