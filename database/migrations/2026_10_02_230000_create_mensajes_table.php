<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('de_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('para_id')->constrained('users')->cascadeOnDelete();
            $table->text('texto');
            $table->timestamp('leido_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['para_id', 'leido_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
