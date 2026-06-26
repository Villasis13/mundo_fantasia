<?php

namespace App\Livewire\Logistica;

use App\Models\General;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GestionProductos extends Component
{
    use WithPagination, WithFileUploads;

    // ── Formulario — datos base ───────────────────────────────────
    public ?int    $idEditar              = null;
    public ?int    $idFamilia            = null;
    public ?int    $idCa                 = null;
    public ?int    $idMedida             = null;
    public ?int    $idTipoAfectacion     = null;
    public string  $proNombre          = '';
    public string  $proCodigo          = '';
    public string  $proCodigoInterno   = '';
    public string  $proDescripcion     = '';
    public string  $proMarca           = '';
    public bool    $impuestoBolsa      = false;
    public         $proFoto            = null;
    public string  $fotoActual         = '';

    // ── Formulario — costos y precios ─────────────────────────────
    public string  $proCostoBase        = '0';
    public string  $proFlete            = '0';
    public string  $proMargenGanancia   = '0';
    public float   $proCostoTotal       = 0;
    public string  $proPrecioVenta      = '0';
    public string  $proPrecioPublico    = '0';
    public string  $proPrecioMayorista  = '0';

    // ── Empresa / Sucursales ──────────────────────────────────────
    public int   $empresaIdModal = 0;
    public array $configuracion  = [];

    // ── Control modal ─────────────────────────────────────────────
    public bool   $modoEdicion = false;
    public ?int   $idEliminar  = null;
    public ?int   $idDetalle   = null;
    public string $tabActiva   = 'base';

    // ── Filtros y paginación ──────────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'p.id_pro';
    public string $ordenDireccion = 'asc';
    public int    $filtroEmpresa   = 0;
    public int    $filtroFamilia   = 0;
    public int    $filtroCategoria = 0;
    public string $filtroStock     = ''; // '' todos | 'con' con stock | 'sin' sin stock

    // ── Adquisiciones recientes ───────────────────────────────────
    public ?int   $productoSeleccionado    = null;
    public string $productoSeleccionadoNombre = '';
    public array  $adquisicionesCompras    = [];
    public array  $adquisicionesNotas      = [];
    public array  $adquisicionesVentas     = [];

    // ── Series de producto ────────────────────────────────────────
    public array  $seriesProducto          = [];
    public string $nuevaSerie              = '';
    public string $nuevaSerieObservacion   = '';
    public string $errorSerie              = '';
    public string $successSerie            = '';
    public ?int   $editandoSerieId         = null;
    public string $editandoSerieNumero     = '';
    public string $editandoSerieObservacion = '';
    public string $errorEdicionSerie       = '';

    // ── Importación masiva (solo superadmin) ──────────────────────
    public        $archivoImport     = null;
    public array  $importResultado   = [];
    public bool   $importProcesado   = false;
    public string $destinoImportKey  = '';
    public array  $almacenesImport   = [];
    public array  $tiendasImport     = [];

    private ?Logs $logs         = null;
    private int   $cachedRoleId = 0;

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
        abort_if(!auth()->user()->can('gestion_productos.listar'), 403);

        $this->almacenesImport = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', '!=', 0)
            ->select('a.id_almacen', 'a.almacen_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.id_empresa')->orderBy('a.id_almacen')
            ->get()->toArray();

        $this->tiendasImport = DB::table('tiendas as t')
            ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
            ->where('t.tienda_estado', '!=', 0)
            ->whereIn('t.tienda_tipo', [1, 2])
            ->whereNull('t.id_tienda_padre')
            ->select('t.id_tienda', 't.tienda_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.id_empresa')->orderBy('t.id_tienda')
            ->get()->toArray();
    }

    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Validación ────────────────────────────────────────────────
    protected function rules(): array
    {
        $ignore = $this->idEditar ? ",{$this->idEditar},id_pro" : '';

        $rules = [
            'idCa'               => 'required|integer|exists:categorias,id_ca',
            'idMedida'           => 'required|integer|exists:medida,id_medida',
            'idTipoAfectacion'   => 'required|integer|exists:tipo_afectacion,id_tipo_afectacion',
            'proNombre'      => 'required|string|max:255',
            'proCodigo'      => "required|string|max:100|unique:productos,pro_codigo{$ignore}",
            'proMarca'       => 'nullable|string|max:150',
        ];

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'idCa.required'               => 'Selecciona la categoría.',
            'idMedida.required'           => 'Selecciona la unidad de medida.',
            'idTipoAfectacion.required'   => 'Selecciona el tipo de afectación.',
            'proNombre.required'      => 'El nombre del producto es obligatorio.',
            'proCodigo.required'      => 'El código del producto es obligatorio.',
            'proCodigo.unique'        => 'Este código ya está registrado.',
            'empresaIdModal.required' => 'Debes seleccionar una empresa.',
        ];
    }

    // ── Lifecycle hooks ───────────────────────────────────────────
    public function updatedIdFamilia(): void
    {
        $this->idCa = null;
    }

    public function updatedProCostoBase(): void    { $this->recalcularCostoTotal(); }
    public function updatedProFlete(): void         { $this->recalcularCostoTotal(); }
    public function updatedProMargenGanancia(): void { $this->recalcularCostoTotal(); }

    private function recalcularCostoTotal(): void
    {
        $base   = (float) str_replace(',', '.', $this->proCostoBase);
        $flete  = (float) str_replace(',', '.', $this->proFlete);
        $margen = (float) str_replace(',', '.', $this->proMargenGanancia);

        // Costo Total = (Base + Flete) × (1 + Margen% / 100)
        $this->proCostoTotal = round(($base + $flete) * (1 + $margen / 100), 2);
    }

    private function generarCodigoInterno(): string
    {
        $ultimo = DB::table('productos')
            ->where('pro_codigo_interno', 'like', 'PRO-%')
            ->orderByDesc('id_pro')
            ->value('pro_codigo_interno');

        $numero = 1;
        if ($ultimo) {
            $partes  = explode('-', $ultimo);
            $numero  = ((int) end($partes)) + 1;
        }

        return 'PRO-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    // ── Exportar Excel ───────────────────────────────────────────
    public function exportarExcel(): mixed
    {
        if (!auth()->user()->can('gestion_productos.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return null;
        }

        $columna   = in_array($this->ordenColumna, ['p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'c.ca_nombre'])
                     ? $this->ordenColumna : 'p.id_pro';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $rows = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->leftJoin('familias as f', 'f.id_fa', '=', 'c.id_fa')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->selectRaw("p.pro_nombre, p.pro_codigo, p.pro_codigo_interno, p.pro_marca,
                c.ca_nombre, f.fa_nombre, m.medida_nombre,
                COALESCE((SELECT SUM(ap.ap_stock) FROM almacen_producto ap
                           WHERE ap.id_pro = p.id_pro AND ap.ap_estado = 1), 0) as stock_almacen,
                COALESCE((SELECT SUM(ps.ps_stock) FROM producto_sucursal ps
                           WHERE ps.id_pro = p.id_pro AND ps.ps_estado = 1), 0) as stock_sedes")
            ->where('p.pro_estado', 1)
            ->when($this->buscar, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('p.pro_nombre',          'like', "%{$this->buscar}%")
                          ->orWhere('p.pro_codigo',         'like', "%{$this->buscar}%")
                          ->orWhere('p.pro_codigo_interno', 'like', "%{$this->buscar}%")
                          ->orWhere('p.pro_marca',          'like', "%{$this->buscar}%");
                });
            })
            ->when($this->filtroFamilia > 0, fn($q) => $q->where('f.id_fa', $this->filtroFamilia))
            ->when($this->filtroCategoria > 0, fn($q) => $q->where('p.id_ca', $this->filtroCategoria))
            ->when($this->filtroStock === 'con', fn($q) => $q->whereExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)->where('ps2.ps_stock', '>', 0);
            }))
            ->when($this->filtroStock === 'sin', fn($q) => $q->whereNotExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)->where('ps2.ps_stock', '>', 0);
            }))
            ->orderBy($columna, $direccion)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        $headers = ['#', 'Nombre', 'Marca', 'Código', 'Cód. Interno', 'Familia', 'Categoría', 'Unidad', 'Stock Almacén', 'Stock Sedes'];
        $sheet->fromArray([$headers], null, 'A1');
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $dataRows = [];
        foreach ($rows as $idx => $row) {
            $dataRows[] = [
                $idx + 1,
                $row->pro_nombre,
                $row->pro_marca ?? '',
                $row->pro_codigo,
                $row->pro_codigo_interno ?? '',
                $row->fa_nombre ?? '',
                $row->ca_nombre ?? '',
                $row->medida_nombre ?? '',
                (float) $row->stock_almacen,
                (float) $row->stock_sedes,
            ];
        }
        $sheet->fromArray($dataRows, null, 'A2');

        foreach ($rows as $idx => $row) {
            if (((float) $row->stock_almacen + (float) $row->stock_sedes) <= 0) {
                $excelRow = $idx + 2;
                $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FECACA']],
                ]);
            }
        }

        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $filename = 'productos_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $esAdmin        = $this->esAdmin();
        $esSuperAdmin   = $this->esSuperAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;

        $columnasPermitidas = ['p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'c.ca_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'p.id_pro';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $productos = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->leftJoin('familias as f', 'f.id_fa', '=', 'c.id_fa')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'p.id_empresa')
            ->selectRaw('p.id_pro, p.pro_nombre, p.pro_codigo, p.pro_codigo_interno,
                p.pro_marca, p.pro_foto, p.impuesto_bolsa, p.id_empresa, p.id_medida,
                c.ca_nombre, f.fa_nombre, f.id_fa, m.medida_nombre,
                e.empresa_nombrecomercial,
                (SELECT COUNT(*) FROM producto_sucursal ps
                 WHERE ps.id_pro = p.id_pro AND ps.ps_estado = 1) as num_sucursales')
            ->where('p.pro_estado', 1)
            ->when($this->buscar, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('p.pro_nombre',         'like', "%{$this->buscar}%")
                          ->orWhere('p.pro_codigo',        'like', "%{$this->buscar}%")
                          ->orWhere('p.pro_codigo_interno','like', "%{$this->buscar}%")
                          ->orWhere('p.pro_marca',         'like', "%{$this->buscar}%");
                });
            })
            ->when($this->filtroFamilia > 0, fn($q) => $q->where('f.id_fa', $this->filtroFamilia))
            ->when($this->filtroCategoria > 0, fn($q) => $q->where('p.id_ca', $this->filtroCategoria))
            ->when($this->filtroStock === 'con', fn($q) => $q->whereExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)
                    ->where('ps2.ps_stock', '>', 0);
            }))
            ->when($this->filtroStock === 'sin', fn($q) => $q->whereNotExists(function ($sub) {
                $sub->selectRaw(1)->from('producto_sucursal as ps2')
                    ->whereColumn('ps2.id_pro', 'p.id_pro')
                    ->where('ps2.ps_estado', 1)
                    ->where('ps2.ps_stock', '>', 0);
            }))
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $familias = DB::table('familias')
            ->where('fa_estado', 1)
            ->orderBy('fa_nombre')
            ->get();

        $categorias = $this->idFamilia
            ? DB::table('categorias')
                ->where('id_fa', $this->idFamilia)
                ->where('ca_estado', 1)
                ->orderBy('ca_nombre')
                ->get()
            : collect();

        $categoriasFilro = $this->filtroFamilia > 0
            ? DB::table('categorias')
                ->where('id_fa', $this->filtroFamilia)
                ->where('ca_estado', 1)
                ->orderBy('ca_nombre')
                ->get()
            : collect();

        $medidas = DB::table('medida')
            ->where('medida_activo', 1)
            ->orderBy('medida_nombre')
            ->get();

        $tiposAfectacion = DB::table('tipo_afectacion')
            ->orderBy('id_tipo_afectacion')
            ->get();

        $empresas = collect();

        $idsPro = $productos->pluck('id_pro')->all();
        $stockPorProducto = $idsPro
            ? DB::table('producto_sucursal as ps')
                ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
                ->whereIn('ps.id_pro', $idsPro)
                ->where('ps.ps_estado', 1)
                ->select('ps.id_pro', 't.tienda_nombre', 'ps.ps_stock')
                ->orderBy('t.tienda_nombre')
                ->get()
                ->groupBy('id_pro')
            : collect();

        $stockAlmacen = $idsPro
            ? DB::table('almacen_producto as ap')
                ->join('almacen as a', 'a.id_almacen', '=', 'ap.id_almacen')
                ->whereIn('ap.id_pro', $idsPro)
                ->where('ap.ap_estado', 1)
                ->select('ap.id_pro', 'a.almacen_nombre', 'ap.ap_stock')
                ->orderBy('a.almacen_nombre')
                ->get()
                ->groupBy('id_pro')
            : collect();

        $marcas = DB::table('productos')
            ->whereNotNull('pro_marca')
            ->where('pro_marca', '!=', '')
            ->where('pro_estado', 1)
            ->distinct()
            ->orderBy('pro_marca')
            ->pluck('pro_marca');

        $detalleProd         = null;
        $detalleStockTiendas = collect();
        $detalleStockAlmacen = collect();

        if ($this->idDetalle) {
            $detalleProd = DB::table('productos as p')
                ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                ->leftJoin('familias as f', 'f.id_fa', '=', 'c.id_fa')
                ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
                ->where('p.id_pro', $this->idDetalle)
                ->select('p.*', 'c.ca_nombre', 'f.fa_nombre', 'm.medida_nombre')
                ->first();

            $detalleStockTiendas = DB::table('producto_sucursal as ps')
                ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
                ->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
                ->where('ps.id_pro', $this->idDetalle)
                ->where('ps.ps_estado', 1)
                ->select(
                    't.tienda_nombre', 'ps.ps_stock',
                    'ps.ps_precio_uni', 'ps.ps_precio_uni_2', 'ps.ps_precio_uni_3',
                    'ps.ps_stock_minimo', 'ta.descripcion as tipo_afectacion'
                )
                ->orderBy('t.tienda_nombre')
                ->get();

            $detalleStockAlmacen = DB::table('almacen_producto as ap')
                ->join('almacen as a', 'a.id_almacen', '=', 'ap.id_almacen')
                ->where('ap.id_pro', $this->idDetalle)
                ->where('ap.ap_estado', 1)
                ->select('a.almacen_nombre', 'ap.ap_stock')
                ->orderBy('a.almacen_nombre')
                ->get();
        }

        return view('livewire.logistica.gestion-productos', compact(
            'productos', 'familias', 'categorias', 'categoriasFilro', 'medidas', 'tiposAfectacion',
            'empresas', 'esAdmin', 'esSuperAdmin', 'stockPorProducto', 'stockAlmacen',
            'marcas', 'detalleProd', 'detalleStockTiendas', 'detalleStockAlmacen'
        ));
    }

    // ── Ordenar ───────────────────────────────────────────────────
    public function ordenar(string $columna): void
    {
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    // ── Abrir modal nuevo ─────────────────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion      = false;
        $this->proCodigoInterno = $this->generarCodigoInterno();
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ────────────────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();

        $producto = DB::table('productos')->where('id_pro', $id)->first();
        if (!$producto) {
            session()->flash('error', 'Producto no encontrado.');
            return;
        }

        $this->idEditar          = $id;
        $this->idMedida          = (int) $producto->id_medida;
        $this->proNombre         = $producto->pro_nombre;
        $this->proCodigo         = $producto->pro_codigo;
        $this->proCodigoInterno  = $producto->pro_codigo_interno ?? '';
        $this->proDescripcion    = $producto->pro_descripcion ?? '';
        $this->proMarca          = $producto->pro_marca ?? '';
        $this->impuestoBolsa     = (bool) $producto->impuesto_bolsa;
        $this->fotoActual        = $producto->pro_foto ?? '';
        $this->proCostoBase      = (string) ((float) ($producto->pro_costo_base ?? 0));
        $this->proFlete          = (string) ((float) ($producto->pro_flete ?? 0));
        $this->proMargenGanancia = (string) ((float) ($producto->pro_margen_ganancia ?? 0));
        $this->proCostoTotal     = (float) ($producto->pro_costo_total ?? 0);
        $this->proPrecioVenta    = (string) ((float) ($producto->pro_precio_venta ?? 0));

        // Cargar familia → categoría
        if ($producto->id_ca) {
            $this->idCa = (int) $producto->id_ca;
        }
        if (!empty($producto->id_fac)) {
            $this->idFamilia = (int) $producto->id_fac;
        } elseif ($producto->id_ca) {
            $cat = DB::table('categorias')->where('id_ca', $producto->id_ca)->first();
            $this->idFamilia = $cat ? (int) $cat->id_fa : null;
        }

        // Cargar tipo de afectación global (del primer registro de sucursal)
        $primerPs = DB::table('producto_sucursal')
            ->where('id_pro', $id)
            ->whereNotNull('id_tipo_afectacion')
            ->first();
        $this->idTipoAfectacion   = $primerPs ? (int) $primerPs->id_tipo_afectacion : null;
        $this->proPrecioPublico   = (string) ((float) ($primerPs->ps_precio_uni   ?? 0));
        $this->proPrecioMayorista = (string) ((float) ($primerPs->ps_precio_uni_2 ?? 0));

        // Cargar configuración existente por sucursal
        DB::table('producto_sucursal')
            ->where('id_pro', $id)
            ->get()
            ->each(function ($ps) {
                $this->configuracion[(string) $ps->id_tienda] = [
                    'id_tipo_afectacion' => $ps->id_tipo_afectacion,
                    'ps_precio_uni'      => $ps->ps_precio_uni,
                    'ps_precio_uni_2'    => $ps->ps_precio_uni_2,
                    'ps_precio_uni_3'    => $ps->ps_precio_uni_3,
                    'ps_stock_minimo'    => $ps->ps_stock_minimo,
                    'ps_estado'          => $ps->ps_estado,
                ];
            });

        $this->modoEdicion = true;
        $this->dispatch('abrirModal');
    }

    // ── Ver detalle ───────────────────────────────────────────────
    public function verDetalle(int $id): void
    {
        $this->idDetalle = $id;
        $this->dispatch('abrirModalDetalle');
    }

    // ── Adquisiciones recientes del producto seleccionado ─────────
    public function verAdquisicionesRecientes(): void
    {
        if (!$this->productoSeleccionado) return;

        $prod = DB::table('productos')->where('id_pro', $this->productoSeleccionado)->first();
        $this->productoSeleccionadoNombre = $prod ? $prod->pro_nombre : '';

        $this->adquisicionesCompras = DB::table('orden_compra_detalle as ocd')
            ->join('orden_compra as oc', 'oc.id_orden_compra', '=', 'ocd.id_orden_compra')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->where('ocd.id_pro', $this->productoSeleccionado)
            ->where('oc.orden_compra_activo', 1)
            ->select(
                'oc.id_orden_compra',
                'oc.orden_compra_numero',
                'oc.orden_compra_fecha',
                'oc.orden_compra_tipo_doc',
                'oc.orden_compra_numero_doc',
                'oc.orden_compra_guia_transportista',
                'oc.orden_compra_guia_remitente',
                'oc.orden_compra_estado',
                'oc.orden_compra_total',
                'oc.condicion_pago',
                'pv.proveedores_nombre',
                'ocd.detalle_compra_cantidad',
                'ocd.detalle_compra_precio_compra',
                'ocd.detalle_compra_total_pedido'
            )
            ->orderByDesc('oc.orden_compra_fecha')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $idOrdenes = array_column($this->adquisicionesCompras, 'id_orden_compra');

        $this->adquisicionesNotas = empty($idOrdenes) ? [] :
            DB::table('notas_compra as nc')
                ->leftJoin('proveedores as pv', 'pv.id_proveedores', '=', 'nc.id_proveedores')
                ->whereIn('nc.id_orden_compra', $idOrdenes)
                ->where('nc.nota_estado', '!=', 'anulado')
                ->select(
                    'nc.id_nota_compra',
                    'nc.id_orden_compra',
                    'nc.tipo_nota',
                    'nc.nota_numero',
                    'nc.nota_numero_doc',
                    'nc.nota_fecha',
                    'nc.nota_motivo',
                    'nc.nota_total',
                    'nc.nota_estado',
                    'pv.proveedores_nombre'
                )
                ->orderByDesc('nc.nota_fecha')
                ->get()
                ->map(fn($r) => (array) $r)
                ->toArray();

        $this->adquisicionesVentas = DB::table('ventas_detalle as vd')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('users as u', 'u.id_users', '=', 'v.id_users')
            ->where('vd.id_pro', $this->productoSeleccionado)
            ->whereNull('va.id_venta')
            ->select(
                'v.id_venta',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_tipo',
                'v.venta_fecha',
                'v.venta_total',
                'vd.venta_detalle_cantidad',
                'vd.venta_detalle_precio_unitario',
                'vd.venta_detalle_importe_total',
                DB::raw("COALESCE(c.cliente_razonsocial, c.cliente_nombre, 'Sin cliente') as cliente_nombre"),
                DB::raw("COALESCE(c.cliente_numero, '') as cliente_doc"),
                'u.nombre_users'
            )
            ->orderByDesc('v.venta_fecha')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->dispatch('abrirModalAdquisiciones');
    }

    // ── Series de producto ────────────────────────────────────────
    public function abrirModalSeries(): void
    {
        if (!$this->productoSeleccionado) return;
        $this->errorSerie   = '';
        $this->successSerie = '';
        $this->nuevaSerie   = '';
        $this->nuevaSerieObservacion = '';
        $prod = DB::table('productos')->where('id_pro', $this->productoSeleccionado)->first();
        $this->productoSeleccionadoNombre = $prod ? $prod->pro_nombre : '';
        $this->cargarSeries();
        $this->dispatch('abrirModalSeries');
    }

    private function cargarSeries(): void
    {
        $this->seriesProducto = DB::table('producto_series as ps')
            ->leftJoin('ventas as v', 'v.id_venta', '=', 'ps.id_venta')
            ->leftJoin('users as u', 'u.id_users', '=', 'ps.id_users')
            ->where('ps.id_pro', $this->productoSeleccionado)
            ->select(
                'ps.id_producto_serie',
                'ps.numero_serie',
                'ps.estado',
                'ps.observacion',
                'ps.created_at',
                'v.venta_serie',
                'v.venta_correlativo',
                'u.nombre_users'
            )
            ->orderByDesc('ps.created_at')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function registrarSerie(): void
    {
        $this->errorSerie   = '';
        $this->successSerie = '';

        $serie = trim($this->nuevaSerie);
        if ($serie === '') {
            $this->errorSerie = 'El número de serie es obligatorio.';
            return;
        }

        $existe = DB::table('producto_series')
            ->where('id_pro', $this->productoSeleccionado)
            ->where('numero_serie', $serie)
            ->exists();

        if ($existe) {
            $this->errorSerie = "La serie «{$serie}» ya está registrada para este producto.";
            return;
        }

        DB::table('producto_series')->insert([
            'id_pro'       => $this->productoSeleccionado,
            'numero_serie' => $serie,
            'estado'       => 1,
            'observacion'  => trim($this->nuevaSerieObservacion) ?: null,
            'id_users'     => auth()->user()->id_users,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->nuevaSerie             = '';
        $this->nuevaSerieObservacion  = '';
        $this->successSerie           = "Serie «{$serie}» registrada correctamente.";
        $this->cargarSeries();
    }

    public function darDeBajaSerie(int $idSerie): void
    {
        DB::table('producto_series')
            ->where('id_producto_serie', $idSerie)
            ->where('estado', 1)
            ->update(['estado' => 0, 'updated_at' => now()]);
        $this->cargarSeries();
    }

    public function iniciarEdicionSerie(int $idSerie): void
    {
        $s = DB::table('producto_series')->where('id_producto_serie', $idSerie)->first();
        if (!$s) return;
        $this->editandoSerieId          = $idSerie;
        $this->editandoSerieNumero      = $s->numero_serie;
        $this->editandoSerieObservacion = $s->observacion ?? '';
        $this->errorEdicionSerie        = '';
        $this->successSerie             = '';
    }

    public function cancelarEdicionSerie(): void
    {
        $this->editandoSerieId          = null;
        $this->editandoSerieNumero      = '';
        $this->editandoSerieObservacion = '';
        $this->errorEdicionSerie        = '';
    }

    public function guardarEdicionSerie(): void
    {
        $this->errorEdicionSerie = '';
        $numero = trim($this->editandoSerieNumero);

        if ($numero === '') {
            $this->errorEdicionSerie = 'El número de serie no puede estar vacío.';
            return;
        }

        $duplicado = DB::table('producto_series')
            ->where('id_pro', $this->productoSeleccionado)
            ->where('numero_serie', $numero)
            ->where('id_producto_serie', '!=', $this->editandoSerieId)
            ->exists();

        if ($duplicado) {
            $this->errorEdicionSerie = "La serie «{$numero}» ya existe para este producto.";
            return;
        }

        DB::table('producto_series')
            ->where('id_producto_serie', $this->editandoSerieId)
            ->update([
                'numero_serie' => $numero,
                'observacion'  => trim($this->editandoSerieObservacion) ?: null,
                'updated_at'   => now(),
            ]);

        $this->successSerie    = "Serie actualizada correctamente.";
        $this->editandoSerieId = null;
        $this->cargarSeries();
    }

    // ── Confirmar / Eliminar ──────────────────────────────────────
    public function confirmarEliminar(int $id): void
    {
        $this->idEliminar = $id;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar(): void
    {
        if (!auth()->user()->can('gestion_productos.cambiar_estado')) {
            $this->dispatch('cerrarModalEliminar');
            session()->flash('error', 'No tienes permiso para desactivar productos.');
            return;
        }

        try {
            DB::table('productos')
                ->where('id_pro', $this->idEliminar)
                ->update(['pro_estado' => 0, 'updated_at' => now()]);
            $this->idEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al eliminar el producto.');
        }
    }

    // ── Guardar ───────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_productos.actualizar' : 'gestion_productos.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        $idEmpresa = null;

        DB::beginTransaction();
        try {
            $this->recalcularCostoTotal();

            $datosBase = [
                'id_empresa'          => $idEmpresa,
                'id_ca'               => $this->idCa,
                'id_fac'              => $this->idFamilia,
                'id_medida'           => $this->idMedida,
                'pro_nombre'          => $this->proNombre,
                'pro_codigo'          => $this->proCodigo,
                'pro_codigo_interno'  => $this->proCodigoInterno,
                'pro_descripcion'     => null,
                'pro_marca'           => $this->proMarca ?: null,
                'pro_foto'            => null,
                'impuesto_bolsa'      => $this->impuestoBolsa ? 1 : 0,
                'pro_costo_base'      => (float) str_replace(',', '.', $this->proCostoBase),
                'pro_flete'           => (float) str_replace(',', '.', $this->proFlete),
                'pro_margen_ganancia' => (float) str_replace(',', '.', $this->proMargenGanancia),
                'pro_costo_total'     => $this->proCostoTotal,
                'pro_precio_venta'    => (float) str_replace(',', '.', $this->proPrecioVenta),
                'updated_at'          => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('productos')->where('id_pro', $this->idEditar)->update($datosBase);
                $idPro = $this->idEditar;
                DB::table('producto_sucursal')
                    ->where('id_pro', $idPro)
                    ->update(['id_tipo_afectacion' => $this->idTipoAfectacion, 'updated_at' => now()]);
            } else {
                $datosBase['pro_estado'] = 1;
                $datosBase['created_at'] = now();
                $idPro = DB::table('productos')->insertGetId($datosBase);

                // Auto-asignar a todas las sedes activas
                $tiendasActivas = DB::table('tiendas')
                    ->where('tienda_estado', 1)
                    ->pluck('id_tienda');

                foreach ($tiendasActivas as $idTienda) {
                    DB::table('producto_sucursal')->insert([
                        'id_pro'             => $idPro,
                        'id_tienda'          => $idTienda,
                        'id_tipo_afectacion' => $this->idTipoAfectacion,
                        'ps_precio_uni'      => (float) str_replace(',', '.', $this->proPrecioPublico),
                        'ps_precio_uni_2'    => (float) str_replace(',', '.', $this->proPrecioMayorista),
                        'ps_precio_uni_3'    => 0,
                        'ps_stock'           => 0,
                        'ps_stock_minimo'    => 0,
                        'ps_porcen_igv'      => 18,
                        'ps_estado'          => 1,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }

            // Guardar configuración por sede (edición manual si aplica)
            foreach ($this->configuracion as $idTiendaKey => $cfg) {
                $idTiendaInt = (int) $idTiendaKey;
                if ($idTiendaInt <= 0) continue;
                $updateData = [
                    'id_tipo_afectacion' => $cfg['id_tipo_afectacion'] ?: null,
                    'ps_precio_uni'      => (float) ($cfg['ps_precio_uni'] ?? 0),
                    'ps_precio_uni_2'    => (float) ($cfg['ps_precio_uni_2'] ?? 0),
                    'ps_precio_uni_3'    => (float) ($cfg['ps_precio_uni_3'] ?? 0),
                    'ps_stock_minimo'    => (float) ($cfg['ps_stock_minimo'] ?? 10),
                    'ps_porcen_igv'      => 18,
                    'ps_estado'          => !empty($cfg['ps_estado']) ? 1 : 0,
                    'updated_at'         => now(),
                ];

                $existe = DB::table('producto_sucursal')
                    ->where('id_pro', $idPro)
                    ->where('id_tienda', $idTiendaInt)
                    ->exists();

                if ($existe) {
                    DB::table('producto_sucursal')
                        ->where('id_pro', $idPro)
                        ->where('id_tienda', $idTiendaInt)
                        ->update($updateData);
                } else {
                    DB::table('producto_sucursal')->insert(array_merge($updateData, [
                        'id_pro'     => $idPro,
                        'id_tienda'  => $idTiendaInt,
                        'ps_stock'   => 0,
                        'created_at' => now(),
                    ]));
                }
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Producto actualizado correctamente.'
                : 'Producto creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el producto.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->proFoto = null;
        $this->reset([
            'idEditar', 'idFamilia', 'idCa', 'idMedida', 'idTipoAfectacion',
            'proNombre', 'proCodigo', 'proCodigoInterno', 'proDescripcion', 'proMarca', 'impuestoBolsa',
            'fotoActual', 'empresaIdModal', 'configuracion',
            'modoEdicion', 'idEliminar', 'tabActiva',
            'proCostoBase', 'proFlete', 'proMargenGanancia', 'proCostoTotal', 'proPrecioVenta',
            'proPrecioPublico', 'proPrecioMayorista',
        ]);
        $this->tabActiva = 'base';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void          { $this->resetPage(); }
    public function updatingPorPagina(): void       { $this->resetPage(); }
    public function updatingFiltroEmpresa(): void   { $this->resetPage(); }
    public function updatingFiltroCategoria(): void { $this->resetPage(); }
    public function updatingFiltroStock(): void     { $this->resetPage(); }
    public function updatingFiltroFamilia(): void
    {
        $this->filtroCategoria = 0;
        $this->resetPage();
    }

    // ── Modal importar ────────────────────────────────────────────
    public function abrirModalImport(): void
    {
        if (!$this->esSuperAdmin()) return;
        $this->archivoImport    = null;
        $this->importResultado  = [];
        $this->importProcesado  = false;
        $this->destinoImportKey = '';
        $this->resetErrorBag(['archivoImport', 'destinoImportKey']);
        $this->dispatch('abrirModalImport');
    }

    public function cerrarModalImport(): void
    {
        $this->archivoImport    = null;
        $this->importResultado  = [];
        $this->importProcesado  = false;
        $this->destinoImportKey = '';
        $this->resetErrorBag(['archivoImport', 'destinoImportKey']);
        $this->dispatch('cerrarModalImport');
    }

    public function importarExcel(): void
    {
        if (!$this->esSuperAdmin()) {
            session()->flash('error', 'No tienes permiso para importar productos.');
            return;
        }

        $this->validate(
            [
                'destinoImportKey' => 'required|string|min:1',
                'archivoImport'    => 'required|file|mimes:xlsx,xls|max:10240',
            ],
            [
                'destinoImportKey.required' => 'Selecciona el destino de la importación.',
                'destinoImportKey.min'      => 'Selecciona el destino de la importación.',
                'archivoImport.required'    => 'Selecciona un archivo Excel.',
                'archivoImport.mimes'       => 'El archivo debe ser .xlsx o .xls.',
                'archivoImport.max'         => 'El archivo no debe superar los 10 MB.',
            ]
        );

        // Parsear destino: 'almacen_2' o 'tienda_5'
        [$tipoDestino, $idDestino] = explode('_', $this->destinoImportKey, 2);
        $idDestino = (int) $idDestino;

        if (!in_array($tipoDestino, ['almacen', 'tienda']) || $idDestino < 1) {
            $this->addError('destinoImportKey', 'Destino inválido.');
            return;
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $this->archivoImport->getRealPath()
            );

            $hoja = $spreadsheet->getActiveSheet();
            $rows = $hoja->toArray(null, true, true, false);

            // Validar las 11 cabeceras obligatorias
            $esperadas = ['CODIGO','PRODUCTO','MARCA','UNIDAD_MEDIDA','STOCK_INICIAL','STOCK',
                          'PRECIO_PUBLICO','PRECIO_MAYOR','PRECIO_ESPECIAL','COSTO','VALOR_INVENTARIO'];
            $cabeceras = array_map(fn($h) => strtoupper(trim((string)($h ?? ''))), $rows[0] ?? []);
            foreach ($esperadas as $i => $col) {
                if (($cabeceras[$i] ?? '') !== $col) {
                    $this->addError('archivoImport',
                        "Columna ".($i+1)." debe ser '$col', se encontró '".($cabeceras[$i] ?? 'vacío')."'.");
                    return;
                }
            }

            DB::beginTransaction();

            $proCreados      = 0;
            $proActualizados = 0;
            $proOmitidos     = 0;

            // Cache medidas por nombre
            $medidaCache = [];
            DB::table('medida')->get()->each(function ($m) use (&$medidaCache) {
                $medidaCache[mb_strtolower(trim($m->medida_nombre))] = (int) $m->id_medida;
            });

            // Todas las tiendas activas (para registrar el producto en todas)
            $tiendasActivas = DB::table('tiendas')
                ->where('tienda_estado', '!=', 0)
                ->pluck('id_tienda');

            // Códigos existentes
            $codExistentes = DB::table('productos')
                ->pluck('id_pro', 'pro_codigo')
                ->toArray();

            foreach (array_slice($rows, 1) as $row) {
                $codigo         = trim((string) ($row[0] ?? ''));
                $nombre         = trim((string) ($row[1] ?? ''));
                $marca          = trim((string) ($row[2] ?? ''));
                $medidaNombre   = mb_strtolower(trim((string) ($row[3] ?? '')));
                $stockInicial   = (float) str_replace(',', '.', $row[4] ?? 0);
                $stock          = (float) str_replace(',', '.', $row[5] ?? 0);
                $precioPublico  = (float) str_replace(',', '.', $row[6] ?? 0);
                $precioMayor    = (float) str_replace(',', '.', $row[7] ?? 0);
                $precioEspecial = (float) str_replace(',', '.', $row[8] ?? 0);
                $costo          = (float) str_replace(',', '.', $row[9] ?? 0);
                // columna K (VALOR_INVENTARIO) ignorada — es STOCK × COSTO

                if (!$codigo || !$nombre) {
                    $proOmitidos++;
                    continue;
                }

                $idMedida = $medidaCache[$medidaNombre] ?? 58; // default: UNIDAD (BIENES)

                [$idFac, $idCa] = $this->resolverFamiliaCategoriaPorNombre($nombre);

                if (isset($codExistentes[$codigo])) {
                    // Producto ya existe → actualizar datos base y stock del destino seleccionado
                    $idPro = $codExistentes[$codigo];

                    DB::table('productos')->where('id_pro', $idPro)->update([
                        'pro_marca'      => $marca ?: null,
                        'pro_costo_base' => $costo,
                        'id_medida'      => $idMedida,
                        'id_ca'          => $idCa,
                        'id_fac'         => $idFac,
                        'updated_at'     => now(),
                    ]);

                    if ($tipoDestino === 'almacen') {
                        $ap = DB::table('almacen_producto')
                            ->where('id_pro', $idPro)->where('id_almacen', $idDestino)->first();
                        if ($ap) {
                            DB::table('almacen_producto')->where('id_ap', $ap->id_ap)->update([
                                'ap_stock'        => $stock,
                                'ap_precio_costo' => $costo,
                                'updated_at'      => now(),
                            ]);
                        } else {
                            DB::table('almacen_producto')->insert([
                                'id_almacen'      => $idDestino,
                                'id_pro'          => $idPro,
                                'id_orden_compra' => null,
                                'ap_stock'        => $stock,
                                'ap_precio_costo' => $costo,
                                'ap_estado'       => 1,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ]);
                        }
                    } else {
                        $ps = DB::table('producto_sucursal')
                            ->where('id_pro', $idPro)->where('id_tienda', $idDestino)->first();
                        if ($ps) {
                            DB::table('producto_sucursal')->where('id_ps', $ps->id_ps)->update([
                                'ps_precio_uni'   => $precioPublico,
                                'ps_precio_uni_2' => $precioMayor,
                                'ps_precio_uni_3' => $precioEspecial,
                                'ps_stock'        => $stock,
                                'updated_at'      => now(),
                            ]);
                        } else {
                            DB::table('producto_sucursal')->insert([
                                'id_pro'             => $idPro,
                                'id_tienda'          => $idDestino,
                                'id_tipo_afectacion' => 1,
                                'ps_precio_uni'      => $precioPublico,
                                'ps_precio_uni_2'    => $precioMayor,
                                'ps_precio_uni_3'    => $precioEspecial,
                                'ps_stock'           => $stock,
                                'ps_stock_minimo'    => 0,
                                'ps_porcen_igv'      => 18,
                                'ps_estado'          => 1,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ]);
                        }
                    }

                    $proActualizados++;
                } else {
                    // Producto nuevo → crear con todos los datos
                    $codigoInterno = $this->generarCodigoInterno();
                    $idPro = DB::table('productos')->insertGetId([
                        'id_empresa'          => null,
                        'id_ca'               => $idCa,
                        'id_fac'              => $idFac,
                        'id_medida'           => $idMedida,
                        'pro_nombre'          => $nombre,
                        'pro_codigo'          => $codigo,
                        'pro_codigo_interno'  => $codigoInterno,
                        'pro_descripcion'     => null,
                        'pro_marca'           => $marca ?: null,
                        'pro_stock_inicial'   => $stockInicial,
                        'pro_foto'            => null,
                        'impuesto_bolsa'      => 0,
                        'pro_costo_base'      => $costo,
                        'pro_flete'           => 0,
                        'pro_margen_ganancia' => 0,
                        'pro_costo_total'     => $costo,
                        'pro_precio_venta'    => $precioPublico,
                        'pro_estado'          => 1,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                    $codExistentes[$codigo] = $idPro;

                    if ($tipoDestino === 'almacen') {
                        // Stock real en el almacén seleccionado
                        DB::table('almacen_producto')->insert([
                            'id_almacen'      => $idDestino,
                            'id_pro'          => $idPro,
                            'id_orden_compra' => null,
                            'ap_stock'        => $stock,
                            'ap_precio_costo' => $costo,
                            'ap_estado'       => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                        // Registrar en todas las tiendas con stock 0
                        foreach ($tiendasActivas as $idTienda) {
                            DB::table('producto_sucursal')->insert([
                                'id_pro'             => $idPro,
                                'id_tienda'          => $idTienda,
                                'id_tipo_afectacion' => 1,
                                'ps_precio_uni'      => $precioPublico,
                                'ps_precio_uni_2'    => $precioMayor,
                                'ps_precio_uni_3'    => $precioEspecial,
                                'ps_stock'           => 0,
                                'ps_stock_minimo'    => 0,
                                'ps_porcen_igv'      => 18,
                                'ps_estado'          => 1,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ]);
                        }
                    } else {
                        // Registrar en todas las tiendas; solo la seleccionada recibe stock real
                        foreach ($tiendasActivas as $idTienda) {
                            $esSel = (int)$idTienda === $idDestino;
                            DB::table('producto_sucursal')->insert([
                                'id_pro'             => $idPro,
                                'id_tienda'          => $idTienda,
                                'id_tipo_afectacion' => 1,
                                'ps_precio_uni'      => $esSel ? $precioPublico  : 0,
                                'ps_precio_uni_2'    => $esSel ? $precioMayor    : 0,
                                'ps_precio_uni_3'    => $esSel ? $precioEspecial : 0,
                                'ps_stock'           => $esSel ? $stock          : 0,
                                'ps_stock_minimo'    => 0,
                                'ps_porcen_igv'      => 18,
                                'ps_estado'          => 1,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ]);
                        }
                    }

                    $proCreados++;
                }
            }

            DB::commit();

            $this->importResultado = [
                'creados'      => $proCreados,
                'actualizados' => $proActualizados,
                'omitidos'     => $proOmitidos,
            ];
            $this->importProcesado = true;
            $this->archivoImport   = null;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->addError('archivoImport', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    // ── Clasificación automática por nombre ───────────────────────
    private function resolverFamiliaCategoriaPorNombre(string $nombre): array
    {
        $n = mb_strtolower($nombre);

        // Audio y sonido
        if (preg_match('/audif[oó]n|auricular/', $n))                              return [21, 115];
        if (preg_match('/parlante|bocina|altavoz/', $n))                            return [21, 116];
        if (preg_match('/micro[fó]fono/', $n))                                      return [21, 117];
        if (preg_match('/radio/', $n))                                              return [21, 118];

        // Bebé e infantil
        if (preg_match('/bib[eé]r[oó]n/', $n))                                     return [22, 122];
        if (preg_match('/andador/', $n))                                            return [22, 120];
        if (preg_match('/ba[ñn]era.*infantil|tina.*beb[eé]/', $n))                  return [22, 121];
        if (preg_match('/beb[eé]|infantil|reci[eé]n nacid/', $n))                   return [22, 119];

        // Belleza y cuidado personal
        if (preg_match('/shampoo|champ[uú]|acondicionador|tinte.*cabello/', $n))    return [23, 124];
        if (preg_match('/maquillaje|l[aá]piz labial|lip|rubor|base cosmeti/', $n))  return [23, 125];
        if (preg_match('/jab[oó]n|desodorante|loción|perfume|colonia|crema corporal/', $n)) return [23, 126];
        if (preg_match('/rasuradora|afeitadora|navaja|crema.*afeitar|barb/', $n))   return [23, 127];
        if (preg_match('/peineta|cepillo.*cabello|rizador|plancha.*cabello|secadora/', $n)) return [23, 128];

        // Cocina y menaje
        if (preg_match('/olla/', $n))                                               return [24, 135];
        if (preg_match('/sart[eé]n/', $n))                                          return [24, 136];
        if (preg_match('/vaso|copa/', $n))                                          return [24, 130];
        if (preg_match('/plato|vajilla/', $n))                                      return [24, 131];
        if (preg_match('/taza|taz[oó]n/', $n))                                      return [24, 132];
        if (preg_match('/bowl|ensaladera/', $n))                                    return [24, 133];
        if (preg_match('/jarra|termo/', $n))                                        return [24, 134];
        if (preg_match('/t[aá]per|tupper|recipiente.*cocina/', $n))                 return [24, 137];
        if (preg_match('/cuchillo|tenedor|cuchara|cubierto|cuchar[oó]n|esp[aá]tula/', $n)) return [24, 138];
        if (preg_match('/utensilio.*cocina|cocina/', $n))                           return [24, 129];

        // Comercio y empaque
        if (preg_match('/bolsa/', $n))                                              return [25, 139];
        if (preg_match('/empaque|embalaje|cinta.*embalaje/', $n))                   return [25, 140];

        // Decoración y temporada
        if (preg_match('/navidad|navide[ñn]|[aá]rbol.*navidad|noel/', $n))          return [26, 142];
        if (preg_match('/globo|serpentina|fiesta|cumplea[ñn]os|confeti/', $n))      return [26, 143];
        if (preg_match('/flor.*artificial|planta.*artificial/', $n))                return [26, 144];
        if (preg_match('/regalo|envoltura|lazo.*regalo/', $n))                      return [26, 145];
        if (preg_match('/adorno|decoraci[oó]n|figur[ií]n/', $n))                    return [26, 141];

        // Electricidad e iluminación
        if (preg_match('/foco|bombilla|bombillo/', $n))                             return [27, 146];
        if (preg_match('/\bled\b|tira.*led/', $n))                                  return [27, 147];
        if (preg_match('/linterna/', $n))                                           return [27, 148];
        if (preg_match('/tomacorriente/', $n))                                      return [27, 149];
        if (preg_match('/enchufe|interruptor|apagador/', $n))                       return [27, 150];
        if (preg_match('/extensi[oó]n.*el[eé]ctrica|zapatilla.*el[eé]ctrica/', $n)) return [27, 151];
        if (preg_match('/cinta.*aislar|tapas.*toma|accesorio.*el[eé]ctrico/', $n))  return [27, 152];

        // Electrodomésticos
        if (preg_match('/licuadora/', $n))                                          return [28, 153];
        if (preg_match('/ventilador/', $n))                                         return [28, 154];
        if (preg_match('/plancha/', $n))                                            return [28, 155];
        if (preg_match('/horno|microondas/', $n))                                   return [28, 156];
        if (preg_match('/hervidor|kettle/', $n))                                    return [28, 157];
        if (preg_match('/estufa|hornilla/', $n))                                    return [28, 158];
        if (preg_match('/cafetera|exprimidor|sandwichera|tostadora/', $n))          return [28, 159];

        // Ferretería y herramientas
        if (preg_match('/alicate|pinza/', $n))                                      return [29, 161];
        if (preg_match('/martillo/', $n))                                           return [29, 162];
        if (preg_match('/wincha|cinta.*medir/', $n))                                return [29, 163];
        if (preg_match('/bisagra|manija|jalador/', $n))                             return [29, 164];
        if (preg_match('/silicona|pegamento|adhesivo|sellador/', $n))               return [29, 165];
        if (preg_match('/niple|codo.*plomería|uni[oó]n.*plomería|gasfiter/', $n))   return [29, 166];
        if (preg_match('/desarmador|destornillador|llave.*inglesa|herramienta/', $n)) return [29, 160];

        // Juguetería
        if (preg_match('/mu[ñn]ec[ao]/', $n))                                       return [30, 168];
        if (preg_match('/carro.*juguete|coche.*juguete|veh[ií]culo.*juguete/', $n)) return [30, 169];
        if (preg_match('/pelota|balón/', $n))                                       return [30, 170];
        if (preg_match('/did[áa]ctico|puzzle|rompecabeza/', $n))                    return [30, 171];
        if (preg_match('/slime|masa.*modelar/', $n))                                return [30, 172];
        if (preg_match('/juguete/', $n))                                            return [30, 167];

        // Limpieza y hogar
        if (preg_match('/balde/', $n))                                              return [31, 173];
        if (preg_match('/batea|tina/', $n))                                         return [31, 174];
        if (preg_match('/escoba|escobill[oó]n/', $n))                               return [31, 175];
        if (preg_match('/trapeador|mopa\b/', $n))                                   return [31, 176];
        if (preg_match('/recogedor/', $n))                                          return [31, 177];
        if (preg_match('/tacho|basurero|papelera/', $n))                            return [31, 178];
        if (preg_match('/pa[ñn]o|esponja/', $n))                                    return [31, 179];
        if (preg_match('/cepillo.*limpiar|rascador/', $n))                          return [31, 180];
        if (preg_match('/lejía|detergente|limpiador|quitagrasa|desinfectante/', $n)) return [31, 181];

        // Mascotas
        if (preg_match('/mascota|perro|gato|hamster|conejo|pez\b|acuario/', $n))    return [32, 182];

        // Moda y accesorios
        if (preg_match('/mochila|morral/', $n))                                     return [33, 183];
        if (preg_match('/cartera/', $n))                                            return [33, 184];
        if (preg_match('/billetera|monedero/', $n))                                 return [33, 185];
        if (preg_match('/lentes|gafas|anteojos/', $n))                              return [33, 186];
        if (preg_match('/reloj/', $n))                                              return [33, 187];
        if (preg_match('/gorra|sombrero|gorro/', $n))                               return [33, 188];
        if (preg_match('/pulsera|collar|aretes|aro\b|anillo|bisuter[ií]a/', $n))    return [33, 189];
        if (preg_match('/zapato|sandalia|zapatilla|calzado|bota/', $n))             return [33, 190];
        if (preg_match('/cintur[oó]n|correa/', $n))                                 return [33, 191];

        // Muebles y exteriores
        if (preg_match('/\bmesa\b/', $n))                                           return [34, 192];
        if (preg_match('/\bsilla\b|sill[oó]n/', $n))                               return [34, 193];
        if (preg_match('/coj[ií]n/', $n))                                           return [34, 194];
        if (preg_match('/mueble|estante|repisa/', $n))                              return [34, 195];

        // Organización y almacenamiento
        if (preg_match('/caja.*organiz/', $n))                                      return [35, 196];
        if (preg_match('/canasta/', $n))                                            return [35, 197];
        if (preg_match('/cesto/', $n))                                              return [35, 198];
        if (preg_match('/colgador|gancho/', $n))                                    return [35, 199];
        if (preg_match('/organizador/', $n))                                        return [35, 200];

        // Papelería y oficina
        if (preg_match('/l[aá]piz|lapicero|bol[ií]grafo|pluma/', $n))               return [36, 202];
        if (preg_match('/papel\b|cartulina|cartón/', $n))                           return [36, 203];
        if (preg_match('/tajador|sacapuntas/', $n))                                 return [36, 204];
        if (preg_match('/cuaderno|libreta|agenda/', $n))                            return [36, 205];
        if (preg_match('/pintura|t[eé]mpera|acuarela/', $n))                        return [36, 206];
        if (preg_match('/tijeras|regla|compás|corrector|borrador|grapas|folder|archivador/', $n)) return [36, 201];

        // Ropa y textil
        if (preg_match('/medias|calc[eé]t[ií]n/', $n))                              return [37, 208];
        if (preg_match('/calz[oó]n|b[oó]xer|bra\b|brasier|ropa interior/', $n))    return [37, 209];
        if (preg_match('/s[aá]bana|funda|cobertor|cubrecama|manta/', $n))           return [37, 210];
        if (preg_match('/toalla/', $n))                                             return [37, 211];
        if (preg_match('/polo|camisa|pantalón|short|vestido|falda|chompa/', $n))    return [37, 207];

        // Seguridad
        if (preg_match('/candado/', $n))                                            return [38, 212];
        if (preg_match('/cerradura|chapa/', $n))                                    return [38, 213];
        if (preg_match('/cerrajo/', $n))                                            return [38, 214];
        if (preg_match('/cadena/', $n))                                             return [38, 215];

        // Tecnología y conectividad
        if (preg_match('/cable.*usb|cable.*hdmi|cable.*datos|cable.*carga/', $n))   return [39, 216];
        if (preg_match('/cargador/', $n))                                           return [39, 217];
        if (preg_match('/adaptador/', $n))                                          return [39, 218];
        if (preg_match('/control.*remoto/', $n))                                    return [39, 219];
        if (preg_match('/case.*celular|funda.*celular|protector.*pantalla|vidrio.*templado/', $n)) return [39, 220];
        if (preg_match('/\busb\b|memoria.*flash|microsd|disco.*duro/', $n))         return [39, 221];
        if (preg_match('/power.*bank|batería.*externa/', $n))                       return [39, 222];

        // Medicamentos
        if (preg_match('/jarabe|jarabe|sirop/', $n))                                return [42, 229];
        if (preg_match('/pastilla|tableta|c[aá]psula/', $n))                        return [42, 230];

        // Misceláneos específicos
        if (preg_match('/balanza/', $n))                                            return [40, 224];
        if (preg_match('/botiquín|bot[ií]quin|curita|venda/', $n))                  return [40, 225];
        if (preg_match('/encendedor|f[oó]sforo/', $n))                              return [40, 226];
        if (preg_match('/atomizador|aspersor/', $n))                                return [40, 227];
        if (preg_match('/aceite|lubricante/', $n))                                  return [40, 228];

        // Default: Misceláneos > Productos varios
        return [40, 223];
    }
}
