<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaPagar extends Model
{
    use SoftDeletes;

    protected $table      = 'cuentas_pagar';
    protected $primaryKey = 'id_cuenta_pagar';
}
