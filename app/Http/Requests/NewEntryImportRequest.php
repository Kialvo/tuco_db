<?php
// app/Http/Requests/NewEntryImportRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NewEntryImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // app/Http/Requests/NewEntryImportRequest.php

    public function rules(): array
    {
        return [
            'file' => ['required','file','mimes:csv,txt','max:20480'],
            // removed: 'create_missing_contacts'
            'create_missing_categories' => ['nullable','boolean'],
            'dedupe_by_domain'          => ['nullable','boolean'],
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'ok'     => false,
            'message'=> 'Validation error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
