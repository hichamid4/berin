<?php

namespace App\Actions\Accounts;

use Illuminate\Support\Str;

/**
 * Resolves the private URL prefix of the administration area.
 *
 * The prefix is a secret stored in the local environment (ADMIN_PATH). It is an additional
 * layer only: access is always decided by authentication, role, account state and the
 * second factor, never by knowing the URL.
 */
class AdministratorPath
{
    /**
     * Minimum length of a configured path; shorter values are treated as missing.
     */
    public const MIN_LENGTH = 24;

    /**
     * Length of a generated path (random characters after the prefix).
     */
    public const GENERATED_RANDOM_LENGTH = 32;

    public static function prefix(): string
    {
        $configured = self::configured();

        return $configured ?? self::derivedFromApplicationKey();
    }

    /**
     * The explicitly configured path, or null when it is missing or too weak.
     */
    public static function configured(): ?string
    {
        $path = trim((string) config('admin.path'), " /\t\n\r");

        return self::isAcceptable($path) ? $path : null;
    }

    public static function isConfigured(): bool
    {
        return self::configured() !== null;
    }

    /**
     * A single URL segment of letters, digits, dashes and underscores, long enough to be unguessable.
     */
    public static function isAcceptable(string $path): bool
    {
        return strlen($path) >= self::MIN_LENGTH
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]+\z/', $path) === 1
            && ! in_array(strtolower($path), ['admin', 'administrator', 'administration'], true);
    }

    public static function generate(): string
    {
        return 'manage-'.Str::lower(Str::random(self::GENERATED_RANDOM_LENGTH));
    }

    /**
     * Fallback used only when ADMIN_PATH is not set: unique to this installation and as secret
     * as APP_KEY, so the administration area never moves to a predictable address.
     */
    private static function derivedFromApplicationKey(): string
    {
        return 'manage-'.substr(hash_hmac('sha256', 'brein-administration-path', (string) config('app.key')), 0, self::GENERATED_RANDOM_LENGTH);
    }
}
