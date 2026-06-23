<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id('id_plan');
            $table->string('plan_nombre', 100);
            $table->string('plan_descripcion')->nullable();
            $table->decimal('plan_precio', 10, 2)->default(0.00);
            $table->unsignedInteger('plan_duracion_dias')->comment('Duración del plan en días');
            $table->tinyInteger('plan_estado')->default(1)->comment('1=activo, 0=inactivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
