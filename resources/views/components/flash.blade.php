@if (session('status'))
    <div {{ $attributes->merge(['class' => 'alert alert-success d-flex align-items-center gap-2']) }} role="status">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ match(session('status')) {
            'verification-link-sent' => 'A verification link has been sent to your email address.',
            'email-verified' => 'Your email address is verified.',
            'password-reset-link-sent' => 'If an eligible account matches that email, a password reset link has been sent.',
            'password-reset' => 'Your password has been reset. You can now sign in.',
            'email-verified-sign-in' => 'Your email address is verified. Sign in to continue.',
            default => 'Your request was completed.',
        } }}</span>
    </div>
@endif
@if (session('success'))
    <div {{ $attributes->merge(['class' => 'alert alert-success d-flex align-items-start gap-2']) }} role="status">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div {{ $attributes->merge(['class' => 'alert alert-danger d-flex align-items-start gap-2']) }} role="alert">
        <i class="bi bi-exclamation-octagon-fill mt-1"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
