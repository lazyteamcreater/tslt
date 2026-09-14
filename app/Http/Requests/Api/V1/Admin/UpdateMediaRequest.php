<?php

namespace App\Http\Requests\Api\V1\Admin;


use Illuminate\Validation\Rule;

class UpdateMediaRequest extends StoreMediaRequest
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
                'sometimes',
                'required',
                Rule::in([
                    'audio',
                    'video',
                ]),
            ],

            'title' => [
                'sometimes',
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
                'sometimes',
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
                'sometimes',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
