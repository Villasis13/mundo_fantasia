<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\Submenu;

class CxCController extends Controller
{
    private $logs;
    private $submenu;

    public function __construct()
    {
        $this->logs    = new Logs();
        $this->submenu = new Submenu();
    }

    public function cuentasCobrar()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('cuentas_cobrar');
            return view('cxc.cuentas_cobrar', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert('Error al mostrar el contenido. Redireccionando al inicio.');
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
}
