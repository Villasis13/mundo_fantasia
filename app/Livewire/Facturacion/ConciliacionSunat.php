<?php

namespace App\Livewire\Facturacion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConciliacionSunat extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

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
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        if ($this->esAdmin()) {
            return $this->adminEmpresaId();
        }
        $idSucursal = (int) session('sucursal_activa_id', 0);
        if (!$idSucursal) return null;
        $id = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->sucursalSeleccionada;
        }
        return (int) session('sucursal_activa_id', 0);
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->esSuperAdmin()) {
            if ($this->sucursalSeleccionada) {
                $query->where('v.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($this->empresaSeleccionada) {
                $query->where('v.id_empresa', $this->empresaSeleccionada);
            }
        } elseif ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($this->sucursalSeleccionada) {
                $query->where('v.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($empresaId) {
                $query->where('v.id_empresa', $empresaId);
            }
        } else {
            $idSucursal = (int) session('sucursal_activa_id', 0);
            $query->where('v.id_sucursal', $idSucursal);
        }
    }

    // ── Filtros ────────────────────────────────────────────────
    public string $desde      = '';
    public string $hasta      = '';
    public string $tipoVenta  = '';        // '' = todos, '01','03','07','08'
    public string $vistaActiva = 'resumen';
    public bool   $buscando   = false;

    // ── Resultados ─────────────────────────────────────────────
    public $resumenTotales = [];
    public $resumenPorTipo = [];
    public $detalle        = [];

    public function mount(): void
    {
        abort_if(!auth()->user()->can('conciliacion_sunat.listar'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();

                if (collect($this->sucursalesDisponibles)->count() === 1) {
                    $this->sucursalSeleccionada = collect($this->sucursalesDisponibles)->first()->id_sucursal;
                }
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscando = false;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get()
            : [];
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->buscando = false;
    }

    public function buscar(): void
    {
        $this->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ], [
            'desde.required'       => 'La fecha "Desde" es obligatoria.',
            'hasta.required'       => 'La fecha "Hasta" es obligatoria.',
            'hasta.after_or_equal' => '"Hasta" debe ser igual o posterior a "Desde".',
        ]);

        $this->buscando = true;
        $this->cargarDatos();
    }

    public function setVista(string $vista): void
    {
        $this->vistaActiva = $vista;
    }

    private function buildQuery(): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('ventas as v')
            ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
            ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
            ->select(
                'v.id_venta', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_tipo', 'v.venta_fecha', 'v.venta_total',
                'v.venta_estado_sunat', 'v.anulado_sunat',
                'v.venta_tipo_envio', 'v.venta_fecha_envio', 'v.venta_respuesta_sunat',
                'mo.simbolo',
                DB::raw("CASE WHEN c.id_tipo_documento = 4 THEN c.cliente_razonsocial ELSE c.cliente_nombre END as cliente_nombre"),
                DB::raw("c.cliente_numero"),
                DB::raw("CASE v.venta_tipo
                    WHEN '01' THEN 'Factura'
                    WHEN '03' THEN 'Boleta'
                    WHEN '07' THEN 'Nota Crédito'
                    WHEN '08' THEN 'Nota Débito'
                    ELSE v.venta_tipo END as tipo_label")
            )
            ->whereIn('v.venta_tipo', ['01', '03', '07', '08'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

        if ($this->tipoVenta !== '') {
            $query->where('v.venta_tipo', $this->tipoVenta);
        }

        $this->aplicarFiltroUbicacion($query);

        return $query->orderBy('v.venta_fecha')->orderBy('v.venta_serie')->orderBy('v.venta_correlativo');
    }

    private function cargarDatos(): void
    {
        $rows = $this->buildQuery()->get();

        // ── Totales globales ──────────────────────────────────
        $emitidas   = $rows->count();
        $declaradas = $rows->where('venta_estado_sunat', 1)->count();
        $pendientes = $rows->where('venta_estado_sunat', 0)->count();
        $anuladas   = $rows->where('anulado_sunat', 1)->count();

        $montoEmitido   = $rows->sum('venta_total');
        $montoDeclarado = $rows->where('venta_estado_sunat', 1)->sum('venta_total');
        $montoPendiente = $rows->where('venta_estado_sunat', 0)->sum('venta_total');

        $this->resumenTotales = compact(
            'emitidas', 'declaradas', 'pendientes', 'anuladas',
            'montoEmitido', 'montoDeclarado', 'montoPendiente'
        );

        // ── Resumen por tipo de comprobante ────────────────────
        $tipos = [
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota Crédito',
            '08' => 'Nota Débito',
        ];

        $this->resumenPorTipo = collect($tipos)->map(function ($label, $codigo) use ($rows) {
            $grupo = $rows->where('venta_tipo', $codigo);
            return (object) [
                'tipo'            => $codigo,
                'label'           => $label,
                'emitidas'        => $grupo->count(),
                'declaradas'      => $grupo->where('venta_estado_sunat', 1)->count(),
                'pendientes'      => $grupo->where('venta_estado_sunat', 0)->count(),
                'anuladas'        => $grupo->where('anulado_sunat', 1)->count(),
                'monto_emitido'   => $grupo->sum('venta_total'),
                'monto_declarado' => $grupo->where('venta_estado_sunat', 1)->sum('venta_total'),
                'monto_pendiente' => $grupo->where('venta_estado_sunat', 0)->sum('venta_total'),
            ];
        })->filter(fn($r) => $r->emitidas > 0)->values();

        $this->detalle = $rows;
    }

    public function exportarPdf(): void
    {
        try {
            if (!auth()->user()->can('conciliacion_sunat.exportar')) {
                session()->flash('error', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlace', url: route('facturacion.conciliacion_sunat_pdf', $this->buildExportParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function exportarExcel(): void
    {
        try {
            if (!auth()->user()->can('conciliacion_sunat.exportar')) {
                session()->flash('error', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlace', url: route('facturacion.conciliacion_sunat_excel', $this->buildExportParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    private function buildExportParams(): array
    {
        return [
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'tipo'        => $this->tipoVenta,
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        return view('livewire.facturacion.conciliacion-sunat', compact(
            'empresas', 'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
