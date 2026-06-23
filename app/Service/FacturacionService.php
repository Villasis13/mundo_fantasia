<?php

namespace App\Service;

use App\Models\apiFacturacion;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Envio_resumen;
use App\Models\Envio_resumen_detalle;
use App\Models\GeneradorXML;
use App\Models\Logs;
use App\Models\Serie;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use App\Models\Ventas;
use Illuminate\Support\Facades\DB;

class FacturacionService
{
    private Logs $logs;
    private Ventas $ventas;
    private Empresa $empresa;
    private Cliente $cliente;

    public function __construct()
    {
        $this->logs    = new Logs();
        $this->ventas  = new Ventas();
        $this->empresa = new Empresa();
        $this->cliente = new Cliente();
    }

    /**
     * Genera el XML y envía un comprobante individual (factura/boleta/nota) a SUNAT.
     * Retorna ['code' => 1, 'message' => '...'] en éxito o ['code' => 2, 'message' => '...'] en error.
     */
    public function enviarComprobante(int $idVenta): array
    {
        try {
            $venta = $this->ventas->listar_soloventa_x_id($idVenta);
            if (!$venta) {
                return $this->error("No se encontró información de la venta solicitada.");
            }

            $detalle = $this->ventas->listar_venta_detalle_x_id_venta($idVenta);
            if (!$detalle) {
                return $this->error("No se encontraron detalles asociados a la venta.");
            }

            $emisor = $this->empresa->listar_datos_empresa_x_id((int) $venta->id_empresa);
            if (!$emisor) {
                return $this->error("No se encontró información registrada de la empresa.");
            }
            if (empty($emisor->empresa_ruta_certificado)) {
                return $this->error("La empresa no tiene configurado el certificado digital (archivo PEM).");
            }
            if (empty($emisor->empresa_clave_certificado)) {
                return $this->error("La empresa no tiene configurada la clave del certificado digital.");
            }

            $cliente = $this->cliente->listar_clienteventa_x_id($venta->id_clientes);
            if (!$cliente) {
                return $this->error("No se encontró información del cliente asociado a la venta.");
            }

            $ruta = "ApiFacturacion/xml/";
            if (!file_exists($ruta)) {
                mkdir($ruta, 0775, true);
            }

            $nombre = "{$emisor->empresa_ruc}-{$venta->venta_tipo}-{$venta->venta_serie}-{$venta->venta_correlativo}";

            if ($venta->venta_tipo === '01' || $venta->venta_tipo === '03') {
                GeneradorXML::CrearXMLFactura($ruta . $nombre, $emisor, $cliente, $venta, $detalle);
            } elseif ($venta->venta_tipo === '07') {
                $descripcion = Tipo_ncredito::listar_tipo_notaC_x_codigo($venta->venta_codigo_motivo_nota);
                GeneradorXML::CrearXMLNotaCredito($ruta . $nombre, $emisor, $cliente, $venta, $detalle, $descripcion);
            } else {
                $descripcion = Tipo_ndebito::listar_tipo_notaD_x_codigo($venta->venta_codigo_motivo_nota);
                GeneradorXML::CrearXMLNotaDebito($ruta . $nombre, $emisor, $cliente, $venta, $detalle, $descripcion);
            }

            $result = apiFacturacion::EnviarComprobanteElectronico($emisor, $nombre, "ApiFacturacion/xml/", "ApiFacturacion/cdr/", $idVenta);
            if ($result !== apiFacturacion::OK) {
                $messages = [
                    apiFacturacion::ERROR_GENERAL       => "No se pudo generar o procesar el comprobante electrónico.",
                    apiFacturacion::ERROR_SUNAT_RECHAZO => "SUNAT rechazó el comprobante. Revise el detalle del rechazo.",
                    apiFacturacion::ERROR_COMUNICACION  => "No se pudo conectar con SUNAT. Intente nuevamente.",
                    apiFacturacion::ERROR_BD            => "No se pudo guardar la información del comprobante en la base de datos.",
                ];
                return $this->error($messages[$result] ?? "Error desconocido al enviar comprobante.");
            }

            $ventaActualizar = Ventas::find($idVenta);
            $ventaActualizar->venta_tipo_envio   = 1;
            $ventaActualizar->venta_estado_sunat = 1;
            $ventaActualizar->venta_fecha_envio  = date('Y-m-d H:i:s');
            $ventaActualizar->save();

            return ['code' => apiFacturacion::OK, 'message' => '¡Comprobante Enviado a Sunat!'];

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return $this->error("Ocurrió un error interno. Por favor, inténtelo nuevamente.");
        }
    }

