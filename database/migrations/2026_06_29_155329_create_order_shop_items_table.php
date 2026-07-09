<?php

use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Product\ProductFlat;
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
        Schema::create('order_shop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(OrderShop::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ProductFlat::class)->constrained()->cascadeOnDelete();
            $table->json('product_data')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shop_items');
    }
};
