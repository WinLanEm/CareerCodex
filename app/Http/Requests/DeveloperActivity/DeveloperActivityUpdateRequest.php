<?php

namespace App\Http\Requests\DeveloperActivity;

use Illuminate\Foundation\Http\FormRequest;

class DeveloperActivityUpdateRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'is_approved' => 'nullable|boolean',
            'repository_name' => 'nullable|string|max:255',
            'url' => 'nullable|string',
            'additions' => 'nullable|int',
            'deletions' => 'nullable|int',
        ];
    }
    public function messages(): array
    {
        return [
            'title.string' => 'title must be a string.',
            'title.max' => 'title must be less than 255 characters.',
            'is_approved.boolean' => 'is_approved value must be boolean.',
            'repository_name.string' => 'repository_name must be a string.',
            'repository_name.max' => 'repository_name must be less than 255 characters.',
            'url.string' => 'url must be a string.',
            'additions.int' => 'additions must be an integer.',
            'deletions.int' => 'deletions must be an integer.',
        ];
    }
}
