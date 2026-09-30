<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['active', 'sort_order']);
        });

        foreach (['brand' => 'Ecommerce', 'buy' => 'Beli', 'sell' => 'Jual'] as $key => $name) {
            DB::table('footer_groups')->insert([
                'key' => $key, 'name' => $name, 'active' => true,
                'sort_order' => array_search($key, ['brand', 'buy', 'sell']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_groups');
    }
};
