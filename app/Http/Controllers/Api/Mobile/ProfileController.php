<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdateSkillsRequest;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\ActivityEvent;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The member's own profile, password and (handymen) skills, with the website's validation rules.
 */
class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return ProfileResource::make($request->user()->load(['homeownerProfile', 'handymanProfile.skills']));
    }

    /**
     * Same effect as the website account page. Changing the email address requires confirming the
     * new address again, exactly like on the website; the app then asks the member to confirm it.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
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

        if ($emailChanged) {
            rescue(fn () => $user->sendEmailVerificationNotification());
        }

        return response()->json([
            'data' => ProfileResource::make($user->refresh()->load(['homeownerProfile', 'handymanProfile.skills']))->resolve($request),
            'email_verification_required' => $emailChanged,
        ]);
    }

    /**
     * Change the password and sign out every other device (other app tokens and website sessions).
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        DB::transaction(function () use ($user, $request, $currentToken): void {
            $user->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->whereKeyNot($currentToken->getKey())->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        ActivityEvent::record($user, 'account.password_changed', 'user', $user->id);

        return response()->json(['message' => 'Your password was changed. Other signed-in devices were signed out.']);
    }

    /**
     * Skill catalog for handymen: active skills plus any inactive skill they still hold.
     */
    public function skills(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isHandyman(), 403);
        $held = $user->handymanProfile->skills()->pluck('skills.id');

        $skills = Skill::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $held))
            ->with(['services' => fn ($query) => $query->select('services.id', 'services.name', 'services.category_id')])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $skills->map(fn (Skill $skill): array => [
            'id' => $skill->id,
            'name' => $skill->name,
            'is_active' => (bool) $skill->is_active,
            'selected' => $held->contains($skill->id),
            'services' => $skill->services->pluck('name')->values(),
        ])]);
    }

    public function updateSkills(UpdateSkillsRequest $request): JsonResponse
    {
        $user = $request->user();
        $changes = $user->handymanProfile->skills()->sync($request->validated('skill_ids'));

        ActivityEvent::record($user, 'handyman.skills_updated', 'user', $user->id, [
            'added' => count($changes['attached']),
            'removed' => count($changes['detached']),
        ]);

        return $this->skills($request);
    }
}
