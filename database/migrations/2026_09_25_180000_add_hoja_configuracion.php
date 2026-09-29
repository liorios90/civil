<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->string('tipo_hoja')->default('valores');
        });

        Schema::table('medicion_lineas', function (Blueprint $table) {
            $table->decimal('longitud', 16, 4)->nullable()->after('numero');
            $table->decimal('area', 16, 4)->nullable()->after('longitud');
            $table->decimal('volumen', 16, 4)->nullable()->after('area');
        });

        Schema::create('anexo_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anexo_id')->constrained()->cascadeOnDelete();
            $table->string('ruta');
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexo_imagenes');
        Schema::table('medicion_lineas', function (Blueprint $table) {
            $table->dropColumn(['longitud', 'area', 'volumen']);
        });
        Schema::table('rubros', function (Blueprint $table) {
            $table->dropColumn('tipo_hoja');
        });
    }
};
