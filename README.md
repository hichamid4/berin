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
8. Start the site with `php artisan serve`.

Create the first platform administrator from a trusted terminal with `php artisan app:create-administrator`.

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
- Administrator: `/admin/login`

Passwords must be at least 8 characters. Homeowners and handymen must give a phone number in international format (Moroccan numbers may be typed as `06…`); it is used for WhatsApp contact after an offer is accepted. Role and active-account checks run on protected server routes. Administrator accounts cannot be self-registered from the website.

## Checks

- PHP formatting: `vendor/bin/pint --dirty`
- Frontend production build: `npm run build`
- PHP tests: tests that reset the database only run against a database whose name ends in `_testing`; otherwise they are skipped. Tests using `DatabaseTransactions` roll back their own changes and can run against the development database, for example `DB_DATABASE=brein vendor/bin/phpunit`.

See [FUNCTIONAL_SPECIFICATION.md](FUNCTIONAL_SPECIFICATION.md) for the approved product requirements and traceability.
