<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class RecepcionCompras extends Component
{
    use WithPagination;

    public string $vista = 'lista';

    // ── Filtros ───────────────────────────────────────────────
    public int    $filtroEmpresa   = 0;
    public int    $filtroSucursal  = 0;
    public int    $filtroProveedor = 0;
    public string $filtroDesde     = '';
    public string $filtroHasta     = '';
    public int    $porPagina       = 10;

    // ── Recepción ─────────────────────────────────────────────
    public ?int  $idOrdenRecibir        = null;
    public int   $idAlmacenSeleccionado = 0;
    public array $cantidadesRecibidas   = [];

    private ?Logs $logs        = null;
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
        abort_if(!auth()->user()->can('recepcion_almacen.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Abrir formulario de recepción ─────────────────────────
    public function abrirRecepcion(int $idOrden): void
    {
        $orden = DB::table('orden_compra')
            ->where('id_orden_compra', $idOrden)
            ->whereIn('orden_compra_estado', ['pendiente', 'en_transito'])
            ->first();

        if (!$orden) return;

        $this->idOrdenRecibir        = $idOrden;
        $this->idAlmacenSeleccionado = 0;
        $this->resetErrorBag();

        // Auto-seleccionar el primer almacén disponible (hay uno general compartido)
        $almacen = DB::table('almacen')
            ->where('almacen_estado', 1)
            ->orderBy('id_almacen')
            ->value('id_almacen');

        if ($almacen) {
            $this->idAlmacenSeleccionado = (int) $almacen;
        }

        $detalle = DB::table('orden_compra_detalle')
            ->where('id_orden_compra', $idOrden)
            ->where('detalle_compra_estado', 1)
            ->get();

        $cantidades = [];
        foreach ($detalle as $item) {
            $cantidades[$item->id_detalle_compra] = (float) $item->detalle_compra_cantidad;
        }
        $this->cantidadesRecibidas = $cantidades;
        $this->vista = 'recibir';
    }

    public function volverLista(): void
    {
        $this->vista                 = 'lista';
        $this->idOrdenRecibir        = null;
        $this->cantidadesRecibidas   = [];
        $this->idAlmacenSeleccionado = 0;
        $this->resetErrorBag();
        $this->resetPage();
    }

    // ── Confirmar recepción ───────────────────────────────────
    public function confirmarRecepcion(): void
    {
        if (!$this->idOrdenRecibir) return;

        if (!auth()->user()->can('recepcion_almacen.crear')) {
            session()->flash('error', 'No tienes permiso para recepcionar órdenes.');
            return;
        }

        if (!$this->idAlmacenSeleccionado) {
            $this->addError('almacen', 'Debe seleccionar un almacén de destino.');
            return;
        }

        $orden = DB::table('orden_compra')
            ->where('id_orden_compra', $this->idOrdenRecibir)
            ->first();

        if (!$orden || !in_array($orden->orden_compra_estado, ['pendiente', 'en_transito'])) {
            session()->flash('error', 'Solo se puede recepcionar una orden pendiente o en tránsito.');
            return;
        }

        $detalle = DB::table('orden_compra_detalle')
            ->where('id_orden_compra', $this->idOrdenRecibir)
            ->where('detalle_compra_estado', 1)
            ->get();

        $hayAlguna = collect($this->cantidadesRecibidas)->filter(fn($v) => (float) $v > 0)->isNotEmpty();
        if (!$hayAlguna) {
            $this->addError('cantidades', 'Ingrese al menos una cantidad mayor a 0.');
            return;
        }

        DB::beginTransaction();
        try {
            $idMovimiento = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'           => now()->toDateString(),
                'id_users'                              => auth()->user()->id_users,
                'id_sucursal'                           => null,
                'movimientos_productos_fecha_creacion'  => now(),
                'movimientos_productos_tipo'            => 1,
                'movimientos_productos_estado'          => 1,
                'movimientos_productos_motivo'          => 'Recepción OC ' . $orden->orden_compra_numero,
                'created_at'                            => now(),
                'updated_at'                            => now(),
            ]);

            foreach ($detalle as $item) {
                $cantidad = max(0, (float) ($this->cantidadesRecibidas[$item->id_detalle_compra] ?? 0));
                if ($cantidad <= 0) continue;

                $costoUni = (float) $item->detalle_compra_precio_compra;

                $ap = DB::table('almacen_producto')
                    ->where('id_almacen', $this->idAlmacenSeleccionado)
                    ->where('id_pro', $item->id_pro)
                    ->first();

                if ($ap) {
                    DB::table('almacen_producto')
                        ->where('id_ap', $ap->id_ap)
                        ->increment('ap_stock', $cantidad, [
                            'id_orden_compra' => $this->idOrdenRecibir,
                            'ap_precio_costo' => $costoUni,
                            'updated_at'      => now(),
                        ]);
                } else {
                    DB::table('almacen_producto')->insert([
                        'id_almacen'      => $this->idAlmacenSeleccionado,
                        'id_pro'          => $item->id_pro,
                        'id_orden_compra' => $this->idOrdenRecibir,
                        'ap_stock'        => $cantidad,
                        'ap_precio_costo' => $costoUni,
                        'ap_estado'       => 1,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                DB::table('movimientos_productos_detalle')->insert([
                    'id_movimientos_productos'               => $idMovimiento,
                    'id_pro'                                 => $item->id_pro,
                    'movimientos_productos_detalle_cantidad' => (string) $cantidad,
                    'costo_unitario'                         => $costoUni,
                    'id_referencia'                          => $this->idOrdenRecibir,
                    'tipo_referencia'                        => 'compra',
                    'movimientos_productos_detalle_estado'   => '1',
                    'created_at'                             => now(),
                    'updated_at'                             => now(),
                ]);

                DB::table('orden_compra_detalle')
                    ->where('id_detalle_compra', $item->id_detalle_compra)
                    ->update([
                        'detalle_compra_cantidad_recibida' => $cantidad,
                        'updated_at'                       => now(),
                    ]);
            }

            DB::table('orden_compra')
                ->where('id_orden_compra', $this->idOrdenRecibir)
                ->update([
                    'orden_compra_estado'          => 'recibido',
                    'orden_compra_fecha_recibida'   => now(),
                    'orden_compra_usuario_recibido' => auth()->user()->nombre_users ?? (string) auth()->id(),
                    'id_almacen'                   => $this->idAlmacenSeleccionado,
                    'updated_at'                   => now(),
                ]);

            DB::commit();
            $this->volverLista();
            session()->flash('success', 'Recepción confirmada. Stock actualizado en almacén.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al confirmar la recepción.');
        }
    }

    public function updatedFiltroEmpresa(): void  { $this->filtroSucursal = 0; $this->resetPage(); }
    public function updatedFiltroSucursal(): void  { $this->resetPage(); }
    public function updatedFiltroProveedor(): void { $this->resetPage(); }
    public function updatingPorPagina(): void      { $this->resetPage(); }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $esSuperAdmin   = $this->esSuperAdmin();
        $esAdmin        = $this->esAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        $empresaFiltroActiva = $esSuperAdmin
            ? ($this->filtroEmpresa ?: null)
            : $adminEmpresaId;

        $sucursalesFilter = $empresaFiltroActiva
            ? DB::table('tiendas')
                ->where('id_empresa', $empresaFiltroActiva)
                ->whereIn('tienda_tipo', [1, 2])
                ->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();

        $proveedores = DB::table('proveedores')
            ->where('proveedores_estado', 1)
            ->orderBy('proveedores_nombre')->get();

        $ordenes = DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
            ->select(
                'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_estado',
                'oc.orden_compra_fecha', 'oc.orden_compra_numero_doc', 'oc.orden_compra_tipo_doc',
                'oc.orden_compra_total', 'oc.id_sucursal',
                'pv.proveedores_nombre', 't.tienda_nombre'
            )
            ->where('oc.orden_compra_activo', 1)
            ->whereIn('oc.orden_compra_estado', ['pendiente', 'en_transito'])
            ->when($esAdmin && $adminEmpresaId, fn($q) => $q->whereExists(fn($sub) => $sub
                ->select(DB::raw(1))->from('tiendas')
                ->whereColumn('tiendas.id_tienda', 'oc.id_sucursal')
                ->where('tiendas.id_empresa', $adminEmpresaId)))
            ->when($esSuperAdmin && $this->filtroEmpresa > 0, fn($q) => $q->whereExists(fn($sub) => $sub
                ->select(DB::raw(1))->from('tiendas')
                ->whereColumn('tiendas.id_tienda', 'oc.id_sucursal')
                ->where('tiendas.id_empresa', $this->filtroEmpresa)))
            ->when($this->filtroSucursal > 0, fn($q) => $q->where('oc.id_sucursal', $this->filtroSucursal))
            ->when($this->filtroProveedor > 0, fn($q) => $q->where('oc.id_proveedores', $this->filtroProveedor))
            ->when($this->filtroDesde, fn($q) => $q->whereDate('oc.orden_compra_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta, fn($q) => $q->whereDate('oc.orden_compra_fecha', '<=', $this->filtroHasta))
            ->orderByDesc('oc.id_orden_compra')
            ->paginate($this->porPagina);

        $ordenDetalle         = null;
        $detalleItems         = collect();
        $almacenesDisponibles = collect();

        if ($this->vista === 'recibir' && $this->idOrdenRecibir) {
            $ordenDetalle = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->leftJoin('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
                ->where('oc.id_orden_compra', $this->idOrdenRecibir)
                ->select('oc.*', 'pv.proveedores_nombre', 't.tienda_nombre')
                ->first();

            $detalleItems = DB::table('orden_compra_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_orden_compra', $this->idOrdenRecibir)
                ->where('d.detalle_compra_estado', 1)
                ->select('d.*', 'p.pro_codigo', 'p.pro_nombre')
                ->get();

            if ($ordenDetalle) {
                $almacenesDisponibles = DB::table('almacen')
                    ->where('almacen_estado', 1)
                    ->orderBy('almacen_nombre')
                    ->get();
            }
        }

        return view('livewire.logistica.recepcion-compras', compact(
            'empresas', 'sucursalesFilter', 'proveedores',
            'ordenes', 'esSuperAdmin', 'esAdmin',
            'ordenDetalle', 'detalleItems', 'almacenesDisponibles'
        ));
    }
}
