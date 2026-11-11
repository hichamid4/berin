<?php

namespace App\Http\Resources\Mobile;

use App\Models\OfferPhoto;
use App\Models\RequestPhoto;
use Illuminate\Http\Request;

/**
 * A private request or offer photo. The URL requires the device token (same policy as the website).
 *
 * @mixin RequestPhoto|OfferPhoto
 */
class PhotoResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->resource instanceof RequestPhoto
                ? route('api.mobile.photos.request', $this->id)
                : route('api.mobile.photos.offer', $this->id),
        ];
    }
}
