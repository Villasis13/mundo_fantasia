<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_documento', function (Blueprint $table) {
            $table->id('id_tipo_documento');
            $table->string('tipodocumento_codigo', 10);
            $table->string('tipo_documento_identidad');
            $table->string('tipo_documento_identidad_abr');
            $table->tinyInteger('tipo_documento_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_documento');
    }
};