    /**
     * Genera el resumen diario (RC) y lo envía a SUNAT para una empresa.
     * $idSucursal = 0 procesa todas las sucursales de la empresa.
     * Retorna ['code' => 1, 'message' => '...'] en éxito o ['code' => 2, 'message' => '...'] en error.
     */
    public function enviarResumenDiario(string $fecha, int $idEmpresa, int $idSucursal = 0): array
    {
        try {
            $emisor = $this->empresa->listar_datos_empresa_x_id($idEmpresa);
            if (!$emisor) {
                return $this->error("No se encontró información registrada de la empresa (emisor).");
            }
            if (empty($emisor->empresa_ruta_certificado)) {
                return $this->error("La empresa no tiene configurado el certificado digital (ruta del archivo PEM).");
            }
            if (empty($emisor->empresa_clave_certificado)) {
                return $this->error("La empresa no tiene configurada la clave del certificado digital (PEM).");
            }

            $items = $this->ventas->listar_venta_x_fecha($fecha, "01", $idEmpresa, $idSucursal ?: null);
            if (empty($items) || count($items) === 0) {
                return $this->error("No se encontraron comprobantes para generar el resumen (RC) en la fecha indicada.");
            }

            $filaSerie = DB::table('serie')
                ->where('tipocomp', 'RC')
                ->where('id_empresa', $idEmpresa)
                ->first();
            if (!$filaSerie) {
                return $this->error("No se pudo obtener la configuración de serie/correlativo para RC.");
            }

            $serie       = date('Ymd');
            $correlativo = ($filaSerie->serie !== $serie) ? 1 : ((int) $filaSerie->correlativo + 1);

            $cabecera = [
                "tipocomp"      => "RC",
                "serie"         => $serie,
                "correlativo"   => $correlativo,
                "fecha_emision" => date('Y-m-d'),
                "fecha_envio"   => date('Y-m-d'),
            ];

            $xmlDir = (config('services.facturacion.local') == 1)
                ? public_path('ApiFacturacion/xml')
                : 'ApiFacturacion/xml';

            if (!is_dir($xmlDir)) {
                @mkdir($xmlDir, 0775, true);
            }
            if (!is_dir($xmlDir)) {
                return $this->error("No se pudo crear o acceder a la carpeta de XML: {$xmlDir}.");
            }

            $nombreXml = "{$emisor->empresa_ruc}-{$cabecera['tipocomp']}-{$cabecera['serie']}-{$cabecera['correlativo']}";
            $nom       = rtrim($xmlDir, '/\\') . DIRECTORY_SEPARATOR . $nombreXml;

            GeneradorXML::CrearXMLResumenDocumentos($emisor, $cabecera, $items, $nom, $fecha);

            $respuesta = apiFacturacion::EnviarResumenComprobantes($emisor, $nombreXml, "ApiFacturacion/xml/", 1);

            $message = $respuesta['mensaje'] ?? 'No se obtuvo respuesta del envío.';
            if (($respuesta['result'] ?? 0) !== apiFacturacion::OK) {
                return $this->error($message);
            }

            $ticket = $respuesta['ticket'] ?? null;
            if (!$ticket || $ticket === '0') {
                return $this->error("SUNAT no devolvió un ticket válido.");
            }

            $envio = new Envio_resumen();
            $envio->id_empresa                = $emisor->id_empresa;
            $envio->envio_resumen_fecha       = $fecha;
            $envio->envio_resumen_serie       = $cabecera['serie'];
            $envio->envio_resumen_correlativo = $cabecera['correlativo'];
            $envio->envio_resumen_nombreXML   = 'ApiFacturacion/xml/' . $nombreXml . '.XML';
            $envio->envio_resumen_estado      = 1;
            $envio->envio_resumen_estadosunat = $message;
            $envio->envio_resumen_ticket      = $ticket;
            $envio->envio_sunat_datetime      = date('Y-m-d H:i:s');
            $envio->envio_resumen_codigo_hash = $respuesta['hash'];

            if (!$envio->save()) {
                return $this->error("Ocurrió un error al guardar el registro del resumen diario.");
            }

            $idEnvioResumen  = $envio->id_envio_resumen;
            $serieActualizar = Serie::find($filaSerie->id_serie);
            if ($filaSerie->serie !== $serie) {
                $serieActualizar->serie = $serie;
            }
            $serieActualizar->correlativo = $correlativo;
            if (!$serieActualizar->save()) {
                return $this->error("No se pudo actualizar la serie/correlativo del resumen diario.");
            }

            foreach ($items as $item) {
                $detalle                                 = new Envio_resumen_detalle();
                $detalle->id_envio_resumen               = $idEnvioResumen;
                $detalle->id_venta                       = $item->id_venta;
                $detalle->envio_resumen_detalle_condicion = 1;

                if (!$detalle->save()) {
                    return $this->error("No se pudo registrar el detalle del resumen diario.");
                }

                $ventaActualizar = Ventas::find($item->id_venta);
                $ventaActualizar->venta_tipo_envio  = 2;
                $ventaActualizar->venta_fecha_envio = date('Y-m-d H:i:s');

                if ($item->anulado_sunat == "1" && $item->venta_condicion_resumen == "1") {
                    $ventaActualizar->venta_estado_sunat      = 0;
                    $ventaActualizar->venta_condicion_resumen = 3;
                } else {
                    $ventaActualizar->venta_estado_sunat = 1;
                }

                if (!$ventaActualizar->save()) {
                    return $this->error("No se pudo actualizar el estado de una venta incluida en el resumen.");
                }
            }

            $consulta = apiFacturacion::ConsultarTicket($emisor, $cabecera, $ticket, "ApiFacturacion/cdr/", 1, $idEmpresa);
            return [
                'code'    => $consulta['result'] ?? 2,
                'message' => $consulta['mensaje'] ?? 'No se obtuvo respuesta al consultar ticket.',
            ];

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return $this->error("Ocurrió un error interno en el sistema. Por favor, inténtelo nuevamente o contacte con el administrador.");
        }
    }

    private function error(string $message): array
    {
        return ['code' => 2, 'message' => $message];
    }
}
