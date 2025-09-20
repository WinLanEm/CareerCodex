<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceAchievementUpdateRequest extends FormRequest
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
            'description' => 'nullable|string',
            'result' => 'nullable|string',
            'hours_spent' => 'nullable|int|between:1,999',
            'date' => 'nullable|date|date_format:Y-m-d',
            'skills' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'Title must be a string',
            'title.max' => 'Title is too long, maximum 255 characters',
            'description.string' => 'Description must be a string',
            'result.string' => 'Result must be a string',
            'hours_spent.int' => 'Hours spent must be an integer',
            'hours_spent.between' => 'Hours spent must be between 1 and 999',
            'date.date' => 'Date must be a date',
            'date.date_format' => 'Date format must be Y-m-d',
            'skills.array' => 'Skills must be an array',
        ];
    }
}
