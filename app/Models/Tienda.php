<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    protected $table      = 'tiendas';
    protected $primaryKey = 'id_tienda';

    protected $fillable = [
        'id_empresa',
        'tienda_nombre',
        'tienda_principal',
        'tienda_microtime',
        'tienda_estado',
    ];
}
