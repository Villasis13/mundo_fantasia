<?php
/**
 * sync_precios.php
 * Copia PRECIO y PRECIODIST desde tunidprod (BD antigua) hacia producto_sucursal.
 * Ejecutar desde la raíz del proyecto: php sync_precios.php
 */

require __DIR__ . '/vendor/autoload.php';
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sqlFile = __DIR__ . '/req/tunidprod_extract.sql';

if (!file_exists($sqlFile)) {
    die("Error: no se encontró el archivo {$sqlFile}\n");
}

echo "=== Sincronización de precios desde BD antigua ===\n";
echo "Archivo: {$sqlFile}\n\n";

// ── Paso 1: parsear tunidprod_extract.sql ─────────────────────────────────
// Usamos CODIGO='01' (lista principal) y UNID='1.0000' (unidad base).
// Para cada CODPROD guardamos el último precio encontrado.

echo "Paso 1: Leyendo y parseando el archivo SQL...\n";

$precios   = [];  // [ codprod => [precio, preciodist] ]
$parsedRows = 0;

$handle = fopen($sqlFile, 'r');
if (!$handle) {
    die("Error: no se pudo abrir el archivo.\n");
}

// Regex: captura los 9 primeros campos quoted de cada tupla
// Grupos: 1=CODIGO, 2=UNID, 3=CODPROD, 4=PRECIO, 5=PRECIODIST
$pattern = "/\('(\\d+)','[^']*','[^']*','([^']*)','([^']*)','[^']*','[^']*','([^']*)','([^']*)'/";

while (($line = fgets($handle)) !== false) {
    if (stripos($line, 'INSERT INTO') === false) continue;

    preg_match_all($pattern, $line, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $codigo     = $m[1];
        $unid       = $m[2];
        $codprod    = trim($m[3]);
        $precio     = (float) $m[4];
        $preciodist = (float) $m[5];

        // Solo lista principal (CODIGO=01) y unidad base (UNID=1.0000)
        if ($codigo !== '01' || $unid !== '1.0000') continue;
        if ($codprod === '') continue;

        $precios[$codprod] = [$precio, $preciodist];
        $parsedRows++;
    }
}
fclose($handle);

$uniqueProducts = count($precios);
echo "  Filas procesadas (CODIGO=01, UNID=1.0000): {$parsedRows}\n";
echo "  Productos únicos con precio: {$uniqueProducts}\n\n";

if ($uniqueProducts === 0) {
    die("No se encontraron precios. Verifique el archivo.\n");
}

// ── Paso 2: actualizar producto_sucursal ─────────────────────────────────
echo "Paso 2: Actualizando precios en producto_sucursal...\n";

$updatedSucursal = 0;   // filas de producto_sucursal actualizadas
$notInProducts   = 0;   // CODPRODs sin match en tabla productos
$noSucursal      = 0;   // productos encontrados pero sin filas en producto_sucursal

$chunkSize = 500;
$chunks    = array_chunk($precios, $chunkSize, true);
$total     = count($chunks);
$current   = 0;

foreach ($chunks as $chunk) {
    $current++;
    echo "  Chunk {$current}/{$total}...\r";

    // Obtener los id_pro de los productos con estos códigos internos
    $codprods = array_keys($chunk);

    $productos = DB::table('productos')
        ->whereIn('pro_codigo_interno', $codprods)
        ->select('id_pro', 'pro_codigo_interno')
        ->get()
        ->keyBy('pro_codigo_interno');

    foreach ($chunk as $codprod => [$precio, $preciodist]) {
        if (!isset($productos[$codprod])) {
            $notInProducts++;
            continue;
        }

        $idPro = $productos[$codprod]->id_pro;

        $rows = DB::table('producto_sucursal')
            ->where('id_pro', $idPro)
            ->update([
                'ps_precio_uni'   => $precio,
                'ps_precio_uni_2' => $preciodist,
                'updated_at'      => now(),
            ]);

        if ($rows > 0) {
            $updatedSucursal += $rows;
        } else {
            $noSucursal++;
        }
    }
}

echo "\n\n=== Resultado ===\n";
echo "Filas de producto_sucursal actualizadas : {$updatedSucursal}\n";
echo "Productos no encontrados en productos   : {$notInProducts}\n";
echo "Productos encontrados sin sucursal      : {$noSucursal}\n";
echo "\n¡Proceso completado!\n";
