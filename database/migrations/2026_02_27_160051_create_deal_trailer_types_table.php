<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_trailer_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            // Allowed values: RGN, F, SD, HS, R, V, CN
            $table->enum('type', ['RGN','F','SD','HS','R','V','CN']);

            $table->timestamps();

            $table->unique(['deal_id', 'type']);
            $table->index(['type', 'deal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_trailer_types');
    }
};