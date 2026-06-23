<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja', function (Blueprint $table) {
            $table->id('id_caja');
            $table->foreignId('id_caja_numero')->constrained('caja_numero', 'id_caja_numero');
            $table->date('caja_fecha');
            $table->unsignedBigInteger('id_users_apertura');
            $table->decimal('caja_apertura', 10, 2);
            $table->dateTime('caja_fecha_apertura');
            $table->unsignedBigInteger('id_users_cierre')->nullable();
            $table->decimal('caja_cierre', 10, 2)->nullable();
            $table->decimal('caja_cierre_dolar', 10, 2)->nullable();
            $table->dateTime('caja_fecha_cierre')->nullable();
            $table->tinyInteger('caja_estado')->nullable();
            $table->tinyInteger('caja_rendicion')->default(0);
            $table->timestamps();

            $table->foreign('id_users_apertura', 'fk_caja_users_apertura')
                ->references('id_users')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('id_users_cierre', 'fk_caja_users_cierre')
                ->references('id_users')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja');
    }
};
