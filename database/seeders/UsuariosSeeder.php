<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('user_sucursal')->truncate();
        DB::table('users')->truncate();
        DB::table('persona')->truncate();

        DB::table('persona')->insert([
            [
                'id_persona'              => 1,
                'id_empresa'              => 1,
                'persona_nombre'          => 'Eder Alfredo',
                'persona_apellido_paterno'=> 'Apagueño',
                'persona_apellido_materno'=> 'Reyna',
                'persona_email'           => 'ederalfredo@bufeotec.com',
                'persona_tipo_documento'  => '2',
                'persona_dni'             => '74077975',
                'persona_telefono'        => '956449198',
                'persona_nacimiento'      => '2004-02-21',
                'persona_blacklist'       => null,
                'persona_empleado'        => null,
                'person_codigo'           => '5465468498484948das',
                'persona_estado'          => 1,
                'created_at'              => null,
                'updated_at'              => null,
            ],
            [
                'id_persona'              => 10,
                'id_empresa'              => 1,
                'persona_nombre'          => 'Katerine',
                'persona_apellido_paterno'=> 'Usedo',
                'persona_apellido_materno'=> 'Hinostroza',
                'persona_email'           => 'example1@gmail.com',
                'persona_tipo_documento'  => '1',
                'persona_dni'             => '74077578',
                'persona_telefono'        => null,
                'persona_nacimiento'      => null,
                'persona_blacklist'       => 'NO',
                'persona_empleado'        => 1,
                'person_codigo'           => '1776612958.2407',
                'persona_estado'          => 1,
                'created_at'              => '2026-04-19 10:35:58',
                'updated_at'              => '2026-04-19 10:35:58',
            ],
            [
                'id_persona'              => 11,
                'id_empresa'              => 1,
                'persona_nombre'          => 'Annel Stephany',
                'persona_apellido_paterno'=> 'Ihuaraqui',
                'persona_apellido_materno'=> 'Ricopa',
                'persona_email'           => 'annelstephanyricopa@gmail.com',
                'persona_tipo_documento'  => '1',
                'persona_dni'             => '71115938',
                'persona_telefono'        => null,
                'persona_nacimiento'      => null,
                'persona_blacklist'       => 'NO',
                'persona_empleado'        => 1,
                'person_codigo'           => '1776613199.1626',
                'persona_estado'          => 1,
                'created_at'              => '2026-04-19 10:39:59',
                'updated_at'              => '2026-04-20 12:57:00',
            ],
            [
                'id_persona'              => 12,
                'id_empresa'              => 1,
                'persona_nombre'          => 'Jacinto Rafael',
                'persona_apellido_paterno'=> 'Laura',
                'persona_apellido_materno'=> 'Ccori',
                'persona_email'           => 'example3@gmail.com',
                'persona_tipo_documento'  => '1',
                'persona_dni'             => '74077972',
                'persona_telefono'        => null,
                'persona_nacimiento'      => null,
                'persona_blacklist'       => 'NO',
                'persona_empleado'        => 1,
                'person_codigo'           => '1776867784.8111',
                'persona_estado'          => 1,
                'created_at'              => '2026-04-22 09:23:04',
                'updated_at'              => '2026-04-23 14:09:49',
            ],
        ]);

        // Contraseñas: todas usan "password" = "12345678" (hash bcrypt)
        DB::table('users')->insert([
            [
                'id_users'       => 1,
                'nombre_users'   => 'Eder Alfredo',
                'email'          => 'ederalfredo@bufeotec.com',
                'password'       => '$2y$10$PguwZg.k8bCjqmvyH8Z9BuaLgKJv4QcVhtkO6QFwUbcnMR08VlNq2',
                'username'       => 'superadmin',
                'user_fotografia'=> 'sin-fotografia.png',
                'id_persona'     => 1,
                'users_estado'   => 1,
                'created_at'     => '2023-06-13 17:56:32',
                'updated_at'     => now(),
            ],
            [
                'id_users'       => 10,
                'nombre_users'   => 'Katerine',
                'email'          => 'example1@gmail.com',
                'password'       => '$2y$10$0l0hctXjdMKRpzxTAExwrudohuROAdjewl26SXorMByt/DFaaTwry',
                'username'       => 'admin',
                'user_fotografia'=> 'sin-fotografia.png',
                'id_persona'     => 10,
                'users_estado'   => 1,
                'created_at'     => '2026-04-19 10:35:58',
                'updated_at'     => '2026-04-19 10:35:58',
            ],
            [
                'id_users'       => 11,
                'nombre_users'   => 'Annel Stephany',
                'email'          => 'annelstephanyricopa@gmail.com',
                'password'       => '$2y$10$iyPE4UVdH43iX0IZmExwXeyPHTshZ0RC4Q9VUs1JJMcNmO82kuIr6',
                'username'       => 'vendedor',
                'user_fotografia'=> 'sin-fotografia.png',
                'id_persona'     => 11,
                'users_estado'   => 1,
                'created_at'     => '2026-04-19 10:39:59',
                'updated_at'     => '2026-04-20 12:57:00',
            ],
            [
                'id_users'       => 12,
                'nombre_users'   => 'Jacinto Rafael',
                'email'          => 'example3@gmail.com',
                'password'       => '$2y$10$.urvaBme0KtcjUGkhTKah.I6EsrFT8bOHZ9TvA9.ISxKW3yWN8uwG',
                'username'       => 'contador',
                'user_fotografia'=> 'sin-fotografia.png',
                'id_persona'     => 12,
                'users_estado'   => 1,
                'created_at'     => '2026-04-22 09:23:04',
                'updated_at'     => '2026-04-23 14:09:49',
            ],
        ]);

        DB::table('user_sucursal')->insert([
            ['id_users' => 10, 'id_sucursal' => 1, 'created_at' => '2026-04-19 10:35:58', 'updated_at' => '2026-04-19 10:35:58'],
            ['id_users' => 11, 'id_sucursal' => 1, 'created_at' => '2026-04-20 12:57:00', 'updated_at' => '2026-04-20 12:57:00'],
            ['id_users' => 11, 'id_sucursal' => 2, 'created_at' => '2026-04-20 12:57:00', 'updated_at' => '2026-04-20 12:57:00'],
            ['id_users' => 12, 'id_sucursal' => 1, 'created_at' => '2026-04-23 14:09:49', 'updated_at' => '2026-04-23 14:09:49'],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
