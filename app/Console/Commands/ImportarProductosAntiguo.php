<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportarProductosAntiguo extends Command
{
    protected $signature   = 'importar:productos-antiguo';
    protected $description = 'Importa productos, familias y stock desde el dump SQL de Mundo Fantasía antiguo';

    private const ID_EMPRESA = 1;
    private const ID_TIENDA  = 10;  // tienda PRINCIPAL
    private const TMP_DB     = 'mundo_import_tmp';
    private const SQL_FILE   = 'req/mundo_20260611_192544.sql';

    public function handle(): int
    {
        // ── 0. Guardia ────────────────────────────────────────────────
        if (DB::table('productos')->exists()) {
            if (!$this->confirm('Ya existen productos en el sistema. ¿Continuar de todos modos?')) {
                return self::FAILURE;
            }
        }

        // ── 1. Almacén Principal ──────────────────────────────────────
        $idAlmacen = DB::table('almacen')
            ->where('id_empresa', self::ID_EMPRESA)
            ->value('id_almacen');

        if (!$idAlmacen) {
            $idAlmacen = DB::table('almacen')->insertGetId([
                'id_empresa'     => self::ID_EMPRESA,
                'almacen_nombre' => 'Almacén Principal',
                'almacen_estado' => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $this->info("Almacén Principal creado (id={$idAlmacen}).");
        } else {
            $this->info("Almacén existente (id={$idAlmacen}).");
        }

        // ── 2. Crear BD temporal e importar dump ──────────────────────
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
            $this->error("Error al importar el SQL (código {$ret}): {$stderr}");
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }
        $this->info('SQL importado correctamente.');

        // ── 3. Conexión a BD temporal ─────────────────────────────────
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

        // ── 4. Productos activos ──────────────────────────────────────
        $this->info('Leyendo productos activos...');
        $productos = $tmp->table('tproducto')
            ->where('ACTIVO', 1)
            ->where('ACTIVIX', 1)
            ->get(['FAMILIA', 'SUBFAMI', 'CODIGO', 'PRODUCTO', 'CDUNID', 'UNIDAD',
                   'PRECOSTO', 'PRECIO', 'MARCA', 'ICBPER']);

        $this->info("  → {$productos->count()} productos activos encontrados.");

        // ── 5. Familias ───────────────────────────────────────────────
        $familiasCodigos = $productos
            ->pluck('FAMILIA')
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        $familias = $tmp->table('tfamilias')
            ->whereIn('CODIGO', $familiasCodigos->toArray())
            ->get(['CODIGO', 'FAMILIA']);

        $this->info("Insertando {$familias->count()} familias...");
        $familiaMap = []; // CODIGO(3) => id_fa

        foreach ($familias as $f) {
            $nombre = $this->toUtf8(trim($f->FAMILIA));
            if (!$nombre) continue;
            $id = DB::table('familias')->insertGetId([
                'id_empresa' => self::ID_EMPRESA,
                'fa_nombre'  => $nombre,
                'fa_estado'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $familiaMap[trim($f->CODIGO)] = $id;
        }

        // ── 6. Categorías (subfamilias) ───────────────────────────────
        $subfamiKeys = $productos
            ->map(fn($p) => trim($p->FAMILIA) . str_pad(trim($p->SUBFAMI), 3, '0', STR_PAD_LEFT))
            ->unique()
            ->filter()
            ->values();

        $subfamilias = $tmp->table('tsubfami')
            ->whereIn('CODIGO', $subfamiKeys->toArray())
            ->get(['CODIGO', 'FAMILIA']);

        $this->info("Insertando {$subfamilias->count()} categorías...");
        $categoriaMap      = []; // tsubfami.CODIGO(6) => id_ca
        $catGeneralPorFam  = []; // familiaMap key     => id_ca (fallback)

        foreach ($subfamilias as $s) {
            $codFamilia = substr(trim($s->CODIGO), 0, 3);
            $idFa = $familiaMap[$codFamilia] ?? null;
            if (!$idFa) continue;
            $nombre = $this->toUtf8(trim($s->FAMILIA));
            if (!$nombre) continue;
            $id = DB::table('categorias')->insertGetId([
                'id_fa'      => $idFa,
                'ca_nombre'  => $nombre,
                'ca_estado'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $categoriaMap[trim($s->CODIGO)] = $id;
        }

        // Categoría "General" de respaldo para familias con productos sin subfamilia válida
        foreach ($productos as $p) {
            $key = trim($p->FAMILIA) . str_pad(trim($p->SUBFAMI), 3, '0', STR_PAD_LEFT);
            if (isset($categoriaMap[$key])) continue;
            $codFam = trim($p->FAMILIA);
            $idFa   = $familiaMap[$codFam] ?? null;
            if (!$idFa || isset($catGeneralPorFam[$codFam])) continue;
            $id = DB::table('categorias')->insertGetId([
                'id_fa'      => $idFa,
                'ca_nombre'  => 'General',
                'ca_estado'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $catGeneralPorFam[$codFam] = $id;
        }

        // ── 7. Productos + producto_sucursal ──────────────────────────
        $this->info("Insertando productos y asignando a tienda PRINCIPAL...");
        $medidaMap   = $this->medidaMap();
        $productoMap = []; // old CODIGO => id_pro
        $seen        = [];

        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($productos as $p) {
                $codigo = trim($p->CODIGO);
                if (!$codigo || isset($seen[$codigo])) {
                    $bar->advance();
                    continue;
                }
                $seen[$codigo] = true;

                $subfamiKey = trim($p->FAMILIA) . str_pad(trim($p->SUBFAMI), 3, '0', STR_PAD_LEFT);
                $idCa = $categoriaMap[$subfamiKey]
                    ?? $catGeneralPorFam[trim($p->FAMILIA)]
                    ?? null;

                $idFa = $familiaMap[trim($p->FAMILIA)] ?? null;

                if (!$idCa || !$idFa) {
                    $bar->advance();
                    continue;
                }

                $unidad   = strtolower(trim($p->UNIDAD));
                $idMedida = $medidaMap[$unidad] ?? 58; // fallback UNIDAD (BIENES)

                $idPro = DB::table('productos')->insertGetId([
                    'id_empresa'          => self::ID_EMPRESA,
                    'id_ca'               => $idCa,
                    'id_fac'              => $idFa,
                    'id_medida'           => $idMedida,
                    'pro_nombre'          => $this->toUtf8(trim($p->PRODUCTO)),
                    'pro_codigo'          => $codigo,
                    'pro_codigo_interno'  => $codigo,
                    'pro_descripcion'     => null,
                    'pro_foto'            => null,
                    'pro_marca'           => $this->toUtf8(trim($p->MARCA)) ?: null,
                    'impuesto_bolsa'      => (int) $p->ICBPER,
                    'pro_costo_base'      => (float) $p->PRECOSTO,
                    'pro_flete'           => 0,
                    'pro_margen_ganancia' => 0,
                    'pro_costo_total'     => (float) $p->PRECOSTO,
                    'pro_precio_venta'    => (float) $p->PRECIO,
                    'pro_stock_inicial'   => 0,
                    'pro_estado'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // Asignar a tienda PRINCIPAL (precio público = precio venta del sistema antiguo)
                DB::table('producto_sucursal')->insert([
                    'id_pro'             => $idPro,
                    'id_sucursal'        => null,
                    'id_tienda'          => self::ID_TIENDA,
                    'id_tipo_afectacion' => 1, // Gravada IGV 18%
                    'ps_precio_uni'      => (float) $p->PRECIO,
                    'ps_precio_uni_2'    => 0,
                    'ps_precio_uni_3'    => 0,
                    'ps_stock'           => 0,
                    'ps_stock_minimo'    => 0,
                    'ps_porcen_igv'      => 18,
                    'ps_estado'          => 1,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                $productoMap[$codigo] = $idPro;
                $bar->advance();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine();
            $this->error('Error insertando productos: ' . $e->getMessage());
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();

        // ── 8. Stock desde talmacen ──────────────────────────────────
        $this->info('Insertando stock (talmacen, CODALMA=01)...');

        $stocks = $tmp->table('talmacen')
            ->where('CODALMA', '01')
            ->whereIn('CODIGO', array_keys($productoMap))
            ->get(['CODIGO', 'STOCK', 'PCOSTOINI']);

        $stockMap = $stocks->keyBy(fn($s) => trim($s->CODIGO));

        DB::beginTransaction();
        try {
            foreach ($productoMap as $oldCodigo => $idPro) {
                $s = $stockMap[$oldCodigo] ?? null;
                DB::table('almacen_producto')->insert([
                    'id_almacen'      => $idAlmacen,
                    'id_pro'          => $idPro,
                    'ap_stock'        => $s ? (float) $s->STOCK : 0,
                    'ap_precio_costo' => ($s && $s->PCOSTOINI > 0) ? (float) $s->PCOSTOINI : null,
                    'ap_estado'       => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error insertando stock: ' . $e->getMessage());
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }

        // ── 9. Limpiar BD temporal ───────────────────────────────────
        $this->info('Eliminando base de datos temporal...');
        DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');

        // ── 10. Resumen ───────────────────────────────────────────────
        $this->newLine();
        $this->info('Importacion completada.');
        $this->table(
            ['Entidad', 'Registros'],
            [
                ['Familias',              DB::table('familias')->count()],
                ['Categorias',            DB::table('categorias')->count()],
                ['Productos',             DB::table('productos')->count()],
                ['Filas producto/tienda', DB::table('producto_sucursal')->count()],
                ['Filas almacen_producto',DB::table('almacen_producto')->count()],
                ['Productos con stock>0', DB::table('almacen_producto')->where('ap_stock', '>', 0)->count()],
            ]
        );

        return self::SUCCESS;
    }

    // ── Mapa UNIDAD (antiguo) → id_medida (nuevo) ────────────────────
    private function medidaMap(): array
    {
        return [
            'und'      => 58, 'unidad'   => 58, 'uni'     => 58,
            'par'      => 44, 'pares'    => 44,
            'doc'      => 14, 'docena'   => 14,
            'paq'      => 43, 'paquete'  => 43, 'pack'    => 43, 'sobre' => 43,
            'kit'      => 26,
            'jgo'      => 22, 'juego'    => 22, 'set'     => 22,
            'mlr'      => 39, 'millar'   => 39,
            'mtr'      => 31, 'metro'    => 31, 'mt'      => 31,
            'kg'       => 23, 'kilo'     => 23, 'kilogram'=> 23,
            'lt'       => 29, 'litro'    => 29,
            'cja'      => 6,  'caja'     => 6,
            'blsa'     => 4,  'bolsa'    => 4,
            'rollo'    => 64, 'rol'      => 64,
            'pza'      => 48, 'pieza'    => 48,
        ];
    }

    // ── Convierte latin1/ISO-8859-1 a UTF-8 si hace falta ────────────
    private function toUtf8(string $str): string
    {
        if ($str === '') return '';
        return mb_detect_encoding($str, 'UTF-8', true)
            ? $str
            : mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
}
