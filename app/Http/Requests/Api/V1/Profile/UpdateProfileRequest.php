<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $this->user()->id
                ),
            ],

            'avatar_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'male',
                    'female',
                    'other',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'အမည်ထည့်ရန် လိုအပ်ပါသည်။',

            'email.required' =>
                'အီးမေးလ်ထည့်ရန် လိုအပ်ပါသည်။',

            'email.email' =>
                'အီးမေးလ်လိပ်စာ မှန်ကန်စွာထည့်ပါ။',

            'email.unique' =>
                'ဤအီးမေးလ်ကို အခြားအကောင့်တစ်ခုမှ အသုံးပြုထားပြီးဖြစ်ပါသည်။',

            'avatar_url.url' =>
                'Profile ပုံ Link မမှန်ကန်ပါ။',

            'gender.in' =>
                'Gender အချက်အလက် မမှန်ကန်ပါ။',
        ];
    }
}
