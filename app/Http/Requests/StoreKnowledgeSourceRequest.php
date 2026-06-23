<?php

namespace App\Http\Requests;

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
                    'knowledge.max_files_per_upload',
                    10
                ),
            ],

            'files.*' => [
                'required',

                File::types([
                    'pdf',
                    'docx',
                    'txt',
                    'csv',
                    'xlsx',
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                ])->max(
                    config(
                        'knowledge.max_file_size_kb',
                        51200
                    )
                ),
            ],
        ];
    }
}
