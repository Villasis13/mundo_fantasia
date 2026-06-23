<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_planes', function (Blueprint $table) {
            $table->id('id_empresa_plan');
            $table->foreignId('id_empresa')->constrained('empresa', 'id_empresa')->cascadeOnDelete();
            $table->foreignId('id_plan')->constrained('planes', 'id_plan')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->comment('Fecha hasta la cual el plan es válido');
            $table->tinyInteger('estado')->default(1)->comment('1=activo, 0=vencido/cancelado');
            $table->decimal('monto_pagado', 10, 2)->default(0.00)->comment('Monto real pagado por este plan');
            $table->string('observacion')->nullable()->comment('Notas de la asignación o renovación');
            $table->unsignedBigInteger('id_users')->nullable()->comment('Usuario que asignó el plan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_planes');
    }
};
