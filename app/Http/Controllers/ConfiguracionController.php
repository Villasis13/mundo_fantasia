<?php

namespace App\Http\Controllers;

use App\Models\General;
use App\Models\Logs;
use App\Models\Menu;
use App\Models\Opciones;
use App\Models\Persona;
use App\Models\Submenu;
use App\Models\Tipo_documento;
use App\Models\User;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Role as ModelsRole;
use ZipStream\File;

class ConfiguracionController extends Controller
{
    private $menus;
    private $submenu;
    private $logs;
    private $usuarios;
    private $tipoDocument;
    private $opciones;
    private $persona;
    private $general;
    private $grupo;
    public function __construct()
    {
        $this->menus = new Menu();
        $this->submenu = new Submenu();
        $this->logs = new Logs();
        $this->usuarios = new User();
        $this->tipoDocument = new Tipo_documento();
        $this->opciones = new Opciones();
        $this->persona = new Persona();
        $this->general = new General();
        $this->grupo = new Grupo();
    }

    public function menus()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("menus");

            return view('configuracion/menus', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }
    public function submenus($ID)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("submenu");

            return view('configuracion/submenu', compact('ID', 'opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }
    public function opciones($ID)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("opciones");

            return view('configuracion/opciones', compact('ID', 'opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }
    public function usuarios()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("usuarios");
            return view('configuracion/usuarios', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }
    public function iconos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("iconos");

            return view('configuracion/iconos',compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }
    public function roles()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("roles");
            return view('configuracion/roles', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                    alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                    window.location.href = '" . route('admin') . "';
                </script>";
        }

    }

    public function cajasPorSucursal($ID)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("cajas");
            return view('configuracion.cajas', compact('ID', 'opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function sucursales($ID)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("sucursal");
            return view('configuracion.sucursales', compact('ID', 'opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function empresas()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("empresas");
            return view('configuracion.empresas', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function plan()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("plan");
            return view('configuracion.planes', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function grupos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("grupos");
            return view('configuracion.grupos', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function tiendas($idEmpresa)
    {
        try {
            $empresa  = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
            $opciones = collect();
            return view('configuracion.tiendas', compact('opciones', 'idEmpresa', 'empresa'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function almacenes($idTienda)
    {
        try {
            $tienda   = DB::table('tiendas')->where('id_tienda', $idTienda)->first();
            $empresa  = $tienda ? DB::table('empresa')->where('id_empresa', $tienda->id_empresa)->first() : null;
            $opciones = collect();
            return view('configuracion.almacenes', compact('opciones', 'idTienda', 'tienda', 'empresa'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function cajasPorTienda($idTienda)
    {
        try {
            $opciones = collect();
            return view('configuracion.cajas-tienda', compact('idTienda', 'opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function empresasPorGrupo($idGrupo)
    {
        try {
            $grupo    = DB::table('grupos')->where('id_grupo', $idGrupo)->first();
            $opciones = $this->submenu->optiones_por_vista("empresas");
            return view('configuracion.empresas', compact('opciones', 'idGrupo', 'grupo'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
}
