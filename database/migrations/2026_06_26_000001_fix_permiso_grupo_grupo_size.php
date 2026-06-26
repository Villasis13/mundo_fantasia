<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * permiso_grupo_grupo guardaba el id (menu/submenu/opcion) pero era TINYINT (máx 127).
     * Al pasar los IDs de 127 se desbordaba. Se amplía a BIGINT UNSIGNED para que
     * coincida con id_opciones (bigint unsigned).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `permissions` MODIFY `permiso_grupo_grupo` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `permissions` MODIFY `permiso_grupo_grupo` TINYINT NULL');
    }
};
