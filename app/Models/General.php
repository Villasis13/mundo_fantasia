<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use App\Support\Legacy\QrLib;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
class General extends Model
{
    use HasFactory;
    private $ventas;
    private $logs;

    public function __construct()
    {
        parent::__construct();
        $this->ventas = new Ventas();
        $this->logs   = new Logs();
    }

    public function save_files($archivo, $rutaImg)
    {
        try {
            if ($archivo) {
                $originalName = $archivo->getClientOriginalName();
                $timestamp    = now()->format('Ymd_His');
                $newFileName  = $timestamp . '_' . $originalName;
                $subcarpeta   = $rutaImg;

                if (!Storage::disk('public_uploads')->exists($subcarpeta)) {
                    Storage::disk('public_uploads')->makeDirectory($subcarpeta);
                }

                $path = $archivo->storeAs($subcarpeta, $newFileName, 'public_uploads');
                return 'uploads/' . $path;
            }
            return [];
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return [];
        }
    }

    public function convertir_webp($imagen, $ruta)
    {
        $ruta = public_path($ruta);

        if (!File::exists($ruta)) {
            File::makeDirectory($ruta, 0755, true);
        }

        $nombreLimpio = Str::slug(pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME));

        $filename     = time() . '-' . $nombreLimpio;
        $webpFilename = $filename . '.webp';
        $webpFilePath = $ruta . '/' . $webpFilename;

        // Convertir a webp
        $img = Image::make($imagen)->encode('webp', 80);
        $img->save($webpFilePath);

        // Retornar ruta relativa (mejor práctica)
        return 'productos/' . $webpFilename;
    }

    public function generar_qr($id)
    {
        QrLib::load();
        $venta   = $this->ventas->listar_venta_x_id($id);
        $cliente = DB::table('clientes')
            ->join('tipo_documento', 'clientes.id_tipo_documento', '=', 'tipo_documento.id_tipo_documento')
            ->where('clientes.id_clientes', $venta->id_clientes)
            ->first();
        $empresa = DB::table('empresa')->where('id_empresa', 1)->first();

        $nombre_qr  = $empresa->empresa_ruc . '-' . $venta->venta_tipo . '-' . $venta->venta_serie . '-' . $venta->venta_correlativo;
        $contenido_qr = $empresa->empresa_ruc . '|' . $venta->venta_tipo . '|' . $venta->venta_serie . '|' . $venta->venta_correlativo
            . '|' . $venta->venta_totaligv . '|' . $venta->venta_total . '|' . date('Y-m-d', strtotime($venta->venta_fecha))
            . '|' . $cliente->tipodocumento_codigo . '|' . $cliente->cliente_numero;

        $dirQr   = base_path('ApiFacturacion/imagenqr');
        $ruta_qr = $dirQr . DIRECTORY_SEPARATOR . $nombre_qr . '.png';

        if (!is_dir($dirQr)) {
            mkdir($dirQr, 0755, true);
        }

        if (!file_exists($ruta_qr)) {
            \QRcode::png($contenido_qr, $ruta_qr, 'H - mejor', '3');
        }
        return $ruta_qr;
    }

    public function consultar_documento_migo($DocumentType, $num)
    {
        try {
            $DocumentType = (int) $DocumentType;
            $num          = trim((string) $num);

            if ($num === '') {
                return ['success' => false, 'message' => 'Número de documento es obligatorio.'];
            }

            $token = config('services.tokens.api_migo');
            if (!$token) {
                return ['success' => false, 'message' => 'Token MIGO no configurado en el servidor.'];
            }

            if ($DocumentType === 4) {
                if (strlen($num) != 11) {
                    return ['success' => false, 'message' => 'El ruc debe contener 11 dígitos.'];
                }
                if (!ctype_digit($num)) {
                    return ['success' => false, 'message' => 'El ruc debe contener solo números.'];
                }
                if ($num === '00000000000') {
                    return ['success' => true, 'message' => 'Proveedor Extranjero', 'tipo_respuesta' => 'text-success',
                        'data' => ['nombre' => null, 'direccion' => null, 'condicion_de_domicilio' => 'HABIDO']];
                }

                $resp = $this->migo_post('https://api.migo.pe/api/v1/ruc', ['token' => $token, 'ruc' => $num]);
                if (!isset($resp['success']) || !$resp['success']) {
                    return ['success' => false, 'message' => $resp['message'] ?? 'Error consultando RUC.'];
                }

                $cond        = $resp['condicion_de_domicilio'] ?? '';
                $tipoRespuesta = ($cond === 'NO HABIDO') ? 'text-danger' : 'text-success';
                $out = ['success' => true, 'message' => 'Datos Encontrados', 'tipo_respuesta' => $tipoRespuesta,
                    'data' => ['nombre' => $resp['nombre_o_razon_social'] ?? null, 'direccion' => $resp['direccion'] ?? null,
                        'condicion_de_domicilio' => $cond]];

                if ($cond === 'NO HABIDO') {
                    $out['warning'] = 'Este ruc se encuentra como NO HABIDO.';
                }
                return $out;
            }

            if (strlen($num) != 8) {
                return ['success' => false, 'message' => 'El DNI debe contener 8 dígitos.'];
            }
            if (!ctype_digit($num)) {
                return ['success' => false, 'message' => 'El DNI debe contener solo números.'];
            }
            if ($num === '00000000') {
                return ['success' => true, 'message' => 'CLIENTE GENERAL', 'tipo_respuesta' => 'text-success',
                    'data' => ['nombre' => 'CLIENTE GENERAL', 'direccion' => null, 'condicion_de_domicilio' => 'HABIDO']];
            }

            $resp = $this->migo_post('https://api.migo.pe/api/v1/dni', ['token' => $token, 'dni' => $num]);
            if (!isset($resp['success']) || !$resp['success']) {
                return ['success' => false, 'message' => $resp['message'] ?? 'Error consultando DNI.'];
            }

            return ['success' => true, 'message' => 'Datos Encontrados', 'tipo_respuesta' => 'text-success',
                'data' => ['nombre' => $resp['nombre'] ?? null, 'direccion' => null, 'condicion_de_domicilio' => 'HABIDO']];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ocurrió un error interno.'];
        }
    }

    private function migo_post($url, $fields)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'Error cURL: ' . $err];
        }
        curl_close($ch);

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return ['success' => false, 'message' => 'Respuesta inválida del API.'];
        }
        return $json;
    }

    public function actualizarStockPorDetalle($detalleVenta, $operacion = 'sumar', $idSucursal = null)
    {
        if (!$idSucursal) {
            $primerDetalle = collect($detalleVenta)->first();
            if ($primerDetalle && isset($primerDetalle->id_venta)) {
                $idSucursal = DB::table('ventas')
                    ->select('id_sucursal')
                    ->where('id_venta', $primerDetalle->id_venta)
                    ->value('id_sucursal');
            }
        }

        foreach ($detalleVenta as $detalle) {
            $producto = Productos::find($detalle->id_pro);
            if (!$producto) {
                throw new \Exception("No se encontró el producto con ID {$detalle->id_pro}.");
            }

            if ((int) $producto->id_medida == 58 && (float) $producto->impuesto_bolsa == 0) {
                $cantidad = (float) $detalle->venta_detalle_cantidad;
                if ($idSucursal) {
                    $psQuery = DB::table('producto_sucursal')
                        ->where('id_pro', $producto->id_pro)
                        ->where('id_sucursal', $idSucursal);

                    $operacion === 'sumar'
                        ? $psQuery->increment('ps_stock', $cantidad)
                        : $psQuery->decrement('ps_stock', $cantidad);
                }
            }
        }
    }
}
