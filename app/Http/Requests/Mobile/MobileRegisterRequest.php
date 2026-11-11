<?php

namespace App\Http\Requests\Mobile;

use App\Http\Requests\Auth\RegisterUserRequest;
use App\UserRole;
use Illuminate\Validation\Rule;

/**
 * Same rules as the website sign-up form; the role comes from the payload instead of the URL.
 */
class MobileRegisterRequest extends RegisterUserRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in([UserRole::Homeowner->value, UserRole::Handyman->value])],
            ...parent::rules(),
        ];
    }
}
