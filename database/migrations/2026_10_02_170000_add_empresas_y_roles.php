<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('responsable');
            $table->date('fecha_inicio');
            $table->boolean('activo')->default(true);
            $table->text('telefonos')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('usuario')->after('password');
            $table->foreignId('empresa_id')->nullable()->after('rol')->constrained()->nullOnDelete();
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (DB::table('contratos')->whereNull('empresa_id')->exists()) {
            $empresaId = DB::table('empresas')->insertGetId([
                'nombre' => 'Empresa inicial',
                'responsable' => 'Por completar',
                'fecha_inicio' => now()->toDateString(),
                'activo' => true,
                'telefonos' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('contratos')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
            DB::table('users')->insert([
                'name' => 'Administrador',
                'email' => 'admin@ifrantech.net',
                'password' => Hash::make('Admin-2026'),
                'rol' => 'administrador',
                'empresa_id' => $empresaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('users')->where('email', 'sistemas@ifrantech.net')->exists()) {
            DB::table('users')->insert([
                'name' => 'Sistemas',
                'email' => 'sistemas@ifrantech.net',
                'password' => Hash::make('Sistemas-2026'),
                'rol' => 'sistemas',
                'empresa_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn('rol');
        });
        Schema::dropIfExists('empresas');
    }
};
