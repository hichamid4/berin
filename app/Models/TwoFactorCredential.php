<?php

namespace App\Models;

use Database\Factories\TwoFactorCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An administrator's authenticator-app secret. Attributes are set explicitly by
 * App\Actions\Accounts\AdministratorTwoFactor, never by mass assignment.
 */
class TwoFactorCredential extends Model
{
    /** @use HasFactory<TwoFactorCredentialFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $hidden = ['secret', 'recovery_codes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'confirmed_at' => 'datetime',
            'last_used_timestep' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function remainingRecoveryCodes(): int
    {
        return count($this->recovery_codes ?? []);
    }
}
