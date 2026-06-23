<?php

namespace App\Livewire\Facturacion;

use App\Http\Controllers\FacturacionController;
use App\Models\Logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AlertasSunat extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        if ($this->esAdmin()) {
            return $this->adminEmpresaId();
        }
        $idSucursal = (int) session('sucursal_activa_id', 0);
        if (!$idSucursal) return null;
        $id = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
        return $id ? (int) $id : null;
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->esSuperAdmin()) {
            if ($this->sucursalSeleccionada) {
                $query->where('v.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($this->empresaSeleccionada) {
                $query->where('v.id_empresa', $this->empresaSeleccionada);
            }
        } elseif ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($this->sucursalSeleccionada) {
                $query->where('v.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($empresaId) {
                $query->where('v.id_empresa', $empresaId);
            }
        } else {
            $idSucursal = (int) session('sucursal_activa_id', 0);
            $query->where('v.id_sucursal', $idSucursal);
        }
    }

    // ── Confirmación de acciones ──────────────────────────────
    public ?int   $idVentaConfirmacion = null;
    public string $accionConfirmacion  = '';
    public string $mensajeConfirmacion = '';

    public function mount(): void
    {
        abort_if(!auth()->user()->can('alertas_sunat.listar'), 403);

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();

                if (collect($this->sucursalesDisponibles)->count() === 1) {
                    $this->sucursalSeleccionada = collect($this->sucursalesDisponibles)->first()->id_sucursal;
                }
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get()
            : [];
    }

    // ── Acciones ──────────────────────────────────────────────

    public function confirmarEnvio(int $idVenta): void
    {
        $this->idVentaConfirmacion = $idVenta;
        $this->accionConfirmacion  = 'enviar';
        $this->mensajeConfirmacion = '¿Desea intentar enviar este comprobante a SUNAT?';
        $this->dispatch('abrirModalAlertasSunat');
    }

    public function confirmarMarcarEnviado(int $idVenta): void
    {
        $this->idVentaConfirmacion = $idVenta;
        $this->accionConfirmacion  = 'marcar_enviado';
        $this->mensajeConfirmacion = '¿Confirma marcar este comprobante como aceptado por SUNAT manualmente?';
        $this->dispatch('abrirModalAlertasSunat');
    }

    public function confirmarIgnorar(int $idVenta): void
    {
        $this->idVentaConfirmacion = $idVenta;
        $this->accionConfirmacion  = 'ignorar';
        $this->mensajeConfirmacion = '¿Desea marcar este comprobante como procesado (anulado/ignorado)?';
        $this->dispatch('abrirModalAlertasSunat');
    }

    public function ejecutarConfirmacion(): void
    {
        if (!$this->accionConfirmacion || !$this->idVentaConfirmacion) return;

        $accion  = $this->accionConfirmacion;
        $idVenta = $this->idVentaConfirmacion;

        $this->dispatch('cerrarModalAlertasSunat');
        $this->limpiarConfirmacion();

        match ($accion) {
            'enviar'        => $this->enviarASunat($idVenta),
            'marcar_enviado'=> $this->marcarComoEnviado($idVenta),
            'ignorar'       => $this->marcarComoIgnorado($idVenta),
            default         => null,
        };
    }

    private function limpiarConfirmacion(): void
    {
        $this->accionConfirmacion  = '';
        $this->idVentaConfirmacion = null;
        $this->mensajeConfirmacion = '';
    }

    private function enviarASunat(int $idVenta): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para enviar comprobantes a SUNAT.');
            return;
        }
        try {
            $controller = app(FacturacionController::class);
            $response   = $controller->crear_xml_enviar_sunat(new Request(['id_venta' => $idVenta]));
            $data       = json_decode($response->getContent(), true);

            if (($data['result']['code'] ?? 0) === 1) {
                session()->flash('success', $data['result']['message'] ?? '¡Comprobante enviado a SUNAT!');
            } else {
                session()->flash('error', $data['result']['message'] ?? 'Error al enviar. Verifique los datos del comprobante.');
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error interno al intentar el envío.');
        }
    }

    private function marcarComoEnviado(int $idVenta): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para esta acción.');
            return;
        }
        try {
            $venta = DB::table('ventas')->where('id_venta', $idVenta)->first();
            DB::table('ventas')->where('id_venta', $idVenta)->update([
                'venta_tipo_envio'      => 1,
                'venta_estado_sunat'    => 1,
                'venta_fecha_envio'     => now(),
                'venta_respuesta_sunat' => "Marcado manualmente como aceptado — {$venta->venta_serie}-{$venta->venta_correlativo}",
            ]);
            session()->flash('success', 'Comprobante marcado como enviado.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al actualizar el estado.');
        }
    }

    private function marcarComoIgnorado(int $idVenta): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para esta acción.');
            return;
        }
        try {
            DB::table('ventas')->where('id_venta', $idVenta)->update([
                'venta_tipo_envio'      => 1,
                'venta_estado_sunat'    => 1,
                'venta_fecha_envio'     => now(),
                'venta_respuesta_sunat' => 'Marcado manualmente como anulado/ignorado',
                'anulado_sunat'         => 1,
                'venta_cancelar'        => 0,
            ]);
            session()->flash('success', 'Comprobante marcado como ignorado.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al actualizar el estado.');
        }
    }

    // ── Query base de pendientes ──────────────────────────────

    private function buildPendientesQuery(): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('ventas as v')
            ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
            ->select(
                'v.id_venta', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_tipo', 'v.venta_fecha', 'v.venta_total',
                'v.venta_respuesta_sunat', 'v.id_empresa', 'v.id_sucursal',
                DB::raw("DATEDIFF(CURDATE(), DATE(v.venta_fecha)) as dias_pendiente"),
                DB::raw("CASE v.venta_tipo
                    WHEN '01' THEN 'Factura'
                    WHEN '03' THEN 'Boleta'
                    WHEN '07' THEN 'Nota Crédito'
                    WHEN '08' THEN 'Nota Débito'
                    ELSE v.venta_tipo END as tipo_label"),
                DB::raw("CASE WHEN c.id_tipo_documento = 4 THEN c.cliente_razonsocial ELSE c.cliente_nombre END as cliente_nombre")
            )
            ->where('v.venta_estado_sunat', 0)
            ->where('v.venta_tipo', '<>', '20');

        $this->aplicarFiltroUbicacion($query);
        return $query;
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        // ── Contadores para tarjetas ──────────────────────────
        $contadores = [
            'hoy'       => 0,
            'atrasados' => 0,
            'con_error' => 0,
            'mes'       => 0,
        ];

        $atrasados  = collect();
        $conError   = collect();
        $hoy        = collect();

        if ($idEmpresaActiva || (!$this->esSuperAdmin() && !$this->esAdmin())) {
            $hoy = (clone $this->buildPendientesQuery())
                ->whereDate('v.venta_fecha', today())
                ->orderBy('v.venta_fecha')
                ->get();

            $atrasados = (clone $this->buildPendientesQuery())
                ->whereDate('v.venta_fecha', '<', today())
                ->orderBy('v.venta_fecha')
                ->get();

            $conError = (clone $this->buildPendientesQuery())
                ->whereNotNull('v.venta_respuesta_sunat')
                ->where('v.venta_respuesta_sunat', '!=', '')
                ->orderByDesc(DB::raw('DATEDIFF(CURDATE(), DATE(v.venta_fecha))'))
                ->get();

            $mesActual = now()->format('Y-m');
            $contadores['mes'] = (clone $this->buildPendientesQuery())
                ->whereRaw("DATE_FORMAT(v.venta_fecha, '%Y-%m') = ?", [$mesActual])
                ->count();

            $contadores['hoy']       = $hoy->count();
            $contadores['atrasados'] = $atrasados->count();
            $contadores['con_error'] = $conError->count();
        }

        return view('livewire.facturacion.alertas-sunat', compact(
            'empresas', 'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'contadores', 'hoy', 'atrasados', 'conError'
        ));
    }
}
