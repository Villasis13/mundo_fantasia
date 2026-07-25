<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InventarioPermanente extends Component
{
    // Rango: 'fecha' (desde/hasta) | 'periodo' (mes/año)
    public string $modoRango  = 'fecha';
    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public int    $filtroMes  = 0;
    public int    $filtroAnio = 0;

    // Almacén / Empresa (0 = Todos)
    public int $empresaSeleccionada = 0;

    // Familia / Categoría (0 = Todos)
    public int $filtroFamilia   = 0;
    public int $filtroCategoria = 0;

    // Tipo de reporte
    public string $tipoReporte = 'detallado_diario'; // detallado_diario | acumulado_dia

    // Valorizado | Unidades físicas
    public string $valorizado = 'valorizado'; // valorizado | unidades

    // Mostrar productos
    public string $filtroExistencias = 'todos'; // todos | con_saldo | con_saldo_positivo | con_movimientos | sin_movimientos

    // Ordenar por
    public string $ordenar = 'producto'; // producto | codigo

    private ?Logs $logs = null;

    public function boot(): void { $this->logs = new Logs(); }

    public function mount(): void
    {
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
        $this->filtroMes   = (int) now()->format('n');
        $this->filtroAnio  = (int) now()->format('Y');

        // Almacén por defecto: primera empresa
        $this->empresaSeleccionada = (int) (DB::table('empresa')->orderBy('id_empresa')->value('id_empresa') ?? 0);
    }

    public function updatedFiltroFamilia(): void { $this->filtroCategoria = 0; }

    private function paramsExport(): array
    {
        return [
            'modo'         => $this->modoRango,
            'desde'        => $this->filtroDesde,
            'hasta'        => $this->filtroHasta,
            'mes'          => $this->filtroMes,
            'anio'         => $this->filtroAnio,
            'familia'      => $this->filtroFamilia,
            'categoria'    => $this->filtroCategoria,
            'tipo'         => $this->tipoReporte,
            'valorizado'   => $this->valorizado,
            'existencias'  => $this->filtroExistencias,
            'empresa'      => $this->empresaSeleccionada,
            'ordenar'      => $this->ordenar,
            '_'            => now()->format('YmdHis'), // anti-caché del navegador
        ];
    }

    public function descargarExcel(): void
    {
        if (!auth()->user()->can('inventario_permanente.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.inventario_permanente_excel', $this->paramsExport()));
    }

    public function descargarPdf(): void
    {
        if (!auth()->user()->can('inventario_permanente.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.inventario_permanente_pdf', $this->paramsExport()));
    }

    public function render()
    {
        $empresas = DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')
            ->get(['id_empresa', 'empresa_razon_social', 'empresa_nombrecomercial']);
        $familias = DB::table('familias')->orderBy('fa_nombre')->get(['id_fa', 'fa_nombre']);
        $categorias = $this->filtroFamilia > 0
            ? DB::table('categorias')->where('id_fa', $this->filtroFamilia)->orderBy('ca_nombre')->get(['id_ca', 'ca_nombre'])
            : DB::table('categorias')->orderBy('ca_nombre')->get(['id_ca', 'ca_nombre']);

        return view('livewire.reporte.inventario-permanente', compact('empresas', 'familias', 'categorias'));
    }
}
