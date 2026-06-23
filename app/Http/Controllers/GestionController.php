<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Familia;
use App\Models\General;
use App\Models\Logs;
use App\Models\Menu;
use App\Models\Opciones;
use App\Models\Persona;
use App\Models\Proveedores;
use App\Models\Submenu;
use App\Models\Tipo_documento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class GestionController extends Controller
{
    private $menus;
    private $submenu;
    private $logs;
    private $usuarios;
    private $tipoDocument;
    private $opciones;
    private $persona;
    private $general;
    private $proveedores;
    private $familias;
    private $categorias;
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
        $this->proveedores = new Proveedores();
        $this->familias = new Familia();
        $this->categorias = new Categoria();
//        $this->familias = new Fa
    }
    public function proveedores()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("proveedores");
            return view('gestion/proveedores', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function familias()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("familias");
            return view('gestion/familias', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function categorias(int $familia)
    {
        try {
            $opciones  = $this->submenu->optiones_por_vista("familias");
            $idFamilia = $familia;
            return view('gestion/categorias', compact('opciones', 'idFamilia'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");window.location.href='" . route('admin') . "';</script>";
        }
    }
}
