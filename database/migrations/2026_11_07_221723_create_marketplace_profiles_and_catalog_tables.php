<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeowner_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->foreign('user_id', 'homeowner_profiles_user_fk')
                ->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->engine = 'InnoDB';
        });

        Schema::create('handyman_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('photo_path', 512)->nullable();
            $table->foreign('user_id', 'handyman_profiles_user_fk')
                ->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->index('city', 'handyman_profiles_city_idx');
            $table->engine = 'InnoDB';
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->engine = 'InnoDB';
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('name', 160);
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category_id', 'name'], 'services_category_name_unique');
            $table->index(['category_id', 'is_active'], 'services_catalog_filter_idx');
            $table->engine = 'InnoDB';
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->engine = 'InnoDB';
        });

        Schema::create('handyman_skill', function (Blueprint $table) {
            $table->unsignedBigInteger('handyman_id');
            $table->unsignedBigInteger('skill_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['handyman_id', 'skill_id']);
            $table->foreign('handyman_id', 'handyman_skill_profile_fk')
                ->references('user_id')->on('handyman_profiles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('skill_id', 'handyman_skill_skill_fk')
                ->references('id')->on('skills')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['skill_id', 'handyman_id'], 'handyman_skill_reverse_idx');
            $table->engine = 'InnoDB';
        });

        Schema::create('service_skill', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('skill_id');
            $table->primary(['service_id', 'skill_id']);
            $table->foreign('service_id', 'service_skill_service_fk')
                ->references('id')->on('services')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('skill_id', 'service_skill_skill_fk')
                ->references('id')->on('skills')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['skill_id', 'service_id'], 'service_skill_reverse_idx');
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_skill');
        Schema::dropIfExists('handyman_skill');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('handyman_profiles');
        Schema::dropIfExists('homeowner_profiles');
    }
};
