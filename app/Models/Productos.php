<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Productos extends Model
{
    use HasFactory;
    protected $table      = 'productos';
    protected $primaryKey = 'id_pro';
    private $logs;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->logs = new Logs();
    }

    public function datos_productos($id, $idSucursal = null)
    {
        try {
            $result = DB::table('productos as p')
                ->select(
                    'p.id_pro', 'p.id_empresa', 'p.id_ca', 'p.id_medida', 'p.pro_nombre',
                    'p.pro_codigo', 'p.pro_descripcion', 'p.pro_foto', 'p.pro_estado', 'p.impuesto_bolsa',
                    'ps.*', 'ta.descripcion'
                )
                ->join('producto_sucursal as ps',      'ps.id_pro',              '=', 'p.id_pro')
                ->leftJoin('tipo_afectacion as ta',   'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
                ->where([['p.pro_estado', 1], ['ps.ps_estado', 1], ['p.id_pro', $id], ['ps.id_tienda', $idSucursal]])
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $result = null;
        }
        return $result;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_ca', 'id_ca');
    }

    public function medida(): BelongsTo
    {
        return $this->belongsTo(Medida::class, 'id_medida', 'id_medida');
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(ProductoSucursal::class, 'id_pro', 'id_pro');
    }

    public function movimientosDetalle(): HasMany
    {
        return $this->hasMany(MovimientosProductosDetalle::class, 'id_pro', 'id_pro');
    }
}
