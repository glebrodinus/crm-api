<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('deal_id')
                ->nullable()
                ->constrained('deals')
                ->nullOnDelete();

            // Audit + assignment
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('assigned_to_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Task data
            $table->enum('type', [
                'call',
                'quote',
                'follow_up',
                'email',
                'meeting',
                'update',
                'invoice',
                'payment',
                'claim',
            ]);

            $table->string('title')->nullable();
            $table->tinyInteger('priority')->default(1); // 1 (low) to 4 (high)

            $table->text('note')->nullable();

            $table->timestamp('due_at');

            // Completion
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['assigned_to_user_id', 'due_at']);
            $table->index(['deal_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};