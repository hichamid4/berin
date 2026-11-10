<?php

namespace App\Providers;

use App\ContactMessageStatus;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        $this->limitContactMessages();
        $this->shareUnreadContactMessageCount();
        $this->brandAuthenticationEmails();
    }

    /**
     * Flood guard for the Contact Us endpoint: caps every POST, valid or not. The stricter quota on
     * accepted messages is applied by ContactMessageController after validation, so a visitor who
     * corrects a few typos is never told they have already sent messages.
     */
    private function limitContactMessages(): void
    {
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinute(10)
            ->by('contact-requests|'.$request->ip())
            ->response(fn (Request $request, array $headers) => to_route('contact')
                ->withInput($request->except(StoreContactMessageRequest::HONEYPOT_FIELD))
                ->withErrors(['message' => 'Too many attempts from your connection. Please wait a minute before trying again.'])
                ->withHeaders($headers)));
    }

    /**
     * Give the administrator sidebar the number of contact messages nobody has opened yet.
     */
    private function shareUnreadContactMessageCount(): void
    {
        View::composer('layouts.admin', function (BladeView $view): void {
            $view->with('newContactMessageCount', ContactMessage::query()->where('status', ContactMessageStatus::New)->count());
        });
    }

    /**
     * Render Laravel's verification and password-reset notifications with BreIn templates,
     * keeping the framework's signed URLs and token handling unchanged.
     */
    private function brandAuthenticationEmails(): void
    {
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject('Confirm your email address · BreIn')
            ->view(['emails.action', 'emails.action-text'], [
                'subject' => 'Confirm your email address',
                'preheader' => 'One click to activate your BreIn account.',
                'eyebrow' => 'Account activation',
                'heading' => 'Confirm your email address',
                'name' => $notifiable->first_name,
                'paragraphs' => [
                    'Welcome to BreIn! Please confirm that this address belongs to you to activate your '.$notifiable->role->value.' account.',
                    $notifiable->isHandyman()
                        ? 'Once confirmed, you can choose your skills and start receiving requests that match them.'
                        : 'Once confirmed, you can post requests and receive offers from qualified handymen.',
                ],
                'button' => 'Verify my email address',
                'url' => $url,
                'expiry' => 'This link expires in '.config('auth.verification.expire', 60).' minutes. You can request a new one from the verification page at any time.',
                'ignore' => 'If you did not create a BreIn account, you can safely ignore this email; no account will be activated.',
            ]));

        ResetPassword::toMailUsing(fn (object $notifiable, string $token): MailMessage => (new MailMessage)
            ->subject('Reset your password · BreIn')
            ->view(['emails.action', 'emails.action-text'], [
                'subject' => 'Reset your password',
                'preheader' => 'Choose a new password for your BreIn account.',
                'eyebrow' => 'Password recovery',
                'heading' => 'Reset your password',
                'name' => $notifiable->first_name,
                'paragraphs' => ['We received a request to reset the password of your BreIn account. Use the button below to choose a new one.'],
                'button' => 'Choose a new password',
                'url' => url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)),
                'expiry' => 'This link expires in '.config('auth.passwords.users.expire', 60).' minutes.',
                'ignore' => 'If you did not ask for a password reset, ignore this email; your password stays unchanged.',
            ]));
    }
}
