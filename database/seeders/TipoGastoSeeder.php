<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoGastoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('tipo_gasto')->truncate();

        DB::table('tipo_gasto')->insert([
            ['tipo_gasto_nombre' => 'CAJA CHICA',                    'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'DEPOSITO EN CUENTA',            'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'TRANSFERENCIA DE FONDOS',       'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'TARJ. DE DEBITO',               'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'TARJ. DE CREDITO',              'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'EFECTIVO',                      'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'PAGO A PERSONAL',               'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'GASTOS SIN SUSTENTO',           'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'GASTOS DE LA EMPRESA',          'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'BOVEDA',                        'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'DONACION',                      'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'ANTICIPOS RECIBIDOS',           'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'COMPRAS',                       'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'CANCELACION EN EFECTIVO',       'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'CANCELACION A DEPOSITO EN CTA.','tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'INGRESO EFECTIVO A CAJA',       'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'EGRESO DE EFECTIVO',            'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'DEVOLUCION DE PRESTAMO',        'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'PAGO SERVICIO TECNICO',         'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'PAGO EN EFEC. FACTURAS',        'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'AMORTIZACION DE FACTURAS',      'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'NOTA DE CREDITO / BOLETA',      'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'NOTA DE CREDITO / FACTURA',     'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'EFECTIVO SOBRANTE',             'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'DEVOLUCION DE ANTICIPOS EFECT.','tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'PRESTAMO AL PERSONAL',          'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tipo_gasto_nombre' => 'PAGO DE CLIENTES',              'tipo_gasto_estado' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
