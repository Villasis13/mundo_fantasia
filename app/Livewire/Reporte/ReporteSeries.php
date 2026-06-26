<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteSeries extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;

    public string $buscar      = '';
    public string $filtroEstado = '';   // '' | 1 disponible | 2 vendido (según tu uso)
    public bool   $buscado     = false;
    public int    $porPagina   = 20;

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

    private function empresaUsuario(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_series_productos.listar'), 403);
        $empresaId = $this->empresaUsuario();
        if ($empresaId) $this->empresaSeleccionada = $empresaId;
    }

    public function updatedEmpresaSeleccionada(): void { $this->buscado = false; $this->resetPage(); }
    public function updatedBuscar(): void               { $this->resetPage(); }
    public function updatedFiltroEstado(): void         { $this->buscado = false; $this->resetPage(); }
    public function updatingPorPagina(): void           { $this->resetPage(); }

    public function generar(): void { $this->buscado = true; $this->resetPage(); }

    private function buildParams(): array
    {
        return [
            'id_empresa' => $this->resolverIdEmpresa() ?? 0,
            'q'          => $this->buscar,
            'estado'     => $this->filtroEstado,
        ];
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_series_productos.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.series_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_series_productos.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.series_excel', $this->buildParams()));
    }

    private function buildQuery()
    {
        $idEmpresa = $this->resolverIdEmpresa();

        $query = DB::table('producto_series as s')
            ->join('productos as p', 'p.id_pro', '=', 's.id_pro')
            ->leftJoin('users as u', 'u.id_users', '=', 's.id_users');

        if ($idEmpresa) {
            $query->where('p.id_empresa', $idEmpresa);
        }

        if (trim($this->buscar) !== '') {
            $like = '%' . trim($this->buscar) . '%';
            $query->where(fn($q) => $q->where('s.numero_serie', 'like', $like)
                                      ->orWhere('p.pro_nombre', 'like', $like)
                                      ->orWhere('p.pro_codigo', 'like', $like));
        }

        if ($this->filtroEstado !== '') {
            $query->where('s.estado', (int) $this->filtroEstado);
        }

        return $query->select(
            's.numero_serie', 's.estado', 's.observacion',
            's.id_venta', 's.id_orden_compra', 's.created_at',
            'p.pro_nombre', 'p.pro_codigo', 'u.nombre_users'
        )->orderByDesc('s.id_producto_serie');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $series = collect();
        $total  = 0;

        if ($this->buscado) {
            $series = $this->buildQuery()->paginate($this->porPagina);
            $total  = $this->buildQuery()->count();
        }

        return view('livewire.reporte.reporte-series', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'series', 'total'
        ));
    }
}
