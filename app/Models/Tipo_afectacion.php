<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipo_afectacion extends Model
{
    use HasFactory;
    protected $table      = 'tipo_afectacion';
    protected $primaryKey = 'id_tipo_afectacion';
}
