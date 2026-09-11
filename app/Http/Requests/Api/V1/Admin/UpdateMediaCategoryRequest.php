<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'sometimes',
                'required',
                Rule::in([
                    'audio',
                    'video',
                ]),
            ],

            'image_url' => [
                'nullable',
                'string',
                'max:2048',
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
        ];
    }
}
