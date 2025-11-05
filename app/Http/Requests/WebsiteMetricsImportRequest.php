<?php
// app/Http/Requests/WebsiteMetricsImportRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteMetricsImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'          => ['nullable','file','mimes:csv,txt','max:20480'],
            'sheet_url'     => ['nullable','string','max:2048'],
            'has_header'    => ['nullable','boolean'],
            'decimal_comma' => ['nullable','boolean'],
        ];
    }

    public function messages(): array
    {
        return ['file.mimes' => 'Please upload a CSV file.'];
    }
}
