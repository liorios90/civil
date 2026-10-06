<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_rubros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->text('descripcion');
            $table->string('unidad', 20)->default('u');
            $table->decimal('cantidad_contratada', 16, 4)->default(0);
            $table->decimal('precio_unitario', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_rubros');
    }
};
