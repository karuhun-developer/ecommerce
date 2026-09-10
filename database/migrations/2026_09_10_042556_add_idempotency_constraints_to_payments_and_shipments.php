<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['payable_type', 'payable_id'], 'payments_payable_unique');
        });

        Schema::table('order_shop_shipments', function (Blueprint $table) {
            $table->string('provider_event_key', 64)->nullable()->unique()->after('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_shop_shipments', function (Blueprint $table) {
            $table->dropUnique(['provider_event_key']);
            $table->dropColumn('provider_event_key');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_payable_unique');
        });
    }
};
