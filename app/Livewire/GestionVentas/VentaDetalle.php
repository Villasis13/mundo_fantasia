<?php

namespace App\Livewire\GestionVentas;

use App\Http\Controllers\GestionventasController;
use App\Mail\ComprobanteCorreo;
use App\Models\Empresa;
use App\Models\General;
use App\Models\Logs;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use App\Models\Ventas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class VentaDetalle extends Component
{
    public int $ventaId = 0;

    #[Validate('required|email', message: 'Ingresa un correo válido.')]
    public string $correoDestino = '';

    public bool $enviando = false;

    private Logs    $logs;
    private General $general;
    private Ventas  $ventas;
    private Empresa $empresaModel;

    public function boot(): void
    {
        $this->logs         = new Logs();
        $this->general      = new General();
        $this->ventas       = new Ventas();
        $this->empresaModel = new Empresa();
    }

    public function mount(int $ventaId): void
    {
        abort_if(!auth()->user()->can('detalle_venta.listar'), 403);
        $this->ventaId = $ventaId;
    }

    public function enviarComprobante(): void
    {
        $this->validate();
        $this->enviando = true;

        try {
            $venta       = $this->ventas->listar_venta_x_id($this->ventaId);
            $empresa     = DB::table('empresa')->where('id_empresa', 1)->first();
            $comprobante = app(GestionventasController::class)->imprimir_ticket_pdf_local($this->ventaId);

            $rutaXML = (!empty($venta->venta_rutaXML) && file_exists($venta->venta_rutaXML))
                ? $venta->venta_rutaXML : null;
            $rutaCDR = (!empty($venta->venta_rutaCDR) && file_exists($venta->venta_rutaCDR))
                ? $venta->venta_rutaCDR : null;

            Mail::to(strtolower($this->correoDestino))
                ->send(new ComprobanteCorreo($comprobante, $empresa->empresa_correo, $rutaXML, $rutaCDR));

            $this->correoDestino = '';
            $this->dispatch('correoEnviado', mensaje: 'Comprobante enviado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->dispatch('notificar', mensaje: 'Ocurrió un error al enviar el correo.', tipo: 'error');
        } finally {
            $this->enviando = false;
        }
    }

    public function render()
    {
        $venta        = $this->ventas->listar_venta_x_id($this->ventaId);
        $detalleVenta = $this->ventas->listar_venta_detalle_x_id_venta($this->ventaId);
        $empresa      = $this->empresaModel->listar_datos_empresa();
        $datos        = '';

        if ($venta && in_array($venta->venta_tipo, ['07', '08'])) {
            $tipoDatos  = $venta->venta_tipo === '07'
                ? Tipo_ncredito::listar_tipo_notaC_x_codigo($venta->venta_codigo_motivo_nota)
                : Tipo_ndebito::listar_tipo_notaD_x_codigo($venta->venta_codigo_motivo_nota);
            $datos      = $tipoDatos->tipo_nota_descripcion ?? '';
            $venta->des = $datos;
        }

        $cuotas = DB::table('ventas_cuotas')->where('id_venta', $this->ventaId)->get();
        $rutaQr = $this->general->generar_qr($this->ventaId);

        return view('livewire.gestion-ventas.venta-detalle', compact(
            'venta', 'detalleVenta', 'empresa', 'datos', 'cuotas', 'rutaQr'
        ));
    }
}
