<?php

namespace App\Http\Resources\Mobile;

use App\Models\Service;
use Illuminate\Http\Request;

/**
 * A service of the catalog. Price figures come from live and accepted offers when loaded.
 *
 * @mixin Service
 */
class CatalogServiceResource extends MobileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->relationLoaded('category') ? $this->category?->name : null,
            'skills' => $this->relationLoaded('skills') ? $this->skills->pluck('name')->values() : [],
            'requests_count' => $this->whenCounted('requests'),
            'price_from' => $this->offer_price_min !== null ? (float) $this->offer_price_min : null,
            'price_to' => $this->offer_price_max !== null ? (float) $this->offer_price_max : null,
            'currency' => 'MAD',
        ];
    }
}
