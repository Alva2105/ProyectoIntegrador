<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->string('cod_seg', 10)->primary();
            $table->unsignedInteger('cod_solicitudes_seg');   // cambiado a unsignedInteger
            $table->string('cod_usuarios_seg');
            $table->timestamp('fcs_seg')->useCurrent();
            $table->string('tit_seg', 100)->nullable();
            $table->text('obs_seg')->nullable();
            $table->timestamps();

            $table->foreign('cod_solicitudes_seg')
                  ->references('cod_solicitudes')
                  ->on('solicitudes')
                  ->onDelete('cascade');

            $table->foreign('cod_usuarios_seg')
                  ->references('cod_usuarios')
                  ->on('usuarios')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};