<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('offer_id')->primary();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->foreign('offer_id', 'reviews_offer_fk')
                ->references('id')->on('offers')->restrictOnDelete()->cascadeOnUpdate();
            $table->engine = 'InnoDB';
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_check CHECK (rating BETWEEN 1 AND 5)');

        Schema::create('handyman_favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('homeowner_id');
            $table->unsignedBigInteger('handyman_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['homeowner_id', 'handyman_id']);
            $table->foreign('homeowner_id', 'handyman_favorites_homeowner_fk')
                ->references('user_id')->on('homeowner_profiles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('handyman_id', 'handyman_favorites_handyman_fk')
                ->references('user_id')->on('handyman_profiles')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['handyman_id', 'homeowner_id'], 'handyman_favorites_reverse_idx');
            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handyman_favorites');
        DB::statement('ALTER TABLE reviews DROP CHECK reviews_rating_check');
        Schema::dropIfExists('reviews');
    }
};
