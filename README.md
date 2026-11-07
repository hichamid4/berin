# BreIn

BreIn is a responsive home-services marketplace for homeowners and handymen. This repository is the Laravel/PHP website implementation; its database and business logic are being built in staged modules so the same rules can later serve a mobile API.

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
5. Install frontend dependencies and build assets: `npm install` and `npm run build`.
6. Configure a real SMTP or mail provider in `.env` before testing email verification or password reset delivery. The default local mailer writes messages to the application log.
7. Start the site with `php artisan serve`.

No default user accounts or sample data are seeded. Create the first platform administrator from a trusted terminal with `php artisan app:create-administrator`.

## Authentication

- Homeowners: `/login` and `/register/homeowner`
- Handymen: `/handyman/login` and `/register/handyman`
- Administrator: `/admin/login`

Role and active-account checks run on protected server routes. Administrator accounts cannot be self-registered from the website.

## Checks

- PHP tests: `php artisan test`
- PHP formatting: `vendor/bin/pint --dirty`
- Frontend production build: `npm run build`

See [FUNCTIONAL_SPECIFICATION.md](FUNCTIONAL_SPECIFICATION.md) for the approved product requirements and traceability.
