<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteInventarioGeneral extends Component
{
    public int    $filtroFamilia   = 0;
    public int    $filtroCategoria = 0;
    public string $filtroBusqueda  = '';   // código o descripción
    public string $filtroDesde = '';
    public string $filtroHasta = '';

    private $logs;

    public function updatedFiltroFamilia(): void { $this->filtroCategoria = 0; }

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_inventario_general.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    public function descargarExcel(): void
    {
        if (!auth()->user()->can('reporte_inventario_general.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_inventario_general_excel', [
            'familia'   => $this->filtroFamilia,
            'categoria' => $this->filtroCategoria,
            'busqueda'  => $this->filtroBusqueda,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
        ]));
    }

    public function render()
    {
        $familias = DB::table('familias')->orderBy('fa_nombre')->get(['id_fa', 'fa_nombre']);
        $categorias = $this->filtroFamilia > 0
            ? DB::table('categorias')->where('id_fa', $this->filtroFamilia)->orderBy('ca_nombre')->get(['id_ca', 'ca_nombre'])
            : DB::table('categorias')->orderBy('ca_nombre')->get(['id_ca', 'ca_nombre']);

        return view('livewire.reporte.reporte-inventario-general', compact('familias', 'categorias'));
    }
}
