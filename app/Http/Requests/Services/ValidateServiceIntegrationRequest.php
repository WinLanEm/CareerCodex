<?php

namespace App\Http\Requests\Services;

use App\Enums\AuthServiceEnum;
use App\Enums\ServiceConnectionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ValidateServiceIntegrationRequest extends FormRequest
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
            'service' => [
                'required',
                'string',
                Rule::enum(ServiceConnectionsEnum::class),
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'service.required' => 'Service is required.',
            'service.string' => 'Service must be string.',
            sprintf('service.%s', Enum::class) => '":input" is not valid service. Available: '.implode(', ', ServiceConnectionsEnum::getValues()),'',
        ];
    }
    public function prepareForValidation(): void
    {
        $this->merge([
            'service' => $this->route('service'),
        ]);
    }
}
