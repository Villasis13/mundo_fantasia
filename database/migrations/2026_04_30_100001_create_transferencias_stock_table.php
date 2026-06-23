<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_stock', function (Blueprint $table) {
            $table->id('id_transferencia');
            $table->string('transferencia_numero', 30)->unique();
            $table->unsignedBigInteger('id_sucursal_origen');
            $table->unsignedBigInteger('id_sucursal_destino');
            $table->unsignedBigInteger('id_users');
            $table->date('transferencia_fecha');
            $table->enum('transferencia_estado', ['pendiente', 'en_transito', 'recibido', 'anulado'])->default('pendiente');
            $table->text('transferencia_motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_stock');
    }
};
