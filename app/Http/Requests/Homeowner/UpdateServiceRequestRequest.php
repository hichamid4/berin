<?php

namespace App\Http\Requests\Homeowner;

use App\Actions\Marketplace\PhotoStorage;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('serviceRequest'));
    }

    /**
     * The service cannot change after creation because offers were made for that service.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:8', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'service_location' => ['required', 'string', 'max:500'],
            'service_city' => ['required', 'string', 'max:120'],
            'desired_start_date' => ['required', 'date', 'after_or_equal:today'],
            'photos' => ['nullable', 'array', 'max:'.ServiceRequest::MAX_PHOTOS],
            'photos.*' => PhotoStorage::rules(),
        ];
    }
}
