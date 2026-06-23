<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('empresa')->truncate();
        DB::table('empresa')->insert([
            'id_empresa'             => 1,
            'empresa_razon_social'   => 'HHK SOLUTIONS E.I.R.L.',
            'empresa_nombrecomercial'=> "EMY'S PETS",
            'empresa_descripcion'    => "EMY'S PETS",
            'empresa_ruc'            => '20612115592',
            'empresa_domiciliofiscal'=> 'MZA. W LOTE. 1 A.F. LOS LEONES DE LA FRAGATA',
            'empresa_pais'           => 'PE',
            'empresa_telefono1'      => '902 675 298',
            'empresa_telefono2'      => '982 726 070',
            'empresa_foto'           => 'logo.png',
            'empresa_foto_ticket'    => 'logo.png',
            'empresa_correo'         => 'karenpamela@emyspets.com',
            'empresa_usuario_sol'    => 'MODDATOS',
            'empresa_clave_sol'      => 'MODDATOS',
            'empresa_estado'         => '1',
            'empresa_ruta_certificado'  => null,
            'empresa_clave_certificado' => null,
            'id_ubigeo'              => 1311,
            'created_at'             => null,
            'updated_at'             => now(),
        ]);

        DB::table('sucursals')->truncate();
        DB::table('sucursals')->insert([
            ['id_sucursal' => 1, 'id_empresa' => 1, 'sucursal_nombre' => "EMY'S PETS",        'sucursal_direccion' => null, 'sucursal_estado' => 1, 'created_at' => '2026-04-19 09:18:28', 'updated_at' => '2026-04-19 09:18:28'],
            ['id_sucursal' => 2, 'id_empresa' => 1, 'sucursal_nombre' => "EMY'S PETS IQUITOS", 'sucursal_direccion' => null, 'sucursal_estado' => 1, 'created_at' => '2026-04-19 22:08:28', 'updated_at' => '2026-04-19 22:08:28'],
        ]);

        DB::table('empresa_planes')->truncate();
        DB::table('empresa_planes')->insert([
            'id_empresa_plan' => 6,
            'id_empresa'      => 1,
            'id_plan'         => 1,
            'fecha_inicio'    => '2026-04-19',
            'fecha_fin'       => '2026-05-19',
            'estado'          => 1,
            'monto_pagado'    => 100.00,
            'observacion'     => null,
            'id_users'        => 1,
            'created_at'      => '2026-04-19 10:37:47',
            'updated_at'      => '2026-04-19 10:37:47',
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
