<?php

use App\Models\Order\Order;
use App\Models\Shop\Shop;
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
        Schema::create('order_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Shop::class)->constrained()->cascadeOnDelete();
            $table->string('waybill_number')->nullable();
            $table->json('shipping_data')->nullable();
            $table->decimal('total_checkout', 15, 2)->default(0);
            $table->decimal('total_shipping', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0)->comment('Pajak (PPN) 11% dari total checkout');
            $table->decimal('total', 15, 2)->default(0);
            $table->boolean('shipping_status')->default(false)->comment('0 = not shipped, 1 = on the way, 2 = delivered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shops');
    }
};
