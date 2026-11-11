<?php

namespace App\Http\Resources\Mobile;

use App\Models\Review;
use Illuminate\Http\Request;

/**
 * A published review. The author is shown by first name and initial only, as on the website.
 *
 * @mixin Review
 */
class ReviewResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $serviceRequest = $this->relationLoaded('offer') && $this->offer->relationLoaded('request') ? $this->offer->request : null;
        $author = $serviceRequest?->relationLoaded('homeowner') ? $serviceRequest->homeowner?->user : null;

        return [
            'rating' => $this->rating,
            'comment' => $this->comment,
            'service' => $serviceRequest?->relationLoaded('service') ? $serviceRequest->service?->name : null,
            'author' => $author ? ($author->isActive() ? $author->shortName() : 'Former member') : null,
            'created_at' => self::timestamp($this->created_at),
            'updated_at' => self::timestamp($this->updated_at),
        ];
    }
}
