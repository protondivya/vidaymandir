<?php

declare(strict_types=1);

namespace App\Http\Requests\Book;

use App\Enums\BookStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:280', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:books,slug'],
            'synopsis' => ['nullable', 'string'],
            'language' => ['required', 'string', 'regex:/^[a-z]{2}$/i'],
            'page_count' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'word_count' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'cover_image_url' => ['nullable', 'string', 'url', 'max:500'],
            'license_type_id' => ['required', 'integer', 'exists:license_types,id'],
            'rights_source' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(array_column(BookStatus::cases(), 'value'))],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['required', 'integer', 'distinct', 'exists:authors,id'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
