<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\Submenu;

class CxPController extends Controller
{
    private $logs;
    private $submenu;

    public function __construct()
    {
        $this->logs    = new Logs();
        $this->submenu = new Submenu();
    }

    public function cuentasPagar()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('cuentas_pagar');
            return view('cxp.cuentas_pagar', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert('Error al mostrar el contenido. Redireccionando al inicio.');
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
}
