<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tipo_ndebito extends Model
{
    use HasFactory;
    protected $table      = 'tipo_ndebitos';
    protected $primaryKey = 'id_tipo_ndebito';

    public static function listar_tipo_notaD_x_codigo($codigo)
    {
        try {
            return DB::table('tipo_ndebitos as t')->where('t.codigo', $codigo)->first();
        } catch (\Exception $e) {
            (new Logs())->insertarLog($e);
            return null;
        }
    }

    public static function listar_descripcion_segun_nota_debito()
    {
        try {
            return DB::table('tipo_ndebitos as t')->where('t.estado', 0)->get();
        } catch (\Exception $e) {
            (new Logs())->insertarLog($e);
            return collect();
        }
    }
}
