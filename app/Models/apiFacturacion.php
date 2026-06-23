<?php

namespace App\Models;

use App\Models\Signature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//use App\Models\Signature;
class apiFacturacion extends Model
{
    use HasFactory;

    public const OK = 1;
    public const ERROR_GENERAL = 2;
    public const ERROR_SUNAT_RECHAZO = 3;
    public const ERROR_COMUNICACION = 4;
    public const ERROR_BD = 5;
    public static function EnviarComprobanteElectronico(
        $emisor,
        string $nombre,
        string $ruta_archivo_xml,
        string $ruta_archivo_cdr,
        int $id_venta,
        $estado = null
    ){
        $flg_firma = 0;

        // 1) Rutas base
        $xmlRelative = $ruta_archivo_xml . $nombre . '.XML';
        $xmlPath = self::resolvePath($xmlRelative, $estado); // path real (public_path si aplica)

        // 2) Obtener certificado/clave de la empresa
        $consultaEmpresa = Empresa::where('id_empresa', '=', $emisor->id_empresa)->first();
        $ruta_firma = $consultaEmpresa?->empresa_ruta_certificado ?? '';
        $pass_firma = $consultaEmpresa?->empresa_clave_certificado ?? '';

        // 3) Firmar XML
        $resp = Signature::signature_xml($flg_firma, $xmlPath, $ruta_firma, $pass_firma);
        if (($resp['respuesta'] ?? null) !== 'ok') {
            return self::ERROR_GENERAL;
        }

        // 3.1) Guardar codigo hash en BD
        if (!self::updateVenta($id_venta, ['venta_codigo_hash' => $resp['hash_cpe']])) {
            return self::ERROR_BD;
        }

        // 4) Guardar ruta del XML en BD
        if (!self::updateVenta($id_venta, ['venta_rutaXML' => $xmlRelative])) {
            return self::ERROR_BD;
        }

        // 5) Crear ZIP del XML
        $zipName = $nombre . '.ZIP';
        $zipRelative = $ruta_archivo_xml . $zipName;
        $zipPath = self::resolvePath($zipRelative, $estado);

        if (!self::createZipWithFile($zipPath, $xmlPath, $nombre . '.XML')) {
            return self::ERROR_GENERAL;
        }

        // 6) Enviar a SUNAT
        $ws = config('services.facturacion.url');
        $zipBase64 = base64_encode(file_get_contents($zipPath));

        $xml_envio = self::buildSoapSendBill($emisor, $zipName, $zipBase64);

        [$httpcode, $response] = self::sendSoap($ws, $xml_envio);
        if ($httpcode !== 200 || !$response) {
            self::updateVenta($id_venta, ['venta_respuesta_sunat' => 'Hubo o existe un problema de conexión']);
            return self::ERROR_COMUNICACION;
        }

        // 7) Procesar respuesta (CDR o fault)
        $doc = new \DOMDocument();
        $doc->loadXML($response);

        $appResponseNode = $doc->getElementsByTagName('applicationResponse')->item(0);
        if (!$appResponseNode || !$appResponseNode->nodeValue) {
            // Fault
            $codigo  = $doc->getElementsByTagName('faultcode')->item(0)?->nodeValue ?? 'SIN_CODIGO';
            $mensaje = $doc->getElementsByTagName('faultstring')->item(0)?->nodeValue ?? 'Sin mensaje';
            $estado_sunat = "Ocurrió un error con código: {$codigo} Msje: {$mensaje}";

            self::updateVenta($id_venta, ['venta_respuesta_sunat' => $estado_sunat]);
            return self::ERROR_SUNAT_RECHAZO;
        }

        // 8) Guardar CDR (la respuesta llega en base64, tú la guardas como ZIP R-*.ZIP)
        $cdrZipBinary = base64_decode($appResponseNode->nodeValue);

        $cdrZipName = 'R-' . $zipName; // R-NOMBRE.ZIP
        $cdrZipRelative = $ruta_archivo_cdr . $cdrZipName;
        $cdrZipPath = self::resolvePath($cdrZipRelative, $estado);

        if (!self::writeFile($cdrZipPath, $cdrZipBinary)) {
            return self::ERROR_GENERAL;
        }

        // 9) Extraer XML del CDR: R-NOMBRE.XML
        $cdrXmlName = 'R-' . $nombre . '.XML';
        $cdrXmlRelative = $ruta_archivo_cdr . $cdrXmlName;
        $cdrXmlPath = self::resolvePath($cdrXmlRelative, $estado);

        if (!self::extractFromZip($cdrZipPath, self::resolvePath($ruta_archivo_cdr, $estado), $cdrXmlName)) {
            return self::ERROR_GENERAL;
        }

        // 10) Guardar ruta del CDR XML en BD
        if (!self::updateVenta($id_venta, ['venta_rutaCDR' => $cdrXmlRelative])) {
            return self::ERROR_BD;
        }

        // 11) Leer el CDR y actualizar respuesta SUNAT
        $estado_sunat = self::leerEstadoDesdeCdr($cdrXmlPath);
        if (!self::updateVenta($id_venta, ['venta_respuesta_sunat' => $estado_sunat])) {
            return self::ERROR_BD;
        }

        return self::OK;
    }
    public static function EnviarResumenComprobantes(
        $emisor,
        string $nombre,
        string $ruta_archivo_xml,
        ?int $id_empresa = null,
        $estado = null
    ) {

        /* =========================
         * 1) RESOLVER RUTA DEL XML
         * ========================= */
        $xmlRelative = $ruta_archivo_xml . $nombre . '.XML';
        $xmlPath = self::resolvePath($xmlRelative, $estado);


        /* =========================
         * 2) OBTENER CERTIFICADO DIGITAL
         * ========================= */
        $empresa = Empresa::find($id_empresa);

        $ruta_firma = $empresa?->empresa_ruta_certificado ?? '';
        $pass_firma = $empresa?->empresa_clave_certificado ?? '';


        /* =========================
         * 3) FIRMAR XML
         * ========================= */
        $codigoHash = null;
        $resp = Signature::signature_xml(0, $xmlPath, $ruta_firma, $pass_firma);

        if (($resp['respuesta'] ?? '') !== 'ok') {
            return ["result" => self::ERROR_GENERAL, "ticket" => "0", "mensaje" => "No se pudo firmar el XML del resumen."];
        }
        $codigoHash = $resp['hash_cpe'];


        /* =========================
         * 4) CREAR ZIP DEL XML
         * ========================= */
        $zipName = $nombre . '.ZIP';
        $zipRelative = $ruta_archivo_xml . $zipName;
        $zipPath = self::resolvePath($zipRelative, $estado);

        if (!self::createZipWithFile($zipPath, $xmlPath, $nombre . '.XML')) {
            return ["result" => self::ERROR_GENERAL, "ticket" => "0", "mensaje" => "No se pudo generar el archivo ZIP del resumen."];
        }


        /* =========================
         * 5) PREPARAR ENVÍO A SUNAT
         * ========================= */
        $ws = config('services.facturacion.url');

        $zipBase64 = base64_encode(file_get_contents($zipPath));

        $xml_envio = self::buildSoapSendSummary($emisor, $zipName, $zipBase64);


        /* =========================
         * 6) ENVIAR SOAP A SUNAT
         * ========================= */
        [$httpcode, $response] = self::sendSoap($ws, $xml_envio);
        if ($httpcode !== 200 || !$response) {
            return ["result" => self::ERROR_COMUNICACION, "ticket" => "0", "mensaje" => "Problema de conexión con SUNAT."];
        }


        /* =========================
         * 7) PROCESAR RESPUESTA DE SUNAT
         * ========================= */
        $doc = new \DOMDocument();
        $doc->loadXML($response);

        $ticketNode = $doc->getElementsByTagName('ticket')->item(0);

        if ($ticketNode && $ticketNode->nodeValue) {
            return ["result" => self::OK, "ticket" => $ticketNode->nodeValue, "mensaje" => "Ticket generado correctamente.",'hash' => $codigoHash];
        }


        /* =========================
         * 8) ERROR DEVUELTO POR SUNAT
         * ========================= */
        $codigo = $doc->getElementsByTagName("faultcode")->item(0)?->nodeValue ?? 'SIN_CODIGO';
        $mensaje = $doc->getElementsByTagName("faultstring")->item(0)?->nodeValue ?? 'Sin mensaje';

        return ["result" => self::ERROR_SUNAT_RECHAZO, "ticket" => "0", "mensaje" => "Error {$codigo}: {$mensaje}"];
    }
    public static function ConsultarTicket(
        $emisor,
        array $cabecera,
        string $ticket,
        string $ruta_archivo_cdr,
        int $tipo,
        ?int $id_empresa = null,
        $estado = null
    ) {
        $nombre = $emisor->empresa_ruc.'-'.$cabecera['tipocomp'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'];

        $ws = config('services.facturacion.url');
        $xml_envio = self::buildSoapGetStatus($emisor, $ticket);

        [$httpcode, $response] = self::sendSoap($ws, $xml_envio);

        if ($httpcode !== 200 || !$response) {
            return ['result' => self::ERROR_COMUNICACION, 'mensaje' => 'Problema de conexión con SUNAT.'];
        }

        $doc = new \DOMDocument();
        $doc->loadXML($response);

        // ✅ IGUAL QUE EL ORIGINAL: usar isset()
        if (isset($doc->getElementsByTagName('content')->item(0)->nodeValue)) {

            $cdr = $doc->getElementsByTagName('content')->item(0)->nodeValue;
            $cdr = base64_decode($cdr);

            // ✅ Resolver ruta igual que el original pero con public_path si aplica
            $zipPath = self::resolvePath($ruta_archivo_cdr, $estado) . "R-{$nombre}.ZIP";
            $xmlPath = self::resolvePath($ruta_archivo_cdr, $estado) . "R-{$nombre}.XML";
            $xmlRelative = $ruta_archivo_cdr . "R-{$nombre}.XML";

            file_put_contents($zipPath, $cdr);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo(self::resolvePath($ruta_archivo_cdr, $estado), "R-{$nombre}.XML");
                $zip->close();
            }

            $xml_cdr = simplexml_load_file($xmlPath);
            $xml_cdr->registerXPathNamespace('c', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $Description = $xml_cdr->xpath('///c:Description');
            $mensaje_consulta = (string)($Description[0] ?? 'Aceptado');

            self::updateEstadoConsultaTicket($emisor,$tipo, $ticket, $xmlRelative, $mensaje_consulta);

            return ['result' => self::OK, 'mensaje' => $mensaje_consulta];

        } else {

            $codigo  = $doc->getElementsByTagName('faultcode')->item(0)?->nodeValue ?? 'SIN_CODIGO';
            $mensaje = $doc->getElementsByTagName('faultstring')->item(0)?->nodeValue ?? 'Sin mensaje';
            $mensaje_consulta = "Error {$codigo}: {$mensaje}";

            self::updateEstadoConsultaTicket($emisor,$tipo, $ticket, '', $mensaje_consulta);

            return ['result' => self::ERROR_SUNAT_RECHAZO, 'mensaje' => $mensaje_consulta];
        }
    }
    private static function resolvePath(string $path, ?int $estado = null): string
    {
        return ($estado === 1) ? public_path($path) : $path;
    }
    private static function updateVenta(int $idVenta, array $data): bool
    {
        $venta = Ventas::find($idVenta);
        if (!$venta) return false;

        foreach ($data as $k => $v) {
            $venta->{$k} = $v;
        }
        return (bool) $venta->save();
    }
    private static function createZipWithFile(string $zipPath, string $filePath, string $nameInZip): bool
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFile($filePath, $nameInZip);
            $zip->close();
            return true;
        }
        return false;
    }
    private static function writeFile(string $path, string $content): bool
    {
        // Crear carpeta si no existe
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return file_put_contents($path, $content) !== false;
    }
