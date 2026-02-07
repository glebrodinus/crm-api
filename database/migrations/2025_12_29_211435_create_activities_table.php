<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('type', ['call', 'email', 'text', 'meeting']);

            $table->enum('outcome', [
                'attempted',
                'connected',
                'sent',
                'failed',
            ])->nullable();

            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');

            $table->boolean('voicemail_left')->default(false);
            $table->string('note')->nullable();

            $table->string('contact_phone')->nullable();
            $table->string('contact_phone_extension')->nullable();
            $table->string('contact_email')->nullable();

            $table->timestampTz('occurred_at')->useCurrent(); // store UTC

            $table->timestamps();

            $table->index(['account_id', 'occurred_at']);
            $table->index(['deal_id', 'occurred_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};