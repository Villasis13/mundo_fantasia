<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Empresa extends Model
{
    use HasFactory;
    protected $table      = 'empresa';
    protected $primaryKey = 'id_empresa';
    private $logs;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->logs = new Logs();
    }

    public function listar_datos_empresa()
    {
        try {
            return DB::table('empresa as e')
                ->join('ubigeo as u', 'u.id_ubigeo', '=', 'e.id_ubigeo')
                ->where('e.id_empresa', 1)
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return null;
        }
    }

    public function listar_datos_empresa_x_id(int $idEmpresa)
    {
        try {
            return DB::table('empresa as e')
                ->join('ubigeo as u', 'u.id_ubigeo', '=', 'e.id_ubigeo')
                ->where('e.id_empresa', $idEmpresa)
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return null;
        }
    }

    public function sucursales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sucursal::class, 'id_empresa', 'id_empresa');
    }
}
