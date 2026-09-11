<?php

namespace App\Http\Requests\Api\V1\Voice;

use Illuminate\Foundation\Http\FormRequest;

class StartVoiceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'nullable',
                'string',
                'max:150',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'ခေါင်းစဉ်သည် အလွန်ရှည်လျားနေပါသည်။',
        ];
    }
}
