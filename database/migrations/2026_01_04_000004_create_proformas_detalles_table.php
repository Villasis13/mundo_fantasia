<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proformas_detalles', function (Blueprint $table) {
            $table->id('id_profo_deta');
            $table->foreignId('id_profo')->constrained('proformas', 'id_profo');
            $table->foreignId('id_pro')->constrained('productos', 'id_pro');
            $table->decimal('profo_deta_precio', 10, 2);
            $table->integer('profo_deta_cantidad');
            $table->string('profo_deta_observacion', 500)->nullable();
            $table->tinyInteger('profo_deta_estado');
            $table->tinyInteger('profo_deta_usado')->nullable()
                ->comment('0 cunado aun no generan un venta con esto 1 cuando si');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proformas_detalles');
    }
};
