<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoGuia extends Model
{
    protected $table = 'motivos_guias';
    protected $primaryKey = 'id_motivo_guia';

    protected $fillable = [
        'id_users',
        'motivo_guia_concepto',
        'motivo_guia_codigo',
        'motivo_guia_microtime',
        'motivo_guia_estado',
    ];

    /** Motivos activos como colección (concepto + código SUNAT) */
    public static function activos()
    {
        return static::where('motivo_guia_estado', 1)
            ->orderBy('motivo_guia_concepto')
            ->get(['id_motivo_guia', 'motivo_guia_concepto', 'motivo_guia_codigo']);
    }

    /** Código SUNAT de un motivo por su concepto ('' si no tiene) */
    public static function codigoPorConcepto(?string $concepto): string
    {
        if (!$concepto) return '';
        return (string) (static::where('motivo_guia_concepto', $concepto)
            ->where('motivo_guia_estado', 1)
            ->value('motivo_guia_codigo') ?? '');
    }
}
