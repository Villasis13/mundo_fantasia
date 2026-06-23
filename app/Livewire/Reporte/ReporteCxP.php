<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCxP extends Component
{
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroProveedor  = '';
    public string $filtroDesde     = '';
    public string $filtroHasta     = '';
    public string $filtroVinculada = '';   // '' | '1' | '0'
    public bool   $buscado         = false;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esSuperAdmin()) return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        if ($this->esAdmin())      return $this->adminEmpresaId();
        $idSuc = (int) session('sucursal_activa_id', 0);
        return $idSuc ? DB::table('sucursals')->where('id_sucursal', $idSuc)->value('id_empresa') : null;
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) return $this->sucursalSeleccionada;
        return (int) session('sucursal_activa_id', 0);
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_cxp.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta  = now()->addMonth()->format('Y-m-d');

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')->orderBy('sucursal_nombre')->get();
                if ($this->sucursalesDisponibles->count() === 1)
                    $this->sucursalSeleccionada = $this->sucursalesDisponibles->first()->id_sucursal;
            }
        } elseif (!$this->esSuperAdmin()) {
            $this->sucursalSeleccionada = (int) session('sucursal_activa_id', 0);
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->buscado = false;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('sucursals')->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)->whereNull('deleted_at')->orderBy('sucursal_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void  { $this->buscado = false; }
    public function updatedFiltroProveedor(): void       { $this->buscado = false; }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }
    public function updatedFiltroVinculada(): void       { $this->buscado = false; }

    public function generar(): void { $this->buscado = true; }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_cxp.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_cxp_pdf', $this->buildParams()));
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_cxp.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_cxp_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return [
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'proveedor' => $this->filtroProveedor,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
            'vinculada' => $this->filtroVinculada,
        ];
    }

    public function calcularReporte(): array
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        $hoy        = now()->toDateString();

        $query = DB::table('cuentas_pagar as cp')
            ->select('cp.*', 'p.proveedores_nombre', 'p.proveedores_numero_documento',
                DB::raw("CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada"))
            ->join('proveedores as p', 'p.id_proveedores', '=', 'cp.id_proveedores')
            ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'p.proveedores_numero_documento')
            ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'cp.id_empresa')
            ->whereNull('cp.deleted_at')
            ->where('cp.cp_estado', '!=', 0);

        if ($idSucursal > 0)   $query->where('cp.id_sucursal', $idSucursal);
        elseif ($idEmpresa)    $query->where('cp.id_empresa', $idEmpresa);
        if ($this->filtroDesde) $query->whereDate('cp.cp_fecha_vencimiento', '>=', $this->filtroDesde);
        if ($this->filtroHasta) $query->whereDate('cp.cp_fecha_vencimiento', '<=', $this->filtroHasta);
        if ($this->filtroProveedor !== '') {
            $like = '%'.$this->filtroProveedor.'%';
            $query->where(function ($q) use ($like) {
                $q->where('p.proveedores_nombre', 'like', $like)
                  ->orWhere('p.proveedores_numero_documento', 'like', $like);
            });
        }
        if ($this->filtroVinculada === '1') {
            $query->whereRaw('ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo');
        } elseif ($this->filtroVinculada === '0') {
            $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        }

        $cuentas = $query->orderBy('cp.cp_fecha_vencimiento')->get();

        $totales = [
            'total'   => $cuentas->sum('cp_monto_total'),
            'pagado'  => $cuentas->sum('cp_monto_pagado'),
            'saldo'   => $cuentas->sum('cp_saldo'),
            'vencido' => $cuentas->filter(fn($c) => $c->cp_fecha_vencimiento < $hoy && $c->cp_estado != 3)->sum('cp_saldo'),
        ];

        $ids = $cuentas->pluck('id_cuenta_pagar')->all();
        $pagosPorCuenta = collect();
        if (count($ids)) {
            $pagosPorCuenta = DB::table('pagos_cuentas_pagar as pcp')
                ->select('pcp.*', 'tp.tipo_pago_nombre', 'u.nombre_users')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pcp.id_tipo_pago')
                ->join('users as u', 'u.id_users', '=', 'pcp.id_users')
                ->whereIn('pcp.id_cuenta_pagar', $ids)
                ->whereNull('pcp.deleted_at')
                ->orderBy('pcp.pcp_fecha')
                ->get()
                ->groupBy('id_cuenta_pagar');
        }

        return compact('cuentas', 'totales', 'pagosPorCuenta');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $empresas     = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $reporte = $this->buscado ? $this->calcularReporte() : null;

        return view('livewire.reporte.reporte-cxp', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'reporte'
        ));
    }
}
