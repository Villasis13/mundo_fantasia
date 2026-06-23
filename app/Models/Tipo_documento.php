<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipo_documento extends Model
{
    use HasFactory;
    protected $table      = 'tipo_documento';
    protected $primaryKey = 'id_tipo_documento';
}
