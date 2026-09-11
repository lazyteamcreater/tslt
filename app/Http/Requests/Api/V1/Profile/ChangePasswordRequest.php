<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' =>
                'လက်ရှိစကားဝှက် ထည့်ရန်လိုအပ်ပါသည်။',

            'password.required' =>
                'စကားဝှက်အသစ် ထည့်ရန်လိုအပ်ပါသည်။',

            'password.confirmed' =>
                'စကားဝှက်အသစ် နှစ်ခုမတူညီပါ။',

            'password.min' =>
                'စကားဝှက်သည် အနည်းဆုံး ၈ လုံးရှိရပါမည်။',
        ];
    }
}
