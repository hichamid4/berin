<?php

namespace App\Models;

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

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id');
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
