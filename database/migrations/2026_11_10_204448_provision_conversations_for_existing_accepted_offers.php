<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give every offer that was accepted before messaging existed its conversation.
 *
 * Only the offer each request actually selected (service_requests.accepted_offer_id) qualifies.
 * The NOT EXISTS guard plus the unique offer_id index make the step safe to repeat.
 * Data-only and additive: down() leaves the rows alone (the table migration's down() drops them).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now()->format('Y-m-d H:i:s.u');

        DB::statement(
            'INSERT INTO conversations (offer_id, request_id, homeowner_id, handyman_id, last_activity_at, created_at, updated_at)
             SELECT o.id, o.request_id, r.homeowner_id, o.handyman_id, COALESCE(o.accepted_at, ?), ?, ?
             FROM offers o
             INNER JOIN service_requests r ON r.id = o.request_id AND r.accepted_offer_id = o.id
             WHERE o.status = ?
               AND NOT EXISTS (SELECT 1 FROM conversations c WHERE c.offer_id = o.id)',
            [$now, $now, $now, 'accepted'],
        );
    }

    public function down(): void
    {
        //
    }
};
