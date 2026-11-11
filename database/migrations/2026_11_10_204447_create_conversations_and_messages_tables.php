<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-app messaging for the mobile application.
 *
 * A conversation belongs to exactly one accepted offer (unique offer_id) and its two participants,
 * the request's homeowner and the offer's handyman. The composite foreign key ties the stored
 * request to the offer's own request, so a conversation can never point at a mismatched pair.
 * Messages are immutable apart from read_at; (sender_id, client_message_id) makes retries idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('request_id');
            $table->foreignId('homeowner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('handyman_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->dateTime('last_activity_at', 6);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique('offer_id', 'conversations_one_per_offer_unique');
            $table->foreign('offer_id', 'conversations_offer_fk')->references('id')->on('offers')->restrictOnDelete();
            $table->foreign(['request_id', 'offer_id'], 'conversations_request_offer_fk')
                ->references(['request_id', 'id'])->on('offers')->restrictOnDelete();
            $table->index(['homeowner_id', 'last_activity_at'], 'conversations_homeowner_activity_idx');
            $table->index(['handyman_id', 'last_activity_at'], 'conversations_handyman_activity_idx');
            $table->engine = 'InnoDB';
        });

        DB::statement('ALTER TABLE conversations ADD CONSTRAINT conversations_distinct_participants_check CHECK (homeowner_id <> handyman_id)');

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->restrictOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->uuid('client_message_id');
            $table->text('body');
            $table->dateTime('read_at', 6)->nullable();
            $table->dateTime('created_at', 6);

            $table->unique(['sender_id', 'client_message_id'], 'messages_sender_client_message_unique');
            $table->index(['conversation_id', 'id'], 'messages_conversation_cursor_idx');
            $table->index(['recipient_id', 'read_at', 'conversation_id'], 'messages_recipient_unread_idx');
            $table->engine = 'InnoDB';
        });

        DB::statement('ALTER TABLE messages ADD CONSTRAINT messages_distinct_parties_check CHECK (sender_id <> recipient_id)');

        Schema::table('conversations', function (Blueprint $table): void {
            $table->foreign('last_message_id', 'conversations_last_message_fk')->references('id')->on('messages')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropForeign('conversations_last_message_fk');
        });

        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
