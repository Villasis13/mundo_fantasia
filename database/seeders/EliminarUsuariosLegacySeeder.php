<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EliminarUsuariosLegacySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $personaIds = DB::table('persona')
            ->where('person_codigo', 'like', 'LEGV_%')
            ->pluck('id_persona');

        $userIds = DB::table('users')
            ->whereIn('id_persona', $personaIds)
            ->pluck('id_users');

        DB::table('model_has_roles')
            ->whereIn('model_id', $userIds)
            ->where('model_type', 'App\\Models\\User')
            ->delete();

        DB::table('user_sucursal')
            ->whereIn('id_users', $userIds)
            ->delete();

        DB::table('users')
            ->whereIn('id_users', $userIds)
            ->delete();

        DB::table('persona')
            ->whereIn('id_persona', $personaIds)
            ->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("Eliminados: {$userIds->count()} usuarios y {$personaIds->count()} personas legacy.");
    }
}
