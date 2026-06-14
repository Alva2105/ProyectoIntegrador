<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_vehiculo', function (Blueprint $table) {
            $table->increments('cod_historial');
            $table->date('fec_his')->default(DB::raw('CURRENT_DATE'));
            $table->text('des_his')->nullable();
            $table->integer('cod_vehiculos_his');
            $table->unsignedInteger('cod_clientes_his');
            $table->unsignedInteger('cod_mantenimientos_his');
            $table->foreign(['cod_vehiculos_his', 'cod_clientes_his'])
                  ->references(['cod_vehiculos', 'cod_clientes_veh'])
                  ->on('vehiculos')
                  ->onDelete('cascade');
            $table->foreign('cod_mantenimientos_his')
                  ->references('cod_mantenimientos')
                  ->on('mantenimientos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void { Schema::dropIfExists('historial_vehiculo'); }
};