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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->increments('cod_solicitudes');
            $table->date('fec_sol')->default(DB::raw('CURRENT_DATE'));
            $table->text('obs_sol')->nullable();
            $table->string('tma_sol', 20);     // PREVENTIVO | CORRECTIVO
            $table->string('est_sol', 20)->default('PENDIENTE');
            $table->unsignedInteger('cod_clientes_sol');
            $table->integer('cod_vehiculos_sol');
 
            $table->foreign('cod_clientes_sol')->references('cod_clientes')->on('clientes');
            // FK compuesta a vehiculos
            $table->foreign(['cod_vehiculos_sol', 'cod_clientes_sol'])
                  ->references(['cod_vehiculos', 'cod_clientes_veh'])
                  ->on('vehiculos');
        });
    }
 
    public function down(): void { Schema::dropIfExists('solicitudes'); }
};