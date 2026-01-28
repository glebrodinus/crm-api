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

            // Account info
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
            $table->enum('status', ['lead', 'active', 'inactive'])->default('lead');

            // Qualification
            $table->boolean('is_qualified')->default(false);
            $table->timestamp('qualified_at')->nullable();
            $table->foreignId('qualified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Do-not-contact / blocked
            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('blocked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
                
            $table->string('note')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'status']);
            $table->index(['owner_user_id', 'status']);
            $table->index('is_blocked');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};