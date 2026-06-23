<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta_detalle extends Model
{
    use HasFactory;
    protected $table      = 'ventas_detalle';
    protected $primaryKey = 'id_venta_detalle';
    protected $fillable   = [
        'id_venta', 'id_producto', 'venta_detalle_valor_unitario', 'venta_detalle_precio_unitario',
        'venta_detalle_nombre_producto', 'pres_nombre', 'pres_factor', 'venta_detalle_cantidad', 'venta_detalle_total_igv',
        'venta_detalle_porcentaje_igv', 'venta_detalle_total_icbper', 'venta_detalle_valor_total',
        'venta_detalle_importe_total', 'id_producto_precios',
    ];

    public static function guardar_venta_detalle($detalle_venta)
    {
        $venta_exitosa = new Venta_detalle();
        $venta_exitosa->fill($detalle_venta);
        $venta_exitosa->save();
        return $venta_exitosa;
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Ventas::class, 'id_venta', 'id_venta');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'id_pro', 'id_pro');
    }
}
