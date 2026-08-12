<?php

declare(strict_types=1);

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class UploadBookPdfRequest extends FormRequest
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
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
