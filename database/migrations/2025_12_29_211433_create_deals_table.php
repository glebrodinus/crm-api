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

            // audit
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // status (clean CRM flow)
            $table->enum('status', [
                'requested',
                'quoted',
                'booked',
                'lost',
            ])->default('requested');

            // lane
            $table->string('origin_city')->nullable();
            $table->string('origin_state', 2)->nullable();
            $table->string('origin_zip', 10)->nullable();

            $table->string('destination_city')->nullable();
            $table->string('destination_state', 2)->nullable();
            $table->string('destination_zip', 10)->nullable();

            $table->string('commodity')->nullable();
            $table->integer('weight_lbs')->nullable();

            // dates
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();

            // multiple trailer types
            $table->json('trailer_types')->nullable(); // ["RGN","SD"]

            // flags
            $table->boolean('is_oversize')->default(false);
            $table->boolean('is_overweight')->default(false);
            $table->boolean('needs_tarp')->default(false);
            $table->boolean('is_team')->default(false);
            $table->boolean('is_government')->default(false);
            $table->boolean('is_non_operational')->default(false);

            // money
            $table->decimal('customer_rate', 10, 2)->nullable();     // revenue
            $table->decimal('carrier_rate', 10, 2)->nullable();      // expense
            $table->decimal('lost_rate', 10, 2)->nullable(); // competitor rate if lost

            $table->decimal('company_profit', 10, 2)->nullable();    // optional snapshot
            $table->decimal('agent_profit', 10, 2)->nullable();      // optional snapshot
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