<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->json('columnas')->nullable()->after('hoja');
        });

        Schema::table('medicion_lineas', function (Blueprint $table) {
            $table->json('celdas')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('medicion_lineas', function (Blueprint $table) {
            $table->dropColumn('celdas');
        });
        Schema::table('anexos', function (Blueprint $table) {
            $table->dropColumn('columnas');
        });
    }
};
