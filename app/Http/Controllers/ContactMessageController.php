<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    public const CONFIRMATION = 'Thank you, your message has been sent. Our team reads every message and will reply to the email address you gave us.';

    public function create(): View
    {
        return view('pages.contact');
    }

    /**
     * Store a visitor message for the administrators. Honeypot hits and identical re-submissions
     * receive the same confirmation as a real message but are not stored, so automated senders
     * learn nothing and double clicks never create duplicates.
     */
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $message = new ContactMessage($request->validated());

        if (! $request->isLikelySpam() && ! $message->isDuplicateOfRecentSubmission()) {
            $message->save();
        }

        return to_route('contact')->with('contact_message_sent', true);
    }
}
