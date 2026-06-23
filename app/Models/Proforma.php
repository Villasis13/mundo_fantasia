<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Proforma extends Model
{
    use HasFactory;
    protected $table      = 'proformas';
    protected $primaryKey = 'id_profo';
    private $logs;

    public function __construct()
    {
        parent::__construct();
        $this->logs = new Logs();
    }

    public function listar_proformas_activas($desde, $hasta)
    {
        try {
            $result = DB::table('proformas as p')
                ->join('clientes as c', 'c.id_clientes', '=', 'p.id_clientes')
                ->join('users as u', 'u.id_users', '=', 'p.id_users')
                ->leftJoin('proformas_detalles as pd', 'pd.id_profo', '=', 'p.id_profo')
                ->select('p.*', 'c.*', 'u.*', DB::raw('SUM(pd.profo_deta_cantidad * pd.profo_deta_precio) as total'))
                ->where('p.profo_estado', 1)
                ->whereBetween('p.profo_fecha_emision', [$desde, $hasta])
                ->groupBy('p.id_profo')
                ->orderBy('p.id_profo', 'desc')
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function listar_proforma_x_id($id)
    {
        try {
            $result = DB::table('proformas as p')
                ->join('clientes as c', 'c.id_clientes', '=', 'p.id_clientes')
                ->join('users as u', 'u.id_users', '=', 'p.id_users')
                ->join('persona as per', 'per.id_persona', '=', 'u.id_persona')
                ->select('p.*', 'c.*', 'u.*', 'per.*')
                ->where('p.profo_estado', 1)
                ->where('p.id_profo', $id)
                ->groupBy('p.id_profo')
                ->orderBy('p.id_profo', 'desc')
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $result = null;
        }
        return $result;
    }

    public function listar_detalle_x_id($id)
    {
        try {
            $result = DB::table('proformas_detalles as pd')
                ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
                ->where('pd.profo_deta_estado', 1)
                ->where('pd.id_profo', $id)
                ->orderBy('pd.id_profo', 'desc')
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_clientes', 'id_clientes');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(Venta_detalle::class, 'id_profo', 'id_profo');
    }
}
