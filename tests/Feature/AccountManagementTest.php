<?php

namespace Tests\Feature;

use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Offer;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Skill;
use App\Models\User;
use App\OfferStatus;
use App\RequestStatus;
use App\UserRole;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_update_with_a_new_email_requires_verification_again(): void
    {
        Notification::fake();
        $homeowner = HomeownerProfile::factory()->create();

        $this->actingAs($homeowner->user)->put(route('account.profile.update'), [
            'first_name' => 'Salma',
            'last_name' => 'Idrissi',
            'username' => 'salma-new',
            'email' => 'Salma.New@example.test',
            'phone_e164' => '+212611223344',
            'city' => 'Rabat',
            'address' => 'Agdal',
        ])->assertRedirect(route('verification.notice'));

        $user = $homeowner->user->fresh();
        $this->assertSame('salma.new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('Rabat', $homeowner->fresh()->city);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'A-brand-new-password-1',
            'password_confirmation' => 'A-brand-new-password-1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'Test-password-2026!',
            'password' => 'A-brand-new-password-1',
            'password_confirmation' => 'A-brand-new-password-1',
        ])->assertSessionHas('success');

        $this->assertTrue(password_verify('A-brand-new-password-1', $user->fresh()->password));
    }

    public function test_handyman_skill_selection_drives_request_matching(): void
    {
        $skill = Skill::factory()->create();
        $service = Service::factory()->create();
        $service->skills()->attach($skill);
        $request = ServiceRequest::factory()->create(['service_id' => $service->id]);
        $handyman = HandymanProfile::factory()->create();
        $handyman->skills()->attach(Skill::factory()->create());

        $this->actingAs($handyman->user)->get(route('handyman.requests.show', $request))->assertForbidden();

        $this->actingAs($handyman->user)->put(route('account.skills.update'), ['skill_ids' => [$skill->id]])->assertSessionHas('success');

        $this->assertSame([$skill->id], $handyman->skills()->pluck('skills.id')->all());
        $this->actingAs($handyman->user)->get(route('handyman.requests.show', $request))->assertOk();
    }

    public function test_handyman_cannot_select_an_inactive_skill(): void
    {
        $handyman = HandymanProfile::factory()->create();
        $inactive = Skill::factory()->inactive()->create();

        $this->actingAs($handyman->user)->put(route('account.skills.update'), ['skill_ids' => [$inactive->id]])
            ->assertSessionHasErrors('skill_ids.0');
    }

    public function test_closing_an_account_anonymizes_it_and_keeps_history(): void
    {
        $homeowner = HomeownerProfile::factory()->create();
        $handyman = HandymanProfile::factory()->create();
        $open = ServiceRequest::factory()->create(['homeowner_id' => $homeowner->user_id]);
        $pending = Offer::factory()->create(['request_id' => $open->id, 'handyman_id' => $handyman->user_id]);
        $homeowner->favoriteHandymen()->attach($handyman->user_id);

        $this->actingAs($homeowner->user)->delete(route('account.destroy'), [
            'close_password' => 'Test-password-2026!',
            'confirmation' => 'CLOSE',
        ])->assertRedirect(route('home'));

        $this->assertGuest();
        $user = User::withTrashed()->findOrFail($homeowner->user_id);
        $this->assertSame('anonymized', $user->status);
        $this->assertNull($user->email);
        $this->assertNull($user->username);
        $this->assertSame('Former member', $user->fullName());
        $this->assertSoftDeleted($user);
        $this->assertSame(RequestStatus::Cancelled, $open->fresh()->status);
        $this->assertSame(OfferStatus::Declined, $pending->fresh()->status);
        $this->assertDatabaseMissing('handyman_favorites', ['homeowner_id' => $homeowner->user_id]);
        $this->assertDatabaseHas('service_requests', ['id' => $open->id, 'homeowner_id' => $homeowner->user_id]);
    }

    public function test_account_with_an_unfinished_booked_job_cannot_be_closed(): void
    {
        $homeowner = HomeownerProfile::factory()->create();
        $request = ServiceRequest::factory()->create(['homeowner_id' => $homeowner->user_id]);
        $offer = Offer::factory()->accepted()->create(['request_id' => $request->id]);
        $request->forceFill(['status' => RequestStatus::Booked, 'accepted_offer_id' => $offer->id])->save();

        $this->actingAs($homeowner->user)->from(route('account.edit'))->delete(route('account.destroy'), [
            'close_password' => 'Test-password-2026!',
            'confirmation' => 'CLOSE',
        ])->assertSessionHas('error', fn (string $message): bool => str_contains($message, '1 booked job'));

        $this->assertSame('active', $homeowner->user->fresh()->status);
    }

    public function test_administrator_blocks_and_unblocks_a_member(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $member = User::factory()->create();

        $this->actingAs($administrator)->post(route('admin.users.block', $member))->assertSessionHas('success');
        $this->assertSame('blocked', $member->fresh()->status);
        $this->assertSame($administrator->id, $member->fresh()->blocked_by);

        $this->actingAs($member->fresh())->get(route('homeowner.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($administrator)->post(route('admin.users.unblock', $member))->assertSessionHas('success');
        $this->assertSame('active', $member->fresh()->status);
        $this->assertNull($member->fresh()->blocked_at);
    }

    public function test_handyman_removing_a_skill_hides_its_requests(): void
    {
        [$kept, $removed] = Skill::factory()->count(2)->create();
        $service = Service::factory()->create();
        $service->skills()->attach($removed);
        $request = ServiceRequest::factory()->create(['service_id' => $service->id, 'title' => 'Request needing the removed skill']);
        $handyman = HandymanProfile::factory()->create();
        $handyman->skills()->attach([$kept->id, $removed->id]);

        $this->actingAs($handyman->user)->get(route('handyman.requests.index'))->assertSee('Request needing the removed skill');

        $this->actingAs($handyman->user)->put(route('account.skills.update'), ['skill_ids' => [$kept->id]])->assertSessionHas('success');

        $this->actingAs($handyman->user)->get(route('handyman.requests.index'))->assertDontSee('Request needing the removed skill');
        $this->actingAs($handyman->user)->get(route('handyman.requests.show', $request))->assertForbidden();
        $this->assertModelExists($removed);
    }

    public function test_administrator_searches_users_and_anonymizes_a_member(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $member = User::factory()->create(['first_name' => 'Zakaria', 'last_name' => 'Searchable', 'phone_e164' => '+212600000099']);

        $this->actingAs($administrator)->get(route('admin.users.index', ['q' => 'Searchable']))->assertOk()->assertSee('Zakaria Searchable');
        $this->actingAs($administrator)->get(route('admin.users.index', ['q' => 'no-such-member']))->assertOk()->assertDontSee('Zakaria Searchable');

        $this->actingAs($administrator)->post(route('admin.users.anonymize', $member), ['confirmation' => 'wrong'])->assertSessionHasErrors('confirmation');
        $this->assertSame('active', $member->fresh()->status);

        $this->actingAs($administrator)->post(route('admin.users.anonymize', $member), ['confirmation' => 'ANONYMIZE'])->assertSessionHas('success');
        $anonymized = User::withTrashed()->findOrFail($member->id);
        $this->assertSame('anonymized', $anonymized->status);
        $this->assertNull($anonymized->phone_e164);
        $this->assertNull($anonymized->email);
        $this->actingAs($administrator)->get(route('admin.users.show', $member->id))->assertOk()->assertSee('Former member');
    }

    public function test_administrator_accounts_cannot_be_blocked(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $otherAdministrator = User::factory()->role(UserRole::Administrator)->create();

        $this->actingAs($administrator)->post(route('admin.users.block', $otherAdministrator))
            ->assertSessionHas('error', 'Administrator accounts cannot be blocked from the dashboard.');
        $this->assertSame('active', $otherAdministrator->fresh()->status);
    }
}
