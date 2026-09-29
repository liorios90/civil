<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frentes', function (Blueprint $table) {
            $table->boolean('es_catalogo')->default(false)->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('frentes', function (Blueprint $table) {
            $table->dropColumn('es_catalogo');
        });
    }
};
