<?php

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Shop::class)->constrained()->cascadeOnDelete();
            $table->string('type')->default('simple')->comment('simple, variable, digital, service');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->unsignedBigInteger('stock')->default(0)->comment('Total stock for simple product. For variable product, this will be the sum of all variant stocks');
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedBigInteger('total_reviews')->default(0);
            $table->unsignedBigInteger('total_sales')->default(0);
            $table->boolean('is_unlimited_stock')->default(false)->comment('If true, stock will not be reduced when order is placed');
            $table->boolean('status')->default(true)->comment('true = active, false = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
