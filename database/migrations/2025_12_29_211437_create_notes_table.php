<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            // noteable_type + noteable_id
            $table->morphs('noteable');

            $table->enum('type', ['note', 'link'])->default('note');

            $table->text('content')->nullable();
            $table->text('url')->nullable();
            $table->string('url_label')->nullable();

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_important')->default(false);

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('created_by_user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};