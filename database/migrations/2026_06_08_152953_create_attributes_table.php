<?php

use App\Models\Attribute\AttributeGroup;
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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Shop::class)->nullable()->comment('If null, this attribute is global and can be used by all shops')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AttributeGroup::class)->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Red, XL, White, etc');
            $table->string('value')->nullable()->comment('E.g color: #ff0000, size: L, etc');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
