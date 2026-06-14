<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->increments('cod_notificaciones');
            $table->string('tip_not', 30);
            $table->text('men_not');
            $table->timestamp('fec_not')->useCurrent();
            $table->boolean('lei_not')->default(false);
            $table->string('cod_usuarios_not', 10)->nullable();
            $table->unsignedInteger('cod_clientes_not')->nullable();

            $table->foreign('cod_usuarios_not')->references('cod_usuarios')->on('usuarios');
            $table->foreign('cod_clientes_not')->references('cod_clientes')->on('clientes');
        });
    }

    public function down(): void { Schema::dropIfExists('notificaciones'); }
};