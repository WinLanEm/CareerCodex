<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'password' => 'required|string|min:6|confirmed',
            'name' => 'required|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name is too long.',
        ];
    }
}
