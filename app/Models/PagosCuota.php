<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PagosCuota extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "pagos_cuotas";
    protected $primaryKey = "id_pagos_cuota";
    private $logs;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->logs = new Logs();
    }
    public function listarPagosRealizados($desde,$hasta,$tipo,$idEmpresaActiva,$idSucursal){
        try {

            $datos = PagosCuota::select('m.simbolo','tp.tipo_pago_nombre','vc.venta_cuota_numero','v.venta_serie','v.venta_correlativo','pagos_cuotas.pagos_cuota_fecha','pagos_cuotas.pagos_cuota_monto')
                ->join('tipo_pago as tp','tp.id_tipo_pago','=','pagos_cuotas.id_tipo_pago')
                ->join('ventas_cuotas as vc','vc.id_ventas_cuotas','=','pagos_cuotas.id_ventas_cuotas')
                ->join('ventas as v','v.id_venta','=','vc.id_venta')
                ->join('monedas as m','m.id_moneda','=','v.id_moneda')
                ->where('v.anulado_sunat','=',0)
                ->where('v.id_empresa','=',$idEmpresaActiva)
                ->whereBetween(DB::raw('DATE(pagos_cuotas.pagos_cuota_fecha)'), [$desde, $hasta]);
            if ($idSucursal){
                $datos->where('v.id_sucursal','=',$idSucursal);
            }
            if ($tipo == 1){
                $datos = $datos->get();
            }else{
                $datos = $datos->sum('pagos_cuota_monto');
            }
            return $datos;
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
        }
    }
}
