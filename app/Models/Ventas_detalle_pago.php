<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Ventas_detalle_pago extends Model
{
    use HasFactory;
    protected $table      = 'ventas_detalle_pagos';
    protected $primaryKey = 'id_venta_detalle_pago';

    public static function listar_formas_x_idventa($id)
    {
        try {
            return DB::table('ventas_detalle_pagos as vdp')
                ->join('tipo_pago as tp', 'vdp.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('vdp.id_venta', $id)
                ->get();
        } catch (\Exception $e) {
            (new Logs())->insertarLog($e);
            return collect();
        }
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Ventas::class, 'id_venta', 'id_venta');
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(Tipo_pago::class, 'id_tipo_pago', 'id_tipo_pago');
    }
}
