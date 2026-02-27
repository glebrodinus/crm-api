<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            // Team ownership
            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            // User ownership / audit
            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('last_contacted_at')->nullable();

            // Basic info
            $table->string('name');
            $table->string('website')->nullable();

            $table->string('address')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('country', 3)->nullable()->default('USA');
            $table->string('phone')->nullable();

            // Relationship status
            $table->enum('status', [
                'lead',        // new prospect
                'active',      // working / shipping
                'inactive',    // no recent activity
                'unreachable'  // many attempts, no contact
            ])->default('lead');

            // Qualification system (3-state via timestamps)
            $table->timestamp('qualified_at')->nullable();
            $table->foreignId('qualified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('disqualified_at')->nullable();
            $table->foreignId('disqualified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('disqualified_reason')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'status']);
            $table->index(['owner_user_id', 'status']);
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