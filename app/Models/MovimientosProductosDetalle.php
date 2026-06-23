<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientosProductosDetalle extends Model
{
    protected $table      = 'movimientos_productos_detalle';
    protected $primaryKey = 'id_movimientos_productos_detalle';

    public $timestamps = true;

    protected $fillable = [
        'id_movimientos_productos',
        'id_pro',
        'movimientos_productos_detalle_cantidad',
        'movimientos_productos_detalle_estado',
    ];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimientos_productos::class, 'id_movimientos_productos', 'id_movimientos_productos');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'id_pro', 'id_pro');
    }
}
