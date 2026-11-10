<?php

namespace App\Models;

use App\OfferStatus;
use App\RequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['homeowner_id', 'service_id', 'title', 'description', 'service_location', 'service_city', 'desired_start_date', 'status'])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    public const MAX_PHOTOS = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'desired_start_date' => 'date',
            'handyman_completed_at' => 'datetime',
            'homeowner_confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(HomeownerProfile::class, 'homeowner_id', 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'request_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RequestPhoto::class, 'request_id')->orderBy('sort_order');
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->homeowner_id === $user->id;
    }

    public function isOpen(): bool
    {
        return $this->status === RequestStatus::Open;
    }

    /**
     * Whether the handyman holds at least one skill required by this request's service.
     */
    public function matchesHandyman(HandymanProfile $handyman): bool
    {
        return $this->service->skills()
            ->whereIn('skills.id', $handyman->skills()->select('skills.id'))
            ->exists();
    }

    /**
     * Whether the handyman currently has a pending or accepted offer on this request.
     */
    public function hasActiveOfferFrom(HandymanProfile $handyman): bool
    {
        return $this->offers()
            ->where('handyman_id', $handyman->user_id)
            ->whereIn('status', [OfferStatus::Pending, OfferStatus::Accepted])
            ->exists();
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', RequestStatus::Open);
    }

    /**
     * Restrict requests to those whose service needs at least one of the handyman's skills.
     */
    #[Scope]
    protected function matchingHandyman(Builder $query, HandymanProfile $handyman): Builder
    {
        return $query->whereHas('service.skills', fn (Builder $skills) => $skills->whereIn(
            'skills.id',
            $handyman->skills()->select('skills.id'),
        ));
    }
}
