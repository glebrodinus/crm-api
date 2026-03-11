<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('deal_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            // only store real states
            $table->enum('status', ['draft', 'sent', 'rejected'])
                ->default('draft');

            // pricing
            $table->decimal('customer_rate', 10, 2);

            // negotiation info
            $table->decimal('competitor_rate', 10, 2)->nullable();
            $table->decimal('customer_counter_rate', 10, 2)->nullable();

            // rejection info
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_reason')->nullable();

            $table->string('note')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // accepted = selected quote
            $table->timestamp('accepted_at')->nullable();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['deal_id', 'status']);
            $table->index(['deal_id', 'accepted_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_quotes');
    }
};