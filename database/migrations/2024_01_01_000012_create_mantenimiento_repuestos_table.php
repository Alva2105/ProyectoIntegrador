<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mantenimiento_repuestos', function (Blueprint $table) {
            $table->unsignedInteger('cod_mantenimientos');
            $table->unsignedInteger('cod_repuestos');
            $table->integer('cantidad');
            $table->primary(['cod_mantenimientos', 'cod_repuestos']);
            $table->foreign('cod_mantenimientos')->references('cod_mantenimientos')->on('mantenimientos')->onDelete('cascade');
            $table->foreign('cod_repuestos')->references('cod_repuestos')->on('repuestos')->onDelete('cascade');
        });
    }

    public function down(): void { Schema::dropIfExists('mantenimiento_repuestos'); }
};