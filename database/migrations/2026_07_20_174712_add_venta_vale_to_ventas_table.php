<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $after = Schema::hasColumn('ventas', 'venta_anticipo_usado') ? 'venta_anticipo_usado' : 'venta_codigo_hash';
        DB::statement("ALTER TABLE `ventas` ADD COLUMN `venta_vale` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `{$after}`");
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'venta_vale')) {
            DB::statement("ALTER TABLE `ventas` DROP COLUMN `venta_vale`");
        }
    }
};
