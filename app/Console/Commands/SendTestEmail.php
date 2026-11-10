<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

#[Signature('app:mail-test {to : Recipient address that should receive the test email}')]
#[Description('Send a branded test email to check the configured mailer. Never prints credentials.')]
class SendTestEmail extends Command
{
    public function handle(): int
    {
        $recipient = (string) $this->argument('to');

        if (Validator::make(['to' => $recipient], ['to' => ['required', 'email:rfc']])->fails()) {
            $this->error('Provide a valid recipient email address.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $this->table(['Setting', 'Value'], [
            ['Mailer', $mailer],
            ['Host', (string) config("mail.mailers.{$mailer}.host", '—')],
            ['Port', (string) config("mail.mailers.{$mailer}.port", '—')],
            ['Scheme', (string) (config("mail.mailers.{$mailer}.scheme") ?? 'auto')],
            ['Username set', filled(config("mail.mailers.{$mailer}.username")) ? 'yes' : 'no'],
            ['Password set', filled(config("mail.mailers.{$mailer}.password")) ? 'yes' : 'no'],
            ['From', config('mail.from.name').' <'.config('mail.from.address').'>'],
        ]);

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("The \"{$mailer}\" mailer does not deliver to an inbox. Set MAIL_MAILER=smtp in .env, then run: php artisan config:clear");
        }

        try {
            Mail::send(['emails.action', 'emails.action-text'], [
                'subject' => 'BreIn mail test',
                'preheader' => 'Your BreIn mail configuration works.',
                'eyebrow' => 'Configuration check',
                'heading' => 'Email delivery works',
                'name' => 'there',
                'paragraphs' => ['This message was sent by the app:mail-test command. If you can read it in your inbox, verification and password reset emails will be delivered too.'],
                'button' => 'Open BreIn',
                'url' => url('/'),
                'expiry' => 'Sent at '.now()->toDateTimeString().' ('.config('app.timezone').').',
                'ignore' => 'No action is required.',
            ], fn (Message $message) => $message->to($recipient)->subject('BreIn mail test'));
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());
            $this->line('Check MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME and the app password, then run php artisan config:clear.');

            return self::FAILURE;
        }

        $this->info("Test email handed to the \"{$mailer}\" mailer for {$recipient}. Check the inbox and the spam folder.");

        return self::SUCCESS;
    }
}
