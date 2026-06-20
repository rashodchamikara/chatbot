<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreKnowledgeSourceRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => [
                'required',
                'array',
                'min:1',
                'max:' . config(
                    'knowledge.max_files_per_upload'
                ),
            ],

            'files.*' => [
                'required',

                File::types(
                    config(
                        'knowledge.allowed_extensions'
                    )
                )->max(
                    config(
                        'knowledge.max_file_size_kb'
                    )
                ),
            ],
        ];
    }
}
