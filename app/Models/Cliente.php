<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cliente extends Model
{
    use HasFactory;
    protected $table      = 'clientes';
    protected $primaryKey = 'id_clientes';
    private $log;

    public function __construct()
    {
        parent::__construct();
        $this->log = new Logs();
    }

    public function listar_clienteventa_x_id($id)
    {
        try {
            $cliente = DB::table('clientes as c')
                ->join('tipo_documento as ti', 'c.id_tipo_documento', '=', 'ti.id_tipo_documento')
                ->where('c.id_clientes', $id)
                ->first();
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $cliente = null;
        }
        return $cliente;
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(Tipo_documento::class, 'id_tipo_documento', 'id_tipo_documento');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Ventas::class, 'id_clientes', 'id_clientes');
    }
}
