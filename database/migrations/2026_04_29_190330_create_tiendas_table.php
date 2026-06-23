<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tiendas', function (Blueprint $table) {
            $table->bigIncrements('id_tienda');
            $table->unsignedBigInteger('id_empresa');
            $table->string('tienda_nombre', 255);
            $table->tinyInteger('tienda_principal')->default(0);
            $table->string('tienda_microtime', 255)->nullable();
            $table->tinyInteger('tienda_estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiendas');
    }
};
