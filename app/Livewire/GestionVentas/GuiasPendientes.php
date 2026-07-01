<?php

namespace App\Livewire\GestionVentas;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class GuiasPendientes extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $filtroDesde  = '';
    public string $filtroHasta  = '';
    public string $filtroEstado = '';   // '' | pendiente | enviado | anulado
    public string $filtroNumero = '';

    public function updatedFiltroDesde(): void  { $this->resetPage(); }
    public function updatedFiltroHasta(): void  { $this->resetPage(); }
    public function updatedFiltroEstado(): void { $this->resetPage(); }
    public function updatedFiltroNumero(): void { $this->resetPage(); }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('guias_remision.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = DB::table('guias_remision as gr')
            ->leftJoin('users as u', 'u.id_users', '=', 'gr.id_users')
            ->select(
                'gr.id_guia', 'gr.guia_serie', 'gr.guia_correlativo', 'gr.guia_numero', 'gr.guia_tipo',
                'gr.guia_fecha_emision', 'gr.guia_fecha_traslado', 'gr.guia_dest_nombre', 'gr.guia_dest_numero_doc',
                'gr.guia_estado', 'gr.guia_estado_sunat', 'u.nombre_users'
            )
            ->whereDate('gr.guia_fecha_emision', '>=', $this->filtroDesde)
            ->whereDate('gr.guia_fecha_emision', '<=', $this->filtroHasta);

        // Estado: pendiente de enviar / enviado / anulado
        if ($this->filtroEstado === 'pendiente') {
            $query->where('gr.guia_estado', 1)->where('gr.guia_estado_sunat', 0);
        } elseif ($this->filtroEstado === 'enviado') {
            $query->where('gr.guia_estado', 1)->where('gr.guia_estado_sunat', 1);
        } elseif ($this->filtroEstado === 'anulado') {
            $query->where('gr.guia_estado', 0);
        }

        if (trim($this->filtroNumero) !== '') {
            $t = '%' . trim($this->filtroNumero) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('gr.guia_numero', 'like', $t)
                  ->orWhere('gr.guia_serie', 'like', $t)
                  ->orWhere('gr.guia_correlativo', 'like', $t);
            });
        }

        $guias = $query->orderByDesc('gr.id_guia')->paginate(15);

        return view('livewire.gestion-ventas.guias-pendientes', compact('guias'));
    }
}
