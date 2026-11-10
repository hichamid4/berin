<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isHandyman();
    }

    /**
     * Only active skills can be newly selected; already-held inactive skills may be kept.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $heldSkillIds = $this->user()->handymanProfile?->skills()->pluck('skills.id')->all() ?? [];

        return [
            'skill_ids' => ['required', 'array', 'min:1'],
            'skill_ids.*' => [
                'integer', 'distinct',
                Rule::exists('skills', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $heldSkillIds ?: [0])),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['skill_ids.required' => 'Select at least one skill so that you can receive matching requests.'];
    }
}
