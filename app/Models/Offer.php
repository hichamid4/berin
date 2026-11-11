<?php

namespace App\Models;

use App\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['request_id', 'handyman_id', 'price', 'currency', 'start_date', 'end_date', 'description', 'status', 'accepted_at'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    public const MAX_PHOTOS = 5;

    /**
     * Offers whose price reflects the current market for a service: live (pending) and agreed
     * (accepted) offers. Withdrawn, declined and superseded offers are excluded.
     *
     * @var list<OfferStatus>
     */
    public const PRICE_REFERENCE_STATUSES = [OfferStatus::Pending, OfferStatus::Accepted];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'accepted_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function handyman(): BelongsTo
    {
        return $this->belongsTo(HandymanProfile::class, 'handyman_id', 'user_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'offer_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OfferPhoto::class)->orderBy('sort_order');
    }

    public function isPending(): bool
    {
        return $this->status === OfferStatus::Pending;
    }

    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 0, ',', ' ').' '.$this->currency;
    }

    /**
     * Restrict to offers whose price is used for public price ranges and the catalog price filter.
     */
    #[Scope]
    protected function priceReference(Builder $query): Builder
    {
        return $query->whereIn('offers.status', self::PRICE_REFERENCE_STATUSES);
    }
}
