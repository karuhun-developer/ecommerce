<?php

use App\Models\Location\Location;
use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Location::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->index();
            $table->unsignedBigInteger('ref_number')->index();
            $table->json('guest_data')->nullable();
            $table->decimal('total_checkout', 15, 2)->default(0);
            $table->decimal('total_shipping', 15, 2)->default(0);
            $table->decimal('application_fee', 15, 2)->default(0);
            $table->decimal('insurance_fee', 15, 2)->default(0);
            $table->decimal('payment_fee', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0)->comment('Pajak (PPN) 11% dari total checkout');
            $table->decimal('total', 15, 2)->default(0);
            $table->boolean('status')->default(false)->comment('Status order, true jika sudah dibayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
