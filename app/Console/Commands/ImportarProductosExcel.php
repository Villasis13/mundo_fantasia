<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarProductosExcel extends Command
{
    protected $signature   = 'importar:productos';
    protected $description = 'Limpia productos e importa desde el Excel de stock (req/)';

    // Abreviaturas del Excel → id_medida en BD
    private array $medidaMap = [
        'Und' => 58, // NIU - UNIDAD (BIENES)
        'Caj' => 6,  // BX - CAJA
        'Box' => 6,  // BX - CAJA
        'Jgo' => 22, // SET - JUEGO
        'Set' => 22, // SET - JUEGO
        'Par' => 44, // PR - PAR
        'Paq' => 43, // PK - PAQUETE
        'Pck' => 43, // PK - PAQUETE
        'Gru' => 19, // GRO - GRUESA
        'Bol' => 4,  // BG - BOLSA
        'Bli' => 58, // sin equivalente → UNIDAD
        'Pla' => 49, // PG - PLACAS
        'Tir' => 58, // sin equivalente → UNIDAD
    ];

    public function handle(): void
    {
        $ruta = base_path('req/productos con stock al 13-06-2026.xlsx');

        if (!file_exists($ruta)) {
            $this->error("Archivo no encontrado: $ruta");
            return;
        }

        // ── Familia → {id_fa, primera id_ca} ────────────────────────────
        $familiasDB = DB::table('familias')->get()->keyBy(fn($f) => strtoupper(trim($f->fa_nombre)));

        $catPorFamilia = DB::table('categorias')
            ->orderBy('id_ca')
            ->get()
            ->groupBy('id_fa')
            ->map(fn($cats) => $cats->first()->id_ca);

        // ── Leer Excel ────────────────────────────────────────────────────
        $this->info('Cargando Excel...');
        $spreadsheet = IOFactory::load($ruta);
        $ws          = $spreadsheet->getActiveSheet();
        $ultimaFila  = $ws->getHighestDataRow(); // 3500

        // ── Recolectar filas válidas ──────────────────────────────────────
        $filas = [];
        for ($r = 5; $r <= $ultimaFila; $r++) {
            $nombre = trim((string) $ws->getCell("F{$r}")->getValue());
            if ($nombre === '') continue;

            $filas[] = [
                'familia'       => strtoupper(trim((string) $ws->getCell("B{$r}")->getValue())),
                'nombre'        => $nombre,
                'unidad'        => trim((string) $ws->getCell("G{$r}")->getValue()),
                'stock'         => (float) $ws->getCell("H{$r}")->getValue(),
                'codigo_viejo'  => trim((string) $ws->getCell("C{$r}")->getValue()),
            ];
        }

        $totalProductos = count($filas);
        $this->info("Productos a importar: {$totalProductos}");

        if (!$this->confirm("¿Limpiar TODOS los productos actuales e importar {$totalProductos} nuevos?")) {
            $this->warn('Cancelado.');
            return;
        }

        // ── Limpiar BD ───────────────────────────────────────────────────
        $this->info('Limpiando tablas...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('TRUNCATE TABLE producto_sucursal');
        DB::statement('TRUNCATE TABLE productos');
        DB::statement('ALTER TABLE productos AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE producto_sucursal AUTO_INCREMENT = 1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Constantes ───────────────────────────────────────────────────
        $idEmpresa        = 1;
        $idTienda         = 10;
        $idTipoAfectacion = 1; // IGV (OP. GRAVADAS)
        $now              = now()->toDateTimeString();
        $sinFamilia       = [];
        $BATCH            = 200;

        $this->info('Importando...');
        $bar = $this->output->createProgressBar($totalProductos);
        $bar->start();

        $prodBatch = [];
        $psBatch   = [];
        $idPro     = 0; // Tras TRUNCATE+RESET el AI comienza en 1

        foreach ($filas as $f) {
            $idPro++;
            $codigo = str_pad($idPro, 6, '0', STR_PAD_LEFT);

            $idMedida = $this->medidaMap[$f['unidad']] ?? 58;

            $familiaObj = $familiasDB[$f['familia']] ?? null;
            if (!$familiaObj) {
                $sinFamilia[$f['familia']] = true;
                $familiaObj = $familiasDB['AREA DE ADORNOS Y REGALOS'] ?? $familiasDB->first();
            }
            $idFa = $familiaObj->id_fa;
            $idCa = $catPorFamilia[$idFa] ?? 1;

            $prodBatch[] = [
                'id_empresa'          => $idEmpresa,
                'id_ca'               => $idCa,
                'id_medida'           => $idMedida,
                'pro_nombre'          => $f['nombre'],
                'pro_codigo'          => $codigo,
                'pro_codigo_interno'  => $f['codigo_viejo'] ?: $codigo,
                'pro_descripcion'     => null,
                'pro_marca'           => null,
                'pro_stock_inicial'   => 0,
                'id_fac'              => $idFa,
                'pro_costo_base'      => 0,
                'pro_flete'           => 0,
                'pro_margen_ganancia' => 0,
                'pro_costo_total'     => 0,
                'pro_precio_venta'    => 0,
                'pro_estado'          => 1,
                'impuesto_bolsa'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            $psBatch[] = [
                'id_pro'              => $idPro,
                'id_sucursal'         => null,
                'id_tienda'           => $idTienda,
                'id_tipo_afectacion'  => $idTipoAfectacion,
                'ps_precio_uni'       => 0,
                'ps_precio_uni_2'     => 0,
                'ps_precio_uni_3'     => 0,
                'ps_stock'            => $f['stock'],
                'ps_stock_minimo'     => 0,
                'ps_porcen_igv'       => 18.00,
                'ps_estado'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            if (count($prodBatch) >= $BATCH) {
                DB::table('productos')->insert($prodBatch);
                DB::table('producto_sucursal')->insert($psBatch);
                $prodBatch = [];
                $psBatch   = [];
            }

            $bar->advance();
        }

        // Último lote
        if ($prodBatch) {
            DB::table('productos')->insert($prodBatch);
            DB::table('producto_sucursal')->insert($psBatch);
        }

        $bar->finish();
        $this->newLine();

        if ($sinFamilia) {
            $this->warn('Familias del Excel sin coincidencia en BD (asignadas a ADORNOS Y REGALOS):');
            foreach (array_keys($sinFamilia) as $fa) {
                $this->line("  - $fa");
            }
        }

        $this->info("✓ Importados {$idPro} productos correctamente.");
    }
}
