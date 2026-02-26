<?php

namespace App\Http\Requests\DocumentManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_doctype'      => ['required', 'integer', 'min:1'],
            'doc_name'        => ['required', 'string', 'max:100'],
            'doc_description' => ['nullable', 'string'],
            'id_docowner'     => ['required', 'integer', 'min:1'],
            'id_owner'        => ['required', 'integer', 'min:1'],
            'doc_expiration'  => ['nullable', 'date'],
            'file'            => ['nullable', 'file', 'max:51200'], // 50MB
        ];
    }
}
