<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreign(['id', 'accepted_offer_id'], 'service_requests_selected_offer_fk')
                ->references(['request_id', 'id'])->on('offers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign('service_requests_selected_offer_fk');
        });
    }
};
