<?php

namespace App\Http\Requests\DeveloperActivity;

use App\Enums\AuthServiceEnum;
use App\Enums\DeveloperActivityEnum;
use App\Models\DeveloperActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class DeveloperActivityIndexRequest extends FormRequest
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
            'type' => [
                'nullable',
                'string',
                Rule::enum(DeveloperActivityEnum::class),
            ],
            'is_approved' => 'boolean|nullable',
            'start_at' => 'date|nullable|date_format:Y-m-d',
            'end_at' => 'date|nullable|after:start_at|date_format:Y-m-d',
        ];
    }
    public function messages(): array
    {
        return [
            'page.int' => 'page must be an integer.',
            'type.string' => 'type must be a string.',
            'is_approved.boolean' => 'is_approved must be a boolean.',
            'start_at.date' => 'start_at must be a date.',
            'start_at.format' => 'start_at must be a date. YYYY-MM-DD format.',
            'end_at.date' => 'end_at must be a date.',
            'end_at.format' => 'end_at must be a date. YYYY-MM-DD format.',
            'end_at.after' => 'end_at must be after start at.',
            sprintf('type.%s', Enum::class) => '":input" is not valid type. Available: '.implode(', ', DeveloperActivityEnum::getValues()),'',
        ];
    }
}
