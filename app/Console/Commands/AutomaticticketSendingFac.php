<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Logs;
use App\Models\Ventas;
use App\Service\FacturacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutomaticticketSendingFac extends Command
{
    protected $signature   = 'sunatFac:enviar-comprobantes';
    protected $description = 'Envío automático de facturas/boletas pendientes a SUNAT (multiempresa)';

    private Logs $logs;

    public function __construct()
    {
        parent::__construct();
        $this->logs = new Logs();
    }

    public function handle()
    {
        try {
            $service  = new FacturacionService();
            $ventas   = new Ventas();
            $empresas = Empresa::where('empresa_estado', 1)->get();

            foreach ($empresas as $empresa) {
                $pendientes = $ventas->listar_ventas_facturas($empresa->id_empresa);

                foreach ($pendientes as $v) {
                    $resultado = $service->enviarComprobante((int) $v->id_venta);

                    if ($resultado['code'] !== 1) {
                        Log::error("[sunatFac] Empresa #{$empresa->id_empresa} - Venta #{$v->id_venta}: {$resultado['message']}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }
}
