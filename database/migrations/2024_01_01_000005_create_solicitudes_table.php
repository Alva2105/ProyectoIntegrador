<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->string('cod_solicitudes', 10)->primary();
            $table->date('fec_sol')->default(DB::raw('CURRENT_DATE'));
            $table->text('obs_sol')->nullable();
            $table->string('tma_sol', 20);
            $table->string('est_sol', 20)->default('PENDIENTE');
            $table->unsignedInteger('cod_clientes_sol');
            $table->integer('cod_vehiculos_sol');

            $table->foreign('cod_clientes_sol')->references('cod_clientes')->on('clientes');
            $table->foreign(['cod_vehiculos_sol', 'cod_clientes_sol'])
                  ->references(['cod_vehiculos', 'cod_clientes_veh'])
                  ->on('vehiculos');
        });
    }

    public function down(): void { Schema::dropIfExists('solicitudes'); }
};