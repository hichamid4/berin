<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogServiceRequest extends FormRequest
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
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => [
                'required', 'string', 'max:160',
                Rule::unique('services', 'name')
                    ->where('category_id', $this->integer('category_id'))
                    ->ignore($this->route('service')),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'skill_ids' => ['required', 'array', 'min:1'],
            'skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'skill_ids.required' => 'Select at least one skill so that qualified handymen can see requests for this service.',
            'name.unique' => 'This category already has a service with that name.',
        ];
    }
}
