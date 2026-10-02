<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropForeign(['contrato_id']);
            $table->foreign('contrato_id')->references('id')->on('contratos')->nullOnDelete();
            $table->index(['empresa_id', 'created_at']);
        });

        DB::table('historial_cambios')
            ->whereNull('empresa_id')
            ->update([
                'empresa_id' => DB::raw('(select contratos.empresa_id from contratos where contratos.id = historial_cambios.contrato_id)'),
            ]);

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modificado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
            $table->dropConstrainedForeignId('modificado_por');
        });

        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'created_at']);
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropForeign(['contrato_id']);
            $table->foreign('contrato_id')->references('id')->on('contratos')->cascadeOnDelete();
        });
    }
};
