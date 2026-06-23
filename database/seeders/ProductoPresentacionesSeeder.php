<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Migra las presentaciones de producto desde el sistema anterior.
 *
 * Fuente: req/tunidprod_extract.sql  (tabla `tunidprod` del sistema legacy)
 * Columnas 0-indexed que se usan:
 *   [1] UNIDAD    → pres_nombre
 *   [2] UNIDA     → pres_abreviatura
 *   [3] UNID      → pres_factor  (1.0000 = Unidad, 12.0000 = Docena, etc.)
 *   [4] CODPROD   → pro_codigo_interno en productos
 *   [6] PRECOSTO  → pres_precio_costo
 *   [7] PRECIO    → pres_precio_1
 *   [8] PRECIODIST→ pres_precio_2
 *  [22] ACTIVIX   → pres_estado
 */
class ProductoPresentacionesSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run(): void
    {
        $file = base_path('req/tunidprod_extract.sql');

        if (!file_exists($file)) {
            $this->command->error("Archivo no encontrado: {$file}");
            return;
        }

        $this->command->info('Cargando mapa de productos...');

        // Construir mapa: pro_codigo_interno => id_pro  (solo los que tienen código interno)
        $codMap = DB::table('productos')
            ->whereNotNull('pro_codigo_interno')
            ->where('pro_codigo_interno', '!=', '')
            ->pluck('id_pro', 'pro_codigo_interno')
            ->toArray();

        $this->command->info(count($codMap) . ' productos con código interno cargados.');
        $this->command->info('Procesando archivo SQL...');

        $handle  = fopen($file, 'r');
        $batch   = [];
        $inserted = 0;
        $skipped  = 0;
        $lineNum  = 0;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('producto_presentaciones')->truncate();

        while (!feof($handle)) {
            $line = fgets($handle);
            $lineNum++;

            $trimmed = ltrim($line);
            if (!str_starts_with($trimmed, 'INSERT INTO')) {
                continue;
            }

            // Extraer la parte de VALUES
            $pos = strpos($trimmed, 'VALUES (');
            if ($pos === false) {
                continue;
            }

            // Cadena desde el primer '(' de la lista de tuples
            $valuesPart = substr($trimmed, $pos + 7); // incluye el '('
            $valuesPart = rtrim($valuesPart, " ;\r\n");

            // Dividir en tuplas individuales por ),(
            // Los valores no contienen '(' ni ')' — son códigos, nombres y números
            $tuples = explode('),(', $valuesPart);

            foreach ($tuples as $i => $tuple) {
                // Quitar el '(' inicial del primero y el ')' final del último
                if ($i === 0) {
                    $tuple = ltrim($tuple, '(');
                }
                if ($i === count($tuples) - 1) {
                    $tuple = rtrim($tuple, ')');
                }

                $values = str_getcsv($tuple, ',', "'");

                // Necesitamos al menos 23 columnas (índice 22 = ACTIVIX)
                if (count($values) < 23) {
                    $skipped++;
                    continue;
                }

                $codprod = trim($values[4]);

                if ($codprod === '' || !isset($codMap[$codprod])) {
                    $skipped++;
                    continue;
                }

                $nombre = trim($values[1]);
                if ($nombre === '') {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'id_pro'            => $codMap[$codprod],
                    'pres_nombre'       => $nombre,
                    'pres_abreviatura'  => trim($values[2]),
                    'pres_factor'       => (float) $values[3],
                    'pres_precio_costo' => (float) $values[6],
                    'pres_precio_1'     => (float) $values[7],
                    'pres_precio_2'     => (float) $values[8],
                    'pres_estado'       => (int)   $values[22],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                if (count($batch) >= self::BATCH_SIZE) {
                    $this->insertarBatch($batch);
                    $inserted += count($batch);
                    $batch = [];
                    $this->command->line("  {$inserted} presentaciones insertadas...");
                }
            }
        }

        fclose($handle);

        // Insertar el último batch
        if (!empty($batch)) {
            $this->insertarBatch($batch);
            $inserted += count($batch);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✓ Completado: {$inserted} presentaciones importadas, {$skipped} filas omitidas (sin producto en sistema).");
    }

    private function insertarBatch(array $batch): void
    {
        // insertOrIgnore respeta el UNIQUE (id_pro, pres_nombre) sin abortar
        DB::table('producto_presentaciones')->insertOrIgnore($batch);
    }
}
