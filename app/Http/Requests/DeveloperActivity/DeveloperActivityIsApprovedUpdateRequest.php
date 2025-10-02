<?php

namespace App\Http\Requests\DeveloperActivity;

use Illuminate\Foundation\Http\FormRequest;

class DeveloperActivityIsApprovedUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'developer_activity_ids'   => ['required', 'array'],
            'developer_activity_ids.*' => ['required', 'integer', 'exists:developer_activities,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'developer_activity_ids.*.required' => 'Developer activity id is required.',
            'developer_activity_ids.*.integer' => 'Developer activity id is integer.',
            'developer_activity_ids.*.exists' => 'Developer activity id is invalid.',
            'developer_activity_ids.required' => 'Developer activity ids is required.',
            'developer_activity_ids.array' => 'Developer activity ids is array.',
        ];
    }
}
