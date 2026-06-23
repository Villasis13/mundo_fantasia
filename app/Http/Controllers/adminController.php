<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Caja_numero;
use App\Models\General;
use App\Models\Logs;
use App\Models\Menu;
use App\Models\PagosCuota;
use App\Models\Persona;
use App\Models\User;
use App\Models\Ventas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class adminController extends Controller
{
    private $usuario;
    private $log;
    private $general;
    private $caja;
    private $caja_numero;
    private $ventas;
    private $pagosCuota;
    public function __construct()
    {
        $this->usuario = new User();
        $this->log = new Logs();
        $this->general = new General();
        $this->caja = new Caja();
        $this->caja_numero = new Caja_numero();
        $this->ventas =  new Ventas();
        $this->pagosCuota =  new PagosCuota();
    }

    public function inicio(){
        try{
            $opciones = null;
            $datos_usuario = $this->usuario->listar_datos_usuario(Auth::id());
            return view('admin/inicio', compact('opciones','datos_usuario'));
        }catch (\Exception $e){
            $this->log->insertarLog($e);
            echo "<script>
                    alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                </script>";
            return redirect()->route('admin');

        }
    }
    public function perfil()
    {
        $opciones = [];
        return view('admin/perfil',compact('opciones'));
    }

}
