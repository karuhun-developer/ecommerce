<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_links', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('position')->default('left');
            $table->string('destination')->default('page');
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('url', 2048)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['active', 'position', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_links');
    }
};
