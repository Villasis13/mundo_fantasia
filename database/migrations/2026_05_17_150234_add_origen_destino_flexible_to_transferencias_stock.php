<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE transferencias_stock
                MODIFY id_almacen_origen BIGINT UNSIGNED NULL,
                ADD COLUMN id_tienda_origen BIGINT UNSIGNED NULL AFTER id_almacen_origen,
                MODIFY id_tienda_destino BIGINT UNSIGNED NULL,
                ADD COLUMN id_almacen_destino BIGINT UNSIGNED NULL AFTER id_tienda_destino
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE transferencias_stock
                DROP COLUMN id_tienda_origen,
                DROP COLUMN id_almacen_destino
        ');
        DB::statement('
            ALTER TABLE transferencias_stock
                MODIFY id_almacen_origen BIGINT UNSIGNED NOT NULL,
                MODIFY id_tienda_destino BIGINT UNSIGNED NOT NULL
        ');
    }
};
