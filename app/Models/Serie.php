<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Serie extends Model
{
    use HasFactory;
    protected $table      = 'serie';
    protected $primaryKey = 'id_serie';

    public function listarSerie($co)
    {
        return DB::table('serie')->where([['tipocomp', $co], ['estado', 1]])->get();
    }

    public function listarDatos_Serie($co)
    {
        return DB::table('serie')->where([['tipocomp', $co], ['estado', 1]])->first();
    }

    public function listarSerie_caja($tipo, $id_caja)
    {
        return DB::table('serie')
            ->where([['tipocomp', $tipo], ['estado', 1]])
            ->where('id_caja_numero', $id_caja)
            ->get();
    }

    public function sacar_serie($id_serie, $id_caja_numero)
    {
        return DB::table('serie')
            ->where([['id_serie', $id_serie], ['id_caja_numero', $id_caja_numero]])
            ->first();
    }

    public function cajaNumero(): BelongsTo
    {
        return $this->belongsTo(Caja_numero::class, 'id_caja_numero', 'id_caja_numero');
    }
}
