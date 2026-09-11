<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                Rule::in([
                    'android',
                    'ios',
                ]),
            ],

            'latest_version' => [
                'required',
                'string',
                'max:30',
            ],

            'latest_build' => [
                'required',
                'integer',
                'min:1',
            ],

            'minimum_version' => [
                'nullable',
                'string',
                'max:30',
            ],

            'minimum_build' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'force_update' => [
                'nullable',
                'boolean',
            ],

            'download_url' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
