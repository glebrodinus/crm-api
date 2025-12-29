<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // stage-based quotes (no version column)
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])
                ->default('draft');

            // pricing
            $table->decimal('customer_rate', 10, 2)->nullable();
            $table->decimal('fuel_surcharge', 10, 2)->nullable();

            // store misc accessorials in JSON (tarp, permits, escorts, etc.)
            $table->json('accessorials')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['deal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_quotes');
    }
};