<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Grupo extends Model
{
    protected $table      = 'grupos';
    protected $primaryKey = 'id_grupo';

    protected $fillable = [
        'id_users',
        'grupo_nombre',
        'grupo_microtime',
        'grupo_estado',
    ];
}
