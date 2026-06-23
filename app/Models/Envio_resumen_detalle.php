<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Envio_resumen_detalle extends Model
{
    use HasFactory;
    protected $table = "envio_resumen_detalle";
    protected $primaryKey = "id_envio_resumen_detalle";
}
