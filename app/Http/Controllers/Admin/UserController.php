<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\AccountLifecycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityEvent;
use App\Models\Offer;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = UserRole::tryFrom($request->string('role')->toString());
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $users = User::query()
            ->withTrashed()
            ->with(['homeownerProfile', 'handymanProfile'])
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when(in_array($status, ['active', 'blocked', 'anonymized'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'role', 'status', 'search'));
    }

    public function show(User $user, AccountLifecycle $lifecycle): View
    {
        $user->load(['homeownerProfile', 'handymanProfile.skills', 'blockedBy']);

        $requests = $user->isHomeowner()
            ? ServiceRequest::query()->where('homeowner_id', $user->id)->with('service')->withCount('offers')->latest()->get()
            : collect();

        $offers = $user->isHandyman()
            ? Offer::query()->where('handyman_id', $user->id)->with(['request.service', 'review'])->latest()->get()
            : collect();

        $reviewStats = $user->isHandyman()
            ? Review::query()->whereIn('offer_id', $offers->pluck('id'))->selectRaw('count(*) as total, avg(rating) as average')->first()
            : null;

        $activity = ActivityEvent::query()
            ->where(fn (Builder $query) => $query->where('actor_user_id', $user->id)
                ->orWhere(fn (Builder $inner) => $inner->where('subject_type', 'user')->where('subject_id', $user->id)))
            ->with('actor')
            ->latest('created_at')
            ->latest('id')
            ->limit(15)
            ->get();

        return view('admin.users.show', [
            'member' => $user,
            'requests' => $requests,
            'offers' => $offers,
            'reviewStats' => $reviewStats,
            'activity' => $activity,
            'closureBlocker' => $user->status === 'anonymized' ? null : $lifecycle->closureBlocker($user),
        ]);
    }

    public function edit(User $user): View
    {
        abort_if($user->status === 'anonymized', 404);

        return view('admin.users.edit', ['member' => $user->load(['homeownerProfile', 'handymanProfile'])]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->status === 'anonymized', 404);
        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $request): void {
            $user->forceFill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'phone_e164' => $data['phone_e164'] ?? null,
            ])->save();

            ($user->homeownerProfile ?? $user->handymanProfile)?->update([
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            ActivityEvent::record($request->user(), 'user.profile_updated', 'user', $user->id);
        });

        return to_route('admin.users.show', $user)->with('success', "{$user->fullName()}'s profile was updated.");
    }

    public function block(Request $request, User $user, AccountLifecycle $lifecycle): RedirectResponse
    {
        $lifecycle->block($user, $request->user());

        return back()->with('success', "{$user->fullName()} was blocked and signed out. They can no longer use protected marketplace actions.");
    }

    public function unblock(Request $request, User $user, AccountLifecycle $lifecycle): RedirectResponse
    {
        $lifecycle->unblock($user, $request->user());

        return back()->with('success', "{$user->fullName()} was unblocked.");
    }

    public function anonymize(Request $request, User $user, AccountLifecycle $lifecycle): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:ANONYMIZE']], ['confirmation.in' => 'Type ANONYMIZE to confirm.']);
        $lifecycle->anonymize($user, $request->user());

        return to_route('admin.users.show', $user)->with('success', 'The account was anonymized. Its history is kept without personal data.');
    }
}
