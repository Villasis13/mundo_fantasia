<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class MercaderiaTransito extends Component
{
    use WithPagination;

    public string $tab = 'compras';

    public int    $filtroEmpresa  = 0;
    public int    $filtroSucursal = 0;
    public int    $porPagina      = 15;

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
        abort_if(!auth()->user()->can('mercaderia_transito.listar'), 403);
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    public function updatedTab(): void          { $this->resetPage(); }
    public function updatedFiltroEmpresa(): void  { $this->filtroSucursal = 0; $this->resetPage(); }
    public function updatedFiltroSucursal(): void { $this->resetPage(); }
    public function updatingPorPagina(): void     { $this->resetPage(); }

    private function buildParams(): array
    {
        return array_filter([
            'id_empresa'  => $this->filtroEmpresa  ?: null,
            'id_sucursal' => $this->filtroSucursal ?: null,
        ]);
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('mercaderia_transito.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('logistica.mercaderia_transito_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('mercaderia_transito.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('logistica.mercaderia_transito_excel', $this->buildParams()));
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $adminEmpId   = $esAdmin ? $this->adminEmpresaId() : null;

        $empresaFiltroActiva = $esSuperAdmin ? ($this->filtroEmpresa ?: null) : $adminEmpId;

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', 0)->orderBy('empresa_razon_social')->get()
            : collect();

        $sucursalesDisponibles = $empresaFiltroActiva
            ? DB::table('sucursals')->where('id_empresa', $empresaFiltroActiva)
                ->where('sucursal_estado', 1)->whereNull('deleted_at')->orderBy('sucursal_nombre')->get()
            : collect();

        // ── Órdenes de compra en tránsito (pendiente / en_transito) ─
        $comprasTransito = DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->leftJoin('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
            ->select(
                'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_estado',
                'oc.orden_compra_fecha', 'oc.orden_compra_total',
                'pv.proveedores_nombre', 's.sucursal_nombre'
            )
            ->whereIn('oc.orden_compra_estado', ['pendiente', 'en_transito'])
            ->where('oc.orden_compra_activo', 1)
            ->when($esSuperAdmin && !$empresaFiltroActiva, fn($q) => $q->whereRaw('0 = 1'))
            ->when($empresaFiltroActiva, function ($q) use ($empresaFiltroActiva) {
                $q->whereExists(fn($sub) => $sub->select(DB::raw(1))
                    ->from('sucursals')->whereColumn('sucursals.id_sucursal', 'oc.id_sucursal')
                    ->where('sucursals.id_empresa', $empresaFiltroActiva));
            })
            ->when($this->filtroSucursal > 0, fn($q) => $q->where('oc.id_sucursal', $this->filtroSucursal))
            ->orderByDesc('oc.id_orden_compra')
            ->paginate($this->porPagina, ['*'], 'comprasPage');

        // ── Transferencias en tránsito ──────────────────────────────
        $transferenciasTransito = DB::table('transferencias_stock as t')
            ->join('sucursals as so', 'so.id_sucursal', '=', 't.id_sucursal_origen')
            ->join('sucursals as sd', 'sd.id_sucursal', '=', 't.id_sucursal_destino')
            ->join('users as u', 'u.id_users', '=', 't.id_users')
            ->select('t.*', 'so.sucursal_nombre as origen_nombre', 'sd.sucursal_nombre as destino_nombre', 'u.nombre_users')
            ->whereIn('t.transferencia_estado', ['pendiente', 'en_transito'])
            ->when($esSuperAdmin && !$empresaFiltroActiva, fn($q) => $q->whereRaw('0 = 1'))
            ->when($empresaFiltroActiva, function ($q) use ($empresaFiltroActiva) {
                $q->where(function ($inner) use ($empresaFiltroActiva) {
                    $inner->whereExists(fn($sub) => $sub->select(DB::raw(1))
                        ->from('sucursals')->whereColumn('sucursals.id_sucursal', 't.id_sucursal_origen')
                        ->where('sucursals.id_empresa', $empresaFiltroActiva))
                          ->orWhereExists(fn($sub) => $sub->select(DB::raw(1))
                        ->from('sucursals')->whereColumn('sucursals.id_sucursal', 't.id_sucursal_destino')
                        ->where('sucursals.id_empresa', $empresaFiltroActiva));
                });
            })
            ->when($this->filtroSucursal > 0, fn($q) => $q->where(function ($inner) {
                $inner->where('t.id_sucursal_origen', $this->filtroSucursal)
                      ->orWhere('t.id_sucursal_destino', $this->filtroSucursal);
            }))
            ->orderByDesc('t.id_transferencia')
            ->paginate($this->porPagina, ['*'], 'transferenciasPage');

        return view('livewire.logistica.mercaderia-transito', compact(
            'comprasTransito', 'transferenciasTransito',
            'empresas', 'sucursalesDisponibles', 'esSuperAdmin', 'esAdmin'
        ));
    }
}
