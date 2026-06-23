<?php

namespace App\Livewire\Facturacion;

use App\Http\Controllers\FacturacionController;
use App\Models\Logs;
use App\Models\Ventas_detalle_pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ResumenDiario extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
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
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
        }
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    // ── Filtros ───────────────────────────────────────────────
    public string $fechaHoy = '';
    public bool   $buscar   = false;

    // ── Confirmación de acciones ──────────────────────────────
    public string $accionConfirmacion  = '';
    public string $mensajeConfirmacion = '';

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscar = false;

        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = [];
        }
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->buscar = false;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('resumen_diario.listar'), 403);

        $this->fechaHoy = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada = $empresaId;
            $this->buscar = true;
        }
    }

    // ── Acciones ──────────────────────────────────────────────

    public function listar(): void
    {
        $this->buscar = true;
    }

    public function confirmarEnvioResumen(): void
    {
        $this->accionConfirmacion  = 'enviar_resumen';
        $this->mensajeConfirmacion = '¿Está seguro que desea enviar el Resumen Diario a SUNAT?';

        $this->dispatch('abrirModalConfirmacionResumen');
    }

    public function ejecutarConfirmacion(): void
    {
        if ($this->accionConfirmacion !== 'enviar_resumen') {
            return;
        }

        $this->dispatch('cerrarModalConfirmacionResumen');
        $this->enviarResumenSunat();
        $this->limpiarConfirmacion();
    }

    private function limpiarConfirmacion(): void
    {
        $this->accionConfirmacion  = '';
        $this->mensajeConfirmacion = '';
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            $empresaId = $this->empresaSeleccionada > 0
                ? $this->empresaSeleccionada
                : $this->empresaUsuario();
            if ($empresaId) {
                $query->where('v.id_empresa', $empresaId);
            }
        } else {
            $tiendaId = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($tiendaId) {
                $query->where('v.id_sucursal', (int) $tiendaId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }
    }

    // ── Acciones SUNAT ────────────────────────────────────────

    public function enviarResumenSunat(): void
    {
        if (!auth()->user()->can('resumen_diario.crear')) {
            session()->flash('error', 'No tienes permiso para enviar el resumen diario.');
            return;
        }

        try {
            $idEmpresaActiva = $this->resolverIdEmpresa();
            if (!$idEmpresaActiva) {
                session()->flash('error', 'No se pudo determinar la empresa activa.');
                return;
            }

            $idSucursal = $this->sucursalSeleccionada;

            $controller = app(FacturacionController::class);
            $response   = $controller->crear_enviar_resumen_sunat(new Request([
                'fecha'      => $this->fechaHoy,
                'id_empresa' => $idEmpresaActiva,
                'id_sucursal' => $idSucursal,
            ]));
            $data = json_decode($response->getContent(), true);

            if (($data['result']['code'] ?? 0) === 1) {
                session()->flash('success', $data['result']['message'] ?? '¡Resumen diario enviado a SUNAT!');
            } else {
                session()->flash('error', $data['result']['message'] ?? 'Error al enviar el resumen diario.');
            }
            $this->listar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error inesperado al enviar el resumen diario.');
        }
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        $ventas = collect();
        if ($this->buscar && $idEmpresaActiva) {
            $query = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'c.id_tipo_documento','c.cliente_numero','c.cliente_razonsocial','c.cliente_nombre','c.cliente_direccion','c.cliente_telefono','c.cliente_correo','c.cliente_estado',
                    'mo.id_moneda','mo.moneda','mo.abreviado','mo.abrstandar','mo.simbolo','mo.activo',
                    'u.id_users','u.nombre_users',
                    'td.tipodocumento_codigo','td.tipo_documento_identidad','td.tipo_documento_identidad_abr','td.tipo_documento_estado'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->join('tipo_documento as td', 'c.id_tipo_documento', '=', 'td.id_tipo_documento')
                ->where('v.venta_estado_sunat', 0)
                ->where('v.venta_tipo', '<>', '01')
                ->where('v.venta_tipo', '<>', '20')
                ->where('v.tipo_documento_modificar', '<>', '01')
                ->where('v.venta_tipo_envio', '<>', 1);

            $this->aplicarFiltroUbicacion($query);

            if ($this->fechaHoy) {
                $query->whereDate('v.venta_fecha', $this->fechaHoy);
            }

            $ventas = $query->orderBy('v.venta_fecha', 'asc')->get();
            foreach ($ventas as $v) {
                $v->tipo_pago = Ventas_detalle_pago::listar_formas_x_idventa($v->id_venta);
            }
        }

        return view('livewire.facturacion.resumen-diario', compact(
            'empresas', 'ventas',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
