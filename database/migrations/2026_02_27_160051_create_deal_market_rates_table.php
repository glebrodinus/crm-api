<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_market_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            // Open source name, ex: "DAT", "TRUCKSTOP", "SONAR", etc.
            $table->string('source', 50);

            $table->decimal('low_rate', 10, 2)->nullable();
            $table->decimal('avg_rate', 10, 2)->nullable();
            $table->decimal('high_rate', 10, 2)->nullable();

            $table->timestamp('pulled_at')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['deal_id', 'source']);
            $table->index(['source', 'pulled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_market_rates');
    }
};