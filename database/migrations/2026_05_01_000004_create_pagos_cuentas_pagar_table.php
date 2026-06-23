<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuentas_pagar', function (Blueprint $table) {
            $table->id('id_pago_cp');
            $table->foreignId('id_cuenta_pagar')->constrained('cuentas_pagar', 'id_cuenta_pagar')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_users')->constrained('users', 'id_users')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_tipo_pago')->constrained('tipo_pago', 'id_tipo_pago')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('pcp_monto', 10, 2);
            $table->date('pcp_fecha');
            $table->string('pcp_numero_operacion', 100)->nullable()
                ->comment('Número de operación para transferencia o QR');
            $table->string('pcp_voucher', 200)->nullable();
            $table->string('pcp_observacion', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_cuenta_pagar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cuentas_pagar');
    }
};
