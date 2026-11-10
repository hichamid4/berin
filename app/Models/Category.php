<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * Bootstrap icon names used to present categories, keyed by category name.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        'Plumbing' => 'bi-droplet-half',
        'Electrical' => 'bi-lightning-charge',
        'Painting & Decoration' => 'bi-palette',
        'Carpentry & Furniture' => 'bi-hammer',
        'Air Conditioning & Heating' => 'bi-snow',
        'Cleaning & Housekeeping' => 'bi-stars',
        'Gardening & Outdoor' => 'bi-tree',
        'Masonry & Renovation' => 'bi-bricks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function icon(): string
    {
        return self::ICONS[$this->name] ?? 'bi-tools';
    }
}
