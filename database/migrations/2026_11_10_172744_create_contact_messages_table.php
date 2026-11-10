<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contact Us messages handled by administrators in the dashboard.
 *
 * Forward-only by design: it only adds a new table and is intentionally written without a
 * down() method, so a rollback can never silently destroy messages received from visitors.
 * Only the details needed to answer are stored: no IP address, user agent or account link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 254);
            $table->string('subject', 150);
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->dateTime('read_at', 6)->nullable();
            $table->dateTime('resolved_at', 6)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->index(['status', 'created_at'], 'contact_messages_status_created_idx');
            $table->index(['created_at'], 'contact_messages_created_idx');
            $table->engine = 'InnoDB';
        });

        DB::statement("ALTER TABLE contact_messages ADD CONSTRAINT contact_messages_status_check CHECK (status IN ('new', 'read', 'resolved'))");
        DB::statement("ALTER TABLE contact_messages ADD CONSTRAINT contact_messages_resolved_state_check CHECK ((status = 'resolved') = (resolved_at IS NOT NULL))");
        DB::statement("ALTER TABLE contact_messages ADD CONSTRAINT contact_messages_read_state_check CHECK (status = 'new' OR read_at IS NOT NULL)");
    }
};
