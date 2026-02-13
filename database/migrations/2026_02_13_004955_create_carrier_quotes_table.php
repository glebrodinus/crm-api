<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carrier_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // Carrier snapshot (no carrier table)
            $table->string('carrier_name')->nullable();
            $table->string('carrier_mc', 20)->nullable();
            $table->string('carrier_usdot', 20)->nullable();

            // Contact snapshot
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 255)->nullable();

            // Pricing
            $table->decimal('carrier_rate', 10, 2)->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index('deal_id');
            $table->index('carrier_mc');
            $table->index('carrier_usdot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_quotes');
    }
};