<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CajaSerieSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('serie')->truncate();
        DB::table('caja_numero')->truncate();

        DB::table('caja_numero')->insert([
            ['id_caja_numero' => 5, 'id_sucursal' => 1, 'caja_numero_nombre' => 'Caja Principal', 'caja_numero_impresora' => 'ticketera',  'caja_numero_estado' => '1', 'created_at' => '2026-04-19 09:21:33', 'updated_at' => '2026-04-19 09:21:33'],
            ['id_caja_numero' => 6, 'id_sucursal' => 2, 'caja_numero_nombre' => 'CAJA 1',         'caja_numero_impresora' => 'ticketera1', 'caja_numero_estado' => '1', 'created_at' => '2026-04-19 22:08:42', 'updated_at' => '2026-04-19 22:08:42'],
        ]);

        DB::table('serie')->insert([
            ['id_serie' => 29, 'id_caja_numero' => 5,    'id_empresa' => null, 'tipocomp' => '01', 'serie' => 'F001',     'correlativo' => 6, 'estado' => 1, 'created_at' => '2026-04-19 09:21:33', 'updated_at' => '2026-04-23 10:32:24'],
            ['id_serie' => 30, 'id_caja_numero' => 5,    'id_empresa' => null, 'tipocomp' => '03', 'serie' => 'B001',     'correlativo' => 6, 'estado' => 1, 'created_at' => '2026-04-19 09:21:33', 'updated_at' => '2026-04-22 19:40:16'],
            ['id_serie' => 31, 'id_caja_numero' => 5,    'id_empresa' => null, 'tipocomp' => '20', 'serie' => 'NV001',    'correlativo' => 0, 'estado' => 1, 'created_at' => '2026-04-19 09:21:33', 'updated_at' => '2026-04-21 22:20:43'],
            ['id_serie' => 32, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => '07', 'serie' => 'FN01',     'correlativo' => 1, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-22 23:25:14'],
            ['id_serie' => 33, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => '07', 'serie' => 'BN01',     'correlativo' => 2, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-22 23:26:31'],
            ['id_serie' => 34, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => '08', 'serie' => 'FD01',     'correlativo' => 0, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-19 09:24:08'],
            ['id_serie' => 35, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => '08', 'serie' => 'BD01',     'correlativo' => 1, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-22 23:27:02'],
            ['id_serie' => 36, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => 'RC', 'serie' => '20260422', 'correlativo' => 6, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-22 23:27:12'],
            ['id_serie' => 37, 'id_caja_numero' => null, 'id_empresa' => 1,    'tipocomp' => 'RA', 'serie' => '20260423', 'correlativo' => 1, 'estado' => 1, 'created_at' => '2026-04-19 09:24:08', 'updated_at' => '2026-04-23 08:32:55'],
            ['id_serie' => 38, 'id_caja_numero' => 6,    'id_empresa' => null, 'tipocomp' => '01', 'serie' => 'F002',     'correlativo' => 0, 'estado' => 1, 'created_at' => '2026-04-19 22:08:42', 'updated_at' => '2026-04-22 10:39:42'],
            ['id_serie' => 39, 'id_caja_numero' => 6,    'id_empresa' => null, 'tipocomp' => '03', 'serie' => 'B002',     'correlativo' => 0, 'estado' => 1, 'created_at' => '2026-04-19 22:08:42', 'updated_at' => '2026-04-22 09:29:19'],
            ['id_serie' => 40, 'id_caja_numero' => 6,    'id_empresa' => null, 'tipocomp' => '20', 'serie' => 'NV002',    'correlativo' => 0, 'estado' => 1, 'created_at' => '2026-04-19 22:08:42', 'updated_at' => '2026-04-19 22:08:42'],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
