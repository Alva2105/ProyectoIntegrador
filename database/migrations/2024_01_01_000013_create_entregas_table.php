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
        Schema::create('entregas', function (Blueprint $table) {
            $table->increments('cod_entregas');
            $table->date('fec_ent')->default(DB::raw('CURRENT_DATE'));
            $table->text('obs_ent')->nullable();
            $table->unsignedInteger('cod_mantenimientos_ent')->unique();
            $table->onDelete('cascade');
            $table->foreign('cod_mantenimientos_ent')->references('cod_mantenimientos')->on('mantenimientos');
        });
    }
 
    public function down(): void { Schema::dropIfExists('entregas'); }
};