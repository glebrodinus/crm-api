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

            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_important')->default(false);

            $table->timestamps();

            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};