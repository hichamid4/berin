<?php

namespace Tests\Feature;

use App\ContactMessageStatus;
use App\Models\ActivityEvent;
use App\Models\ContactMessage;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Salma Bennani',
            'email' => 'salma.bennani@example.test',
            'subject' => 'Question about posting a request',
            'message' => 'Hello, can I post a request for two different services at the same address?',
        ], $overrides);
    }

    public function test_the_contact_page_shows_the_form_with_a_hidden_spam_trap(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Send us a message')
            ->assertSee('action="'.route('contact.store').'"', false)
            ->assertSee('name="website"', false)
            ->assertSee('class="contact-trap" aria-hidden="true"', false);
    }

    public function test_a_valid_message_is_stored_as_new_and_the_visitor_sees_a_confirmation(): void
    {
        $this->post(route('contact.store'), $this->validMessage([
            'name' => '  Salma   Bennani ',
            'email' => ' Salma.Bennani@Example.TEST ',
        ]))
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_message_sent', true)
            ->assertSessionHasNoErrors();

        $message = ContactMessage::query()->sole();
        $this->assertSame('Salma Bennani', $message->name);
        $this->assertSame('salma.bennani@example.test', $message->email);
        $this->assertSame('Question about posting a request', $message->subject);
        $this->assertSame(ContactMessageStatus::New, $message->status);
        $this->assertNull($message->read_at);
        $this->assertNull($message->handled_by);

        $this->get(route('contact'))->assertSee('Message sent')->assertDontSee('Send us a message');
    }

    public function test_only_the_details_needed_to_reply_are_stored(): void
    {
        $this->post(route('contact.store'), $this->validMessage(['phone' => '+212612345678', 'status' => 'resolved']));

        $columns = array_keys((array) DB::table('contact_messages')->sole());
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'email', 'subject', 'message', 'status', 'read_at', 'resolved_at', 'handled_by', 'created_at', 'updated_at'],
            $columns,
        );
        $this->assertSame(ContactMessageStatus::New, ContactMessage::query()->sole()->status);
    }

    public function test_missing_fields_are_rejected_with_clear_messages(): void
    {
        $this->from(route('contact'))->post(route('contact.store'), [])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors([
                'name' => 'Please tell us your name.',
                'email' => 'Please enter the email address we should reply to.',
                'subject' => 'Please add a subject so we can route your message.',
                'message' => 'Please write your message.',
            ]);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_invalid_and_oversized_values_are_rejected(): void
    {
        $this->from(route('contact'))->post(route('contact.store'), $this->validMessage([
            'name' => str_repeat('a', 101),
            'email' => 'not-an-email',
            'subject' => 'Hi',
            'message' => 'Too short',
        ]))->assertSessionHasErrors([
            'name' => 'Your name may not be longer than 100 characters.',
            'email' => 'Please enter a valid email address, for example name@example.com.',
            'subject' => 'The subject must be at least 3 characters.',
            'message' => 'Your message must be at least 10 characters so we can understand your request.',
        ]);

        $this->from(route('contact'))->post(route('contact.store'), $this->validMessage(['message' => str_repeat('a', 5001)]))
            ->assertSessionHasErrors(['message' => 'Your message may not be longer than 5,000 characters.']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_validation_errors_keep_the_visitor_input_and_render_on_the_form(): void
    {
        $this->from(route('contact'))->followingRedirects()
            ->post(route('contact.store'), $this->validMessage(['email' => 'broken']))
            ->assertOk()
            ->assertSee('Please enter a valid email address, for example name@example.com.')
            ->assertSee('value="Salma Bennani"', false)
            ->assertSee('Hello, can I post a request for two different services at the same address?');
    }

    public function test_a_filled_honeypot_looks_successful_but_stores_nothing(): void
    {
        $this->post(route('contact.store'), $this->validMessage(['website' => 'https://spam.example']))
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_message_sent', true)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_an_identical_message_sent_twice_is_stored_once(): void
    {
        $this->post(route('contact.store'), $this->validMessage())->assertRedirect(route('contact'));
        $this->post(route('contact.store'), $this->validMessage())->assertRedirect(route('contact'))->assertSessionHas('contact_message_sent', true);

        $this->assertDatabaseCount('contact_messages', 1);

        $this->travel(ContactMessage::DUPLICATE_WINDOW_MINUTES + 1)->minutes();
        $this->post(route('contact.store'), $this->validMessage());

        $this->assertDatabaseCount('contact_messages', 2);
    }

    public function test_repeated_submissions_are_rate_limited_per_visitor(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->from(route('contact'))->post(route('contact.store'), $this->validMessage(['subject' => "Question number {$attempt}"]))
                ->assertRedirect(route('contact'))
                ->assertSessionHasNoErrors();
        }

        $this->from(route('contact'))->post(route('contact.store'), $this->validMessage(['subject' => 'Question number 4']))
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['message' => 'You have sent several messages in the last minute. Please wait a little before sending another one.'])
            ->assertSessionHasInput('subject', 'Question number 4');

        $this->assertDatabaseCount('contact_messages', 3);
        $this->assertDatabaseMissing('contact_messages', ['subject' => 'Question number 4']);

        $this->travel(61)->seconds();
        $this->from(route('contact'))->post(route('contact.store'), $this->validMessage(['subject' => 'Question number 5']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_messages', 4);
    }

    public function test_rejected_submissions_do_not_use_up_the_message_quota(): void
    {
        foreach (range(1, 4) as $attempt) {
            $this->from(route('contact'))->post(route('contact.store'), $this->validMessage(['email' => "typo-{$attempt}"]))
                ->assertSessionHasErrors(['email' => 'Please enter a valid email address, for example name@example.com.']);
        }

        $this->from(route('contact'))->post(route('contact.store'), $this->validMessage())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contact_message_sent', true);

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_the_daily_quota_applies_after_the_minute_quota_resets(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('contact.store'), $this->validMessage(['subject' => "Daily question {$attempt}"]))->assertSessionHasNoErrors();
            if ($attempt % 3 === 0) {
                $this->travel(61)->seconds();
            }
        }

        $this->travel(61)->seconds();
        $this->post(route('contact.store'), $this->validMessage(['subject' => 'Daily question 11']))
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['message' => 'You have sent several messages today. Please wait a little before sending another one.']);

        $this->assertDatabaseCount('contact_messages', 10);
    }

    public function test_a_flood_of_requests_is_stopped_before_validation_even_without_a_referrer(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('contact.store'), [])->assertSessionHasErrors('name');
        }

        $this->post(route('contact.store'), $this->validMessage())
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['message' => 'Too many attempts from your connection. Please wait a minute before trying again.']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        ContactMessage::factory()->create(['subject' => 'Discount of 50% on plumbing?']);
        ContactMessage::factory()->create(['subject' => 'Painting quote 500 MAD']);

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['q' => '50%']))
            ->assertSee('Discount of 50% on plumbing?')
            ->assertDontSee('Painting quote 500 MAD');
    }

    public function test_guests_homeowners_and_handymen_cannot_reach_contact_messages(): void
    {
        $message = ContactMessage::factory()->create();

        $this->get(route('admin.contact-messages.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.contact-messages.show', $message))->assertRedirect(route('admin.login'));

        foreach ([UserRole::Homeowner, UserRole::Handyman] as $role) {
            $member = User::factory()->role($role)->create();

            $this->actingAs($member)->get(route('admin.contact-messages.index'))->assertForbidden();
            $this->actingAs($member)->get(route('admin.contact-messages.show', $message))->assertForbidden();
            $this->actingAs($member)->patch(route('admin.contact-messages.status', $message), ['status' => 'resolved'])->assertForbidden();
        }

        $this->assertSame(ContactMessageStatus::New, $message->fresh()->status);
    }

    public function test_administrator_lists_messages_with_dates_and_status_counts(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $older = ContactMessage::factory()->create(['subject' => 'Older question', 'created_at' => now()->subDays(3)]);
        $newer = ContactMessage::factory()->resolved()->create(['subject' => 'Newer question']);

        $this->actingAs($administrator)->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSeeInOrder(['Newer question', 'Older question'])
            ->assertSee($older->created_at->translatedFormat('j M Y'))
            ->assertSee('2 messages')
            ->assertSee('Contact messages');
        $this->assertNotNull($newer->resolved_at);
    }

    public function test_administrator_searches_and_filters_messages_by_status(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        ContactMessage::factory()->create(['subject' => 'Leaking kitchen tap', 'message' => 'My handyman did not come back to finish the job.']);
        ContactMessage::factory()->read()->create(['subject' => 'Invoice question', 'email' => 'karim.tahiri@example.test']);
        ContactMessage::factory()->resolved()->create(['subject' => 'Password reset problem']);

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['q' => 'kitchen']))
            ->assertOk()->assertSee('Leaking kitchen tap')->assertDontSee('Invoice question')->assertDontSee('Password reset problem');

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['q' => 'karim.tahiri']))
            ->assertSee('Invoice question')->assertDontSee('Leaking kitchen tap');

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['q' => 'finish the job']))
            ->assertSee('Leaking kitchen tap');

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['status' => 'resolved']))
            ->assertSee('Password reset problem')->assertDontSee('Leaking kitchen tap')->assertDontSee('Invoice question');

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['status' => 'read', 'q' => 'kitchen']))
            ->assertSee('No message matches these filters.');

        $this->actingAs($administrator)->get(route('admin.contact-messages.index', ['status' => 'unknown']))
            ->assertOk()->assertSee('Leaking kitchen tap')->assertSee('Invoice question');
    }

    public function test_administrator_views_message_details_without_changing_the_status(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $message = ContactMessage::factory()->create([
            'subject' => 'Handyman profile photo',
            'message' => "First line.\nSecond line with <script>alert(1)</script>",
        ]);

        $this->actingAs($administrator)->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->assertSee('Handyman profile photo')
            ->assertSee($message->email)
            ->assertSee($message->created_at->translatedFormat('j M Y H:i'))
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Mark as read');

        $this->assertSame(ContactMessageStatus::New, $message->fresh()->status);
    }

    public function test_administrator_marks_a_message_as_read_then_resolved_and_can_reopen_it(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $message = ContactMessage::factory()->create();
        $detail = route('admin.contact-messages.show', $message);

        $this->actingAs($administrator)->from($detail)->patch(route('admin.contact-messages.status', $message), ['status' => 'read'])
            ->assertRedirect($detail)->assertSessionHas('success', 'The message was marked as Read.');
        $message->refresh();
        $this->assertSame(ContactMessageStatus::Read, $message->status);
        $this->assertNotNull($message->read_at);
        $this->assertNull($message->resolved_at);
        $this->assertSame($administrator->id, $message->handled_by);

        $this->actingAs($administrator)->from($detail)->patch(route('admin.contact-messages.status', $message), ['status' => 'resolved'])
            ->assertSessionHas('success', 'The message was marked as Resolved.');
        $message->refresh();
        $this->assertSame(ContactMessageStatus::Resolved, $message->status);
        $this->assertNotNull($message->resolved_at);

        $this->actingAs($administrator)->from($detail)->patch(route('admin.contact-messages.status', $message), ['status' => 'read']);
        $message->refresh();
        $this->assertSame(ContactMessageStatus::Read, $message->status);
        $this->assertNull($message->resolved_at);
        $this->assertNotNull($message->read_at);

        $this->assertSame(3, ActivityEvent::query()->where('subject_type', 'contact_message')->where('subject_id', $message->id)->count());
        $this->assertDatabaseHas('activity_events', [
            'actor_user_id' => $administrator->id,
            'action' => 'contact_message.status_updated',
            'subject_type' => 'contact_message',
            'subject_id' => $message->id,
        ]);
    }

    public function test_a_new_message_can_be_resolved_directly_and_marked_unread_again(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $message = ContactMessage::factory()->create();

        $this->actingAs($administrator)->patch(route('admin.contact-messages.status', $message), ['status' => 'resolved']);
        $message->refresh();
        $this->assertSame(ContactMessageStatus::Resolved, $message->status);
        $this->assertNotNull($message->read_at);

        $this->actingAs($administrator)->patch(route('admin.contact-messages.status', $message), ['status' => 'new']);
        $message->refresh();
        $this->assertSame(ContactMessageStatus::New, $message->status);
        $this->assertNull($message->read_at);
        $this->assertNull($message->resolved_at);
    }

    public function test_an_invalid_status_is_rejected_and_an_unchanged_status_is_not_logged(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $message = ContactMessage::factory()->read()->create();

        $this->actingAs($administrator)->patch(route('admin.contact-messages.status', $message), ['status' => 'archived'])
            ->assertSessionHasErrors(['status' => 'Choose a valid status: new, read or resolved.']);

        $this->actingAs($administrator)->patch(route('admin.contact-messages.status', $message), ['status' => 'read'])
            ->assertSessionHas('success', 'This message is already marked as Read.');

        $this->assertSame(ContactMessageStatus::Read, $message->fresh()->status);
        $this->assertSame(0, ActivityEvent::query()->where('subject_type', 'contact_message')->count());
    }

    public function test_the_sidebar_shows_how_many_messages_are_new(): void
    {
        $administrator = User::factory()->role(UserRole::Administrator)->create();
        $newCount = ContactMessage::query()->where('status', 'new')->count();
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->resolved()->create();

        $this->actingAs($administrator)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.contact-messages.index'))
            ->assertSee(($newCount + 2).'<span class="visually-hidden"> new</span>', false);
    }

    public function test_the_database_rejects_inconsistent_statuses(): void
    {
        $message = ContactMessage::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('contact_messages')->where('id', $message->id)->update(['status' => 'resolved', 'read_at' => now()]);
    }
}
