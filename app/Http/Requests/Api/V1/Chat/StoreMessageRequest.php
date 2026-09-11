<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:3000',
            ],

            'type' => [
                'nullable',
                'string',
                'in:text',
            ],

            'reply_to_id' => [
                'nullable',
                'integer',
                'exists:messages,id,deleted_at,NULL',
            ],

            'guest_id' => [
                'nullable',
                'string',
                'max:64',
            ],

            'guest_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' =>
                'စာသားထည့်ရန် လိုအပ်ပါသည်။',

            'body.max' =>
                'စာသားသည် အများဆုံး စာလုံး ၃၀၀၀ အထိသာ ရေးနိုင်ပါသည်။',
        ];
    }
}
