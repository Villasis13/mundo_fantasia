<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tienda', function (Blueprint $table) {
            $table->unsignedBigInteger('id_users');
            $table->unsignedBigInteger('id_tienda');
            $table->timestamps();
            $table->primary(['id_users', 'id_tienda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tienda');
    }
};
