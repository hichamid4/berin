<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\AccountLifecycle;
use App\Http\Requests\Account\CloseAccountRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdateSkillsRequest;
use App\Models\ActivityEvent;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function edit(Request $request, AccountLifecycle $lifecycle): View
    {
        $user = $request->user()->load(['homeownerProfile', 'handymanProfile.skills']);

        if ($user->isAdministrator()) {
            return view('admin.account', compact('user'));
        }

        $skillCatalog = $user->isHandyman()
            ? Skill::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $user->handymanProfile->skills->pluck('id')))
                ->with(['services' => fn ($query) => $query->select('services.id', 'services.name', 'services.category_id')])
                ->orderBy('name')
                ->get()
            : collect();

        return view('account.edit', [
            'user' => $user,
            'profile' => $user->homeownerProfile ?? $user->handymanProfile,
            'skillCatalog' => $skillCatalog,
            'categoryNames' => Category::query()->pluck('name', 'id'),
            'closureBlocker' => $lifecycle->closureBlocker($user),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $email = mb_strtolower($data['email']);
        $emailChanged = $email !== $user->email;
        $oldPhoto = null;

        DB::transaction(function () use ($user, $data, $email, $emailChanged, $request, &$oldPhoto): void {
            $user->forceFill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $email,
                'phone_e164' => $data['phone_e164'] ?? null,
                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            ])->save();

            $profile = $user->homeownerProfile ?? $user->handymanProfile;
            $profile?->fill(['city' => $data['city'] ?? null, 'address' => $data['address'] ?? null]);

            if ($user->isHandyman() && $profile !== null) {
                if ($request->hasFile('photo')) {
                    $oldPhoto = $profile->photo_path;
                    $profile->photo_path = $request->file('photo')->storeAs('handymen', $user->id.'-'.Str::uuid().'.'.$request->file('photo')->extension(), 'local');
                } elseif ($request->boolean('remove_photo')) {
                    $oldPhoto = $profile->photo_path;
                    $profile->photo_path = null;
                }
            }

            $profile?->save();
            ActivityEvent::record($user, 'account.profile_updated', 'user', $user->id, ['email_changed' => $emailChanged]);
        });

        if ($oldPhoto !== null) {
            Storage::disk('local')->delete($oldPhoto);
        }

        if ($emailChanged && ! $user->isAdministrator()) {
            $user->sendEmailVerificationNotification();

            return to_route('verification.notice')->with('status', 'verification-link-sent');
        }

        return back()->with('success', 'Your profile was updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();
        Auth::logoutOtherDevices($request->validated('password'));
        ActivityEvent::record($user, 'account.password_changed', 'user', $user->id);

        return back()->with('success', 'Your password was changed. Other signed-in devices were logged out.');
    }

    public function updateSkills(UpdateSkillsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $changes = $user->handymanProfile->skills()->sync($request->validated('skill_ids'));

        ActivityEvent::record($user, 'handyman.skills_updated', 'user', $user->id, [
            'added' => count($changes['attached']),
            'removed' => count($changes['detached']),
        ]);

        return back()->with('success', 'Your skills were saved. Matching requests now reflect your new selection.');
    }

    public function destroy(CloseAccountRequest $request, AccountLifecycle $lifecycle): RedirectResponse
    {
        $user = $request->user();
        $lifecycle->anonymize($user, $user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('success', 'Your account was closed and your personal data was removed. Past jobs remain anonymously in the marketplace history.');
    }
}
