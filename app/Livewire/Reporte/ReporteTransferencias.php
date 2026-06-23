<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteTransferencias extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroDesde  = '';
    public string $filtroHasta  = '';
    public string $filtroEstado = '';
    public bool   $buscado      = false;
    public int    $porPagina    = 20;

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
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) return $this->sucursalSeleccionada;
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_transferencias.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get();
        }

        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) $this->sucursalSeleccionada = (int) $idTienda;
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->buscado = false; $this->resetPage();
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->buscado = false; $this->resetPage(); }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }
    public function updatedFiltroEstado(): void          { $this->buscado = false; }
    public function updatingPorPagina(): void            { $this->resetPage(); }

    public function generar(): void { $this->buscado = true; $this->resetPage(); }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_transferencias.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_transferencias_pdf', $this->buildParams()));
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_transferencias.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_transferencias_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return [
            'empresa'  => $this->empresaSeleccionada,
            'sucursal' => $this->sucursalSeleccionada,
            'desde'    => $this->filtroDesde,
            'hasta'    => $this->filtroHasta,
            'estado'   => $this->filtroEstado,
        ];
    }

    public function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('transferencias_stock as ts')
            ->select(
                'ts.id_transferencia', 'ts.transferencia_numero', 'ts.transferencia_fecha',
                'ts.transferencia_estado', 'ts.transferencia_motivo',
                'so.tienda_nombre as origen_nombre',
                'sd.tienda_nombre as destino_nombre',
                'u.nombre_users',
                DB::raw('(SELECT COUNT(*) FROM transferencias_stock_detalle tsd WHERE tsd.id_transferencia = ts.id_transferencia) as total_items'),
                DB::raw('(SELECT COALESCE(SUM(tsd2.detalle_cantidad),0) FROM transferencias_stock_detalle tsd2 WHERE tsd2.id_transferencia = ts.id_transferencia) as total_unidades')
            )
            ->join('tiendas as so', 'so.id_tienda', '=', 'ts.id_almacen_origen')
            ->join('tiendas as sd', 'sd.id_tienda', '=', 'ts.id_tienda_destino')
            ->join('users as u',    'u.id_users',   '=', 'ts.id_users');

        if ($idSucursal > 0) {
            $query->where(fn($q) => $q->where('ts.id_almacen_origen', $idSucursal)
                                      ->orWhere('ts.id_tienda_destino', $idSucursal));
        } elseif ($idEmpresa) {
            $empresaId = $idEmpresa;
            $query->where(function ($q) use ($empresaId) {
                $q->whereIn('ts.id_almacen_origen',  fn($s) => $s->select('id_tienda')->from('tiendas')->where('id_empresa', $empresaId))
                  ->orWhereIn('ts.id_tienda_destino', fn($s) => $s->select('id_tienda')->from('tiendas')->where('id_empresa', $empresaId));
            });
        }

        if ($this->filtroDesde) $query->whereDate('ts.transferencia_fecha', '>=', $this->filtroDesde);
        if ($this->filtroHasta) $query->whereDate('ts.transferencia_fecha', '<=', $this->filtroHasta);
        if ($this->filtroEstado !== '') $query->where('ts.transferencia_estado', $this->filtroEstado);

        return $query->orderByDesc('ts.transferencia_fecha');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $transferencias = collect();
        $totales        = null;

        if ($this->buscado) {
            $transferencias = $this->buildQuery()->paginate($this->porPagina);
            $all = $this->buildQuery()->get();
            $totales = [
                'cantidad'        => $all->count(),
                'total_items'     => $all->sum('total_items'),
                'total_unidades'  => $all->sum('total_unidades'),
            ];
        }

        return view('livewire.reporte.reporte-transferencias', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'transferencias', 'totales'
        ));
    }
}
