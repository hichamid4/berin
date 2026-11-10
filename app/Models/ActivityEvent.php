<?php

namespace App\Models;

use Database\Factories\ActivityEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_user_id', 'action', 'subject_type', 'subject_id', 'metadata', 'created_at'])]
class ActivityEvent extends Model
{
    /** @use HasFactory<ActivityEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    /**
     * Persist an auditable platform event for the administrator activity monitor.
     *
     * @param  array<string, scalar|null>|null  $metadata
     */
    public static function record(?User $actor, string $action, string $subjectType, ?int $subjectId = null, ?array $metadata = null): self
    {
        return self::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
