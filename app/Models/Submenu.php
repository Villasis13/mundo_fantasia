<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Submenu extends Model
{
    use HasFactory;
    protected $table      = 'submenu';
    protected $primaryKey = 'id_submenu';
    protected $fillable   = ['id_menu', 'submenu_nombre', 'submenu_funcion', 'submenu_mostrar', 'submenu_orden', 'submenu_estado'];
    private $log;

    public function __construct()
    {
        $this->log = new Logs();
    }

    public function optiones_por_vista($nombre)
    {
        try {
            $result = DB::table('submenu')
                ->join('opciones', 'submenu.id_submenu', '=', 'opciones.id_submenu')
                ->where([['opciones.opciones_estado', 1], ['opciones.opciones_mostrar', 1], ['submenu.submenu_funcion', $nombre]])
                ->orderBy('opciones_orden', 'asc')
                ->get();
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'id_menu', 'id_menu');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opciones::class, 'id_submenu', 'id_submenu');
    }
}
