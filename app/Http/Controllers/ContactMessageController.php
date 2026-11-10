<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;

class ContactMessageController extends Controller
{
    public const CONFIRMATION = 'Thank you, your message has been sent. Our team reads every message and will reply to the email address you gave us.';

    public const MESSAGES_PER_MINUTE = 3;

    public const MESSAGES_PER_DAY = 10;

    public function create(): View
    {
        return view('pages.contact');
    }

    /**
     * Store a visitor message for the administrators. Only submissions that pass validation count
     * towards the per-visitor quota. Honeypot hits and identical re-submissions receive the same
     * confirmation as a real message but are not stored, so automated senders learn nothing and
     * double clicks never create duplicates.
     */
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $quotas = [
            'in the last minute' => ['contact-minute|'.$request->ip(), self::MESSAGES_PER_MINUTE, 60],
            'today' => ['contact-day|'.$request->ip(), self::MESSAGES_PER_DAY, 86400],
        ];

        foreach ($quotas as $period => [$key, $maxMessages]) {
            if (RateLimiter::tooManyAttempts($key, $maxMessages)) {
                return to_route('contact')
                    ->withInput($request->except(StoreContactMessageRequest::HONEYPOT_FIELD))
                    ->withErrors(['message' => "You have sent several messages {$period}. Please wait a little before sending another one."]);
            }
        }

        foreach ($quotas as [$key, , $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }

        $message = new ContactMessage($request->validated());

        if (! $request->isLikelySpam() && ! $message->isDuplicateOfRecentSubmission()) {
            $message->save();
        }

        return to_route('contact')->with('contact_message_sent', true);
    }
}
