<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Author */
class AuthorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bio' => $this->bio,
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'books_count' => $this->books_count ?? 0,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
