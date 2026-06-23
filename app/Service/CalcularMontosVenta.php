<?php

namespace App\Service;

use App\Models\Logs;
use App\Models\Productos;
use Illuminate\Support\Facades\DB;

class CalcularMontosVenta
{
    private $logs;
    private $producto;
    public function __construct()
    {
        $this->logs = new Logs();
        $this->producto = new Productos();
    }

    public function calcularMontos($items, $porcentajaIgv, $idSucursal = null, bool $esGratuita = false){
        try {

            $items = json_decode(json_encode($items));
            // Inicialización de montos
            $montos = [
                'gravada'     => 0,
                'exonerada'   => 0,
                'inafectada'  => 0,
                'gratuito'    => 0,
                'igv'         => 0,
                'impuesto'    => 0, // Impuesto a la bolsa (si aplica)
            ];

            // Transferencia a título gratuito: todos los ítems van a gratuita, total = 0
            if ($esGratuita) {
                foreach ($items as $item) {
                    $cantidad = (float) $item->cantidad;
                    if ($cantidad <= 0) continue;
                    $montos['gratuito'] += round((float) $item->precio_venta * $cantidad, 2);
                }
                return array_merge(['total' => 0], $montos);
            }

            foreach ($items as $item) {

                $cantidad       = (float) $item->cantidad;
                $precioUnitario = round((float) $item->precio_venta, 2);

                if ($cantidad <= 0) continue;

                // Servicio (sin id_pro): usar tipo afectación del ítem directamente
                if (is_null($item->id_pro)) {
                    $tipoAfectacion = (int) ($item->id_tipo_afectacion ?? 1);
                    $tasa = $porcentajaIgv / 100;
                    if ($tipoAfectacion === 1) {
                        $base = round($precioUnitario, 2);
                        $montos['gravada'] += round($base * $cantidad, 2);
                        $montos['igv']     += round($base * $tasa * $cantidad, 2);
                    } elseif ($tipoAfectacion === 2) {
                        $montos['exonerada'] += $precioUnitario * $cantidad;
                    } elseif ($tipoAfectacion === 3) {
                        $montos['inafectada'] += $precioUnitario * $cantidad;
                    }
                    continue;
                }

                $producto = $this->producto->datos_productos($item->id_pro,$idSucursal);

                // Si no existe el producto, se omite
                if (!$producto) {
                    continue;
                }
                $tipoAfectacion  = (int) $producto->id_tipo_afectacion;

                // Evitar división entre cero
                if ($cantidad <= 0) {
                    continue;
                }

                if ($producto->impuesto_bolsa == 0){

                    switch ($tipoAfectacion) {
                        case 1: // GRAVADA (precio incluye IGV)

                            $tasa = $porcentajaIgv / 100; // ej: 18 -> 0.18 | 10.5 -> 0.105

                            $base = round($precioUnitario, 2);
                            $igv  = round($base * $tasa, 2);

                            $montos['gravada'] += round($base * $cantidad, 2);
                            $montos['igv']     += round($igv  * $cantidad, 2);

                            break;

                        case 2: // EXONERADA
                            $montos['exonerada'] += $precioUnitario * $cantidad;
                            break;

                        case 3: // INAFECTADA
                            $montos['inafectada'] += $precioUnitario * $cantidad;
                            break;

                        case 4: // GRATUITA
                            $montos['gratuito'] += $precioUnitario * $cantidad;
                            break;
                    }

                }

                if ($producto->impuesto_bolsa == 1){ // APLICA IMPUESTO A BOLSA
                    $montos['impuesto'] += 0.50 * $cantidad;
                }
            }

            $total = round($montos['gravada'] + $montos['exonerada'] + $montos['inafectada'] + $montos['igv'] + $montos['impuesto'], 2);


            return array_merge(['total' => round($total, 2)], $montos);

        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            return [
                'total'       => 0,
                'gravada'     => 0,
                'igv'         => 0,
                'exonerada'   => 0,
                'inafectada'  => 0,
                'impuesto'    => 0,
                'gratuito'    => 0,
            ];
        }
    }
}
