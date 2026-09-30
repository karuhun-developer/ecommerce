<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->string('key')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};
