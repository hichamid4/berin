<?php

namespace App\Http\Requests\Handyman;

use App\Actions\Marketplace\PhotoStorage;
use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;

class OfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $this->route('offer') instanceof Offer
            ? (bool) $user?->can('update', $this->route('offer'))
            : (bool) $user?->can('makeOffer', $this->route('serviceRequest'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:'.Offer::MAX_PHOTOS],
            'photos.*' => PhotoStorage::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date cannot be before the start date.',
            'price.gt' => 'The price must be greater than 0 MAD.',
        ];
    }
}
