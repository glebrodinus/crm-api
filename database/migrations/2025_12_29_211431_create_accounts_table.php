<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

        // Communication tracking
        $table->timestamp('last_contacted_at')->nullable();
        $table->timestamp('last_attempted_at')->nullable();
        $table->timestamp('last_deal_at')->nullable();

        // Follow-up
        $table->timestamp('follow_up_at')->nullable();

        $table->enum('follow_up_type', [
            'call',
            'email',
            'text',
            'meeting'
        ])->nullable();

        $table->unsignedBigInteger('follow_up_contact_id')->nullable();

        $table->string('follow_up_note')->nullable();

            // Basic info
            $table->string('name');
            $table->string('dba_name')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();

            $table->string('address')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('country', 3)->nullable()->default('USA');
            $table->enum('timezone', ['PST', 'MST', 'CST', 'EST'])->nullable();
            $table->string('phone')->nullable();

            // Relationship status (business relationship only)
            $table->enum('status', [
                'lead',     // prospect (no booked deal yet)
                'active',   // booked at least 1 deal
                'inactive', // previously active, but not shipping recently
            ])->default('lead');

            $table->timestamp('unreachable_at')->nullable();
            $table->string('unreachable_reason')->nullable();
            $table->foreignId('unreachable_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Qualification system (3-state via timestamps)
            $table->timestamp('qualified_at')->nullable();
            $table->foreignId('qualified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('qualified_reason')->nullable();

            $table->timestamp('disqualified_at')->nullable();
            $table->foreignId('disqualified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('disqualified_reason')->nullable();

            $table->string('note')->nullable();


            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['created_by_user_id', 'status']);

            $table->index('last_contacted_at');
            $table->index('last_attempted_at');
            $table->index('last_deal_at');

            $table->index('qualified_at');
            $table->index('disqualified_at');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};