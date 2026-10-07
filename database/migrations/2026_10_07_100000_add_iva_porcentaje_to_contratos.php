<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->decimal('iva_porcentaje', 5, 2)->default(15)->after('monto_contrato_iva');
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            update contratos
            set iva_porcentaje = coalesce((
                select planillas.iva_porcentaje
                from planillas
                where planillas.contrato_id = contratos.id
                order by planillas.id
                limit 1
            ), 15)
        ');
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn('iva_porcentaje');
        });
    }
};
