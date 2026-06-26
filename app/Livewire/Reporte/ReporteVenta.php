<?php

namespace App\Livewire\Reporte;

use App\Models\General;
use App\Models\Logs;
use App\Models\PagosCuota;
use App\Models\Ventas;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Carbon\Carbon;

class ReporteVenta extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
    private $logs;
    private $general;
    private $venta;
    private $pagosCuota;

    public function boot(): void
    {
        $this->logs       = new Logs();
        $this->general    = new General();
        $this->venta      = new Ventas();
        $this->pagosCuota = new PagosCuota();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    // ── Helpers de rol ────────────────────────────────────────
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
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->sucursalSeleccionada;
        }
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscar = false;

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : [];
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->buscar = false;
    }

    // ── Tipo de reporte ──────────────────────────────────────
    public string $tipoReporte = 'ventas';

    // ── Filtros ───────────────────────────────────────────────
    #[Validate([
        'required',
        'date'
    ], message: [
        'required' => 'La fecha "Desde" es obligatoria.',
        'date'     => 'La fecha "Desde" no tiene un formato de fecha válido.'
    ])]
    public $desde;

    #[Validate([
        'required',
        'date',
        'after_or_equal:desde'
    ], message: [
        'required'         => 'La fecha "Hasta" es obligatoria.',
        'date'             => 'La fecha "Hasta" no tiene un formato de fecha válido.',
        'after_or_equal'   => 'La fecha "Hasta" debe ser igual o posterior a la fecha "Desde".'
    ])]
    public $hasta;

    public $buscar;

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_ventas.listar'), 403);

        $this->desde  = now()->format('Y-m-d');
        $this->hasta  = now()->format('Y-m-d');
        $this->buscar = false;

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada = $empresaId;

            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }
    }

    public function listarRegistrosPagos(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->buscar = true;
    }

    // ── Exportables ───────────────────────────────────────────

    private function pdfRoute(): string
    {
        return match ($this->tipoReporte) {
            'ventas_detallado' => 'reporte.imprimir_pdf_ventas_vendedor',
            'resumen_ventas'   => 'reporte.imprimir_pdf_ventas_cliente',
            default            => 'reporte.imprimir_pdf_reporte_ventas',
        };
    }

    private function excelRoute(): string
    {
        return match ($this->tipoReporte) {
            'ventas_detallado' => 'reporte.imprimir_excel_ventas_vendedor',
            'resumen_ventas'   => 'reporte.imprimir_excel_ventas_cliente',
            'para_estudio'     => 'reporte.imprimir_excel_para_estudio',
            default            => 'reporte.imprimir_excel_reporte_ventas',
        };
    }

    public function imprimirPdf(): void
    {
        try {
            if (!auth()->user()->can('reporte_ventas.exportar')) {
                session()->flash('error', 'Acceso denegado. No tiene permisos para generar este reporte.');
                return;
            }
            $url = route($this->pdfRoute(), $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function imprimirExcel(): void
    {
        try {
            if (!auth()->user()->can('reporte_ventas.exportar')) {
                session()->flash('error', 'Acceso denegado. No tiene permisos para generar este reporte.');
                return;
            }
            $url = route($this->excelRoute(), $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function exportarFormato14(): void
    {
        try {
            if (!auth()->user()->can('reporte_ventas.exportar')) {
                session()->flash('error', 'Acceso denegado. No tiene permisos para generar este reporte.');
                return;
            }
            $url = route('reporte.formato_14_excel', $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    private function buildExportableParams(): array
    {
        return [
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'id_empresa'  => $this->resolverIdEmpresa()  ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();
        $idSucursal      = $this->resolverIdSucursal();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        $ventasBrutas     = 0;
        $pagosCuotas      = 0;
        $notasCredito     = 0;
        $notasDebito      = 0;
        $notasVentas      = 0;
        $listaVentas      = [];
        $listaNotaCredito = [];
        $listaNotaDebito  = [];
        $listaPagosCuotas = [];
        $listaNotasVentas = [];
        $labels           = [];
        $ingresos         = [];

        if ($this->buscar && $idEmpresaActiva) {

            $desde = $this->desde;
            $hasta = $this->hasta;

            // ── Gráfico: ingreso por día ──────────────────────
            $fechaCiclo = Carbon::parse($desde);
            $fechaFin   = Carbon::parse($hasta);

            while ($fechaCiclo->lte($fechaFin)) {
                $dia      = $fechaCiclo->format('Y-m-d');
                $labels[] = $fechaCiclo->format('d/m');

                $vbDia  = $this->venta->listarVentasPorTipo(['01', '03'], $dia, $dia, 2,$idEmpresaActiva, $idSucursal, false);
                $ncDia  = $this->venta->listarVentasPorTipo(['07'],       $dia, $dia, 2,$idEmpresaActiva, $idSucursal, true);
                $ndDia  = $this->venta->listarVentasPorTipo(['08'],       $dia, $dia, 2,$idEmpresaActiva, $idSucursal, true);
                $nvDia  = $this->venta->listarVentasNotasVentas($dia, $dia, 2, $idEmpresaActiva, $idSucursal);
                $pcDia  = $this->pagosCuota->listarPagosRealizados($dia, $dia, 2, $idEmpresaActiva, $idSucursal);

                $ingresos[] = round($vbDia - $ncDia + $ndDia + $nvDia + $pcDia, 2);
                $fechaCiclo->addDay();
            }

            // ── Totales del período completo ──────────────────
            $ventasBrutas = $this->venta->listarVentasPorTipo(['01', '03'], $desde, $hasta, 2,$idEmpresaActiva, $idSucursal, false);
            $notasCredito = $this->venta->listarVentasPorTipo(['07'],       $desde, $hasta, 2,$idEmpresaActiva, $idSucursal, true);
            $notasDebito  = $this->venta->listarVentasPorTipo(['08'],       $desde, $hasta, 2,$idEmpresaActiva, $idSucursal, true);
            $notasVentas  = $this->venta->listarVentasNotasVentas($desde, $hasta, 2, $idEmpresaActiva, $idSucursal);
            $pagosCuotas  = $this->pagosCuota->listarPagosRealizados($desde, $hasta, 2, $idEmpresaActiva, $idSucursal);

            // ── Listas para tablas de detalle ─────────────────
            $listaVentas      = $this->venta->listarVentasPorTipo(['01', '03'], $desde, $hasta, 1,$idEmpresaActiva, $idSucursal, false);
            $listaNotaCredito = $this->venta->listarVentasPorTipo(['07'],       $desde, $hasta, 1,$idEmpresaActiva, $idSucursal, true);
            $listaNotaDebito  = $this->venta->listarVentasPorTipo(['08'],       $desde, $hasta, 1,$idEmpresaActiva, $idSucursal, true);
            $listaPagosCuotas = $this->pagosCuota->listarPagosRealizados($desde, $hasta, 1, $idEmpresaActiva, $idSucursal);
            $listaNotasVentas = $this->venta->listarVentasNotasVentas($desde, $hasta, 1, $idEmpresaActiva, $idSucursal);

            $this->dispatch('grafico', labels: $labels, totales: $ingresos);
        }

        return view('livewire.reporte.reporte-venta', compact(
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'empresas',
            'notasVentas', 'listaNotasVentas',
            'ventasBrutas', 'pagosCuotas',
            'notasCredito', 'notasDebito',
            'listaVentas', 'listaNotaCredito',
            'listaNotaDebito', 'listaPagosCuotas'
        ));
    }
}
