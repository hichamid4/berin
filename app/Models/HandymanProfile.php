<?php

namespace App\Models;

use Database\Factories\HandymanProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['user_id', 'address', 'city', 'photo_path'])]
class HandymanProfile extends Model
{
    /** @use HasFactory<HandymanProfileFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'handyman_skill', 'handyman_id', 'skill_id')
            ->withPivot('created_at');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'handyman_id', 'user_id');
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Offer::class, 'handyman_id', 'offer_id', 'user_id', 'id');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(HomeownerProfile::class, 'handyman_favorites', 'handyman_id', 'homeowner_id')
            ->withPivot('created_at');
    }
}
