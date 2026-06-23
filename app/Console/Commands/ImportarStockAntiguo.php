<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportarStockAntiguo extends Command
{
    protected $signature   = 'importar:stock-antiguo';
    protected $description = 'Importa solo el stock desde el dump SQL antiguo (complemento de importar:productos-antiguo)';

    private const TMP_DB   = 'mundo_import_tmp';
    private const SQL_FILE = 'req/mundo_20260611_192544.sql';

    public function handle(): int
    {
        // ── 1. Verificar estado del sistema ───────────────────────────
        $totalProductos = DB::table('productos')->count();
        if (!$totalProductos) {
            $this->error('No hay productos en el sistema. Ejecuta primero importar:productos-antiguo.');
            return self::FAILURE;
        }

        $idAlmacen = DB::table('almacen')->value('id_almacen');
        if (!$idAlmacen) {
            $this->error('No existe ningún almacén. Ejecuta primero importar:productos-antiguo.');
            return self::FAILURE;
        }

        if (DB::table('almacen_producto')->exists()) {
            if (!$this->confirm('Ya hay registros en almacen_producto. ¿Truncar y reimportar stock?')) {
                return self::FAILURE;
            }
            DB::table('almacen_producto')->truncate();
        }

        $this->info("Productos en sistema: {$totalProductos} | Almacén id={$idAlmacen}");

        // ── 2. Mapeo codigo_antiguo → id_pro ─────────────────────────
        $this->info('Cargando mapa de productos...');
        $productoMap = DB::table('productos')
            ->whereNotNull('pro_codigo')
            ->pluck('id_pro', 'pro_codigo');

        $this->info("  → {$productoMap->count()} códigos mapeados.");

        // ── 3. Crear BD temporal e importar dump ──────────────────────
        $this->info('Creando base de datos temporal...');
        DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
        DB::statement('CREATE DATABASE `' . self::TMP_DB . '` CHARACTER SET latin1 COLLATE latin1_swedish_ci');

        $sqlPath = str_replace('/', DIRECTORY_SEPARATOR, base_path(self::SQL_FILE));
        $host    = config('database.connections.mysql.host', '127.0.0.1');
        $port    = config('database.connections.mysql.port', 3306);
        $user    = config('database.connections.mysql.username', 'root');
        $pass    = config('database.connections.mysql.password', '');

        $this->info('Importando SQL dump (puede tardar 2-4 minutos)...');
        $passFlag = $pass ? " -p\"{$pass}\"" : '';
        $cmd = "mysql -h\"{$host}\" -P{$port} -u\"{$user}\"{$passFlag} \"" . self::TMP_DB . "\"";

        $descriptors = [
            0 => ['file', $sqlPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($proc)) {
            $this->error('No se pudo ejecutar mysql. Verificar que mysql esté en el PATH.');
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }

        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $ret = proc_close($proc);

        if ($ret !== 0) {
            $this->error("Error importando SQL (código {$ret}): {$stderr}");
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }
        $this->info('SQL importado correctamente.');

        // ── 4. Conexión a BD temporal ─────────────────────────────────
        Config::set('database.connections.tmp_import', [
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => self::TMP_DB,
            'username'  => $user,
            'password'  => $pass,
            'charset'   => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
        $tmp = DB::connection('tmp_import');

        // ── 5. Leer talmacen (CODALMA=01) sin whereIn ────────────────
        $this->info('Leyendo stock desde talmacen (CODALMA=01)...');
        $totalAlmacen = $tmp->table('talmacen')->where('CODALMA', '01')->count();
        $this->info("  → {$totalAlmacen} filas en talmacen para almacén principal.");

        // ── 6. Insertar almacen_producto en lotes ────────────────────
        $this->info("Insertando stock en almacen_producto...");
        $bar     = $this->output->createProgressBar($totalAlmacen);
        $bar->start();

        $insertados = 0;
        $saltados   = 0;
        $lote       = [];

        $tmp->table('talmacen')
            ->where('CODALMA', '01')
            ->select(['CODIGO', 'STOCK', 'PCOSTOINI'])
            ->orderBy('REGIDEN')
            ->chunk(500, function ($filas) use (
                &$lote, &$insertados, &$saltados,
                $productoMap, $idAlmacen, $bar
            ) {
                foreach ($filas as $s) {
                    $codigo = trim($s->CODIGO);
                    $idPro  = $productoMap[$codigo] ?? null;

                    if (!$idPro) {
                        $saltados++;
                        $bar->advance();
                        continue;
                    }

                    $lote[] = [
                        'id_almacen'      => $idAlmacen,
                        'id_pro'          => $idPro,
                        'ap_stock'        => (float) $s->STOCK,
                        'ap_precio_costo' => $s->PCOSTOINI > 0 ? (float) $s->PCOSTOINI : null,
                        'ap_estado'       => 1,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    $bar->advance();

                    if (count($lote) >= 200) {
                        DB::table('almacen_producto')->insertOrIgnore($lote);
                        $insertados += count($lote);
                        $lote = [];
                    }
                }
            });

        // Último lote
        if ($lote) {
            DB::table('almacen_producto')->insertOrIgnore($lote);
            $insertados += count($lote);
        }

        $bar->finish();
        $this->newLine();

        // ── 7. Productos sin stock en talmacen → insertar con stock 0 ─
        $this->info('Generando filas con stock=0 para productos sin entrada en talmacen...');
        $yaInsertados = DB::table('almacen_producto')->pluck('id_pro');
        $sinStock = DB::table('productos')
            ->whereNotIn('id_pro', $yaInsertados)
            ->where('pro_estado', 1)
            ->pluck('id_pro');

        $this->info("  → {$sinStock->count()} productos sin stock en talmacen.");

        foreach ($sinStock->chunk(200) as $chunk) {
            $lote = [];
            foreach ($chunk as $idPro) {
                $lote[] = [
                    'id_almacen'      => $idAlmacen,
                    'id_pro'          => $idPro,
                    'ap_stock'        => 0,
                    'ap_precio_costo' => null,
                    'ap_estado'       => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
            if ($lote) DB::table('almacen_producto')->insertOrIgnore($lote);
        }

        // ── 8. Limpiar BD temporal ───────────────────────────────────
        $this->info('Eliminando base de datos temporal...');
        DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');

        // ── 9. Resumen ───────────────────────────────────────────────
        $this->newLine();
        $this->info('Stock importado.');
        $this->table(
            ['Dato', 'Valor'],
            [
                ['Filas en almacen_producto',    DB::table('almacen_producto')->count()],
                ['Con stock > 0',                DB::table('almacen_producto')->where('ap_stock', '>', 0)->count()],
                ['Con stock = 0',                DB::table('almacen_producto')->where('ap_stock', 0)->count()],
                ['Sin match en talmacen',        $saltados],
            ]
        );

        return self::SUCCESS;
    }
}
