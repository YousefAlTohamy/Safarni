<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'regex:/^[a-z0-9._%+-]+@gmail\.com$/',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:255',
                'regex:/[A-Z]/',
                'regex:/[^a-zA-Z0-9]/',
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.regex' => 'Email must be a valid lowercase @gmail.com address.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.max' => 'Password must not exceed 255 characters.',
            'password.regex' => 'Password must contain at least 1 uppercase letter and 1 special character.',
        ];
    }
}
