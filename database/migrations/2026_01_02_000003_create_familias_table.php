<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias', function (Blueprint $table) {
            $table->id('id_fa');
            $table->foreignId('id_empresa')->nullable()->constrained('empresa', 'id_empresa')->nullOnDelete();
            $table->string('fa_nombre');
            $table->tinyInteger('fa_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familias');
    }
};
