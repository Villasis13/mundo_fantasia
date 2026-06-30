<?php

namespace App\Http\Controllers;

use App\Mail\ComprobanteCorreo;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\General;
use App\Models\Logs;
use App\Models\Movimientos_productos;
use App\Models\PDFBufeo;
use App\Models\Productos;
use App\Models\Proforma;
use App\Models\Serie;
use App\Models\Submenu;
use App\Models\Tipo_documento;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use App\Models\Tipo_pago;
use App\Models\Ventas;
use App\Models\Ventas_detalle_pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Luecano\NumeroALetras\NumeroALetras;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GestionventasController extends Controller
{
    private $submenu;
    private $logs;
    private $general;
    private $productos;
    private $tipo_pago;
    private $empresa;
    private  $movimeinto_producto;
    private $caja;
    private $clientes;
    private $tipo_documento;
    private $serie;
    private  $venta;
    private $proforma;

    public function __construct()
    {
        $this->submenu = new Submenu();
        $this->logs = new Logs();
        $this->general = new General();
        $this->productos = new Productos();
        $this->tipo_pago = new Tipo_pago();
        $this->empresa = new Empresa();
        $this->caja = new Caja();
        $this->movimeinto_producto =  new Movimientos_productos();
        $this->clientes =  new Cliente();
        $this->tipo_documento =  new Tipo_documento();
        $this->serie =  new Serie();
        $this->venta = new Ventas();
        $this->proforma = new Proforma();
    }


    public function movimientos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("movimientos");
            return view('gestionventas/movimientos', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function proformas()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("proformas");
            return view('gestionventas/proformas', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function realizar_ventas()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("realizar_ventas");

            return view('gestionventas/realizar_venta', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function ventas_servicios()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("ventas_servicios");

            return view('gestionventas/ventas_servicios', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function guias_remision()
    {
        try {
            $opciones   = $this->submenu->optiones_por_vista('guias_remision');
            $pendientes = DB::table('guias_remision')->where('guia_estado', 1)->where('guia_estado_sunat', 0)->count();
            return view('gestion-ventas.guias_remision', compact('opciones', 'pendientes'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function generar_guia()
    {
        try {
            abort_if(!auth()->user()->can('guias_remision.listar'), 403);
            $opciones = $this->submenu->optiones_por_vista('guias_remision');
            return view('gestion-ventas.generar_guia', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function pendientes_guia()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('guias_remision');
            return view('gestion-ventas.pendientes_guia', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function imprimir_guia_pdf(\Illuminate\Http\Request $request)
    {
        try {
            $idGuia = (int) ($request->input('id_guia') ?? 0);

            $g = DB::table('guias_remision as gr')
                ->leftJoin('empresa as e', 'e.id_empresa', '=', 'gr.id_empresa')
                ->leftJoin('ubigeo as up', 'up.ubigeo_cod', '=', 'gr.guia_partida_ubigeo')
                ->leftJoin('ubigeo as ul', 'ul.ubigeo_cod', '=', 'gr.guia_llegada_ubigeo')
                ->where('gr.id_guia', $idGuia)
                ->select('gr.*',
                    'e.empresa_razon_social', 'e.empresa_ruc', 'e.empresa_domiciliofiscal', 'e.empresa_foto',
                    DB::raw("CONCAT_WS(' / ', up.ubigeo_departamento, up.ubigeo_provincia, up.ubigeo_distrito) as ubigeo_part_txt"),
                    DB::raw("CONCAT_WS(' / ', ul.ubigeo_departamento, ul.ubigeo_provincia, ul.ubigeo_distrito) as ubigeo_llega_txt"))
                ->first();

            if (!$g) abort(404, 'Guía no encontrada.');

            $detalle = DB::table('guias_remision_detalle')->where('id_guia', $idGuia)->get();

            $motivos = ['01'=>'01 - Venta','02'=>'02 - Compra','03'=>'03 - Venta con entrega a terceros',
                '04'=>'04 - Traslado entre establecimientos','05'=>'05 - Consignación','06'=>'06 - Devolución','13'=>'13 - Otros'];
            $motivo = $motivos[$g->guia_motivo_traslado] ?? $g->guia_motivo_traslado;
            $modalidad = $g->guia_modalidad_traslado === '01' ? 'Público' : 'Privado';
            $tipoGuia  = $g->guia_tipo === '31' ? 'TRANSPORTISTA' : 'REMITENTE';
            $serieCompleta = $g->guia_numero ?: ($g->guia_serie . '-' . str_pad((string)$g->guia_correlativo, 8, '0', STR_PAD_LEFT));

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();
            $xL = 10; $W = 190;

            // ── Encabezado ──
            $ys = 10;
            if ($g->empresa_foto && file_exists(public_path($g->empresa_foto))) {
                $pdf->Image(public_path($g->empresa_foto), $xL, $ys, 45, 0);
            }
            $pdf->SetXY(58, $ys + 2);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->MultiCell(82, 5, utf8_decode($g->empresa_razon_social ?? ''), 0, 'C');
            $pdf->SetX(58);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->MultiCell(82, 4, utf8_decode($g->empresa_domiciliofiscal ?? ''), 0, 'C');

            $rx = 145; $rw = 55;
            $pdf->SetDrawColor(80, 80, 80); $pdf->SetLineWidth(0.4);
            $pdf->SetXY($rx, $ys);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell($rw, 9, 'RUC ' . ($g->empresa_ruc ?? ''), 1, 1, 'C');
            $pdf->SetX($rx);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell($rw, 5, utf8_decode('GUÍA DE REMISIÓN ELECTRÓNICA'), 1, 1, 'C', true);
            $pdf->SetX($rx);
            $pdf->Cell($rw, 5, $tipoGuia, 1, 1, 'C', true);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetX($rx);
            $pdf->SetFont('Helvetica', 'B', 13);
            $pdf->Cell($rw, 11, $serieCompleta, 1, 1, 'C');

            $pdf->SetY(max(38, $pdf->GetY()) + 3);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($xL, $pdf->GetY(), $xL + $W, $pdf->GetY());
            $pdf->Ln(3);

            $bar = function ($label) use ($pdf, $xL) {
                $pdf->SetFillColor(80, 80, 80); $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Helvetica', 'B', 8); $pdf->SetX($xL);
                $pdf->Cell(190, 6, utf8_decode(' ' . $label), 0, 1, 'L', true);
                $pdf->SetFillColor(255, 255, 255); $pdf->SetTextColor(0, 0, 0); $pdf->Ln(1);
            };
            $linea = function ($et, $val, $w1 = 38, $w2 = 152, $ln = 1) use ($pdf, $xL) {
                $pdf->SetX($xL);
                $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell($w1, 5, utf8_decode($et), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($w2, 5, utf8_decode($val), 0, $ln, 'L');
            };

            // ── Datos generales ──
            $bar('DATOS DE LA GUÍA');
            $linea('Fecha de emisión:', $g->guia_fecha_emision ? date('d/m/Y', strtotime($g->guia_fecha_emision)) : '-');
            $linea('Fecha de traslado:', $g->guia_fecha_traslado ? date('d/m/Y', strtotime($g->guia_fecha_traslado)) : '-');
            $linea('Motivo de traslado:', $motivo);
            $linea('Modalidad de transporte:', $modalidad);
            if ($g->guia_observaciones) $linea('Observación:', $g->guia_observaciones);
            $pdf->Ln(2);

            // ── Destinatario ──
            $bar('DATOS DEL DESTINATARIO');
            $linea('Destinatario:', $g->guia_dest_nombre ?? '-');
            $linea('N° Documento:', $g->guia_dest_numero_doc ?? '-');
            if ($g->guia_dest_direccion) $linea('Dirección:', $g->guia_dest_direccion);
            $pdf->Ln(2);

            // ── Traslado ──
            $bar('PUNTO DE PARTIDA Y LLEGADA');
            $linea('Partida:', trim(($g->guia_partida_direccion ?? '') . '  —  ' . ($g->ubigeo_part_txt ?? '')));
            $linea('Llegada:', trim(($g->guia_llegada_direccion ?? '') . '  —  ' . ($g->ubigeo_llega_txt ?? '')));
            $linea('Peso bruto:', number_format((float)$g->guia_peso_bruto, 3) . ' ' . ($g->guia_unidad_medida ?? 'KGM') . '   |   Bultos: ' . ($g->guia_nro_bultos ?? '-'));
            $pdf->Ln(2);

            // ── Transporte ──
            $bar('TRANSPORTE / CONDUCTOR / VEHÍCULO');
            $linea('Transportista:', trim(($g->guia_transportista_ruc ?? '') . '  ' . ($g->guia_transportista_nombre ?? '')) ?: '-');
            $linea('Vehículo (placa):', trim(($g->guia_vehiculo_placa ?? '') . '  ' . ($g->guia_vehiculo_marca ?? '')));
            $linea('Conductor:', trim(($g->guia_conductor_nombre ?? '') . '  Lic: ' . ($g->guia_conductor_licencia ?? '') . '  Doc: ' . ($g->guia_conductor_numero_doc ?? '')));
            $pdf->Ln(2);

            // ── Bienes ──
            $bar('BIENES A TRASLADAR');
            $pdf->SetFont('Helvetica', 'B', 7.5);
            $pdf->SetFillColor(220, 220, 220); $pdf->SetX($xL);
            $cols = ['Código'=>24, 'Descripción'=>96, 'U.M.'=>16, 'Cant.'=>18, 'P.Unit'=>18, 'P.Total'=>18];
            foreach ($cols as $h=>$w) $pdf->Cell($w, 6, utf8_decode($h), 1, 0, 'C', true);
            $pdf->Ln();
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pesoTotal = 0;
            foreach ($detalle as $d) {
                $pt = (float)$d->detalle_cantidad * (float)$d->detalle_peso_unitario;
                $pesoTotal += $pt;
                $pdf->SetX($xL);
                $pdf->Cell(24, 5, utf8_decode(mb_strimwidth($d->detalle_codigo ?? '-', 0, 14, '..')), 1, 0, 'C');
                $pdf->Cell(96, 5, utf8_decode(mb_strimwidth($d->detalle_descripcion ?? '', 0, 62, '..')), 1, 0, 'L');
                $pdf->Cell(16, 5, $d->detalle_unidad_medida ?? '', 1, 0, 'C');
                $pdf->Cell(18, 5, number_format((float)$d->detalle_cantidad, 2), 1, 0, 'R');
                $pdf->Cell(18, 5, number_format((float)$d->detalle_peso_unitario, 3), 1, 0, 'R');
                $pdf->Cell(18, 5, number_format($pt, 3), 1, 1, 'R');
            }
            $pdf->SetFont('Helvetica', 'B', 7.5);
            $pdf->SetX($xL);
            $pdf->Cell(172, 6, 'PESO TOTAL', 1, 0, 'R');
            $pdf->Cell(18, 6, number_format($pesoTotal, 3), 1, 1, 'R');

            $pdf->Output('I', 'guia-' . $serieCompleta . '.pdf');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el PDF de la guía.');window.close();</script>";
        }
    }

    public function registro_pagos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("registro_pagos");
            return view('gestionventas.registro_pagos', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function notas_de_venta()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("notas_de_venta");
            return view('gestionventas.notas_de_venta', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function venta_detalle()
    {
        try {
            $ventaId = (int)request()->get('venta_id');
            $opciones = $this->submenu->optiones_por_vista("venta_detalle");
            return view('gestionventas/venta_detalle', compact('opciones', 'ventaId'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }


    public function imprimir_proforma(){
        $id = $_GET['data'];
        $id_proforma = (int)$id;
        if($id_proforma){
            $guardar_localmente = true;
            $ruta_guardado = "";

            $proforma =  $this->proforma->listar_proforma_x_id($id_proforma);
            $detalle =  $this->proforma->listar_detalle_x_id($id_proforma);
            $empresa =  DB::table('empresa')
                ->where('id_empresa','=',1)->first();


            $pdf = new PDFBufeo('P');
            $pdf->AddPage();
            //CABECERA DEL ARCHIVO
            if (file_exists($empresa->empresa_foto_ticket)) {
                $pdf->Image("$empresa->empresa_foto_ticket", 10, 10, 30,30);
            }
            $pdf->Ln(5);
            $pdf->SetFillColor(220,220,220);
            $pdf->SetFont('Arial','B',14);
            // FILA 1
            $pdf->Cell(35,6,'',0,0,'');
            $pdf->Cell(95,6,$empresa->empresa_razon_social,0,0,1);
            $pdf->Cell(50,0,'','T',1,'R');
            // FILA 2
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(130,6,'',0,0,1);
            $pdf->Cell(50,8, "RUC: $empresa->empresa_ruc",0,1,'C',0);
            // FILA 3
//            $pdf->SetFillColor(231,193,201);
            $pdf->SetFillColor(192,54,97);
            if (file_exists('home.png')) {
                $pdf->Image("home.png", 45, 24, 4,4);
            }
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(35,6,'',0,0,'');
            $pdf->Cell(95,6,utf8_decode("$empresa->empresa_domiciliofiscal"),0,0,'L',0);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(50,8,utf8_decode('COTIZACIÓN'),0,1,'C',1);
            $pdf->SetFillColor(220,220,220);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','',10);
            // FILA 4
            $pdf->Cell(35,4,'',0,0,'');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(95,0.5,utf8_decode("$empresa->empresa_telefono1  /  $empresa->empresa_telefono2  "),0,0,'L',0);
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(50,8,"$proforma->profo_serie - 0$proforma->profo_correlativo",0,1,'C',0);
            // FILA 5
            $pdf->Cell(35,4,'',0,0);
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(95,-6,utf8_decode("$empresa->empresa_correo"),0,0,'L',0);
            $pdf->Cell(50,0,'','T',1,'R');

            $pdf->Ln(5);
            $pdf->SetFillColor(192,54,97);

            $pdf->Cell(180,0,'','T',1,'R');
            $pdf->Ln(3);
            //
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(18,5,utf8_decode("SEÑORES :"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(97,5,utf8_decode("$proforma->cliente_razonsocial"),0,0,'L');
            $fecha = date('d/m/Y',strtotime($proforma->profo_fecha_emision));
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("FECHA :"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("$fecha"),0,1,'L');
            //
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(18,5,utf8_decode("DIRECCIÓN :"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(97,5,utf8_decode("$proforma->cliente_direccion"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("MONEDA :"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("SOLES"),0,1,'L');
            //
            if ($proforma->id_tipo_documento == 2){
                $documento = 'DNI:';
            }else{
                $documento = 'RUC:';
            }
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(18,5,utf8_decode("$documento"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(32,5,utf8_decode("$proforma->cliente_numero"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(20,5,utf8_decode("TELÉFONO:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(45,5,utf8_decode("$proforma->cliente_telefono"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("REFERENCIA:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode(""),0,1,'L');
            //
            if ($proforma->profo_forma_pago == 1){
                $forma_pago = "CONTADO";
            }else{
                $forma_pago = 'CREDITO';
            }
            $pdf->SetFont('Arial','B',7);
//            $pdf->Cell(20,5,utf8_decode("ATENCIÓN:"),0,0,'L');
            $pdf->Cell(20,5,utf8_decode(""),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(30,5,utf8_decode(""),0,0,'L');
//            $pdf->Cell(30,5,utf8_decode("$proforma->nombre_users"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
//            $pdf->Cell(20,5,utf8_decode("CORREO:"),0,0,'L');
            $pdf->Cell(20,5,utf8_decode(""),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(45,5,utf8_decode(""),0,0,'L');
//            $pdf->Cell(45,5,utf8_decode("karenpamela@emyspets.com"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("FORMA DE PAGO:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("$forma_pago"),0,1,'L');
            $pdf->Ln(3);
            $pdf->Cell(180,0,'','T',1,'R');
            $pdf->Ln(3);
            //
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(30,5,utf8_decode("LUGAR DE ENTREGA:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(85,5,utf8_decode("$proforma->profo_lugar_entrega"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("VENDEDOR:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("$proforma->nombre_users"),0,1,'L');
            //
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(30,5,utf8_decode("OBSERVACIONES:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(85,5,utf8_decode("$proforma->profo_observacion"),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("CARGO:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("Dep de Ventas"),0,1,'L');
            //
            $pdf->Cell(115,5,utf8_decode(""),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("CORREO:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("$proforma->email"),0,1,'L');
            //
            $pdf->Cell(115,5,utf8_decode(""),0,0,'L');
            $pdf->SetFont('Arial','B',7);
            $pdf->Cell(25,5,utf8_decode("TELÉFONO:"),0,0,'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell(25,5,utf8_decode("$proforma->persona_telefono"),0,1,'L');
            $pdf->Ln(3);
            $pdf->Cell(180,0,'','T',1,'R');
            $pdf->Ln(3);
            //COLUMNAS
            $pdf->SetFont('Helvetica','B',7);
            $pdf->SetTextColor(255,255,255);
            $pdf->Cell(10, 6, 'ITEM', 1,'','C',1);
            $pdf->Cell(25, 6, utf8_decode("CÓDIGO"),1,0,'C',1);
            $pdf->Cell(90, 6, utf8_decode('DESCRIPCIÓN'), 1,'','C',1);
            $pdf->Cell(15, 6, 'CANT',1,0,'C',1);
            $pdf->Cell(20, 6, 'PRECIO',1,0,'C',1);
            $pdf->Cell(20, 6, 'TOTAL',1,1,'C',1);
            $pdf->Ln(2);
            $pdf->SetTextColor(0,0,0);
            //PRODUCTOS
            $pdf->SetWidths(array(10,25,90,15,20,20));
            $aa=1;
            $filas_tot = 0;
            $total_proforma = 0;
            foreach ($detalle as $f){
                $pdf->SetFont('Helvetica', '', 7);
                $desc =  $f->profo_deta_observacion ? utf8_decode(": $f->profo_deta_observacion") : '';
                $nombre = utf8_decode("$f->pro_nombre $desc");
                $pdf->Row(array($aa,$f->pro_codigo, $nombre,  number_format(round("$f->profo_deta_cantidad",2), 2, '.', ' '),  number_format(round("$f->profo_deta_precio",2), 2, '.', ' '),number_format(round($f->profo_deta_precio * $f->profo_deta_cantidad,2), 2, '.', ' ')));
                $cant = strlen($nombre);
                $filas = ceil($cant / 65);
                if($filas==0){$filas=1;}
                $filas_tot+=$filas;
                $he = 4 * $filas;
                $aa++;
                $total_proforma += (float)$f->profo_deta_precio * (float)$f->profo_deta_cantidad;

            }
            $pdf->Ln(5);
            $da = new NumeroALetras();
            $importe_letra = $da->toInvoice($total_proforma,'2','soles');
//            $pdf->Cell(70, 3, "$importe_letra", 0,0,'L');
            $pdf->Ln(5);
            $pdf->Cell(105,20,utf8_decode("$importe_letra"),1,0,'C');
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFont('Arial','B',8);
            $pdf->Cell(45,20,utf8_decode("TOTAL GENERAL"),1,0,'C',1);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','',8);
            $pdf->Cell(30,20,"S/ ".number_format(round($total_proforma,2), 2, '.', ' '),1,1,'C');
            $pdf->SetFont('Arial','',6);
            $pdf->Ln(2);
            $pdf->Cell(180,4,utf8_decode("Sistema de Géstion Administrativa, Desarrollado por Bufeo Innovacion Tecnologica S.a.c. | Whatsapp 925812998 | E-mail: bufeotec@gmail.com"),0,0,'C');

            if(isset($guardar_localmente) && isset($ruta_guardado)){

//                $ruta_guardado = 'comprobantes/'.date('Y-m-d').'.pdf';
                $ruta_guardado = "Proforma-$proforma->profo_serie-0$proforma->profo_correlativo".'.pdf';
                $pdf->Output("I",$ruta_guardado);
            } else {
                $pdf->Output('',"proformas-" .date('Y-m-d'));
            }
            exit;

        }
    }
    public function imprimir_ticket_pdf(){
        $id = $_GET['venta_id'];
        $id_venta = (int)$id;
        if($id_venta){
            $guardar_localmente = true;
            $ruta_guardado = "";
            $dato_venta = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);
            $formas_de_pago_mensaje = collect($formas_de_pago)->pluck('tipo_pago_nombre')->filter()->implode(' - ');

            $empresa = $this->empresa->listar_datos_empresa();
            $ruta_qr = $this->general->generar_qr($dato_venta->id_venta);
            $dnni = "";
            if ($dato_venta->venta_tipo == "03") {
                $tipo_comprobante = "BOLETA DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DNI';
                $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "01") {
                $tipo_comprobante = "FACTURA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'RUC';
                $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "07") {
                $tipo_comprobante = "NOTA DE CRÉDITO DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            } else if($dato_venta->venta_tipo == "08") {
                $tipo_comprobante = "NOTA DE DÉBITO DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            }else if($dato_venta->venta_tipo == "20") {
                $tipo_comprobante = "NOTA DE VENTA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            }
            $da = new NumeroALetras();
            $importe_letra = $da->toInvoice($dato_venta->venta_total,'2','soles');


            $pdf = new PDFBufeo('P');
            $pdf->AddPage();

            // ── CABECERA ─────────────────────────────────────────────────────
            if (file_exists($dato_venta->empresa_foto_ticket)) {
                $pdf->Image("$dato_venta->empresa_foto_ticket", 10, 10, 30, 0);
            }
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetXY(45, 10);
            $pdf->Cell(80, 6, utf8_decode($dato_venta->empresa_razon_social), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetXY(45, 17);
            $pdf->MultiCell(80, 5, utf8_decode($dato_venta->empresa_domiciliofiscal), 0, 'C');
            $posY = $pdf->GetY();
            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');
            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();
            foreach ($sedes as $sede) {
                $sedeTexto = $sede->tienda_nombre;
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $sede->tienda_direccion;
                }
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetXY(45, $posY);
                $pdf->Cell(80, 5, utf8_decode($sedeTexto), 0, 1, 'C');
                $posY = $pdf->GetY();
            }
            // Recuadro comprobante (derecha)
            $pdf->SetY(10); $pdf->SetX(130);
            $pdf->Cell(60, 28, '', 1, 1);
            $pdf->SetY(10); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell(60, 5, utf8_decode('Emisor Electrónico Obligado'), 0, 1, 'C');
            $pdf->SetY(15); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(16); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(60, 5, 'RUC: ' . $dato_venta->empresa_ruc, 0, 1, 'C');
            $pdf->SetY(21); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(22); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(60, 5, utf8_decode($tipo_comprobante), 0, 1, 'C');
            $pdf->SetY(27); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(28); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(60, 5, $serie_correlativo, 0, 1, 'C');

            // ── DATOS GENERALES ───────────────────────────────────────────────
            $dataY = max(45, $posY + 3);
            $pdf->SetY($dataY); $pdf->SetX(10);
            $pdf->Cell(180, 32, '', 1, 1);
            $pdf->SetY($dataY + 2); $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(26, 4, utf8_decode('FECHA EMISIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(24, 4, date('d/m/Y', strtotime($dato_venta->venta_fecha)), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(10, 4, 'HORA:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(24, 4, date('H:i:s', strtotime($dato_venta->venta_fecha)), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(17, 4, 'MONEDA:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(55, 4, utf8_decode($dato_venta->moneda), 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, 'CLIENTE:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $nombre_cliente = ($dato_venta->id_tipo_documento != 4)
                ? utf8_decode($dato_venta->cliente_nombre)
                : utf8_decode($dato_venta->cliente_razonsocial);
            $pdf->Cell(140, 4, $nombre_cliente, 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, utf8_decode('CONDICIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(40, 4, utf8_decode($dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, "$dnni:", 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(74, 4, "$documento", 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, utf8_decode('DIRECCIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $direccionCliente = isset($dato_venta->cliente_direccion) ? $dato_venta->cliente_direccion : '-';
            $pdf->Cell(140, 4, utf8_decode($direccionCliente), 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, 'VENDEDOR:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(140, 4, utf8_decode($dato_venta->nombre_users), 0, 1, 'L');
            if ($dato_venta->tipo_documento_modificar) {
                if ($dato_venta->venta_tipo == "07") {
                    $datos = Tipo_ncredito::listar_tipo_notaC_x_codigo($dato_venta->venta_codigo_motivo_nota);
                } else {
                    $datos = Tipo_ndebito::listar_tipo_notaD_x_codigo($dato_venta->venta_codigo_motivo_nota);
                }
                $motivo              = $datos->tipo_nota_descripcion ?? '-';
                $comprobanteAfectado = $dato_venta->tipo_documento_modificar == '03' ? 'BOLETA' : 'FACTURA';
                $pdf->SetX(12);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(38, 4, utf8_decode('COMPROBANTE AFECTADO:'), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell(124, 4, utf8_decode($comprobanteAfectado . ' ' . ($dato_venta->serie_modificar ?? '-') . '-' . ($dato_venta->correlativo_modificar ?? '-') . ' / MOTIVO: ' . $motivo), 0, 1, 'L');
            }
            $pdf->SetY(max($pdf->GetY() + 2, $dataY + 34));

            // ── TABLA DE PRODUCTOS ─────────────────────────────────────────────
            $pdf->SetFillColor(180, 180, 180);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell(10,  10, 'CANT',                      1, 0, 'C', 1);
            $pdf->Cell(18,  10, 'UNID.MEDIDA',               1, 0, 'C', 1);
            $pdf->Cell(107, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C', 1);
            $pdf->Cell(25,  10, 'P.U.',                      1, 0, 'C', 1);
            $pdf->Cell(20,  10, 'VALOR VENTA',               1, 1, 'C', 1);
            $pdf->SetWidths([10, 18, 107, 25, 20]);
            foreach ($detalle_venta as $f) {
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Row([
                    number_format((float)$f->venta_detalle_cantidad, 0, '.', ','),
                    'UNIDAD',
                    utf8_decode($f->venta_detalle_nombre_producto),
                    number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', ','),
                    number_format(round((float)$f->venta_detalle_importe_total,  2), 2, '.', ','),
                ]);
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(1,   6, '', 'LB', 0, 'L');
            $pdf->Cell(134, 6, utf8_decode('SON: ' . $importe_letra), 'B', 0, 'L');
            $pdf->Cell(45,  6, '', 'RB', 1, 'L');

            // ── PIE DEL PDF ────────────────────────────────────────────────────
            if ($dato_venta->venta_tipo != "20") {
                $qr_y_pos = $pdf->GetY() + 4;
                $pdf->Image("$ruta_qr", 10, $qr_y_pos, 40, 40, '', '');
                $pdf->SetY($qr_y_pos); $pdf->SetX(54);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(55, 5, utf8_decode('FORMA DE PAGO: ' . ($dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO')), 0, 1, 'L');
                $pdf->SetX(54);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Cell(55, 5, utf8_decode('PAGO CON: ' . $dato_venta->simbolo . ' ' . number_format((float)$dato_venta->venta_pago_cliente, 2, '.', ',')), 0, 1, 'L');
                $pdf->SetX(54);
                $pdf->Cell(55, 5, utf8_decode('VUELTO: ' . $dato_venta->simbolo . ' ' . number_format((float)$dato_venta->venta_vuelto, 2, '.', ',')), 0, 1, 'L');
                $col_desc = 50; $col_sep = 10; $col_val = 20;
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetY($qr_y_pos); $pdf->SetX(110);
                foreach ([
                    [utf8_decode('Op. Gravada'),   number_format((float)$dato_venta->venta_totalgravada,   2, '.', ',')],
                    [utf8_decode('Op. Inafecta'),  number_format((float)$dato_venta->venta_totalinafecta,  2, '.', ',')],
                    [utf8_decode('Op. Exonerada'), number_format((float)$dato_venta->venta_totalexonerada, 2, '.', ',')],
                    [utf8_decode('Op. Gratuita'),  number_format((float)$dato_venta->venta_totalgratuita,  2, '.', ',')],
                    ['IGV',                         number_format((float)$dato_venta->venta_totaligv,       2, '.', ',')],
                ] as $item) {
                    $pdf->SetX(110);
                    $pdf->Cell($col_desc, 5, $item[0],             'LTR', 0, 'L');
                    $pdf->Cell($col_sep,  5, $dato_venta->simbolo, 'TR',  0, 'C');
                    $pdf->Cell($col_val,  5, $item[1],             'LTR', 1, 'R');
                }
                $pdf->SetX(110);
                $pdf->Cell($col_desc + $col_sep + $col_val, 0.5, '', 'T', 1);
                $pdf->SetX(110);
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->Cell($col_desc, 5, 'IMPORTE TOTAL',          'LBR', 0, 'L');
                $pdf->Cell($col_sep,  5, $dato_venta->simbolo,      'BR',  0, 'C');
                $pdf->Cell($col_val,  5, number_format((float)$dato_venta->venta_total, 2, '.', ','), 'LBR', 1, 'R');
                $pdf->SetY($qr_y_pos + 42); $pdf->SetX(10);
                $pdf->SetFont('Helvetica', '', 8);
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $pdf->MultiCell(0, 5, utf8_decode('CODIGO HASH: ' . $dato_venta->venta_codigo_hash), 0, 'L');
                }
                $pdf->SetX(10);
                $pdf->Cell(0, 5, utf8_decode('LA FACTURA ELECTRONICA PUEDE SER CONSULTADA EN:'), 0, 1, 'L');
                $pdf->SetX(10);
                $pdf->Cell(0, 5, 'WWW.BUFEO.COM/CPE', 0, 1, 'L');
                $pdf->Ln(3);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(0, 5, utf8_decode('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA'), 0, 1, 'C');
                $pdf->Ln(2);
                $pdf->SetFont('Helvetica', 'I', 7);
                $pdf->Cell(0, 5, utf8_decode('Representación impresa de comprobante de pago electrónico'), 0, 1, 'C');
            } else {
                $pdf->Ln(5);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Cell(180, 5, 'ESTE NO ES UN COMPROBANTE VALIDO PARA SUNAT', 0, 1, 'C');
                $pdf->Cell(180, 5, utf8_decode('SI REQUIERE UNA BOLETA O FACTURA, SOLICÍTALO'), 0, 1, 'C');
            }

            if(isset($guardar_localmente) && isset($ruta_guardado)){
                $ruta_guardado = 'comprobantes/'."$serie_correlativo-" .date('Y-m-d').'.pdf';
                $pdf->Output("I",$ruta_guardado);
            } else {
                $pdf->Output('',"$serie_correlativo-" .date('Y-m-d'));
            }
            exit;

        }
    }
    public function imprimir_ticket_pdf_local($id_ve){
        $id = $id_ve;
        $id_venta = (int)$id;
        if($id_venta){

            $dato_venta = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);
            $formas_de_pago_mensaje = collect($formas_de_pago)->pluck('tipo_pago_nombre')->filter()->implode(' - ');

            $empresa = $this->empresa->listar_datos_empresa();
            $ruta_qr = $this->general->generar_qr($dato_venta->id_venta);

            $empresa = $this->empresa->listar_datos_empresa();
            $ruta_qr = $this->general->generar_qr($dato_venta->id_venta);
            $dnni = "";
            if ($dato_venta->venta_tipo == "03") {
                $tipo_comprobante = "BOLETA DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DNI';
                $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "01") {
                $tipo_comprobante = "FACTURA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'RUC';
                $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "07") {
                $tipo_comprobante = "NOTA DE CRÉDITO DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            } else if($dato_venta->venta_tipo == "08") {
                $tipo_comprobante = "NOTA DE DÉBITO DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            }else if($dato_venta->venta_tipo == "20") {
                $tipo_comprobante = "NOTA DE VENTA";
                $serie_correlativo = $dato_venta->venta_serie."-".$dato_venta->venta_correlativo;
                $dnni = 'DOCUMENTO';
                $documento = "$dato_venta->cliente_numero";
            }
            $da = new NumeroALetras();
            $importe_letra = $da->toInvoice($dato_venta->venta_total,'2','soles');

            $pdf = new PDFBufeo('P');
            $pdf->AddPage();

            // ── CABECERA ─────────────────────────────────────────────────────
            if (file_exists($dato_venta->empresa_foto_ticket)) {
                $pdf->Image("$dato_venta->empresa_foto_ticket", 10, 10, 30, 0);
            }
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetXY(45, 10);
            $pdf->Cell(80, 6, utf8_decode($dato_venta->empresa_razon_social), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetXY(45, 17);
            $pdf->MultiCell(80, 5, utf8_decode($dato_venta->empresa_domiciliofiscal), 0, 'C');
            $posY = $pdf->GetY();
            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');
            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();
            foreach ($sedes as $sede) {
                $sedeTexto = $sede->tienda_nombre;
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $sede->tienda_direccion;
                }
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetXY(45, $posY);
                $pdf->Cell(80, 5, utf8_decode($sedeTexto), 0, 1, 'C');
                $posY = $pdf->GetY();
            }
            // Recuadro comprobante (derecha)
            $pdf->SetY(10); $pdf->SetX(130);
            $pdf->Cell(60, 28, '', 1, 1);
            $pdf->SetY(10); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell(60, 5, utf8_decode('Emisor Electrónico Obligado'), 0, 1, 'C');
            $pdf->SetY(15); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(16); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(60, 5, 'RUC: ' . $dato_venta->empresa_ruc, 0, 1, 'C');
            $pdf->SetY(21); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(22); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(60, 5, utf8_decode($tipo_comprobante), 0, 1, 'C');
            $pdf->SetY(27); $pdf->SetX(130);
            $pdf->Cell(60, 0, '', 'T', 1);
            $pdf->SetY(28); $pdf->SetX(130);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(60, 5, $serie_correlativo, 0, 1, 'C');

            // ── DATOS GENERALES ───────────────────────────────────────────────
            $dataY = max(45, $posY + 3);
            $pdf->SetY($dataY); $pdf->SetX(10);
            $pdf->Cell(180, 32, '', 1, 1);
            $pdf->SetY($dataY + 2); $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(26, 4, utf8_decode('FECHA EMISIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(24, 4, date('d/m/Y', strtotime($dato_venta->venta_fecha)), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(10, 4, 'HORA:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(24, 4, date('H:i:s', strtotime($dato_venta->venta_fecha)), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(17, 4, 'MONEDA:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(55, 4, utf8_decode($dato_venta->moneda), 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, 'CLIENTE:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $nombre_cliente = ($dato_venta->id_tipo_documento != 4)
                ? utf8_decode($dato_venta->cliente_nombre)
                : utf8_decode($dato_venta->cliente_razonsocial);
            $pdf->Cell(140, 4, $nombre_cliente, 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, utf8_decode('CONDICIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(40, 4, utf8_decode($dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, "$dnni:", 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(74, 4, "$documento", 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, utf8_decode('DIRECCIÓN:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $direccionCliente = isset($dato_venta->cliente_direccion) ? $dato_venta->cliente_direccion : '-';
            $pdf->Cell(140, 4, utf8_decode($direccionCliente), 0, 1, 'L');
            $pdf->SetX(12);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, 'VENDEDOR:', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(140, 4, utf8_decode($dato_venta->nombre_users), 0, 1, 'L');
            if ($dato_venta->tipo_documento_modificar) {
                if ($dato_venta->venta_tipo == "07") {
                    $datos = Tipo_ncredito::listar_tipo_notaC_x_codigo($dato_venta->venta_codigo_motivo_nota);
                } else {
                    $datos = Tipo_ndebito::listar_tipo_notaD_x_codigo($dato_venta->venta_codigo_motivo_nota);
                }
                $motivo              = $datos->tipo_nota_descripcion ?? '-';
                $comprobanteAfectado = $dato_venta->tipo_documento_modificar == '03' ? 'BOLETA' : 'FACTURA';
                $pdf->SetX(12);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(38, 4, utf8_decode('COMPROBANTE AFECTADO:'), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell(124, 4, utf8_decode($comprobanteAfectado . ' ' . ($dato_venta->serie_modificar ?? '-') . '-' . ($dato_venta->correlativo_modificar ?? '-') . ' / MOTIVO: ' . $motivo), 0, 1, 'L');
            }
            $pdf->SetY(max($pdf->GetY() + 2, $dataY + 34));

            // ── TABLA DE PRODUCTOS ─────────────────────────────────────────────
            $pdf->SetFillColor(180, 180, 180);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell(10,  10, 'CANT',                      1, 0, 'C', 1);
            $pdf->Cell(18,  10, 'UNID.MEDIDA',               1, 0, 'C', 1);
            $pdf->Cell(107, 10, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C', 1);
            $pdf->Cell(25,  10, 'P.U.',                      1, 0, 'C', 1);
            $pdf->Cell(20,  10, 'VALOR VENTA',               1, 1, 'C', 1);
            $pdf->SetWidths([10, 18, 107, 25, 20]);
            foreach ($detalle_venta as $f) {
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Row([
                    number_format((float)$f->venta_detalle_cantidad, 0, '.', ','),
                    'UNIDAD',
                    utf8_decode($f->venta_detalle_nombre_producto),
                    number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', ','),
                    number_format(round((float)$f->venta_detalle_importe_total,  2), 2, '.', ','),
                ]);
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(1,   6, '', 'LB', 0, 'L');
            $pdf->Cell(134, 6, utf8_decode('SON: ' . $importe_letra), 'B', 0, 'L');
            $pdf->Cell(45,  6, '', 'RB', 1, 'L');

            // ── PIE DEL PDF ────────────────────────────────────────────────────
            if ($dato_venta->venta_tipo != "20") {
                $qr_y_pos = $pdf->GetY() + 4;
                $pdf->Image("$ruta_qr", 10, $qr_y_pos, 40, 40, '', '');
                $pdf->SetY($qr_y_pos); $pdf->SetX(54);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(55, 5, utf8_decode('FORMA DE PAGO: ' . ($dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO')), 0, 1, 'L');
                $pdf->SetX(54);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Cell(55, 5, utf8_decode('PAGO CON: ' . $dato_venta->simbolo . ' ' . number_format((float)$dato_venta->venta_pago_cliente, 2, '.', ',')), 0, 1, 'L');
                $pdf->SetX(54);
                $pdf->Cell(55, 5, utf8_decode('VUELTO: ' . $dato_venta->simbolo . ' ' . number_format((float)$dato_venta->venta_vuelto, 2, '.', ',')), 0, 1, 'L');
                $col_desc = 50; $col_sep = 10; $col_val = 20;
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetY($qr_y_pos); $pdf->SetX(110);
                foreach ([
                    [utf8_decode('Op. Gravada'),   number_format((float)$dato_venta->venta_totalgravada,   2, '.', ',')],
                    [utf8_decode('Op. Inafecta'),  number_format((float)$dato_venta->venta_totalinafecta,  2, '.', ',')],
                    [utf8_decode('Op. Exonerada'), number_format((float)$dato_venta->venta_totalexonerada, 2, '.', ',')],
                    [utf8_decode('Op. Gratuita'),  number_format((float)$dato_venta->venta_totalgratuita,  2, '.', ',')],
                    ['IGV',                         number_format((float)$dato_venta->venta_totaligv,       2, '.', ',')],
                ] as $item) {
                    $pdf->SetX(110);
                    $pdf->Cell($col_desc, 5, $item[0],             'LTR', 0, 'L');
                    $pdf->Cell($col_sep,  5, $dato_venta->simbolo, 'TR',  0, 'C');
                    $pdf->Cell($col_val,  5, $item[1],             'LTR', 1, 'R');
                }
                $pdf->SetX(110);
                $pdf->Cell($col_desc + $col_sep + $col_val, 0.5, '', 'T', 1);
                $pdf->SetX(110);
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->Cell($col_desc, 5, 'IMPORTE TOTAL',          'LBR', 0, 'L');
                $pdf->Cell($col_sep,  5, $dato_venta->simbolo,      'BR',  0, 'C');
                $pdf->Cell($col_val,  5, number_format((float)$dato_venta->venta_total, 2, '.', ','), 'LBR', 1, 'R');
                $pdf->SetY($qr_y_pos + 42); $pdf->SetX(10);
                $pdf->SetFont('Helvetica', '', 8);
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $pdf->MultiCell(0, 5, utf8_decode('CODIGO HASH: ' . $dato_venta->venta_codigo_hash), 0, 'L');
                }
                $pdf->SetX(10);
                $pdf->Cell(0, 5, utf8_decode('LA FACTURA ELECTRONICA PUEDE SER CONSULTADA EN:'), 0, 1, 'L');
                $pdf->SetX(10);
                $pdf->Cell(0, 5, 'WWW.BUFEO.COM/CPE', 0, 1, 'L');
                $pdf->Ln(3);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell(0, 5, utf8_decode('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA'), 0, 1, 'C');
            } else {
                $pdf->Ln(5);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Cell(180, 5, 'ESTE NO ES UN COMPROBANTE VALIDO PARA SUNAT', 0, 1, 'C');
                $pdf->Cell(180, 5, utf8_decode('SI REQUIERE UNA BOLETA O FACTURA, SOLICÍTALO'), 0, 1, 'C');
            }

            $dir = storage_path('app/comprobantes_ventas');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $pdfFilePath = $dir . DIRECTORY_SEPARATOR . $serie_correlativo . '-' . date('Y-m-d') . '.pdf';
            $pdf->Output($pdfFilePath, 'F');
            return $pdfFilePath;

        }
    }
    public function imprimir_ticketera_venta(){
        $id = $_GET['venta_id'];
        $id_venta = (int)$id;
        if($id_venta){
            $dato_venta    = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);

            $formas_de_pago_mensaje = '';
            foreach ($formas_de_pago as $for) {
                $formas_de_pago_mensaje .= $for->tipo_pago_nombre . ' ';
            }
            $formas_de_pago_mensaje = trim($formas_de_pago_mensaje);

            $fecha_obj        = \DateTime::createFromFormat('Y-m-d H:i:s', $dato_venta->venta_fecha);
            $fecha_formateada = $fecha_obj->format('d/m/Y');
            $hora_formateada  = $fecha_obj->format('H:i:s');

            $ruta_qr = $this->general->generar_qr($dato_venta->id_venta);

            if ($dato_venta->venta_tipo == "03") {
                $tipo_comprobante  = "BOLETA DE VENTA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie . "-" . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DNI'; $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "01") {
                $tipo_comprobante  = "FACTURA ELECTRONICA";
                $serie_correlativo = $dato_venta->venta_serie . "-" . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'RUC'; $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "07") {
                $tipo_comprobante  = utf8_decode("NOTA DE CRÉDITO ELECTRONICA");
                $serie_correlativo = $dato_venta->venta_serie . "-" . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "08") {
                $tipo_comprobante  = utf8_decode("NOTA DE DÉBITO ELECTRONICA");
                $serie_correlativo = $dato_venta->venta_serie . "-" . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } else if ($dato_venta->venta_tipo == "20") {
                $tipo_comprobante  = "NOTA DE VENTA";
                $serie_correlativo = $dato_venta->venta_serie . "-" . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            }

            $da = new NumeroALetras();
            $esGratuitaHeader = ((float)$dato_venta->venta_total == 0 && (float)$dato_venta->venta_totalgratuita > 0);
            $importe_letra = $esGratuitaHeader
                ? utf8_decode('TRANSFERENCIA GRATUITA - Valor referencial: ') . $da->toInvoice((float)$dato_venta->venta_totalgratuita, '2', 'soles')
                : $da->toInvoice((float)$dato_venta->venta_total, '2', 'soles');

            $condicion_pago = $dato_venta->id_formas_pago == 1 ? 'CONTADO' : utf8_decode('CRÉDITO');

            // Sedes (excluir la sede de la caja si aplica)
            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');
            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();

            // SubTotal = suma de bases sin IGV ni ICBPER
            $subTotal = (float)$dato_venta->venta_totalgravada
                      + (float)$dato_venta->venta_totalexonerada
                      + (float)$dato_venta->venta_totalinafecta;

            $tieneDesc  = !empty(trim((string)$dato_venta->empresa_descripcion));
            $tieneTel   = !empty($dato_venta->empresa_telefono1) || !empty($dato_venta->empresa_telefono2);
            $tieneEmail = !empty($dato_venta->empresa_correo);

            // ── Altura dinámica ──────────────────────────────────
            $filas_detalle = count($detalle_venta);
            $filas_sedes   = count($sedes);
            $altura_base   = 217
                           + ($filas_sedes * 4)
                           + ($tieneDesc   ? 10 : 0)
                           + ($tieneTel    ? 4  : 0)
                           + ($tieneEmail  ? 4  : 0);
            $altura_total  = $altura_base + (12 * $filas_detalle);

            // ── Inicializar PDF ───────────────────────────────────
            $pdf = new PDFBufeo('P', 'mm', [80, $altura_total]);
            $pdf->SetMargins(5, 4, 5);
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            $pdf->SetDrawColor(60, 60, 60);
            $cw = 70;

            // ══ CABECERA EMPRESA ══════════════════════════════════
            $pdf->Ln(4);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->Cell($cw, 6, utf8_decode($dato_venta->empresa_razon_social), 0, 1, 'C');

            if ($tieneDesc) {
                $pdf->SetFont('Helvetica', '', 6);
                $descTexto = str_replace('\n', "\n", (string)$dato_venta->empresa_descripcion);
                $pdf->MultiCell($cw, 3, utf8_decode($descTexto), 0, 'C');
            }

            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell($cw, 4, utf8_decode($dato_venta->empresa_domiciliofiscal), 0, 1, 'C');
            $pdf->Cell($cw, 3.5, utf8_decode($dato_venta->ubigeo_departamento . '-' . $dato_venta->ubigeo_provincia . '-' . $dato_venta->ubigeo_distrito), 0, 1, 'C');

            if ($tieneTel) {
                if (!empty($dato_venta->empresa_telefono1) && !empty($dato_venta->empresa_telefono2)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1 . '  Telef: ' . $dato_venta->empresa_telefono2;
                } elseif (!empty($dato_venta->empresa_telefono1)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1;
                } else {
                    $telLinea = 'Telef: ' . $dato_venta->empresa_telefono2;
                }
                $pdf->Cell($cw, 3.5, $telLinea, 0, 1, 'C');
            }

            if ($tieneEmail) {
                $pdf->Cell($cw, 3.5, 'Email: ' . $dato_venta->empresa_correo, 0, 1, 'C');
            }

            foreach ($sedes as $sede) {
                $sedeTexto = $sede->tienda_nombre;
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $sede->tienda_direccion;
                }
                $pdf->SetFont('Helvetica', '', 6);
                $pdf->Cell($cw, 3.5, utf8_decode($sedeTexto), 0, 1, 'C');
            }

            $pdf->Ln(1);
            $pdf->SetDrawColor(170, 170, 170);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetDrawColor(60, 60, 60);
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell($cw, 5, 'RUC N' . chr(176) . ' ' . $dato_venta->empresa_ruc, 0, 1, 'C');

            // ══ LÍNEA GRUESA ══════════════════════════════════════
            $pdf->Ln(1);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Ln(2);

            // ══ TIPO COMPROBANTE (fondo gris) ═════════════════════
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell($cw, 6, utf8_decode($tipo_comprobante), 0, 1, 'C', true);
            $pdf->Cell($cw, 5, $serie_correlativo, 0, 1, 'C');
            $pdf->SetFillColor(255, 255, 255);

            // ══ BANNER GRATUITA (si aplica) ═══════════════════════
            $esGratuitaPdf = ((float)$dato_venta->venta_total == 0 && (float)$dato_venta->venta_totalgratuita > 0);
            if ($esGratuitaPdf) {
                $pdf->Ln(1);
                $pdf->SetFillColor(255, 243, 205);
                $pdf->SetDrawColor(255, 193, 7);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->Cell($cw, 5, utf8_decode('TRANSFERENCIA A TÍTULO GRATUITO'), 1, 1, 'C', true);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(60, 60, 60);
            }

            // ══ LÍNEA FINA ════════════════════════════════════════
            $pdf->Ln(1);
            $pdf->SetDrawColor(170, 170, 170);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetDrawColor(60, 60, 60);
            $pdf->Ln(2);

            // ══ FECHA / HORA (sin etiquetas) ══════════════════════
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell(35, 4, $fecha_formateada, 0, 0, 'L');
            $pdf->Cell(35, 4, $hora_formateada,  0, 1, 'R');

            // ══ DATOS DEL CLIENTE ═════════════════════════════════
            $clienteNombre = ($dato_venta->id_tipo_documento != 4)
                ? $dato_venta->cliente_nombre
                : $dato_venta->cliente_razonsocial;

            if (!empty(trim((string)$clienteNombre))) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->Write(4, 'Nomb: ');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Write(4, utf8_decode($clienteNombre));
                $pdf->Ln(4);
            }

            if (!empty($dato_venta->cliente_direccion)) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->Write(4, 'Direc: ');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Write(4, utf8_decode($dato_venta->cliente_direccion));
                $pdf->Ln(4);
            }

            if (!empty($documento)) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->Write(4, "$dnni: ");
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Write(4, $documento);
                $pdf->Ln(4);
            }

            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell($cw, 4, utf8_decode('Condición de Pago: ' . $condicion_pago), 0, 1, 'R');
            $pdf->Cell($cw, 4, 'Forma de Pago: ' . utf8_decode(trim($formas_de_pago_mensaje)), 0, 1, 'R');

            // ══ LÍNEA GRUESA ══════════════════════════════════════
            $pdf->Ln(1);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Ln(1.5);

            // ══ ENCABEZADO DE COLUMNAS (fondo oscuro) ═════════════
            $pdf->SetFillColor(50, 50, 50);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 6.5);
            $pdf->Cell(28, 5, 'Detalle',      0, 0, 'L', true);
            $pdf->Cell(20, 5, 'CantidadUMed', 0, 0, 'C', true);
            $pdf->Cell(11, 5, 'Precio',       0, 0, 'R', true);
            $pdf->Cell(11, 5, 'Total',        0, 1, 'R', true);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);

            // ══ DETALLE DE PRODUCTOS (2 filas por producto) ═══════
            foreach ($detalle_venta as $f) {
                // Fila 1: código + nombre a ancho completo
                $descripcion = '';
                if (!empty($f->pro_codigo)) {
                    $descripcion = utf8_decode($f->pro_codigo) . ' ';
                }
                $descripcion .= utf8_decode($f->venta_detalle_nombre_producto);
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->MultiCell($cw, 4, $descripcion, 0, 'L');

                // Fila 2: cantidad | unidad x factor | precio | total
                $numCantidad = number_format((float)$f->venta_detalle_cantidad, 0, '.', '');
                $unidad      = !empty($f->pres_nombre)
                    ? utf8_decode($f->pres_nombre)
                    : (!empty($f->medida_codigo_unidad) ? utf8_decode($f->medida_codigo_unidad) : 'Und');
                $factor      = (float)($f->pres_factor ?? 1.0);
                $factorText  = $factor > 1 ? number_format($factor, 0) : $numCantidad;
                $cantTexto   = $unidad . ' x ' . $factorText;
                $precioUnit  = number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', '');
                $totalItem   = number_format(round((float)$f->venta_detalle_importe_total,   2), 2, '.', '');

                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell(9,  4, $numCantidad, 0, 0, 'R');
                $pdf->SetFont('Helvetica', '', 6);
                $pdf->Cell(20, 4, $cantTexto,   0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell(21, 4, $precioUnit,  0, 0, 'R');
                $pdf->Cell(20, 4, $totalItem,   0, 1, 'R');
            }

            // ══ SEPARADOR ═════════════════════════════════════════
            $pdf->Ln(1.5);
            $pdf->SetDrawColor(100, 100, 100);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetDrawColor(60, 60, 60);
            $pdf->Ln(2);

            // ══ SUBTOTALES ════════════════════════════════════════
            $pdf->SetFont('Helvetica', '', 7);
            $sim    = $dato_venta->simbolo;
            $labelW = 40;
            $valorW = 30;

            if ((float)$dato_venta->venta_totalgratuita > 0) {
                $pdf->Cell($labelW, 4, 'Op.Grat:', 0, 0, 'R');
                $pdf->Cell($valorW, 4, "$sim " . number_format((float)$dato_venta->venta_totalgratuita, 2), 0, 1, 'R');
            }

            $pdf->Cell($labelW, 4, 'SubTotal:', 0, 0, 'R');
            $pdf->Cell($valorW, 4, "$sim " . number_format($subTotal, 2), 0, 1, 'R');

            $pdf->Cell($labelW, 4, 'I.G.V.:', 0, 0, 'R');
            $pdf->Cell($valorW, 4, "$sim " . number_format((float)$dato_venta->venta_totaligv, 2), 0, 1, 'R');

            $pdf->Cell($labelW, 4, 'ICBPER:', 0, 0, 'R');
            $pdf->Cell($valorW, 4, "$sim " . number_format((float)$dato_venta->venta_icbper, 2), 0, 1, 'R');

            $pdf->Cell($labelW, 4, 'Cp Exon:', 0, 0, 'R');
            $pdf->Cell($valorW, 4, "$sim " . number_format((float)$dato_venta->venta_totalexonerada, 2), 0, 1, 'R');

            // ══ TOTAL DESTACADO ═══════════════════════════════════
            $pdf->Ln(1);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Ln(1);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell($labelW, 5, 'Imp.Total:', 0, 0, 'R');
            $pdf->Cell($valorW, 5, "$sim " . number_format((float)$dato_venta->venta_total, 2), 0, 1, 'R');
            $pdf->SetLineWidth(0.4);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetLineWidth(0.2);
            $pdf->Ln(2);

            // ══ IMPORTE EN LETRAS ═════════════════════════════════
            $pdf->SetFont('Helvetica', 'I', 7);
            $pdf->MultiCell($cw, 3.5, utf8_decode('Son: ' . $importe_letra), 0, 'R');

            // ══ DESGLOSE DE PAGOS ═════════════════════════════════
            if ($dato_venta->id_formas_pago == 1 && !$esGratuitaPdf) {
                $pdf->Ln(1);
                $pdf->SetFont('Helvetica', '', 7);
                foreach ($formas_de_pago as $for) {
                    $pdf->Cell($labelW, 4, utf8_decode($for->tipo_pago_nombre) . ':', 0, 0, 'R');
                    $pdf->Cell($valorW, 4, "$sim " . number_format((float)$for->venta_detalle_pago_monto, 2), 0, 1, 'R');
                }
                $pdf->Cell($labelW, 4, 'Vuelto:', 0, 0, 'R');
                $pdf->Cell($valorW, 4, "$sim " . number_format((float)$dato_venta->venta_vuelto, 2), 0, 1, 'R');
            }

            // ══ QR (izquierda) + PUNTO DE VENTA / VENDEDOR ════════
            if ($dato_venta->venta_tipo != '20') {
                $pdf->Ln(3);
                $wQR = 22;
                $yQR = $pdf->GetY();
                $pdf->Image($ruta_qr, 5, $yQR, $wQR, $wQR);

                $pdf->SetXY(5 + $wQR + 2, $yQR + 4);
                $pdf->SetFont('Helvetica', '', 7);
                $ptoVenta = !empty($dato_venta->tienda_nombre) ? utf8_decode($dato_venta->tienda_nombre) : '-';
                $pdf->Cell(46, 4.5, 'Pto.Venta: ' . $ptoVenta, 0, 1, 'L');
                $pdf->SetX(5 + $wQR + 2);
                $pdf->Cell(46, 4.5, utf8_decode('Vendedor: ' . $dato_venta->nombre_users), 0, 1, 'L');

                $pdf->SetY($yQR + $wQR + 2);
            }

            // ══ PIE ═══════════════════════════════════════════════
            $pdf->Ln(1);
            $pdf->SetDrawColor(170, 170, 170);
            $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
            $pdf->SetDrawColor(60, 60, 60);
            $pdf->Ln(2);

            $pdf->SetFont('Helvetica', '', 6);
            $pdf->MultiCell($cw, 3, utf8_decode('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA'), 0, 'C');
            $pdf->Ln(1);
            $pdf->MultiCell($cw, 3, utf8_decode('** Una vez salida la mercadería, no se aceptan cambios ni devoluciones **'), 0, 'C');
            $pdf->Ln(1);
            $pdf->MultiCell($cw, 3, '** Gracias por su Compra **', 0, 'C');

            if ($dato_venta->venta_tipo != '20') {
                $pdf->Ln(2);
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $pdf->SetFont('Helvetica', '', 5.5);
                    $pdf->MultiCell($cw, 3, utf8_decode('CODIGO HASH: ' . $dato_venta->venta_codigo_hash), 0, 'C');
                    $pdf->Ln(1);
                }
                $pdf->SetFont('Helvetica', 'I', 6);
                $pdf->MultiCell($cw, 3, utf8_decode('Consulte validez de su comprobante en:'), 0, 'C');
                $pdf->SetFont('Helvetica', '', 5.5);
                $pdf->MultiCell($cw, 3, 'http://e.consulta.sunat.gob.pe/ol-ti-itconsvalcpe/ConsVCpe.htm', 0, 'C');
                $pdf->Ln(1);
                $pdf->SetFont('Helvetica', 'I', 6);
                $pdf->MultiCell($cw, 3, utf8_decode('Representación impresa de comprobante de pago electrónico'), 0, 'C');
            }

            $pdf->Output('I', $serie_correlativo . '-' . date('Y-m-d') . '.pdf');
            exit;
        }
    }

    public function imprimir_resumen_caja()
    {
        $idCaja = (int) request('caja_id', 0);

        $caja = DB::table('caja as c')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->join('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
            ->join('users as u', 'u.id_users', '=', 'c.id_users_apertura')
            ->leftJoin('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id_users')
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('c.id_caja', $idCaja)
            ->select('c.*', 'cn.caja_numero_nombre', 'e.empresa_razon_social', 'e.empresa_ruc',
                     't.tienda_nombre', 'u.nombre_users', 'r.name as rol_nombre')
            ->first();

        if (!$caja) abort(404, 'Caja no encontrada');

        // Pre-calcular cantidad de vendedores para altura dinámica
        $idRolVendedorPre  = DB::table('roles')->where('name', 'vendedor')->value('id');
        $idsVendedoresPre  = DB::table('model_has_roles')->where('role_id', $idRolVendedorPre)->pluck('model_id')->toArray();
        $idsConVentasPre   = DB::table('ventas')->where('id_caja', $idCaja)->distinct()->pluck('id_users')->toArray();
        $numVendedores     = count(array_unique(array_merge($idsVendedoresPre, $idsConVentasPre)));

        $cw           = 70;
        $altura_total = 400 + ($numVendedores * 4);

        $pdf = new PDFBufeo('P', 'mm', [80, $altura_total]);
        $pdf->SetMargins(5, 4, 5);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetDrawColor(60, 60, 60);

        // ── TÍTULO EMPRESA ──────────────────────────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell($cw, 7, utf8_decode($caja->empresa_razon_social), 0, 1, 'C');

        // ── SUBTÍTULO ───────────────────────────────────────────
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, utf8_decode('Resumen de Ventas'), 0, 1, 'C');

        // Fecha y caja
        $pdf->Ln(1);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, 'Caja:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, utf8_decode($caja->caja_numero_nombre), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, 'Fecha:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, \Carbon\Carbon::parse($caja->caja_fecha)->format('d/m/Y'), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, 'Hora:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, \Carbon\Carbon::parse($caja->caja_fecha_apertura)->format('H:i:s'), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, 'Operador:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, utf8_decode($caja->nombre_users . ' (' . $caja->rol_nombre . ')'), 0, 1, 'R');

        // ── SEPARADOR ───────────────────────────────────────────
        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        // ── Nro de Transacciones ────────────────────────────────
        $nroTransacciones = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->count();

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, utf8_decode('Nro de Transacciones:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, (string) $nroTransacciones, 0, 1, 'R');

        // ── Boletas ─────────────────────────────────────────────
        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $boletaInicial = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '03')
            ->orderBy('venta_correlativo', 'asc')
            ->select('venta_serie', 'venta_correlativo')
            ->first();

        $boletaFinal = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '03')
            ->orderBy('venta_correlativo', 'desc')
            ->select('venta_serie', 'venta_correlativo')
            ->first();

        $totalBoletas = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '03')
            ->sum('venta_total');

        $fmtBoleta = fn($b) => $b ? $b->venta_serie . '-' . (int)$b->venta_correlativo : 'Sin registro';

        $filasBoleta = [
            'Nro de Boleta Inicial'  => $fmtBoleta($boletaInicial),
            'Nro de Boleta Final'    => $fmtBoleta($boletaFinal),
            'Venta Total Boleta'     => 'S/ ' . number_format((float)$totalBoletas, 2),
            'Nro Boletas Anulados'   => '0',
            'Total Boletas Anulados' => 'S/ 0.00',
        ];
        $pdf->SetFont('Helvetica', '', 7);
        foreach ($filasBoleta as $label => $valor) {
            $pdf->Cell($cw / 2, 4, utf8_decode($label . ':'), 0, 0, 'L');
            $pdf->Cell($cw / 2, 4, $valor, 0, 1, 'R');
        }

        // ── Facturas ─────────────────────────────────────────────
        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $facturaInicial = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '01')
            ->orderBy('venta_correlativo', 'asc')
            ->select('venta_serie', 'venta_correlativo')
            ->first();

        $facturaFinal = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '01')
            ->orderBy('venta_correlativo', 'desc')
            ->select('venta_serie', 'venta_correlativo')
            ->first();

        $totalFacturas = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('venta_tipo', '01')
            ->sum('venta_total');

        $fmtFactura = fn($f) => $f ? $f->venta_serie . '-' . (int)$f->venta_correlativo : 'Sin registro';

        $filasFactura = [
            'Nro de Factura Inicial'  => $fmtFactura($facturaInicial),
            'Nro de Factura Final'    => $fmtFactura($facturaFinal),
            'Venta Total Factura'     => 'S/ ' . number_format((float)$totalFacturas, 2),
            'Nro Facturas Anulados'   => '0',
            'Total Facturas Anulados' => 'S/ 0.00',
        ];
        $pdf->SetFont('Helvetica', '', 7);
        foreach ($filasFactura as $label => $valor) {
            $pdf->Cell($cw / 2, 4, utf8_decode($label . ':'), 0, 0, 'L');
            $pdf->Cell($cw / 2, 4, $valor, 0, 1, 'R');
        }

        // ── Separador post-facturas ──────────────────────────────
        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        // ── SECCIÓN: Total de Ventas ─────────────────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, 'Total de Ventas', 0, 1, 'C');
        $pdf->Ln(1);

        $totalGeneral  = (float)$totalBoletas + (float)$totalFacturas;

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, 'Venta Total Boleta:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format((float)$totalBoletas, 2), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, 'Venta Total Facturas:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format((float)$totalFacturas, 2), 0, 1, 'R');
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($cw / 2, 4, 'Total General:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalGeneral, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        // ── SECCIÓN: Tipos de Pago ───────────────────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, 'Tipos de Pago', 0, 1, 'C');
        $pdf->Ln(1);

        $pagosPorTipo = DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->where('v.id_caja', $idCaja)
            ->where('v.id_users', auth()->user()->id_users)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->get()
            ->mapWithKeys(fn($r) => [strtoupper($r->tipo_pago_nombre) => (float)$r->total]);

        $totalCredito = DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->where('id_users', auth()->user()->id_users)
            ->where('id_formas_pago', 2)
            ->sum('venta_total');

        $medios = [
            'Credito'       => (float)$totalCredito,
            'Efectivo'      => $pagosPorTipo->get('EFECTIVO', 0),
            'Yape'          => $pagosPorTipo->get('YAPE', 0),
            'Plin'          => $pagosPorTipo->get('PLIN', 0),
            'Transferencia' => $pagosPorTipo->get('TRANSFERENCIA BANCARIA', $pagosPorTipo->get('TRANSFERENCIA', 0)),
            'Deposito'      => $pagosPorTipo->get('DEPÓSITO', $pagosPorTipo->get('DEPOSITO', 0)),
            'Cheque'        => $pagosPorTipo->get('CHEQUE', 0),
        ];

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($medios as $nombre => $monto) {
            $pdf->Cell($cw / 2, 4, utf8_decode($nombre . ':'), 0, 0, 'L');
            $pdf->Cell($cw / 2, 4, 'S/ ' . number_format((float)$monto, 2), 0, 1, 'R');
        }

        // ── Marcas de tarjeta ────────────────────────────────────
        $marcasTotales = DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->where('v.id_caja', $idCaja)
            ->where('v.id_users', auth()->user()->id_users)
            ->whereRaw("UPPER(tp.tipo_pago_nombre) LIKE '%TARJETA%'")
            ->whereNotNull('vdp.marca_tarjeta')
            ->where('vdp.marca_tarjeta', '!=', '')
            ->groupBy('vdp.marca_tarjeta')
            ->select('vdp.marca_tarjeta', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->get()
            ->mapWithKeys(fn($r) => [strtoupper($r->marca_tarjeta) => (float)$r->total]);

        $marcas = [
            'Tarjeta - Visa'             => 'VISA',
            'Tarjeta - Mastercard'       => 'MASTERCARD',
            'Tarjeta - American Express' => 'AMERICAN EXPRESS',
            'Tarjeta - UnionPay'         => 'UNIONPAY',
        ];

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($marcas as $label => $key) {
            $monto = $marcasTotales->get($key, 0);
            $pdf->SetX(5);
            $pdf->Cell($cw / 2, 4, utf8_decode($label . ':'), 0, 0, 'L');
            $pdf->Cell($cw / 2, 4, 'S/ ' . number_format((float)$monto, 2), 0, 1, 'R');
        }

        $totalMarcas = $marcasTotales->sum();

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($cw / 2, 4, 'Total:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalMarcas, 2), 0, 1, 'R');
        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        // ── SECCIÓN: Ventas x Vendedor ───────────────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, utf8_decode('Ventas x Vendedor'), 0, 1, 'C');
        $pdf->Ln(1);

        // Totales por vendedor real:
        //   pedido  → pedidos.id_users
        //   proforma → proformas.id_users
        //   directo  → ventas.id_users (fallback)
        $ventasPorVendedor = DB::table('ventas as v')
            ->leftJoin('pedidos as ped', 'ped.id_pedido', '=', 'v.id_pedido')
            ->leftJoin('proformas as pro', 'pro.id_profo', '=', 'v.id_profo')
            ->where('v.id_caja', $idCaja)
            ->select(
                DB::raw('COALESCE(ped.id_users, pro.id_users, v.id_users) as vendedor_id'),
                DB::raw('SUM(v.venta_total) as total')
            )
            ->groupBy(DB::raw('COALESCE(ped.id_users, pro.id_users, v.id_users)'))
            ->get()
            ->mapWithKeys(fn($r) => [(int)$r->vendedor_id => (float)$r->total]);

        // Usuarios con rol vendedor (aunque no hayan vendido)
        $idRolVendedor = DB::table('roles')->where('name', 'vendedor')->value('id');
        $idsVendedores = DB::table('model_has_roles')
            ->where('role_id', $idRolVendedor)
            ->pluck('model_id')
            ->map(fn($id) => (int)$id)
            ->toArray();

        $idsRelevantes = array_unique(array_merge($idsVendedores, $ventasPorVendedor->keys()->toArray()));

        $usuarios = DB::table('users')
            ->whereIn('id_users', $idsRelevantes)
            ->orderBy('nombre_users')
            ->get(['id_users', 'nombre_users']);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($usuarios as $u) {
            $total = $ventasPorVendedor->get((int)$u->id_users, 0);
            $pdf->Cell($cw / 2, 4, utf8_decode($u->nombre_users), 0, 0, 'L');
            $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($total, 2), 0, 1, 'R');
        }

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        // ── SECCIÓN: Saldo de Caja ───────────────────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, 'Saldo de Caja', 0, 1, 'C');
        $pdf->Ln(1);

        $totalNotaCredito = 0.00;
        $totalNotaDebito  = 0.00;
        $totalSaldoVenta  = $totalGeneral - $totalNotaCredito + $totalNotaDebito;

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, 'Total Ventas:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalGeneral, 2), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, utf8_decode('Total Nota Crédito:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalNotaCredito, 2), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, utf8_decode('Total Nota Débito:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalNotaDebito, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($cw / 2, 4, 'Total Saldo de Venta:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalSaldoVenta, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        // ── SECCIÓN: Saldo de Caja (Efectivo) ───────────────────
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, utf8_decode('Saldo de Caja (Efectivo)'), 0, 1, 'C');
        $pdf->Ln(1);

        $totalVentasEfectivo   = (float)$pagosPorTipo->get('EFECTIVO', 0);
        $ncEfectivo            = 0.00;
        $ndEfectivo            = 0.00;
        $totalVentaEfectivo    = $totalVentasEfectivo - $ncEfectivo + $ndEfectivo;
        $pagosAnticipadoVinc   = 0.00;
        $totalFinalEfectivo    = $totalVentaEfectivo - $pagosAnticipadoVinc;

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, 'Total Ventas Efectivo:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalVentasEfectivo, 2), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, utf8_decode('Total Nota Crédito:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($ncEfectivo, 2), 0, 1, 'R');
        $pdf->Cell($cw / 2, 4, utf8_decode('Total Nota Débito:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($ndEfectivo, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($cw / 2, 4, 'Total Venta Efectivo:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalVentaEfectivo, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw / 2, 4, utf8_decode('Pagos Anticip/Vinculado:'), 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($pagosAnticipadoVinc, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($cw / 2, 4, 'Total Venta Efectivo:', 0, 0, 'L');
        $pdf->Cell($cw / 2, 4, 'S/ ' . number_format($totalFinalEfectivo, 2), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);

        $pdf->Output('I', 'resumen-caja-' . $idCaja . '.pdf');
        exit;
    }

    public function imprimir_ticketera_escpos_old()
    {
        $id_venta = (int) request('venta_id', 0);
        if (!$id_venta) {
            return response()->json(['ok' => false, 'error' => 'ID de venta inválido']);
        }

        try {
            $dato_venta     = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta  = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);

            if (!$dato_venta) {
                return response()->json(['ok' => false, 'error' => 'Venta no encontrada']);
            }

            $formas_de_pago_mensaje = '';
            foreach ($formas_de_pago as $for) {
                $formas_de_pago_mensaje .= $for->tipo_pago_nombre . ' ';
            }
            $formas_de_pago_mensaje = trim($formas_de_pago_mensaje);

            $fecha_obj        = \DateTime::createFromFormat('Y-m-d H:i:s', $dato_venta->venta_fecha);
            $fecha_formateada = $fecha_obj->format('d/m/Y');
            $hora_formateada  = $fecha_obj->format('H:i:s');

            // QR content (same formula used in generar_qr)
            $contenido_qr = $dato_venta->empresa_ruc . '|' . $dato_venta->venta_tipo . '|'
                . $dato_venta->venta_serie . '|' . $dato_venta->venta_correlativo
                . '|' . $dato_venta->venta_totaligv . '|' . $dato_venta->venta_total
                . '|' . date('Y-m-d', strtotime($dato_venta->venta_fecha))
                . '|' . $dato_venta->tipodocumento_codigo . '|' . $dato_venta->cliente_numero;

            if ($dato_venta->venta_tipo == '03') {
                $tipo_comprobante  = 'BOLETA DE VENTA ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DNI'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '01') {
                $tipo_comprobante  = 'FACTURA ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'RUC'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '07') {
                $tipo_comprobante  = 'NOTA DE CREDITO ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '08') {
                $tipo_comprobante  = 'NOTA DE DEBITO ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } else {
                $tipo_comprobante  = 'NOTA DE VENTA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            }

            $da = new NumeroALetras();
            $esGratuita = ((float)$dato_venta->venta_total == 0 && (float)$dato_venta->venta_totalgratuita > 0);
            $importe_letra = $esGratuita
                ? 'TRANSFERENCIA GRATUITA - Valor ref.: ' . $da->toInvoice((float)$dato_venta->venta_totalgratuita, '2', 'soles')
                : $da->toInvoice((float)$dato_venta->venta_total, '2', 'soles');

            $condicion_pago = $dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CREDITO';

            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');
            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();

            $subTotal = (float)$dato_venta->venta_totalgravada
                      + (float)$dato_venta->venta_totalexonerada
                      + (float)$dato_venta->venta_totalinafecta;

            $sim = $dato_venta->simbolo;
            $tieneDesc  = !empty(trim((string)$dato_venta->empresa_descripcion));
            $tieneTel   = !empty($dato_venta->empresa_telefono1) || !empty($dato_venta->empresa_telefono2);
            $tieneEmail = !empty($dato_venta->empresa_correo);

            // ── Helpers ──────────────────────────────────────────────
            $COL = 48;
            $SEP_THIN  = str_repeat('-', $COL) . "\n";
            $SEP_THICK = str_repeat('=', $COL) . "\n";

            // Right-aligned label + value on a single line
            $rLine = function (string $label, string $value) use ($COL): string {
                $total = strlen($label) + strlen($value);
                if ($total >= $COL) return $label . $value . "\n";
                return str_pad($label, $COL - strlen($value)) . $value . "\n";
            };

            // ── Inicializar impresora ─────────────────────────────────
            $tmpEscpos = tempnam(sys_get_temp_dir(), 'ticket_');
            $connector = new FilePrintConnector($tmpEscpos);
            $printer   = new Printer($connector);
            $printer->initialize();

            // ══ CABECERA EMPRESA ══════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($dato_venta->empresa_razon_social . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();

            if ($tieneDesc) {
                $desc = str_replace('\n', "\n", (string)$dato_venta->empresa_descripcion);
                $printer->text($desc . "\n");
            }

            $printer->text($dato_venta->empresa_domiciliofiscal . "\n");
            $printer->text($dato_venta->ubigeo_departamento . '-' . $dato_venta->ubigeo_provincia . '-' . $dato_venta->ubigeo_distrito . "\n");

            if ($tieneTel) {
                if (!empty($dato_venta->empresa_telefono1) && !empty($dato_venta->empresa_telefono2)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1 . '  Telef: ' . $dato_venta->empresa_telefono2;
                } elseif (!empty($dato_venta->empresa_telefono1)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1;
                } else {
                    $telLinea = 'Telef: ' . $dato_venta->empresa_telefono2;
                }
                $printer->text($telLinea . "\n");
            }

            if ($tieneEmail) {
                $printer->text('Email: ' . $dato_venta->empresa_correo . "\n");
            }

            foreach ($sedes as $sede) {
                $sedeTexto = $sede->tienda_nombre;
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $sede->tienda_direccion;
                }
                $printer->text($sedeTexto . "\n");
            }

            $printer->text($SEP_THIN);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text('RUC N° ' . $dato_venta->empresa_ruc . "\n");
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ══ TIPO COMPROBANTE ══════════════════════════════════════
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($tipo_comprobante . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();
            $printer->text($serie_correlativo . "\n");

            if ($esGratuita) {
                $printer->text('*** TRANSFERENCIA A TITULO GRATUITO ***' . "\n");
            }

            $printer->text($SEP_THIN);

            // ══ FECHA / HORA ══════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_pad($fecha_formateada, $COL - strlen($hora_formateada)) . $hora_formateada . "\n");

            // ══ DATOS DEL CLIENTE ═════════════════════════════════════
            $clienteNombre = ($dato_venta->id_tipo_documento != 4)
                ? $dato_venta->cliente_nombre
                : $dato_venta->cliente_razonsocial;

            if (!empty(trim((string)$clienteNombre))) {
                $printer->text('Nomb: ' . $clienteNombre . "\n");
            }
            if (!empty($dato_venta->cliente_direccion)) {
                $printer->text('Direc: ' . $dato_venta->cliente_direccion . "\n");
            }
            if (!empty($documento)) {
                $printer->text($dnni . ': ' . $documento . "\n");
            }

            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text('Condicion de Pago: ' . $condicion_pago . "\n");
            $printer->text('Forma de Pago: ' . $formas_de_pago_mensaje . "\n");

            // ══ DETALLE DE PRODUCTOS ══════════════════════════════════
            $printer->text($SEP_THICK);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text(
                str_pad('Detalle', 26)
                . str_pad('Cant/UM', 10, ' ', STR_PAD_LEFT)
                . str_pad('Precio', 6, ' ', STR_PAD_LEFT)
                . str_pad('Total', 6, ' ', STR_PAD_LEFT) . "\n"
            );
            $printer->selectPrintMode();
            $printer->text($SEP_THIN);

            foreach ($detalle_venta as $f) {
                // Fila 1: código + nombre
                $descripcion = '';
                if (!empty($f->pro_codigo)) {
                    $descripcion = $f->pro_codigo . ' ';
                }
                $descripcion .= $f->venta_detalle_nombre_producto;
                $printer->text(wordwrap($descripcion, $COL, "\n", true) . "\n");

                // Fila 2: cantidad | unidad x factor | precio | total
                $numCantidad = number_format((float)$f->venta_detalle_cantidad, 0, '.', '');
                $unidad      = !empty($f->pres_nombre)
                    ? $f->pres_nombre
                    : (!empty($f->medida_codigo_unidad) ? $f->medida_codigo_unidad : 'Und');
                $factor      = (float)($f->pres_factor ?? 1.0);
                $factorText  = $factor > 1 ? number_format($factor, 0) : $numCantidad;
                $cantTexto   = $unidad . ' x ' . $factorText;
                $precioUnit  = number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', '');
                $totalItem   = number_format(round((float)$f->venta_detalle_importe_total,   2), 2, '.', '');

                $printer->text(
                    str_pad($numCantidad, 5, ' ', STR_PAD_LEFT)
                    . ' '
                    . str_pad($cantTexto, 20)
                    . str_pad($precioUnit, 11, ' ', STR_PAD_LEFT)
                    . str_pad($totalItem, 11, ' ', STR_PAD_LEFT) . "\n"
                );
            }

            $printer->text($SEP_THIN);

            // ══ SUBTOTALES ════════════════════════════════════════════
            if ((float)$dato_venta->venta_totalgratuita > 0) {
                $printer->text($rLine('Op.Grat:', "$sim " . number_format((float)$dato_venta->venta_totalgratuita, 2)));
            }
            $printer->text($rLine('SubTotal:', "$sim " . number_format($subTotal, 2)));
            $printer->text($rLine('I.G.V.:', "$sim " . number_format((float)$dato_venta->venta_totaligv, 2)));
            $printer->text($rLine('ICBPER:', "$sim " . number_format((float)$dato_venta->venta_icbper, 2)));
            $printer->text($rLine('Cp Exon:', "$sim " . number_format((float)$dato_venta->venta_totalexonerada, 2)));

            $printer->text($SEP_THICK);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text($rLine('Imp.Total:', "$sim " . number_format((float)$dato_venta->venta_total, 2)));
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ══ IMPORTE EN LETRAS ═════════════════════════════════════
            $printer->text(wordwrap('Son: ' . $importe_letra, $COL, "\n", true) . "\n");

            // ══ DESGLOSE DE PAGOS ═════════════════════════════════════
            if ($dato_venta->id_formas_pago == 1 && !$esGratuita) {
                foreach ($formas_de_pago as $for) {
                    $printer->text($rLine($for->tipo_pago_nombre . ':', "$sim " . number_format((float)$for->venta_detalle_pago_monto, 2)));
                }
                $printer->text($rLine('Vuelto:', "$sim " . number_format((float)$dato_venta->venta_vuelto, 2)));
            }

            // ══ QR ════════════════════════════════════════════════════
            if ($dato_venta->venta_tipo != '20') {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->qrCode($contenido_qr, Printer::QR_ECLEVEL_M, 5);
            }

            // ══ PTO VENTA / VENDEDOR ══════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $ptoVenta = !empty($dato_venta->tienda_nombre) ? $dato_venta->tienda_nombre : '-';
            $printer->text('Pto.Venta: ' . $ptoVenta . "\n");
            $printer->text('Vendedor: ' . $dato_venta->nombre_users . "\n");

            // ══ PIE ═══════════════════════════════════════════════════
            $printer->text($SEP_THIN);
            $printer->text('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA' . "\n");
            $printer->text('** Una vez salida la mercaderia, no se aceptan cambios ni devoluciones **' . "\n");
            $printer->text('** Gracias por su Compra **' . "\n");

            if ($dato_venta->venta_tipo != '20') {
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $printer->text('CODIGO HASH: ' . $dato_venta->venta_codigo_hash . "\n");
                }
                $printer->text('Consulte validez en: http://e.consulta.sunat.gob.pe/ol-ti-itconsvalcpe/ConsVCpe.htm' . "\n");
                $printer->text('Representacion impresa de comprobante de pago electronico' . "\n");
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();
            $fileSize='';
            $cmd='';
            // Enviar al spooler de Windows vía shell (más robusto desde servicio Apache)
            exec('copy /b ' . escapeshellarg($tmpEscpos) . ' "\\\\localhost\\ticket" 2>&1', $out, $code);
            @unlink($tmpEscpos);

            if ($code !== 0) {
                throw new \Exception('No se pudo enviar a la impresora. Asegúrese de que "ticket" esté compartida. ' . implode(' ', $out));
            }

            return response()->json(['ok' => true, '_debug' => ['fileSize' => $fileSize, 'execOut' => $out, 'execCode' => $code, 'cmd' => $cmd]]);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    public function imprimir_ticketera_escpos_old_new()
    {
        $id_venta = (int) request('venta_id', 0);
        if (!$id_venta) {
            return response()->json(['ok' => false, 'error' => 'ID de venta inválido']);
        }

        try {
            $dato_venta     = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta  = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);

            if (!$dato_venta) {
                return response()->json(['ok' => false, 'error' => 'Venta no encontrada']);
            }

            $formas_de_pago_mensaje = '';
            foreach ($formas_de_pago as $for) {
                $formas_de_pago_mensaje .= $for->tipo_pago_nombre . ' ';
            }
            $formas_de_pago_mensaje = trim($formas_de_pago_mensaje);

            $fecha_obj        = \DateTime::createFromFormat('Y-m-d H:i:s', $dato_venta->venta_fecha);
            $fecha_formateada = $fecha_obj->format('d/m/Y');
            $hora_formateada  = $fecha_obj->format('H:i:s');

            // QR content (same formula used in generar_qr)
            $contenido_qr = $dato_venta->empresa_ruc . '|' . $dato_venta->venta_tipo . '|'
                . $dato_venta->venta_serie . '|' . $dato_venta->venta_correlativo
                . '|' . $dato_venta->venta_totaligv . '|' . $dato_venta->venta_total
                . '|' . date('Y-m-d', strtotime($dato_venta->venta_fecha))
                . '|' . $dato_venta->tipodocumento_codigo . '|' . $dato_venta->cliente_numero;

            if ($dato_venta->venta_tipo == '03') {
                $tipo_comprobante  = 'BOLETA DE VENTA ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DNI'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '01') {
                $tipo_comprobante  = 'FACTURA ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'RUC'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '07') {
                $tipo_comprobante  = 'NOTA DE CREDITO ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } elseif ($dato_venta->venta_tipo == '08') {
                $tipo_comprobante  = 'NOTA DE DEBITO ELECTRONICA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            } else {
                $tipo_comprobante  = 'NOTA DE VENTA';
                $serie_correlativo = $dato_venta->venta_serie . '-' . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
            }

            $da = new NumeroALetras();
            $esGratuita = ((float)$dato_venta->venta_total == 0 && (float)$dato_venta->venta_totalgratuita > 0);
            $importe_letra = $esGratuita
                ? 'TRANSFERENCIA GRATUITA - Valor ref.: ' . $da->toInvoice((float)$dato_venta->venta_totalgratuita, '2', 'soles')
                : $da->toInvoice((float)$dato_venta->venta_total, '2', 'soles');

            $condicion_pago = $dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CREDITO';

            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');
            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();

            $subTotal = (float)$dato_venta->venta_totalgravada
                      + (float)$dato_venta->venta_totalexonerada
                      + (float)$dato_venta->venta_totalinafecta;

            $sim = $dato_venta->simbolo;
            $tieneDesc  = !empty(trim((string)$dato_venta->empresa_descripcion));
            $tieneTel   = !empty($dato_venta->empresa_telefono1) || !empty($dato_venta->empresa_telefono2);
            $tieneEmail = !empty($dato_venta->empresa_correo);

            // ── Helpers ──────────────────────────────────────────────
            $COL = 48;
            $SEP_THIN  = str_repeat('-', $COL) . "\n";
            $SEP_THICK = str_repeat('=', $COL) . "\n";

            // Right-aligned label + value on a single line
            $rLine = function (string $label, string $value) use ($COL): string {
                $total = strlen($label) + strlen($value);
                if ($total >= $COL) return $label . $value . "\n";
                return str_pad($label, $COL - strlen($value)) . $value . "\n";
            };

            // ── Inicializar impresora ─────────────────────────────────
            $tmpEscpos = tempnam(sys_get_temp_dir(), 'ticket_');
            $connector = new FilePrintConnector($tmpEscpos);
            $printer   = new Printer($connector);
            $printer->initialize();

            // ══ CABECERA EMPRESA ══════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($dato_venta->empresa_razon_social . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();

            if ($tieneDesc) {
                $desc = str_replace('\n', "\n", (string)$dato_venta->empresa_descripcion);
                $printer->text($desc . "\n");
            }

            $printer->text($dato_venta->empresa_domiciliofiscal . "\n");
            $printer->text($dato_venta->ubigeo_departamento . '-' . $dato_venta->ubigeo_provincia . '-' . $dato_venta->ubigeo_distrito . "\n");

            if ($tieneTel) {
                if (!empty($dato_venta->empresa_telefono1) && !empty($dato_venta->empresa_telefono2)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1 . '  Telef: ' . $dato_venta->empresa_telefono2;
                } elseif (!empty($dato_venta->empresa_telefono1)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1;
                } else {
                    $telLinea = 'Telef: ' . $dato_venta->empresa_telefono2;
                }
                $printer->text($telLinea . "\n");
            }

            if ($tieneEmail) {
                $printer->text('Email: ' . $dato_venta->empresa_correo . "\n");
            }

            foreach ($sedes as $sede) {
                $sedeTexto = $sede->tienda_nombre;
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $sede->tienda_direccion;
                }
                $printer->text($sedeTexto . "\n");
            }

            $printer->text($SEP_THIN);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text('RUC N° ' . $dato_venta->empresa_ruc . "\n");
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ══ TIPO COMPROBANTE ══════════════════════════════════════
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($tipo_comprobante . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();
            $printer->text($serie_correlativo . "\n");

            if ($esGratuita) {
                $printer->text('*** TRANSFERENCIA A TITULO GRATUITO ***' . "\n");
            }

            $printer->text($SEP_THIN);

            // ══ FECHA / HORA ══════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_pad($fecha_formateada, $COL - strlen($hora_formateada)) . $hora_formateada . "\n");

            // ══ DATOS DEL CLIENTE ═════════════════════════════════════
            $clienteNombre = ($dato_venta->id_tipo_documento != 4)
                ? $dato_venta->cliente_nombre
                : $dato_venta->cliente_razonsocial;

            if (!empty(trim((string)$clienteNombre))) {
                $printer->text('Nomb: ' . $clienteNombre . "\n");
            }
            if (!empty($dato_venta->cliente_direccion)) {
                $printer->text('Direc: ' . $dato_venta->cliente_direccion . "\n");
            }
            if (!empty($documento)) {
                $printer->text($dnni . ': ' . $documento . "\n");
            }

            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text('Condicion de Pago: ' . $condicion_pago . "\n");
            $printer->text('Forma de Pago: ' . $formas_de_pago_mensaje . "\n");

            // ══ DETALLE DE PRODUCTOS ══════════════════════════════════
            $printer->text($SEP_THICK);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text(
                str_pad('Detalle', 26)
                . str_pad('Cant/UM', 10, ' ', STR_PAD_LEFT)
                . str_pad('Precio', 6, ' ', STR_PAD_LEFT)
                . str_pad('Total', 6, ' ', STR_PAD_LEFT) . "\n"
            );
            $printer->selectPrintMode();
            $printer->text($SEP_THIN);

            foreach ($detalle_venta as $f) {
                // Fila 1: código + nombre
                $descripcion = '';
                if (!empty($f->pro_codigo)) {
                    $descripcion = $f->pro_codigo . ' ';
                }
                $descripcion .= $f->venta_detalle_nombre_producto;
                $printer->text(wordwrap($descripcion, $COL, "\n", true) . "\n");

                // Fila 2: cantidad | unidad x factor | precio | total
                $numCantidad = number_format((float)$f->venta_detalle_cantidad, 0, '.', '');
                $unidad      = !empty($f->pres_nombre)
                    ? $f->pres_nombre
                    : (!empty($f->medida_codigo_unidad) ? $f->medida_codigo_unidad : 'Und');
                $factor      = (float)($f->pres_factor ?? 1.0);
                $factorText  = $factor > 1 ? number_format($factor, 0) : $numCantidad;
                $cantTexto   = $unidad . ' x ' . $factorText;
                $precioUnit  = number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', '');
                $totalItem   = number_format(round((float)$f->venta_detalle_importe_total,   2), 2, '.', '');

                $printer->text(
                    str_pad($numCantidad, 5, ' ', STR_PAD_LEFT)
                    . ' '
                    . str_pad($cantTexto, 20)
                    . str_pad($precioUnit, 11, ' ', STR_PAD_LEFT)
                    . str_pad($totalItem, 11, ' ', STR_PAD_LEFT) . "\n"
                );
            }

            $printer->text($SEP_THIN);

            // ══ SUBTOTALES ════════════════════════════════════════════
            if ((float)$dato_venta->venta_totalgratuita > 0) {
                $printer->text($rLine('Op.Grat:', "$sim " . number_format((float)$dato_venta->venta_totalgratuita, 2)));
            }
            $printer->text($rLine('SubTotal:', "$sim " . number_format($subTotal, 2)));
            $printer->text($rLine('I.G.V.:', "$sim " . number_format((float)$dato_venta->venta_totaligv, 2)));
            $printer->text($rLine('ICBPER:', "$sim " . number_format((float)$dato_venta->venta_icbper, 2)));
            $printer->text($rLine('Cp Exon:', "$sim " . number_format((float)$dato_venta->venta_totalexonerada, 2)));

            $printer->text($SEP_THICK);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text($rLine('Imp.Total:', "$sim " . number_format((float)$dato_venta->venta_total, 2)));
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ══ IMPORTE EN LETRAS ═════════════════════════════════════
            $printer->text(wordwrap('Son: ' . $importe_letra, $COL, "\n", true) . "\n");

            // ══ DESGLOSE DE PAGOS ═════════════════════════════════════
            if ($dato_venta->id_formas_pago == 1 && !$esGratuita) {
                foreach ($formas_de_pago as $for) {
                    $printer->text($rLine($for->tipo_pago_nombre . ':', "$sim " . number_format((float)$for->venta_detalle_pago_monto, 2)));
                }
                $printer->text($rLine('Vuelto:', "$sim " . number_format((float)$dato_venta->venta_vuelto, 2)));
            }

            // ══ QR ════════════════════════════════════════════════════
            if ($dato_venta->venta_tipo != '20') {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->qrCode($contenido_qr, Printer::QR_ECLEVEL_M, 5);
            }

            // ══ PTO VENTA / VENDEDOR ══════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $ptoVenta = !empty($dato_venta->tienda_nombre) ? $dato_venta->tienda_nombre : '-';
            $printer->text('Pto.Venta: ' . $ptoVenta . "\n");
            $printer->text('Vendedor: ' . $dato_venta->nombre_users . "\n");

            // ══ PIE ═══════════════════════════════════════════════════
            $printer->text($SEP_THIN);
            $printer->text('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA' . "\n");
            $printer->text('** Una vez salida la mercaderia, no se aceptan cambios ni devoluciones **' . "\n");
            $printer->text('** Gracias por su Compra **' . "\n");

            if ($dato_venta->venta_tipo != '20') {
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $printer->text('CODIGO HASH: ' . $dato_venta->venta_codigo_hash . "\n");
                }
                $printer->text('Consulte validez en: http://e.consulta.sunat.gob.pe/ol-ti-itconsvalcpe/ConsVCpe.htm' . "\n");
                $printer->text('Representacion impresa de comprobante de pago electronico' . "\n");
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            // ── Leer el archivo ESC/POS generado ─────────────────────
            $escposData = file_get_contents($tmpEscpos);
            @unlink($tmpEscpos);

            if ($escposData === false || strlen($escposData) === 0) {
                throw new \Exception('No se pudo leer el archivo ESC/POS temporal.');
            }

            // ── Enviar al agente de impresión en la PC del usuario ────
            $ip_cliente = request()->ip();  // IP de tu PC: 192.168.8.234

            $response = \Illuminate\Support\Facades\Http::timeout(5)->post(
                "http://{$ip_cliente}:8091/imprimir_raw",
                [
                    'token'        => 'mundofantasia2026',
                    'escpos_base64' => base64_encode($escposData),
                ]
            );

            if (!$response->successful()) {
                throw new \Exception('El agente de impresión no respondió. ¿Está abierto el iniciar_agente.bat en tu PC?');
            }

            $result = $response->json();
            if (!($result['ok'] ?? false)) {
                throw new \Exception('Error en el agente: ' . ($result['mensaje'] ?? 'desconocido'));
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    public function imprimir_ticketera_escpos()
    {
        $id_venta = (int) request('venta_id', 0);
        if (!$id_venta) {
            return response()->json(['ok' => false, 'error' => 'ID de venta inválido']);
        }

        try {
            $dato_venta     = $this->venta->listar_venta_x_id_pdf($id_venta);
            $detalle_venta  = $this->venta->listar_venta_detalle_x_id_venta_pdf($id_venta);
            $formas_de_pago = Ventas_detalle_pago::listar_formas_x_idventa($id_venta);

            if (!$dato_venta) {
                return response()->json(['ok' => false, 'error' => 'Venta no encontrada']);
            }

            // ════════════════════════════════════════════════════════════
            // HELPER UTF-8
            // Convierte cualquier string a UTF-8 válido y elimina
            // caracteres de control que mike42/escpos-php rechaza.
            // ════════════════════════════════════════════════════════════
            $u = function (string $s): string {
                $enc = mb_detect_encoding($s, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
                if ($enc && $enc !== 'UTF-8') {
                    $s = mb_convert_encoding($s, 'UTF-8', $enc);
                }
                $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
                $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
                return $s;
            };

            // ── Forma de pago ────────────────────────────────────────────
            $formas_de_pago_mensaje = '';
            foreach ($formas_de_pago as $for) {
                $formas_de_pago_mensaje .= $u($for->tipo_pago_nombre) . ' ';
            }
            $formas_de_pago_mensaje = trim($formas_de_pago_mensaje);

            // ── Fecha y hora ─────────────────────────────────────────────
            $fecha_obj        = \DateTime::createFromFormat('Y-m-d H:i:s', $dato_venta->venta_fecha);
            $fecha_formateada = $fecha_obj->format('d/m/Y');
            $hora_formateada  = $fecha_obj->format('H:i:s');

            // ── Contenido QR ─────────────────────────────────────────────
            $contenido_qr = $dato_venta->empresa_ruc . '|' . $dato_venta->venta_tipo . '|'
                . $dato_venta->venta_serie . '|' . $dato_venta->venta_correlativo
                . '|' . $dato_venta->venta_totaligv . '|' . $dato_venta->venta_total
                . '|' . date('Y-m-d', strtotime($dato_venta->venta_fecha))
                . '|' . $dato_venta->tipodocumento_codigo . '|' . $dato_venta->cliente_numero;

            // ── Tipo de comprobante ──────────────────────────────────────
            $serie_correlativo = $dato_venta->venta_serie . '-'
                . str_pad($dato_venta->venta_correlativo, 8, '0', STR_PAD_LEFT);

            switch ($dato_venta->venta_tipo) {
                case '01':
                    $tipo_comprobante = 'FACTURA ELECTRONICA';
                    $dnni = 'RUC'; $documento = $dato_venta->cliente_numero;
                    break;
                case '03':
                    $tipo_comprobante = 'BOLETA DE VENTA ELECTRONICA';
                    $dnni = 'DNI'; $documento = $dato_venta->cliente_numero;
                    break;
                case '07':
                    $tipo_comprobante = 'NOTA DE CREDITO ELECTRONICA';
                    $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
                    break;
                case '08':
                    $tipo_comprobante = 'NOTA DE DEBITO ELECTRONICA';
                    $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
                    break;
                default:
                    $tipo_comprobante = 'NOTA DE VENTA';
                    $dnni = 'DOCUMENTO'; $documento = $dato_venta->cliente_numero;
                    break;
            }

            // ── Importe en letras ────────────────────────────────────────
            $da         = new NumeroALetras();
            $esGratuita = ((float)$dato_venta->venta_total == 0
                && (float)$dato_venta->venta_totalgratuita > 0);

            $importe_letra = $esGratuita
                ? 'TRANSFERENCIA GRATUITA - Valor ref.: '
                . $da->toInvoice((float)$dato_venta->venta_totalgratuita, '2', 'soles')
                : $da->toInvoice((float)$dato_venta->venta_total, '2', 'soles');

            $condicion_pago = $dato_venta->id_formas_pago == 1 ? 'CONTADO' : 'CREDITO';

            // ── Sedes adicionales ────────────────────────────────────────
            $idTiendaCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $dato_venta->id_caja_numero)
                ->value('id_tienda');

            $sedesQuery = DB::table('tiendas')
                ->where('id_empresa', $dato_venta->id_empresa)
                ->where('tienda_estado', '!=', 0)
                ->whereIn('tienda_tipo', [1, 2]);
            if ($idTiendaCaja) {
                $sedesQuery->where('id_tienda', '!=', $idTiendaCaja);
            }
            $sedes = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();

            // ── Subtotal ─────────────────────────────────────────────────
            $subTotal = (float)$dato_venta->venta_totalgravada
                + (float)$dato_venta->venta_totalexonerada
                + (float)$dato_venta->venta_totalinafecta;

            // ── Símbolo monetario: ASCII puro, sin multibyte ─────────────
            $sim = 'S/.';

            // ── Flags ────────────────────────────────────────────────────
            $tieneDesc  = !empty(trim((string)$dato_venta->empresa_descripcion));
            $tieneTel   = !empty($dato_venta->empresa_telefono1)
                || !empty($dato_venta->empresa_telefono2);
            $tieneEmail = !empty($dato_venta->empresa_correo);

            // ════════════════════════════════════════════════════════════
            // HELPERS DE FORMATO
            // ⬇⬇⬇  ANCHO DE COLUMNA  ⬇⬇⬇
            // Epson TM-m244A imprime a 42 columnas con este papel.
            // Si una línea AÚN se parte en dos, baja a 40.
            // Si sobra mucho espacio a la derecha, sube a 44 o 48.
            // ════════════════════════════════════════════════════════════
            $COL       = 42;
            $SEP_THIN  = str_repeat('-', $COL) . "\n";
            $SEP_THICK = str_repeat('=', $COL) . "\n";

            /**
             * $rLine — alinea label a la izquierda y value a la derecha.
             */
            $rLine = function (string $label, string $value) use ($COL): string {
                $lenLabel = mb_strlen($label, 'UTF-8');
                $lenValue = mb_strlen($value,  'UTF-8');
                if (($lenLabel + $lenValue) >= $COL) {
                    return $label . $value . "\n";
                }
                $byteExtra = strlen($label) - $lenLabel;
                $padTarget = ($COL - $lenValue) + $byteExtra;
                return str_pad($label, $padTarget) . $value . "\n";
            };

            // ════════════════════════════════════════════════════════════
            // INICIALIZAR IMPRESORA
            // ════════════════════════════════════════════════════════════
            $tmpEscpos = tempnam(sys_get_temp_dir(), 'ticket_');
            $connector = new FilePrintConnector($tmpEscpos);
            $printer   = new Printer($connector);
            $printer->initialize();

            // ════════════════════════════════════════════════════════════
            // CABECERA EMPRESA
            // ════════════════════════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($u($dato_venta->empresa_razon_social) . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();

            if ($tieneDesc) {
                $desc = str_replace('\n', "\n", (string)$dato_venta->empresa_descripcion);
                $printer->text(wordwrap($u($desc), $COL, "\n", true) . "\n");
            }

            $printer->text(wordwrap($u($dato_venta->empresa_domiciliofiscal), $COL, "\n", true) . "\n");
            $printer->text(wordwrap(
                    $u($dato_venta->ubigeo_departamento) . '-'
                    . $u($dato_venta->ubigeo_provincia)  . '-'
                    . $u($dato_venta->ubigeo_distrito),
                    $COL, "\n", true
                ) . "\n");

            if ($tieneTel) {
                if (!empty($dato_venta->empresa_telefono1) && !empty($dato_venta->empresa_telefono2)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1
                        . '  Telef: ' . $dato_venta->empresa_telefono2;
                } elseif (!empty($dato_venta->empresa_telefono1)) {
                    $telLinea = 'Cel. ' . $dato_venta->empresa_telefono1;
                } else {
                    $telLinea = 'Telef: ' . $dato_venta->empresa_telefono2;
                }
                $printer->text(wordwrap($u($telLinea), $COL, "\n", true) . "\n");
            }

            if ($tieneEmail) {
                $printer->text(wordwrap('Email: ' . $u($dato_venta->empresa_correo), $COL, "\n", true) . "\n");
            }

            foreach ($sedes as $sede) {
                $sedeTexto = $u($sede->tienda_nombre);
                if (!empty($sede->tienda_direccion)) {
                    $sedeTexto .= ' - ' . $u($sede->tienda_direccion);
                }
                $printer->text(wordwrap($sedeTexto, $COL, "\n", true) . "\n");
            }

            $printer->text($SEP_THIN);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text($u('RUC N° ' . $dato_venta->empresa_ruc) . "\n");
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ════════════════════════════════════════════════════════════
            // TIPO DE COMPROBANTE
            // ════════════════════════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->setTextSize(1, 2);
            $printer->text($u($tipo_comprobante) . "\n");
            $printer->setTextSize(1, 1);
            $printer->selectPrintMode();
            $printer->text($u($serie_correlativo) . "\n");

            if ($esGratuita) {
                $printer->text($u('*** TRANSFERENCIA A TITULO GRATUITO ***') . "\n");
            }

            $printer->text($SEP_THIN);

            // ════════════════════════════════════════════════════════════
            // FECHA / HORA
            // ════════════════════════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(
                str_pad($fecha_formateada, $COL - strlen($hora_formateada))
                . $hora_formateada . "\n"
            );

            // ════════════════════════════════════════════════════════════
            // DATOS DEL CLIENTE
            // ════════════════════════════════════════════════════════════
            $clienteNombre = ($dato_venta->id_tipo_documento != 4)
                ? $dato_venta->cliente_nombre
                : $dato_venta->cliente_razonsocial;

            if (!empty(trim((string)$clienteNombre))) {
                $printer->text(wordwrap('Nomb: ' . $u($clienteNombre), $COL, "\n", true) . "\n");
            }
            if (!empty($dato_venta->cliente_direccion)) {
                $printer->text(wordwrap('Direc: ' . $u($dato_venta->cliente_direccion), $COL, "\n", true) . "\n");
            }
            if (!empty($documento)) {
                $printer->text($u($dnni) . ': ' . $u($documento) . "\n");
            }

            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text($u('Condicion de Pago: ' . $condicion_pago) . "\n");
            $printer->text($u('Forma de Pago: '     . $formas_de_pago_mensaje) . "\n");

            // ════════════════════════════════════════════════════════════
            // DETALLE DE PRODUCTOS
            // Anchos: 22 + 10 + 10 = 42 exacto
            // ════════════════════════════════════════════════════════════
            $printer->text($SEP_THICK);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text(
                str_pad('Detalle', 22)
                . str_pad('P.Unit', 10, ' ', STR_PAD_LEFT)
                . str_pad('Total',  10, ' ', STR_PAD_LEFT) . "\n"
            );
            $printer->selectPrintMode();
            $printer->text($SEP_THIN);

            foreach ($detalle_venta as $f) {
                // Fila 1: código + nombre
                $descripcion = '';
                if (!empty($f->pro_codigo)) {
                    $descripcion = $f->pro_codigo . ' ';
                }
                $descripcion .= $f->venta_detalle_nombre_producto;
                $printer->text(wordwrap($u($descripcion), $COL, "\n", true) . "\n");

                // Fila 2: cant/unidad | precio | total  (22-10-10)
                $numCantidad = number_format((float)$f->venta_detalle_cantidad, 0, '.', '');

                $unidad = !empty($f->pres_nombre)
                    ? $u($f->pres_nombre)
                    : (!empty($f->medida_codigo_unidad) ? $u($f->medida_codigo_unidad) : 'Und');

                $factor     = (float)($f->pres_factor ?? 1.0);
                $factorText = $factor > 1 ? number_format($factor, 0) : $numCantidad;
                $cantTexto  = $unidad . ' x ' . $factorText;

                $precioUnit = number_format(round((float)$f->venta_detalle_precio_unitario, 2), 2, '.', '');
                $totalItem  = number_format(round((float)$f->venta_detalle_importe_total,   2), 2, '.', '');

                // Col 1 (22): cantidad + unidad
                $col1raw = str_pad($numCantidad, 4, ' ', STR_PAD_LEFT) . ' ' . $cantTexto;
                $col1    = str_pad(mb_substr($col1raw, 0, 22), 22);

                // Col 2 (10): precio unitario
                $col2 = str_pad($precioUnit, 10, ' ', STR_PAD_LEFT);

                // Col 3 (10): total ítem
                $col3 = str_pad($totalItem, 10, ' ', STR_PAD_LEFT);

                $printer->text($col1 . $col2 . $col3 . "\n");
            }

            $printer->text($SEP_THIN);

            // ════════════════════════════════════════════════════════════
            // SUBTOTALES
            // ════════════════════════════════════════════════════════════
            if ((float)$dato_venta->venta_totalgratuita > 0) {
                $printer->text($rLine(
                    'Op.Grat:',
                    $sim . ' ' . number_format((float)$dato_venta->venta_totalgratuita, 2)
                ));
            }
            $printer->text($rLine('SubTotal:', $sim . ' ' . number_format($subTotal, 2)));
            $printer->text($rLine('I.G.V.:',   $sim . ' ' . number_format((float)$dato_venta->venta_totaligv,       2)));
            $printer->text($rLine('ICBPER:',   $sim . ' ' . number_format((float)$dato_venta->venta_icbper,         2)));
            $printer->text($rLine('Cp Exon:',  $sim . ' ' . number_format((float)$dato_venta->venta_totalexonerada, 2)));

            $printer->text($SEP_THICK);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
            $printer->text($rLine('Imp.Total:', $sim . ' ' . number_format((float)$dato_venta->venta_total, 2)));
            $printer->selectPrintMode();
            $printer->text($SEP_THICK);

            // ════════════════════════════════════════════════════════════
            // IMPORTE EN LETRAS
            // ════════════════════════════════════════════════════════════
            $printer->text(wordwrap($u('Son: ' . $importe_letra), $COL, "\n", true) . "\n");

            // ════════════════════════════════════════════════════════════
            // DESGLOSE DE PAGOS
            // ════════════════════════════════════════════════════════════
            if ($dato_venta->id_formas_pago == 1 && !$esGratuita) {
                foreach ($formas_de_pago as $for) {
                    $printer->text($rLine(
                        $u($for->tipo_pago_nombre) . ':',
                        $sim . ' ' . number_format((float)$for->venta_detalle_pago_monto, 2)
                    ));
                }
                $printer->text($rLine(
                    'Vuelto:',
                    $sim . ' ' . number_format((float)$dato_venta->venta_vuelto, 2)
                ));
            }

            // ════════════════════════════════════════════════════════════
            // QR
            // ════════════════════════════════════════════════════════════
            if ($dato_venta->venta_tipo != '20') {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->qrCode($contenido_qr, Printer::QR_ECLEVEL_M, 5);
            }

            // ════════════════════════════════════════════════════════════
            // PUNTO DE VENTA / VENDEDOR
            // ════════════════════════════════════════════════════════════
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $ptoVenta = !empty($dato_venta->tienda_nombre) ? $u($dato_venta->tienda_nombre) : '-';
            $printer->text('Pto.Venta: ' . $ptoVenta . "\n");
            $printer->text('Vendedor: '  . $u($dato_venta->nombre_users) . "\n");

            // ════════════════════════════════════════════════════════════
            // PIE
            // ════════════════════════════════════════════════════════════
            $printer->text($SEP_THIN);
            $printer->text(wordwrap(
                    $u('BIENES TRANSFERIDOS EN LA AMAZONIA PARA SER CONSUMIDOS EN LA MISMA'),
                    $COL, "\n", true
                ) . "\n");
            $printer->text(wordwrap(
                    $u('** Una vez salida la mercaderia, no se aceptan cambios ni devoluciones **'),
                    $COL, "\n", true
                ) . "\n");
            $printer->text($u('** Gracias por su Compra **') . "\n");

            if ($dato_venta->venta_tipo != '20') {
                if (!empty($dato_venta->venta_codigo_hash)) {
                    $printer->text(wordwrap($u('CODIGO HASH: ' . $dato_venta->venta_codigo_hash), $COL, "\n", true) . "\n");
                }
                $printer->text(wordwrap(
                        'Consulte validez en: http://e.consulta.sunat.gob.pe/ol-ti-itconsvalcpe/ConsVCpe.htm',
                        $COL, "\n", true
                    ) . "\n");
                $printer->text(wordwrap(
                        $u('Representacion impresa de comprobante de pago electronico'),
                        $COL, "\n", true
                    ) . "\n");
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            // ════════════════════════════════════════════════════════════
            // ENVIAR AL AGENTE DE IMPRESIÓN
            // ════════════════════════════════════════════════════════════
            $escposData = file_get_contents($tmpEscpos);
            @unlink($tmpEscpos);

            if ($escposData === false || strlen($escposData) === 0) {
                throw new \Exception('No se pudo leer el archivo ESC/POS temporal.');
            }

            $ip_cliente = request()->ip();

            $response = \Illuminate\Support\Facades\Http::timeout(5)->post(
                "http://{$ip_cliente}:8091/imprimir_raw",
                [
                    'token'         => 'mundofantasia2026',
                    'escpos_base64' => base64_encode($escposData),
                ]
            );

            if (!$response->successful()) {
                throw new \Exception(
                    'El agente de impresion no respondio. '
                    . 'Verifica que iniciar_agente.bat este abierto en tu PC.'
                );
            }

            $result = $response->json();
            if (!($result['ok'] ?? false)) {
                throw new \Exception('Error en el agente: ' . ($result['mensaje'] ?? 'desconocido'));
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function enviarComprobanteporCorreo( Request $request){
        // Código de error general
        $result = 2;
        // Mensaje a devolver en caso de hacer consulta por app
        $message = 'OK';
        try {

            $validator = Validator::make($request->all(), [
                'correoDestino' => 'required|email',
                'id_venta'=>'required'// Ajusta el nombre del parámetro según tus necesidades
            ]);

            if ($validator->fails()) {
                $result = 6;
                $message = "Integridad de datos fallida. Alguno(s) de los parámetros se están enviando de manera incorrecta";
            } else {

                $venta = $this->venta->listar_venta_x_id($request->id_venta);
                if ($venta){
                    $empresa = DB::table('empresa')->where('id_empresa','=',1)->first();
                    $comprobante = $this->imprimir_ticket_pdf_local($request->id_venta);
                    $rutaXML = null;
                    $rutaCDR = null;
                    if (file_exists($venta->venta_rutaXML)){
                        $rutaXML = $venta->venta_rutaXML;
                    }
                    if (file_exists($venta->venta_rutaCDR)){
                        $rutaCDR = $venta->venta_rutaCDR;
                    }
                    $correo_corpo = $empresa->empresa_correo;
                    $envio = Mail::to(strtolower($request->correoDestino))->send(new ComprobanteCorreo($comprobante,$correo_corpo,$rutaXML,$rutaCDR));
                    if($envio){
                        $result = 1;
                        $message = "Envio del comprobante fue existo";
                    }else{
                        $result = 2;
                        $message = "Ocurrio un erro al envio del comprobante";
                    }
                }else{
                    $result = 2;
                    $message = "No se encontro información de la venta.";
                }
            }

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
        return response()->json(["result" => ["code" => $result, "message" => $message]]);

    }

    public function imprimirPdfReporteHistorialNotasVenta(Request $request)
    {
        try {
            $desde      = $request->desde      ?? null;
            $hasta      = $request->hasta      ?? null;
            $idCliente  = $request->cliente    ?? null;
            $idSucursal = $request->idSucursal ?? null;
            $idEmpresa  = $request->idEmpresa  ?? null;

            // ── Misma consulta que el Livewire ────────────────────
            $query = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'mo.simbolo',
                    'u.nombre_users',
                    'c.id_tipo_documento',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_numero'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo',  'v.id_moneda',   '=', 'mo.id_moneda')
                ->join('users as u',     'v.id_users',    '=', 'u.id_users')
                ->where('v.venta_tipo', '=', '20');

            if ($desde && $hasta) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
            }
            if ($idCliente) {
                $query->where('v.id_clientes', $idCliente);
            }
            if ($idSucursal) {
                $query->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa) {
                $query->whereIn('v.id_sucursal', function ($sub) use ($idEmpresa) {
                    $sub->select('id_sucursal')->from('sucursals')
                        ->where('id_empresa', $idEmpresa)
                        ->where('sucursal_estado', 1)
                        ->whereNull('deleted_at');
                });
            }

            $registros = $query->orderByDesc('v.venta_fecha')->get();

            // ── Datos para el encabezado ──────────────────────────
            $nombreCliente = 'TODOS';
            if ($idCliente) {
                $cli = DB::table('clientes')->where('id_clientes', $idCliente)->first();
                if ($cli) {
                    $nombreCliente = $cli->cliente_razonsocial ?? $cli->cliente_nombre;
                }
            }

            $fechaDesde = $desde ? date('d/m/Y', strtotime($desde)) : '-';
            $fechaHasta = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';

            // ── FPDF — A4 Vertical ────────────────────────────────
            // Ancho útil: 210mm - 10mm(izq) - 10mm(der) = 190mm
            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();

            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Notas de Venta'), 0, 1, 'C', 0);
            $pdf->Ln(4);
            $pdf->Cell(180, 0, '', 'T', 1, 'R');
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(20, 4, utf8_decode('Cliente: '), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(100, 4, utf8_decode($nombreCliente), 0, 1, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(20, 4, utf8_decode('Desde: '), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($fechaDesde), 0, 0, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(20, 4, utf8_decode('Hasta: '), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($fechaHasta), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1, 'R');
            $pdf->Ln(3);

            // ── Encabezado de tabla ───────────────────────────────
            // Total anchos = 8+18+22+20+42+30+22+18+10 = 190mm
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(8,  10, utf8_decode('N°'),                 1, 0, 'C', 1);
            $pdf->Cell(18, 10, utf8_decode('Fecha'),              1, 0, 'C', 1);
            $pdf->Cell(22, 10, utf8_decode('Serie-Correlativo'),  1, 0, 'C', 1);
            $pdf->Cell(20, 10, utf8_decode('N° Documento'),       1, 0, 'C', 1);
            $pdf->Cell(42, 10, utf8_decode('Cliente'),            1, 0, 'C', 1);
            $pdf->Cell(30, 10, utf8_decode('Registrado Por'),     1, 0, 'C', 1);
            $pdf->Cell(22, 10, utf8_decode('Total'),              1, 0, 'C', 1);
            $pdf->Cell(18, 10, utf8_decode('Estado'),             1, 1, 'C', 1);

            $pdf->SetWidths([8, 18, 22, 20, 42, 30, 22, 18]);

            // ── Filas de datos ────────────────────────────────────
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetTextColor(0, 0, 0);

            $numero = 1;
            foreach ($registros as $reg) {

                $fechaEmision     = !empty($reg->venta_fecha)
                    ? date('d/m/Y H:i', strtotime($reg->venta_fecha))
                    : '';
                $serieCorrelativo = $reg->venta_serie . '-' . $reg->venta_correlativo;
                $cliente          = $reg->id_tipo_documento == 4
                    ? $reg->cliente_razonsocial
                    : $reg->cliente_nombre;
                $estado           = $reg->anulado_sunat == 1 ? 'Anulado' : 'Vigente';
                $total            = $reg->simbolo . number_format($reg->venta_total, 2);

                // Resaltar anulados en rojo claro (igual que la vista: #efa6ad)
                if ($reg->anulado_sunat == 1) {
                    $pdf->SetFillColor(239, 166, 173);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                $pdf->Row([
                    $numero,
                    utf8_decode($fechaEmision),
                    utf8_decode($serieCorrelativo),
                    utf8_decode($reg->cliente_numero),
                    utf8_decode($cliente),
                    utf8_decode($reg->nombre_users),
                    utf8_decode($total),
                    utf8_decode($estado),
                ]);

                $numero++;
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Notas_Venta_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }
    public function imprimirExcelHistorialNotasDeVenta(Request $request)
    {
        try {
            $desde      = $request->desde      ?? null;
            $hasta      = $request->hasta      ?? null;
            $idCliente  = $request->cliente    ?? null;
            $idSucursal = $request->idSucursal ?? null;
            $idEmpresa  = $request->idEmpresa  ?? null;

            // ── Misma consulta que el Livewire ────────────────────
            $query = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'mo.simbolo',
                    'u.nombre_users',
                    'c.id_tipo_documento',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_numero'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo',  'v.id_moneda',   '=', 'mo.id_moneda')
                ->join('users as u',     'v.id_users',    '=', 'u.id_users')
                ->where('v.venta_tipo', '=', '20');

            if ($desde && $hasta) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
            }
            if ($idCliente) {
                $query->where('v.id_clientes', $idCliente);
            }
            if ($idSucursal) {
                $query->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa) {
                $query->whereIn('v.id_sucursal', function ($sub) use ($idEmpresa) {
                    $sub->select('id_sucursal')->from('sucursals')
                        ->where('id_empresa', $idEmpresa)
                        ->where('sucursal_estado', 1)
                        ->whereNull('deleted_at');
                });
            }

            $registros = $query->orderByDesc('v.venta_fecha')->get();

            // ── Datos para el encabezado ──────────────────────────
            $nombreCliente = 'TODOS';
            if ($idCliente) {
                $cli = DB::table('clientes')->where('id_clientes', $idCliente)->first();
                if ($cli) {
                    $nombreCliente = $cli->cliente_razonsocial ?? $cli->cliente_nombre;
                }
            }

            $fechaDesde = $desde ? date('d/m/Y', strtotime($desde)) : '-';
            $fechaHasta = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';

            // ── PhpSpreadsheet ────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Notas de Venta');

            // Estilos reutilizables
            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEtiqueta = [
                'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            ];
            $estiloValor = [
                'font' => ['size' => 9, 'name' => 'Arial'],
            ];
            $estiloEncabezadoTabla = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFFFFFFF'],
                ]],
            ];

            // ── Fila 1: Título ────────────────────────────────────
            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'Reporte de Notas de Venta');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // ── Filas 2-3: Filtros aplicados ──────────────────────
            $filtros = [
                ['Cliente:', $nombreCliente, 'Desde:', $fechaDesde],
                ['Hasta:',   $fechaHasta,    'Total registros:', $registros->count()],
            ];

            $fila = 2;
            foreach ($filtros as $row) {
                $sheet->setCellValue("A{$fila}", $row[0]);
                $sheet->setCellValue("B{$fila}", $row[1]);
                $sheet->setCellValue("E{$fila}", $row[2]);
                $sheet->setCellValue("F{$fila}", $row[3]);
                $sheet->getStyle("A{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->getStyle("B{$fila}")->applyFromArray($estiloValor);
                $sheet->getStyle("E{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->getStyle("F{$fila}")->applyFromArray($estiloValor);
                $fila++;
            }

            // ── Fila 5: Encabezados de la tabla ───────────────────
            $filaEncabezado = 5;
            $encabezados    = ['#', 'Fecha de Emisión', 'Serie-Correlativo', 'N° Documento', 'Cliente', 'Registrado Por', 'Total', 'Estado'];
            $columnas       = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

            foreach ($columnas as $i => $col) {
                $sheet->setCellValue("{$col}{$filaEncabezado}", $encabezados[$i]);
            }
            $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->applyFromArray($estiloEncabezadoTabla);
            $sheet->getRowDimension($filaEncabezado)->setRowHeight(24);

            // ── Filas de datos ─────────────────────────────────────
            $filaData = 6;
            $numero   = 1;

            foreach ($registros as $reg) {
                $fechaEmision     = !empty($reg->venta_fecha)
                    ? date('d/m/Y H:i:s', strtotime($reg->venta_fecha))
                    : '';
                $serieCorrelativo = $reg->venta_serie . '-' . $reg->venta_correlativo;
                $cliente          = $reg->id_tipo_documento == 4
                    ? $reg->cliente_razonsocial
                    : $reg->cliente_nombre;
                $estado           = $reg->anulado_sunat == 1 ? 'Anulado' : 'Vigente';
                $total            = $reg->simbolo . number_format($reg->venta_total, 2);

                $sheet->setCellValue("A{$filaData}", $numero);
                $sheet->setCellValue("B{$filaData}", $fechaEmision);
                $sheet->setCellValue("C{$filaData}", $serieCorrelativo);
                $sheet->setCellValue("D{$filaData}", $reg->cliente_numero);
                $sheet->setCellValue("E{$filaData}", $cliente);
                $sheet->setCellValue("F{$filaData}", $reg->nombre_users);
                $sheet->setCellValue("G{$filaData}", $total);
                $sheet->setCellValue("H{$filaData}", $estado);

                // Anulados con fondo rojo claro (igual que la vista)
                if ($reg->anulado_sunat == 1) {
                    $sheet->getStyle("A{$filaData}:H{$filaData}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFefa6ad']],
                    ]);
                } elseif ($filaData % 2 == 0) {
                    // Fila alternada gris claro
                    $sheet->getStyle("A{$filaData}:H{$filaData}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    ]);
                }

                // Borde y alineación vertical en todas las filas
                $sheet->getStyle("A{$filaData}:H{$filaData}")->applyFromArray([
                    'borders'   => ['allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFD0D0D0'],
                    ]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $filaData++;
                $numero++;
            }

            // ── Ancho de columnas ──────────────────────────────────
            $anchos = ['A' => 5, 'B' => 20, 'C' => 18, 'D' => 14, 'E' => 35, 'F' => 22, 'G' => 13, 'H' => 10];
            foreach ($anchos as $col => $ancho) {
                $sheet->getColumnDimension($col)->setWidth($ancho);
            }

            // ── Descargar ──────────────────────────────────────────
            $nombreArchivo = 'Notas_Venta_' . date('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el Excel.');
        }
    }
    public function clientes()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("clientes");
            return view('gestionventas.clientes',compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
        }
    }

    public function pedidos()
    {
        abort_if(!auth()->user()->can('pedidos.listar'), 403);
        $opciones = $this->submenu->optiones_por_vista('pedidos');
        return view('gestion-ventas.pedidos', compact('opciones'));
    }

    public function caja_pedidos()
    {
        abort_if(!auth()->user()->can('caja_pedidos.listar'), 403);
        $opciones = $this->submenu->optiones_por_vista('caja_pedidos');
        return view('gestion-ventas.caja', compact('opciones'));
    }

    public function transferencia_gratuita()
    {
        abort_if(!auth()->user()->can('transferencia_gratuita.listar'), 403);
        $opciones = $this->submenu->optiones_por_vista('transferencia_gratuita');
        return view('gestion-ventas.transferencia-gratuita', compact('opciones'));
    }

    public function despacho()
    {
        abort_if(!auth()->user()->can('despacho.listar'), 403);
        $opciones = $this->submenu->optiones_por_vista('despacho');
        return view('gestion-ventas.despacho', compact('opciones'));
    }

    public function imprimir_ticket_pedido(Request $request)
    {
        $id         = (int) $request->get('data');
        $id_pedido  = $id;

        if (!$id_pedido) {
            abort(404);
        }

        $pedido = DB::table('pedidos as p')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'p.id_tienda')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'p.id_empresa')
            ->where('p.id_pedido', $id_pedido)
            ->select(
                'p.*',
                't.tienda_nombre',
                'e.empresa_razon_social',
                'e.empresa_nombrecomercial',
                'e.empresa_domiciliofiscal',
                'e.empresa_ruc',
                'e.empresa_telefono1',
                'e.empresa_foto_ticket'
            )
            ->first();

        if (!$pedido) {
            abort(404);
        }

        $detalle = DB::table('pedidos_detalle as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->where('pd.id_pedido', $id_pedido)
            ->where('pd.pedido_deta_estado', 1)
            ->select('pd.*', 'p.pro_codigo')
            ->get();

        $empresa_nombre = $pedido->empresa_nombrecomercial ?: $pedido->empresa_razon_social;

        // ====== CONFIG TICKET 80mm ======
        $ticketW  = 80;
        $left     = 5;
        $right    = 5;
        $usableW  = $ticketW - $left - $right;
        $hLine    = 4;

        $hHeader  = 55;
        $hCliente = 25;
        $hTabla   = 14;
        $hTotales = 20;
        $hFooter  = 15;
        $hExtra   = 10;

        // Calcular altura dinámica
        $tmp = new PDFBufeo('P', 'mm', [80, 300]);
        $tmp->SetFont('Helvetica', '', 7);
        $wItem = 40;
        $hDetalle = 0;
        foreach ($detalle as $f) {
            $txt    = utf8_decode($f->pedido_deta_nombre);
            $lineas = $tmp->NbLines($wItem, $txt);
            $hDetalle += max(1, $lineas) * $hLine;
        }

        $altura_total = $hHeader + $hCliente + $hTabla + $hDetalle + $hTotales + $hFooter + $hExtra;
        $altura_total = max(150, min($altura_total, 3000));

        $pdf = new PDFBufeo('P', 'mm', [$ticketW, $altura_total]);
        $pdf->SetMargins($left, 5, $right);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Logotipo
        if ($pedido->empresa_foto_ticket && file_exists($pedido->empresa_foto_ticket)) {
            $pdf->Image($pedido->empresa_foto_ticket, config('services.pdf.izq_logo_ticket', 20), 5, 20, 20);
        }
        $pdf->Ln(22);

        // Nombre empresa
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell($usableW, 4, utf8_decode($empresa_nombre), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($usableW, 4, "RUC {$pedido->empresa_ruc}", 0, 1, 'C');
        $pdf->Cell($usableW, 4, utf8_decode($pedido->empresa_domiciliofiscal ?? ''), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell($usableW, 6, 'PEDIDO', 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($usableW, 5, $pedido->pedido_numero, 0, 1, 'C');

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($usableW, 4, date('d/m/Y H:i:s', strtotime($pedido->created_at)), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->Cell($usableW, 0, '', 'T', 1, 'C');
        $pdf->Ln(2);

        // Datos del cliente
        $pdf->SetFont('Helvetica', '', 7);
        if ($pedido->pedido_cliente_nombre) {
            $pdf->MultiCell($usableW, 4, utf8_decode("Cliente: {$pedido->pedido_cliente_nombre}"), 0, 'L');
        }
        if ($pedido->pedido_cliente_doc) {
            $pdf->Cell($usableW, 4, "Doc: {$pedido->pedido_cliente_doc}", 0, 1, 'L');
        }
        if ($pedido->tienda_nombre) {
            $pdf->Cell($usableW, 4, utf8_decode("Tienda: {$pedido->tienda_nombre}"), 0, 1, 'L');
        }
        if ($pedido->pedido_observacion) {
            $pdf->MultiCell($usableW, 4, utf8_decode("Obs: {$pedido->pedido_observacion}"), 0, 'L');
        }

        $pdf->Ln(2);
        $pdf->Cell($usableW, 0, '', 'T', 1, 'C');
        $pdf->Ln(2);

        // Encabezado tabla
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(40, 5, utf8_decode('Producto'), 1, 0, 'C');
        $pdf->Cell(10, 5, 'Cant', 1, 0, 'C');
        $pdf->Cell(10, 5, 'Precio', 1, 0, 'C');
        $pdf->Cell(10, 5, 'Total', 1, 1, 'C');

        // Productos
        $total_pedido = 0;
        $pdf->SetWidths([40, 10, 10, 10]);
        foreach ($detalle as $f) {
            $subtotal = (float) $f->pedido_deta_precio * (float) $f->pedido_deta_cantidad;
            $total_pedido += $subtotal;
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Row([
                utf8_decode($f->pedido_deta_nombre),
                number_format($f->pedido_deta_cantidad, 2),
                number_format($f->pedido_deta_precio, 2),
                number_format($subtotal, 2),
            ]);
        }

        $pdf->Ln(2);
        $pdf->Cell($usableW, 0, '', 'T', 1, 'C');
        $pdf->Ln(2);

        // Total
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($usableW, 5, 'TOTAL: S/ ' . number_format($total_pedido, 2), 0, 1, 'R');

        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($usableW, 4, utf8_decode('Gracias por su preferencia'), 0, 1, 'C');

        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->Cell($usableW, 4, utf8_decode('Sistema Aquiles ERP | Bufeo TEC'), 0, 1, 'C');

        $pdf->Output('I', "Pedido-{$pedido->pedido_numero}.pdf");
        exit;
    }

    // ── Exportar clientes a Excel ─────────────────────────────
    public function exportarClientesExcel(Request $request)
    {
        try {
            abort_if(!auth()->user()->can('gestion_de_clientes.listar'), 403);

            $buscar       = $request->buscar       ?? '';
            $filtroEmpresa = (int) ($request->filtroEmpresa ?? 0);

            $cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
            $esSuperAdmin = $cachedRoleId === 1;
            $esAdmin      = $cachedRoleId === 2;

            $query = DB::table('clientes as c')
                ->select(
                    'c.id_clientes',
                    'td.tipo_documento_identidad_abr',
                    'c.cliente_numero',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_telefono',
                    'c.cliente_direccion',
                    'c.cliente_fecha',
                    DB::raw("COALESCE(e.empresa_nombrecomercial, e.empresa_razon_social) as empresa_nombre")
                )
                ->join('tipo_documento as td', 'td.id_tipo_documento', '=', 'c.id_tipo_documento')
                ->leftJoin('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
                ->where('c.cliente_estado', 1)
                ->where(function ($q) use ($buscar) {
                    $q->where('c.cliente_nombre',       'like', "%{$buscar}%")
                      ->orWhere('c.cliente_razonsocial', 'like', "%{$buscar}%")
                      ->orWhere('c.cliente_numero',      'like', "%{$buscar}%")
                      ->orWhere('c.cliente_telefono',    'like', "%{$buscar}%");
                });

            if ($esSuperAdmin) {
                if ($filtroEmpresa > 0) {
                    $query->where('c.id_empresa', $filtroEmpresa);
                }
            } elseif ($esAdmin) {
                $empresaId = DB::table('user_tienda as ut')
                    ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
                    ->where('ut.id_users', auth()->user()->id_users)
                    ->value('t.id_empresa');
                if ($empresaId) {
                    $query->where('c.id_empresa', $empresaId);
                }
            }

            $registros = $query->orderBy('c.cliente_nombre')->get();

            // ── PhpSpreadsheet ─────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Clientes');

            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEtiqueta = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial']];
            $estiloValor    = ['font' => ['size' => 9, 'name' => 'Arial']];
            $estiloEncabezado = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloFila = [
                'font'    => ['size' => 9, 'name' => 'Arial'],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
            ];

            // Fila 1: Título
            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'Reporte de Clientes');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // Filas 2-3: Filtros
            $sheet->setCellValue('A2', 'Búsqueda:');
            $sheet->setCellValue('B2', $buscar ?: 'Todos');
            $sheet->setCellValue('E2', 'Total registros:');
            $sheet->setCellValue('F2', $registros->count());
            $sheet->setCellValue('A3', 'Generado:');
            $sheet->setCellValue('B3', now()->format('d/m/Y H:i'));
            $sheet->getStyle('A2:A3')->applyFromArray($estiloEtiqueta);
            $sheet->getStyle('B2:B3')->applyFromArray($estiloValor);
            $sheet->getStyle('E2')->applyFromArray($estiloEtiqueta);
            $sheet->getStyle('F2')->applyFromArray($estiloValor);

            // Fila 5: Encabezados
            $encabezados = ['#', 'Tipo Doc.', 'N° Documento', 'Nombre / Razón Social', 'Teléfono', 'Dirección', 'Empresa', 'Fecha Registro'];
            $columnas    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            foreach ($columnas as $i => $col) {
                $sheet->setCellValue("{$col}5", $encabezados[$i]);
            }
            $sheet->getStyle('A5:H5')->applyFromArray($estiloEncabezado);
            $sheet->getRowDimension(5)->setRowHeight(18);

            // Anchos de columna
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(10);
            $sheet->getColumnDimension('C')->setWidth(14);
            $sheet->getColumnDimension('D')->setWidth(36);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(36);
            $sheet->getColumnDimension('G')->setWidth(28);
            $sheet->getColumnDimension('H')->setWidth(16);

            // Filas de datos
            $fila = 6;
            foreach ($registros as $i => $r) {
                $sheet->setCellValue("A{$fila}", $i + 1);
                $sheet->setCellValue("B{$fila}", $r->tipo_documento_identidad_abr);
                $sheet->setCellValue("C{$fila}", $r->cliente_numero);
                $sheet->setCellValue("D{$fila}", $r->cliente_razonsocial ?: $r->cliente_nombre);
                $sheet->setCellValue("E{$fila}", $r->cliente_telefono ?? '');
                $sheet->setCellValue("F{$fila}", $r->cliente_direccion ?? '');
                $sheet->setCellValue("G{$fila}", $r->empresa_nombre ?? '');
                $sheet->setCellValue("H{$fila}", $r->cliente_fecha ? date('d/m/Y', strtotime($r->cliente_fecha)) : '');

                $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloFila);
                if ($fila % 2 === 0) {
                    $sheet->getStyle("A{$fila}:H{$fila}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8F9FA');
                }
                $fila++;
            }

            $nombreArchivo = 'Clientes_' . now()->format('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('Gestionventas.clientes')->with('error', 'Ocurrió un error al generar el Excel.');
        }
    }
}
