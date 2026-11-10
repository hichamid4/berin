<?php

namespace App\Actions\Accounts;

use App\Models\ActivityEvent;
use App\Models\TwoFactorCredential;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Authenticator-app (TOTP, RFC 6238) second factor for administrators.
 */
class AdministratorTwoFactor
{
    /**
     * Accepted clock drift, in 30-second steps, on either side of the current time.
     */
    private const WINDOW = 1;

    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private Google2FA $google2fa) {}

    /**
     * Start (or resume) enrolment: an unconfirmed secret is kept until a valid code confirms it,
     * so reloading the setup page does not invalidate an already scanned QR code.
     */
    public function beginEnrolment(User $administrator): TwoFactorCredential
    {
        $credential = $administrator->twoFactorCredential()->first();

        if ($credential?->isConfirmed()) {
            throw new LogicException('This administrator already has a confirmed second factor.');
        }

        if ($credential === null) {
            $credential = new TwoFactorCredential;
            $credential->user_id = $administrator->id;
            $credential->secret = $this->google2fa->generateSecretKey(32);
            $credential->recovery_codes = [];
            $credential->save();
        }

        return $credential;
    }

    /**
     * Confirm enrolment with a code from the app. Returns the plain recovery codes (shown once),
     * or null when the code is wrong.
     *
     * @return list<string>|null
     */
    public function confirmEnrolment(User $administrator, string $code): ?array
    {
        return DB::transaction(function () use ($administrator, $code): ?array {
            $credential = TwoFactorCredential::query()->whereKey($administrator->id)->lockForUpdate()->first();

            if ($credential === null || $credential->isConfirmed() || ! $this->consumeCode($credential, $code)) {
                return null;
            }

            $plainCodes = $this->newRecoveryCodes();
            $credential->recovery_codes = $this->hashCodes($plainCodes);
            $credential->confirmed_at = now();
            $credential->save();

            ActivityEvent::record($administrator, 'admin.two_factor_enabled', 'user', $administrator->id);

            return $plainCodes;
        });
    }

    /**
     * Verify a code (or a one-time recovery code) for a confirmed credential.
     *
     * @return 'totp'|'recovery_code'|null the method that succeeded
     */
    public function verify(User $administrator, string $code): ?string
    {
        return DB::transaction(function () use ($administrator, $code): ?string {
            $credential = TwoFactorCredential::query()->whereKey($administrator->id)->lockForUpdate()->first();

            if ($credential === null || ! $credential->isConfirmed()) {
                return null;
            }

            if ($this->looksLikeAuthenticatorCode($code)) {
                return $this->consumeCode($credential, $code) ? 'totp' : null;
            }

            return $this->consumeRecoveryCode($credential, $code) ? 'recovery_code' : null;
        });
    }

    /**
     * Replace all recovery codes; returns the new plain codes (shown once).
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $administrator): array
    {
        return DB::transaction(function () use ($administrator): array {
            $credential = TwoFactorCredential::query()->whereKey($administrator->id)->lockForUpdate()->firstOrFail();
            $plainCodes = $this->newRecoveryCodes();
            $credential->recovery_codes = $this->hashCodes($plainCodes);
            $credential->save();

            ActivityEvent::record($administrator, 'admin.two_factor_recovery_codes_regenerated', 'user', $administrator->id);

            return $plainCodes;
        });
    }

    /**
     * Remove the second factor so the administrator enrols again at the next sign-in.
     * Only reachable from the trusted command line (recovery procedure).
     */
    public function reset(User $administrator): bool
    {
        $deleted = TwoFactorCredential::query()->whereKey($administrator->id)->delete() > 0;

        if ($deleted) {
            ActivityEvent::record(null, 'admin.two_factor_reset', 'user', $administrator->id, ['via' => 'console']);
        }

        return $deleted;
    }

    public function qrCodeSvg(User $administrator, TwoFactorCredential $credential): string
    {
        $uri = $this->google2fa->getQRCodeUrl((string) config('admin.two_factor.issuer'), (string) $administrator->email, $credential->secret);

        return (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($uri);
    }

    /**
     * The secret grouped in blocks of four for manual entry in an authenticator app.
     */
    public function formattedSecret(TwoFactorCredential $credential): string
    {
        return trim(chunk_split($credential->secret, 4, ' '));
    }

    private function looksLikeAuthenticatorCode(string $code): bool
    {
        return preg_match('/\A\d{6}\z/', $this->normalizeDigits($code)) === 1;
    }

    private function normalizeDigits(string $code): string
    {
        return preg_replace('/\s+/', '', $code) ?? '';
    }

    /**
     * Accept a valid code only if it belongs to a newer time step than the last accepted one,
     * so an intercepted code cannot be replayed.
     */
    private function consumeCode(TwoFactorCredential $credential, string $code): bool
    {
        $code = $this->normalizeDigits($code);

        if (preg_match('/\A\d{6}\z/', $code) !== 1) {
            return false;
        }

        // Passing an integer (0 for a first use) makes the library return the matching time step.
        $timestep = $this->google2fa->verifyKeyNewer($credential->secret, $code, $credential->last_used_timestep ?? 0, self::WINDOW);

        if (! is_int($timestep)) {
            return false;
        }

        $credential->last_used_timestep = $timestep;
        $credential->save();

        return true;
    }

    /**
     * Stored form of a recovery code: case, spaces and dashes do not matter when it is typed.
     */
    public static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? ''));
    }

    private function consumeRecoveryCode(TwoFactorCredential $credential, string $code): bool
    {
        $hash = self::hashRecoveryCode($code);
        $remaining = $credential->recovery_codes ?? [];

        foreach ($remaining as $index => $storedHash) {
            if (hash_equals($storedHash, $hash)) {
                unset($remaining[$index]);
                $credential->recovery_codes = array_values($remaining);
                $credential->save();

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function newRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < (int) config('admin.two_factor.recovery_codes', 8); $i++) {
            $characters = '';
            for ($j = 0; $j < 10; $j++) {
                $characters .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
            }
            $codes[] = substr($characters, 0, 5).'-'.substr($characters, 5);
        }

        return $codes;
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string>
     */
    private function hashCodes(array $plainCodes): array
    {
        return array_map(self::hashRecoveryCode(...), $plainCodes);
    }
}
