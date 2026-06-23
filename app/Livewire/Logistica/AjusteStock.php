<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AjusteStock extends Component
{
    use WithPagination;

    public string $vista = 'historial';

    // ── Selector de ubicación ─────────────────────────────────────
    public string $ubicacionKey = '';
    public int    $idTienda     = 0;

    // ── Búsqueda y lista de productos ────────────────────────────
    public string $buscarProducto = '';
    public array  $resultados     = [];
    public array  $items          = [];  // [{id_pro, nombre, codigo, stock_actual, cantidad}]

    // ── Revisión post-guardar ─────────────────────────────────────
    public ?int $idInventarioActivo = null;

    // ── Modal detalle ─────────────────────────────────────────────
    public ?int $idInventarioDetalle = null;

    // ── Filtros historial ─────────────────────────────────────────
    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public int    $porPagina   = 10;

    private int   $cachedRoleId = 0;
    private ?Logs $logs         = null;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    public function mount(): void
    {
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function almacenId(): ?int
    {
        return str_starts_with($this->ubicacionKey, 'almacen_')
            ? ((int) substr($this->ubicacionKey, 8) ?: null)
            : null;
    }

    private function empresaId(): ?int
    {
        return str_starts_with($this->ubicacionKey, 'empresa_')
            ? ((int) substr($this->ubicacionKey, 8) ?: null)
            : null;
    }

    private function tiendaId(): ?int
    {
        return ($this->empresaId() !== null && $this->idTienda > 0) ? $this->idTienda : null;
    }

    private function ubicacionConfigurada(): bool
    {
        return $this->almacenId() !== null || $this->tiendaId() !== null;
    }

    // ── Watchers ──────────────────────────────────────────────────
    public function updatedUbicacionKey(): void
    {
        $this->idTienda       = 0;
        $this->items          = [];
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function updatedIdTienda(): void
    {
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function updatingPorPagina(): void { $this->resetPage(); }
    public function updatedFiltroDesde(): void { $this->resetPage(); }
    public function updatedFiltroHasta(): void { $this->resetPage(); }

    public function updatedBuscarProducto(): void
    {
        if (!$this->ubicacionConfigurada() || strlen(trim($this->buscarProducto)) < 2) {
            $this->resultados = [];
            return;
        }

        $yaAgregados = collect($this->items)->pluck('id_pro')->all();
        $b     = trim($this->buscarProducto);
        $almId = $this->almacenId();
        $tndId = $this->tiendaId();

        if ($almId) {
            $this->resultados = DB::table('almacen_producto as ap')
                ->join('productos as p', 'p.id_pro', '=', 'ap.id_pro')
                ->where('ap.id_almacen', $almId)
                ->where('ap.ap_estado', 1)
                ->where('p.pro_estado', 1)
                ->whereNotIn('p.id_pro', $yaAgregados)
                ->where(function ($q) use ($b) {
                    $q->where('p.pro_nombre', 'like', "%{$b}%")
                      ->orWhere('p.pro_codigo', 'like', "%{$b}%");
                })
                ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                         DB::raw('ap.ap_stock as stock_actual'))
                ->orderBy('p.pro_nombre')->limit(10)->get()->toArray();
        } else {
            $this->resultados = DB::table('producto_sucursal as ps')
                ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
                ->where('ps.id_tienda', $tndId)
                ->where('ps.ps_estado', 1)
                ->where('p.pro_estado', 1)
                ->whereNotIn('p.id_pro', $yaAgregados)
                ->where(function ($q) use ($b) {
                    $q->where('p.pro_nombre', 'like', "%{$b}%")
                      ->orWhere('p.pro_codigo', 'like', "%{$b}%");
                })
                ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                         DB::raw('ps.ps_stock as stock_actual'))
                ->orderBy('p.pro_nombre')->limit(10)->get()->toArray();
        }
    }

    // ── Formulario ────────────────────────────────────────────────
    public function agregarProducto(int $idPro, string $nombre, string $codigo, float $stock): void
    {
        foreach ($this->items as $item) {
            if ((int) $item['id_pro'] === $idPro) return;
        }
        $this->items[] = [
            'id_pro'       => $idPro,
            'nombre'       => $nombre,
            'codigo'       => $codigo,
            'stock_actual' => $stock,
            'cantidad'     => '',
        ];
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function quitarItem(int $idx): void
    {
        array_splice($this->items, $idx, 1);
        $this->items = array_values($this->items);
    }

    public function nuevaInventario(): void
    {
        $this->limpiarFormulario();
        $this->vista = 'nuevo';
    }

    public function volverHistorial(): void
    {
        $this->limpiarFormulario();
        $this->vista = 'historial';
        $this->resetPage();
    }

    // ── Guardar inventario ────────────────────────────────────────
    public function guardar(): void
    {
        if (empty($this->items)) {
            $this->addError('items', 'Agregue al menos un producto.');
            return;
        }

        if (!$this->ubicacionConfigurada()) {
            $this->addError('ubicacionKey', 'Seleccione un almacén o sede.');
            return;
        }

        $almId = $this->almacenId();
        $tndId = $this->tiendaId();

        DB::beginTransaction();
        try {
            $numero = 'INV-' . date('Y') . '-' . str_pad(
                DB::table('inventario')->count() + 1, 5, '0', STR_PAD_LEFT
            );

            $idInventario = DB::table('inventario')->insertGetId([
                'inventario_numero' => $numero,
                'id_almacen'        => $almId,
                'id_tienda'         => $tndId,
                'id_users'          => auth()->user()->id_users,
                'inventario_fecha'  => now()->format('Y-m-d'),
                'inventario_estado' => 'borrador',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            foreach ($this->items as $item) {
                $stockContado = (float) $item['cantidad'];
                $stockSistema = (float) $item['stock_actual'];

                DB::table('inventario_detalle')->insert([
                    'id_inventario' => $idInventario,
                    'id_pro'        => (int) $item['id_pro'],
                    'stock_sistema' => $stockSistema,
                    'stock_contado' => $stockContado,
                    'diferencia'    => $stockContado - $stockSistema,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->idInventarioActivo = $idInventario;
            $this->vista = 'revision';
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al guardar el inventario.');
        }
    }

    // ── Confirmar: ingreso para sobrantes, mensaje para faltantes ─
    public function confirmarInventario(int $idInventario): void
    {
        $inv = DB::table('inventario')->where('id_inventario', $idInventario)->first();
        if (!$inv || $inv->inventario_estado === 'confirmado') return;

        $sobrantes = DB::table('inventario_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_inventario', $idInventario)
            ->where('d.diferencia', '>', 0)
            ->select('d.*', 'p.pro_nombre')
            ->get();

        $totalFaltantes = DB::table('inventario_detalle')
            ->where('id_inventario', $idInventario)
            ->where('diferencia', '<', 0)
            ->count();

        DB::beginTransaction();
        try {
            if ($sobrantes->isNotEmpty()) {
                $idMov = DB::table('movimientos_productos')->insertGetId([
                    'movimientos_productos_fecha'          => now()->toDateString(),
                    'id_users'                             => auth()->user()->id_users,
                    'id_sucursal'                          => $inv->id_tienda,
                    'id_almacen'                           => $inv->id_almacen,
                    'movimientos_productos_fecha_creacion' => now(),
                    'movimientos_productos_tipo'           => 1,
                    'movimientos_productos_estado'         => 1,
                    'movimientos_productos_motivo'         => "Ingreso sobrantes inventario {$inv->inventario_numero}",
                    'created_at'                           => now(),
                    'updated_at'                           => now(),
                ]);

                foreach ($sobrantes as $d) {
                    $diferencia = (float) $d->diferencia;

                    if ($inv->id_almacen) {
                        $costo = (float) (DB::table('almacen_producto')
                            ->where('id_almacen', $inv->id_almacen)
                            ->where('id_pro', $d->id_pro)
                            ->value('ap_precio_costo') ?? 0);
                        DB::table('almacen_producto')
                            ->where('id_almacen', $inv->id_almacen)
                            ->where('id_pro', $d->id_pro)
                            ->increment('ap_stock', $diferencia, ['updated_at' => now()]);
                    } else {
                        $costo = (float) (DB::table('producto_sucursal')
                            ->where('id_tienda', $inv->id_tienda)
                            ->where('id_pro', $d->id_pro)
                            ->value('ps_precio_uni') ?? 0);
                        DB::table('producto_sucursal')
                            ->where('id_tienda', $inv->id_tienda)
                            ->where('id_pro', $d->id_pro)
                            ->increment('ps_stock', $diferencia, ['updated_at' => now()]);
                    }

                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'               => $idMov,
                        'id_pro'                                 => $d->id_pro,
                        'movimientos_productos_detalle_cantidad' => (string) $diferencia,
                        'costo_unitario'                         => $costo,
                        'id_referencia'                          => $idInventario,
                        'tipo_referencia'                        => 'inventario',
                        'movimientos_productos_detalle_estado'   => 1,
                        'created_at'                             => now(),
                        'updated_at'                             => now(),
                    ]);
                }
            }

            DB::table('inventario')
                ->where('id_inventario', $idInventario)
                ->update(['inventario_estado' => 'confirmado', 'updated_at' => now()]);

            DB::commit();

            // Cerrar revisión o modal según origen
            $fromRevision = ($this->idInventarioActivo === $idInventario);
            if ($fromRevision) {
                $this->idInventarioActivo = null;
                $this->vista = 'historial';
            } else {
                $this->idInventarioDetalle = null;
                $this->dispatch('cerrarModalDetalleInventario');
            }
            $this->resetPage();

            $totalSobrantes = $sobrantes->count();
            if ($totalSobrantes > 0 && $totalFaltantes > 0) {
                $msg = "Se crearon movimientos de ingreso para los {$totalSobrantes} producto(s) con sobrante. "
                     . "Los {$totalFaltantes} producto(s) con faltantes deben regularizarse con un comprobante de venta.";
            } elseif ($totalSobrantes > 0) {
                $msg = "Se crearon movimientos de ingreso para los {$totalSobrantes} producto(s) con sobrante.";
            } elseif ($totalFaltantes > 0) {
                $msg = "Inventario confirmado. Los {$totalFaltantes} producto(s) con faltantes deben regularizarse con un comprobante de venta.";
            } else {
                $msg = "Inventario confirmado. No hay diferencias de stock.";
            }
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al confirmar el inventario.');
        }
    }

    // ── Ver detalle desde historial ───────────────────────────────
    public function verDetalle(int $id): void
    {
        $this->idInventarioDetalle = $id;
        $this->dispatch('abrirModalDetalleInventario');
    }

    // ── Exportar Excel ────────────────────────────────────────────
    public function exportarExcel(int $idInventario): mixed
    {
        if (!auth()->user()->can('ajuste_stock.exportar')) {
            return null;
        }

        $inv = DB::table('inventario')->where('id_inventario', $idInventario)->first();
        if (!$inv) return null;

        $items = DB::table('inventario_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_inventario', $idInventario)
            ->select('d.*', 'p.pro_nombre', 'p.pro_codigo')
            ->orderBy('p.pro_nombre')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');

        // ── Estilo base: fondo blanco, negrita, texto negro ──────────
        $sheet->getStyle('A1:P2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true],
        ]);

        // ── Fusionar primero ──────────────────────────────────────
        $sheet->mergeCells('A1:A2');  // CODIGO
        $sheet->mergeCells('B1:B2');  // PRODUCTO
        $sheet->mergeCells('C1:E1');  // SISTEMA
        $sheet->mergeCells('F1:H1');  // FISICO
        $sheet->mergeCells('I1:I2');  // S/F
        $sheet->mergeCells('J1:L1');  // FALTANTE
        $sheet->mergeCells('M1:O1');  // SOBRANTE
        $sheet->mergeCells('P1:P2');  // OBSERVACION

        // ── Valores fila 1 (grupos) ───────────────────────────────
        $sheet->setCellValue('A1', 'CODIGO');
        $sheet->setCellValue('B1', 'PRODUCTO');
        $sheet->setCellValue('C1', 'SISTEMA');
        $sheet->setCellValue('F1', 'FISICO');
        $sheet->setCellValue('I1', 'S/F');
        $sheet->setCellValue('J1', 'FALTANTE');
        $sheet->setCellValue('M1', 'SOBRANTE');
        $sheet->setCellValue('P1', 'OBSERVACION');

        // ── Valores fila 2 (sub-cabeceras) ────────────────────────
        $sheet->setCellValue('C2', 'STOCK');
        $sheet->setCellValue('D2', 'COSTO');
        $sheet->setCellValue('E2', 'TOTAL SISTEMA');
        $sheet->setCellValue('F2', 'FISICO');
        $sheet->setCellValue('G2', 'COSTO');
        $sheet->setCellValue('H2', 'TOTAL FISICO');
        $sheet->setCellValue('J2', 'FALTANTE');
        $sheet->setCellValue('K2', 'COSTO');
        $sheet->setCellValue('L2', 'TOTAL FALTANTE');
        $sheet->setCellValue('M2', 'SOBRANTE');
        $sheet->setCellValue('N2', 'COSTO');
        $sheet->setCellValue('O2', 'TOTAL SOBRANTE');

        // ── Colores de texto por grupo ────────────────────────────
        $purple = ['font' => ['bold' => true, 'color' => ['rgb' => '7B2D8B']]]; // SISTEMA
        $cyan   = ['font' => ['bold' => true, 'color' => ['rgb' => '0E7490']]]; // FISICO
        $orange = ['font' => ['bold' => true, 'color' => ['rgb' => 'C2410C']]]; // FALTANTE
        $red    = ['font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']]]; // SOBRANTE

        foreach (['C1', 'C2', 'D2', 'E2'] as $c) $sheet->getStyle($c)->applyFromArray($purple);
        foreach (['F1', 'F2', 'G2', 'H2'] as $c) $sheet->getStyle($c)->applyFromArray($cyan);
        foreach (['J1', 'J2', 'K2', 'L2'] as $c) $sheet->getStyle($c)->applyFromArray($orange);
        foreach (['M1', 'M2', 'N2', 'O2'] as $c) $sheet->getStyle($c)->applyFromArray($red);

        // ── Filas de datos (desde fila 3) ────────────────────────
        // A=CODIGO, B=PRODUCTO, C=STOCK, D=COSTO, E=TOTAL SISTEMA,
        // F=FISICO, G=COSTO, H=TOTAL FISICO, I=S/F,
        // J=FALTANTE, K=COSTO, L=TOTAL FALTANTE,
        // M=SOBRANTE, N=COSTO, O=TOTAL SOBRANTE, P=OBSERVACION
        $row = 3;
        foreach ($items as $item) {
            if ($inv->id_almacen) {
                $costo = (float) (DB::table('almacen_producto')
                    ->where('id_almacen', $inv->id_almacen)
                    ->where('id_pro', $item->id_pro)
                    ->value('ap_precio_costo') ?? 0);
            } else {
                /*$costo = (float) (DB::table('producto_sucursal')
                    ->where('id_tienda', $inv->id_tienda)
                    ->where('id_pro', $item->id_pro)
                    ->value('ps_precio_uni') ?? 0);*/
                $costo = (float) (DB::table('almacen_producto')
                    ->where('id_pro', $item->id_pro)
                    ->orderBy('id_ap', 'asc')
                    ->value('ap_precio_costo') ?? 0);
            }

            $stock    = (float) $item->stock_sistema;
            $fisico   = (float) $item->stock_contado;
            $dif      = (float) $item->diferencia;
            $faltante = $dif < 0 ? abs($dif) : 0;
            $sobrante = $dif > 0 ? $dif : 0;

            $data = [
                'A' => $item->pro_codigo,
                'B' => $item->pro_nombre,
                'C' => $stock,
                'D' => $costo,
                'E' => round($stock    * $costo, 2),
                'F' => $fisico,
                'G' => $costo,
                'H' => round($fisico   * $costo, 2),
                'I' => $dif,
                'J' => $faltante,
                'K' => $costo,
                'L' => round($faltante * $costo, 2),
                'M' => $sobrante,
                'N' => $costo,
                'O' => round($sobrante * $costo, 2),
                'P' => '',
            ];

            foreach ($data as $col => $value) {
                $sheet->setCellValue($col . $row, $value);
            }

            if ($dif < 0) {
                $bg = 'FEE2E2';
            } elseif ($dif > 0) {
                $bg = 'FEF9C3';
            } else {
                $bg = null;
            }

            if ($bg) {
                $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                ]);
            }

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(38);
        foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);
        }
        $sheet->getColumnDimension('P')->setWidth(22);

        $numero   = preg_replace('/[^A-Za-z0-9\-]/', '_', $inv->inventario_numero ?? 'inv');
        $filename = "inventario_{$numero}_" . now()->format('Ymd_His') . '.xlsx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['ubicacionKey', 'idTienda', 'buscarProducto', 'resultados', 'items', 'idInventarioActivo']);
        $this->resetErrorBag();
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $almacenes = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', 1)
            ->orderBy('e.empresa_nombrecomercial')
            ->orderBy('a.almacen_nombre')
            ->select('a.id_almacen', 'a.almacen_nombre', 'e.empresa_nombrecomercial')
            ->get();

        $empresas = DB::table('empresa')
            ->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_razon_social')
            ->get();

        $sedes = $this->empresaId()
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaId())
                ->whereIn('tienda_tipo', [1, 2])
                ->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : collect();

        // Datos para vista revisión
        $revisionInventario = null;
        $revisionItems      = collect();
        if ($this->vista === 'revision' && $this->idInventarioActivo) {
            $revisionInventario = DB::table('inventario as i')
                ->leftJoin('almacen as a',   'a.id_almacen', '=', 'i.id_almacen')
                ->leftJoin('tiendas as t',   't.id_tienda',  '=', 'i.id_tienda')
                ->leftJoin('empresa as ea',  'ea.id_empresa', '=', 'a.id_empresa')
                ->leftJoin('empresa as et',  'et.id_empresa', '=', 't.id_empresa')
                ->select(
                    'i.*',
                    DB::raw("COALESCE(a.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                    DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre")
                )
                ->where('i.id_inventario', $this->idInventarioActivo)
                ->first();

            $revisionItems = DB::table('inventario_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_inventario', $this->idInventarioActivo)
                ->select('d.*', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('p.pro_nombre')
                ->get();
        }

        // Datos para modal detalle
        $detalleInventario = null;
        $detalleItems      = collect();
        if ($this->idInventarioDetalle) {
            $detalleInventario = DB::table('inventario as i')
                ->leftJoin('almacen as a',  'a.id_almacen',  '=', 'i.id_almacen')
                ->leftJoin('tiendas as t',  't.id_tienda',   '=', 'i.id_tienda')
                ->leftJoin('empresa as ea', 'ea.id_empresa', '=', 'a.id_empresa')
                ->leftJoin('empresa as et', 'et.id_empresa', '=', 't.id_empresa')
                ->join('users as u',        'u.id_users',    '=', 'i.id_users')
                ->select(
                    'i.*',
                    DB::raw("COALESCE(a.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                    DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre"),
                    'u.nombre_users'
                )
                ->where('i.id_inventario', $this->idInventarioDetalle)
                ->first();

            $detalleItems = DB::table('inventario_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_inventario', $this->idInventarioDetalle)
                ->select('d.*', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('p.pro_nombre')
                ->get();
        }

        // Historial paginado
        $inventarios = DB::table('inventario as i')
            ->leftJoin('almacen as a',  'a.id_almacen',  '=', 'i.id_almacen')
            ->leftJoin('tiendas as t',  't.id_tienda',   '=', 'i.id_tienda')
            ->leftJoin('empresa as ea', 'ea.id_empresa', '=', 'a.id_empresa')
            ->leftJoin('empresa as et', 'et.id_empresa', '=', 't.id_empresa')
            ->join('users as u',        'u.id_users',    '=', 'i.id_users')
            ->select(
                'i.*',
                DB::raw("COALESCE(a.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre"),
                'u.nombre_users',
                DB::raw('(SELECT COUNT(*) FROM inventario_detalle WHERE id_inventario = i.id_inventario) as total_productos'),
                DB::raw('(SELECT COUNT(*) FROM inventario_detalle WHERE id_inventario = i.id_inventario AND diferencia > 0) as total_sobrantes'),
                DB::raw('(SELECT COUNT(*) FROM inventario_detalle WHERE id_inventario = i.id_inventario AND diferencia < 0) as total_faltantes')
            )
            ->when($this->filtroDesde, fn($q) => $q->whereDate('i.inventario_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta, fn($q) => $q->whereDate('i.inventario_fecha', '<=', $this->filtroHasta))
            ->orderByDesc('i.id_inventario')
            ->paginate($this->porPagina);

        return view('livewire.logistica.ajuste-stock', compact(
            'almacenes', 'empresas', 'sedes',
            'revisionInventario', 'revisionItems',
            'detalleInventario', 'detalleItems',
            'inventarios', 'esSuperAdmin', 'esAdmin'
        ));
    }
}
