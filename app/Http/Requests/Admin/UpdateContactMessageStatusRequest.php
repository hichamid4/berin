<?php

namespace App\Http\Requests\Admin;

use App\ContactMessageStatus;
use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactMessageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Administrator;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ContactMessageStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Choose a status for this message.',
            'status.enum' => 'Choose a valid status: new, read or resolved.',
        ];
    }

    public function status(): ContactMessageStatus
    {
        return ContactMessageStatus::from($this->validated('status'));
    }
}
