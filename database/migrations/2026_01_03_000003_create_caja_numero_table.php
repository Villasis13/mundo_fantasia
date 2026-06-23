<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_numero', function (Blueprint $table) {
            $table->id('id_caja_numero');
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->string('caja_numero_nombre');
            $table->string('caja_numero_impresora');
            $table->string('caja_numero_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_numero');
    }
};
