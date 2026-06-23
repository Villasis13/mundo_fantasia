<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_users')->constrained('users', 'id_users')->cascadeOnDelete();
            $table->foreignId('id_sucursal')->constrained('sucursals', 'id_sucursal')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_users', 'id_sucursal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sucursal');
    }
};
