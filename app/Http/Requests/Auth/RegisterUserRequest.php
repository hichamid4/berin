<?php

namespace App\Http\Requests\Auth;

use App\Rules\PhoneNumber;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public const PASSWORD_MIN_LENGTH = 8;

    public function authorize(): bool
    {
        return in_array($this->route('role'), [UserRole::Homeowner->value, UserRole::Handyman->value], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone_e164' => PhoneNumber::normalize($this->input('phone_e164'))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique('users', 'email')],
            'phone_e164' => ['required', 'string', 'max:16', new PhoneNumber],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'confirmed', 'min:'.self::PASSWORD_MIN_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_e164.required' => 'A phone number is required so that the other party can reach you on WhatsApp after a booking.',
            'password.min' => 'The password must be at least '.self::PASSWORD_MIN_LENGTH.' characters.',
        ];
    }
}
