<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PagoCuentaPagar extends Model
{
    use SoftDeletes;

    protected $table      = 'pagos_cuentas_pagar';
    protected $primaryKey = 'id_pago_cp';
}
