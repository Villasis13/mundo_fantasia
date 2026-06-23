<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('ubigeo')->truncate();

        $data = json_decode(file_get_contents(__DIR__ . '/data/ubigeo.json'), true);

        foreach (array_chunk($data, 200) as $chunk) {
            DB::table('ubigeo')->insert($chunk);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
