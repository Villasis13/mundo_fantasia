<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos nuevos en ventas (después de venta_codigo_hash)
        DB::statement("ALTER TABLE `ventas`
            ADD COLUMN `venta_es_anticipo` TINYINT NOT NULL DEFAULT 0 AFTER `venta_codigo_hash`,
            ADD COLUMN `venta_anticipo_usado` TINYINT NOT NULL DEFAULT 0 AFTER `venta_es_anticipo`");

        // Tabla venta_anticipos
        DB::statement("CREATE TABLE `venta_anticipos` (
            `id_venta_anticipo` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_venta` BIGINT UNSIGNED NULL,
            `id_venta_servicio` BIGINT UNSIGNED NULL,
            `venta_anticipo_monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `venta_anticipo_mirotime` VARCHAR(50) NULL,
            `venta_anticipo_estado` TINYINT NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id_venta_anticipo`),
            KEY `idx_va_id_venta` (`id_venta`),
            KEY `idx_va_id_venta_servicio` (`id_venta_servicio`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_anticipos');
        if (Schema::hasColumn('ventas', 'venta_anticipo_usado')) {
            DB::statement("ALTER TABLE `ventas` DROP COLUMN `venta_anticipo_usado`");
        }
        if (Schema::hasColumn('ventas', 'venta_es_anticipo')) {
            DB::statement("ALTER TABLE `ventas` DROP COLUMN `venta_es_anticipo`");
        }
    }
};
