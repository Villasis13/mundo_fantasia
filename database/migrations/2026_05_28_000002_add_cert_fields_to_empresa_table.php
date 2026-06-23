<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('empresa_cert_pfx', 500)->nullable()->after('empresa_clave_certificado');
            $table->string('empresa_cert_password', 255)->nullable()->after('empresa_cert_pfx');
            $table->date('empresa_cert_vencimiento')->nullable()->after('empresa_cert_password');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['empresa_cert_pfx', 'empresa_cert_password', 'empresa_cert_vencimiento']);
        });
    }
};
