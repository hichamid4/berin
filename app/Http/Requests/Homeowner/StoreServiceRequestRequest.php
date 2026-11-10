<?php

namespace App\Http\Requests\Homeowner;

use App\Actions\Marketplace\PhotoStorage;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', ServiceRequest::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required', 'integer',
                Rule::exists('services', 'id')->where('is_active', true)
                    ->whereIn('category_id', fn ($query) => $query->select('id')->from('categories')->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'min:8', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'service_location' => ['required', 'string', 'max:500'],
            'service_city' => ['required', 'string', 'max:120'],
            'desired_start_date' => ['required', 'date', 'after_or_equal:today'],
            'photos' => ['nullable', 'array', 'max:'.ServiceRequest::MAX_PHOTOS],
            'photos.*' => PhotoStorage::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.exists' => 'Choose a service that is currently available.',
            'photos.*.max' => 'Each photo must be 5 MB or smaller.',
            'photos.*.mimes' => 'Photos must be JPG, PNG or WebP images.',
        ];
    }
}
