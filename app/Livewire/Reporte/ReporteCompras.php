<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCompras extends Component
{
    public string $filtroDesde        = '';
    public string $filtroHasta        = '';
    public string $filtroTransportista = '';   // nombre del transportista
    public string $tipoReporte        = 'registro'; // registro | estudio

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_compras.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    private function buildParams(): array
    {
        return [
            'desde'        => $this->filtroDesde,
            'hasta'        => $this->filtroHasta,
            'transportista'=> $this->filtroTransportista,
            'tipo'         => $this->tipoReporte,
        ];
    }

    public function descargarExcel(): void
    {
        if (!auth()->user()->can('reporte_compras.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_compras_excel', $this->buildParams()));
    }

    public function render()
    {
        $transportistas = DB::table('transportistas')
            ->where('transportista_estado', 1)
            ->orderBy('transportista_nombre')
            ->get(['id_transportista', 'transportista_nombre']);

        return view('livewire.reporte.reporte-compras', compact('transportistas'));
    }
}
