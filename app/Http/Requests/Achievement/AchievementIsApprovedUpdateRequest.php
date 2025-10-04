<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class AchievementIsApprovedUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'achievement_ids'   => ['required', 'array'],
            'achievement_ids.*' => ['required', 'integer', 'exists:achievements,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'achievement_ids.*.required' => 'Achievement id is required.',
            'achievement_ids.*.integer' => 'Achievement id is integer.',
            'achievement_ids.*.exists' => 'Achievement id is invalid.',
            'achievement_ids.required' => 'Achievement ids is required.',
            'achievement_ids.array' => 'Achievement ids is array.',
        ];
    }
}