//    private static function extractFromZip(string $zipPath, string $extractTo, string $fileName): bool
//    {
//        $zip = new \ZipArchive();
//        if ($zip->open($zipPath) === true) {
//            $zip->extractTo($extractTo, $fileName);
//            $zip->close();
//            return true;
//        }
//        return false;
//    }
    private static function extractFromZip(string $zipPath, string $extractTo, string $fileName): bool
    {
        $zip = new \ZipArchive();

        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
            return false;
        }

        if (!is_dir($extractTo)) {
            if (!mkdir($extractTo, 0775, true) && !is_dir($extractTo)) {
                $zip->close();
                return false;
            }
        }

        $ok = $zip->extractTo($extractTo, [$fileName]);
        $zip->close();

        $xmlPath = rtrim($extractTo, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        return $ok && is_file($xmlPath);
    }
    private static function buildSoapSendBill($emisor, string $fileName, string $contentBase64): string
    {
        // OJO: aquí asumo que $emisor tiene empresa_ruc, usuario_sol, clave_sol como en tu código.
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <soapenv:Header>
        <wsse:Security>
            <wsse:UsernameToken>
                <wsse:Username>'.$emisor->empresa_ruc.$emisor->empresa_usuario_sol.'</wsse:Username>
                <wsse:Password>'.$emisor->empresa_clave_sol.'</wsse:Password>
            </wsse:UsernameToken>
        </wsse:Security>
        </soapenv:Header>
        <soapenv:Body>
        <ser:sendBill>
            <fileName>'.$fileName.'</fileName>
            <contentFile>'.$contentBase64.'</contentFile>
        </ser:sendBill>
        </soapenv:Body>
    </soapenv:Envelope>';
    }
    private static function sendSoap(string $ws, string $xml_envio): array
    {
        $header = [
            'Content-type: text/xml; charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ',
            'Content-length: ' . strlen($xml_envio), // ojo: en tu código decía "Content-lenght"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $ws);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_envio);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if (config('services.facturacion.cacert') == 1) {
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.pem");
        }

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$httpcode, $response];
    }
    private static function leerEstadoDesdeCdr(string $rutaCdrXml): string
    {
        $xml_cdr = simplexml_load_file($rutaCdrXml);
        if ($xml_cdr === false) {
            $errs = libxml_get_errors();
            libxml_clear_errors();
            return 'No se pudo leer el CDR: ' . ($errs[0]->message ?? 'Error desconocido');
        }

        $xml_cdr->registerXPathNamespace('c', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $Description = $xml_cdr->xpath('///c:Description');
        $desc = (string)($Description[0] ?? '');

        return $desc !== '' ? $desc : 'Respuesta CDR sin descripción.';
    }
    private static function buildSoapSendSummary($emisor, string $fileName, string $contentBase64): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
        xmlns:ser="http://service.sunat.gob.pe"
        xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">

        <soapenv:Header>
            <wsse:Security>
                <wsse:UsernameToken>
                    <wsse:Username>'.$emisor->empresa_ruc.$emisor->empresa_usuario_sol.'</wsse:Username>
                    <wsse:Password>'.$emisor->empresa_clave_sol.'</wsse:Password>
                </wsse:UsernameToken>
            </wsse:Security>
        </soapenv:Header>

        <soapenv:Body>
            <ser:sendSummary>
                <fileName>'.$fileName.'</fileName>
                <contentFile>'.$contentBase64.'</contentFile>
            </ser:sendSummary>
        </soapenv:Body>

    </soapenv:Envelope>';
    }
    private static function buildSoapGetStatus($emisor, string $ticket): string
    {
            return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:ser="http://service.sunat.gob.pe"
            xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">

            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>'.$emisor->empresa_ruc.$emisor->empresa_usuario_sol.'</wsse:Username>
                        <wsse:Password>'.$emisor->empresa_clave_sol.'</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>

            <soapenv:Body>
                <ser:getStatus>
                    <ticket>'.$ticket.'</ticket>
                </ser:getStatus>
            </soapenv:Body>

        </soapenv:Envelope>';
    }
    public static function EnviarGuiaRemision(
        $emisor,
        string $nombre,
        string $ruta_archivo_xml,
        string $ruta_archivo_cdr,
        int $id_guia,
        $estado = null
    ) {
        $xmlRelative = $ruta_archivo_xml . $nombre . '.XML';
        $xmlPath = self::resolvePath($xmlRelative, $estado);

        $consultaEmpresa = Empresa::where('id_empresa', $emisor->id_empresa)->first();
        $ruta_firma = $consultaEmpresa?->empresa_ruta_certificado ?? '';
        $pass_firma = $consultaEmpresa?->empresa_clave_certificado ?? '';

        $resp = Signature::signature_xml(0, $xmlPath, $ruta_firma, $pass_firma);
        if (($resp['respuesta'] ?? null) !== 'ok') {
            return self::ERROR_GENERAL;
        }

        if (!self::updateGuia($id_guia, ['guia_ruta_xml' => $xmlRelative])) {
            return self::ERROR_BD;
        }

        $zipName = $nombre . '.ZIP';
        $zipRelative = $ruta_archivo_xml . $zipName;
        $zipPath = self::resolvePath($zipRelative, $estado);

        if (!self::createZipWithFile($zipPath, $xmlPath, $nombre . '.XML')) {
            return self::ERROR_GENERAL;
        }

        $ws = config('services.facturacion.url');
        $zipBase64 = base64_encode(file_get_contents($zipPath));
        $xml_envio = self::buildSoapSendBill($emisor, $zipName, $zipBase64);

        [$httpcode, $response] = self::sendSoap($ws, $xml_envio);
        if ($httpcode !== 200 || !$response) {
            self::updateGuia($id_guia, ['guia_respuesta_sunat' => 'Hubo o existe un problema de conexión']);
            return self::ERROR_COMUNICACION;
        }

        $doc = new \DOMDocument();
        $doc->loadXML($response);

        $appResponseNode = $doc->getElementsByTagName('applicationResponse')->item(0);
        if (!$appResponseNode || !$appResponseNode->nodeValue) {
            $codigo  = $doc->getElementsByTagName('faultcode')->item(0)?->nodeValue ?? 'SIN_CODIGO';
            $mensaje = $doc->getElementsByTagName('faultstring')->item(0)?->nodeValue ?? 'Sin mensaje';
            self::updateGuia($id_guia, ['guia_respuesta_sunat' => "Error {$codigo}: {$mensaje}"]);
            return self::ERROR_SUNAT_RECHAZO;
        }

        $cdrZipBinary  = base64_decode($appResponseNode->nodeValue);
        $cdrZipName    = 'R-' . $zipName;
        $cdrZipRelative = $ruta_archivo_cdr . $cdrZipName;
        $cdrZipPath    = self::resolvePath($cdrZipRelative, $estado);

        if (!self::writeFile($cdrZipPath, $cdrZipBinary)) {
            return self::ERROR_GENERAL;
        }

        $cdrXmlName    = 'R-' . $nombre . '.XML';
        $cdrXmlRelative = $ruta_archivo_cdr . $cdrXmlName;
        $cdrXmlPath    = self::resolvePath($cdrXmlRelative, $estado);

        if (!self::extractFromZip($cdrZipPath, self::resolvePath($ruta_archivo_cdr, $estado), $cdrXmlName)) {
            return self::ERROR_GENERAL;
        }

        if (!self::updateGuia($id_guia, ['guia_ruta_cdr' => $cdrXmlRelative])) {
            return self::ERROR_BD;
        }

        $estado_sunat = self::leerEstadoDesdeCdr($cdrXmlPath);
        self::updateGuia($id_guia, [
            'guia_respuesta_sunat' => $estado_sunat,
            'guia_estado_sunat'    => 1,
            'guia_fecha_envio'     => now(),
            'guia_estado'          => 'enviado',
        ]);

        return self::OK;
    }
    private static function updateGuia(int $idGuia, array $data): bool
    {
        return (bool) DB::table('guias_remision')->where('id_guia', $idGuia)->update($data);
    }
    private static function updateEstadoConsultaTicket($emisor,int $tipo, string $ticket, string $rutaCdr, string $mensaje): void
    {
        if ($tipo === 1) {
            Envio_resumen::where([['envio_resumen_ticket', $ticket],['id_empresa',$emisor->id_empresa]])->update([
                'envio_resumen_nombreCDR' => $rutaCdr,
                'envio_resumen_estadosunat_consulta' => $mensaje,
            ]);
            return;
        }

        Ventas_anulado::join('ventas as v','v.id_venta','=','ventas_anulados.id_venta')
            ->where([['ventas_anulados.venta_anulacion_ticket', $ticket],['v.id_empresa',$emisor->id_empresa]])->update([
            'ventas_anulados.venta_anulado_rutaCDR' => $rutaCdr,
            'ventas_anulados.venta_anulado_estado_sunat' => $mensaje,
        ]);
    }
}
