<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\RequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Conversation for a freshly booked job: an accepted offer selected by its (booked) request.
 *
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_id' => fn (): int => $this->createAcceptedOffer()->id,
            'request_id' => fn (array $attributes): int => Offer::query()->findOrFail($attributes['offer_id'])->request_id,
            'homeowner_id' => fn (array $attributes): int => ServiceRequest::query()->findOrFail($attributes['request_id'])->homeowner_id,
            'handyman_id' => fn (array $attributes): int => Offer::query()->findOrFail($attributes['offer_id'])->handyman_id,
            'last_activity_at' => now(),
        ];
    }

    private function createAcceptedOffer(): Offer
    {
        $offer = Offer::factory()->accepted()->create();

        ServiceRequest::query()->whereKey($offer->request_id)->update([
            'status' => RequestStatus::Booked,
            'accepted_offer_id' => $offer->id,
        ]);

        return $offer;
    }
}
