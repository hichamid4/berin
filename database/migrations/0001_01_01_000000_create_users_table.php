<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20)->default('homeowner');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('username', 40)->nullable()->unique();
            $table->string('email', 254)->nullable()->unique();
            $table->string('phone_e164', 16)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status', 20)->default('active');
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role', 'status'], 'users_role_status_idx');
            $table->index('blocked_at', 'users_blocked_at_idx');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('homeowner', 'handyman', 'admin'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_identity_check CHECK ((status = 'anonymized' AND deleted_at IS NOT NULL AND email IS NULL AND username IS NULL) OR (status IN ('active', 'blocked') AND deleted_at IS NULL AND email IS NOT NULL AND username IS NOT NULL))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_block_state_check CHECK ((status = 'blocked' AND blocked_at IS NOT NULL AND blocked_by IS NOT NULL) OR (status <> 'blocked' AND blocked_at IS NULL AND blocked_by IS NULL))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
