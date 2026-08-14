<?php

declare(strict_types=1);

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReadingProgressRequest extends FormRequest
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
            'current_page' => ['nullable', 'integer', 'min:1'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * At least one progress field must be supplied.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('current_page') || $this->filled('percent')) {
                return;
            }

            $validator->errors()->add('current_page', 'Either current_page or percent must be provided.');
        });
    }
}
