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
        ];
    }
    public function messages(): array
    {
        return [
            'title.string' => 'title must be a string.',
            'title.max' => 'title must be less than 255 characters.',
            'is_approved.boolean' => 'is_approved value must be boolean.',
        ];
    }
}
