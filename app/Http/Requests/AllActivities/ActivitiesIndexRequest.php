<?php

namespace App\Http\Requests\AllActivities;

use Illuminate\Foundation\Http\FormRequest;

class ActivitiesIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'type' => 'sometimes|in:task,commit,pull_request,all',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The type must be one of: task, commit, pull_request, all',
            'date_from.before_or_equal' => 'The start date must be before or equal to the end date',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date',
            'cursor.string' => 'The cursor must be a string',
            'per_page.integer' => 'The cursor must be an integer',
            'per_page.min' => 'per_page min 1',
            'per_page.max' => 'per_page max 50',
            'date_from.date' => 'The start date must be before or equal to the end date',
            'date_to.date' => 'The end date must be before or equal to the start date',
        ];
    }
}
