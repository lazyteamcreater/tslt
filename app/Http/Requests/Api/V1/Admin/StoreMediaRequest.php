<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_category_id' => [
                'nullable',
                'integer',
                'exists:media_categories,id',
            ],

            'type' => [
                'required',
                Rule::in([
                    'audio',
                    'video',
                ]),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'speaker' => [
                'nullable',
                'string',
                'max:150',
            ],

            'source_url' => [
                'required',
                'string',
                'max:5000',
            ],

            'thumbnail_url' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'duration_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
