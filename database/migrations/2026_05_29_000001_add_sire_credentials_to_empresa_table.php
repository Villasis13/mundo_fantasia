<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('empresa_sire_client_id', 100)->nullable()->after('empresa_cert_vencimiento');
            $table->string('empresa_sire_client_secret', 100)->nullable()->after('empresa_sire_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['empresa_sire_client_id', 'empresa_sire_client_secret']);
        });
    }
};
