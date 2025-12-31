<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+( [a-zA-Z0-9\-_]+)*$/', // No leading/trailing spaces, single space between words, allowed chars: alphanumeric, -, _
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
                'regex:/^[a-z0-9._%+-]+@gmail\.com$/', // Lowercase, English chars, @gmail.com only
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:6',
                'max:255',
                'regex:/[A-Z]/',      // At least 1 uppercase
                'regex:/[^a-zA-Z0-9]/', // At least 1 special char
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
            'name.min' => 'Name must be at least 3 characters.',
            'name.regex' => 'Name must not start or end with spaces, allow only single spaces between words, and can only contain letters, numbers, dashes, and underscores.',
            'email.regex' => 'Email must be a valid lowercase @gmail.com address.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.max' => 'Password must not exceed 255 characters.',
            'password.regex' => 'Password must contain at least 1 uppercase letter and 1 special character.',
        ];
    }
}
