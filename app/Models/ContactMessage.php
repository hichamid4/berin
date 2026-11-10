<?php

namespace App\Models;

use App\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'email', 'subject', 'message'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    /**
     * An identical message re-sent within this many minutes is treated as a duplicate.
     */
    public const DUPLICATE_WINDOW_MINUTES = 10;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'read_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    /**
     * Messages whose name, email, subject or text contains the search term.
     *
     * @param  Builder<ContactMessage>  $query
     */
    public function scopeMatching(Builder $query, string $search): void
    {
        $pattern = '%'.addcslashes($search, '\\%_').'%';

        $query->where(fn (Builder $inner) => $inner
            ->where('name', 'like', $pattern)
            ->orWhere('email', 'like', $pattern)
            ->orWhere('subject', 'like', $pattern)
            ->orWhere('message', 'like', $pattern));
    }

    public function isDuplicateOfRecentSubmission(): bool
    {
        return self::query()
            ->where('email', $this->email)
            ->where('subject', $this->subject)
            ->where('message', $this->message)
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->exists();
    }

    /**
     * Move the message to a new status, keeping read/resolved timestamps consistent with it.
     */
    public function transitionTo(ContactMessageStatus $status, User $administrator): void
    {
        $this->status = $status;
        $this->read_at = $status === ContactMessageStatus::New ? null : ($this->read_at ?? now());
        $this->resolved_at = $status === ContactMessageStatus::Resolved ? ($this->resolved_at ?? now()) : null;
        $this->handled_by = $administrator->id;
        $this->save();
    }
}
