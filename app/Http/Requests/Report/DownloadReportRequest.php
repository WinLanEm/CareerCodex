<?php

namespace App\Http\Requests\Report;

use App\Enums\ReportTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class DownloadReportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::enum(ReportTypeEnum::class)
            ],
            'start_date' => 'date|date_format:Y-m-d|nullable',
            'end_date' => 'date|date_format:Y-m-d|nullable|after:start_date',
        ];
    }
    public function messages()
    {
        return [
            'type.required' => 'type is required.',
            'type.string' => 'type must be string.',
            sprintf('type.%s', Enum::class) => '":input" is not valid type. Available: '.implode(', ', ReportTypeEnum::getValues()),'',
            'start_date.date' => 'Start date must be a date.',
            'end_date.date' => 'End date must be a date.',
            'end_date.after' => 'End date must be after start date.',
            'start_date.format' => 'Start date must be a date format. YYYY-MM-DD format.',
            'end_date.format' => 'End date must be a date format. YYYY-MM-DD format.',
        ];
    }
}
