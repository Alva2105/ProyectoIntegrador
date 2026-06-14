<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->increments('cod_asignaciones');
            $table->date('fec_asi')->default(DB::raw('CURRENT_DATE'));
            $table->text('obs_asi')->nullable();
            $table->string('cod_solicitudes_asi', 10);
            $table->string('cod_usuarios_asi', 10);

            $table->unique(['cod_solicitudes_asi', 'cod_usuarios_asi']);
            $table->foreign('cod_solicitudes_asi')->references('cod_solicitudes')->on('solicitudes');
            $table->foreign('cod_usuarios_asi')->references('cod_usuarios')->on('usuarios');
        });
    }

    public function down(): void { Schema::dropIfExists('asignaciones'); }
};