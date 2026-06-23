<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Logs;
use App\Service\FacturacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutomaticticketSending extends Command
{
    protected $signature   = 'sunat:enviar-comprobantes';
    protected $description = 'Envío automático del resumen diario (RC) a SUNAT (multiempresa)';

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
            $fecha    = date('Y-m-d');
            $empresas = Empresa::where('empresa_estado', 1)->get();

            foreach ($empresas as $empresa) {
                $resultado = $service->enviarResumenDiario($fecha, (int) $empresa->id_empresa);

                if ($resultado['code'] !== 1) {
                    Log::warning("[sunat:RC] Empresa #{$empresa->id_empresa}: {$resultado['message']}");
                }
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }
}
