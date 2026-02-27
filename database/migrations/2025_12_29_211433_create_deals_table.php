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

            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', [
                'requested',
                'quoted',
                'booked',
                'lost',
            ])->default('requested');

            $table->string('origin_city')->nullable();
            $table->string('origin_state', 2)->nullable();
            $table->string('origin_zip', 10)->nullable();

            $table->string('destination_city')->nullable();
            $table->string('destination_state', 2)->nullable();
            $table->string('destination_zip', 10)->nullable();

            $table->string('commodity')->nullable();
            $table->integer('weight_lbs')->nullable();

            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->json('trailer_types')->nullable();

            // flags
            $table->boolean('is_oversize')->default(false);
            $table->boolean('is_overweight')->default(false);
            $table->boolean('tarp_required')->default(false);
            $table->boolean('is_team')->default(false);
            $table->boolean('is_government')->default(false);
            $table->boolean('is_non_operational')->default(false);

            // temperature
            $table->boolean('is_temp_required')->default(false);
            $table->smallInteger('temperature_from')->nullable();
            $table->smallInteger('temperature_to')->nullable();

            // money
            $table->decimal('customer_rate', 10, 2)->nullable();
            $table->decimal('carrier_rate', 10, 2)->nullable();
            $table->decimal('lost_rate', 10, 2)->nullable();

            $table->decimal('company_profit', 10, 2)->nullable();
            $table->decimal('agent_profit', 10, 2)->nullable();
            $table->decimal('agent_commission_percent', 5, 2)->nullable();

            $table->timestamp('closed_at')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};