<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_stops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            // Order of stops within a deal (1..N)
            $table->unsignedSmallInteger('sequence');

            // pick / drop / stop
            $table->enum('type', ['pick', 'drop', 'stop']);

            // location (keep nullable for now; UI can require them)
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();

            // optional for quoting (not execution)
            $table->date('date')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->unique(['deal_id', 'sequence']);
            $table->index(['deal_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_stops');
    }
};