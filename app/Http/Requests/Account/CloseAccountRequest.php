<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class CloseAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isAdministrator();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'close_password' => ['required', 'string', 'current_password'],
            'confirmation' => ['required', 'string', 'in:CLOSE'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['confirmation.in' => 'Type CLOSE in capital letters to confirm.'];
    }
}
