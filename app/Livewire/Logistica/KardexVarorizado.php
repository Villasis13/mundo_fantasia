<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KardexVarorizado extends Component
{
    // ── Rol y contexto ─────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int    $empresaSeleccionada   = 0;
    public string $ubicacionKey          = ''; // 'sucursal_{id}' | 'almacen_{id}'
    public int    $sucursalSeleccionada  = 0;
    public int    $almacenSeleccionado   = 0;
    public        $sucursalesDisponibles = [];
    public        $almacenesDisponibles  = [];
    public        $empresas              = [];

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->orderBy('ut.id_tienda')
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if (str_starts_with($this->ubicacionKey, 'empresa_')) {
            $id = (int) substr($this->ubicacionKey, 8);
            return $id > 0 ? $id : null;
        }
        return null;
    }

    private function resolverIdSucursal(): int
    {
        return $this->sucursalSeleccionada;
    }

    private function resolverIdAlmacen(): int
    {
        return $this->almacenSeleccionado;
    }

    // ── Búsqueda de producto ───────────────────────────────────
    public string $busquedaProducto  = '';
    public ?int   $idProducto        = null;
    public string $productoNombre    = '';
    public        $sugerenciasProducto = [];

    // ── Filtros ────────────────────────────────────────────────
    public string $desde      = '';
    public string $hasta      = '';
    public string $tipoKardex = 'fisico';
    public bool   $buscando   = false;
    public array  $headerInfo = [];

    // ── Resumido ───────────────────────────────────────────────
    public int   $familiaSeleccionada = 0;
    public       $familias            = [];
    public array $lineasResumido      = [];
    public array $kardexPorProducto   = [];

    // ── Resultados ─────────────────────────────────────────────
    public array  $lineas        = [];
    public        $saldoInicial  = null;
    public        $totales       = null;

    public function mount(): void
    {
        abort_if(!auth()->user()->can('kardex_valorizado.listar'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        $this->cargarEmpresas();

        $this->almacenesDisponibles = DB::table('almacen')
            ->join('empresa as e', 'e.id_empresa', '=', 'almacen.id_empresa')
            ->where('almacen.almacen_estado', 1)
            ->orderBy('e.empresa_nombrecomercial')
            ->orderBy('almacen.almacen_nombre')
            ->get(['almacen.id_almacen', DB::raw("CONCAT(almacen.almacen_nombre, ' - ', e.empresa_nombrecomercial) as almacen_nombre")]);
    }


public function updatedUbicacionKey(): void
    {
        $this->sucursalSeleccionada  = 0;
        $this->almacenSeleccionado   = 0;
        $this->sucursalesDisponibles = [];
        $this->buscando              = false;
        $this->lineas                = [];
        $this->kardexPorProducto     = [];
        $this->headerInfo            = [];

        if (str_starts_with($this->ubicacionKey, 'almacen_')) {
            $this->almacenSeleccionado = (int) substr($this->ubicacionKey, 8);
        } elseif (str_starts_with($this->ubicacionKey, 'empresa_')) {
            $empresaId = (int) substr($this->ubicacionKey, 8);
            if ($empresaId > 0) {
                $this->sucursalesDisponibles = DB::table('tiendas')
                    ->where('id_empresa', $empresaId)
                    ->where('tienda_estado', 1)
                    ->orderBy('tienda_nombre')
                    ->get(['id_tienda', 'tienda_nombre']);
            }
        }
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->buscando          = false;
        $this->lineas            = [];
        $this->kardexPorProducto = [];
        $this->headerInfo        = [];
    }

    public function updatedTipoKardex(): void
    {
        $this->buscando          = false;
        $this->lineas            = [];
        $this->lineasResumido    = [];
        $this->kardexPorProducto = [];
        $this->headerInfo        = [];

        if ($this->tipoKardex === 'resumido' && $this->idProducto) {
            $idFa = DB::table('productos as p')
                ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                ->where('p.id_pro', $this->idProducto)
                ->value('f.id_fa');
            if ($idFa) {
                $this->familiaSeleccionada = (int) $idFa;
            }
        }
    }

    public function updatedFamiliaSeleccionada(): void
    {
        $this->buscando          = false;
        $this->lineas            = [];
        $this->lineasResumido    = [];
        $this->kardexPorProducto = [];
        $this->saldoInicial   = null;
        $this->totales        = null;
    }

    private function cargarHeaderInfo(): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        $idAlmacen  = $this->resolverIdAlmacen();

        $alm = $idAlmacen > 0 ? DB::table('almacen')->where('id_almacen', $idAlmacen)->first() : null;

        // Cuando se selecciona almacén, obtener la empresa desde él
        if (!$idEmpresa && $alm) {
            $idEmpresa = $alm->id_empresa ?? null;
        }

        $emp  = $idEmpresa  ? DB::table('empresa')->where('id_empresa', $idEmpresa)->first() : null;
        $sede = $idSucursal > 0 ? DB::table('tiendas')->where('id_tienda', $idSucursal)->first() : null;

        $this->headerInfo = [
            'empresa_nombre' => $emp  ? ($emp->empresa_razon_social ?? $emp->empresa_nombrecomercial ?? '') : '',
            'empresa_ruc'    => $emp  ? ($emp->empresa_ruc ?? '') : '',
            'sede_nombre'    => $alm  ? ($alm->almacen_nombre ?? 'Almacén')
                              : ($sede ? ($sede->tienda_nombre ?? '') : 'General'),
        ];
    }

    private function cargarEmpresas(): void {} // carga real en render()

    private static function tdocSunat(?string $tipoRef): string
    {
        return match(strtolower((string) $tipoRef)) {
            'compra'              => '01',
            'transferencia'       => '09',
            'merma_transferencia' => '09',
            'nota'                => '07',
            'nc_compra'           => '07',
            'nd_compra'           => '08',
            default               => '00',
        };
    }

    private static function tipoOpSunat(?string $tipoRef): string
    {
        return match(strtolower((string) $tipoRef)) {
            'compra'              => '02',
            'merma_compra'        => '13',
            'anulacion_compra'    => '05',
            'nc_compra'           => '05',
            'nd_compra'           => '02',
            'transferencia'       => '11',
            'merma_transferencia' => '13',
            'inventario'          => '99',
            'autoconsumo'         => '10',
            'nota'                => '01',
            default               => '99',
        };
    }

    // ── Búsqueda de producto con autocompletado ────────────────
    public function buscarProducto(): void
    {
        $term = trim($this->busquedaProducto);
        if (strlen($term) < 2) {
            $this->sugerenciasProducto = [];
            return;
        }

        $query = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->leftJoin('familias as f', 'f.id_fa', '=', 'c.id_fa')
            ->where('p.pro_estado', 1)
            ->where(function ($q) use ($term) {
                $q->where('p.pro_nombre', 'like', "%{$term}%")
                  ->orWhere('p.pro_codigo', 'like', "%{$term}%")
                  ->orWhere('f.fa_nombre', 'like', "%{$term}%")
                  ->orWhere('c.ca_nombre', 'like', "%{$term}%");
            });

        // Filtrar por ubicación seleccionada
        $idSucursal = $this->resolverIdSucursal();
        $idAlmacen  = $this->resolverIdAlmacen();
        $idEmpresa  = $this->resolverIdEmpresa();

        if ($idAlmacen > 0) {
            $query->whereExists(function ($sub) use ($idAlmacen) {
                $sub->from('almacen_producto as ap')
                    ->whereColumn('ap.id_pro', 'p.id_pro')
                    ->where('ap.id_almacen', $idAlmacen)
                    ->where('ap.ap_estado', 1);
            });
        } elseif ($idSucursal > 0) {
            $query->whereExists(function ($sub) use ($idSucursal) {
                $sub->from('producto_sucursal as ps')
                    ->whereColumn('ps.id_pro', 'p.id_pro')
                    ->where('ps.id_tienda', $idSucursal)
                    ->where('ps.ps_estado', 1);
            });
        } elseif ($idEmpresa) {
            $query->whereExists(function ($sub) use ($idEmpresa) {
                $sub->from('producto_sucursal as ps')
                    ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
                    ->whereColumn('ps.id_pro', 'p.id_pro')
                    ->where('t.id_empresa', $idEmpresa)
                    ->where('ps.ps_estado', 1);
            })->orWhereExists(function ($sub) use ($idEmpresa) {
                $sub->from('almacen_producto as ap')
                    ->join('almacen as a', 'a.id_almacen', '=', 'ap.id_almacen')
                    ->whereColumn('ap.id_pro', 'p.id_pro')
                    ->where('a.id_empresa', $idEmpresa)
                    ->where('ap.ap_estado', 1);
            });
        } else {
            $query->whereExists(function ($sub) {
                $sub->from('almacen_producto as ap')
                    ->whereColumn('ap.id_pro', 'p.id_pro')
                    ->where('ap.ap_estado', 1);
            });
        }

        $this->sugerenciasProducto = $query
            ->orderBy('p.pro_nombre')
            ->limit(10)
            ->get(['p.id_pro', 'p.pro_nombre', 'p.pro_codigo']);
    }

    public function seleccionarProducto(int $idPro, string $nombre, string $codigo): void
    {
        $this->idProducto          = $idPro;
        $this->productoNombre      = "{$nombre} [{$codigo}]";
        $this->busquedaProducto    = "{$nombre} [{$codigo}]";
        $this->sugerenciasProducto = [];
        $this->buscando            = false;
        $this->lineas              = [];

        // Auto-seleccionar familia del producto si aún no hay una seleccionada
        if (!$this->familiaSeleccionada) {
            $idFa = DB::table('productos as p')
                ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                ->where('p.id_pro', $idPro)
                ->value('f.id_fa');
            if ($idFa) {
                $this->familiaSeleccionada = (int) $idFa;
            }
        }
    }

    public function limpiarProducto(): void
    {
        $this->idProducto          = null;
        $this->productoNombre      = '';
        $this->busquedaProducto    = '';
        $this->sugerenciasProducto = [];
        $this->buscando            = false;
        $this->lineas              = [];
        $this->kardexPorProducto   = [];
    }

    // ── Buscar kardex ──────────────────────────────────────────
    public function buscar(): void
    {
        if (empty($this->ubicacionKey)) {
            $this->addError('ubicacionKey', 'Debe seleccionar un Almacén o Empresa.');
            return;
        }

        if (str_starts_with($this->ubicacionKey, 'empresa_') && $this->sucursalSeleccionada === 0) {
            $this->addError('ubicacion', 'Debe seleccionar una sede antes de buscar.');
            return;
        }

        $this->validate([
            'familiaSeleccionada' => 'required|integer|min:1',
            'desde'               => 'required|date',
            'hasta'               => 'required|date|after_or_equal:desde',
        ], [
            'familiaSeleccionada.required' => 'Debe seleccionar una Familia / Marca.',
            'familiaSeleccionada.min'      => 'Debe seleccionar una Familia / Marca.',
            'desde.required'               => 'La fecha "Desde" es obligatoria.',
            'hasta.required'               => 'La fecha "Hasta" es obligatoria.',
            'hasta.after_or_equal'         => '"Hasta" debe ser igual o posterior a "Desde".',
        ]);

        $this->buscando = true;
        $this->cargarHeaderInfo();

        if ($this->tipoKardex === 'resumido') {
            $this->cargarKardexResumido();
        } elseif ($this->idProducto) {
            $this->cargarKardex();
        } else {
            // fisico/valorizado sin producto → kardex de todos los productos de la familia
            $this->cargarKardexMultiple();
        }
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $q, string $prefix = 'mp'): void
    {
        $idSucursal = $this->resolverIdSucursal();
        $idAlmacen  = $this->resolverIdAlmacen();
        $idEmpresa  = $this->resolverIdEmpresa();

        if ($idAlmacen > 0) {
            $q->where("{$prefix}.id_almacen", $idAlmacen);
        } elseif ($idSucursal > 0) {
            $q->where("{$prefix}.id_sucursal", $idSucursal);
        } elseif ($idEmpresa) {
            $q->where(function ($sub) use ($idEmpresa, $prefix) {
                $sub->whereExists(function ($s) use ($idEmpresa, $prefix) {
                    $s->from('tiendas as t_loc')
                      ->whereColumn("t_loc.id_tienda", "{$prefix}.id_sucursal")
                      ->where('t_loc.id_empresa', $idEmpresa);
                })->orWhereExists(function ($s) use ($idEmpresa, $prefix) {
                    $s->from('almacen as a_loc')
                      ->whereColumn("a_loc.id_almacen", "{$prefix}.id_almacen")
                      ->where('a_loc.id_empresa', $idEmpresa);
                });
            });
        }
        // Sin filtro de ubicación: se muestran todos los movimientos
    }

    private function buildBaseWhere(\Illuminate\Database\Query\Builder $q): void
    {
        $q->where('mpd.id_pro', $this->idProducto)
          ->where('mp.movimientos_productos_estado', 1);
        $this->aplicarFiltroUbicacion($q);
    }

    private function calcularKardexProducto(int $idPro): array
    {
        $qSaldo = DB::table('movimientos_productos as mp')
            ->join('movimientos_productos_detalle as mpd', 'mpd.id_movimientos_productos', '=', 'mp.id_movimientos_productos')
            ->where('mpd.id_pro', $idPro)
            ->where('mp.movimientos_productos_estado', 1)
            ->where('mp.movimientos_productos_fecha', '<', $this->desde);
        $this->aplicarFiltroUbicacion($qSaldo);
        $saldo = $qSaldo->selectRaw('
            SUM(CASE WHEN mp.movimientos_productos_tipo = 1 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END)
          - SUM(CASE WHEN mp.movimientos_productos_tipo = 2 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) as saldo_cantidad,
            SUM(CASE WHEN mp.movimientos_productos_tipo = 1 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END)
          - SUM(CASE WHEN mp.movimientos_productos_tipo = 2 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) as saldo_valor
        ')->first();
        $saldoCantidad = (float) ($saldo->saldo_cantidad ?? 0);
        $saldoValor    = (float) ($saldo->saldo_valor    ?? 0);

        $qMov = DB::table('movimientos_productos as mp')
            ->join('movimientos_productos_detalle as mpd', 'mpd.id_movimientos_productos', '=', 'mp.id_movimientos_productos')
            ->join('users as u', 'u.id_users', '=', 'mp.id_users')
            ->where('mpd.id_pro', $idPro)
            ->where('mp.movimientos_productos_estado', 1)
            ->whereBetween('mp.movimientos_productos_fecha', [$this->desde, $this->hasta]);
        $this->aplicarFiltroUbicacion($qMov);
        $movimientos = $qMov->select(
                'mp.movimientos_productos_fecha as fecha',
                'mp.id_movimientos_productos',
                'mp.movimientos_productos_tipo as tipo',
                'mp.movimientos_productos_motivo as motivo',
                'mp.concepto',
                'mpd.movimientos_productos_detalle_cantidad as cantidad',
                'mpd.costo_unitario',
                'mpd.id_referencia',
                'mpd.tipo_referencia',
                'u.nombre_users as usuario'
            )
            ->orderBy('mp.movimientos_productos_fecha')
            ->orderBy('mp.id_movimientos_productos')
            ->get();

        $totalEntradaCant = $totalEntradaValor = $totalSalidaCant = $totalSalidaValor = 0.0;
        $lineas = [];
        foreach ($movimientos as $mov) {
            $cantidad   = (float) $mov->cantidad;
            $costoUnit  = (float) $mov->costo_unitario;
            $costoTotal = $cantidad * $costoUnit;
            $base = [
                'fecha'           => $mov->fecha,
                'id_movimiento'   => $mov->id_movimientos_productos,
                'tipo'            => (int) $mov->tipo,
                'motivo'          => $mov->motivo,
                'concepto'        => $mov->concepto,
                'id_referencia'   => $mov->id_referencia,
                'tipo_referencia' => $mov->tipo_referencia,
                'tdoc'            => self::tdocSunat($mov->tipo_referencia),
                'tipo_op'         => self::tipoOpSunat($mov->tipo_referencia),
                'usuario'         => $mov->usuario,
            ];
            if ((int) $mov->tipo === 1) {
                $saldoCantidad    += $cantidad;
                $saldoValor       += $costoTotal;
                $totalEntradaCant  += $cantidad;
                $totalEntradaValor += $costoTotal;
                $lineas[] = $base + [
                    'entrada_cant'  => $cantidad, 'entrada_cu'  => $costoUnit, 'entrada_total'  => $costoTotal,
                    'salida_cant'   => null,       'salida_cu'   => null,       'salida_total'   => null,
                    'saldo_cant'    => $saldoCantidad, 'saldo_valor' => $saldoValor,
                ];
            } else {
                $saldoCantidad   -= $cantidad;
                $saldoValor      -= $costoTotal;
                $totalSalidaCant  += $cantidad;
                $totalSalidaValor += $costoTotal;
                $lineas[] = $base + [
                    'entrada_cant'  => null,       'entrada_cu'  => null,       'entrada_total'  => null,
                    'salida_cant'   => $cantidad,  'salida_cu'   => $costoUnit, 'salida_total'   => $costoTotal,
                    'saldo_cant'    => $saldoCantidad, 'saldo_valor' => $saldoValor,
                ];
            }
        }

        return [
            'saldoInicial' => ['cantidad' => (float) ($saldo->saldo_cantidad ?? 0), 'valor' => (float) ($saldo->saldo_valor ?? 0)],
            'lineas'       => $lineas,
            'totales'      => [
                'entrada_cant'  => $totalEntradaCant,
                'entrada_valor' => $totalEntradaValor,
                'salida_cant'   => $totalSalidaCant,
                'salida_valor'  => $totalSalidaValor,
                'saldo_cant'    => $saldoCantidad,
                'saldo_valor'   => $saldoValor,
            ],
        ];
    }

    private function cargarKardex(): void
    {
        try {
            $r = $this->calcularKardexProducto($this->idProducto);
            $this->saldoInicial = $r['saldoInicial'];
            $this->lineas       = $r['lineas'];
            $this->totales      = $r['totales'];
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorGeneral', 'Error al generar el kardex.');
        }
    }

    private function cargarKardexMultiple(): void
    {
        $this->kardexPorProducto = [];
        try {
            $productos = DB::table('productos as p')
                ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                ->where('f.id_fa', $this->familiaSeleccionada)
                ->where('p.pro_estado', 1)
                ->orderBy('p.pro_nombre')
                ->get(['p.id_pro', 'p.pro_nombre', 'p.pro_codigo']);

            foreach ($productos as $prod) {
                $r  = $this->calcularKardexProducto($prod->id_pro);
                $si = $r['saldoInicial'];
                if ($si['cantidad'] == 0 && $si['valor'] == 0 && empty($r['lineas'])) {
                    continue;
                }
                $this->kardexPorProducto[] = [
                    'id_pro'       => $prod->id_pro,
                    'nombre'       => $prod->pro_nombre,
                    'codigo'       => $prod->pro_codigo,
                    'saldoInicial' => $si,
                    'lineas'       => $r['lineas'],
                    'totales'      => $r['totales'],
                ];
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorGeneral', 'Error al generar el kardex.');
        }
    }

    private function cargarKardexResumido(): void
    {
        try {
            $q = DB::table('movimientos_productos_detalle as mpd')
                ->join('movimientos_productos as mp', 'mp.id_movimientos_productos', '=', 'mpd.id_movimientos_productos')
                ->join('productos as p', 'p.id_pro', '=', 'mpd.id_pro')
                ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                ->where('mp.movimientos_productos_estado', 1)
                ->where('p.pro_estado', 1);

            if ($this->familiaSeleccionada > 0) {
                $q->where('f.id_fa', $this->familiaSeleccionada);
            }
            if ($this->idProducto) {
                $q->where('mpd.id_pro', $this->idProducto);
            }

            $this->aplicarFiltroUbicacion($q);

            $resultados = $q->selectRaw("
                mpd.id_pro,
                p.pro_codigo,
                p.pro_nombre,
                f.id_fa,
                f.fa_nombre,
                SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 1
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) -
                SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 2
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS saldo_ini_cant,
                SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 1
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) -
                SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 2
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS saldo_ini_valor,
                SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 1
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS ingresos_cant,
                SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 1
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS ingresos_valor,
                SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 2
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS egresos_cant,
                SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 2
                    THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS egresos_valor
            ", [
                $this->desde, $this->desde,
                $this->desde, $this->desde,
                $this->desde, $this->hasta,
                $this->desde, $this->hasta,
                $this->desde, $this->hasta,
                $this->desde, $this->hasta,
            ])
            ->groupBy('mpd.id_pro', 'p.pro_codigo', 'p.pro_nombre', 'f.id_fa', 'f.fa_nombre')
            ->orderBy('f.fa_nombre')
            ->orderBy('p.pro_nombre')
            ->get();

            $grupos = [];
            foreach ($resultados as $r) {
                $saldoIniCant  = (float) $r->saldo_ini_cant;
                $saldoIniValor = (float) $r->saldo_ini_valor;
                $ingresosCant  = (float) $r->ingresos_cant;
                $ingresosValor = (float) $r->ingresos_valor;
                $egresosCant   = (float) $r->egresos_cant;
                $egresosValor  = (float) $r->egresos_valor;

                if ($saldoIniCant == 0 && $ingresosCant == 0 && $egresosCant == 0) continue;

                $saldoFinalCant  = $saldoIniCant  + $ingresosCant  - $egresosCant;
                $saldoFinalValor = $saldoIniValor + $ingresosValor - $egresosValor;
                $cu = $saldoFinalCant != 0 ? $saldoFinalValor / $saldoFinalCant : 0;

                $famNombre = $r->fa_nombre;
                if (!isset($grupos[$famNombre])) {
                    $grupos[$famNombre] = ['familia' => $famNombre, 'productos' => [], 'total_ct' => 0.0];
                }
                $grupos[$famNombre]['productos'][] = [
                    'codigo'           => $r->pro_codigo,
                    'nombre'           => $r->pro_nombre,
                    'saldo_ini_cant'   => $saldoIniCant,
                    'ingresos_cant'    => $ingresosCant,
                    'egresos_cant'     => $egresosCant,
                    'saldo_final_cant' => $saldoFinalCant,
                    'c_u'              => $cu,
                    'c_t'              => $saldoFinalValor,
                ];
                $grupos[$famNombre]['total_ct'] += $saldoFinalValor;
            }

            $this->lineasResumido = array_values($grupos);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorGeneral', 'Error al generar el kardex resumido.');
        }
    }

    // ── Exportar ───────────────────────────────────────────────
    private function buildExportableParams(): array
    {
        return [
            'id_pro'      => $this->idProducto ?? 0,
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'id_empresa'  => $this->resolverIdEmpresa()  ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
            'id_almacen'  => $this->resolverIdAlmacen(),
            'tipo'        => $this->tipoKardex,
            'id_familia'  => $this->familiaSeleccionada,
        ];
    }

    public function exportarPdf(): void
    {
        try {
            if (!auth()->user()->can('kardex_valorizado.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('logistica.kardex_valorizado_pdf', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function exportarExcel(): void
    {
        try {
            if (!auth()->user()->can('kardex_valorizado.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('logistica.kardex_valorizado_excel', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function render()
    {
        $this->empresas = DB::table('empresa')
            ->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_nombrecomercial')
            ->get(['id_empresa', 'empresa_nombrecomercial']);

        $this->familias = DB::table('familias')
            ->orderBy('fa_nombre')
            ->get(['id_fa', 'fa_nombre']);

        return view('livewire.logistica.kardex-valorizado');
    }
}
