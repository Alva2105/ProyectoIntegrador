<?php
 
// =====================================================================
// INSTRUCCIONES DE USO:
// Coloca cada clase en un archivo separado dentro de database/migrations/
// con el nombre: YYYY_MM_DD_HHMMSS_nombre.php
// El orden de los archivos importa (por las FKs).
// =====================================================================
 
// ─────────────────────────────────────────────────────────────────────
// 2024_01_01_000001_create_roles_table.php
// ─────────────────────────────────────────────────────────────────────
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('cod_usuarios', 10)->primary();
            $table->string('nom_usu', 100);
            $table->string('app_usu', 100);
            $table->string('apm_usu', 100)->nullable();
            $table->string('email_usu', 50)->unique();
            $table->text('pas_usu');
            $table->text('img_usu')->nullable();
            $table->unsignedInteger('cod_roles_usu');
 
            $table->foreign('cod_roles_usu')->references('cod_roles')->on('roles');
        });
    }
 
    public function down(): void { Schema::dropIfExists('usuarios'); }
};