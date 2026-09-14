<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = strtolower(
            trim((string) $this->input('type'))
        );

        $orientation = strtolower(
            trim((string) $this->input(
                'video_orientation',
                ''
            ))
        );

        $this->merge([
            'type' => $type,
            'video_orientation' => $type === 'video'
                ? ($orientation !== ''
                    ? $orientation
                    : 'landscape')
                : null,
        ]);
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
                'required',
                Rule::in([
                    'audio',
                    'video',
                ]),
            ],

            /*
             * Video ဖြစ်ရင် portrait/landscape လိုအပ်ပါတယ်။
             * Audio ဖြစ်ရင် prepareForValidation မှာ null ပြောင်းပါမယ်။
             */
            'video_orientation' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('type') === 'video'
                ),
                'nullable',
                Rule::in([
                    'portrait',
                    'landscape',
                ]),
            ],

            'title' => [
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
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'video_orientation.required' =>
                'Video ပုံစံရွေးရန် လိုအပ်ပါသည်။',

            'video_orientation.in' =>
                'Video ပုံစံသည် portrait သို့မဟုတ် landscape ဖြစ်ရပါမည်။',
        ];
    }
}
