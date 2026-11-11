<?php

namespace Tests\Feature;

use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Private WebSocket channel authorization with the mobile device token. Uses the Reverb
 * (Pusher-protocol) broadcaster with test-only credentials, so real signatures are produced.
 */
class MobileBroadcastChannelTest extends TestCase
{
    use DatabaseTransactions, InteractsWithMobileApi;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'phpunit-key',
            'broadcasting.connections.reverb.secret' => 'phpunit-secret',
            'broadcasting.connections.reverb.app_id' => '100001',
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        app(BroadcastManager::class)->forgetDrivers();
    }

    private function authorize(string $channel): TestResponse
    {
        return $this->postJson(route('api.mobile.broadcasting.auth'), ['socket_id' => '123456.654321', 'channel_name' => $channel]);
    }

    public function test_both_participants_may_subscribe_to_their_conversation(): void
    {
        $booked = $this->bookedConversation();
        $channel = 'private-conversations.'.$booked['conversation']->id;

        $this->asMobileUser($booked['homeowner']);
        $signature = $this->authorize($channel)->assertOk()->json('auth');
        $this->assertSame('phpunit-key:'.hash_hmac('sha256', '123456.654321:'.$channel, 'phpunit-secret'), $signature);

        $this->asMobileUser($booked['handyman']);
        $this->authorize($channel)->assertOk()->assertJsonStructure(['auth']);
    }

    public function test_unrelated_members_may_not_subscribe_to_a_conversation(): void
    {
        $booked = $this->bookedConversation();
        $channel = 'private-conversations.'.$booked['conversation']->id;

        foreach ([HomeownerProfile::factory()->create()->user, HandymanProfile::factory()->create()->user] as $intruder) {
            $this->asMobileUser($intruder);
            $this->authorize($channel)->assertForbidden();
        }

        $this->asMobileUser($booked['homeowner']);
        $this->authorize('private-conversations.999999999')->assertForbidden();
    }

    public function test_members_may_only_subscribe_to_their_own_inbox_channel(): void
    {
        $booked = $this->bookedConversation();

        $this->asMobileUser($booked['homeowner']);
        $this->authorize('private-users.'.$booked['homeowner']->id)->assertOk();
        $this->authorize('private-users.'.$booked['handyman']->id)->assertForbidden();
    }

    public function test_channel_authorization_requires_a_mobile_token(): void
    {
        $booked = $this->bookedConversation();

        $this->authorize('private-conversations.'.$booked['conversation']->id)->assertUnauthorized();
        $this->actingAs($booked['homeowner'])->authorize('private-conversations.'.$booked['conversation']->id)->assertUnauthorized();
    }

    public function test_the_realtime_settings_never_contain_the_reverb_secret(): void
    {
        $booked = $this->bookedConversation();

        $response = $this->asMobileUser($booked['homeowner'])->getJson(route('api.mobile.realtime'))
            ->assertOk()
            ->assertJsonPath('data.key', 'phpunit-key')
            ->assertJsonPath('data.auth_endpoint', route('api.mobile.broadcasting.auth'));

        $this->assertStringNotContainsString('phpunit-secret', $response->getContent());
    }
}
