<?php

namespace App\Http\Requests\Handyman;

use App\Actions\Marketplace\PhotoStorage;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Offer;
use App\Models\ServiceRequest;
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
     * Explain refusals the handyman can understand instead of showing a bare 403 page.
     */
    protected function failedAuthorization(): void
    {
        $request = $this->route('serviceRequest');
        $profile = $this->user()?->handymanProfile;

        if ($request instanceof ServiceRequest && $profile !== null) {
            if ($request->hasActiveOfferFrom($profile)) {
                throw new BusinessRuleViolation('You already sent an offer for this request. You can edit or withdraw it below; a second offer was not created.');
            }

            if (! $request->isOpen()) {
                throw new BusinessRuleViolation('This request is no longer open for offers, so your offer was not sent.');
            }
        }

        if ($this->route('offer') instanceof Offer) {
            throw new BusinessRuleViolation('This offer can no longer be edited because it is not pending or the request is closed.');
        }

        parent::failedAuthorization();
    }

    /**
     * Send the handyman back to the offer form itself, where the field errors are shown.
     */
    protected function getRedirectUrl(): string
    {
        return strtok(parent::getRedirectUrl(), '#').'#offer';
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
