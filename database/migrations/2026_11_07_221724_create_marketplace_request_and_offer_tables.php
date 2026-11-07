<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homeowner_id')->constrained('homeowner_profiles', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('title', 180);
            $table->text('description');
            $table->string('service_location', 500);
            $table->string('service_city', 120);
            $table->date('desired_start_date');
            $table->string('status', 24)->default('open');
            $table->unsignedBigInteger('accepted_offer_id')->nullable();
            $table->timestamp('handyman_completed_at')->nullable();
            $table->timestamp('homeowner_confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['id', 'accepted_offer_id'], 'service_requests_selected_offer_idx');
            $table->index(['homeowner_id', 'status', 'created_at'], 'service_requests_owner_status_idx');
            $table->index(['service_id', 'status', 'desired_start_date'], 'service_requests_service_status_idx');
            $table->index(['service_city', 'status'], 'service_requests_city_status_idx');
            $table->engine = 'InnoDB';
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('service_requests')->restrictOnDelete();
            $table->foreignId('handyman_id')->constrained('handyman_profiles', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('MAD');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            $table->string('status', 20)->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_request_id')->nullable()
                ->storedAs("CASE WHEN `status` = 'accepted' THEN `request_id` ELSE NULL END");
            $table->timestamps();
            $table->unique(['request_id', 'id'], 'offers_request_id_unique');
            $table->unique('accepted_request_id', 'offers_one_accepted_per_request_unique');
            $table->index(['request_id', 'status'], 'offers_request_status_idx');
            $table->index(['handyman_id', 'status', 'created_at'], 'offers_handyman_status_idx');
            $table->index(['currency', 'price'], 'offers_currency_price_idx');
            $table->engine = 'InnoDB';
        });

        DB::statement("ALTER TABLE service_requests ADD CONSTRAINT service_requests_status_check CHECK (status IN ('open', 'booked', 'completion_pending', 'completed', 'cancelled'))");
        DB::statement("ALTER TABLE service_requests ADD CONSTRAINT service_requests_lifecycle_check CHECK ((status = 'open' AND accepted_offer_id IS NULL AND handyman_completed_at IS NULL AND homeowner_confirmed_at IS NULL AND cancelled_at IS NULL) OR (status = 'booked' AND accepted_offer_id IS NOT NULL AND handyman_completed_at IS NULL AND homeowner_confirmed_at IS NULL AND cancelled_at IS NULL) OR (status = 'completion_pending' AND accepted_offer_id IS NOT NULL AND handyman_completed_at IS NOT NULL AND homeowner_confirmed_at IS NULL AND cancelled_at IS NULL) OR (status = 'completed' AND accepted_offer_id IS NOT NULL AND handyman_completed_at IS NOT NULL AND homeowner_confirmed_at IS NOT NULL AND cancelled_at IS NULL) OR (status = 'cancelled' AND accepted_offer_id IS NULL AND handyman_completed_at IS NULL AND homeowner_confirmed_at IS NULL AND cancelled_at IS NOT NULL))");
        DB::statement("ALTER TABLE offers ADD CONSTRAINT offers_status_check CHECK (status IN ('pending', 'accepted', 'declined', 'withdrawn', 'superseded'))");
        DB::statement('ALTER TABLE offers ADD CONSTRAINT offers_dates_price_check CHECK (end_date >= start_date AND price > 0)');
        DB::statement("ALTER TABLE offers ADD CONSTRAINT offers_accepted_timestamp_check CHECK ((status = 'accepted' AND accepted_at IS NOT NULL) OR (status <> 'accepted' AND accepted_at IS NULL))");

        Schema::create('request_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('service_requests')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 512)->unique();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['request_id', 'sort_order'], 'request_photos_order_unique');
            $table->engine = 'InnoDB';
        });

        Schema::create('offer_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 512)->unique();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['offer_id', 'sort_order'], 'offer_photos_order_unique');
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_photos');
        Schema::dropIfExists('request_photos');

        DB::statement('ALTER TABLE service_requests DROP CHECK service_requests_lifecycle_check');
        DB::statement('ALTER TABLE service_requests DROP CHECK service_requests_status_check');
        DB::statement('ALTER TABLE offers DROP CHECK offers_accepted_timestamp_check');
        DB::statement('ALTER TABLE offers DROP CHECK offers_dates_price_check');
        DB::statement('ALTER TABLE offers DROP CHECK offers_status_check');

        Schema::dropIfExists('offers');
        Schema::dropIfExists('service_requests');
    }
};
