<?php

namespace App\Livewire\GestionVentas;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class GuiasPendientes extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $buscar = '';

    public function updatedBuscar(): void { $this->resetPage(); }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('guias_remision.listar'), 403);
    }

    public function render()
    {
        $query = DB::table('guias_remision as gr')
            ->leftJoin('users as u', 'u.id_users', '=', 'gr.id_users')
            ->select(
                'gr.id_guia', 'gr.guia_serie', 'gr.guia_correlativo', 'gr.guia_numero', 'gr.guia_tipo',
                'gr.guia_fecha_emision', 'gr.guia_fecha_traslado', 'gr.guia_dest_nombre', 'gr.guia_dest_numero_doc',
                'gr.guia_estado_sunat', 'u.nombre_users'
            );

        if (trim($this->buscar) !== '') {
            $t = '%' . trim($this->buscar) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('gr.guia_numero', 'like', $t)
                  ->orWhere('gr.guia_serie', 'like', $t)
                  ->orWhere('gr.guia_dest_nombre', 'like', $t)
                  ->orWhere('gr.guia_dest_numero_doc', 'like', $t);
            });
        }

        $guias = $query->orderByDesc('gr.id_guia')->paginate(15);

        return view('livewire.gestion-ventas.guias-pendientes', compact('guias'));
    }
}
