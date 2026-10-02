<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tablas = ['frentes', 'rubros', 'planillas', 'planilla_rubros', 'medicion_lineas', 'anexo_imagenes'];

    public function up(): void
    {
        foreach ($this->tablas as $nombre) {
            Schema::table($nombre, function (Blueprint $table) {
                $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('modificado_por')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('frente_id')->nullable()->index();
            $table->unsignedBigInteger('planilla_rubro_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('usuario_nombre')->nullable();
            $table->string('modelo', 40);
            $table->unsignedBigInteger('modelo_id');
            $table->string('accion', 20);
            $table->string('descripcion', 500);
            $table->json('cambios')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['contrato_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
        foreach ($this->tablas as $nombre) {
            Schema::table($nombre, function (Blueprint $table) {
                $table->dropConstrainedForeignId('creado_por');
                $table->dropConstrainedForeignId('modificado_por');
            });
        }
    }
};
