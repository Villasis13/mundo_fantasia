<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serie', function (Blueprint $table) {
            $table->id('id_serie');
            $table->foreignId('id_caja_numero')->nullable()
                ->constrained('caja_numero', 'id_caja_numero')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_empresa')->nullable()
                ->constrained('empresa', 'id_empresa');
            $table->char('tipocomp', 2);
            $table->string('serie', 20);
            $table->integer('correlativo');
            $table->tinyInteger('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serie');
    }
};
