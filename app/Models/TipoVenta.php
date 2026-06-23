<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoVenta extends Model
{
    protected $table      = 'tipo_venta';
    protected $primaryKey = 'id_tipo_venta';

    public $timestamps = true;

    protected $fillable = [
        'tipo_venta_nombre',
        'tipo_venta_estado',
    ];

    protected $casts = [
        'tipo_venta_estado' => 'boolean',
    ];
}
