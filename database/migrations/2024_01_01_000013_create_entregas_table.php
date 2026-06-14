<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->increments('cod_entregas');
            $table->date('fec_ent')->default(DB::raw('CURRENT_DATE'));
            $table->text('obs_ent')->nullable();
            $table->unsignedInteger('cod_mantenimientos_ent')->unique();
            $table->foreign('cod_mantenimientos_ent')->references('cod_mantenimientos')->on('mantenimientos')->onDelete('cascade');
        });
    }

    public function down(): void { Schema::dropIfExists('entregas'); }
};