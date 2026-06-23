<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id('id_pro');
            $table->foreignId('id_empresa')->nullable()->constrained('empresa', 'id_empresa')->nullOnDelete();
            $table->foreignId('id_ca')->constrained('categorias', 'id_ca');
            $table->foreignId('id_medida')->constrained('medida', 'id_medida');
            $table->string('pro_nombre');
            $table->string('pro_codigo');
            $table->string('pro_descripcion')->nullable();
            $table->string('pro_foto')->nullable();
            $table->tinyInteger('pro_estado')->nullable();
            $table->tinyInteger('impuesto_bolsa')->nullable()->comment('0 no es bolsa 1 es bolsa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
