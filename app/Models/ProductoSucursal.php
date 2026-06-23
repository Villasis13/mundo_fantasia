<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoSucursal extends Model
{
    protected $table      = 'producto_sucursal';
    protected $primaryKey = 'id_ps';

    public $timestamps = true;

    protected $fillable = [
        'id_pro',
        'id_sucursal',
        'id_tipo_afectacion',
        'ps_precio_uni',
        'ps_precio_uni_2',
        'ps_precio_uni_3',
        'ps_stock',
        'ps_stock_minimo',
        'ps_porcen_igv',
        'ps_estado',
    ];

    protected $casts = [
        'ps_precio_uni'   => 'decimal:2',
        'ps_precio_uni_2' => 'decimal:2',
        'ps_precio_uni_3' => 'decimal:2',
        'ps_stock'        => 'decimal:2',
        'ps_stock_minimo' => 'decimal:2',
        'ps_porcen_igv'   => 'decimal:2',
        'ps_estado'       => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'id_pro', 'id_pro');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function tipoAfectacion(): BelongsTo
    {
        return $this->belongsTo(Tipo_afectacion::class, 'id_tipo_afectacion', 'id_tipo_afectacion');
    }
}
