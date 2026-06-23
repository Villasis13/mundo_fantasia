<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaMovimiento extends Model
{
    use SoftDeletes;

    protected $table      = 'caja_movimientos';
    protected $primaryKey = 'id_caja_movimiento';
}
