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

            // status
            $table->enum('status', [
                'requested',
                'quoted',
                'booked',
                'lost',
                'cancelled',
            ])->default('requested');

            // origin/destination snapshots (synced from stops)
            $table->string('origin_city')->nullable();
            $table->string('origin_state', 2)->nullable();
            $table->string('origin_zip', 10)->nullable();

            $table->string('destination_city')->nullable();
            $table->string('destination_state', 2)->nullable();
            $table->string('destination_zip', 10)->nullable();

            // load info
            $table->string('commodity')->nullable();
            $table->integer('weight_lbs')->nullable();

            // planned date windows (synced from stops)
            $table->date('pickup_date_from')->nullable();
            $table->date('pickup_date_to')->nullable();

            $table->date('delivery_date_from')->nullable();
            $table->date('delivery_date_to')->nullable();

            // trip duration hint
            $table->unsignedSmallInteger('trip_days')->nullable();

            // actual execution
            $table->timestamp('actual_pickup_at')->nullable();
            $table->timestamp('actual_delivery_at')->nullable();

            // distance
            $table->unsignedInteger('distance_miles')->nullable();

            // flags
            $table->boolean('is_partial')->default(false);
            $table->boolean('is_non_divisible')->default(false);
            $table->boolean('is_oversize')->default(false);
            $table->boolean('is_overweight')->default(false);
            $table->boolean('is_tarp_required')->default(false);
            $table->boolean('is_team')->default(false);
            $table->boolean('is_government')->default(false);
            $table->boolean('is_non_operational')->default(false);

            // temperature
            $table->boolean('is_temp_required')->default(false);
            $table->smallInteger('temperature_from')->nullable();
            $table->smallInteger('temperature_to')->nullable();

            // money (final snapshot)
            $table->decimal('customer_rate', 10, 2)->nullable();
            $table->decimal('carrier_rate', 10, 2)->nullable();

            // suggested carrier rate snapshot
            $table->decimal('suggested_carrier_rate', 10, 2)->nullable();

            // lost intel
            $table->decimal('lost_rate', 10, 2)->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('lost_at')->nullable();

            // RPM snapshots (stored in deals)
            $table->decimal('customer_rpm', 8, 3)->nullable();
            $table->decimal('carrier_rpm', 8, 3)->nullable();
            $table->decimal('suggested_carrier_rpm', 8, 3)->nullable();

            // profit
            $table->decimal('company_profit', 10, 2)->nullable();
            $table->decimal('agent_profit', 10, 2)->nullable();
            $table->decimal('agent_commission_percent', 5, 2)->nullable();

            // acceptance recorded by your agent
            $table->timestamp('customer_accepted_at')->nullable();
            $table->foreignId('customer_accepted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('customer_accepted_method', 20)->nullable(); // email, sms, verbal

            // closure
            $table->timestamp('closed_at')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
            $table->index('account_id');

            $table->index('pickup_date_from');
            $table->index('pickup_date_to');
            $table->index('delivery_date_from');
            $table->index('delivery_date_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};