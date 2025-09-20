<?php

namespace App\Http\Requests\Services;

use App\Enums\AuthServiceEnum;
use App\Enums\ServiceConnectionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ValidateOAuthProviderRequest extends FormRequest
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
            'provider' => [
                'required',
                'string',
                Rule::enum(AuthServiceEnum::class),
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'provider.required' => 'Provider is required.',
            'provider.string' => 'Provider must be string.',
            sprintf('provider.%s', Enum::class) => '":input" is not valid provider. Available: '.implode(', ', AuthServiceEnum::getValues()),'',
        ];
    }
    public function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->route('provider'),
        ]);
    }
}
