<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind auth + AdminMiddleware; guests never reach it.
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
            'has_header' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a CSV file to import.',
            'file.mimes' => 'Please upload a CSV file.',
            'file.max' => 'The file is larger than 20 MB. Split it into smaller files.',
        ];
    }
}
