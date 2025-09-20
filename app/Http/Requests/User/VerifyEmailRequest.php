<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
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
            'email' => 'required|string|email|exists:users,email',
            'code' => 'required|int'
        ];
    }
    public function messages(): array
    {
        return [
            'email.required' => 'email is required',
            'email.email' => 'email is invalid',
            'email.exists' => 'email is invalid',
            'code.required' => 'code is required',
            'code.int' => 'code is invalid'
        ];
    }
}
