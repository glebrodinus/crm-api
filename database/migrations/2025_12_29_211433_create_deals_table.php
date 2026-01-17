<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();

            // audit + assignment
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // only 2 pipelines forever
            $table->enum('pipeline_type', ['cold', 'repeat']);

            // deal lifecycle (quote -> booked -> done/lost)
            $table->enum('status', [
                'quote_requested',
                'quote_sent',
                'negotiating',
                'booked',
                'completed',
                'cancelled',
                'lost',
            ])->default('quote_requested');

            // lane basics (city/state/zip only)
            $table->string('origin_city')->nullable();
            $table->string('origin_state', 2)->nullable();
            $table->string('origin_zip', 10)->nullable();

            $table->string('destination_city')->nullable();
            $table->string('destination_state', 2)->nullable();
            $table->string('destination_zip', 10)->nullable();

            $table->string('equipment_type')->nullable(); // RGN, Step Deck, etc.
            $table->string('commodity')->nullable();
            $table->integer('weight_lbs')->nullable();

            $table->string('note')->nullable();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['owner_user_id', 'pipeline_type', 'status']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
