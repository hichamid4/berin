<?php

namespace App\Http\Controllers\Admin;

use App\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactMessageStatusRequest;
use App\Models\ActivityEvent;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = ContactMessageStatus::tryFrom($request->string('status')->toString());
        $search = trim($request->string('q')->toString());

        $messages = ContactMessage::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statusCounts = ContactMessage::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.contact-messages.index', compact('messages', 'status', 'search', 'statusCounts'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        return view('admin.contact-messages.show', ['contactMessage' => $contactMessage->load('handledBy')]);
    }

    public function updateStatus(UpdateContactMessageStatusRequest $request, ContactMessage $contactMessage): RedirectResponse
    {
        $previous = $contactMessage->status;
        $status = $request->status();

        if ($previous === $status) {
            return back()->with('success', "This message is already marked as {$status->label()}.");
        }

        DB::transaction(function () use ($request, $contactMessage, $previous, $status): void {
            $contactMessage->transitionTo($status, $request->user());

            ActivityEvent::record($request->user(), 'contact_message.status_updated', 'contact_message', $contactMessage->id, [
                'from' => $previous->value,
                'to' => $status->value,
            ]);
        });

        return back()->with('success', "The message was marked as {$status->label()}.");
    }
}
