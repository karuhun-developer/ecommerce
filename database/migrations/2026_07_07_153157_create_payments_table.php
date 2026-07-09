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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('midtrans');
            $table->morphs('payable');
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('payment_type');
            $table->string('account_number');
            $table->string('account_code')->nullable();
            $table->string('channel');
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->decimal('amount', 12, 4)->comment('Original amount before fees');
            $table->decimal('fee', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->comment('Total amount after fees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
