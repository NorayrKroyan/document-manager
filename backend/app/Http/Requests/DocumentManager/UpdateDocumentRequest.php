<?php

namespace App\Http\Requests\DocumentManager;

use App\Models\DocType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDocumentRequest extends FormRequest
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

            // required only for some doctypes (enforced in withValidator)
            'doc_expiration'  => ['nullable', 'date'],

            'file'            => ['nullable', 'file', 'max:51200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $doctypeId = (int) $this->input('id_doctype', 0);
            if ($doctypeId <= 0) return;

            $requireExpire = (int) DocType::query()
                ->where('id_doctype', $doctypeId)
                ->value('require_expire');

            // ✅ if require_expire = 1 => expiration is required
            if ($requireExpire === 1) {
                $exp = trim((string) $this->input('doc_expiration', ''));
                if ($exp === '') {
                    $v->errors()->add('doc_expiration', 'Expiration is required for this document type.');
                }
                return;
            }

            // ✅ if require_expire = 0 => expiration must be NULL (ignore any client value)
            $this->merge(['doc_expiration' => null]);
        });
    }
}
