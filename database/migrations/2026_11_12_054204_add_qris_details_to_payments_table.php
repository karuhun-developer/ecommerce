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
            $table->text('account_number')->change();
            $table->text('provider_token')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->dateTime('last_checked_at')->nullable();
            $table->index(['driver', 'paid_at'], 'payments_pending_driver_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_pending_driver_index');
            $table->dropColumn(['provider_token', 'provider_payment_id', 'last_checked_at']);
        });
    }
};
