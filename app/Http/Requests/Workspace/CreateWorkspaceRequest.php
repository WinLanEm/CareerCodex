<?php

namespace App\Http\Requests\Workspace;

use App\Enums\WorkspaceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateWorkspaceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => [
                'required',
                'string',
                Rule::enum(WorkspaceTypeEnum::class),
            ],
            'description' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'name is required',
            'name.string' => 'name must be a string',
            'name.max' => 'name is too long, maximum 255 characters',
            'type.required' => 'type is required',
            'type.string' => 'type must be a string',
            sprintf('type.%s', Enum::class) => 'type is not a valid enum value (education, work, personal)',
            'description.string' => 'description must be a string',
            'start_date.date_format' => 'start date format is invalid. required - Y-m-d',
            'end_date.date_format' => 'end date format is invalid. required - Y-m-d',
            'end_date.after' => 'end date must be after start date',
        ];
    }
}
