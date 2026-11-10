<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Password reset shares the minimum length defined by registration.
 */
class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'confirmed', 'min:'.RegisterUserRequest::PASSWORD_MIN_LENGTH],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
