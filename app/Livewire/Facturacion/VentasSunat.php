<?php

namespace App\Livewire\Facturacion;

use App\Models\apiFacturacion;
use App\Models\Empresa;
use App\Models\GeneradorXML;
use App\Models\General;
use App\Models\Logs;
use App\Models\Serie;
use App\Models\Ventas;
use App\Models\Ventas_anulado;
use App\Models\Ventas_detalle_pago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class VentasSunat extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios (se resuelven en boot) ─────────────────────
    private $logs;
    private $ventas;
    private $empresas;
    private $general;

    public function boot(): void
    {
        $this->logs = new Logs();
        $this->ventas   = new Ventas();
        $this->empresas = new Empresa();
        $this->general  = new General();

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
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
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

    // ── Filtros ───────────────────────────────────────────────
    public string $tipoVenta   = '0';
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
        abort_if(!auth()->user()->can('historial_ventas_sunat.listar'), 403);

        $this->fechaInicio = now()->format('Y-m-d');
        $this->fechaFinal  = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada = $empresaId;
            $this->buscar = true;
        }
    }

    public function listar(): void
    {
        $this->buscar = true;
    }
    // ── Exportables ───────────────────────────────────────────
    // Despacha un evento JS que construye la URL con todos los
    // parámetros actuales (incluye empresa y sucursal resueltos)
    // y la abre en una nueva pestaña — sin recargar el componente.
    public function exportarExcel(): void
    {
        if (!auth()->user()->can('historial_ventas_sunat.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $this->dispatch('abrirNuevaPestana', url: $this->buildExportableUrl(1));
    }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('historial_ventas_sunat.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $this->dispatch('abrirNuevaPestana', url: $this->buildExportableUrl(2));
    }

    private function buildExportableUrl(int $tipo): string
    {
        $ruta = $tipo === 1
            ? 'facturacion/imprimir_excel_ventas_declaras'
            : 'facturacion/imprimir_pdf_ventas_declaras';

        return url($ruta) . '?' . http_build_query([
                'tipo_venta'   => $this->tipoVenta,
                'fecha_inicio' => $this->fechaInicio,
                'fecha_final'  => $this->fechaFinal,
                'id_empresa'   => $this->resolverIdEmpresa() ?? 0,
                'id_sucursal'  => $this->sucursalSeleccionada,
            ]);
    }

    public function confirmarComunicacionBaja(int $idVenta): void
    {
        $this->idVentaConfirmacion = $idVenta;
        $this->accionConfirmacion  = 'comunicacion_baja';
        $this->mensajeConfirmacion = '¿Está seguro que desea anular este comprobante? Se enviará una Comunicación de Baja a SUNAT.';
        $this->dispatch('abrirModalVentasSunat');
    }
    public function ejecutarConfirmacion(): void
    {
        if ($this->accionConfirmacion === '' || !$this->idVentaConfirmacion) return;

        if (!auth()->user()->can('historial_ventas_sunat.cambiar_estado')) {
            $this->dispatch('cerrarModalVentasSunat');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        $this->dispatch('cerrarModalVentasSunat');

        if ($this->accionConfirmacion === 'comunicacion_baja') {
            $this->ejecutarComunicacionBaja($this->idVentaConfirmacion);
        }

        $this->limpiarConfirmacion();
    }
    private function limpiarConfirmacion(): void
    {
        $this->accionConfirmacion  = '';
        $this->idVentaConfirmacion = null;
        $this->mensajeConfirmacion = '';
    }

    private function ejecutarComunicacionBaja(int $id): void
    {
        try {
            $items = $this->ventas->listar_soloventa_x_id($id);
            if (!$items) {
                session()->flash('error', 'No se encontró información de la venta solicitada.');
                return;
            }

            $id_empresa = $items->id_empresa;

            $filaSerie = DB::table('serie')
                ->where([['tipocomp', '=', 'RA'], ['id_empresa', '=', $id_empresa]])
                ->first();
            if (!$filaSerie) {
                session()->flash('error', 'No se pudo obtener la configuración de serie/correlativo para RC.');
                return;
            }

            $emisor = $this->empresas->listar_datos_empresa_x_id($id_empresa);
            if (!$emisor) {
                session()->flash('error', 'No se encontró información registrada de la empresa.');
                return;
            }
            if (!$emisor->empresa_ruta_certificado) {
                session()->flash('error', 'No se encontró configurado el certificado digital (PEM) de la empresa.');
                return;
            }
            if (!$emisor->empresa_clave_certificado) {
                session()->flash('error', 'No se encontró configurada la clave del certificado digital de la empresa.');
                return;
            }

            // ── Serie y correlativo ───────────────────────────
            $serie       = date('Ymd');
            $correlativo = ($filaSerie->serie !== $serie) ? 1 : ((int) $filaSerie->correlativo + 1);

            $cabecera = [
                'tipocomp'      => 'RA',
                'serie'         => $serie,
                'correlativo'   => $correlativo,
                'fecha_emision' => date('Y-m-d'),
                'fecha_envio'   => date('Y-m-d'),
            ];

            // ── Carpeta XML ───────────────────────────────────
            $ruta = 'ApiFacturacion/xml/';
            if (!is_dir($ruta)) {
                @mkdir($ruta, 0775, true);
            }
            if (!is_dir($ruta)) {
                session()->flash('error', "No se pudo crear o acceder a la carpeta de XML: {$ruta}.");
                return;
            }

            $nombreXml = "{$emisor->empresa_ruc}-{$cabecera['tipocomp']}-{$cabecera['serie']}-{$cabecera['correlativo']}";
            $nom       = rtrim($ruta, '/\\') . DIRECTORY_SEPARATOR . $nombreXml;

            // ── Generar XML ───────────────────────────────────
            GeneradorXML::CrearXmlBajaDocumentos($emisor, $cabecera, $items, $nom);

            // ── Enviar a SUNAT ────────────────────────────────
            $envio   = apiFacturacion::EnviarResumenComprobantes($emisor, $nombreXml, 'ApiFacturacion/xml/', 1);
            $message = $envio['mensaje'] ?? 'No se obtuvo respuesta del envío.';
            if (($envio['result'] ?? 0) !== apiFacturacion::OK) {
                session()->flash('error', $message);
                return;
            }

            $ticket = $envio['ticket'] ?? null;
            if (!$ticket || $ticket === '0') {
                session()->flash('error', 'SUNAT no devolvió un ticket válido.');
                return;
            }

            $ruta_xml = 'ApiFacturacion/xml/' . $nombreXml . '.XML';

            // ── Guardar registro de venta anulada ─────────────
            $ventaAnulada                             = new Ventas_anulado();
            $ventaAnulada->venta_anulado_fecha        = date('Y-m-d', strtotime($items->venta_fecha));
            $ventaAnulada->venta_anulado_serie        = $cabecera['serie'];
            $ventaAnulada->venta_anulado_correlativo  = $cabecera['correlativo'];
            $ventaAnulada->venta_anulacion_ticket     = $ticket;
            $ventaAnulada->venta_anulado_rutaXML      = $ruta_xml;
            $ventaAnulada->venta_anulado_estado_sunat = $message;
            $ventaAnulada->id_venta                   = $id;
            $ventaAnulada->id_users                   = Auth::id();
            if (!$ventaAnulada->save()) {
                session()->flash('error', 'Ocurrió un error al guardar la venta anulada.');
                return;
            }

            // ── Actualizar serie y correlativo ────────────────
            if ($filaSerie->serie !== $serie) {
                $serieActualizar        = Serie::find($filaSerie->id_serie);
                $serieActualizar->serie = $serie;
                if (!$serieActualizar->save()) {
                    session()->flash('error', 'No se pudo actualizar la serie de la comunicación de baja.');
                    return;
                }
            }

            $serieActualizar              = Serie::find($filaSerie->id_serie);
            $serieActualizar->correlativo = $correlativo;
            if (!$serieActualizar->save()) {
                session()->flash('error', 'No se pudo actualizar el correlativo de la comunicación de baja.');
                return;
            }

            // ── Marcar venta como anulada ─────────────────────
            $ventaCambiar                = Ventas::find($id);
            $ventaCambiar->anulado_sunat  = 1;
            $ventaCambiar->venta_cancelar = 0;
            if (!$ventaCambiar->save()) {
                session()->flash('error', 'Ocurrió un error al cambiar el estado de la venta anulada.');
                return;
            }

            // ── Consultar ticket ──────────────────────────────
            $consulta = apiFacturacion::ConsultarTicket($emisor, $cabecera, $ticket, 'ApiFacturacion/cdr/', 2);
            if (($consulta['result'] ?? 0) != 1) {
                session()->flash('error', $consulta['mensaje'] ?? 'No se obtuvo respuesta al consultar el ticket.');
                return;
            }

            // ── Ajuste de stock ───────────────────────────────
            if ($items->venta_tipo === '01') {

                $detalleVenta = $this->ventas->listar_venta_detalle_x_id_venta($items->id_venta);
                if (empty($detalleVenta)) {
                    session()->flash('success', 'Comprobante declarado a SUNAT. No se pudo actualizar el stock (sin detalle).');
                    $this->listar();
                    return;
                }

                try {
                    DB::beginTransaction();
                    $this->general->actualizarStockPorDetalle($detalleVenta, 'sumar', $items->id_sucursal);
                    DB::commit();
                    session()->flash('success', $consulta['mensaje'] ?? 'Ticket consultado y stock actualizado correctamente.');
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->logs->insertarLog($e);
                    session()->flash('error', 'Comprobante declarado a SUNAT, pero ocurrió un error al actualizar el stock.');
                }

            } elseif ($items->venta_tipo === '07' && in_array($items->venta_codigo_motivo_nota, ['01', '02'])) {

                $ventaAfectada = Ventas::where([
                    ['venta_serie',       '=', $items->serie_modificar],
                    ['venta_correlativo', '=', $items->correlativo_modificar],
                    ['id_empresa',        '=', $items->id_empresa],
                    ['id_sucursal',       '=', $items->id_sucursal],
                ])->first();

                if (!$ventaAfectada) {
                    session()->flash('success', 'Comprobante declarado a SUNAT, pero no se encontró la venta afectada para revertir.');
                    $this->listar();
                    return;
                }

                $detalleVenta = $this->ventas->listar_venta_detalle_x_id_venta($items->id_venta);
                if (empty($detalleVenta)) {
                    session()->flash('success', 'Comprobante declarado a SUNAT. No se pudo actualizar el stock (sin detalle).');
                    $this->listar();
                    return;
                }

                try {
                    DB::beginTransaction();
                    $this->general->actualizarStockPorDetalle($detalleVenta, 'restar',$items->id_sucursal);

                    $ventaAfectadaActualizada                = Ventas::find($ventaAfectada->id_venta);
                    $ventaAfectadaActualizada->anulado_sunat  = 0;
                    $ventaAfectadaActualizada->venta_cancelar = 1;

                    if (!$ventaAfectadaActualizada->save()) {
                        DB::rollBack();
                        session()->flash('error', 'Comprobante declarado a SUNAT, pero no se pudo revertir la venta afectada.');
                        return;
                    }

                    DB::commit();
                    session()->flash('success', $consulta['mensaje'] ?? 'Ticket consultado y stock actualizado correctamente.');
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->logs->insertarLog($e);
                    session()->flash('error', 'Comprobante declarado a SUNAT, pero ocurrió un error al actualizar el stock.');
                }

            } else {
                session()->flash('success', $consulta['mensaje'] ?? 'Comunicación de baja procesada correctamente.');
            }

            $this->listar();

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error interno. Inténtelo nuevamente o contacte al administrador.');
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
                    'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_razonsocial',
                    'c.cliente_nombre', 'c.cliente_direccion', 'c.cliente_telefono',
                    'c.cliente_correo', 'c.cliente_estado',
                    'mo.id_moneda', 'mo.moneda', 'mo.abreviado', 'mo.abrstandar',
                    'mo.simbolo', 'mo.activo',
                    'u.id_users', 'u.nombre_users'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.venta_estado_sunat', '=', 1);

            if ($this->tipoVenta !== '0') {
                $query->where('v.venta_tipo', $this->tipoVenta);
            } else {
                $query->where('v.venta_tipo', '<>', '20');
            }

            if ($this->fechaInicio && $this->fechaFinal) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->fechaInicio, $this->fechaFinal]);
            }

            $this->aplicarFiltroUbicacion($query);

            $ventas = $query->orderBy('v.venta_fecha', 'asc')->get();

            foreach ($ventas as $v) {
                $v->resumen = DB::table('envio_resumen_detalle as er')
                    ->join('ventas as v', 'er.id_venta', '=', 'v.id_venta')
                    ->where('er.id_venta', '=', $v->id_venta)->first();

            }
        }

        return view('livewire.facturacion.ventas-sunat', compact(
            'empresas', 'ventas',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
