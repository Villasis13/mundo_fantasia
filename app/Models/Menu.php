<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Menu extends Model
{
    use HasFactory;
    protected $table      = 'menus';
    protected $primaryKey = 'id_menu';
    private $log;

    public function __construct()
    {
        $this->log = new Logs();
    }

    public function listar_menus_y_submenus()
    {
        try {
            $result = DB::table('menus as m')
                ->where([['m.menu_estado', 1], ['m.menu_mostrar', 1]])
                ->orderBy('menu_orden', 'asc')
                ->get();
            foreach ($result as $d) {
                $d->submenu = DB::table('submenu as s')
                    ->where([['s.id_menu', $d->id_menu], ['s.submenu_estado', 1], ['s.submenu_mostrar', 1]])
                    ->orderBy('s.submenu_orden', 'asc')
                    ->get();
            }
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function listar_submenus($id)
    {
        try {
            $result = DB::table('submenu')->where('submenu.id_menu', $id)->get();
            foreach ($result as $d) {
                $d->contar = DB::table('opciones')->where('opciones.id_submenu', $d->id_submenu)->count();
            }
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function listar_opciones($id)
    {
        try {
            $result = DB::table('opciones')->where('opciones.id_submenu', $id)->get();
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $result = [];
        }
        return $result;
    }

    public function submenus(): HasMany
    {
        return $this->hasMany(Submenu::class, 'id_menu', 'id_menu');
    }
}
