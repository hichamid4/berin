<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdministrator();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('skills', 'name')->ignore($this->route('skill'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')],
        ];
    }
}
