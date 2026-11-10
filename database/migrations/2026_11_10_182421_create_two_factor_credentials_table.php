<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authenticator-app (TOTP) credentials for administrators, kept apart from the users table so
 * existing account records are not altered. The secret and the recovery-code hashes are
 * encrypted with APP_KEY by the model; plain recovery codes are never stored.
 *
 * Forward-only by design (no down()): rolling back would silently disable every
 * administrator's second factor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->text('secret');
            $table->text('recovery_codes')->nullable();
            $table->dateTime('confirmed_at', 6)->nullable();
            $table->unsignedBigInteger('last_used_timestep')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->foreign('user_id', 'two_factor_credentials_user_fk')
                ->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->engine = 'InnoDB';
        });
    }
};
