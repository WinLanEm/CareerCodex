<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class AchievementIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'int|nullable',
            'is_approved' => 'boolean|required',
            'workspace_id' => 'int|nullable|exists:workspaces,id',
        ];
    }

    public function messages(): array
    {
        return [
            'page.int' => 'page must be an integer.',
            'is_approved.boolean' => 'is_approved must be an boolean.',
            'is_approved.required' => 'is_approved is required.',
            'workspace_id.int' => 'workspace_id must be an integer.',
            'workspace_id.exists' => 'not found workspace.',
        ];
    }
}
