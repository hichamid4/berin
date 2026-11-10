<?php

namespace App\Models;

use Database\Factories\HomeownerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'address', 'city'])]
class HomeownerProfile extends Model
{
    /** @use HasFactory<HomeownerProfileFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'homeowner_id', 'user_id');
    }

    public function favoriteHandymen(): BelongsToMany
    {
        return $this->belongsToMany(HandymanProfile::class, 'handyman_favorites', 'homeowner_id', 'handyman_id')
            ->withPivot('created_at');
    }
}
