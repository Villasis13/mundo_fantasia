<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListarIngresos extends Component
{
    use WithPagination;

    public int    $filtroProveedor = 0;
    public string $filtroEstado    = '';
    public string $filtroCondicion = '';
    public string $filtroDesde     = '';
    public string $filtroHasta     = '';

    // Detalle
    public int    $ordenSeleccionada = 0;
    public ?array $detalleOrden        = null;
    public ?array $detalleProveedor    = null;
    public array  $detalleTransportistas = [];
    public array  $detalleItems        = [];
    public ?array $detalleResumen      = null;
    public string $detalleObservacion  = '';

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
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function empresaId(): ?int
    {
        if ($this->esSuperAdmin()) {
            return null;
        }
        if ($this->esAdmin()) {
            $id = DB::table('user_sucursal as us')
                ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
                ->where('us.id_users', auth()->user()->id_users)
                ->orderBy('us.id_sucursal')->value('s.id_empresa');
            return $id ? (int) $id : null;
        }
        $idSuc = (int) session('sucursal_activa_id', 0);
        return $idSuc ? (int) DB::table('sucursals')->where('id_sucursal', $idSuc)->value('id_empresa') : null;
    }

    // Cuando se registra una compra desde el otro tab, refrescar el listado
    #[On('compraRegistrada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroProveedor() { $this->resetPage(); }
    public function updatingFiltroEstado()    { $this->resetPage(); }
    public function updatingFiltroCondicion() { $this->resetPage(); }
    public function updatingFiltroDesde()     { $this->resetPage(); }
    public function updatingFiltroHasta()     { $this->resetPage(); }

    // ── Detalle (modal) ───────────────────────────────────────
    public function verDetalle(int $idOrden): void
    {
        $this->ordenSeleccionada     = $idOrden;
        $this->detalleOrden          = null;
        $this->detalleProveedor      = null;
        $this->detalleTransportistas = [];
        $this->detalleItems          = [];
        $this->detalleResumen        = null;
        $this->detalleObservacion    = '';

        try {
            $oc = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->where('oc.id_orden_compra', $idOrden)
                ->select(
                    'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_estado',
                    'oc.orden_compra_fecha_emision_doc', 'oc.orden_compra_numero_doc',
                    'oc.orden_compra_tipo_doc', 'oc.condicion_pago', 'oc.orden_compra_igv_porcentaje',
                    'oc.fecha_almacenamiento', 'oc.orden_compra_fecha_recibida', 'oc.orden_compra_observacion',
                    'oc.orden_compra_total', 'oc.orden_compra_flete', 'oc.orden_compra_descuento_monto',
                    'oc.orden_compra_igv_monto', 'oc.orden_compra_percepcion_monto',
                    'pv.proveedores_nombre', 'pv.proveedores_numero_documento'
                )
                ->first();

            if (!$oc) {
                $this->dispatch('abrirModalDetalleIngreso');
                return;
            }

            $igvPct = (float) ($oc->orden_compra_igv_porcentaje ?? 0);

            $this->detalleOrden = [
                'numero'             => $oc->orden_compra_numero,
                'tipo_doc'           => $oc->orden_compra_tipo_doc ?? '—',
                'numero_doc'         => $oc->orden_compra_numero_doc ?? '—',
                'condicion'          => $oc->condicion_pago,
                'estado'             => $oc->orden_compra_estado,
                'fecha_emision'      => $oc->orden_compra_fecha_emision_doc,
                'fecha_almacenamiento' => $oc->fecha_almacenamiento,
                'fecha_recepcion'    => $oc->orden_compra_fecha_recibida,
                'igv_pct'            => $igvPct,
            ];

            $this->detalleObservacion = (string) ($oc->orden_compra_observacion ?? '');

            $this->detalleProveedor = [
                'nombre' => $oc->proveedores_nombre,
                'ruc'    => $oc->proveedores_numero_documento,
            ];

            $this->detalleTransportistas = DB::table('orden_compra_transportistas')
                ->where('id_orden_compra', $idOrden)
                ->orderBy('oc_trans_orden')
                ->get(['oc_trans_nombre', 'oc_trans_ruc', 'oc_trans_fact', 'oc_trans_fecha'])
                ->map(fn($t) => (array) $t)
                ->filter(fn($t) => trim((string) $t['oc_trans_nombre']) !== ''
                    || trim((string) $t['oc_trans_ruc']) !== ''
                    || trim((string) $t['oc_trans_fact']) !== '')
                ->values()->all();

            $items = DB::table('orden_compra_detalle as d')
                ->leftJoin('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_orden_compra', $idOrden)
                ->where('d.detalle_compra_estado', 1)
                ->select(
                    'p.pro_codigo', 'd.detalle_orden_nombre_producto', 'd.presentacion',
                    'd.detalle_compra_cantidad', 'd.detalle_compra_cantidad_recibida',
                    'd.detalle_compra_precio_compra', 'd.detalle_compra_total_pedido', 'd.flete'
                )
                ->get();

            $this->detalleItems = $items->map(function ($it) use ($igvPct) {
                $subtotal = (float) $it->detalle_compra_total_pedido;
                $flete    = (float) $it->flete;
                $igv      = round($subtotal * $igvPct / 100, 2);
                return [
                    'codigo'            => $it->pro_codigo ?? '—',
                    'descripcion'       => $it->detalle_orden_nombre_producto ?? '—',
                    'presentacion'      => $it->presentacion ?? '—',
                    'cantidad'          => (float) $it->detalle_compra_cantidad,
                    'cantidad_recibida' => $it->detalle_compra_cantidad_recibida !== null
                        ? (float) $it->detalle_compra_cantidad_recibida : null,
                    'costo_unitario'    => (float) $it->detalle_compra_precio_compra,
                    'flete'             => $flete,
                    'igv'               => $igv,
                    'total'             => round($subtotal + $igv + $flete, 2),
                ];
            })->all();

            // Resumen de importes
            $subtotalProd = round($items->sum(fn($it) => (float) $it->detalle_compra_total_pedido), 2);
            $descuento    = (float) ($oc->orden_compra_descuento_monto ?? 0);
            $this->detalleResumen = [
                'subtotal'      => $subtotalProd,
                'descuento'     => $descuento,
                'subtotal_neto' => round($subtotalProd - $descuento, 2),
                'igv'           => (float) ($oc->orden_compra_igv_monto ?? 0),
                'percepcion'    => (float) ($oc->orden_compra_percepcion_monto ?? 0),
                'flete'         => (float) ($oc->orden_compra_flete ?? 0),
                'total'         => (float) ($oc->orden_compra_total ?? 0),
            ];
        } catch (\Exception $e) {
            $this->logs?->insertarLog($e);
        }

        $this->dispatch('abrirModalDetalleIngreso');
    }

    private function buildQuery()
    {
        $idEmpresa = $this->empresaId();

        return DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->select(
                'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_estado',
                'oc.orden_compra_fecha', 'oc.orden_compra_fecha_emision_doc',
                'oc.orden_compra_numero_doc', 'oc.orden_compra_tipo_doc',
                'oc.orden_compra_total', 'oc.condicion_pago',
                'pv.proveedores_nombre'
            )
            ->where('oc.orden_compra_activo', 1)
            ->when($idEmpresa, fn($q) => $q->where('oc.id_empresa', $idEmpresa))
            ->when($this->filtroProveedor > 0,
                fn($q) => $q->where('oc.id_proveedores', $this->filtroProveedor))
            ->when($this->filtroEstado !== '',
                fn($q) => $q->where('oc.orden_compra_estado', $this->filtroEstado))
            ->when($this->filtroCondicion !== '',
                fn($q) => $q->where('oc.condicion_pago', $this->filtroCondicion))
            ->when($this->filtroDesde,
                fn($q) => $q->whereDate('oc.orden_compra_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta,
                fn($q) => $q->whereDate('oc.orden_compra_fecha', '<=', $this->filtroHasta))
            ->orderByDesc('oc.id_orden_compra');
    }

    public function render()
    {
        $idEmpresa   = $this->empresaId();
        $proveedores = DB::table('proveedores')
            ->where('proveedores_estado', 1)
            ->orderBy('proveedores_nombre')
            ->get(['id_proveedores', 'proveedores_nombre']);

        $ingresos = $this->buildQuery()->paginate(15);

        return view('livewire.logistica.listar-ingresos', compact('proveedores', 'ingresos'));
    }
}
