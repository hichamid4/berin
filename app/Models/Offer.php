<?php

namespace App\Models;

use App\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['request_id', 'handyman_id', 'price', 'currency', 'start_date', 'end_date', 'description', 'status', 'accepted_at'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

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

    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 0, ',', ' ').' '.$this->currency;
    }
}
