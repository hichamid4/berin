<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'min:'.RegisterUserRequest::PASSWORD_MIN_LENGTH, 'different:current_password'],
        ];
    }
}
