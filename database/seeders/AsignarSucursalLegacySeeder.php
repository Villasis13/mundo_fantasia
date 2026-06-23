<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsignarSucursalLegacySeeder extends Seeder
{
    public function run(): void
    {
        // Mostrar sucursales disponibles para elegir
        $sucursales = DB::table('sucursals')->get(['id_sucursal', 'sucursal_nombre']);

        if ($sucursales->isEmpty()) {
            $this->command->error('No hay sucursales registradas en la base de datos.');
            return;
        }

        $this->command->info('Sucursales disponibles:');
        foreach ($sucursales as $s) {
            $this->command->line("  [{$s->id_sucursal}] {$s->sucursal_nombre}");
        }

        $idSucursal = $this->command->ask('¿A qué ID de sucursal deseas asignar los usuarios?', $sucursales->first()->id_sucursal);

        $this->command->info("Usando sucursal ID: $idSucursal");

        // Usuarios legacy sin sucursal asignada
        $personaIds = DB::table('persona')
            ->where('person_codigo', 'like', 'LEGV_%')
            ->pluck('id_persona');

        $userIds = DB::table('users')
            ->whereIn('id_persona', $personaIds)
            ->pluck('id_users');

        $now = now()->toDateTimeString();
        $asignados = 0;

        foreach ($userIds as $userId) {
            $yaAsignado = DB::table('user_sucursal')
                ->where('id_users', $userId)
                ->where('id_sucursal', $idSucursal)
                ->exists();

            if (!$yaAsignado) {
                DB::table('user_sucursal')->insert([
                    'id_users'    => $userId,
                    'id_sucursal' => $idSucursal,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $asignados++;
            }
        }

        $this->command->info("Asignados: $asignados usuarios a la sucursal $idSucursal.");
    }
}
