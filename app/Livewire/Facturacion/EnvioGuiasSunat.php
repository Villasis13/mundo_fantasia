<?php

namespace App\Livewire\Facturacion;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EnvioGuiasSunat extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $periodoInicio    = '';
    public string $periodoFin       = '';
    public string $filtroSerieCorr  = '';

    public array $seleccionadas = [];   // ids de guías marcadas para enviar

    public function updatedPeriodoInicio(): void   { $this->resetPage(); }
    public function updatedPeriodoFin(): void       { $this->resetPage(); }
    public function updatedFiltroSerieCorr(): void  { $this->resetPage(); }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('envio_guias_sunat.listar'), 403);
        $this->periodoInicio = now()->startOfMonth()->format('Y-m-d');
        $this->periodoFin    = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = DB::table('guias_remision as gr')
            ->select(
                'gr.id_guia', 'gr.guia_serie', 'gr.guia_correlativo', 'gr.guia_numero',
                'gr.guia_fecha_emision', 'gr.created_at',
                'gr.guia_dest_nombre', 'gr.guia_dest_numero_doc',
                'gr.guia_ruta_xml', 'gr.guia_ruta_cdr', 'gr.guia_estado_sunat',
                'gr.guia_respuesta_sunat', 'gr.guia_fecha_envio'
            )
            ->whereDate('gr.guia_fecha_emision', '>=', $this->periodoInicio)
            ->whereDate('gr.guia_fecha_emision', '<=', $this->periodoFin);

        if (trim($this->filtroSerieCorr) !== '') {
            $t = '%' . trim($this->filtroSerieCorr) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('gr.guia_numero', 'like', $t)
                  ->orWhere('gr.guia_serie', 'like', $t)
                  ->orWhere('gr.guia_correlativo', 'like', $t)
                  ->orWhereRaw("CONCAT(gr.guia_serie,'-',gr.guia_correlativo) LIKE ?", [$t]);
            });
        }

        $guias = $query->orderByDesc('gr.id_guia')->paginate(20);

        return view('livewire.facturacion.envio-guias-sunat', compact('guias'));
    }
}
