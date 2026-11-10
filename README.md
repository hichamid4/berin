# BreIn

BreIn is a responsive home-services marketplace for homeowners and handymen. This repository is the Laravel/PHP website implementation; its database and business logic are built in staged modules so the same rules can later serve a mobile API.

## Requirements

- PHP 8.3 or newer with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, and `zip`
- Composer
- MySQL 8.4 or a compatible MySQL release
- Node.js and npm

## Local setup

1. Install PHP and Composer dependencies with `composer install`.
2. Copy `.env.example` to `.env`; set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for a dedicated MySQL application account.
3. Generate the application key: `php artisan key:generate`.
4. Run migrations: `php artisan migrate`.
5. Optionally load the reference catalog and fictional demonstration data (not seeded in production): `php artisan db:seed`. Demo accounts use `@example.test` addresses and the password `Demo-password-2026!`. The seeders are repeatable and never delete data.
6. Install frontend dependencies and build assets: `npm install` and `npm run build`.
7. Configure email delivery (see below).
8. Generate the private administration path: `php artisan app:admin-path --generate` (see *Administrator access*).
9. Start the site with `php artisan serve`.

Create the first platform administrator from a trusted terminal with `php artisan app:create-administrator`. The administrator sets up an authenticator app at their first sign-in.

## Email delivery (verification and password reset)

Registration sends a verification email through Laravel's `Registered` event and `MustVerifyEmail`. Password reset emails use the same templates. With `MAIL_MAILER=log` (the default) emails are only written to `storage/logs/laravel.log`.

To deliver real emails with Gmail:

1. Turn on 2-Step Verification for the Google account at https://myaccount.google.com/security.
2. Create an App Password at https://myaccount.google.com/apppasswords (name it "BreIn"). Google shows a 16-character code once.
3. In your local `.env` (never commit it), set:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your.address@gmail.com
   MAIL_PASSWORD=yourapppasswordwithoutspaces
   MAIL_FROM_ADDRESS=your.address@gmail.com
   MAIL_FROM_NAME="BreIn"
   ```

   Port 587 with `MAIL_SCHEME=smtp` upgrades to TLS with STARTTLS. For port 465 use `MAIL_SCHEME=smtps`. Gmail requires `MAIL_FROM_ADDRESS` to be the authenticated address.
4. Make sure `APP_URL` matches the address you open in the browser (for example `http://localhost:8000`).
5. Clear cached configuration: `php artisan config:clear`.
6. Send a diagnostic email: `php artisan app:mail-test your.address@gmail.com`. The command shows the mailer, host and port, and whether a username and password are set, without printing them.
7. Register a new account with a real address you can read, open the email and click **Verify my email address**.

Verification links are signed and expire after 60 minutes. They work even if opened in another browser; expired or altered links show a page offering a new link.

## Authentication

- Homeowners: `/login` and `/register/homeowner`
- Handymen: `/handyman/login` and `/register/handyman`
- Administrator: a private address that is not linked anywhere on the website (see *Administrator access* below)

Passwords must be at least 8 characters. Homeowners and handymen must give a phone number in international format (Moroccan numbers may be typed as `06…`); it is used for WhatsApp contact after an offer is accepted. Role and active-account checks run on protected server routes. Administrator accounts cannot be self-registered from the website.

## Administrator access

The administration area is not linked from the public website and is not served at `/admin`. It lives under a private path stored only in your local `.env` (`ADMIN_PATH`); never commit or publish its value. The path only hides the sign-in page. Access still requires the password, an authenticator-app code, the administrator role and an active, verified account.

**One-time setup on each installation:**

1. `php artisan app:admin-path --generate` writes a random `ADMIN_PATH` to `.env`. Then run `php artisan config:clear` (and `php artisan route:clear` if routes are cached) and restart the server.
2. `php artisan app:admin-path --show` prints the private sign-in URL. Bookmark it; don't share it.
3. Sign in there with your administrator email and password. On the first sign-in you are asked to scan a QR code with an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, Aegis…) and confirm a 6-digit code.
4. Save the 8 recovery codes shown once afterwards (password manager or printed copy).

From then on, every administrator sign-in asks for the password and then the current code from the app. Administrators are never kept signed in with a "remember me" cookie. Password and code attempts are rate-limited, and failures are logged in the activity monitor.

**Recovery (avoid lock-out):**

- **Lost phone:** sign in with one recovery code (each works once), then open **Admin → Security** and generate new codes.
- **Lost phone and recovery codes:** on the server, run `php artisan app:admin-two-factor reset you@example.com` (it asks for confirmation). Then sign in with your password and enrol a new phone.
- **Who still needs to enrol:** `php artisan app:admin-two-factor status`.
- **Forgot the private address:** `php artisan app:admin-path --show`.
- **Change the address** (for example if it was shared): `php artisan app:admin-path --generate --force`.

Automated tests use their own fake path from `phpunit.xml`, never your real one. See Section 11.7 of the functional specification for the full design and its limitations.

## Service catalog price filter

The **Services** page can be filtered by offer price in MAD. Services have no fixed price, so the filter uses the prices handymen have actually offered. It keeps a service when at least one **pending or accepted** offer falls in the range. Withdrawn, declined and superseded offers are ignored, the same rule as the price range on each service page. Each service card shows its current offer range.

## Contact Us messages

The public `/contact` form stores messages in the `contact_messages` table; nothing is emailed. Administrators handle them under **Admin → Contact messages**. From there they can search, filter by status, open a message, mark it new, read or resolved, and reply from their own email app. Accepted submissions are limited to 3 per minute and 10 per day per IP address; failed validation attempts don't count, and a separate 10-requests-per-minute throttle stops floods. A hidden honeypot field and a 10-minute duplicate check stop spam and double submissions. Messages are never deleted automatically; the retention period is still an open decision (see Section 11.6 of the functional specification).

**Placeholder:** the email shown on the Contact page (`contact@brein.example`) is not a real mailbox. Replace it in `resources/views/pages/contact.blade.php` when the real support address is known.

## Checks

- PHP formatting: `vendor/bin/pint --dirty`
- Frontend production build: `npm run build`
- PHP tests: tests that reset the database only run against a database whose name ends in `_testing`; otherwise they are skipped. Tests using `DatabaseTransactions` roll back their own changes and can run against the development database, for example `DB_DATABASE=brein vendor/bin/phpunit`.

See [FUNCTIONAL_SPECIFICATION.md](FUNCTIONAL_SPECIFICATION.md) for the approved product requirements and traceability.
