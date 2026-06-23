<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubigeo', function (Blueprint $table) {
            $table->id('id_ubigeo');
            $table->string('ubigeo_cod', 10);
            $table->string('ubigeo_departamento', 100);
            $table->string('ubigeo_provincia', 100);
            $table->string('ubigeo_distrito', 100);
            $table->string('ubigeo_capital', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubigeo');
    }
};
