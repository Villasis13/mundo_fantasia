<?php

namespace App\Http\Controllers;

use App\Models\apiFacturacion;
use App\Models\Opciones;
use App\Models\PDFBufeo;
use App\Models\Productos;
use App\Models\Serie;
use App\Models\User;
use App\Service\FacturacionService;
use Illuminate\Support\Facades\Validator;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Envio_resumen;
use App\Models\Envio_resumen_detalle;
use App\Models\GeneradorXML;
use App\Models\General;
use App\Models\Logs;
use App\Models\Submenu;
use App\Models\Tipo_documento;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use App\Models\Tipo_pago;
use App\Models\Venta_detalle;
use App\Models\Ventas;
use App\Models\Ventas_anulado;
use App\Models\Ventas_detalle_pago;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Luecano\NumeroALetras\NumeroALetras;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
class FacturacionController extends Controller
{
    private $logs;
    private $general;
    private $usuarios;
    private $empresas;
    private $opciones;
    private $submenu;
    private $caja;
    private $serie;
    private $tipos_de_pago;
    private $tipo_documento;
    private $ventas;
    private $cliente;

    public function __construct()
    {
        $this->logs = new Logs();
        $this->empresas = new Empresa();
        $this->submenu = new Submenu();
        $this->serie = new Serie();
        $this->ventas = new Ventas();
        $this->cliente = new \App\Models\Cliente();
    }
    public function alertas_sunat()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("alertas_sunat");
            return view('facturacion/alertas_sunat', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error Al Mostrar Contenido. Redireccionando Al Inicio'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function pendiente_declarar(Request $request)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("pendiente_declarar");
            return view('facturacion/pendientes_declarar', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error Al Mostrar Contenido. Redireccionando Al Inicio'); window.location.href = '" . route('admin') . "';</script>";
        }
    }
    public function historial_envios(Request $request)
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("historial_envios");
            return view('facturacion/historial_envios',compact('opciones'));
        } catch (\Exception $e) {
            echo "<script language=\"javascript\">
                    alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                </script>";
        }
    }
    public function detalle_resumen(Request $request,$id)
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("detalle_resumen");
            $resumen = DB::table('envio_resumen')->where('id_envio_resumen','=',$id)->first();
            $detalle = DB::table('envio_resumen_detalle as er')
                ->join('ventas as v','er.id_venta','=','v.id_venta')
                ->where('er.id_envio_resumen','=',$id)->get();
            foreach ($detalle as $deta){
                $deta->ven = DB::table('ventas as v')
                    ->join('clientes as c','v.id_clientes','=','c.id_clientes')
                    ->join('monedas as m','v.id_moneda','=','m.id_moneda')
                    ->join('users as u' ,'v.id_users','=','u.id_users')
                    ->where('v.id_venta','=',$deta->id_venta)->first();
            }

            return view('facturacion/resumen_detalle',compact('opciones','resumen','detalle'));
        } catch (\Exception $e) {
           $this->logs->insertarLog($e);

            echo "<script language=\"javascript\">
                    alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                </script>";
        }
    }
    public function generar_nota(Request $request , $id)
    {
        try {
            $id_venta = (int)$id;
            if($id_venta){
                $opciones = $this->submenu->optiones_por_vista("generar_nota");

                return view('facturacion/generar_nota',compact('opciones','id_venta'));
            }

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script language=\"javascript\">
                    alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                </script>";
        }
    }
    public function crear_xml_enviar_sunat(Request $request)
    {
        $resultado = (new FacturacionService())->enviarComprobante((int) $request->id_venta);
        return response()->json(["result" => $resultado]);
    }

    public function crear_enviar_resumen_sunat(Request $request)
    {
        $resultado = (new FacturacionService())->enviarResumenDiario(
            $request->input('fecha'),
            (int) $request->input('id_empresa'),
            (int) $request->input('id_sucursal', 0)
        );
        return response()->json(["result" => $resultado]);
    }
    private function obtenerDatosReporteSunat(Request $request): array
    {
        $tipoVenta   = $request->tipo_venta   ?? '0';
        $fechaInicio = $request->fecha_inicio ?? null;
        $fechaFinal  = $request->fecha_final  ?? null;
        $idEmpresa   = (int) ($request->id_empresa  ?? 0);
        $idSucursal  = (int) ($request->id_sucursal ?? 0);

        $query = DB::table('ventas as v')
            ->select(
                'v.*',
                'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_nombre',
                'c.cliente_razonsocial', 'mo.simbolo', 'u.nombre_users'
            )
            ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
            ->join('monedas as mo', 'v.id_moneda',   '=', 'mo.id_moneda')
            ->join('users as u',    'v.id_users',    '=', 'u.id_users')
            ->where('v.venta_estado_sunat', '=', 1);

        // ── Filtro tipo de venta ──────────────────────────────
        if ($tipoVenta !== '0') {
            $query->where('v.venta_tipo', $tipoVenta);
        } else {
            $query->where('v.venta_tipo', '<>', '20');
        }

        // ── Filtro de fechas ──────────────────────────────────
        if ($fechaInicio && $fechaFinal) {
            $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$fechaInicio, $fechaFinal]);
        }

        // ── Filtro empresa / sucursal ─────────────────────────
        // Se respeta la misma jerarquía que el componente Livewire:
        // sucursal tiene prioridad sobre empresa cuando ambos vienen.
        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa > 0) {
            $query->where('v.id_empresa', $idEmpresa);
        }

        $ventas = $query->orderBy('v.venta_fecha', 'asc')->get();

        // ── Adjuntar resumen de envío ─────────────────────────
        foreach ($ventas as $v) {
            $v->resumen = DB::table('envio_resumen_detalle as er')
                ->join('ventas as v2', 'er.id_venta', '=', 'v2.id_venta')
                ->where('er.id_venta', '=', $v->id_venta)
                ->first();
        }

        // ── Resumen de montos por tipo de comprobante ─────────
        $resumen = [
            'BOLETA'          => ['total' => 0, 'cantidad' => 0],
            'FACTURA'         => ['total' => 0, 'cantidad' => 0],
            'NOTA DE CRÉDITO' => ['total' => 0, 'cantidad' => 0],
            'NOTA DE DÉBITO'  => ['total' => 0, 'cantidad' => 0],
        ];

        foreach ($ventas as $v) {
            $tipo = match($v->venta_tipo) {
                '03' => 'BOLETA',
                '01' => 'FACTURA',
                '07' => 'NOTA DE CRÉDITO',
                '08' => 'NOTA DE DÉBITO',
                default => null,
            };
            if ($tipo) {
                $resumen[$tipo]['total']    += $v->venta_total;
                $resumen[$tipo]['cantidad'] += 1;
            }
        }

        $tipoLabel = match($tipoVenta) {
            '03' => 'BOLETA',
            '01' => 'FACTURA',
            '07' => 'NOTA DE CRÉDITO',
            '08' => 'NOTA DE DÉBITO',
            default => 'TODOS',
        };

        // ── Info de empresa/sucursal para cabeceras del reporte ──
        $infoEmpresa  = $idEmpresa  > 0 ? DB::table('empresa')->where('id_empresa',  $idEmpresa)->first()  : null;
        $infoSucursal = $idSucursal > 0 ? DB::table('sucursals')->where('id_sucursal', $idSucursal)->first() : null;

        return compact(
            'ventas', 'resumen', 'tipoVenta', 'tipoLabel',
            'fechaInicio', 'fechaFinal',
            'infoEmpresa', 'infoSucursal'
        );
    }
    public function imprimirPdfHistorialEnvios(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteSunat($request);

            $fechaDesde = $d['fechaInicio'] ? date('d/m/Y', strtotime($d['fechaInicio'])) : '-';
            $fechaHasta = $d['fechaFinal']  ? date('d/m/Y', strtotime($d['fechaFinal']))  : '-';

            // ── FPDF — A4 Vertical — ancho útil 180mm ────────────
            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Comprobantes Emitidos a SUNAT'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(30, 4, utf8_decode('Tipo Comprobante:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(46, 4, utf8_decode($d['tipoLabel']), 0, 0, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Desde:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($fechaDesde), 0, 0, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Hasta:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($fechaHasta), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // ── Encabezado de tabla ───────────────────────────────
            // Anchos: 8+20+16+18+22+20+36+16+14+10 = 180mm
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(8,  8, utf8_decode('N°'),           1, 0, 'C', 1);
            $pdf->Cell(20, 8, utf8_decode('Fecha'),        1, 0, 'C', 1);
            $pdf->Cell(16, 8, utf8_decode('Tipo Envío'),   1, 0, 'C', 1);
            $pdf->Cell(18, 8, utf8_decode('Tipo Comp.'),   1, 0, 'C', 1);
            $pdf->Cell(22, 8, utf8_decode('Serie-Núm.'),   1, 0, 'C', 1);
            $pdf->Cell(20, 8, utf8_decode('N° Documento'), 1, 0, 'C', 1);
            $pdf->Cell(36, 8, utf8_decode('Cliente'),      1, 0, 'C', 1);
            $pdf->Cell(16, 8, utf8_decode('F. Pago'),      1, 0, 'C', 1);
            $pdf->Cell(14, 8, utf8_decode('Total'),        1, 0, 'C', 1);
            $pdf->Cell(10, 8, utf8_decode('Estado'),       1, 1, 'C', 1);

            $pdf->SetWidths([8, 20, 16, 18, 22, 20, 36, 16, 14, 10]);

            // ── Filas de datos ────────────────────────────────────
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetTextColor(0, 0, 0);

            $n = 1;
            foreach ($d['ventas'] as $reg) {
                $tipoComp  = match($reg->venta_tipo) {
                    '03' => 'BOLETA', '01' => 'FACTURA',
                    '07' => 'N. CRÉDITO', '08' => 'N. DÉBITO', default => '--'
                };
                $tipoEnvio = $reg->venta_tipo_envio == 1 ? 'DIRECTO' : 'RES. DIARIO';
                $cliente   = $reg->id_tipo_documento == 4 ? $reg->cliente_razonsocial : $reg->cliente_nombre;
                $formaPago = $reg->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO';
                $estado    = $reg->anulado_sunat == 1 ? 'ANULADO' : 'VIGENTE';

                if ($reg->anulado_sunat == 1) {
                    $pdf->SetFillColor(239, 166, 173);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                $simboloVenta = $reg->venta_tipo == '07' ? '-'.$reg->simbolo :  $reg->simbolo;

                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y H:i', strtotime($reg->venta_fecha))),
                    utf8_decode($tipoEnvio),
                    utf8_decode($tipoComp),
                    utf8_decode($reg->venta_serie . '-' . $reg->venta_correlativo),
                    utf8_decode($reg->cliente_numero),
                    utf8_decode($cliente),
                    utf8_decode($formaPago),
                    utf8_decode($simboloVenta . number_format($reg->venta_total, 2)),
                    utf8_decode($estado),
                ]);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Comprobantes_SUNAT_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }
    public function imprimirExcelHistorialEnvios(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteSunat($request);

            $fechaDesde = $d['fechaInicio'] ? date('d/m/Y', strtotime($d['fechaInicio'])) : '-';
            $fechaHasta = $d['fechaFinal']  ? date('d/m/Y', strtotime($d['fechaFinal']))  : '-';

            // ── PhpSpreadsheet ────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Comprobantes SUNAT');

            // Estilos
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
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloBorde = [
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8, 'name' => 'Arial'],
            ];

            // ── Fila 1: Título ────────────────────────────────────
            $sheet->mergeCells('A1:J1');
            $sheet->setCellValue('A1', 'Reporte de Comprobantes Emitidos a SUNAT');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // ── Filas 2-4: Filtros ────────────────────────────────
            $filtros = [
                ['Tipo Comprobante:', $d['tipoLabel'], 'Desde:', $fechaDesde],
                ['Hasta:',            $fechaHasta,     '',       ''],
            ];

            $fila = 2;
            foreach ($filtros as $row) {
                $sheet->setCellValue("A{$fila}", $row[0]); $sheet->getStyle("A{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->setCellValue("B{$fila}", $row[1]); $sheet->getStyle("B{$fila}")->applyFromArray($estiloValor);
                $sheet->setCellValue("F{$fila}", $row[2]); $sheet->getStyle("F{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->setCellValue("G{$fila}", $row[3]); $sheet->getStyle("G{$fila}")->applyFromArray($estiloValor);
                $fila++;
            }

            // ── Fila 5: Encabezados de tabla ──────────────────────
            $filaEnc = 5;
            $encabezados = [
                'A' => '#',             'B' => 'Fecha',
                'C' => 'Tipo Envío',    'D' => 'Tipo Comprobante',
                'E' => 'Serie-Correlativo', 'F' => 'N° Documento',
                'G' => 'Cliente',       'H' => 'Forma de Pago',
                'I' => 'Total',         'J' => 'Estado',
            ];
            foreach ($encabezados as $col => $texto) {
                $sheet->setCellValue("{$col}{$filaEnc}", $texto);
            }
            $sheet->getStyle("A{$filaEnc}:J{$filaEnc}")->applyFromArray($estiloEncabezadoTabla);
            $sheet->getRowDimension($filaEnc)->setRowHeight(22);

            // ── Filas de datos ────────────────────────────────────
            $filaData = 6;
            $n = 1;
            foreach ($d['ventas'] as $reg) {
                $tipoComp  = match($reg->venta_tipo) {
                    '03' => 'BOLETA', '01' => 'FACTURA',
                    '07' => 'NOTA DE CRÉDITO', '08' => 'NOTA DE DÉBITO', default => '--'
                };
                $tipoEnvio = $reg->venta_tipo_envio == 1 ? 'DIRECTO' : 'RESUMEN DIARIO';
                $cliente   = $reg->id_tipo_documento == 4 ? $reg->cliente_razonsocial : $reg->cliente_nombre;
                $formaPago = $reg->id_formas_pago == 1 ? 'CONTADO' : 'CRÉDITO';
                $estado    = $reg->anulado_sunat == 1 ? 'ANULADO' : 'VIGENTE';

                $simbolo = $reg->venta_tipo == '07' ? '-'.$reg->simbolo: $reg->simbolo;

                $sheet->setCellValue("A{$filaData}", $n++);
                $sheet->setCellValue("B{$filaData}", date('d/m/Y H:i:s', strtotime($reg->venta_fecha)));
                $sheet->setCellValue("C{$filaData}", $tipoEnvio);
                $sheet->setCellValue("D{$filaData}", $tipoComp);
                $sheet->setCellValue("E{$filaData}", $reg->venta_serie . '-' . $reg->venta_correlativo);
                $sheet->setCellValue("F{$filaData}", $reg->cliente_numero);
                $sheet->setCellValue("G{$filaData}", $cliente);
                $sheet->setCellValue("H{$filaData}", $formaPago);
                $sheet->setCellValue("I{$filaData}", $simbolo . number_format($reg->venta_total, 2));
                $sheet->setCellValue("J{$filaData}", $estado);

                $sheet->getStyle("A{$filaData}:J{$filaData}")->applyFromArray($estiloBorde);

                if ($reg->anulado_sunat == 1) {
                    $sheet->getStyle("A{$filaData}:J{$filaData}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFefa6ad']],
                    ]);
                } elseif ($filaData % 2 == 0) {
                    $sheet->getStyle("A{$filaData}:J{$filaData}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    ]);
                }

                $filaData++;
            }

            // Anchos de columnas
            $anchos = ['A'=>5,'B'=>20,'C'=>16,'D'=>18,'E'=>20,'F'=>14,'G'=>35,'H'=>14,'I'=>14,'J'=>10];
            foreach ($anchos as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            // ── Descargar ─────────────────────────────────────────
            $nombreArchivo = 'Comprobantes_SUNAT_' . date('Ymd_His') . '.xlsx';

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

    // ── Conciliación SUNAT ────────────────────────────────────

    public function conciliacion_sunat()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("conciliacion_sunat");
            return view('facturacion/conciliacion_sunat', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error Al Mostrar Contenido. Redireccionando Al Inicio'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosConciliacion(Request $request): array
    {
        $desde      = $request->desde      ?? null;
        $hasta      = $request->hasta      ?? null;
        $tipo       = $request->tipo       ?? '';
        $idEmpresa  = (int) ($request->id_empresa  ?? 0);
        $idSucursal = (int) ($request->id_sucursal ?? 0);

        $query = DB::table('ventas as v')
            ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
            ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
            ->select(
                'v.id_venta', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_tipo', 'v.venta_fecha', 'v.venta_total',
                'v.venta_estado_sunat', 'v.anulado_sunat',
                'v.venta_tipo_envio', 'v.venta_fecha_envio', 'v.venta_respuesta_sunat',
                'mo.simbolo',
                DB::raw("CASE WHEN c.id_tipo_documento = 4 THEN c.cliente_razonsocial ELSE c.cliente_nombre END as cliente_nombre"),
                DB::raw("c.cliente_numero")
            )
            ->whereIn('v.venta_tipo', ['01', '03', '07', '08']);

        if ($desde && $hasta) {
            $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        }
        if ($tipo !== '') {
            $query->where('v.venta_tipo', $tipo);
        }
        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa > 0) {
            $query->where('v.id_empresa', $idEmpresa);
        }

        $rows = $query->orderBy('v.venta_fecha')->orderBy('v.venta_serie')->orderBy('v.venta_correlativo')->get();

        $tipoLabels = ['01' => 'Factura', '03' => 'Boleta', '07' => 'Nota Crédito', '08' => 'Nota Débito'];

        $resumenPorTipo = collect($tipoLabels)->map(function ($label, $codigo) use ($rows) {
            $g = $rows->where('venta_tipo', $codigo);
            return (object) [
                'tipo'            => $codigo,
                'label'           => $label,
                'emitidas'        => $g->count(),
                'declaradas'      => $g->where('venta_estado_sunat', 1)->count(),
                'pendientes'      => $g->where('venta_estado_sunat', 0)->count(),
                'anuladas'        => $g->where('anulado_sunat', 1)->count(),
                'monto_emitido'   => $g->sum('venta_total'),
                'monto_declarado' => $g->where('venta_estado_sunat', 1)->sum('venta_total'),
                'monto_pendiente' => $g->where('venta_estado_sunat', 0)->sum('venta_total'),
            ];
        })->filter(fn($r) => $r->emitidas > 0)->values();

        $infoEmpresa  = $idEmpresa  > 0 ? DB::table('empresa')->where('id_empresa',  $idEmpresa)->first()  : null;
        $infoSucursal = $idSucursal > 0 ? DB::table('sucursals')->where('id_sucursal', $idSucursal)->first() : null;

        return compact('rows', 'resumenPorTipo', 'desde', 'hasta', 'tipo', 'infoEmpresa', 'infoSucursal');
    }

    public function conciliacion_sunat_pdf(Request $request)
    {
        try {
            $d = $this->obtenerDatosConciliacion($request);

            $fechaDesde = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';

            [$nR, $nG, $nB] = [30, 58, 95];
            [$gR, $gG, $gB] = [245, 247, 250];

            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();
            $pdf->AliasNbPages();
            $W = 277;

            // Header
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->Rect(10, 10, $W, 18, 'F');
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 13);
            $pdf->SetXY(10, 13);
            $pdf->Cell($W, 7, utf8_decode('CONCILIACIÓN VENTAS vs SUNAT'), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(10, 21);
            $pdf->Cell($W, 5, utf8_decode("Período: {$fechaDesde} — {$fechaHasta}"), 0, 1, 'C');
            $pdf->SetTextColor(30, 41, 59);

            // Resumen por tipo
            $pdf->SetY(33);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $colW = $W / 8;
            foreach (['Tipo', 'Emitidos', 'Declarados', 'Pendientes', 'Anulados', 'Mto. Emitido', 'Mto. Declarado', 'Mto. Pendiente'] as $h) {
                $pdf->Cell($colW, 6, utf8_decode($h), 0, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 7);

            $totE = $totD = $totP = $totA = $monE = $monD = $monP = 0;
            foreach ($d['resumenPorTipo'] as $i => $fila) {
                $fill = ($i % 2 === 0);
                $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                $pdf->Cell($colW, 5, utf8_decode($fila->label), 0, 0, 'L', $fill);
                $pdf->Cell($colW, 5, $fila->emitidas,  0, 0, 'C', $fill);
                $pdf->Cell($colW, 5, $fila->declaradas, 0, 0, 'C', $fill);
                $pdf->Cell($colW, 5, $fila->pendientes, 0, 0, 'C', $fill);
                $pdf->Cell($colW, 5, $fila->anuladas,  0, 0, 'C', $fill);
                $pdf->Cell($colW, 5, 'S/ ' . number_format($fila->monto_emitido,   2), 0, 0, 'R', $fill);
                $pdf->Cell($colW, 5, 'S/ ' . number_format($fila->monto_declarado, 2), 0, 0, 'R', $fill);
                $pdf->Cell($colW, 5, 'S/ ' . number_format($fila->monto_pendiente, 2), 0, 1, 'R', $fill);
                $totE += $fila->emitidas; $totD += $fila->declaradas;
                $totP += $fila->pendientes; $totA += $fila->anuladas;
                $monE += $fila->monto_emitido; $monD += $fila->monto_declarado; $monP += $fila->monto_pendiente;
            }
            // Fila total
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell($colW, 6, 'TOTAL', 0, 0, 'L', true);
            $pdf->Cell($colW, 6, $totE, 0, 0, 'C', true);
            $pdf->Cell($colW, 6, $totD, 0, 0, 'C', true);
            $pdf->Cell($colW, 6, $totP, 0, 0, 'C', true);
            $pdf->Cell($colW, 6, $totA, 0, 0, 'C', true);
            $pdf->Cell($colW, 6, 'S/ ' . number_format($monE, 2), 0, 0, 'R', true);
            $pdf->Cell($colW, 6, 'S/ ' . number_format($monD, 2), 0, 0, 'R', true);
            $pdf->Cell($colW, 6, 'S/ ' . number_format($monP, 2), 0, 1, 'R', true);

            $pdf->Ln(8);
            $pdf->SetTextColor(30, 41, 59);

            // Detalle
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $dCols = [8, 20, 20, 35, 45, 25, 30, 30, 64];
            foreach (['#', 'Fecha', 'Tipo', 'Serie-Número', 'Cliente', 'RUC/DNI', 'Total', 'Estado', 'Respuesta SUNAT'] as $i => $h) {
                $pdf->Cell($dCols[$i], 6, utf8_decode($h), 0, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetTextColor(30, 41, 59);

            $n = 1;
            foreach ($d['rows'] as $idx => $v) {
                $pdf->CheckPageBreak(5);
                if ($v->anulado_sunat) {
                    $pdf->SetFillColor(253, 237, 181);
                    $estado = 'ANULADO';
                } elseif ($v->venta_estado_sunat) {
                    $pdf->SetFillColor($idx % 2 === 0 ? $gR : 255, $idx % 2 === 0 ? $gG : 255, $idx % 2 === 0 ? $gB : 255);
                    $estado = 'DECLARADO';
                } else {
                    $pdf->SetFillColor(255, 220, 220);
                    $estado = 'PENDIENTE';
                }
                $tipoLabel = match($v->venta_tipo) { '01'=>'Factura','03'=>'Boleta','07'=>'N.Crédito','08'=>'N.Débito', default=>$v->venta_tipo };
                $pdf->Cell($dCols[0], 5, $n++, 0, 0, 'C', true);
                $pdf->Cell($dCols[1], 5, date('d/m/Y', strtotime($v->venta_fecha)), 0, 0, 'C', true);
                $pdf->Cell($dCols[2], 5, utf8_decode($tipoLabel), 0, 0, 'C', true);
                $pdf->Cell($dCols[3], 5, utf8_decode($v->venta_serie . '-' . str_pad($v->venta_correlativo, 8, '0', STR_PAD_LEFT)), 0, 0, 'C', true);
                $pdf->Cell($dCols[4], 5, utf8_decode(mb_substr($v->cliente_nombre, 0, 28)), 0, 0, 'L', true);
                $pdf->Cell($dCols[5], 5, $v->cliente_numero ?? '', 0, 0, 'C', true);
                $pdf->Cell($dCols[6], 5, $v->simbolo . ' ' . number_format($v->venta_total, 2), 0, 0, 'R', true);
                $pdf->Cell($dCols[7], 5, utf8_decode($estado), 0, 0, 'C', true);
                $respuesta = $v->venta_respuesta_sunat ? mb_substr($v->venta_respuesta_sunat, 0, 45) : '—';
                $pdf->Cell($dCols[8], 5, utf8_decode($respuesta), 0, 1, 'L', true);
            }

            // Footer
            $pdf->SetY(-12);
            $pdf->SetDrawColor($nR, $nG, $nB);
            $pdf->Line(10, $pdf->GetY(), 10 + $W, $pdf->GetY());
            $pdf->Ln(1);
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetTextColor(130, 130, 130);
            $pdf->SetX(10);
            $pdf->Cell($W / 2, 4, utf8_decode('Generado el ') . date('d/m/Y H:i'), 0, 0, 'L');
            $pdf->Cell($W / 2, 4, utf8_decode('Página ') . $pdf->PageNo() . ' de {nb}', 0, 0, 'R');

            $pdf->Output('I', 'Conciliacion-SUNAT-' . date('Ymd') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el PDF.'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function conciliacion_sunat_excel(Request $request)
    {
        try {
            $d = $this->obtenerDatosConciliacion($request);

            $fechaDesde = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $navy = 'FF1E3A5F';
            $white = 'FFFFFFFF';

            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $navy], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $estiloEnc = [
                'font'      => ['bold' => true, 'color' => ['argb' => $white], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $white]]],
            ];
            $estiloBorde = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'font'    => ['size' => 8, 'name' => 'Arial'],
            ];

            $spreadsheet = new Spreadsheet();

            // ── Hoja 1: Resumen por tipo ──────────────────────────
            $s1 = $spreadsheet->getActiveSheet();
            $s1->setTitle('Resumen por Tipo');
            $s1->mergeCells('A1:I1');
            $s1->setCellValue('A1', 'Conciliación Ventas vs SUNAT');
            $s1->getStyle('A1')->applyFromArray($estiloTitulo);
            $s1->getRowDimension(1)->setRowHeight(20);
            $s1->setCellValue('A2', 'Período:');
            $s1->setCellValue('B2', "{$fechaDesde} — {$fechaHasta}");
            $s1->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 8]]);

            $encRes = ['A' => 'Tipo', 'B' => 'Emitidos', 'C' => 'Declarados', 'D' => 'Pendientes', 'E' => 'Anulados', 'F' => 'Mto. Emitido', 'G' => 'Mto. Declarado', 'H' => 'Mto. Pendiente', 'I' => '% Declarado'];
            $fr = 4;
            foreach ($encRes as $col => $txt) { $s1->setCellValue("{$col}{$fr}", $txt); }
            $s1->getStyle("A{$fr}:I{$fr}")->applyFromArray($estiloEnc);
            $s1->getRowDimension($fr)->setRowHeight(16);

            $fr = 5;
            $totE = $totD = $totP = $totA = $monE = $monD = $monP = 0;
            foreach ($d['resumenPorTipo'] as $i => $fila) {
                $pct = $fila->emitidas > 0 ? round($fila->declaradas / $fila->emitidas * 100, 1) : 0;
                $s1->setCellValue("A{$fr}", $fila->label);
                $s1->setCellValue("B{$fr}", $fila->emitidas);
                $s1->setCellValue("C{$fr}", $fila->declaradas);
                $s1->setCellValue("D{$fr}", $fila->pendientes);
                $s1->setCellValue("E{$fr}", $fila->anuladas);
                $s1->setCellValue("F{$fr}", (float)$fila->monto_emitido);
                $s1->setCellValue("G{$fr}", (float)$fila->monto_declarado);
                $s1->setCellValue("H{$fr}", (float)$fila->monto_pendiente);
                $s1->setCellValue("I{$fr}", "{$pct}%");
                $s1->getStyle("A{$fr}:I{$fr}")->applyFromArray($estiloBorde);
                if ($i % 2 === 0) {
                    $s1->getStyle("A{$fr}:I{$fr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F7FA']]]);
                }
                $totE += $fila->emitidas; $totD += $fila->declaradas;
                $totP += $fila->pendientes; $totA += $fila->anuladas;
                $monE += $fila->monto_emitido; $monD += $fila->monto_declarado; $monP += $fila->monto_pendiente;
                $fr++;
            }
            $pctTotal = $totE > 0 ? round($totD / $totE * 100, 1) : 0;
            foreach (['A'=>'TOTAL','B'=>$totE,'C'=>$totD,'D'=>$totP,'E'=>$totA,'F'=>(float)$monE,'G'=>(float)$monD,'H'=>(float)$monP,'I'=>"{$pctTotal}%"] as $col => $val) {
                $s1->setCellValue("{$col}{$fr}", $val);
            }
            $s1->getStyle("A{$fr}:I{$fr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => $white]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
            ]);
            $s1->getStyle("F5:H{$fr}")->getNumberFormat()->setFormatCode('#,##0.00');
            foreach (['A'=>20,'B'=>10,'C'=>12,'D'=>12,'E'=>10,'F'=>16,'G'=>16,'H'=>16,'I'=>12] as $col=>$w) {
                $s1->getColumnDimension($col)->setWidth($w);
            }

            // ── Hoja 2: Detalle ───────────────────────────────────
            $s2 = $spreadsheet->createSheet();
            $s2->setTitle('Detalle Comprobantes');
            $s2->mergeCells('A1:I1');
            $s2->setCellValue('A1', 'Detalle de Comprobantes — Conciliación SUNAT');
            $s2->getStyle('A1')->applyFromArray($estiloTitulo);
            $s2->getRowDimension(1)->setRowHeight(20);

            $encDet = ['A'=>'#','B'=>'Fecha','C'=>'Tipo','D'=>'Serie-Número','E'=>'Cliente','F'=>'RUC/DNI','G'=>'Total','H'=>'Estado SUNAT','I'=>'Respuesta SUNAT'];
            $fd = 3;
            foreach ($encDet as $col => $txt) { $s2->setCellValue("{$col}{$fd}", $txt); }
            $s2->getStyle("A{$fd}:I{$fd}")->applyFromArray($estiloEnc);
            $s2->getRowDimension($fd)->setRowHeight(16);

            $fd = 4;
            foreach ($d['rows'] as $i => $v) {
                $tipoLabel = match($v->venta_tipo) { '01'=>'Factura','03'=>'Boleta','07'=>'Nota Crédito','08'=>'Nota Débito', default=>$v->venta_tipo };
                $estado    = $v->anulado_sunat ? 'Anulado' : ($v->venta_estado_sunat ? 'Declarado' : 'Pendiente');
                $s2->setCellValue("A{$fd}", $i + 1);
                $s2->setCellValue("B{$fd}", date('d/m/Y', strtotime($v->venta_fecha)));
                $s2->setCellValue("C{$fd}", $tipoLabel);
                $s2->setCellValue("D{$fd}", $v->venta_serie . '-' . str_pad($v->venta_correlativo, 8, '0', STR_PAD_LEFT));
                $s2->setCellValue("E{$fd}", $v->cliente_nombre);
                $s2->setCellValue("F{$fd}", $v->cliente_numero);
                $s2->setCellValue("G{$fd}", (float)$v->venta_total);
                $s2->setCellValue("H{$fd}", $estado);
                $s2->setCellValue("I{$fd}", $v->venta_respuesta_sunat ?? '');
                $s2->getStyle("A{$fd}:I{$fd}")->applyFromArray($estiloBorde);
                if ($v->anulado_sunat) {
                    $s2->getStyle("A{$fd}:I{$fd}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDE8A8']]]);
                } elseif (!$v->venta_estado_sunat) {
                    $s2->getStyle("A{$fd}:I{$fd}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFDDDD']]]);
                } elseif ($i % 2 === 0) {
                    $s2->getStyle("A{$fd}:I{$fd}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F7FA']]]);
                }
                $fd++;
            }
            $s2->getStyle("G4:G{$fd}")->getNumberFormat()->setFormatCode('#,##0.00');
            foreach (['A'=>5,'B'=>12,'C'=>14,'D'=>20,'E'=>35,'F'=>14,'G'=>14,'H'=>12,'I'=>50] as $col=>$w) {
                $s2->getColumnDimension($col)->setWidth($w);
            }

            $spreadsheet->setActiveSheetIndex(0);
            $writer   = new Xlsx($spreadsheet);
            $filename = 'Conciliacion-SUNAT-' . date('Ymd-His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el Excel.'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function guias_remision()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("guias_remision");
            return view('facturacion/guias_remision', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error Al Mostrar Contenido. Redireccionando Al Inicio'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

}
