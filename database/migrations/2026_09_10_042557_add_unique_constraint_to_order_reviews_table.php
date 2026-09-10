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
        Schema::table('order_reviews', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'order_shop_id', 'reviewable_type', 'reviewable_id'],
                'order_reviews_reviewer_target_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropUnique('order_reviews_reviewer_target_unique');
        });
    }
};
