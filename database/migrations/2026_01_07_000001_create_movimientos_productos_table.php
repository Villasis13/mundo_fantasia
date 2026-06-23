<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_productos', function (Blueprint $table) {
            $table->id('id_movimientos_productos');
            $table->date('movimientos_productos_fecha');
            $table->foreignId('id_users')->constrained('users', 'id_users');
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->dateTime('movimientos_productos_fecha_creacion');
            $table->tinyInteger('movimientos_productos_tipo')->comment('1 ingreso , 2 salida');
            $table->tinyInteger('movimientos_productos_estado');
            $table->string('movimientos_productos_motivo', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_productos');
    }
};
