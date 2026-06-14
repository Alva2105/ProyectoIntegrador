<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->increments('cod_diagnosticos');
            $table->timestamp('fec_dia')->useCurrent();
            $table->text('des_dia');
            $table->string('cod_solicitudes_dia', 10)->unique();

            $table->foreign('cod_solicitudes_dia')->references('cod_solicitudes')->on('solicitudes');
        });
    }

    public function down(): void { Schema::dropIfExists('diagnosticos'); }
};