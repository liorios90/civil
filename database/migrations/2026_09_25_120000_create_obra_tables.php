<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('entidad');
            $table->string('numero_contrato')->nullable();
            $table->string('codigo_proceso');
            $table->text('objeto');
            $table->date('fecha_suscripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('provincia')->nullable();
            $table->string('contratista');
            $table->string('fiscalizador')->nullable();
            $table->string('administrador')->nullable();
            $table->string('plazo')->nullable();
            $table->decimal('monto_contrato', 14, 2);
            $table->decimal('monto_contrato_iva', 14, 2)->nullable();
            $table->decimal('porcentaje_anticipo', 8, 4)->default(0);
            $table->decimal('anticipo', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('frentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('nombre');
            $table->unsignedInteger('orden');
            $table->timestamps();
        });

        Schema::create('rubros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frente_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('codigo')->nullable();
            $table->text('descripcion');
            $table->string('unidad', 20);
            $table->decimal('cantidad_contratada', 16, 4);
            $table->decimal('precio_unitario', 16, 4);
            $table->timestamps();

            $table->unique(['frente_id', 'numero']);
        });

        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained()->cascadeOnDelete();
            $table->string('numero');
            $table->date('periodo_desde')->nullable();
            $table->date('periodo_hasta')->nullable();
            $table->string('estado')->default('borrador');
            $table->decimal('iva_porcentaje', 5, 2)->default(12);
            $table->decimal('descuentos', 14, 2)->default(0);
            $table->decimal('multas', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('planilla_rubros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubro_id')->constrained()->cascadeOnDelete();
            $table->decimal('cantidad_anterior', 16, 4)->default(0);
            $table->decimal('cantidad_actual', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(['planilla_id', 'rubro_id']);
        });

        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_rubro_id')->constrained()->cascadeOnDelete();
            $table->string('tipo')->default('geometrico');
            $table->unsignedInteger('hoja')->default(1);
            $table->timestamps();
        });

        Schema::create('medicion_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anexo_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(1);
            $table->string('descripcion')->nullable();
            $table->decimal('base1', 16, 4)->nullable();
            $table->decimal('base2', 16, 4)->nullable();
            $table->decimal('altura', 16, 4)->nullable();
            $table->decimal('numero', 16, 4)->nullable();
            $table->decimal('total', 16, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicion_lineas');
        Schema::dropIfExists('anexos');
        Schema::dropIfExists('planilla_rubros');
        Schema::dropIfExists('planillas');
        Schema::dropIfExists('rubros');
        Schema::dropIfExists('frentes');
        Schema::dropIfExists('contratos');
    }
};
