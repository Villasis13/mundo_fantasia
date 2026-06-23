<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Autoconsumo extends Component
{
    use WithPagination;

    public string $vista = 'historial';

    // ── Ubicación ─────────────────────────────────────────────────
    public string $ubicacionKey = '';
    public int    $idTienda     = 0;

    // ── Formulario ────────────────────────────────────────────────
    public string $area          = 'Administración';
    public string $autorizacion  = '';
    public string $fechaEmision  = '';

    // ── Búsqueda de productos ─────────────────────────────────────
    public string $buscarProducto = '';
    public array  $resultados     = [];
    public array  $items          = [];

    // ── Selector de presentaciones ────────────────────────────────
    public array $presentacionesPendientes = [];
    public array $productoPendienteData    = [];

    // ── Revisión / detalle ────────────────────────────────────────
    public ?int $idAutoconsumoActivo  = null;
    public ?int $idAutoconsumoDetalle = null;

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
        $this->filtroDesde  = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta  = now()->format('Y-m-d');
        $this->fechaEmision = now()->format('Y-m-d');
        $this->autoResolverUbicacion();
    }

    private function autoResolverUbicacion(): void
    {
        $empresa = DB::table('empresa')
            ->where('empresa_estado', '!=', 0)
            ->orderBy('id_empresa')
            ->first();
        if (!$empresa) return;

        $this->ubicacionKey = 'empresa_' . $empresa->id_empresa;

        $tienda = DB::table('tiendas')
            ->where('id_empresa', $empresa->id_empresa)
            ->where('tienda_estado', 1)
            ->orderBy('id_tienda')
            ->first();
        if ($tienda) {
            $this->idTienda = $tienda->id_tienda;
        }
    }

    // ── Helpers ubicación ─────────────────────────────────────────
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
                         DB::raw('ap.ap_stock as stock_actual'),
                         DB::raw('ap.ap_precio_costo as costo'))
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
                         DB::raw('ps.ps_stock as stock_actual'),
                         DB::raw('ps.ps_precio_uni as costo'))
                ->orderBy('p.pro_nombre')->limit(10)->get()->toArray();
        }
    }

    // ── Formulario ────────────────────────────────────────────────
    public function verificarPresentaciones(int $idPro, string $nombre, string $codigo, float $stockRaw, float $costo): void
    {
        foreach ($this->items as $item) {
            if ((int) $item['id_pro'] === $idPro) return;
        }

        $presentaciones = DB::table('producto_presentaciones')
            ->where('id_pro', $idPro)
            ->where('pres_estado', 1)
            ->orderBy('pres_factor')
            ->get();

        if ($presentaciones->count() === 0) {
            $this->agregarProducto($idPro, $nombre, $codigo, $stockRaw, $costo);
            return;
        }

        if ($presentaciones->count() === 1) {
            $pres   = $presentaciones->first();
            $factor = (float) $pres->pres_factor;
            if ($factor > 0 && $stockRaw < $factor) {
                session()->flash('error', 'Stock insuficiente para "' . $nombre . '" en presentación ' . $pres->pres_nombre . '. Hay ' . (int)$stockRaw . ' uds. y la presentación requiere ' . (int)$factor . '.');
                return;
            }
            $this->agregarProducto($idPro, $nombre, $codigo, $stockRaw, $costo, $pres->pres_nombre, $factor > 0 ? $factor : 1.0);
            return;
        }

        $this->productoPendienteData    = compact('idPro', 'nombre', 'codigo', 'stockRaw', 'costo');
        $this->presentacionesPendientes = $presentaciones->map(fn($p) => [
            'id_pres'     => (int)   $p->id_pres,
            'pres_nombre' => $p->pres_nombre,
            'pres_factor' => (float) $p->pres_factor,
            'stock_pres'  => $p->pres_factor > 0 ? round($stockRaw / $p->pres_factor, 2) : $stockRaw,
        ])->toArray();
        $this->dispatch('abrirModalPresentacionesAutoconsumo');
    }

    public function seleccionarPresentacion(int $idPres): void
    {
        $pres = collect($this->presentacionesPendientes)->firstWhere('id_pres', $idPres);
        if (!$pres || empty($this->productoPendienteData)) return;

        $d      = $this->productoPendienteData;
        $factor = (float) $pres['pres_factor'];

        if ($factor > 0 && (float) $d['stockRaw'] < $factor) {
            $this->presentacionesPendientes = [];
            $this->productoPendienteData    = [];
            $this->dispatch('cerrarModalPresentacionesAutoconsumo');
            session()->flash('error', 'Stock insuficiente para "' . $d['nombre'] . '" en presentación ' . $pres['pres_nombre'] . '.');
            return;
        }

        $this->agregarProducto(
            (int)   $d['idPro'],
            (string)$d['nombre'],
            (string)$d['codigo'],
            (float) $d['stockRaw'],
            (float) $d['costo'],
            $pres['pres_nombre'],
            $factor > 0 ? $factor : 1.0
        );

        $this->presentacionesPendientes = [];
        $this->productoPendienteData    = [];
        $this->dispatch('cerrarModalPresentacionesAutoconsumo');
    }

    public function agregarProducto(int $idPro, string $nombre, string $codigo, float $stockRaw, float $costo, string $presNombre = '', float $presFactor = 1.0): void
    {
        foreach ($this->items as $item) {
            if ((int) $item['id_pro'] === $idPro) return;
        }
        $this->items[] = [
            'id_pro'       => $idPro,
            'nombre'       => $nombre,
            'codigo'       => $codigo,
            'stock_raw'    => $stockRaw,
            'stock_actual' => $presFactor > 0 ? round($stockRaw / $presFactor, 2) : $stockRaw,
            'costo'        => $costo,
            'cantidad'     => '',
            'pres_nombre'  => $presNombre,
            'pres_factor'  => $presFactor,
        ];
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function quitarItem(int $idx): void
    {
        array_splice($this->items, $idx, 1);
        $this->items = array_values($this->items);
    }

    public function nuevoAutoconsumo(): void
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

    // ── Guardar ───────────────────────────────────────────────────
    public function guardar(): void
    {
        if (!$this->ubicacionConfigurada()) {
            $this->addError('ubicacionKey', 'Seleccione un almacén o sede.');
            return;
        }
        if (empty($this->fechaEmision)) {
            $this->addError('fechaEmision', 'Ingrese la fecha de emisión.');
            return;
        }
        if (empty($this->items)) {
            $this->addError('items', 'Agregue al menos un producto.');
            return;
        }

            foreach ($this->items as $idx => $item) {
            if ((float) ($item['cantidad'] ?? 0) <= 0) {
                $this->addError("items_{$idx}", 'Ingrese una cantidad válida en todos los productos.');
                return;
            }
        }

        $almId = $this->almacenId();
        $tndId = $this->tiendaId();

        DB::beginTransaction();
        try {
            // Validar stock vivo con lockForUpdate (igual que Pedidos)
            foreach (collect($this->items)->sortBy('id_pro') as $item) {
                $factor     = (float) ($item['pres_factor'] ?? 1.0);
                $stockDelta = (float) $item['cantidad'] * ($factor > 0 ? $factor : 1.0);

                if ($almId) {
                    $reg     = DB::table('almacen_producto')
                        ->where('id_almacen', $almId)
                        ->where('id_pro', (int) $item['id_pro'])
                        ->lockForUpdate()->first();
                    $stockDb = $reg ? (float) $reg->ap_stock : 0;
                } else {
                    $reg     = DB::table('producto_sucursal')
                        ->where('id_tienda', $tndId)
                        ->where('id_pro', (int) $item['id_pro'])
                        ->lockForUpdate()->first();
                    $stockDb = $reg ? (float) $reg->ps_stock : 0;
                }

                if (!$reg || $stockDb < $stockDelta) {
                    DB::rollBack();
                    $disponible = $factor > 0 ? round($stockDb / $factor, 2) : $stockDb;
                    session()->flash('error', 'Stock insuficiente para "' . $item['nombre'] . '". Disponible: ' . $disponible);
                    return;
                }
            }
            $numero = 'AC-' . date('Y') . '-' . str_pad(
                DB::table('autoconsumo')->count() + 1, 5, '0', STR_PAD_LEFT
            );

            $idAutoconsumo = DB::table('autoconsumo')->insertGetId([
                'autoconsumo_numero'       => $numero,
                'id_almacen'               => $almId,
                'id_tienda'                => $tndId,
                'autoconsumo_area'         => $this->area,
                'autoconsumo_autorizacion' => null,
                'autoconsumo_fecha'        => $this->fechaEmision,
                'id_users'                 => auth()->user()->id_users,
                'autoconsumo_estado'       => 'registrado',
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            $idMov = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'          => $this->fechaEmision,
                'id_users'                             => auth()->user()->id_users,
                'id_sucursal'                          => $tndId,
                'id_almacen'                           => $almId,
                'movimientos_productos_fecha_creacion' => now(),
                'movimientos_productos_tipo'           => 2,
                'movimientos_productos_estado'         => 1,
                'movimientos_productos_motivo'         => "Autoconsumo {$numero} — Área: {$this->area} — Autorizado: {$this->autorizacion}",
                'concepto'                             => 'autoconsumo',
                'created_at'                           => now(),
                'updated_at'                           => now(),
            ]);

            foreach ($this->items as $item) {
                $cantidad   = (float) $item['cantidad'];
                $costo      = (float) $item['costo'];
                $idPro      = (int)   $item['id_pro'];
                $presFactor = (float) ($item['pres_factor'] ?? 1.0);
                $stockDelta = $cantidad * ($presFactor > 0 ? $presFactor : 1.0);

                DB::table('autoconsumo_detalle')->insert([
                    'id_autoconsumo'   => $idAutoconsumo,
                    'id_pro'           => $idPro,
                    'detalle_cantidad' => $stockDelta,
                    'detalle_costo'    => $costo,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                DB::table('movimientos_productos_detalle')->insert([
                    'id_movimientos_productos'               => $idMov,
                    'id_pro'                                 => $idPro,
                    'movimientos_productos_detalle_cantidad' => (string) $stockDelta,
                    'costo_unitario'                         => $costo,
                    'id_referencia'                          => $idAutoconsumo,
                    'tipo_referencia'                        => 'autoconsumo',
                    'movimientos_productos_detalle_estado'   => 1,
                    'created_at'                             => now(),
                    'updated_at'                             => now(),
                ]);

                if ($almId) {
                    DB::table('almacen_producto')
                        ->where('id_almacen', $almId)
                        ->where('id_pro', $idPro)
                        ->decrement('ap_stock', $stockDelta, ['updated_at' => now()]);
                } else {
                    DB::table('producto_sucursal')
                        ->where('id_tienda', $tndId)
                        ->where('id_pro', $idPro)
                        ->decrement('ps_stock', $stockDelta, ['updated_at' => now()]);
                }
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->idAutoconsumoActivo = $idAutoconsumo;
            $this->vista = 'revision';

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al registrar el autoconsumo.');
        }
    }

    // ── Ver detalle ───────────────────────────────────────────────
    public function verDetalle(int $id): void
    {
        $this->idAutoconsumoDetalle = $id;
        $this->dispatch('abrirModalDetalle');
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['ubicacionKey', 'idTienda', 'buscarProducto', 'resultados', 'items', 'idAutoconsumoActivo', 'presentacionesPendientes', 'productoPendienteData']);
        $this->area          = 'Administración';
        $this->autorizacion  = '';
        $this->fechaEmision  = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->autoResolverUbicacion();
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
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

        // Lista de autorizaciones: empresas (RUC - nombre) + personas fijas
        $autorizacionesEmpresas = $empresas->map(fn($e) => [
            'label' => $e->empresa_ruc . ' - ' . $e->empresa_razon_social,
            'value' => $e->empresa_ruc . ' - ' . $e->empresa_razon_social,
        ])->toArray();
        $autorizacionesPersonas = [
            ['label' => 'Ana Morelia',       'value' => 'Ana Morelia'],
            ['label' => 'Sr. Aquiles',       'value' => 'Sr. Aquiles'],
            ['label' => 'Luz Dina',          'value' => 'Luz Dina'],
            ['label' => 'Sr. Carlos',        'value' => 'Sr. Carlos'],
            ['label' => 'Alejandra Kimberlye', 'value' => 'Alejandra Kimberlye'],
            ['label' => 'Fabiana',           'value' => 'Fabiana'],
        ];

        // Vista revisión
        $revisionAutoconsumo = null;
        $revisionItems       = collect();
        if ($this->vista === 'revision' && $this->idAutoconsumoActivo) {
            $revisionAutoconsumo = DB::table('autoconsumo as ac')
                ->leftJoin('almacen as alm', 'alm.id_almacen', '=', 'ac.id_almacen')
                ->leftJoin('tiendas as t',   't.id_tienda',   '=', 'ac.id_tienda')
                ->leftJoin('empresa as ea',  'ea.id_empresa', '=', 'alm.id_empresa')
                ->leftJoin('empresa as et',  'et.id_empresa', '=', 't.id_empresa')
                ->select(
                    'ac.*',
                    DB::raw("COALESCE(alm.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                    DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre"),
                )
                ->where('ac.id_autoconsumo', $this->idAutoconsumoActivo)
                ->first();

            $revisionItems = DB::table('autoconsumo_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_autoconsumo', $this->idAutoconsumoActivo)
                ->select('d.*', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('p.pro_nombre')
                ->get();
        }

        // Modal detalle
        $detalleAutoconsumo = null;
        $detalleItems       = collect();
        if ($this->idAutoconsumoDetalle) {
            $detalleAutoconsumo = DB::table('autoconsumo as ac')
                ->leftJoin('almacen as alm', 'alm.id_almacen', '=', 'ac.id_almacen')
                ->leftJoin('tiendas as t',   't.id_tienda',   '=', 'ac.id_tienda')
                ->leftJoin('empresa as ea',  'ea.id_empresa', '=', 'alm.id_empresa')
                ->leftJoin('empresa as et',  'et.id_empresa', '=', 't.id_empresa')
                ->join('users as u',         'u.id_users',    '=', 'ac.id_users')
                ->select(
                    'ac.*',
                    DB::raw("COALESCE(alm.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                    DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre"),
                    'u.nombre_users',
                )
                ->where('ac.id_autoconsumo', $this->idAutoconsumoDetalle)
                ->first();

            $detalleItems = DB::table('autoconsumo_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_autoconsumo', $this->idAutoconsumoDetalle)
                ->select('d.*', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('p.pro_nombre')
                ->get();
        }

        // Historial paginado
        $autoconsumos = DB::table('autoconsumo as ac')
            ->leftJoin('almacen as alm', 'alm.id_almacen', '=', 'ac.id_almacen')
            ->leftJoin('tiendas as t',   't.id_tienda',   '=', 'ac.id_tienda')
            ->leftJoin('empresa as ea',  'ea.id_empresa', '=', 'alm.id_empresa')
            ->leftJoin('empresa as et',  'et.id_empresa', '=', 't.id_empresa')
            ->join('users as u',         'u.id_users',    '=', 'ac.id_users')
            ->select(
                'ac.*',
                DB::raw("COALESCE(alm.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                DB::raw("COALESCE(ea.empresa_nombrecomercial, et.empresa_nombrecomercial) as empresa_nombre"),
                'u.nombre_users',
                DB::raw('(SELECT COUNT(*) FROM autoconsumo_detalle WHERE id_autoconsumo = ac.id_autoconsumo) as total_productos'),
            )
            ->when($this->filtroDesde, fn($q) => $q->whereDate('ac.autoconsumo_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta, fn($q) => $q->whereDate('ac.autoconsumo_fecha', '<=', $this->filtroHasta))
            ->orderByDesc('ac.id_autoconsumo')
            ->paginate($this->porPagina);

        return view('livewire.logistica.autoconsumo', compact(
            'almacenes', 'empresas', 'sedes',
            'autorizacionesEmpresas', 'autorizacionesPersonas',
            'revisionAutoconsumo', 'revisionItems',
            'detalleAutoconsumo', 'detalleItems',
            'autoconsumos',
        ));
    }
}
