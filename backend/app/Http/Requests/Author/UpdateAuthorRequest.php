<?php

declare(strict_types=1);

namespace App\Http\Requests\Author;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAuthorRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'birth_year' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2100'],
            'death_year' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2100'],
        ];
    }

    /**
     * A death year must not precede the birth year.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $author = $this->route('author');

            $birth = $this->input('birth_year', $author->birth_year);
            $death = $this->input('death_year', $author->death_year);

            if ($birth === null || $death === null) {
                return;
            }

            if ((int) $death < (int) $birth) {
                $validator->errors()->add('death_year', 'The death year cannot be earlier than the birth year.');
            }
        });
    }
}
