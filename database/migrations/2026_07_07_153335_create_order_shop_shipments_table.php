<?php

use App\Models\Order\OrderShop;
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
        Schema::create('order_shop_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(OrderShop::class)->constrained()->cascadeOnDelete();
            $table->string('event')->default('order.status');
            $table->string('courier_tracking_id')->nullable();
            $table->string('courier_waybill_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('courier_company')->nullable();
            $table->string('courier_type')->nullable();
            $table->string('courier_driver_name')->nullable();
            $table->string('courier_driver_phone')->nullable();
            $table->string('courier_driver_photo_url')->nullable();
            $table->string('courier_driver_plate_number')->nullable();
            $table->string('courier_link')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shop_shipments');
    }
};
