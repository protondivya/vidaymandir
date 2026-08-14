<?php

declare(strict_types=1);

namespace App\Http\Requests\Book;

use App\Enums\BookStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:280',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('books', 'slug')->ignore($this->route('book')),
            ],
            'synopsis' => ['sometimes', 'nullable', 'string'],
            'language' => ['sometimes', 'required', 'string', 'regex:/^[a-z]{2}$/i'],
            'page_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2147483647'],
            'word_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2147483647'],
            'cover_image_url' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
            'is_downloadable' => ['sometimes', 'boolean'],
            'license_type_id' => ['sometimes', 'required', 'integer', 'exists:license_types,id'],
            'rights_source' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(array_column(BookStatus::cases(), 'value'))],
            'authors' => ['sometimes', 'array', 'min:1'],
            'authors.*' => ['required', 'integer', 'distinct', 'exists:authors,id'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
