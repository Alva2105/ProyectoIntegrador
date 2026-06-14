<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitudes_repuestos', function (Blueprint $table) {
            $table->increments('cod_solicitudesrep');
            $table->integer('can_sol');
            $table->date('fec_sol_rep')->default(DB::raw('CURRENT_DATE'));
            $table->unsignedInteger('cod_repuestos_sol');
            $table->string('cod_usuarios_sol', 10);

            $table->foreign('cod_repuestos_sol')->references('cod_repuestos')->on('repuestos');
            $table->foreign('cod_usuarios_sol')->references('cod_usuarios')->on('usuarios');
        });
    }

    public function down(): void { Schema::dropIfExists('solicitudes_repuestos'); }
};