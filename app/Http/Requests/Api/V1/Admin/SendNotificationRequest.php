<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'body' => [
                'required',
                'string',
                'max:1000',
            ],

            'type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'reference_id' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }
}
