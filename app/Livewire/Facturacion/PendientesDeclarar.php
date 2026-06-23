<?php

namespace App\Livewire\Facturacion;

use App\Http\Controllers\FacturacionController;
use App\Models\Logs;
use App\Models\Ventas_detalle_pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PendientesDeclarar extends Component
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

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->orderBy('ut.id_tienda')
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

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
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        if ($this->esAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->adminEmpresaId();
        }
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
    }

    // ── Filtros ───────────────────────────────────────────────
    public string $tipoVenta   = '';
    public string $fechaInicio = '';
    public string $fechaFinal  = '';
    public bool   $buscar      = false;

    // ── Confirmación de acciones ──────────────────────────────
    public ?int   $idVentaConfirmacion = null;
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
        abort_if(!auth()->user()->can('pendientes_declarar.listar'), 403);

        $this->fechaInicio = now()->format('Y-m-d');
        $this->fechaFinal  = now()->format('Y-m-d');

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

    public function confirmarEnvioSunat(int $idVenta): void
    {
        $this->prepararConfirmacion(
            'enviar_sunat',
            $idVenta,
            '¿Está seguro que desea enviar a SUNAT este comprobante?'
        );
    }

    public function confirmarCambiarEstadoEnviado(int $idVenta): void
    {
        $this->prepararConfirmacion(
            'estado_enviado',
            $idVenta,
            '¿Confirma que desea marcar este comprobante como aceptado por SUNAT?'
        );
    }

    public function confirmarCambiarEstadoAnulado(int $idVenta): void
    {
        $this->prepararConfirmacion(
            'estado_anulado',
            $idVenta,
            '¿Confirma que desea marcar este comprobante como anulado?'
        );
    }

    public function confirmarEnvioMasivo(): void
    {
        $this->prepararConfirmacion(
            'envio_masivo',
            null,
            '¿Está seguro que desea enviar todas las facturas filtradas a SUNAT?'
        );
    }

    public function ejecutarConfirmacion(): void
    {
        if ($this->accionConfirmacion === '') {
            return;
        }

        $accion  = $this->accionConfirmacion;
        $idVenta = $this->idVentaConfirmacion;

        $this->dispatch('cerrarModalConfirmacionPendientes');

        switch ($accion) {
            case 'enviar_sunat':
                if ($idVenta) {
                    $this->enviarSunat($idVenta);
                }
                break;
            case 'estado_enviado':
                if ($idVenta) {
                    $this->cambiarEstadoEnviado($idVenta);
                }
                break;
            case 'estado_anulado':
                if ($idVenta) {
                    $this->cambiarEstadoAnulado($idVenta);
                }
                break;
            case 'envio_masivo':
                $this->envioMasivo();
                break;
        }

        $this->limpiarConfirmacion();
    }

    private function prepararConfirmacion(string $accion, ?int $idVenta, string $mensaje): void
    {
        $this->accionConfirmacion  = $accion;
        $this->idVentaConfirmacion = $idVenta;
        $this->mensajeConfirmacion = $mensaje;

        $this->dispatch('abrirModalConfirmacionPendientes');
    }

    private function limpiarConfirmacion(): void
    {
        $this->accionConfirmacion  = '';
        $this->idVentaConfirmacion = null;
        $this->mensajeConfirmacion = '';
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->esSuperAdmin()) {
            if ($this->sucursalSeleccionada) {
                $query->where('v.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($this->empresaSeleccionada > 0) {
                $query->where('v.id_empresa', $this->empresaSeleccionada);
            }
        } elseif ($this->esAdmin()) {
            $empresaId = $this->empresaSeleccionada > 0
                ? $this->empresaSeleccionada
                : $this->adminEmpresaId();
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

    public function enviarSunat(int $idVenta): void
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
                session()->flash('error', $data['result']['message'] ?? 'Error al enviar comprobante.');
            }
            $this->listar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error inesperado al enviar el comprobante.');
        }
    }

    public function cambiarEstadoEnviado(int $idVenta): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para cambiar el estado del comprobante.');
            return;
        }

        try {
            $venta    = DB::table('ventas')->where('id_venta', $idVenta)->first();
            $respuesta = "La Factura numero {$venta->venta_serie}-{$venta->venta_correlativo}, ha sido aceptada";
            DB::table('ventas')->where('id_venta', $idVenta)->update([
                'venta_tipo_envio'      => 1,
                'venta_estado_sunat'    => 1,
                'venta_fecha_envio'     => now(),
                'venta_respuesta_sunat' => $respuesta,
            ]);
            session()->flash('success', '¡Fue actualizada como enviada y aceptada!');
            $this->listar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al actualizar el estado del comprobante.');
        }
    }

    public function cambiarEstadoAnulado(int $idVenta): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para cambiar el estado del comprobante.');
            return;
        }

        try {
            DB::table('ventas')->where('id_venta', $idVenta)->update([
                'venta_tipo_envio'      => 1,
                'venta_estado_sunat'    => 1,
                'venta_fecha_envio'     => now(),
                'venta_respuesta_sunat' => 'El comprobante ya esta informado y se encuentra con estado anulado o rechazado',
                'anulado_sunat'         => 1,
                'venta_cancelar'        => 0,
            ]);
            session()->flash('success', '¡Fue actualizada como anulada!');
            $this->listar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al actualizar el estado del comprobante.');
        }
    }

    public function envioMasivo(): void
    {
        if (!auth()->user()->can('pendientes_declarar.crear')) {
            session()->flash('error', 'No tienes permiso para realizar envío masivo.');
            return;
        }

        try {
            if (!$this->buscar) return;

            $idEmpresaActiva = $this->resolverIdEmpresa();
            if (!$idEmpresaActiva) return;

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
                ->where('v.venta_tipo', '01');

            $this->aplicarFiltroUbicacion($query);

            if ($this->fechaInicio && $this->fechaFinal) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->fechaInicio, $this->fechaFinal]);
            }

            $ventasParaEnviar = $query->pluck('v.id_venta');
            $controller       = app(FacturacionController::class);
            $errores          = 0;

            foreach ($ventasParaEnviar as $idVenta) {
                $response = $controller->crear_xml_enviar_sunat(new Request(['id_venta' => $idVenta]));
                $data     = json_decode($response->getContent(), true);
                if (($data['result']['code'] ?? 0) !== 1) {
                    $errores++;
                }
            }

            if ($errores === 0) {
                session()->flash('success', "Se enviaron {$ventasParaEnviar->count()} comprobante(s) a SUNAT correctamente.");
            } else {
                session()->flash('error', "{$errores} comprobante(s) no pudieron enviarse. Verifique el estado de cada uno.");
            }
            $this->listar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error durante el envío masivo.');
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
        if ($this->buscar && ($idEmpresaActiva || $this->esSuperAdmin())) {
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
                ->where('v.venta_tipo', '<>', '20');

            $this->aplicarFiltroUbicacion($query);

            if ($this->tipoVenta !== '') {
                $query->where('v.venta_tipo', $this->tipoVenta);
            }
            if ($this->fechaInicio && $this->fechaFinal) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->fechaInicio, $this->fechaFinal]);
            }

            $ventas = $query->orderBy('v.venta_fecha', 'asc')->get();
            foreach ($ventas as $v) {
                $v->tipo_pago = Ventas_detalle_pago::listar_formas_x_idventa($v->id_venta);
            }
        }

        $ventasCant = 0;
        if ($idEmpresaActiva || $this->esSuperAdmin()) {
            $ventasCant = DB::table('ventas as v')
                ->where('v.venta_estado_sunat', 0)
                ->where('v.venta_tipo', '<>', '20');
            $this->aplicarFiltroUbicacion($ventasCant);

            $ventasCant = $ventasCant->count();
        }

        return view('livewire.facturacion.pendientes-declarar', compact(
            'empresas', 'ventas', 'ventasCant',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
