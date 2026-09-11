<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
                'string',
                'email',
                'max:255',
                'unique:users,email',
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
            'name.required' => 'အမည်ထည့်ရန် လိုအပ်ပါသည်။',

            'email.required' => 'အီးမေးလ်ထည့်ရန် လိုအပ်ပါသည်။',
            'email.email' => 'အီးမေးလ်လိပ်စာ မှန်ကန်စွာထည့်ပါ။',
            'email.unique' => 'ဤအီးမေးလ်ဖြင့် အကောင့်ရှိပြီးသားဖြစ်ပါသည်။',

            'password.required' => 'စကားဝှက်ထည့်ရန် လိုအပ်ပါသည်။',
            'password.confirmed' => 'စကားဝှက်နှစ်ခု မတူညီပါ။',
            'password.min' => 'စကားဝှက်သည် အနည်းဆုံး ၈ လုံးရှိရပါမည်။',
        ];
    }
}
