<?php

namespace App\Http\Requests\Api\V1\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
            ],

            'platform' => [
                'nullable',
                Rule::in([
                    'android',
                    'ios',
                    'web',
                ]),
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:150',
            ],
        ];
    }
}
