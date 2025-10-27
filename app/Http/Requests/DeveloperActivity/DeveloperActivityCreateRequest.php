<?php

namespace App\Http\Requests\DeveloperActivity;

use App\Enums\DeveloperActivityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class DeveloperActivityCreateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'repository_name' => 'required|string|max:255',
            'type' => [
                'required',
                'string',
                Rule::enum(DeveloperActivityEnum::class),
            ],
            'is_approved' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'url' => 'nullable|url',
            'completed_at' => 'nullable|date',
        ];
    }
    public function messages()
    {
        return [
            'repository_name.required' => 'repository name is required.',
            'repository_name.string' => 'repository name must be string.',
            'repository_name.max' => 'repository name is too long.',
            'type.required' => 'type is required.',
            'type.string' => 'type must be string.',
            sprintf('type.%s', Enum::class) => '":input" is not valid type. Available: '.implode(', ', DeveloperActivityEnum::getValues()),'',
            'is_approved.boolean' => 'is_approved must be boolean.',
            'title.required' => 'title is required.',
            'title.string' => 'title must be string.',
            'title.max' => 'title is too long.',
            'url.url' => 'url must be url.',
            'completed_at.date' => 'completed_at must be date.',
        ];
    }
}
