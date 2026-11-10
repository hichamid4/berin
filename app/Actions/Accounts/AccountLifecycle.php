<?php

namespace App\Actions\Accounts;

use App\Exceptions\BusinessRuleViolation;
use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Account status transitions: blocking, unblocking and closure by anonymization.
 *
 * Anonymization keeps the user ID and all historical foreign keys, clears identifying
 * data, and is refused while the account is a party to a booked, unfinished job.
 */
class AccountLifecycle
{
    public function block(User $user, User $administrator): void
    {
        DB::transaction(function () use ($user, $administrator): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->isAdministrator()) {
                throw new BusinessRuleViolation('Administrator accounts cannot be blocked from the dashboard.');
            }

            if ($locked->status !== 'active') {
                throw new BusinessRuleViolation('Only active accounts can be blocked. This account is currently '.$locked->status.'.');
            }

            $locked->forceFill(['status' => 'blocked', 'blocked_at' => now(), 'blocked_by' => $administrator->id])->save();
            DB::table('sessions')->where('user_id', $locked->id)->delete();

            ActivityEvent::record($administrator, 'user.blocked', 'user', $locked->id, ['role' => $locked->role->value]);
        });
    }

    public function unblock(User $user, User $administrator): void
    {
        DB::transaction(function () use ($user, $administrator): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->status !== 'blocked') {
                throw new BusinessRuleViolation('This account is not blocked.');
            }

            $locked->forceFill(['status' => 'active', 'blocked_at' => null, 'blocked_by' => null])->save();

            ActivityEvent::record($administrator, 'user.unblocked', 'user', $locked->id, ['role' => $locked->role->value]);
        });
    }

    /**
     * Explain why an account cannot be closed yet, or return null when closure is allowed.
     */
    public function closureBlocker(User $user): ?string
    {
        if ($user->isAdministrator()) {
            return 'Administrator accounts are managed through the trusted administration process and cannot be closed here.';
        }

        $unfinishedStatuses = [RequestStatus::Booked, RequestStatus::CompletionPending];

        $unfinishedJobs = $user->isHomeowner()
            ? ServiceRequest::query()->where('homeowner_id', $user->id)->whereIn('status', $unfinishedStatuses)->count()
            : Offer::query()
                ->where('handyman_id', $user->id)
                ->where('status', OfferStatus::Accepted)
                ->whereHas('request', fn ($query) => $query->whereIn('status', $unfinishedStatuses))
                ->count();

        if ($unfinishedJobs > 0) {
            return "This account has {$unfinishedJobs} booked ".str('job')->plural($unfinishedJobs)
                .' that '.($unfinishedJobs === 1 ? 'is' : 'are').' not finished yet. Complete and confirm '
                .($unfinishedJobs === 1 ? 'it' : 'them').' before closing the account, so the other party is not left without a contact.';
        }

        return null;
    }

    public function anonymize(User $user, ?User $actor): void
    {
        $photoToDelete = null;

        DB::transaction(function () use ($user, $actor, &$photoToDelete): void {
            $locked = User::query()->withTrashed()->lockForUpdate()->findOrFail($user->id);

            if ($locked->status === 'anonymized') {
                throw new BusinessRuleViolation('This account has already been closed.');
            }

            if ($reason = $this->closureBlocker($locked)) {
                throw new BusinessRuleViolation($reason);
            }

            $cancelledRequests = 0;
            $withdrawnOffers = 0;

            if ($locked->isHomeowner()) {
                $openRequests = ServiceRequest::query()->where('homeowner_id', $locked->id)->where('status', RequestStatus::Open)->lockForUpdate()->get();

                foreach ($openRequests as $request) {
                    $request->offers()->where('status', OfferStatus::Pending)->update(['status' => OfferStatus::Declined, 'updated_at' => now()]);
                    $request->forceFill(['status' => RequestStatus::Cancelled, 'cancelled_at' => now()])->save();
                }

                $cancelledRequests = $openRequests->count();
                DB::table('handyman_favorites')->where('homeowner_id', $locked->id)->delete();
                HomeownerProfile::query()->whereKey($locked->id)->update(['address' => null, 'city' => null]);
            }

            if ($locked->isHandyman()) {
                $withdrawnOffers = Offer::query()->where('handyman_id', $locked->id)->where('status', OfferStatus::Pending)
                    ->update(['status' => OfferStatus::Withdrawn, 'updated_at' => now()]);
                DB::table('handyman_favorites')->where('handyman_id', $locked->id)->delete();

                $profile = HandymanProfile::query()->find($locked->id);
                $photoToDelete = $profile?->photo_path;
                $profile?->update(['address' => null, 'city' => null, 'photo_path' => null]);
            }

            DB::table('password_reset_tokens')->where('email', $locked->email)->delete();
            DB::table('sessions')->where('user_id', $locked->id)->delete();

            $locked->forceFill([
                'first_name' => 'Former',
                'last_name' => 'member',
                'username' => null,
                'email' => null,
                'phone_e164' => null,
                'email_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'status' => 'anonymized',
                'blocked_at' => null,
                'blocked_by' => null,
                'deleted_at' => now(),
            ])->save();

            ActivityEvent::record($actor, $actor?->is($locked) ? 'account.closed' : 'user.anonymized', 'user', $locked->id, [
                'role' => $locked->role->value,
                'requests_cancelled' => $cancelledRequests,
                'offers_withdrawn' => $withdrawnOffers,
            ]);
        });

        if ($photoToDelete !== null) {
            Storage::disk('local')->delete($photoToDelete);
        }
    }
}
