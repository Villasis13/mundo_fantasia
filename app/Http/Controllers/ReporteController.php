<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Empresa;
use App\Models\General;
use App\Models\Logs;
use App\Models\Opciones;
use App\Models\PDFBufeo;
use App\Models\Serie;
use App\Models\Submenu;
use App\Models\Tipo_documento;
use App\Models\Tipo_pago;
use App\Models\User;
use App\Models\Ventas;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\PagosCuota;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use Carbon\Carbon;
class ReporteController extends Controller
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
        $this->usuarios = new User();
        $this->opciones = new Opciones();
        $this->general = new General();
        $this->empresas = new Empresa();
        $this->submenu = new Submenu();
        $this->caja = new Caja();
        $this->serie = new Serie();
        $this->tipos_de_pago = new Tipo_pago();
        $this->tipo_documento = new Tipo_documento();
        $this->ventas = new Ventas();
        $this->cliente = new \App\Models\Cliente();
    }


    public function formato14Excel(Request $request)
    {
        try {
            $idEmpresa  = (int) $request->get('id_empresa', 0);
            $idSucursal = (int) $request->get('id_sucursal', 0);
            $desde      = $request->get('desde');
            $hasta      = $request->get('hasta');

            $meses = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',
                7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];

            $empresa     = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
            $rucEmpresa  = $empresa->empresa_ruc ?? '';
            $nomEmpresa  = $empresa->empresa_razon_social ?? '';
            $fi = Carbon::parse($desde); $ff = Carbon::parse($hasta);
            $periodoIni = $meses[$fi->month] . ' ' . $fi->year;
            $periodoFin = $meses[$ff->month] . ' ' . $ff->year;

            // ── Ventas del período ──
            $query = DB::table('ventas as v')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->leftJoin('tipo_documento as td', 'td.id_tipo_documento', '=', 'c.id_tipo_documento')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '07', '08'])
                ->whereDate('v.venta_fecha', '>=', $desde)
                ->whereDate('v.venta_fecha', '<=', $hasta);

            if ($idSucursal > 0) {
                $query->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa) {
                $query->where('v.id_empresa', $idEmpresa);
            }

            $ventas = $query->orderBy('v.venta_fecha')->orderBy('v.venta_serie')->orderBy('v.venta_correlativo')
                ->select(
                    'v.venta_fecha', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                    'v.venta_totalexonerada', 'v.venta_totalinafecta', 'v.venta_totaligv',
                    'v.venta_totalgravada', 'v.venta_total',
                    'td.tipodocumento_codigo',
                    'c.cliente_numero', 'c.cliente_razonsocial', 'c.cliente_nombre'
                )->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            foreach (['A'=>18,'B'=>20,'E'=>15,'I'=>35,'M'=>15] as $col=>$w) $sheet->getColumnDimension($col)->setWidth($w);
            foreach (['C','D','F','G','H','J','K','L','N','O','P','Q','R','S','T','U'] as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $sheet->setCellValue('A1', 'Formato 14.1- Registro de Ventas e Ingresos');
            $sheet->setCellValue('A2', 'Periodo:');
            $sheet->setCellValue('A3', 'RUC:');
            $sheet->setCellValue('A4', 'Razón Social:');
            $sheet->setCellValue('A5', 'Expresado en:');
            $sheet->getStyle('A1:A5')->getFont()->setBold(true);
            $sheet->setCellValue('B2', "DESDE $periodoIni HASTA $periodoFin");
            $sheet->setCellValueExplicit('B3', $rucEmpresa, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B4', $nomEmpresa);
            $sheet->setCellValue('B5', 'SOLES');
            $sheet->getStyle('B2:B5')->getFont()->setBold(true);

            // Encabezados
            $sheet->setCellValue('A7', 'NUMERO CORRELATIVO');
            $sheet->setCellValue('B7', 'FECHA DE EMISION');
            $sheet->setCellValue('C7', 'FECHA VMTO/PAGO');
            $sheet->mergeCells('D7:F7'); $sheet->setCellValue('D7', 'COMPROBANTE DE PAGO O DOCUMENTO');
            $sheet->setCellValue('D8', 'TIPO'); $sheet->setCellValue('E8', 'SERIE'); $sheet->setCellValue('F8', 'NUMERO');
            $sheet->mergeCells('G7:I7'); $sheet->setCellValue('G7', 'INFORMACION DEL CLIENTE');
            $sheet->setCellValue('G8', 'TIPO DOC.'); $sheet->setCellValue('H8', 'NUMERO'); $sheet->setCellValue('I8', 'APELLIDOS Y NOMBRES O RAZON SOCIAL');
            $sheet->setCellValue('J7', 'BASE IMPONIBLE GRAVADA');
            $sheet->setCellValue('K7', 'EXONERADA'); $sheet->setCellValue('L7', 'INAFECTA');
            $sheet->setCellValue('M7', 'IGV Y/O IPM'); $sheet->setCellValue('N7', 'OTROS TRIBUTOS');
            $sheet->setCellValue('O7', 'IMPORTE TOTAL');
            $sheet->setCellValue('P7', 'TIPO DE CAMBIO');
            $sheet->mergeCells('Q7:U7'); $sheet->setCellValue('Q7', 'REFERENCIA DEL COMPROBANTE ORIGINAL QUE SE MODIFICA');
            $sheet->setCellValue('Q8', 'FECHA'); $sheet->setCellValue('R8', 'TIPO'); $sheet->setCellValue('S8', 'SERIE'); $sheet->setCellValue('T8', 'NUMERO'); $sheet->setCellValue('U8', 'PORC.IGV');
            $sheet->getStyle('A7:U8')->getFont()->setBold(true);
            $sheet->getStyle('A7:U8')->getAlignment()->setHorizontal('center')->setWrapText(true);
            $sheet->getStyle('A7:U8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->freezePane('A9');

            $fila = 9;
            $totalGeneral = 0;
            $mapaDoc = ['2' => '1', '4' => '6']; // interno DNI(2)->1, RUC(4)->6 (código SUNAT)

            foreach ($ventas as $v) {
                $signo = $v->venta_tipo === '07' ? -1 : 1; // nota de crédito resta
                $gravada   = $signo * (float) $v->venta_totalgravada;
                $exonerada = $signo * (float) $v->venta_totalexonerada;
                $inafecta  = $signo * (float) $v->venta_totalinafecta;
                $igv       = $signo * (float) $v->venta_totaligv;
                $total     = $signo * (float) $v->venta_total;
                $totalGeneral += $total;

                $codDoc = $mapaDoc[(string) ($v->tipodocumento_codigo ?? '')] ?? ($v->tipodocumento_codigo ?? '');
                $cliente = $v->cliente_razonsocial ?: $v->cliente_nombre;

                $sheet->setCellValue('A'.$fila, $v->venta_serie.'-'.str_pad((string)$v->venta_correlativo, 8, '0', STR_PAD_LEFT));
                $sheet->setCellValue('B'.$fila, Carbon::parse($v->venta_fecha)->format('d/m/Y'));
                $sheet->setCellValueExplicit('D'.$fila, $v->venta_tipo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('E'.$fila, $v->venta_serie, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F'.$fila, (string)$v->venta_correlativo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G'.$fila, (string)$codDoc, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('H'.$fila, (string)$v->cliente_numero, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('I'.$fila, $cliente);
                foreach (['J'=>$gravada,'K'=>$exonerada,'L'=>$inafecta,'M'=>$igv,'N'=>0.00,'O'=>$total,'U'=>0.00] as $col=>$val) {
                    $sheet->setCellValue($col.$fila, $val);
                    $sheet->getStyle($col.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $fila++;
            }

            $sheet->getStyle('A'.$fila.':U'.$fila)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $sheet->setCellValue('O'.$fila, $totalGeneral);
            $sheet->getStyle('O'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('O'.$fila)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                'font' => ['bold' => true],
            ]);

            $writer   = new Xlsx($spreadsheet);
            $fileName = 'reporte_formato_14.1_' . $desde . ' a ' . $hasta . '.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el Formato 14.1.');window.close();</script>";
        }
    }

    // ════════════════════════════════════════════════════════════
    //  EXPORTACIÓN GENÉRICA (PDF / EXCEL) PARA LOS REPORTES NUEVOS
    // ════════════════════════════════════════════════════════════
    private function pdfTabla(string $titulo, array $headers, array $widths, array $rows, string $filename)
    {
        $pdf = new PDFBufeo('L', 'mm', 'A4');
        $pdf->SetMargins(8, 10, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->AliasNbPages();

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 7, utf8_decode($titulo), 0, 1, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, utf8_decode($h), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetWidths($widths);
        foreach ($rows as $r) {
            $pdf->Row(array_map(fn($c) => utf8_decode((string) $c), array_values($r)));
        }

        // Mostrar inline en el navegador (pestaña nueva), no descargar
        $pdf->Output('I', $filename);
        exit;
    }

    private function excelTabla(string $titulo, array $headers, array $rows, string $filename)
    {
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();

        $sh->setCellValue('A1', $titulo);
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sh->fromArray($headers, null, 'A3');
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sh->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        ]);

        $sh->fromArray(array_map('array_values', $rows), null, 'A4');
        for ($i = 1; $i <= count($headers); $i++) {
            $sh->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $writer = new Xlsx($ss);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── 67: Ventas por Tipo de Pago ──
    private function dataTipoPago(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $idSucursal = (int) $r->get('id_sucursal', 0);
        $desde = $r->get('desde'); $hasta = $r->get('hasta');

        $q = DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')->where('vdp.venta_detalle_pago_estado', 1)
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereDate('v.venta_fecha', '>=', $desde)->whereDate('v.venta_fecha', '<=', $hasta);
        if ($idSucursal > 0) $q->where('v.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $q->where('v.id_empresa', $idEmpresa);

        $datos = $q->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->select('tp.tipo_pago_nombre', DB::raw('COUNT(DISTINCT v.id_venta) as num_operaciones'),
                     DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->orderByDesc('total')->get();

        $totalGen = (float) $datos->sum('total');
        $rows = $datos->map(fn($f) => [
            $f->tipo_pago_nombre, $f->num_operaciones, number_format($f->total, 2),
            ($totalGen > 0 ? number_format($f->total / $totalGen * 100, 1) : '0.0') . '%',
        ])->toArray();

        return [
            'titulo'  => 'Reporte de Ventas por Tipo de Pago (' . $desde . ' a ' . $hasta . ')',
            'headers' => ['Tipo de Pago', 'N° Operaciones', 'Total (S/)', '% Particip.'],
            'widths'  => [130, 50, 50, 50],
            'rows'    => $rows,
            'filebase'=> 'ventas_tipo_pago_' . $desde . '_' . $hasta,
        ];
    }
    public function tipoPagoPdf(Request $r)   { $d = $this->dataTipoPago($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function tipoPagoExcel(Request $r) { $d = $this->dataTipoPago($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    // ── 68: Utilidad de Ventas vs Costo ──
    private function dataUtilidad(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $idSucursal = (int) $r->get('id_sucursal', 0);
        $desde = $r->get('desde'); $hasta = $r->get('hasta'); $buscar = trim((string) $r->get('q', ''));
        $costoExpr = 'COALESCE(p.pro_costo_total, p.pro_costo_base, 0)';

        $q = DB::table('ventas_detalle as vd')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereDate('v.venta_fecha', '>=', $desde)->whereDate('v.venta_fecha', '<=', $hasta);
        if ($idSucursal > 0) $q->where('v.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $q->where('v.id_empresa', $idEmpresa);
        if ($buscar !== '') $q->where(fn($w) => $w->where('vd.venta_detalle_nombre_producto', 'like', "%$buscar%")->orWhere('p.pro_codigo', 'like', "%$buscar%"));

        $datos = $q->groupBy('vd.id_pro', 'vd.venta_detalle_nombre_producto', 'p.pro_codigo')
            ->select('vd.venta_detalle_nombre_producto as producto', 'p.pro_codigo',
                DB::raw('SUM(vd.venta_detalle_cantidad) as cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_venta'),
                DB::raw("SUM(vd.venta_detalle_cantidad * {$costoExpr}) as total_costo"),
                DB::raw("SUM(vd.venta_detalle_importe_total - (vd.venta_detalle_cantidad * {$costoExpr})) as utilidad"))
            ->orderByDesc('utilidad')->get();

        $rows = $datos->map(fn($f) => [
            $f->producto, $f->pro_codigo ?? '-', number_format($f->cantidad, 2),
            number_format($f->total_venta, 2), number_format($f->total_costo, 2), number_format($f->utilidad, 2),
            ($f->total_venta > 0 ? number_format($f->utilidad / $f->total_venta * 100, 1) : '0.0') . '%',
        ])->toArray();

        return [
            'titulo'  => 'Reporte de Utilidad de Ventas vs Costo (' . $desde . ' a ' . $hasta . ')',
            'headers' => ['Producto', 'Código', 'Cant.', 'Venta', 'Costo', 'Utilidad', 'Margen'],
            'widths'  => [95, 30, 25, 35, 35, 35, 26],
            'rows'    => $rows,
            'filebase'=> 'utilidad_ventas_' . $desde . '_' . $hasta,
        ];
    }
    public function utilidadPdf(Request $r)   { $d = $this->dataUtilidad($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function utilidadExcel(Request $r) { $d = $this->dataUtilidad($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    // ── 71: Movimientos de Productos ──
    private function dataMovimientos(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $idSucursal = (int) $r->get('id_sucursal', 0);
        $desde = $r->get('desde'); $hasta = $r->get('hasta');
        $tipo = trim((string) $r->get('tipo', '')); $buscar = trim((string) $r->get('q', ''));

        $q = DB::table('movimientos_productos_detalle as d')
            ->join('movimientos_productos as m', 'm.id_movimientos_productos', '=', 'd.id_movimientos_productos')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'm.id_sucursal')
            ->where('m.movimientos_productos_estado', 1)
            ->whereDate('m.movimientos_productos_fecha', '>=', $desde)->whereDate('m.movimientos_productos_fecha', '<=', $hasta);
        if ($idSucursal > 0) $q->where('m.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $q->where('t.id_empresa', $idEmpresa);
        if ($tipo !== '') $q->where('m.movimientos_productos_tipo', (int) $tipo);
        if ($buscar !== '') $q->where(fn($w) => $w->where('p.pro_nombre', 'like', "%$buscar%")->orWhere('p.pro_codigo', 'like', "%$buscar%"));

        $datos = $q->select('m.movimientos_productos_fecha as fecha', 'm.movimientos_productos_tipo as tipo',
            'm.movimientos_productos_motivo as motivo', 'p.pro_nombre', 'p.pro_codigo', 't.tienda_nombre',
            DB::raw('CAST(d.movimientos_productos_detalle_cantidad AS DECIMAL(14,2)) as cantidad'), 'd.costo_unitario')
            ->orderByDesc('m.movimientos_productos_fecha')->orderByDesc('m.id_movimientos_productos')->get();

        $rows = $datos->map(fn($m) => [
            \Carbon\Carbon::parse($m->fecha)->format('d/m/Y'),
            $m->tipo == 1 ? 'Ingreso' : 'Salida',
            $m->motivo, $m->pro_nombre ?? '-', $m->pro_codigo ?? '-', $m->tienda_nombre ?? '-',
            number_format($m->cantidad, 2), number_format($m->costo_unitario, 2),
        ])->toArray();

        return [
            'titulo'  => 'Reporte de Movimientos de Productos (' . $desde . ' a ' . $hasta . ')',
            'headers' => ['Fecha', 'Tipo', 'Motivo', 'Producto', 'Código', 'Sucursal', 'Cant.', 'Costo U.'],
            'widths'  => [22, 20, 55, 70, 28, 35, 22, 29],
            'rows'    => $rows,
            'filebase'=> 'movimientos_productos_' . $desde . '_' . $hasta,
        ];
    }
    public function movimientosPdf(Request $r)   { $d = $this->dataMovimientos($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function movimientosExcel(Request $r) { $d = $this->dataMovimientos($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    // ── 72: Lista de Precios ──
    private function dataListaPrecios(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $idSucursal = (int) $r->get('id_sucursal', 0);
        $buscar = trim((string) $r->get('q', ''));

        $q = DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
            ->where('ps.ps_estado', 1)->where('p.pro_estado', 1);
        if ($idSucursal > 0) $q->where('ps.id_tienda', $idSucursal);
        elseif ($idEmpresa) $q->where('t.id_empresa', $idEmpresa);
        if ($buscar !== '') $q->where(fn($w) => $w->where('p.pro_nombre', 'like', "%$buscar%")->orWhere('p.pro_codigo', 'like', "%$buscar%")->orWhere('p.pro_marca', 'like', "%$buscar%"));

        $datos = $q->select('p.pro_nombre', 'p.pro_codigo', 'p.pro_marca', 'p.pro_costo_total',
            'ps.ps_precio_uni', 'ps.ps_precio_uni_2', 'ps.ps_precio_uni_3', 'ps.ps_stock', 't.tienda_nombre')
            ->orderBy('p.pro_nombre')->get();

        $rows = $datos->map(fn($f) => [
            $f->pro_nombre, $f->pro_codigo ?? '-', $f->pro_marca ?? '-', $f->tienda_nombre,
            number_format($f->pro_costo_total, 2), number_format($f->ps_precio_uni, 2),
            number_format($f->ps_precio_uni_2, 2), number_format($f->ps_precio_uni_3, 2), number_format($f->ps_stock, 2),
        ])->toArray();

        return [
            'titulo'  => 'Reporte Lista de Precios',
            'headers' => ['Producto', 'Código', 'Marca', 'Sucursal', 'Costo', 'P.Público', 'P.Mayorista', 'P.3', 'Stock'],
            'widths'  => [62, 28, 30, 35, 26, 26, 28, 22, 24],
            'rows'    => $rows,
            'filebase'=> 'lista_precios_' . now()->format('Ymd'),
        ];
    }
    public function listaPreciosPdf(Request $r)   { $d = $this->dataListaPrecios($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function listaPreciosExcel(Request $r) { $d = $this->dataListaPrecios($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    // ── 73: Stock Mínimo ──
    private function dataStockMinimo(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $idSucursal = (int) $r->get('id_sucursal', 0);
        $buscar = trim((string) $r->get('q', ''));

        $q = DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
            ->where('ps.ps_estado', 1)->where('p.pro_estado', 1)
            ->where('ps.ps_stock_minimo', '>', 0)->whereColumn('ps.ps_stock', '<=', 'ps.ps_stock_minimo');
        if ($idSucursal > 0) $q->where('ps.id_tienda', $idSucursal);
        elseif ($idEmpresa) $q->where('t.id_empresa', $idEmpresa);
        if ($buscar !== '') $q->where(fn($w) => $w->where('p.pro_nombre', 'like', "%$buscar%")->orWhere('p.pro_codigo', 'like', "%$buscar%"));

        $datos = $q->select('p.pro_nombre', 'p.pro_codigo', 'ps.ps_stock', 'ps.ps_stock_minimo', 't.tienda_nombre',
            DB::raw('(ps.ps_stock_minimo - ps.ps_stock) as faltante'))->orderByDesc('faltante')->get();

        $rows = $datos->map(fn($f) => [
            $f->pro_nombre, $f->pro_codigo ?? '-', $f->tienda_nombre,
            number_format($f->ps_stock, 2), number_format($f->ps_stock_minimo, 2), number_format($f->faltante, 2),
        ])->toArray();

        return [
            'titulo'  => 'Reporte de Productos con Stock <= Mínimo',
            'headers' => ['Producto', 'Código', 'Sucursal', 'Stock Actual', 'Stock Mínimo', 'Faltante'],
            'widths'  => [85, 35, 45, 35, 35, 30],
            'rows'    => $rows,
            'filebase'=> 'stock_minimo_' . now()->format('Ymd'),
        ];
    }
    public function stockMinimoPdf(Request $r)   { $d = $this->dataStockMinimo($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function stockMinimoExcel(Request $r) { $d = $this->dataStockMinimo($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    // ── 74: Series de Productos ──
    private function dataSeries(Request $r): array
    {
        $idEmpresa = (int) $r->get('id_empresa', 0);
        $buscar = trim((string) $r->get('q', '')); $estado = trim((string) $r->get('estado', ''));

        $q = DB::table('producto_series as s')
            ->join('productos as p', 'p.id_pro', '=', 's.id_pro')
            ->leftJoin('users as u', 'u.id_users', '=', 's.id_users');
        if ($idEmpresa) $q->where('p.id_empresa', $idEmpresa);
        if ($buscar !== '') $q->where(fn($w) => $w->where('s.numero_serie', 'like', "%$buscar%")->orWhere('p.pro_nombre', 'like', "%$buscar%")->orWhere('p.pro_codigo', 'like', "%$buscar%"));
        if ($estado !== '') $q->where('s.estado', (int) $estado);

        $datos = $q->select('s.numero_serie', 's.estado', 's.observacion', 's.id_venta', 's.id_orden_compra',
            's.created_at', 'p.pro_nombre', 'p.pro_codigo', 'u.nombre_users')
            ->orderByDesc('s.id_producto_serie')->get();

        $rows = $datos->map(fn($s) => [
            $s->numero_serie, $s->pro_nombre, $s->pro_codigo ?? '-',
            $s->estado == 2 ? 'Vendido' : 'Disponible',
            $s->id_venta ? 'Venta #' . $s->id_venta : ($s->id_orden_compra ? 'Compra #' . $s->id_orden_compra : '-'),
            $s->observacion ?? '-', $s->nombre_users ?? '-',
            $s->created_at ? \Carbon\Carbon::parse($s->created_at)->format('d/m/Y') : '-',
        ])->toArray();

        return [
            'titulo'  => 'Reporte de Series de Productos',
            'headers' => ['N° Serie', 'Producto', 'Código', 'Estado', 'Origen', 'Observación', 'Usuario', 'Fecha'],
            'widths'  => [38, 60, 26, 26, 30, 45, 30, 26],
            'rows'    => $rows,
            'filebase'=> 'series_productos_' . now()->format('Ymd'),
        ];
    }
    public function seriesPdf(Request $r)   { $d = $this->dataSeries($r); return $this->pdfTabla($d['titulo'], $d['headers'], $d['widths'], $d['rows'], $d['filebase'] . '.pdf'); }
    public function seriesExcel(Request $r) { $d = $this->dataSeries($r); return $this->excelTabla($d['titulo'], $d['headers'], $d['rows'], $d['filebase'] . '.xlsx'); }

    public function reporteSeriesProductos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_series_productos");
            return view('reporte/reporte_series_productos', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteMovimientos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_movimientos");
            return view('reporte/reporte_movimientos', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteListaPrecios()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_lista_precios");
            return view('reporte/reporte_lista_precios', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteStockMinimo()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_stock_minimo");
            return view('reporte/reporte_stock_minimo', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteVentasTipoPago()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_ventas_tipo_pago");
            return view('reporte/reporte_ventas_tipo_pago', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteUtilidad()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_utilidad");
            return view('reporte/reporte_utilidad', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporte_de_ventas()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("reporte_de_ventas");
            return view('reporte/reporte_de_ventas', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }

    }

    private function obtenerDatosReporteVentas(Request $request): array
    {
        $desde = $request->desde ?? null;
        $hasta = $request->hasta ?? null;
        $idEmpresa = $request->id_empresa ?? null;
        $idSucursal = $request->id_sucursal ?? null;

        $venta      = new \App\Models\Ventas();       // ajusta al namespace real
        $pagosCuota = new \App\Models\PagosCuota();  // ajusta al namespace real

        return [
            'desde'           => $desde,
            'hasta'           => $hasta,
            'ventasBrutas'    => $venta->listarVentasPorTipo(['01','03'], $desde, $hasta, 2,$idEmpresa,$idSucursal),
            'notasCredito'    => $venta->listarVentasPorTipo(['07'],      $desde, $hasta, 2,$idEmpresa,$idSucursal),
            'notasDebito'     => $venta->listarVentasPorTipo(['08'],      $desde, $hasta, 2,$idEmpresa,$idSucursal),
            'notasVentas'     => $venta->listarVentasNotasVentas($desde, $hasta, 2,$idEmpresa,$idSucursal),
            'pagosCuotas'     => $pagosCuota->listarPagosRealizados($desde, $hasta, 2,$idEmpresa,$idSucursal),
            'listaVentas'     => $venta->listarVentasPorTipo(['01','03'], $desde, $hasta, 1,$idEmpresa,$idSucursal),
            'listaNotaCredito'=> $venta->listarVentasPorTipo(['07'],      $desde, $hasta, 1,$idEmpresa,$idSucursal),
            'listaNotaDebito' => $venta->listarVentasPorTipo(['08'],      $desde, $hasta, 1,$idEmpresa,$idSucursal),
            'listaPagosCuotas'=> $pagosCuota->listarPagosRealizados($desde, $hasta, 1,$idEmpresa,$idSucursal),
            'listaNotasVentas'=> $venta->listarVentasNotasVentas($desde, $hasta, 1,$idEmpresa,$idSucursal),
        ];
    }
    public function imprimirPdfReporteVentas(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteVentas($request);

            $fechaDesde  = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta  = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $ingresoNeto = $d['ventasBrutas'] - $d['notasCredito'] + $d['notasDebito'] + $d['pagosCuotas'] + $d['notasVentas'];

            // ── FPDF — A4 Vertical — ancho útil 180mm ────────────
            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // ── Título ────────────────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Ventas'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // ── Filtros ───────────────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(18, 4, utf8_decode('Desde: '), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($fechaDesde), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(18, 4, utf8_decode('Hasta: '), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($fechaHasta), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // ── Resumen de totales ────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('RESUMEN DE TOTALES'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(242, 242, 242);

            $totales = [
                ['Boletas y Facturas',    'S/ ' . number_format($d['ventasBrutas'], 2)],
                ['Pagos de Cuotas',  'S/ ' . number_format($d['pagosCuotas'],  2)],
                ['Notas de Venta',   'S/ ' . number_format($d['notasVentas'],  2)],
                ['Notas de Crédito', 'S/ ' . number_format($d['notasCredito'], 2)],
                ['Notas de Débito',  'S/ ' . number_format($d['notasDebito'],  2)],
                ['Ingreso Total',     'S/ ' . number_format($ingresoNeto,       2)],
            ];

            foreach ($totales as $i => $fila) {
                $fill = ($i % 2 == 0);
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                if ($fila[0] === 'Ingreso Total') {
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->SetFillColor(220, 230, 241);
                } else {
                    $pdf->SetFont('Helvetica', '', 8);
                }
                $pdf->Cell(120, 5, utf8_decode($fila[0]), 1, 0, 'L', 1);
                $pdf->Cell(60,  5, utf8_decode($fila[1]), 1, 1, 'R', 1);
            }

            $pdf->Ln(6);

            // ── Helper para imprimir secciones ───────────────────
            // Encabezado de sección
            $imprimirSeccion = function($titulo) use ($pdf) {
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetFillColor(33, 44, 62);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(180, 6, utf8_decode(strtoupper($titulo)), 0, 1, 'L', 1);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(1);
            };

            // Encabezado de tabla
            $imprimirEncabezadoTabla = function($columnas, $anchos) use ($pdf) {
                $pdf->SetFont('Helvetica', 'B', 6);
                $pdf->SetFillColor(70, 90, 110);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($columnas as $i => $col) {
                    $pdf->Cell($anchos[$i], 8, utf8_decode($col), 1, 0, 'C', 1);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetTextColor(0, 0, 0);
            };

            // ── 1. Comprobantes de Venta ──────────────────────────
            // Anchos: 8+20+14+14+16+62+46 = 180mm
            $imprimirSeccion('Comprobantes de Venta Emitidos');
            $imprimirEncabezadoTabla(
                ['#', 'Fecha', 'Tipo', 'Serie', 'Número', 'Cliente', 'Total'],
                [8, 20, 14, 14, 16, 62, 46]
            );
            $pdf->SetWidths([8, 20, 14, 14, 16, 62, 46]);
            $pdf->SetFont('Helvetica', '', 6);
            $n = 1;
            foreach ($d['listaVentas'] as $reg) {
                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y H:i', strtotime($reg->venta_fecha))),
                    utf8_decode($reg->venta_tipo == '01' ? 'FACTURA' : 'BOLETA'),
                    utf8_decode($reg->venta_serie),
                    utf8_decode($reg->venta_correlativo),
                    utf8_decode($reg->cliente_razonsocial),
                    utf8_decode($reg->simbolo . number_format($reg->venta_total, 2)),
                ]);
            }
            $pdf->Ln(5);

            // ── 2. Notas de Venta ─────────────────────────────────
            // Anchos: 8+20+30+76+46 = 180mm
            $imprimirSeccion('Notas de Ventas');
            $imprimirEncabezadoTabla(
                ['#', 'Fecha', 'Serie y Número', 'Cliente', 'Total'],
                [8, 20, 30, 76, 46]
            );
            $pdf->SetWidths([8, 20, 30, 76, 46]);
            $pdf->SetFont('Helvetica', '', 6);
            $n = 1;
            foreach ($d['listaNotasVentas'] as $reg) {
                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y H:i', strtotime($reg->venta_fecha))),
                    utf8_decode($reg->venta_serie . '-' . $reg->venta_correlativo),
                    utf8_decode($reg->cliente_razonsocial),
                    utf8_decode($reg->simbolo . number_format($reg->venta_total, 2)),
                ]);
            }
            $pdf->Ln(5);

            // ── 3. Notas de Crédito ───────────────────────────────
            // Anchos: 8+20+28+24+54+46 = 180mm
            $imprimirSeccion('Notas de Crédito');
            $imprimirEncabezadoTabla(
                ['#', 'Fecha', 'Serie-Correlativo', 'Doc Ref', 'Motivo', 'Total'],
                [8, 20, 28, 24, 54, 46]
            );
            $pdf->SetWidths([8, 20, 28, 24, 54, 46]);
            $pdf->SetFont('Helvetica', '', 6);
            $n = 1;
            foreach ($d['listaNotaCredito'] as $reg) {
                $motivo = Tipo_ncredito::listar_tipo_notaC_x_codigo($reg->venta_codigo_motivo_nota);
                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y H:i', strtotime($reg->venta_fecha))),
                    utf8_decode($reg->venta_serie . '-' . $reg->venta_correlativo),
                    utf8_decode($reg->serie_modificar . '-' . $reg->correlativo_modificar),
                    utf8_decode($motivo ? $motivo->tipo_nota_descripcion : ''),
                    utf8_decode($reg->simbolo . number_format($reg->venta_total, 2)),
                ]);
            }
            $pdf->Ln(5);

            // ── 4. Notas de Débito ────────────────────────────────
            // Anchos: 8+20+28+24+54+46 = 180mm
            $imprimirSeccion('Notas de Débito');
            $imprimirEncabezadoTabla(
                ['#', 'Fecha', 'Serie-Correlativo', 'Doc Ref', 'Motivo', 'Total'],
                [8, 20, 28, 24, 54, 46]
            );
            $pdf->SetWidths([8, 20, 28, 24, 54, 46]);
            $pdf->SetFont('Helvetica', '', 6);
            $n = 1;
            foreach ($d['listaNotaDebito'] as $reg) {
                $motivo = Tipo_ndebito::listar_tipo_notaD_x_codigo($reg->venta_codigo_motivo_nota);
                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y H:i', strtotime($reg->venta_fecha))),
                    utf8_decode($reg->venta_serie . '-' . $reg->venta_correlativo),
                    utf8_decode($reg->serie_modificar . '-' . $reg->correlativo_modificar),
                    utf8_decode($motivo ? $motivo->tipo_nota_descripcion : ''),
                    utf8_decode($reg->simbolo . number_format($reg->venta_total, 2)),
                ]);
            }
            $pdf->Ln(5);

            // ── 5. Pagos de Cuotas ────────────────────────────────
            // Anchos: 8+20+30+30+22+70 = 180mm
            $imprimirSeccion('Pagos de Cuotas');
            $imprimirEncabezadoTabla(
                ['#', 'Fecha', 'Tipo de Pago', 'Comprobante', 'N° Cuota', 'Monto'],
                [8, 20, 36, 36, 20, 60]
            );
            $pdf->SetWidths([8, 20, 36, 36, 20, 60]);
            $pdf->SetFont('Helvetica', '', 6);
            $n = 1;
            foreach ($d['listaPagosCuotas'] as $reg) {
                $nroCuota = str_pad((string)$reg->venta_cuota_numero, 3, '0', STR_PAD_LEFT);
                $pdf->Row([
                    $n++,
                    utf8_decode(date('d/m/Y', strtotime($reg->pagos_cuota_fecha))),
                    utf8_decode($reg->tipo_pago_nombre),
                    utf8_decode($reg->venta_serie . '-' . $reg->venta_correlativo),
                    utf8_decode($nroCuota),
                    utf8_decode($reg->simbolo . number_format($reg->pagos_cuota_monto, 2)),
                ]);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Reporte_Ventas_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }
    public function imprimirExcelReporteVentas(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteVentas($request);

            $fechaDesde  = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta  = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $ingresoNeto = $d['ventasBrutas'] - $d['notasCredito'] + $d['notasDebito'] + $d['pagosCuotas'] + $d['notasVentas'];

            // ── PhpSpreadsheet ────────────────────────────────────
            $spreadsheet = new Spreadsheet();

            // ── Estilos reutilizables ─────────────────────────────
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
            $estiloSeccion = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEncabezadoTabla = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloBordeDato = [
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8, 'name' => 'Arial'],
            ];
            $estiloFilaPar = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
            ];

            // ── Helper: escribir una sección con su tabla ─────────
            // Retorna la siguiente fila libre después de la sección
            $escribirSeccion = function(
                $sheet, $filaInicio, $titulo, $encabezados, $filas, $ultimaCol
            ) use (
                $estiloSeccion, $estiloEncabezadoTabla, $estiloBordeDato, $estiloFilaPar
            ) {
                // Título de sección
                $sheet->mergeCells("A{$filaInicio}:{$ultimaCol}{$filaInicio}");
                $sheet->setCellValue("A{$filaInicio}", $titulo);
                $sheet->getStyle("A{$filaInicio}:{$ultimaCol}{$filaInicio}")->applyFromArray($estiloSeccion);
                $sheet->getRowDimension($filaInicio)->setRowHeight(16);

                // Encabezados de tabla
                $filaEnc = $filaInicio + 1;
                foreach ($encabezados as $col => $texto) {
                    $sheet->setCellValue("{$col}{$filaEnc}", $texto);
                }
                $firstCol = array_key_first($encabezados);
                $sheet->getStyle("{$firstCol}{$filaEnc}:{$ultimaCol}{$filaEnc}")->applyFromArray($estiloEncabezadoTabla);
                $sheet->getRowDimension($filaEnc)->setRowHeight(20);

                // Filas de datos
                $filaData = $filaEnc + 1;
                foreach ($filas as $fila) {
                    $i = 0;
                    foreach ($encabezados as $col => $texto) {
                        $sheet->setCellValue("{$col}{$filaData}", $fila[$i++]);
                    }
                    $sheet->getStyle("{$firstCol}{$filaData}:{$ultimaCol}{$filaData}")->applyFromArray($estiloBordeDato);
                    if ($filaData % 2 == 0) {
                        $sheet->getStyle("{$firstCol}{$filaData}:{$ultimaCol}{$filaData}")->applyFromArray($estiloFilaPar);
                    }
                    $filaData++;
                }

                return $filaData + 1; // deja una fila vacía entre secciones
            };

            // ══════════════════════════════════════════════════════
            //  HOJA 1 — Resumen
            // ══════════════════════════════════════════════════════
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen');

            $sheet->mergeCells('A1:B1');
            $sheet->setCellValue('A1', 'Reporte de Ventas');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            $sheet->setCellValue('A2', 'Desde:');  $sheet->getStyle('A2')->applyFromArray($estiloEtiqueta);
            $sheet->setCellValue('B2', $fechaDesde); $sheet->getStyle('B2')->applyFromArray($estiloValor);
            $sheet->setCellValue('A3', 'Hasta:');  $sheet->getStyle('A3')->applyFromArray($estiloEtiqueta);
            $sheet->setCellValue('B3', $fechaHasta); $sheet->getStyle('B3')->applyFromArray($estiloValor);

            // Tabla resumen
            $sheet->mergeCells('A5:B5');
            $sheet->setCellValue('A5', 'RESUMEN DE TOTALES');
            $sheet->getStyle('A5:B5')->applyFromArray($estiloSeccion);
            $sheet->getRowDimension(5)->setRowHeight(16);

            $totalesResumen = [
                ['Boletas y Facturas',    'S/ ' . number_format($d['ventasBrutas'], 2)],
                ['Pagos de Cuotas',  'S/ ' . number_format($d['pagosCuotas'],  2)],
                ['Notas de Venta',   'S/ ' . number_format($d['notasVentas'],  2)],
                ['Notas de Crédito', 'S/ ' . number_format($d['notasCredito'], 2)],
                ['Notas de Débito',  'S/ ' . number_format($d['notasDebito'],  2)],
                ['Ingreso Total',     'S/ ' . number_format($ingresoNeto,       2)],
            ];

            $filaRes = 6;
            foreach ($totalesResumen as $i => $fila) {
                $sheet->setCellValue("A{$filaRes}", $fila[0]);
                $sheet->setCellValue("B{$filaRes}", $fila[1]);
                $estiloFila = $estiloBordeDato;
                if ($fila[0] === 'Ingreso Total') {
                    $estiloFila['font']['bold'] = true;
                    $sheet->getStyle("A{$filaRes}:B{$filaRes}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                    ]);
                } elseif ($filaRes % 2 == 0) {
                    $sheet->getStyle("A{$filaRes}:B{$filaRes}")->applyFromArray($estiloFilaPar);
                }
                $sheet->getStyle("A{$filaRes}:B{$filaRes}")->applyFromArray($estiloFila);
                $filaRes++;
            }

            $sheet->getColumnDimension('A')->setWidth(22);
            $sheet->getColumnDimension('B')->setWidth(18);

            // ══════════════════════════════════════════════════════
            //  HOJA 2 — Comprobantes de Venta
            // ══════════════════════════════════════════════════════
            $sheetVentas = $spreadsheet->createSheet();
            $sheetVentas->setTitle('Comprobantes de Venta');

            $filasVentas = [];
            $n = 1;
            foreach ($d['listaVentas'] as $reg) {
                $filasVentas[] = [
                    $n++,
                    date('d/m/Y H:i:s', strtotime($reg->venta_fecha)),
                    $reg->venta_tipo == '01' ? 'FACTURA' : 'BOLETA',
                    $reg->venta_serie,
                    $reg->venta_correlativo,
                    $reg->cliente_razonsocial,
                    $reg->id_tipo_documento == 4 ? 'RUC' : 'DNI',
                    $reg->cliente_numero,
                    $reg->simbolo . number_format($reg->venta_total, 2),
                ];
            }

            $escribirSeccion($sheetVentas, 1, 'Comprobantes de Venta Emitidos',
                ['A'=>'#','B'=>'Fecha','C'=>'Tipo','D'=>'Serie','E'=>'Número','F'=>'Cliente','G'=>'Tipo Doc','H'=>'N° Documento','I'=>'Total'],
                $filasVentas, 'I'
            );

            foreach (['A'=>5,'B'=>20,'C'=>10,'D'=>10,'E'=>12,'F'=>35,'G'=>10,'H'=>14,'I'=>14] as $col => $w) {
                $sheetVentas->getColumnDimension($col)->setWidth($w);
            }

            // ══════════════════════════════════════════════════════
            //  HOJA 3 — Notas de Venta
            // ══════════════════════════════════════════════════════
            $sheetNV = $spreadsheet->createSheet();
            $sheetNV->setTitle('Notas de Venta');

            $filasNV = [];
            $n = 1;
            foreach ($d['listaNotasVentas'] as $reg) {
                $filasNV[] = [
                    $n++,
                    date('d/m/Y H:i:s', strtotime($reg->venta_fecha)),
                    $reg->venta_serie . '-' . $reg->venta_correlativo,
                    $reg->cliente_razonsocial,
                    $reg->id_tipo_documento == 4 ? 'RUC' : 'DNI',
                    $reg->cliente_numero,
                    $reg->simbolo . number_format($reg->venta_total, 2),
                ];
            }

            $escribirSeccion($sheetNV, 1, 'Notas de Ventas',
                ['A'=>'#','B'=>'Fecha','C'=>'Serie y Número','D'=>'Cliente','E'=>'Tipo Doc','F'=>'N° Documento','G'=>'Total'],
                $filasNV, 'G'
            );

            foreach (['A'=>5,'B'=>20,'C'=>18,'D'=>35,'E'=>10,'F'=>14,'G'=>14] as $col => $w) {
                $sheetNV->getColumnDimension($col)->setWidth($w);
            }

            // ══════════════════════════════════════════════════════
            //  HOJA 4 — Notas de Crédito
            // ══════════════════════════════════════════════════════
            $sheetNC = $spreadsheet->createSheet();
            $sheetNC->setTitle('Notas de Crédito');

            $filasNC = [];
            $n = 1;
            foreach ($d['listaNotaCredito'] as $reg) {
                $motivo = Tipo_ncredito::listar_tipo_notaC_x_codigo($reg->venta_codigo_motivo_nota);
                $filasNC[] = [
                    $n++,
                    date('d/m/Y H:i:s', strtotime($reg->venta_fecha)),
                    $reg->venta_serie . '-' . $reg->venta_correlativo,
                    $reg->serie_modificar . '-' . $reg->correlativo_modificar,
                    $motivo ? $motivo->tipo_nota_descripcion : '',
                    $reg->simbolo . number_format($reg->venta_total, 2),
                ];
            }

            $escribirSeccion($sheetNC, 1, 'Notas de Crédito',
                ['A'=>'#','B'=>'Fecha','C'=>'Serie-Correlativo','D'=>'Doc Ref','E'=>'Motivo','F'=>'Total'],
                $filasNC, 'F'
            );

            foreach (['A'=>5,'B'=>20,'C'=>20,'D'=>16,'E'=>45,'F'=>14] as $col => $w) {
                $sheetNC->getColumnDimension($col)->setWidth($w);
            }

            // ══════════════════════════════════════════════════════
            //  HOJA 5 — Notas de Débito
            // ══════════════════════════════════════════════════════
            $sheetND = $spreadsheet->createSheet();
            $sheetND->setTitle('Notas de Débito');

            $filasND = [];
            $n = 1;
            foreach ($d['listaNotaDebito'] as $reg) {
                $motivo = Tipo_ndebito::listar_tipo_notaD_x_codigo($reg->venta_codigo_motivo_nota);
                $filasND[] = [
                    $n++,
                    date('d/m/Y H:i:s', strtotime($reg->venta_fecha)),
                    $reg->venta_serie . '-' . $reg->venta_correlativo,
                    $reg->serie_modificar . '-' . $reg->correlativo_modificar,
                    $motivo ? $motivo->tipo_nota_descripcion : '',
                    $reg->simbolo . number_format($reg->venta_total, 2),
                ];
            }

            $escribirSeccion($sheetND, 1, 'Notas de Débito',
                ['A'=>'#','B'=>'Fecha','C'=>'Serie-Correlativo','D'=>'Doc Ref','E'=>'Motivo','F'=>'Total'],
                $filasND, 'F'
            );

            foreach (['A'=>5,'B'=>20,'C'=>20,'D'=>16,'E'=>45,'F'=>14] as $col => $w) {
                $sheetND->getColumnDimension($col)->setWidth($w);
            }

            // ══════════════════════════════════════════════════════
            //  HOJA 6 — Pagos de Cuotas
            // ══════════════════════════════════════════════════════
            $sheetPC = $spreadsheet->createSheet();
            $sheetPC->setTitle('Pagos de Cuotas');

            $filasPC = [];
            $n = 1;
            foreach ($d['listaPagosCuotas'] as $reg) {
                $nroCuota = str_pad((string)$reg->venta_cuota_numero, 3, '0', STR_PAD_LEFT);
                $filasPC[] = [
                    $n++,
                    date('d/m/Y', strtotime($reg->pagos_cuota_fecha)),
                    $reg->tipo_pago_nombre,
                    $reg->venta_serie . '-' . $reg->venta_correlativo,
                    $nroCuota,
                    $reg->simbolo . number_format($reg->pagos_cuota_monto, 2),
                ];
            }

            $escribirSeccion($sheetPC, 1, 'Pagos de Cuotas',
                ['A'=>'#','B'=>'Fecha','C'=>'Tipo de Pago','D'=>'Comprobante Vinculado','E'=>'N° Cuota','F'=>'Monto'],
                $filasPC, 'F'
            );

            foreach (['A'=>5,'B'=>14,'C'=>22,'D'=>22,'E'=>10,'F'=>14] as $col => $w) {
                $sheetPC->getColumnDimension($col)->setWidth($w);
            }

            // Activar la hoja Resumen al abrir el archivo
            $spreadsheet->setActiveSheetIndex(0);

            // ── Descargar ─────────────────────────────────────────
            $nombreArchivo = 'Reporte_Ventas_' . date('Ymd_His') . '.xlsx';

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

    public function imprimirExcelParaEstudio(Request $request)
    {
        try {
            $desde      = $request->desde      ?? null;
            $hasta      = $request->hasta      ?? null;
            $idEmpresa  = (int) ($request->id_empresa  ?? 0);
            $idSucursal = (int) ($request->id_sucursal ?? 0);

            // ── Consulta base: todos los comprobantes del período ─────
            $query = DB::table('ventas as v')
                ->select(
                    'v.id_venta',
                    'v.venta_fecha',
                    'v.venta_tipo',
                    'v.venta_serie',
                    'v.venta_correlativo',
                    'v.venta_totalgravada',
                    'v.venta_totalexonerada',
                    'v.venta_totalinafecta',
                    'v.venta_totaligv',
                    'v.venta_icbper',
                    'v.venta_totaldescuento',
                    'v.venta_total',
                    'v.venta_estado_sunat',
                    'v.anulado_sunat',
                    'v.serie_modificar',
                    'v.correlativo_modificar',
                    'v.tipo_documento_modificar',
                    'c.id_tipo_documento',
                    'c.cliente_numero',
                    'c.cliente_razonsocial',
                    'c.cliente_nombre',
                    'm.abreviado as moneda_nombre',
                    DB::raw('COALESCE((SELECT SUM(dp.venta_detalle_pago_monto) FROM ventas_detalle_pagos dp WHERE dp.id_venta = v.id_venta AND dp.id_tipo_pago = 1), 0) as monto_efectivo'),
                    DB::raw('COALESCE((SELECT SUM(dp.venta_detalle_pago_monto) FROM ventas_detalle_pagos dp WHERE dp.id_venta = v.id_venta AND dp.id_tipo_pago != 1), 0) as monto_no_efectivo'),
                    DB::raw('(SELECT dp2.id_tipo_pago FROM ventas_detalle_pagos dp2 WHERE dp2.id_venta = v.id_venta ORDER BY dp2.venta_detalle_pago_monto DESC LIMIT 1) as primer_tipo_pago_id'),
                    DB::raw('(SELECT tp2.tipo_pago_nombre FROM ventas_detalle_pagos dp2 JOIN tipo_pago tp2 ON tp2.id_tipo_pago = dp2.id_tipo_pago WHERE dp2.id_venta = v.id_venta ORDER BY dp2.venta_detalle_pago_monto DESC LIMIT 1) as primer_tipo_pago_nombre'),
                    DB::raw('(SELECT COUNT(*) FROM ventas_detalle_pagos dp2 WHERE dp2.id_venta = v.id_venta) as num_operaciones')
                )
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->join('monedas as m', 'm.id_moneda', '=', 'v.id_moneda')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '07', '08'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

            if ($idSucursal > 0) {
                $query->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa > 0) {
                $query->where('v.id_empresa', $idEmpresa);
            }

            $ventas = $query->orderBy('v.venta_tipo')->orderBy('v.venta_fecha')->get();

            $facturas      = $ventas->where('venta_tipo', '01')->values();
            $boletas       = $ventas->where('venta_tipo', '03')->values();
            $notasCredito  = $ventas->where('venta_tipo', '07')->values();
            $notasDebito   = $ventas->where('venta_tipo', '08')->values();

            // ── Helpers ───────────────────────────────────────────────
            $tipoDocLabel = fn($v) => $v->id_tipo_documento == 4 ? 'RUC' : 'DNI';
            $razonSocial  = fn($v) => $v->id_tipo_documento == 4 ? $v->cliente_razonsocial : $v->cliente_nombre;
            $estadoLabel  = function($v) {
                if ($v->anulado_sunat) return 'ANULADO';
                return $v->venta_estado_sunat ? 'ACEPTADO' : 'PENDIENTE';
            };
            $subtotal = fn($v) => round(
                $v->venta_totalgravada + $v->venta_totalexonerada + $v->venta_totalinafecta + $v->venta_totaligv + $v->venta_icbper,
                2
            );
            $codigoSunat = function(?int $idTipoPago, ?string $tipoPagoNombre): string {
                $mapa = [
                    1 => '009', // EFECTIVO
                    2 => '005', // TARJETA DÉBITO
                    3 => '006', // TARJETA CRÉDITO
                    8 => '003', // TRANSFERENCIA BANCARIA
                    9 => '001', // DEPÓSITO
                ];
                if ($idTipoPago && isset($mapa[$idTipoPago])) {
                    return $mapa[$idTipoPago];
                }
                return $tipoPagoNombre ?? '';
            };

            // ── PhpSpreadsheet ────────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Ventas');

            // Estilos
            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 11, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloGrupo = [
                'font'      => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF8EA9C4']]],
            ];
            $estiloEncabezado = [
                'font'      => ['bold' => true, 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
            ];
            $estiloSeccion = [
                'font'      => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
            ];
            $estiloDato = [
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8, 'name' => 'Arial'],
            ];
            $estiloFilaPar = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]];

            // ── Fila 1: Título ────────────────────────────────────────
            $titulo = 'REPORTE DE VENTAS DESDE: ' . $desde . ' HASTA ' . $hasta;
            $sheet->mergeCells('A1:Y1');
            $sheet->setCellValue('A1', $titulo);
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(16);

            // ── Fila 2: Agrupadores de columnas ──────────────────────
            $sheet->mergeCells('A2:R2');
            $sheet->setCellValue('A2', 'COMPROBANTE');
            $sheet->getStyle('A2:R2')->applyFromArray($estiloGrupo);

            $sheet->mergeCells('S2:V2');
            $sheet->setCellValue('S2', 'DOCUMENTO DE REFERENCIA');
            $sheet->getStyle('S2:V2')->applyFromArray($estiloGrupo);

            $sheet->mergeCells('W2:Y2');
            $sheet->setCellValue('W2', 'MEDIO DE PAGO');
            $sheet->getStyle('W2:Y2')->applyFromArray($estiloGrupo);

            $sheet->getRowDimension(2)->setRowHeight(14);

            // ── Fila 3: Encabezados de columnas ──────────────────────
            $encabezados = [
                'A' => 'FECHA',         'B' => 'CODIGO',       'C' => 'SERIE',
                'D' => 'NUMERO',        'E' => "TIPO\nDOCUMENTOS", 'F' => 'RUC/DNI',
                'G' => 'RAZON SOCIAL',  'H' => 'MONEDA',       'I' => 'GRAVADO',
                'J' => 'EXONERADO',     'K' => 'ICBPER',       'L' => 'IGV',
                'M' => 'SUBTOTAL',      'N' => 'DESCUENTO',    'O' => 'EFECTIVO',
                'P' => "TRANSFERENCIA\nQR", 'Q' => 'TOTAL',    'R' => 'ESTADO',
                'S' => 'FECHA',         'T' => "TIPO\nDOCUMENTO", 'U' => 'SERIE',
                'V' => 'NUMERO',        'W' => 'CODIGO',       'X' => "N° OPERACION",
                'Y' => "ELEMENTO\nCONTABLE",
            ];
            foreach ($encabezados as $col => $texto) {
                $sheet->setCellValue("{$col}3", $texto);
            }
            $sheet->getStyle('A3:Y3')->applyFromArray($estiloEncabezado);
            $sheet->getRowDimension(3)->setRowHeight(28);

            // ── Anchos de columnas ────────────────────────────────────
            $anchos = [
                'A'=>12,'B'=>8,'C'=>8,'D'=>10,'E'=>10,'F'=>14,'G'=>36,
                'H'=>8,'I'=>10,'J'=>10,'K'=>8,'L'=>10,'M'=>10,'N'=>10,
                'O'=>10,'P'=>12,'Q'=>10,'R'=>12,'S'=>12,'T'=>10,'U'=>8,'V'=>10,
                'W'=>10,'X'=>14,'Y'=>14,
            ];
            foreach ($anchos as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            // ── Helper: escribir sección ──────────────────────────────
            $fila = 4;
            $escribirSeccion = function(string $titulo, $registros, string $tipoDoc) use (
                &$fila, $sheet, $estiloSeccion, $estiloDato, $estiloFilaPar,
                $tipoDocLabel, $razonSocial, $estadoLabel, $subtotal, $codigoSunat
            ) {
                // Cabecera de sección
                $sheet->mergeCells("A{$fila}:Y{$fila}");
                $sheet->setCellValue("A{$fila}", $titulo);
                $sheet->getStyle("A{$fila}:Y{$fila}")->applyFromArray($estiloSeccion);
                $sheet->getRowDimension($fila)->setRowHeight(14);
                $fila++;

                foreach ($registros as $idx => $v) {
                    $sheet->setCellValue("A{$fila}", date('d-m-Y', strtotime($v->venta_fecha)));
                    $sheet->setCellValue("B{$fila}", $tipoDoc);
                    $sheet->setCellValue("C{$fila}", $v->venta_serie);
                    $sheet->setCellValue("D{$fila}", $v->venta_correlativo);
                    $sheet->setCellValue("E{$fila}", $tipoDocLabel($v));
                    $sheet->setCellValue("F{$fila}", $v->cliente_numero);
                    $sheet->setCellValue("G{$fila}", $razonSocial($v));
                    $sheet->setCellValue("H{$fila}", strtoupper($v->moneda_nombre));
                    $sheet->setCellValue("I{$fila}", (float) $v->venta_totalgravada);
                    $sheet->setCellValue("J{$fila}", (float) $v->venta_totalexonerada);
                    $sheet->setCellValue("K{$fila}", (float) $v->venta_icbper);
                    $sheet->setCellValue("L{$fila}", (float) $v->venta_totaligv);
                    $sheet->setCellValue("M{$fila}", $subtotal($v));
                    $sheet->setCellValue("N{$fila}", (float) $v->venta_totaldescuento);
                    $sheet->setCellValue("O{$fila}", (float) $v->monto_efectivo);
                    $sheet->setCellValue("P{$fila}", (float) $v->monto_no_efectivo);
                    $sheet->setCellValue("Q{$fila}", (float) $v->venta_total);
                    $sheet->setCellValue("R{$fila}", $estadoLabel($v));
                    // Documento de referencia (para NC/ND)
                    $sheet->setCellValue("S{$fila}", $v->tipo_documento_modificar ? date('d-m-Y', strtotime($v->venta_fecha)) : '');
                    $sheet->setCellValue("T{$fila}", $v->tipo_documento_modificar ?? '');
                    $sheet->setCellValue("U{$fila}", $v->serie_modificar ?? '');
                    $sheet->setCellValue("V{$fila}", $v->correlativo_modificar ?? '');
                    // Medio de pago
                    $sheet->setCellValue("W{$fila}", $codigoSunat((int) $v->primer_tipo_pago_id, $v->primer_tipo_pago_nombre));
                    $sheet->setCellValue("X{$fila}", (int) $v->num_operaciones);
                    $sheet->setCellValue("Y{$fila}", '70121');

                    $sheet->getStyle("A{$fila}:Y{$fila}")->applyFromArray($estiloDato);
                    if ($idx % 2 !== 0) {
                        $sheet->getStyle("A{$fila}:Y{$fila}")->applyFromArray($estiloFilaPar);
                    }
                    $sheet->getRowDimension($fila)->setRowHeight(13);
                    $fila++;
                }
            };

            $escribirSeccion('FACTURAS',           $facturas,     '01');
            $escribirSeccion('BOLETAS',             $boletas,      '03');
            $escribirSeccion('NOTAS DE CREDITOS',   $notasCredito, '07');
            $escribirSeccion('NOTAS DE DEBITOS',    $notasDebito,  '08');

            // ── Descargar ─────────────────────────────────────────────
            $nombreArchivo = 'Reporte_Ventas_Para_Estudio_' . date('Ymd_His') . '.xlsx';
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

    public function control_pagos_de_cuotas()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("control_pagos_de_cuotas");
            return view('reporte.control_pagos_de_cuotas', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function ventas_por_vendedor()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("ventas_por_vendedor");
            return view('reporte.ventas_por_vendedor', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function ventas_por_cliente()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("ventas_por_cliente");
            return view('reporte.ventas_por_cliente', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function productos_mas_vendidos()
    {
        try {

            $opciones = $this->submenu->optiones_por_vista("productos_mas_vendidos");
            return view('reporte.productos_mas_vendidos', compact('opciones'));
        }catch (\Exception $e){
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }


    private function obtenerDatosReporteCuotas(Request $request): array
    {
        $desde      = $request->desde      ?? null;
        $hasta      = $request->hasta      ?? null;
        $idCliente  = $request->cliente    ?? null;
        $estado     = $request->estado     ?? 'todos';
        $idEmpresa  = (int) ($request->id_empresa  ?? 0);
        $idSucursal = (int) ($request->id_sucursal ?? 0);
        $hoy        = Carbon::today()->toDateString();

        $query = DB::table('ventas_cuotas as vc')
            ->select(
                'vc.id_ventas_cuotas',
                'vc.venta_cuota_numero',
                'vc.venta_cuota_importe',
                'vc.venta_cuota_fecha',
                'vc.venta_cuota_pago',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_tipo',
                'c.cliente_nombre',
                'c.cliente_razonsocial',
                'c.cliente_numero',
                'c.id_tipo_documento',
                DB::raw('COALESCE((
                SELECT SUM(pc2.pagos_cuota_monto)
                FROM pagos_cuotas pc2
                WHERE pc2.id_ventas_cuotas = vc.id_ventas_cuotas
                AND pc2.deleted_at IS NULL
            ), 0) as total_pagado'),
                DB::raw('(
                SELECT pc3.pagos_cuota_fecha
                FROM pagos_cuotas pc3
                WHERE pc3.id_ventas_cuotas = vc.id_ventas_cuotas
                AND pc3.deleted_at IS NULL
                ORDER BY pc3.pagos_cuota_fecha DESC
                LIMIT 1
            ) as ultimo_pago'),
                DB::raw('(
                SELECT tp.tipo_pago_nombre
                FROM pagos_cuotas pc4
                JOIN tipo_pago tp ON tp.id_tipo_pago = pc4.id_tipo_pago
                WHERE pc4.id_ventas_cuotas = vc.id_ventas_cuotas
                AND pc4.deleted_at IS NULL
                ORDER BY pc4.pagos_cuota_fecha DESC
                LIMIT 1
            ) as tipo_pago_nombre')
            )
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->where('vc.venta_cuota_estado', 1)
            ->whereBetween('vc.venta_cuota_fecha', [$desde, $hasta]);

        // ── Filtro empresa / sucursal ─────────────────────────
        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa > 0) {
            $query->where('v.id_empresa', $idEmpresa);
        }

        if ($idCliente) {
            $query->where('v.id_clientes', $idCliente);
        }

        switch ($estado) {
            case 'pagadas':
                $query->where('vc.venta_cuota_pago', 1);
                break;
            case 'vencidas':
                $query->where('vc.venta_cuota_pago', 0)
                    ->where('vc.venta_cuota_fecha', '<', $hoy);
                break;
            case 'por_vencer':
                $query->where('vc.venta_cuota_pago', 0)
                    ->whereBetween('vc.venta_cuota_fecha', [$hoy, Carbon::today()->addDays(7)->toDateString()]);
                break;
            case 'pendientes':
                $query->where('vc.venta_cuota_pago', 0);
                break;
        }

        $cuotas = $query->orderBy('vc.venta_cuota_fecha', 'asc')->get();

        // ── Resumen ───────────────────────────────────────────
        $totalPagado    = $cuotas->where('venta_cuota_pago', 1)->sum('venta_cuota_importe');
        $totalPendiente = $cuotas->where('venta_cuota_pago', 0)->sum('venta_cuota_importe');
        $totalVencido   = $cuotas->where('venta_cuota_pago', 0)->filter(fn($c) => $c->venta_cuota_fecha < $hoy)->sum('venta_cuota_importe');
        $cantPagadas    = $cuotas->where('venta_cuota_pago', 1)->count();
        $cantPendientes = $cuotas->where('venta_cuota_pago', 0)->count();
        $cantVencidas   = $cuotas->where('venta_cuota_pago', 0)->filter(fn($c) => $c->venta_cuota_fecha < $hoy)->count();

        $nombreCliente = 'TODOS';
        if ($idCliente) {
            $cli = DB::table('clientes')->where('id_clientes', $idCliente)->first();
            if ($cli) $nombreCliente = $cli->cliente_razonsocial ?? $cli->cliente_nombre;
        }

        // ── Info empresa/sucursal para cabecera del reporte ──
        $infoEmpresa  = $idEmpresa  > 0 ? DB::table('empresa')->where('id_empresa',   $idEmpresa)->first()  : null;
        $infoSucursal = $idSucursal > 0 ? DB::table('sucursals')->where('id_sucursal', $idSucursal)->first() : null;

        $estadoLabel = match($estado) {
            'pagadas'    => 'Pagadas',
            'vencidas'   => 'Vencidas',
            'por_vencer' => 'Por vencer (7 días)',
            'pendientes' => 'Pendientes',
            default      => 'Todos',
        };

        return compact(
            'cuotas', 'desde', 'hasta', 'nombreCliente', 'estadoLabel', 'hoy',
            'totalPagado', 'totalPendiente', 'totalVencido',
            'cantPagadas', 'cantPendientes', 'cantVencidas',
            'infoEmpresa', 'infoSucursal'
        );
    }

// ── ETIQUETA DE ESTADO ────────────────────────────────────────
    private function etiquetaEstadoCuota($cuota, string $hoy): string
    {
        if ($cuota->venta_cuota_pago == 1) return 'Pagada';
        if ($cuota->venta_cuota_fecha < $hoy) return 'Vencida';
        $dias = Carbon::parse($cuota->venta_cuota_fecha)->diffInDays(Carbon::today(), false);
        if (abs($dias) <= 7) return 'Por vencer';
        return 'Pendiente';
    }


// ============================================================
//  PDF — A4 Vertical (180mm útil)
// ============================================================
    public function imprimirPdfPagosCuotas(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCuotas($request);

            $fechaDesde = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Control de Pagos de Cuotas'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(18, 4, utf8_decode('Cliente:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(80, 4, utf8_decode($d['nombreCliente']), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Estado:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($d['estadoLabel']), 0, 1, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(18, 4, utf8_decode('Desde:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(40, 4, utf8_decode($fechaDesde), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Hasta:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(40, 4, utf8_decode($fechaHasta), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // ── Resumen ───────────────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('RESUMEN'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);

            $resumenFilas = [
                ['Cuotas Pagadas',    $d['cantPagadas'] . ' cuota(s)',    'S/ ' . number_format($d['totalPagado'], 2)],
                ['Cuotas Vencidas',   $d['cantVencidas'] . ' cuota(s)',   'S/ ' . number_format($d['totalVencido'], 2)],
                ['Cuotas Pendientes', $d['cantPendientes'] . ' cuota(s)', 'S/ ' . number_format($d['totalPendiente'], 2)],
            ];

            $pdf->SetFont('Helvetica', '', 7);
            foreach ($resumenFilas as $i => $fila) {
                $pdf->SetFillColor($i % 2 == 0 ? 242 : 255, $i % 2 == 0 ? 242 : 255, $i % 2 == 0 ? 242 : 255);
                $pdf->Cell(70, 5, utf8_decode($fila[0]), 1, 0, 'L', 1);
                $pdf->Cell(55, 5, utf8_decode($fila[1]), 1, 0, 'C', 1);
                $pdf->Cell(55, 5, utf8_decode($fila[2]), 1, 1, 'R', 1);
            }

            $pdf->Ln(5);

            // ── Encabezado tabla ──────────────────────────────────
            // Anchos: 8+30+24+12+22+18+18+20+28 = 180mm
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(8,  8, utf8_decode('N°'),           1, 0, 'C', 1);
            $pdf->Cell(30, 8, utf8_decode('Cliente'),      1, 0, 'C', 1);
            $pdf->Cell(24, 8, utf8_decode('Comprobante'),  1, 0, 'C', 1);
            $pdf->Cell(12, 8, utf8_decode('Cuota'),        1, 0, 'C', 1);
            $pdf->Cell(22, 8, utf8_decode('Importe'),      1, 0, 'C', 1);
            $pdf->Cell(18, 8, utf8_decode('Pagado'),       1, 0, 'C', 1);
            $pdf->Cell(18, 8, utf8_decode('Saldo'),        1, 0, 'C', 1);
            $pdf->Cell(20, 8, utf8_decode('Vencimiento'),  1, 0, 'C', 1);
            $pdf->Cell(28, 8, utf8_decode('Estado'),       1, 1, 'C', 1);

            $pdf->SetWidths([8, 30, 24, 12, 22, 18, 18, 20, 28]);
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetTextColor(0, 0, 0);

            $n = 1;
            foreach ($d['cuotas'] as $c) {
                $cliente  = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;
                $saldo    = max(0, $c->venta_cuota_importe - $c->total_pagado);
                $estadoTx = $this->etiquetaEstadoCuota($c, $d['hoy']);

                // Color por estado
                if ($c->venta_cuota_pago == 1) {
                    $pdf->SetFillColor(212, 244, 232); // verde claro
                } elseif ($c->venta_cuota_fecha < $d['hoy']) {
                    $pdf->SetFillColor(253, 232, 232); // rojo claro
                } elseif (Carbon::parse($c->venta_cuota_fecha)->diffInDays(Carbon::today(), false) >= -7) {
                    $pdf->SetFillColor(255, 243, 205); // amarillo claro
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                $pdf->Row([
                    $n++,
                    utf8_decode(mb_substr($cliente, 0, 18)),
                    utf8_decode(match($c->venta_tipo) { '01'=>'FAC','03'=>'BOL', default=>$c->venta_tipo }) . ' ' . $c->venta_serie . '-' . $c->venta_correlativo,
                    str_pad($c->venta_cuota_numero, 3, '0', STR_PAD_LEFT),
                    'S/ ' . number_format($c->venta_cuota_importe, 2),
                    'S/ ' . number_format($c->total_pagado, 2),
                    'S/ ' . number_format($saldo, 2),
                    date('d/m/Y', strtotime($c->venta_cuota_fecha)),
                    utf8_decode($estadoTx),
                ]);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Control_Cuotas_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }


// ============================================================
//  EXCEL
// ============================================================
    public function imprimirExcelPagosCuotas(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCuotas($request);

            $fechaDesde = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Control de Cuotas');

            // Estilos
            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEtiqueta = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial']];
            $estiloValor    = ['font' => ['size' => 9, 'name' => 'Arial']];
            $estiloEncabezado = [
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

            // Fila 1: Título
            $sheet->mergeCells('A1:I1');
            $sheet->setCellValue('A1', 'Reporte de Control de Pagos de Cuotas');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // Filtros
            $filtros = [
                ['Cliente:', $d['nombreCliente'], 'Estado:', $d['estadoLabel']],
                ['Desde:',   $fechaDesde,         'Hasta:',  $fechaHasta],
            ];
            $fila = 2;
            foreach ($filtros as $row) {
                $sheet->setCellValue("A{$fila}", $row[0]); $sheet->getStyle("A{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->setCellValue("B{$fila}", $row[1]); $sheet->getStyle("B{$fila}")->applyFromArray($estiloValor);
                $sheet->setCellValue("F{$fila}", $row[2]); $sheet->getStyle("F{$fila}")->applyFromArray($estiloEtiqueta);
                $sheet->setCellValue("G{$fila}", $row[3]); $sheet->getStyle("G{$fila}")->applyFromArray($estiloValor);
                $fila++;
            }

            // Resumen
            $fila = 5;
            $sheet->mergeCells("A{$fila}:I{$fila}");
            $sheet->setCellValue("A{$fila}", 'RESUMEN');
            $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getRowDimension($fila)->setRowHeight(14);

            $fila = 6;
            $resumenData = [
                ['Cuotas Pagadas',    $d['cantPagadas'],    'S/ ' . number_format($d['totalPagado'], 2)],
                ['Cuotas Vencidas',   $d['cantVencidas'],   'S/ ' . number_format($d['totalVencido'], 2)],
                ['Cuotas Pendientes', $d['cantPendientes'], 'S/ ' . number_format($d['totalPendiente'], 2)],
            ];
            foreach ($resumenData as $i => $r) {
                $sheet->setCellValue("A{$fila}", $r[0]);
                $sheet->setCellValue("B{$fila}", $r[1]);
                $sheet->setCellValue("C{$fila}", $r[2]);
                $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($estiloBorde);
                if ($i % 2 == 0) {
                    $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]);
                }
                $fila++;
            }

            // Encabezados tabla
            $filaEnc = 10;
            $encabezados = ['A'=>'#','B'=>'Cliente','C'=>'N° Documento','D'=>'Comprobante','E'=>'N° Cuota','F'=>'Importe','G'=>'Pagado','H'=>'Saldo','I'=>'Vencimiento','J'=>'Último Pago','K'=>'Tipo Pago','L'=>'Estado'];
            foreach ($encabezados as $col => $texto) {
                $sheet->setCellValue("{$col}{$filaEnc}", $texto);
            }
            $sheet->getStyle("A{$filaEnc}:L{$filaEnc}")->applyFromArray($estiloEncabezado);
            $sheet->getRowDimension($filaEnc)->setRowHeight(22);

            // Datos
            $filaData = 11;
            $n = 1;
            foreach ($d['cuotas'] as $c) {
                $cliente  = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;
                $saldo    = max(0, $c->venta_cuota_importe - $c->total_pagado);
                $estadoTx = $this->etiquetaEstadoCuota($c, $d['hoy']);
                $tipoComp = match($c->venta_tipo) { '01'=>'FACTURA','03'=>'BOLETA', default=>$c->venta_tipo };

                $sheet->setCellValue("A{$filaData}", $n++);
                $sheet->setCellValue("B{$filaData}", $cliente);
                $sheet->setCellValue("C{$filaData}", $c->cliente_numero);
                $sheet->setCellValue("D{$filaData}", $tipoComp . ' ' . $c->venta_serie . '-' . $c->venta_correlativo);
                $sheet->setCellValue("E{$filaData}", str_pad($c->venta_cuota_numero, 3, '0', STR_PAD_LEFT));
                $sheet->setCellValue("F{$filaData}", 'S/ ' . number_format($c->venta_cuota_importe, 2));
                $sheet->setCellValue("G{$filaData}", 'S/ ' . number_format($c->total_pagado, 2));
                $sheet->setCellValue("H{$filaData}", 'S/ ' . number_format($saldo, 2));
                $sheet->setCellValue("I{$filaData}", date('d/m/Y', strtotime($c->venta_cuota_fecha)));
                $sheet->setCellValue("J{$filaData}", $c->ultimo_pago ? date('d/m/Y', strtotime($c->ultimo_pago)) : '—');
                $sheet->setCellValue("K{$filaData}", $c->tipo_pago_nombre ?? '—');
                $sheet->setCellValue("L{$filaData}", $estadoTx);

                // Color por estado
                if ($c->venta_cuota_pago == 1) {
                    $sheet->getStyle("A{$filaData}:L{$filaData}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD4F4E8']]]);
                } elseif ($c->venta_cuota_fecha < $d['hoy']) {
                    $sheet->getStyle("A{$filaData}:L{$filaData}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDE8E8']]]);
                } elseif ($filaData % 2 == 0) {
                    $sheet->getStyle("A{$filaData}:L{$filaData}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]);
                }

                $sheet->getStyle("A{$filaData}:L{$filaData}")->applyFromArray($estiloBorde);
                $filaData++;
                $n;
            }

            // Anchos columnas
            $anchos = ['A'=>5,'B'=>28,'C'=>14,'D'=>22,'E'=>10,'F'=>14,'G'=>14,'H'=>14,'I'=>14,'J'=>14,'K'=>16,'L'=>14];
            foreach ($anchos as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            // Descargar
            $nombreArchivo = 'Control_Cuotas_' . date('Ymd_His') . '.xlsx';
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


// ============================================================
//  IMPORTS necesarios en tu controlador
// ============================================================
//
//  use PhpOffice\PhpSpreadsheet\Spreadsheet;
//  use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//  use PhpOffice\PhpSpreadsheet\Style\Alignment;
//  use PhpOffice\PhpSpreadsheet\Style\Border;
//  use PhpOffice\PhpSpreadsheet\Style\Fill;
//  use Illuminate\Support\Facades\DB;
//  use Illuminate\Http\Request;
//  use Carbon\Carbon;
// ============================================================

// ── MÉTODO AUXILIAR ───────────────────────────────────────────
    private function obtenerDatosReporteVendedor(Request $request): array
    {
        $desde      = $request->desde    ?? null;
        $hasta      = $request->hasta    ?? null;
        $idVendedor = $request->vendedor ?? null;
        $idEmpresa  = (int) ($request->id_empresa  ?? 0);
        $idSucursal = (int) ($request->id_sucursal ?? 0);

        // Closure reutilizable para aplicar filtro empresa/sucursal
        // sobre la tabla ventas (alias v)
        $filtrarUbicacion = function (\Illuminate\Database\Query\Builder $q) use ($idEmpresa, $idSucursal) {
            if ($idSucursal > 0) {
                $q->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa > 0) {
                $q->where('v.id_empresa', $idEmpresa);
            }
        };

        // ── Resumen por vendedor ──────────────────────────────────
        $queryResumen = DB::table('ventas as v')
            ->select(
                'u.id_users',
                'u.nombre_users',
                DB::raw('SUM(v.venta_total) as total_ventas'),
                DB::raw('COUNT(v.id_venta) as cantidad'),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '01' THEN v.venta_total ELSE 0 END) as total_facturas"),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '03' THEN v.venta_total ELSE 0 END) as total_boletas"),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '20' THEN v.venta_total ELSE 0 END) as total_nv"),
                DB::raw("COUNT(CASE WHEN v.venta_tipo = '01' THEN 1 END) as cant_facturas"),
                DB::raw("COUNT(CASE WHEN v.venta_tipo = '03' THEN 1 END) as cant_boletas"),
                DB::raw("COUNT(CASE WHEN v.venta_tipo = '20' THEN 1 END) as cant_nv"),
            )
            ->join('users as u', 'u.id_users', '=', 'v.id_users')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        $filtrarUbicacion($queryResumen);

        if ($idVendedor) {
            $queryResumen->where('v.id_users', $idVendedor);
        }

        $resumenVendedores = $queryResumen
            ->groupBy('u.id_users', 'u.nombre_users')
            ->orderByDesc('total_ventas')
            ->get();

        // ── Detalle de ventas ─────────────────────────────────────
        $detalleQuery = DB::table('ventas as v')
            ->select(
                'u.nombre_users',
                'v.venta_tipo',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_fecha',
                'v.venta_total',
                'v.id_formas_pago',
                'c.cliente_nombre',
                'c.cliente_razonsocial',
                'c.cliente_numero',
                'c.id_tipo_documento',
                'mo.simbolo',
            )
            ->join('users as u',    'u.id_users',    '=', 'v.id_users')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->join('monedas as mo', 'mo.id_moneda',  '=', 'v.id_moneda')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        $filtrarUbicacion($detalleQuery);

        if ($idVendedor) {
            $detalleQuery->where('v.id_users', $idVendedor);
        }

        $detalleVentas = $detalleQuery
            ->orderBy('u.nombre_users')
            ->orderBy('v.venta_fecha')
            ->get();

        // ── Totales y labels ──────────────────────────────────────
        $totalGeneral = $resumenVendedores->sum('total_ventas');
        $cantGeneral  = $resumenVendedores->sum('cantidad');

        $nombreVendedor = 'TODOS';
        if ($idVendedor) {
            $u = DB::table('users')->where('id_users', $idVendedor)->first();
            if ($u) $nombreVendedor = $u->nombre_users;
        }

        $fechaDesde = $desde ? date('d/m/Y', strtotime($desde)) : '-';
        $fechaHasta = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';

        // ── Info empresa/sucursal para cabecera del reporte ───────
        $infoEmpresa  = $idEmpresa  > 0 ? DB::table('empresa')->where('id_empresa',   $idEmpresa)->first()  : null;
        $infoSucursal = $idSucursal > 0 ? DB::table('sucursals')->where('id_sucursal', $idSucursal)->first() : null;

        return compact(
            'resumenVendedores', 'detalleVentas',
            'totalGeneral', 'cantGeneral',
            'nombreVendedor', 'fechaDesde', 'fechaHasta',
            'infoEmpresa', 'infoSucursal'
        );
    }


// ============================================================
//  PDF — A4 Vertical (180mm útil)
// ============================================================
    public function imprimirPdfVentasVendedor(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteVendedor($request);

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Ventas por Vendedor'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(20, 4, utf8_decode('Vendedor:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(60, 4, utf8_decode($d['nombreVendedor']), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Desde:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($d['fechaDesde']), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Hasta:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($d['fechaHasta']), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // ── SECCIÓN 1: Resumen por vendedor ───────────────────
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('RESUMEN POR VENDEDOR'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(1);

            // Encabezado resumen
            // Anchos: 8+40+22+22+22+22+22+22 = 180mm
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(70, 90, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(8, 7, utf8_decode('#'), 1, 0, 'C', 1);
            $pdf->Cell(40, 7, utf8_decode('Vendedor'), 1, 0, 'C', 1);
            $pdf->Cell(26, 7, utf8_decode('Total'), 1, 0, 'C', 1);
            $pdf->Cell(26, 7, utf8_decode('Facturas'), 1, 0, 'C', 1);
            $pdf->Cell(26, 7, utf8_decode('Boletas'), 1, 0, 'C', 1);
            $pdf->Cell(26, 7, utf8_decode('Notas Venta'), 1, 0, 'C', 1);
            $pdf->Cell(14, 7, utf8_decode('Comp.'), 1, 0, 'C', 1);
            $pdf->Cell(14, 7, utf8_decode('%'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', '', 6);

            foreach ($d['resumenVendedores'] as $i => $v) {
                $porcentaje = $d['totalGeneral'] > 0
                    ? round(($v->total_ventas / $d['totalGeneral']) * 100, 1)
                    : 0;
                $fill = $i % 2 == 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $pdf->Cell(8, 5, $i + 1, 1, 0, 'C', 1);
                $pdf->Cell(40, 5, utf8_decode(mb_substr($v->nombre_users, 0, 22)), 1, 0, 'L', 1);
                $pdf->Cell(26, 5, 'S/ ' . number_format($v->total_ventas, 2), 1, 0, 'R', 1);
                $pdf->Cell(26, 5, 'S/ ' . number_format($v->total_facturas, 2), 1, 0, 'R', 1);
                $pdf->Cell(26, 5, 'S/ ' . number_format($v->total_boletas, 2), 1, 0, 'R', 1);
                $pdf->Cell(26, 5, 'S/ ' . number_format($v->total_nv, 2), 1, 0, 'R', 1);
                $pdf->Cell(14, 5, $v->cantidad, 1, 0, 'C', 1);
                $pdf->Cell(14, 5, $porcentaje . '%', 1, 1, 'C', 1);
            }

            // Fila total
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(48, 5, utf8_decode('TOTAL GENERAL'), 1, 0, 'L', 1);
            $pdf->Cell(26, 5, 'S/ ' . number_format($d['totalGeneral'], 2), 1, 0, 'R', 1);
            $pdf->Cell(26, 5, '', 1, 0, 'C', 1);
            $pdf->Cell(26, 5, '', 1, 0, 'C', 1);
            $pdf->Cell(26, 5, '', 1, 0, 'C', 1);
            $pdf->Cell(14, 5, $d['cantGeneral'], 1, 0, 'C', 1);
            $pdf->Cell(14, 5, '100%', 1, 1, 'C', 1);

            $pdf->Ln(6);

            // ── SECCIÓN 2: Detalle de ventas ──────────────────────
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('DETALLE DE VENTAS'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(1);

            // Encabezado detalle
            // Anchos: 8+30+14+24+42+16+22+24 = 180mm
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(70, 90, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(8, 7, utf8_decode('#'), 1, 0, 'C', 1);
            $pdf->Cell(30, 7, utf8_decode('Vendedor'), 1, 0, 'C', 1);
            $pdf->Cell(16, 7, utf8_decode('Fecha'), 1, 0, 'C', 1);
            $pdf->Cell(14, 7, utf8_decode('Tipo'), 1, 0, 'C', 1);
            $pdf->Cell(24, 7, utf8_decode('Comprobante'), 1, 0, 'C', 1);
            $pdf->Cell(42, 7, utf8_decode('Cliente'), 1, 0, 'C', 1);
            $pdf->Cell(16, 7, utf8_decode('F. Pago'), 1, 0, 'C', 1);
            $pdf->Cell(30, 7, utf8_decode('Total'), 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetWidths([8, 30, 16, 14, 24, 42, 16, 30]);
            $pdf->SetFont('Helvetica', '', 6);

            $n = 1;
            foreach ($d['detalleVentas'] as $venta) {
                $cliente = $venta->id_tipo_documento == 4 ? $venta->cliente_razonsocial : $venta->cliente_nombre;
                $tipoComp = match ($venta->venta_tipo) {
                    '01' => 'FAC',
                    '03' => 'BOL',
                    '20' => 'NV',
                    default => $venta->venta_tipo
                };
                $formaPago = $venta->id_formas_pago == 1 ? 'Contado' : 'Crédito';
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Row([
                    $n++,
                    utf8_decode(mb_substr($venta->nombre_users, 0, 18)),
                    date('d/m/Y', strtotime($venta->venta_fecha)),
                    utf8_decode($tipoComp),
                    utf8_decode($venta->venta_serie . '-' . $venta->venta_correlativo),
                    utf8_decode(mb_substr($cliente, 0, 26)),
                    utf8_decode($formaPago),
                    $venta->simbolo . number_format($venta->venta_total, 2),
                ]);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Ventas_Vendedor_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }


// ============================================================
//  EXCEL — 2 hojas: Resumen + Detalle
// ============================================================
    public function imprimirExcelVentasVendedor(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteVendedor($request);

            $spreadsheet = new Spreadsheet();

            // Estilos reutilizables
            $estiloTitulo = [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEtiqueta = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial']];
            $estiloValor = ['font' => ['size' => 9, 'name' => 'Arial']];
            $estiloSeccion = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEncabezado = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloBorde = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 8, 'name' => 'Arial'],
            ];
            $estiloTotalFila = [
                'font' => ['bold' => true, 'size' => 8, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
            ];

            // ══════════════════════════════════════════════════════
            //  HOJA 1 — Resumen por vendedor
            // ══════════════════════════════════════════════════════
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen');

            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'Reporte de Ventas por Vendedor');
            $sheet->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            $sheet->setCellValue('A2', 'Vendedor:');
            $sheet->getStyle('A2')->applyFromArray($estiloEtiqueta);
            $sheet->setCellValue('B2', $d['nombreVendedor']);
            $sheet->getStyle('B2')->applyFromArray($estiloValor);
            $sheet->setCellValue('E2', 'Desde:');
            $sheet->getStyle('E2')->applyFromArray($estiloEtiqueta);
            $sheet->setCellValue('F2', $d['fechaDesde']);
            $sheet->getStyle('F2')->applyFromArray($estiloValor);
            $sheet->setCellValue('E3', 'Hasta:');
            $sheet->getStyle('E3')->applyFromArray($estiloEtiqueta);
            $sheet->setCellValue('F3', $d['fechaHasta']);
            $sheet->getStyle('F3')->applyFromArray($estiloValor);

            // Sección resumen
            $sheet->mergeCells('A5:H5');
            $sheet->setCellValue('A5', 'RESUMEN POR VENDEDOR');
            $sheet->getStyle('A5:H5')->applyFromArray($estiloSeccion);
            $sheet->getRowDimension(5)->setRowHeight(14);

            // Encabezados
            $encResumen = ['A' => '#', 'B' => 'Vendedor', 'C' => 'Total Ventas', 'D' => 'Facturas', 'E' => 'Boletas', 'F' => 'Notas de Venta', 'G' => 'Comprobantes', 'H' => '% del Total'];
            foreach ($encResumen as $col => $texto) {
                $sheet->setCellValue("{$col}6", $texto);
            }
            $sheet->getStyle('A6:H6')->applyFromArray($estiloEncabezado);
            $sheet->getRowDimension(6)->setRowHeight(20);

            $fila = 7;
            foreach ($d['resumenVendedores'] as $i => $v) {
                $porcentaje = $d['totalGeneral'] > 0
                    ? round(($v->total_ventas / $d['totalGeneral']) * 100, 1) . '%'
                    : '0%';
                $sheet->setCellValue("A{$fila}", $i + 1);
                $sheet->setCellValue("B{$fila}", $v->nombre_users);
                $sheet->setCellValue("C{$fila}", 'S/ ' . number_format($v->total_ventas, 2));
                $sheet->setCellValue("D{$fila}", 'S/ ' . number_format($v->total_facturas, 2));
                $sheet->setCellValue("E{$fila}", 'S/ ' . number_format($v->total_boletas, 2));
                $sheet->setCellValue("F{$fila}", 'S/ ' . number_format($v->total_nv, 2));
                $sheet->setCellValue("G{$fila}", $v->cantidad);
                $sheet->setCellValue("H{$fila}", $porcentaje);
                $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloBorde);
                if ($fila % 2 == 0) {
                    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]);
                }
                $fila++;
            }

            // Fila total
            $sheet->setCellValue("A{$fila}", 'TOTAL');
            $sheet->setCellValue("B{$fila}", '');
            $sheet->setCellValue("C{$fila}", 'S/ ' . number_format($d['totalGeneral'], 2));
            $sheet->setCellValue("D{$fila}", '');
            $sheet->setCellValue("E{$fila}", '');
            $sheet->setCellValue("F{$fila}", '');
            $sheet->setCellValue("G{$fila}", $d['cantGeneral']);
            $sheet->setCellValue("H{$fila}", '100%');
            $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloTotalFila);

            $anchos = ['A' => 5, 'B' => 28, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 18, 'G' => 14, 'H' => 12];
            foreach ($anchos as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            // ══════════════════════════════════════════════════════
            //  HOJA 2 — Detalle de ventas
            // ══════════════════════════════════════════════════════
            $sheetDet = $spreadsheet->createSheet();
            $sheetDet->setTitle('Detalle de Ventas');

            $sheetDet->mergeCells('A1:H1');
            $sheetDet->setCellValue('A1', 'DETALLE DE VENTAS POR VENDEDOR');
            $sheetDet->getStyle('A1:H1')->applyFromArray($estiloSeccion);
            $sheetDet->getRowDimension(1)->setRowHeight(16);

            $encDetalle = ['A' => '#', 'B' => 'Vendedor', 'C' => 'Fecha', 'D' => 'Tipo', 'E' => 'Comprobante', 'F' => 'Cliente', 'G' => 'Forma de Pago', 'H' => 'Total'];
            foreach ($encDetalle as $col => $texto) {
                $sheetDet->setCellValue("{$col}2", $texto);
            }
            $sheetDet->getStyle('A2:H2')->applyFromArray($estiloEncabezado);
            $sheetDet->getRowDimension(2)->setRowHeight(20);

            $filaDet = 3;
            $n = 1;
            foreach ($d['detalleVentas'] as $venta) {
                $cliente = $venta->id_tipo_documento == 4 ? $venta->cliente_razonsocial : $venta->cliente_nombre;
                $tipoComp = match ($venta->venta_tipo) {
                    '01' => 'FACTURA',
                    '03' => 'BOLETA',
                    '20' => 'NOTA VENTA',
                    default => $venta->venta_tipo
                };
                $formaPago = $venta->id_formas_pago == 1 ? 'Contado' : 'Crédito';

                $sheetDet->setCellValue("A{$filaDet}", $n++);
                $sheetDet->setCellValue("B{$filaDet}", $venta->nombre_users);
                $sheetDet->setCellValue("C{$filaDet}", date('d/m/Y H:i', strtotime($venta->venta_fecha)));
                $sheetDet->setCellValue("D{$filaDet}", $tipoComp);
                $sheetDet->setCellValue("E{$filaDet}", $venta->venta_serie . '-' . $venta->venta_correlativo);
                $sheetDet->setCellValue("F{$filaDet}", $cliente);
                $sheetDet->setCellValue("G{$filaDet}", $formaPago);
                $sheetDet->setCellValue("H{$filaDet}", $venta->simbolo . number_format($venta->venta_total, 2));
                $sheetDet->getStyle("A{$filaDet}:H{$filaDet}")->applyFromArray($estiloBorde);
                if ($filaDet % 2 == 0) {
                    $sheetDet->getStyle("A{$filaDet}:H{$filaDet}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]);
                }
                $filaDet++;
            }

            $anchosDet = ['A' => 5, 'B' => 25, 'C' => 18, 'D' => 14, 'E' => 20, 'F' => 35, 'G' => 14, 'H' => 14];
            foreach ($anchosDet as $col => $w) {
                $sheetDet->getColumnDimension($col)->setWidth($w);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $nombreArchivo = 'Ventas_Vendedor_' . date('Ymd_His') . '.xlsx';
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

    private function obtenerDatosReporteCliente(Request $request): array
    {
        $desde     = $request->desde   ?? null;
        $hasta     = $request->hasta   ?? null;
        $idCliente = $request->cliente ?? null;
        $idEmpresa = (int) ($request->id_empresa ?? 0);
        $hoy       = Carbon::today()->toDateString();

        // Closure reutilizable para filtrar por empresa en tabla ventas (alias v)
        $filtrarEmpresa = function (\Illuminate\Database\Query\Builder $q) use ($idEmpresa) {
            if ($idEmpresa > 0) {
                $q->where('v.id_empresa', $idEmpresa);
            }
        };

        // ── Resumen por cliente ───────────────────────────────────
        $queryResumen = DB::table('ventas as v')
            ->select(
                'c.id_clientes',
                'c.cliente_nombre',
                'c.cliente_razonsocial',
                'c.cliente_numero',
                'c.id_tipo_documento',
                DB::raw('SUM(v.venta_total) as total_ventas'),
                DB::raw('COUNT(v.id_venta) as cantidad'),
                DB::raw('ROUND(AVG(v.venta_total), 2) as ticket_promedio'),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '01' THEN v.venta_total ELSE 0 END) as total_facturas"),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '03' THEN v.venta_total ELSE 0 END) as total_boletas"),
                DB::raw("SUM(CASE WHEN v.venta_tipo = '20' THEN v.venta_total ELSE 0 END) as total_nv"),
                DB::raw('COALESCE((
                SELECT SUM(vc2.venta_cuota_importe)
                FROM ventas_cuotas vc2
                JOIN ventas v2 ON v2.id_venta = vc2.id_venta
                WHERE v2.id_clientes = c.id_clientes
                  AND v2.id_empresa  = ' . intval($idEmpresa) . '
                  AND vc2.venta_cuota_pago   = 0
                  AND vc2.venta_cuota_estado = 1
            ), 0) as deuda_pendiente'),
            )
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        $filtrarEmpresa($queryResumen);
        if ($idCliente) $queryResumen->where('c.id_clientes', $idCliente);

        $resumenClientes = $queryResumen
            ->groupBy('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial',
                'c.cliente_numero', 'c.id_tipo_documento')
            ->orderByDesc('total_ventas')
            ->get();

        // ── Detalle de ventas ─────────────────────────────────────
        $queryDetalle = DB::table('ventas as v')
            ->select(
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento',
                'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_fecha', 'v.venta_total', 'v.id_formas_pago',
                'u.nombre_users', 'mo.simbolo',
            )
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->join('users as u',    'u.id_users',    '=', 'v.id_users')
            ->join('monedas as mo', 'mo.id_moneda',  '=', 'v.id_moneda')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        $filtrarEmpresa($queryDetalle);
        if ($idCliente) $queryDetalle->where('v.id_clientes', $idCliente);

        $detalleVentas = $queryDetalle
            ->orderBy('c.cliente_nombre')
            ->orderBy('v.venta_fecha')
            ->get();

        // ── Detalle de productos ──────────────────────────────────
        $queryProductos = DB::table('ventas_detalle as vd')
            ->select(
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento',
                'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre',
                DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_importe'),
                DB::raw('COUNT(DISTINCT v.id_venta) as en_comprobantes'),
            )
            ->join('ventas as v',      'v.id_venta',    '=', 'vd.id_venta')
            ->join('clientes as c',    'c.id_clientes', '=', 'v.id_clientes')
            ->join('productos as p',   'p.id_pro',      '=', 'vd.id_pro')
            ->join('categorias as ca', 'ca.id_ca',      '=', 'p.id_ca')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        $filtrarEmpresa($queryProductos);
        if ($idCliente) $queryProductos->where('v.id_clientes', $idCliente);

        $detalleProductos = $queryProductos
            ->groupBy('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial',
                'c.id_tipo_documento', 'p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre')
            ->orderBy('c.cliente_nombre')
            ->orderByDesc('total_importe')
            ->get();

        // ── Labels ────────────────────────────────────────────────
        $nombreCliente = 'TODOS';
        if ($idCliente) {
            $cli = DB::table('clientes')->where('id_clientes', $idCliente)->first();
            if ($cli) $nombreCliente = $cli->cliente_razonsocial ?? $cli->cliente_nombre;
        }

        $fechaDesde = $desde ? date('d/m/Y', strtotime($desde)) : '-';
        $fechaHasta = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';

        // ── Info empresa para cabecera del reporte ────────────────
        $infoEmpresa = $idEmpresa > 0
            ? DB::table('empresa')->where('id_empresa', $idEmpresa)->first()
            : null;

        return compact(
            'resumenClientes', 'detalleVentas', 'detalleProductos',
            'nombreCliente', 'fechaDesde', 'fechaHasta', 'hoy',
            'infoEmpresa'
        );
    }


// ============================================================
//  PDF — A4 Vertical
// ============================================================
    public function imprimirPdfVentasCliente(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCliente($request);

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Ventas por Cliente'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(18, 4, utf8_decode('Cliente:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(60, 4, utf8_decode($d['nombreCliente']), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Desde:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($d['fechaDesde']), 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Hasta:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(30, 4, utf8_decode($d['fechaHasta']), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // ── SECCIÓN 1: Resumen por cliente ────────────────────
            // Anchos: 8+38+20+24+22+20+18+30 = 180mm
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('RESUMEN POR CLIENTE'), 1, 1, 'C', 1);
            $pdf->Ln(1);

            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(70, 90, 110);
            $pdf->Cell(8,  7, '#',                     1, 0, 'C', 1);
            $pdf->Cell(38, 7, utf8_decode('Cliente'),  1, 0, 'C', 1);
            $pdf->Cell(20, 7, utf8_decode('N° Doc.'),  1, 0, 'C', 1);
            $pdf->Cell(24, 7, utf8_decode('Total'),    1, 0, 'C', 1);
            $pdf->Cell(22, 7, utf8_decode('Facturas'), 1, 0, 'C', 1);
            $pdf->Cell(22, 7, utf8_decode('Boletas'),  1, 0, 'C', 1);
            $pdf->Cell(16, 7, utf8_decode('Comp.'),    1, 0, 'C', 1);
            $pdf->Cell(18, 7, utf8_decode('T. Prom.'), 1, 0, 'C', 1);
            $pdf->Cell(12, 7, utf8_decode('Deuda'),    1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', '', 6);

            foreach ($d['resumenClientes'] as $i => $c) {
                $nombre = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;
                $fill   = $i % 2 == 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $pdf->Cell(8,  5, $i + 1,                                       1, 0, 'C', 1);
                $pdf->Cell(38, 5, utf8_decode(mb_substr($nombre, 0, 22)),        1, 0, 'L', 1);
                $pdf->Cell(20, 5, utf8_decode($c->cliente_numero),               1, 0, 'C', 1);
                $pdf->Cell(24, 5, 'S/ ' . number_format($c->total_ventas, 2),    1, 0, 'R', 1);
                $pdf->Cell(22, 5, 'S/ ' . number_format($c->total_facturas, 2),  1, 0, 'R', 1);
                $pdf->Cell(22, 5, 'S/ ' . number_format($c->total_boletas, 2),   1, 0, 'R', 1);
                $pdf->Cell(16, 5, $c->cantidad,                                  1, 0, 'C', 1);
                $pdf->Cell(18, 5, 'S/ ' . number_format($c->ticket_promedio, 2), 1, 0, 'R', 1);
                $pdf->Cell(12, 5, 'S/ ' . number_format($c->deuda_pendiente, 2), 1, 1, 'R', 1);
            }

            $pdf->Ln(5);

            // ── SECCIÓN 2: Detalle de ventas ──────────────────────
            // Anchos: 8+34+16+24+34+16+22+26 = 180mm
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('DETALLE DE VENTAS'), 1, 1, 'C', 1);
            $pdf->Ln(1);

            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(70, 90, 110);
            $pdf->Cell(8,  7, '#',                         1, 0, 'C', 1);
            $pdf->Cell(34, 7, utf8_decode('Cliente'),      1, 0, 'C', 1);
            $pdf->Cell(18, 7, utf8_decode('Fecha'),        1, 0, 'C', 1);
            $pdf->Cell(14, 7, utf8_decode('Tipo'),         1, 0, 'C', 1);
            $pdf->Cell(24, 7, utf8_decode('Comprobante'),  1, 0, 'C', 1);
            $pdf->Cell(34, 7, utf8_decode('Vendedor'),     1, 0, 'C', 1);
            $pdf->Cell(16, 7, utf8_decode('F. Pago'),      1, 0, 'C', 1);
            $pdf->Cell(32, 7, utf8_decode('Total'),        1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetWidths([8, 34, 18, 14, 24, 34, 16, 32]);
            $pdf->SetFont('Helvetica', '', 6);

            $n = 1;
            foreach ($d['detalleVentas'] as $v) {
                $cliente  = $v->id_tipo_documento == 4 ? $v->cliente_razonsocial : $v->cliente_nombre;
                $tipoComp = match($v->venta_tipo) { '01'=>'FAC','03'=>'BOL','20'=>'NV', default=>$v->venta_tipo };
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Row([
                    $n++,
                    utf8_decode(mb_substr($cliente, 0, 20)),
                    date('d/m/Y', strtotime($v->venta_fecha)),
                    utf8_decode($tipoComp),
                    utf8_decode($v->venta_serie . '-' . $v->venta_correlativo),
                    utf8_decode(mb_substr($v->nombre_users, 0, 20)),
                    utf8_decode($v->id_formas_pago == 1 ? 'Contado' : 'Crédito'),
                    $v->simbolo . number_format($v->venta_total, 2),
                ]);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Ventas_Cliente_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }


// ============================================================
//  EXCEL — 3 hojas: Resumen + Ventas + Productos
// ============================================================
    public function imprimirExcelVentasCliente(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCliente($request);

            $spreadsheet = new Spreadsheet();

            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEtiqueta   = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial']];
            $estiloValor      = ['font' => ['size' => 9, 'name' => 'Arial']];
            $estiloSeccion    = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEncabezado = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloBorde = [
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8, 'name' => 'Arial'],
            ];

            // ── HOJA 1: Resumen ───────────────────────────────────
            $s1 = $spreadsheet->getActiveSheet();
            $s1->setTitle('Resumen');

            $s1->mergeCells('A1:I1');
            $s1->setCellValue('A1', 'Reporte de Ventas por Cliente');
            $s1->getStyle('A1')->applyFromArray($estiloTitulo);
            $s1->getRowDimension(1)->setRowHeight(22);

            $s1->setCellValue('A2', 'Cliente:'); $s1->getStyle('A2')->applyFromArray($estiloEtiqueta);
            $s1->setCellValue('B2', $d['nombreCliente']); $s1->getStyle('B2')->applyFromArray($estiloValor);
            $s1->setCellValue('F2', 'Desde:'); $s1->getStyle('F2')->applyFromArray($estiloEtiqueta);
            $s1->setCellValue('G2', $d['fechaDesde']); $s1->getStyle('G2')->applyFromArray($estiloValor);
            $s1->setCellValue('F3', 'Hasta:'); $s1->getStyle('F3')->applyFromArray($estiloEtiqueta);
            $s1->setCellValue('G3', $d['fechaHasta']); $s1->getStyle('G3')->applyFromArray($estiloValor);

            $s1->mergeCells('A5:I5');
            $s1->setCellValue('A5', 'RESUMEN POR CLIENTE');
            $s1->getStyle('A5:I5')->applyFromArray($estiloSeccion);
            $s1->getRowDimension(5)->setRowHeight(14);

            $encRes = ['A'=>'#','B'=>'Cliente','C'=>'N° Documento','D'=>'Total Ventas','E'=>'Facturas','F'=>'Boletas','G'=>'N. Venta','H'=>'Comp.','I'=>'Ticket Prom.','J'=>'Deuda'];
            foreach ($encRes as $col => $txt) { $s1->setCellValue("{$col}6", $txt); }
            $s1->getStyle('A6:J6')->applyFromArray($estiloEncabezado);
            $s1->getRowDimension(6)->setRowHeight(20);

            $fila = 7;
            foreach ($d['resumenClientes'] as $i => $c) {
                $nombre = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;
                $s1->setCellValue("A{$fila}", $i + 1);
                $s1->setCellValue("B{$fila}", $nombre);
                $s1->setCellValue("C{$fila}", $c->cliente_numero);
                $s1->setCellValue("D{$fila}", 'S/ ' . number_format($c->total_ventas, 2));
                $s1->setCellValue("E{$fila}", 'S/ ' . number_format($c->total_facturas, 2));
                $s1->setCellValue("F{$fila}", 'S/ ' . number_format($c->total_boletas, 2));
                $s1->setCellValue("G{$fila}", 'S/ ' . number_format($c->total_nv, 2));
                $s1->setCellValue("H{$fila}", $c->cantidad);
                $s1->setCellValue("I{$fila}", 'S/ ' . number_format($c->ticket_promedio, 2));
                $s1->setCellValue("J{$fila}", 'S/ ' . number_format($c->deuda_pendiente, 2));
                $s1->getStyle("A{$fila}:J{$fila}")->applyFromArray($estiloBorde);
                if ($fila % 2 == 0) { $s1->getStyle("A{$fila}:J{$fila}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]); }
                $fila++;
            }
            foreach (['A'=>5,'B'=>30,'C'=>14,'D'=>18,'E'=>18,'F'=>18,'G'=>18,'H'=>10,'I'=>16,'J'=>16] as $col => $w) { $s1->getColumnDimension($col)->setWidth($w); }

            // ── HOJA 2: Detalle ventas ────────────────────────────
            $s2 = $spreadsheet->createSheet();
            $s2->setTitle('Detalle Ventas');

            $s2->mergeCells('A1:H1');
            $s2->setCellValue('A1', 'DETALLE DE VENTAS POR CLIENTE');
            $s2->getStyle('A1:H1')->applyFromArray($estiloSeccion);
            $s2->getRowDimension(1)->setRowHeight(16);

            $encDet = ['A'=>'#','B'=>'Cliente','C'=>'Fecha','D'=>'Tipo','E'=>'Comprobante','F'=>'Vendedor','G'=>'F. Pago','H'=>'Total'];
            foreach ($encDet as $col => $txt) { $s2->setCellValue("{$col}2", $txt); }
            $s2->getStyle('A2:H2')->applyFromArray($estiloEncabezado);
            $s2->getRowDimension(2)->setRowHeight(20);

            $filaDet = 3; $n = 1;
            foreach ($d['detalleVentas'] as $v) {
                $cliente  = $v->id_tipo_documento == 4 ? $v->cliente_razonsocial : $v->cliente_nombre;
                $tipoComp = match($v->venta_tipo) { '01'=>'FACTURA','03'=>'BOLETA','20'=>'NOTA VENTA', default=>$v->venta_tipo };
                $s2->setCellValue("A{$filaDet}", $n++);
                $s2->setCellValue("B{$filaDet}", $cliente);
                $s2->setCellValue("C{$filaDet}", date('d/m/Y H:i', strtotime($v->venta_fecha)));
                $s2->setCellValue("D{$filaDet}", $tipoComp);
                $s2->setCellValue("E{$filaDet}", $v->venta_serie . '-' . $v->venta_correlativo);
                $s2->setCellValue("F{$filaDet}", $v->nombre_users);
                $s2->setCellValue("G{$filaDet}", $v->id_formas_pago == 1 ? 'Contado' : 'Crédito');
                $s2->setCellValue("H{$filaDet}", $v->simbolo . number_format($v->venta_total, 2));
                $s2->getStyle("A{$filaDet}:H{$filaDet}")->applyFromArray($estiloBorde);
                if ($filaDet % 2 == 0) { $s2->getStyle("A{$filaDet}:H{$filaDet}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]); }
                $filaDet++;
            }
            foreach (['A'=>5,'B'=>30,'C'=>18,'D'=>14,'E'=>20,'F'=>25,'G'=>12,'H'=>14] as $col => $w) { $s2->getColumnDimension($col)->setWidth($w); }

            // ── HOJA 3: Productos ─────────────────────────────────
            $s3 = $spreadsheet->createSheet();
            $s3->setTitle('Productos');

            $s3->mergeCells('A1:G1');
            $s3->setCellValue('A1', 'PRODUCTOS COMPRADOS POR CLIENTE');
            $s3->getStyle('A1:G1')->applyFromArray($estiloSeccion);
            $s3->getRowDimension(1)->setRowHeight(16);

            $encProd = ['A'=>'#','B'=>'Cliente','C'=>'Producto','D'=>'Código','E'=>'Categoría','F'=>'Cantidad','G'=>'Total'];
            foreach ($encProd as $col => $txt) { $s3->setCellValue("{$col}2", $txt); }
            $s3->getStyle('A2:G2')->applyFromArray($estiloEncabezado);
            $s3->getRowDimension(2)->setRowHeight(20);

            $filaProd = 3; $n = 1;
            foreach ($d['detalleProductos'] as $p) {
                $cliente = $p->id_tipo_documento == 4 ? $p->cliente_razonsocial : $p->cliente_nombre;
                $s3->setCellValue("A{$filaProd}", $n++);
                $s3->setCellValue("B{$filaProd}", $cliente);
                $s3->setCellValue("C{$filaProd}", $p->pro_nombre);
                $s3->setCellValue("D{$filaProd}", $p->pro_codigo);
                $s3->setCellValue("E{$filaProd}", $p->ca_nombre);
                $s3->setCellValue("F{$filaProd}", number_format($p->total_cantidad, 2));
                $s3->setCellValue("G{$filaProd}", 'S/ ' . number_format($p->total_importe, 2));
                $s3->getStyle("A{$filaProd}:G{$filaProd}")->applyFromArray($estiloBorde);
                if ($filaProd % 2 == 0) { $s3->getStyle("A{$filaProd}:G{$filaProd}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]); }
                $filaProd++;
            }
            foreach (['A'=>5,'B'=>30,'C'=>30,'D'=>14,'E'=>20,'F'=>12,'G'=>14] as $col => $w) { $s3->getColumnDimension($col)->setWidth($w); }

            $spreadsheet->setActiveSheetIndex(0);

            $nombreArchivo = 'Ventas_Cliente_' . date('Ymd_His') . '.xlsx';
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
    private function obtenerDatosReporteProductos(Request $request): array
    {
        $desde       = $request->desde     ?? null;
        $hasta       = $request->hasta     ?? null;
        $idCategoria = $request->categoria ?? null;
        $topN        = (int) ($request->top ?? 10);
        $idEmpresa   = (int) ($request->id_empresa  ?? 0);
        $idSucursal  = (int) ($request->id_sucursal ?? 0);

        // Stock: si hay sucursal → stock de esa sucursal
        //        si solo empresa → suma de todas sus sucursales
        $stockSubquery = $idSucursal > 0
            ? DB::raw('(SELECT COALESCE(ps.ps_stock, 0)
                    FROM producto_sucursal ps
                    WHERE ps.id_pro = p.id_pro
                      AND ps.id_sucursal = ' . $idSucursal . '
                    LIMIT 1) as stock_actual')
            : DB::raw('(SELECT COALESCE(SUM(ps.ps_stock), 0)
                    FROM producto_sucursal ps
                    JOIN tiendas t ON t.id_tienda = ps.id_sucursal
                    WHERE ps.id_pro = p.id_pro
                      AND t.id_empresa = ' . $idEmpresa . ') as stock_actual');

        // Closure reutilizable para la query base
        $queryBase = function () use ($desde, $hasta, $idCategoria, $idEmpresa, $idSucursal, $stockSubquery) {
            $q = DB::table('ventas_detalle as vd')
                ->select(
                    'p.id_pro',
                    'p.pro_nombre',
                    'p.pro_codigo',
                    'ca.ca_nombre',
                    $stockSubquery,
                    DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                    DB::raw('SUM(vd.venta_detalle_importe_total) as total_monto'),
                    DB::raw('COUNT(DISTINCT v.id_venta) as en_comprobantes'),
                    DB::raw('ROUND(AVG(vd.venta_detalle_precio_unitario), 2) as precio_promedio'),
                )
                ->join('ventas as v',      'v.id_venta', '=', 'vd.id_venta')
                ->join('productos as p',   'p.id_pro',   '=', 'vd.id_pro')
                ->join('categorias as ca', 'ca.id_ca',   '=', 'p.id_ca')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

            // ── Filtro empresa / sucursal ─────────────────────
            if ($idEmpresa > 0) {
                $q->where('p.id_empresa', $idEmpresa);
            }
            if ($idSucursal > 0) {
                $q->where('v.id_sucursal', $idSucursal);
            }

            if ($idCategoria) {
                $q->where('p.id_ca', $idCategoria);
            }

            return $q->groupBy('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre');
        };

        // ── Rankings ──────────────────────────────────────────
        $rankingCantidad = (clone $queryBase())->orderByDesc('total_cantidad')->limit($topN)->get();
        $rankingMonto    = (clone $queryBase())->orderByDesc('total_monto')->limit($topN)->get();
        $bottomCantidad  = (clone $queryBase())->orderBy('total_cantidad')->limit($topN)->get();
        $bottomMonto     = (clone $queryBase())->orderBy('total_monto')->limit($topN)->get();
        $todosProductos  = (clone $queryBase())->orderByDesc('total_monto')->get();

        // ── Comparativa por mes ───────────────────────────────
        $queryComp = DB::table('ventas_detalle as vd')
            ->select(
                DB::raw("DATE_FORMAT(v.venta_fecha, '%Y-%m') as mes"),
                DB::raw("DATE_FORMAT(v.venta_fecha, '%m/%Y') as mes_label"),
                DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_monto'),
                DB::raw('COUNT(DISTINCT vd.id_pro) as productos_distintos'),
            )
            ->join('ventas as v',    'v.id_venta', '=', 'vd.id_venta')
            ->join('productos as p', 'p.id_pro',   '=', 'vd.id_pro')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);

        if ($idEmpresa > 0) {
            $queryComp->where('p.id_empresa', $idEmpresa);
        }
        if ($idSucursal > 0) {
            $queryComp->where('v.id_sucursal', $idSucursal);
        }
        if ($idCategoria) {
            $queryComp->where('p.id_ca', $idCategoria);
        }

        $comparativaMeses = $queryComp
            ->groupBy(
                DB::raw("DATE_FORMAT(v.venta_fecha, '%Y-%m')"),
                DB::raw("DATE_FORMAT(v.venta_fecha, '%m/%Y')")
            )
            ->orderBy('mes')
            ->get();

        // ── Labels ────────────────────────────────────────────
        $nombreCategoria = 'TODAS';
        if ($idCategoria) {
            $cat = DB::table('categorias')->where('id_ca', $idCategoria)->first();
            if ($cat) $nombreCategoria = $cat->ca_nombre;
        }

        $fechaDesde = $desde ? date('d/m/Y', strtotime($desde)) : '-';
        $fechaHasta = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';

        // ── Info empresa/sucursal para cabecera del reporte ───
        $infoEmpresa  = $idEmpresa  > 0 ? DB::table('empresa')->where('id_empresa', $idEmpresa)->first()   : null;
        $infoSucursal = $idSucursal > 0 ? DB::table('tiendas')->where('id_tienda',  $idSucursal)->first()  : null;

        return compact(
            'rankingCantidad', 'rankingMonto', 'bottomCantidad', 'bottomMonto',
            'todosProductos', 'comparativaMeses',
            'nombreCategoria', 'fechaDesde', 'fechaHasta', 'topN',
            'infoEmpresa', 'infoSucursal'
        );
    }



// ============================================================
//  PDF — A4 Vertical (180mm)
// ============================================================
    public function imprimirPdfVentasProductos(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteProductos($request);

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            // Título
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Productos Más y Menos Vendidos'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Filtros
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(22, 4, utf8_decode('Categoría:'), 0, 0);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(50, 4, utf8_decode($d['nombreCategoria']), 0, 0);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(10, 4, utf8_decode('Top:'), 0, 0);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(20, 4, utf8_decode($d['topN']), 0, 0);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Desde:'), 0, 0);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(25, 4, utf8_decode($d['fechaDesde']), 0, 0);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, utf8_decode('Hasta:'), 0, 0);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(25, 4, utf8_decode($d['fechaHasta']), 0, 1);

            $pdf->Ln(3);
            $pdf->Cell(180, 0, '', 'T', 1);
            $pdf->Ln(4);

            // Helper para imprimir sección de ranking
            // Anchos: 8+52+18+28+28+18+28 = 180mm
            $imprimirRanking = function($titulo, $datos, $campoOrden) use ($pdf) {
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(33, 44, 62);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(180, 6, utf8_decode($titulo), 1, 1, 'C', 1);
                $pdf->Ln(1);

                $pdf->SetFont('Helvetica', 'B', 6);
                $pdf->SetFillColor(70, 90, 110);
                $pdf->Cell(8,  6, '#',                        1, 0, 'C', 1);
                $pdf->Cell(52, 6, utf8_decode('Producto'),   1, 0, 'C', 1);
                $pdf->Cell(22, 6, utf8_decode('Categoría'),  1, 0, 'C', 1);
                $pdf->Cell(24, 6, utf8_decode('Unidades'),   1, 0, 'C', 1);
                $pdf->Cell(26, 6, utf8_decode('Monto'),      1, 0, 'C', 1);
                $pdf->Cell(20, 6, utf8_decode('Precio P.'),  1, 0, 'C', 1);
                $pdf->Cell(16, 6, utf8_decode('Comp.'),      1, 0, 'C', 1);
                $pdf->Cell(12, 6, utf8_decode('Stock'),      1, 1, 'C', 1);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Helvetica', '', 6);

                foreach ($datos as $i => $p) {
                    $fill = $i % 2 == 0;
                    $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                    $pdf->Cell(8,  5, $i + 1,                                              1, 0, 'C', 1);
                    $pdf->Cell(52, 5, utf8_decode(mb_substr($p->pro_nombre, 0, 30)),       1, 0, 'L', 1);
                    $pdf->Cell(22, 5, utf8_decode(mb_substr($p->ca_nombre, 0, 14)),        1, 0, 'L', 1);
                    $pdf->Cell(24, 5, number_format($p->total_cantidad, 0),                1, 0, 'R', 1);
                    $pdf->Cell(26, 5, 'S/ ' . number_format($p->total_monto, 2),           1, 0, 'R', 1);
                    $pdf->Cell(20, 5, 'S/ ' . number_format($p->precio_promedio, 2),       1, 0, 'R', 1);
                    $pdf->Cell(16, 5, $p->en_comprobantes,                                 1, 0, 'C', 1);
                    $pdf->Cell(12, 5, $p->stock_actual,                                    1, 1, 'C', 1);
                }
                $pdf->Ln(5);
            };

            $imprimirRanking("TOP {$d['topN']} — MÁS VENDIDOS POR CANTIDAD", $d['rankingCantidad'], 'cantidad');
            $imprimirRanking("TOP {$d['topN']} — MÁS VENDIDOS POR MONTO", $d['rankingMonto'], 'monto');
            $imprimirRanking("BOTTOM {$d['topN']} — MENOS VENDIDOS POR CANTIDAD", $d['bottomCantidad'], 'cantidad');
            $imprimirRanking("BOTTOM {$d['topN']} — MENOS VENDIDOS POR MONTO", $d['bottomMonto'], 'monto');

            // Comparativa mensual
            // Anchos: 40+40+40+30+30 = 180mm
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, utf8_decode('COMPARATIVA MENSUAL'), 1, 1, 'C', 1);
            $pdf->Ln(1);

            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetFillColor(70, 90, 110);
            $pdf->Cell(36, 6, utf8_decode('Mes'),                   1, 0, 'C', 1);
            $pdf->Cell(40, 6, utf8_decode('Unidades Vendidas'),     1, 0, 'C', 1);
            $pdf->Cell(40, 6, utf8_decode('Monto Total'),           1, 0, 'C', 1);
            $pdf->Cell(36, 6, utf8_decode('Productos Distintos'),   1, 0, 'C', 1);
            $pdf->Cell(28, 6, utf8_decode('Prom. x Producto'),      1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', '', 6);

            foreach ($d['comparativaMeses'] as $i => $mes) {
                $fill = $i % 2 == 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $promedio = $mes->productos_distintos > 0 ? number_format($mes->total_monto / $mes->productos_distintos, 2) : '0.00';
                $pdf->Cell(36, 5, utf8_decode($mes->mes_label),                           1, 0, 'C', 1);
                $pdf->Cell(40, 5, number_format($mes->total_cantidad, 0) . ' unid.',       1, 0, 'R', 1);
                $pdf->Cell(40, 5, 'S/ ' . number_format($mes->total_monto, 2),             1, 0, 'R', 1);
                $pdf->Cell(36, 5, $mes->productos_distintos,                               1, 0, 'C', 1);
                $pdf->Cell(28, 5, 'S/ ' . $promedio,                                      1, 1, 'R', 1);
            }

            $pdf->Ln(7);
            $pdf->Output('', 'Productos_Ventas_' . date('Ymd_His') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('intranet')->with('error', 'Ocurrió un error al generar el PDF.');
        }
    }


// ============================================================
//  EXCEL — 4 hojas
// ============================================================
    public function imprimirExcelVentasProductos(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteProductos($request);

            $spreadsheet = new Spreadsheet();

            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1F2C3E'], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloSeccion = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $estiloEncabezado = [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloBorde = [
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 8, 'name' => 'Arial'],
            ];

            $anchos = ['A'=>5,'B'=>35,'C'=>14,'D'=>20,'E'=>16,'F'=>16,'G'=>16,'H'=>10];

            // Helper para escribir una hoja de ranking
            $escribirHojaRanking = function($sheet, $titulo, $datos) use ($estiloSeccion, $estiloEncabezado, $estiloBorde, $anchos) {
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', $titulo);
                $sheet->getStyle('A1:H1')->applyFromArray($estiloSeccion);
                $sheet->getRowDimension(1)->setRowHeight(16);

                $encabezados = ['A'=>'#','B'=>'Producto','C'=>'Código','D'=>'Categoría','E'=>'Unidades','F'=>'Monto Total','G'=>'Precio Prom.','H'=>'Comp.'];
                foreach ($encabezados as $col => $txt) { $sheet->setCellValue("{$col}2", $txt); }
                $sheet->getStyle('A2:H2')->applyFromArray($estiloEncabezado);
                $sheet->getRowDimension(2)->setRowHeight(20);

                $fila = 3;
                foreach ($datos as $i => $p) {
                    $sheet->setCellValue("A{$fila}", $i + 1);
                    $sheet->setCellValue("B{$fila}", $p->pro_nombre);
                    $sheet->setCellValue("C{$fila}", $p->pro_codigo);
                    $sheet->setCellValue("D{$fila}", $p->ca_nombre);
                    $sheet->setCellValue("E{$fila}", number_format($p->total_cantidad, 0));
                    $sheet->setCellValue("F{$fila}", 'S/ ' . number_format($p->total_monto, 2));
                    $sheet->setCellValue("G{$fila}", 'S/ ' . number_format($p->precio_promedio, 2));
                    $sheet->setCellValue("H{$fila}", $p->en_comprobantes);
                    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloBorde);
                    if ($fila % 2 == 0) { $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']]]); }
                    $fila++;
                }
                foreach ($anchos as $col => $w) { $sheet->getColumnDimension($col)->setWidth($w); }
            };

            // ── Hoja 1: Top por cantidad ──────────────────────────
            $s1 = $spreadsheet->getActiveSheet();
            $s1->setTitle('Top Cantidad');
            $s1->mergeCells('A1:H1');
            $s1->setCellValue('A1', "TOP {$d['topN']} — Más vendidos por CANTIDAD");
            $s1->getStyle('A1:H1')->applyFromArray($estiloSeccion);
            $s1->getRowDimension(1)->setRowHeight(16);
            $enc = ['A'=>'#','B'=>'Producto','C'=>'Código','D'=>'Categoría','E'=>'Unidades','F'=>'Monto Total','G'=>'Precio Prom.','H'=>'Comp.'];
            foreach ($enc as $col => $txt) { $s1->setCellValue("{$col}2", $txt); }
            $s1->getStyle('A2:H2')->applyFromArray($estiloEncabezado);
            $s1->getRowDimension(2)->setRowHeight(20);
            $fila = 3;
            foreach ($d['rankingCantidad'] as $i => $p) {
                $s1->setCellValue("A{$fila}", $i+1); $s1->setCellValue("B{$fila}", $p->pro_nombre); $s1->setCellValue("C{$fila}", $p->pro_codigo);
                $s1->setCellValue("D{$fila}", $p->ca_nombre); $s1->setCellValue("E{$fila}", number_format($p->total_cantidad,0));
                $s1->setCellValue("F{$fila}", 'S/ '.number_format($p->total_monto,2)); $s1->setCellValue("G{$fila}", 'S/ '.number_format($p->precio_promedio,2));
                $s1->setCellValue("H{$fila}", $p->en_comprobantes); $s1->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloBorde);
                if ($fila%2==0) { $s1->getStyle("A{$fila}:H{$fila}")->applyFromArray(['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FFF2F2F2']]]); }
                $fila++;
            }
            foreach ($anchos as $col => $w) { $s1->getColumnDimension($col)->setWidth($w); }

            // ── Hoja 2: Top por monto ─────────────────────────────
            $s2 = $spreadsheet->createSheet(); $s2->setTitle('Top Monto');
            $escribirHojaRanking($s2, "TOP {$d['topN']} — Más vendidos por MONTO", $d['rankingMonto']);

            // ── Hoja 3: Bottom (menos vendidos) ───────────────────
            $s3 = $spreadsheet->createSheet(); $s3->setTitle('Bottom');
            // Menos por cantidad arriba, menos por monto abajo
            $s3->mergeCells('A1:H1'); $s3->setCellValue('A1', "BOTTOM {$d['topN']} — Menos vendidos por CANTIDAD");
            $s3->getStyle('A1:H1')->applyFromArray($estiloSeccion); $s3->getRowDimension(1)->setRowHeight(16);
            foreach ($enc as $col => $txt) { $s3->setCellValue("{$col}2", $txt); }
            $s3->getStyle('A2:H2')->applyFromArray($estiloEncabezado); $s3->getRowDimension(2)->setRowHeight(20);
            $fila = 3;
            foreach ($d['bottomCantidad'] as $i => $p) {
                $s3->setCellValue("A{$fila}", $i+1); $s3->setCellValue("B{$fila}", $p->pro_nombre); $s3->setCellValue("C{$fila}", $p->pro_codigo);
                $s3->setCellValue("D{$fila}", $p->ca_nombre); $s3->setCellValue("E{$fila}", number_format($p->total_cantidad,0));
                $s3->setCellValue("F{$fila}", 'S/ '.number_format($p->total_monto,2)); $s3->setCellValue("G{$fila}", 'S/ '.number_format($p->precio_promedio,2));
                $s3->setCellValue("H{$fila}", $p->en_comprobantes); $s3->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloBorde);
                $fila++;
            }
            foreach ($anchos as $col => $w) { $s3->getColumnDimension($col)->setWidth($w); }

            // ── Hoja 4: Comparativa mensual ───────────────────────
            $s4 = $spreadsheet->createSheet(); $s4->setTitle('Comparativa Mensual');
            $s4->mergeCells('A1:E1'); $s4->setCellValue('A1', 'COMPARATIVA MENSUAL DE VENTAS');
            $s4->getStyle('A1:E1')->applyFromArray($estiloSeccion); $s4->getRowDimension(1)->setRowHeight(16);
            $encComp = ['A'=>'Mes','B'=>'Unidades','C'=>'Monto Total','D'=>'Productos Distintos','E'=>'Promedio x Producto'];
            foreach ($encComp as $col => $txt) { $s4->setCellValue("{$col}2", $txt); }
            $s4->getStyle('A2:E2')->applyFromArray($estiloEncabezado); $s4->getRowDimension(2)->setRowHeight(20);
            $fila = 3;
            foreach ($d['comparativaMeses'] as $mes) {
                $prom = $mes->productos_distintos > 0 ? number_format($mes->total_monto / $mes->productos_distintos, 2) : '0.00';
                $s4->setCellValue("A{$fila}", $mes->mes_label); $s4->setCellValue("B{$fila}", number_format($mes->total_cantidad,0));
                $s4->setCellValue("C{$fila}", 'S/ '.number_format($mes->total_monto,2)); $s4->setCellValue("D{$fila}", $mes->productos_distintos);
                $s4->setCellValue("E{$fila}", 'S/ '.$prom); $s4->getStyle("A{$fila}:E{$fila}")->applyFromArray($estiloBorde);
                if ($fila%2==0) { $s4->getStyle("A{$fila}:E{$fila}")->applyFromArray(['fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FFF2F2F2']]]); }
                $fila++;
            }
            foreach (['A'=>16,'B'=>18,'C'=>20,'D'=>22,'E'=>20] as $col => $w) { $s4->getColumnDimension($col)->setWidth($w); }

            $spreadsheet->setActiveSheetIndex(0);

            $nombreArchivo = 'Productos_Ventas_' . date('Ymd_His') . '.xlsx';
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

    // ══════════════════════════════════════════════════════════
    //  REPORTE DE CAJA
    // ══════════════════════════════════════════════════════════

    public function reporteCaja()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_caja');
            return view('reporte.reporte_caja', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosReporteCaja(Request $request): array
    {
        $idCajaNumero = (int) ($request->id_caja_numero ?? 0);
        $desde        = $request->desde ?? null;
        $hasta        = $request->hasta ?? null;

        $idsCaja = DB::table('caja')
            ->where('id_caja_numero', $idCajaNumero)
            ->when($desde, fn($q) => $q->whereDate('caja_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('caja_fecha', '<=', $hasta))
            ->pluck('id_caja');

        $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')->get();

        $pagosCuotas = DB::table('pagos_cuotas as pc')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
            ->whereNull('pc.deleted_at')->whereIn('v.id_caja', $idsCaja)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')->get();

        $movimientos = DB::table('caja_movimientos as cm')
            ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
            ->join('users as u', 'u.id_users', '=', 'cm.id_users')
            ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
            ->whereNull('cm.deleted_at')->whereIn('cm.id_caja', $idsCaja)
            ->orderBy('cm.created_at')->get();

        $nombreCaja    = DB::table('caja_numero')->where('id_caja_numero', $idCajaNumero)->value('caja_numero_nombre') ?? '-';
        $totalVentas   = $ventasPorMedio->sum('total');
        $totalCuotas   = $pagosCuotas->sum('total');
        $totalIngresos = $movimientos->where('tipo', 1)->sum('monto');
        $totalEgresos  = $movimientos->where('tipo', 2)->sum('monto');
        $totalNeto     = $totalVentas + $totalCuotas + $totalIngresos - $totalEgresos;

        return compact('ventasPorMedio', 'pagosCuotas', 'movimientos',
                       'nombreCaja', 'totalVentas', 'totalCuotas',
                       'totalIngresos', 'totalEgresos', 'totalNeto', 'desde', 'hasta');
    }

    public function reporteCajaPdf(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCaja($request);

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);

            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(180, 8, utf8_decode('Reporte de Caja — ' . $d['nombreCaja']), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $fechaDesde = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaHasta = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $pdf->Cell(180, 5, utf8_decode("Período: {$fechaDesde} al {$fechaHasta}"), 0, 1, 'C');
            $pdf->Ln(3); $pdf->Cell(180, 0, '', 'T', 1); $pdf->Ln(4);

            // Resumen
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(33, 44, 62); $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(180, 6, 'RESUMEN', 1, 1, 'C', 1);
            $pdf->SetTextColor(0, 0, 0);
            $filas = [
                ['Ventas', 'S/ ' . number_format($d['totalVentas'], 2)],
                ['Cobros de cuotas', 'S/ ' . number_format($d['totalCuotas'], 2)],
                ['Ingresos manuales', 'S/ ' . number_format($d['totalIngresos'], 2)],
                ['Egresos manuales', 'S/ ' . number_format($d['totalEgresos'], 2)],
                ['TOTAL NETO', 'S/ ' . number_format($d['totalNeto'], 2)],
            ];
            foreach ($filas as $i => $f) {
                $fill = $i % 2 === 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $bold = $f[0] === 'TOTAL NETO' ? 'B' : '';
                $pdf->SetFont('Helvetica', $bold, 8);
                $pdf->Cell(120, 5, utf8_decode($f[0]), 1, 0, 'L', 1);
                $pdf->Cell(60, 5, utf8_decode($f[1]), 1, 1, 'R', 1);
            }
            $pdf->Ln(4);

            // Ventas por medio de pago
            if ($d['ventasPorMedio']->isNotEmpty()) {
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetFillColor(33, 44, 62); $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(180, 6, 'VENTAS POR MEDIO DE PAGO', 0, 1, 'L', 1);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(70, 89, 110); $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(120, 5, 'Medio de pago', 1, 0, 'C', 1);
                $pdf->Cell(60, 5, 'Total', 1, 1, 'C', 1);
                $pdf->SetTextColor(0, 0, 0);
                foreach ($d['ventasPorMedio'] as $i => $v) {
                    $fill = $i % 2 === 0;
                    $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->Cell(120, 5, utf8_decode($v->tipo_pago_nombre), 1, 0, 'L', 1);
                    $pdf->Cell(60, 5, 'S/ ' . number_format($v->total, 2), 1, 1, 'R', 1);
                }
                $pdf->Ln(4);
            }

            // Movimientos
            if ($d['movimientos']->isNotEmpty()) {
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetFillColor(33, 44, 62); $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(180, 6, 'MOVIMIENTOS MANUALES', 0, 1, 'L', 1);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(70, 89, 110); $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(30, 5, 'Fecha', 1, 0, 'C', 1);
                $pdf->Cell(15, 5, 'Tipo', 1, 0, 'C', 1);
                $pdf->Cell(85, 5, 'Concepto', 1, 0, 'C', 1);
                $pdf->Cell(50, 5, 'Monto', 1, 1, 'C', 1);
                $pdf->SetTextColor(0, 0, 0);
                foreach ($d['movimientos'] as $i => $m) {
                    $fill = $i % 2 === 0;
                    $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->Cell(30, 5, date('d/m/Y H:i', strtotime($m->created_at)), 1, 0, 'L', 1);
                    $pdf->Cell(15, 5, $m->tipo == 1 ? 'Ingreso' : 'Egreso', 1, 0, 'C', 1);
                    $pdf->Cell(85, 5, utf8_decode(mb_strimwidth($m->concepto, 0, 50, '...')), 1, 0, 'L', 1);
                    $pdf->Cell(50, 5, 'S/ ' . number_format($m->monto, 2), 1, 1, 'R', 1);
                }
            }

            ob_end_clean();
            $pdf->Output('I', 'reporte_caja_' . date('Ymd') . '.pdf');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteCajaExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCaja($request);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Caja');

            $estiloTitulo = ['font' => ['bold' => true, 'size' => 13, 'name' => 'Arial'],
                             'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloHeader = ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9, 'name' => 'Arial'],
                             'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                             'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloDato   = ['font' => ['size' => 8, 'name' => 'Arial'],
                             'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];

            $row = 1;
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", 'Reporte de Caja — ' . $d['nombreCaja']);
            $sheet->getStyle("A{$row}")->applyFromArray($estiloTitulo);
            $row += 2;

            // Resumen
            foreach ([['Ventas', $d['totalVentas']], ['Cobros cuotas', $d['totalCuotas']],
                      ['Ingresos manuales', $d['totalIngresos']], ['Egresos manuales', $d['totalEgresos']],
                      ['Total neto', $d['totalNeto']]] as $f) {
                $sheet->setCellValue("A{$row}", $f[0]);
                $sheet->setCellValue("B{$row}", $f[1]);
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $row++;
            }
            $row += 2;

            // Ventas por medio de pago
            if ($d['ventasPorMedio']->isNotEmpty()) {
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", 'VENTAS POR MEDIO DE PAGO');
                $sheet->getStyle("A{$row}")->applyFromArray($estiloHeader);
                $row++;
                foreach ($d['ventasPorMedio'] as $v) {
                    $sheet->setCellValue("A{$row}", $v->tipo_pago_nombre);
                    $sheet->setCellValue("B{$row}", (float) $v->total);
                    $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($estiloDato);
                    $row++;
                }
                $row += 2;
            }

            // Movimientos
            if ($d['movimientos']->isNotEmpty()) {
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", 'MOVIMIENTOS MANUALES');
                $sheet->getStyle("A{$row}")->applyFromArray($estiloHeader);
                $row++;
                $sheet->setCellValue("A{$row}", 'Fecha'); $sheet->setCellValue("B{$row}", 'Tipo');
                $sheet->setCellValue("C{$row}", 'Concepto'); $sheet->setCellValue("D{$row}", 'Monto');
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($estiloHeader);
                $row++;
                foreach ($d['movimientos'] as $m) {
                    $sheet->setCellValue("A{$row}", date('d/m/Y H:i', strtotime($m->created_at)));
                    $sheet->setCellValue("B{$row}", $m->tipo == 1 ? 'Ingreso' : 'Egreso');
                    $sheet->setCellValue("C{$row}", $m->concepto);
                    $sheet->setCellValue("D{$row}", (float) $m->monto);
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($estiloDato);
                    $row++;
                }
            }

            foreach (range('A', 'D') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_caja_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  REPORTE CxC
    // ══════════════════════════════════════════════════════════

    public function reporteCxC()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_cxc');
            return view('reporte.reporte_cxc', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    public function reporteCxCPdf(Request $request)
    {
        try {
            $empresa   = (int) ($request->empresa   ?? 0);
            $sucursal  = (int) ($request->sucursal  ?? 0);
            $cliente   = $request->cliente   ?? '';
            $desde     = $request->desde     ?? null;
            $hasta     = $request->hasta     ?? null;
            $vinculada = $request->vinculada ?? '';
            $estado    = $request->estado    ?? '';

            $hoy = now()->toDateString();
            $subPagado = DB::table('pagos_cuotas as pc2')
                ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
                ->whereNull('pc2.deleted_at')->groupBy('pc2.id_ventas_cuotas');

            $query = DB::table('ventas_cuotas as vc')
                ->select('c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero',
                         'vc.id_ventas_cuotas', 'vc.venta_cuota_importe', 'vc.venta_cuota_fecha',
                         'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                         DB::raw('COALESCE(pag.total_pagado,0) as total_pagado'),
                         DB::raw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado,0),0) as saldo'),
                         DB::raw("CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada"))
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->leftJoinSub($subPagado, 'pag', 'pag.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'c.cliente_numero')
                ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'v.id_empresa')
                ->whereNull('va.id_venta')->where('v.id_formas_pago', 2);

            if ($estado === 'pagadas') {
                $query->whereRaw('COALESCE(pag.total_pagado,0) >= vc.venta_cuota_importe');
            } elseif ($estado === 'vencidas') {
                $query->whereRaw("vc.venta_cuota_fecha < ?", [$hoy])
                      ->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            } elseif ($estado === 'por_vencer') {
                $query->whereRaw("vc.venta_cuota_fecha >= ?", [$hoy])
                      ->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            } elseif ($estado === 'pendientes') {
                $query->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            }
            // sin filtro de estado → muestra todos (igual que el componente con filtroEstado = '')

            if ($sucursal > 0) $query->where('v.id_sucursal', $sucursal);
            elseif ($empresa > 0) $query->where('v.id_empresa', $empresa);
            if ($desde) $query->whereDate('vc.venta_cuota_fecha', '>=', $desde);
            if ($hasta) $query->whereDate('vc.venta_cuota_fecha', '<=', $hasta);
            if ($cliente) {
                $like = '%'.$cliente.'%';
                $query->where(fn($q) => $q->where('c.cliente_nombre','like',$like)
                                          ->orWhere('c.cliente_numero','like',$like));
            }
            if ($vinculada === '1') {
                $query->whereRaw('ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo');
            } elseif ($vinculada === '0') {
                $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
            }

            $cuotas = $query->orderBy('c.cliente_nombre')->orderBy('vc.venta_cuota_fecha')->get();

            $idsCxC = $cuotas->pluck('id_ventas_cuotas')->all();
            $pagosPorCuota = collect();
            if (count($idsCxC)) {
                $pagosPorCuota = DB::table('pagos_cuotas as pc')
                    ->select('pc.*', 'tp.tipo_pago_nombre', 'u.nombre_users')
                    ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
                    ->join('users as u', 'u.id_users', '=', 'pc.id_users')
                    ->whereIn('pc.id_ventas_cuotas', $idsCxC)
                    ->whereNull('pc.deleted_at')
                    ->orderBy('pc.pagos_cuota_fecha')
                    ->get()
                    ->groupBy('id_ventas_cuotas');
            }

            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(267, 8, utf8_decode('Reporte CxC — Aging'), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $fechaD = $desde ? date('d/m/Y', strtotime($desde)) : '-';
            $fechaH = $hasta ? date('d/m/Y', strtotime($hasta)) : '-';
            $pdf->Cell(267, 5, utf8_decode("Período: {$fechaD} al {$fechaH}"), 0, 1, 'C');
            $pdf->Ln(3); $pdf->Cell(267, 0, '', 'T', 1); $pdf->Ln(4);

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(70, 89, 110); $pdf->SetTextColor(255, 255, 255);
            foreach (['Cliente'=>65,'RUC/DNI'=>28,'Documento'=>38,'Vencimiento'=>24,'Importe'=>24,'Pagado'=>24,'Saldo'=>24,'Días'=>25,'Vinc.'=>15] as $col=>$w) {
                $pdf->Cell($w, 6, utf8_decode($col), 1, 0, 'C', 1);
            }
            $pdf->Ln();
            $pdf->SetTextColor(0, 0, 0);

            $totalSaldo = 0;
            foreach ($cuotas as $i => $c) {
                $fill = $i % 2 === 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $pdf->SetFont('Helvetica', '', 7);
                $dias = $c->venta_cuota_fecha < $hoy ? now()->diffInDays($c->venta_cuota_fecha) : 0;
                $saldo = (float) $c->saldo;
                $totalSaldo += $saldo;
                $pdf->Cell(65, 5, utf8_decode(mb_strimwidth($c->cliente_nombre ?: $c->cliente_razonsocial, 0, 33, '..')), 1, 0, 'L', 1);
                $pdf->Cell(28, 5, $c->cliente_numero, 1, 0, 'C', 1);
                $pdf->Cell(38, 5, "{$c->venta_tipo}-{$c->venta_serie}-" . str_pad($c->venta_correlativo, 8, '0', STR_PAD_LEFT), 1, 0, 'C', 1);
                $pdf->Cell(24, 5, date('d/m/Y', strtotime($c->venta_cuota_fecha)), 1, 0, 'C', 1);
                $pdf->Cell(24, 5, 'S/ '.number_format($c->venta_cuota_importe,2), 1, 0, 'R', 1);
                $pdf->Cell(24, 5, 'S/ '.number_format($c->total_pagado,2), 1, 0, 'R', 1);
                $pdf->Cell(24, 5, 'S/ '.number_format($saldo,2), 1, 0, 'R', 1);
                $pdf->Cell(25, 5, $dias > 0 ? "{$dias}d" : '-', 1, 0, 'C', 1);
                $pdf->Cell(15, 5, $c->es_vinculada ? 'Si' : '-', 1, 1, 'C', 1);

                // Sub-filas de pagos CxC
                $pagos = $pagosPorCuota->get($c->id_ventas_cuotas, collect());
                if ($pagos->count()) {
                    $pdf->SetFont('Helvetica', 'I', 6);
                    $pdf->SetFillColor(232, 244, 253);
                    $pdf->SetTextColor(60, 80, 120);
                    $pdf->Cell(10, 4, '', 0, 0, 'L', 1);
                    $pdf->Cell(22, 4, 'Fecha pago', 0, 0, 'C', 1);
                    $pdf->Cell(50, 4, 'Medio de pago', 0, 0, 'L', 1);
                    $pdf->Cell(22, 4, 'Monto', 0, 0, 'R', 1);
                    $pdf->Cell(88, 4, 'Voucher', 0, 0, 'L', 1);
                    $pdf->Cell(75, 4, utf8_decode('Registrado por'), 0, 1, 'L', 1);
                    foreach ($pagos as $pago) {
                        $pdf->Cell(10, 4, '', 0, 0, 'L', 1);
                        $pdf->Cell(22, 4, date('d/m/Y', strtotime($pago->pagos_cuota_fecha)), 0, 0, 'C', 1);
                        $pdf->Cell(50, 4, utf8_decode(mb_strimwidth($pago->tipo_pago_nombre, 0, 30, '..')), 0, 0, 'L', 1);
                        $pdf->Cell(22, 4, 'S/ '.number_format($pago->pagos_cuota_monto, 2), 0, 0, 'R', 1);
                        $pdf->Cell(88, 4, utf8_decode(mb_strimwidth($pago->pagos_cuota_voucher ?? '-', 0, 55, '..')), 0, 0, 'L', 1);
                        $pdf->Cell(75, 4, utf8_decode(mb_strimwidth($pago->nombre_users, 0, 45, '..')), 0, 1, 'L', 1);
                    }
                    $pdf->SetTextColor(0, 0, 0);
                }
            }
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(220, 230, 241);
            // 65+28+38+24+24+24=203 label | 24 saldo | 25 días | 15 vinc. = 267
            $pdf->Cell(203, 5, 'TOTAL', 1, 0, 'R', 1);
            $pdf->Cell(24,  5, 'S/ '.number_format($totalSaldo,2), 1, 0, 'R', 1);
            $pdf->Cell(25,  5, '', 1, 0, 'C', 1);
            $pdf->Cell(15,  5, '', 1, 1, 'C', 1);

            ob_end_clean();
            $pdf->Output('I', 'reporte_cxc_' . date('Ymd') . '.pdf');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteCxCExcel(Request $request)
    {
        try {
            $empresa   = (int) ($request->empresa   ?? 0);
            $sucursal  = (int) ($request->sucursal  ?? 0);
            $cliente   = $request->cliente  ?? '';
            $desde     = $request->desde    ?? null;
            $hasta     = $request->hasta    ?? null;
            $vinculada = $request->vinculada ?? '';
            $estado    = $request->estado   ?? '';
            $hoy       = now()->toDateString();

            $subPagado = DB::table('pagos_cuotas as pc2')
                ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
                ->whereNull('pc2.deleted_at')->groupBy('pc2.id_ventas_cuotas');

            $query = DB::table('ventas_cuotas as vc')
                ->select('c.cliente_nombre','c.cliente_razonsocial','c.cliente_numero',
                         'vc.id_ventas_cuotas','vc.venta_cuota_importe','vc.venta_cuota_fecha',
                         'v.venta_tipo','v.venta_serie','v.venta_correlativo',
                         DB::raw('COALESCE(pag.total_pagado,0) as total_pagado'),
                         DB::raw('GREATEST(vc.venta_cuota_importe-COALESCE(pag.total_pagado,0),0) as saldo'),
                         DB::raw('CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada'))
                ->join('ventas as v','v.id_venta','=','vc.id_venta')
                ->join('clientes as c','c.id_clientes','=','v.id_clientes')
                ->leftJoinSub($subPagado,'pag','pag.id_ventas_cuotas','=','vc.id_ventas_cuotas')
                ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                ->leftJoin('empresa as ev','ev.empresa_ruc','=','c.cliente_numero')
                ->leftJoin('empresa as eo','eo.id_empresa','=','v.id_empresa')
                ->whereNull('va.id_venta')->where('v.id_formas_pago',2);

            if ($estado === 'pagadas') {
                $query->whereRaw('COALESCE(pag.total_pagado,0) >= vc.venta_cuota_importe');
            } elseif ($estado === 'vencidas') {
                $query->whereRaw("vc.venta_cuota_fecha < ?", [$hoy])
                      ->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            } elseif ($estado === 'por_vencer') {
                $query->whereRaw("vc.venta_cuota_fecha >= ?", [$hoy])
                      ->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            } elseif ($estado === 'pendientes') {
                $query->whereRaw('COALESCE(pag.total_pagado,0) < vc.venta_cuota_importe');
            }
            // sin filtro de estado → muestra todos (igual que el componente con filtroEstado = '')

            if ($sucursal > 0) $query->where('v.id_sucursal',$sucursal);
            elseif ($empresa > 0) $query->where('v.id_empresa',$empresa);
            if ($desde) $query->whereDate('vc.venta_cuota_fecha','>=',$desde);
            if ($hasta) $query->whereDate('vc.venta_cuota_fecha','<=',$hasta);
            if ($cliente) { $like='%'.$cliente.'%'; $query->where(fn($q)=>$q->where('c.cliente_nombre','like',$like)->orWhere('c.cliente_numero','like',$like)); }
            if ($vinculada === '1') $query->whereRaw('(ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
            elseif ($vinculada === '0') $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');

            $cuotas = $query->orderBy('c.cliente_nombre')->orderBy('vc.venta_cuota_fecha')->get();

            $idsCxC = $cuotas->pluck('id_ventas_cuotas')->all();
            $pagosPorCuota = collect();
            if (count($idsCxC)) {
                $pagosPorCuota = DB::table('pagos_cuotas as pc')
                    ->select('pc.*', 'tp.tipo_pago_nombre', 'u.nombre_users')
                    ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
                    ->join('users as u', 'u.id_users', '=', 'pc.id_users')
                    ->whereIn('pc.id_ventas_cuotas', $idsCxC)
                    ->whereNull('pc.deleted_at')
                    ->orderBy('pc.pagos_cuota_fecha')
                    ->get()
                    ->groupBy('id_ventas_cuotas');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('CxC Aging');
            $estiloH = ['font'=>['bold'=>true,'color'=>['argb'=>'FFFFFFFF'],'size'=>8,'name'=>'Arial'],
                        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FF46596E']],
                        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font'=>['size'=>8,'name'=>'Arial'],
                        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFD0D0D0']]]];
            $estiloPago = ['font'=>['size'=>7,'name'=>'Arial','color'=>['argb'=>'FF3C5078'],'italic'=>true],
                           'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FFE8F4FD']],
                           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFB8D4EC']]]];

            $row = 1;
            foreach (['A'=>'Cliente','B'=>'RUC/DNI','C'=>'Documento','D'=>'Vencimiento','E'=>'Importe','F'=>'Pagado','G'=>'Saldo','H'=>'Días atraso','I'=>'Vinculada'] as $col=>$h) {
                $sheet->setCellValue("{$col}{$row}", $h);
            }
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloH);
            $row++;

            foreach ($cuotas as $c) {
                $dias = $c->venta_cuota_fecha < $hoy ? now()->diffInDays($c->venta_cuota_fecha) : 0;
                $sheet->setCellValue("A{$row}", $c->cliente_nombre ?: $c->cliente_razonsocial);
                $sheet->setCellValue("B{$row}", $c->cliente_numero);
                $sheet->setCellValue("C{$row}", "{$c->venta_tipo}-{$c->venta_serie}-".str_pad($c->venta_correlativo,8,'0',STR_PAD_LEFT));
                $sheet->setCellValue("D{$row}", date('d/m/Y',strtotime($c->venta_cuota_fecha)));
                $sheet->setCellValue("E{$row}", (float)$c->venta_cuota_importe);
                $sheet->setCellValue("F{$row}", (float)$c->total_pagado);
                $sheet->setCellValue("G{$row}", (float)$c->saldo);
                $sheet->setCellValue("H{$row}", $dias ?: '');
                $sheet->setCellValue("I{$row}", $c->es_vinculada ? 'Sí' : 'No');
                foreach(['E','F','G'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloD);
                $row++;

                // Sub-filas de pagos CxC
                $pagos = $pagosPorCuota->get($c->id_ventas_cuotas, collect());
                foreach ($pagos as $pago) {
                    $sheet->setCellValue("A{$row}", '  → ' . date('d/m/Y', strtotime($pago->pagos_cuota_fecha)));
                    $sheet->setCellValue("B{$row}", $pago->tipo_pago_nombre);
                    $sheet->setCellValue("C{$row}", $pago->pagos_cuota_voucher ?? '');
                    $sheet->setCellValue("D{$row}", '');
                    $sheet->setCellValue("E{$row}", '');
                    $sheet->setCellValue("F{$row}", (float)$pago->pagos_cuota_monto);
                    $sheet->setCellValue("G{$row}", '');
                    $sheet->setCellValue("H{$row}", $pago->nombre_users);
                    $sheet->setCellValue("I{$row}", '');
                    $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloPago);
                    $row++;
                }
            }

            foreach(range('A','I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_cxc_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  REPORTE CxP
    // ══════════════════════════════════════════════════════════

    public function reporteCxP()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_cxp');
            return view('reporte.reporte_cxp', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar el contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosReporteCxP(Request $request): array
    {
        $empresa   = (int) ($request->empresa   ?? 0);
        $sucursal  = (int) ($request->sucursal  ?? 0);
        $proveedor = $request->proveedor ?? '';
        $desde     = $request->desde    ?? null;
        $hasta     = $request->hasta    ?? null;
        $vinculada = $request->vinculada ?? '';
        $estado    = $request->estado   ?? '';
        $hoy       = now()->toDateString();

        $query = DB::table('cuentas_pagar as cp')
            ->select('cp.*', 'p.proveedores_nombre', 'p.proveedores_numero_documento',
                     DB::raw('CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada'))
            ->join('proveedores as p', 'p.id_proveedores', '=', 'cp.id_proveedores')
            ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'p.proveedores_numero_documento')
            ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'cp.id_empresa')
            ->whereNull('cp.deleted_at')->where('cp.cp_estado', '!=', 0);

        if ($sucursal > 0) $query->where('cp.id_sucursal', $sucursal);
        elseif ($empresa > 0) $query->where('cp.id_empresa', $empresa);
        if ($desde) $query->whereDate('cp.cp_fecha_vencimiento', '>=', $desde);
        if ($hasta) $query->whereDate('cp.cp_fecha_vencimiento', '<=', $hasta);
        if ($proveedor) {
            $like = '%'.$proveedor.'%';
            $query->where(fn($q) => $q->where('p.proveedores_nombre','like',$like)->orWhere('p.proveedores_numero_documento','like',$like));
        }
        if ($vinculada === '1') $query->whereRaw('(ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        elseif ($vinculada === '0') $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        if ($estado !== '') $query->where('cp.cp_estado', (int) $estado);

        $cuentas = $query->orderBy('cp.cp_fecha_vencimiento')->get();

        $ids = $cuentas->pluck('id_cuenta_pagar')->all();
        $pagosPorCuenta = collect();
        if (count($ids)) {
            $pagosPorCuenta = DB::table('pagos_cuentas_pagar as pcp')
                ->select('pcp.*', 'tp.tipo_pago_nombre', 'u.nombre_users')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pcp.id_tipo_pago')
                ->join('users as u', 'u.id_users', '=', 'pcp.id_users')
                ->whereIn('pcp.id_cuenta_pagar', $ids)
                ->whereNull('pcp.deleted_at')
                ->orderBy('pcp.pcp_fecha')
                ->get()
                ->groupBy('id_cuenta_pagar');
        }

        return [
            'cuentas'         => $cuentas,
            'pagosPorCuenta'  => $pagosPorCuenta,
            'total'           => $cuentas->sum('cp_monto_total'),
            'pagado'          => $cuentas->sum('cp_monto_pagado'),
            'saldo'           => $cuentas->sum('cp_saldo'),
            'vencido'         => $cuentas->filter(fn($c) => $c->cp_fecha_vencimiento < $hoy && $c->cp_estado != 3)->sum('cp_saldo'),
            'desde'           => $desde,
            'hasta'           => $hasta,
        ];
    }

    public function reporteCxPPdf(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCxP($request);
            $hoy = now()->toDateString();

            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(267, 8, utf8_decode('Reporte de Cuentas por Pagar'), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $fechaD = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaH = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $pdf->Cell(267, 5, utf8_decode("Vencimiento: {$fechaD} al {$fechaH}"), 0, 1, 'C');
            $pdf->Ln(3); $pdf->Cell(267, 0, '', 'T', 1); $pdf->Ln(4);

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetFillColor(70, 89, 110); $pdf->SetTextColor(255, 255, 255);
            foreach (['Proveedor'=>55,'RUC'=>28,'Tipo doc'=>20,'N° doc'=>30,'Emisión'=>22,'Vencimiento'=>22,'Total'=>22,'Pagado'=>22,'Saldo'=>22,'Estado'=>12,'Vinc.'=>12] as $col=>$w) {
                $pdf->Cell($w, 6, utf8_decode($col), 1, 0, 'C', 1);
            }
            $pdf->Ln();
            $pdf->SetTextColor(0, 0, 0);

            $estados = [1=>'Pendiente', 2=>'Parcial', 3=>'Pagada'];
            foreach ($d['cuentas'] as $i => $c) {
                $fill = $i % 2 === 0;
                $pdf->SetFillColor($fill ? 242 : 255, $fill ? 242 : 255, $fill ? 242 : 255);
                $pdf->SetFont('Helvetica', '', 7);
                $vencida = $c->cp_fecha_vencimiento < $hoy && $c->cp_estado != 3;
                if ($vencida) { $pdf->SetTextColor(180, 0, 0); } else { $pdf->SetTextColor(0, 0, 0); }
                $pdf->Cell(55, 5, utf8_decode(mb_strimwidth($c->proveedores_nombre,0,30,'..')),1,0,'L',1);
                $pdf->SetTextColor(0,0,0);
                $pdf->Cell(28, 5, $c->proveedores_numero_documento,1,0,'C',1);
                $pdf->Cell(20, 5, utf8_decode($c->cp_tipo_doc),1,0,'C',1);
                $pdf->Cell(30, 5, $c->cp_numero_doc,1,0,'C',1);
                $pdf->Cell(22, 5, date('d/m/Y',strtotime($c->cp_fecha_emision)),1,0,'C',1);
                $pdf->Cell(22, 5, date('d/m/Y',strtotime($c->cp_fecha_vencimiento)),1,0,'C',1);
                $pdf->Cell(22, 5, 'S/ '.number_format($c->cp_monto_total,2),1,0,'R',1);
                $pdf->Cell(22, 5, 'S/ '.number_format($c->cp_monto_pagado,2),1,0,'R',1);
                $pdf->Cell(22, 5, 'S/ '.number_format($c->cp_saldo,2),1,0,'R',1);
                $pdf->Cell(12, 5, $estados[$c->cp_estado] ?? '-',1,0,'C',1);
                $pdf->Cell(12, 5, ($c->es_vinculada ?? false) ? 'Si' : '',1,1,'C',1);

                // Sub-filas de pagos
                $pagos = $d['pagosPorCuenta']->get($c->id_cuenta_pagar, collect());
                if ($pagos->count()) {
                    $pdf->SetFont('Helvetica', 'I', 6);
                    $pdf->SetFillColor(232, 244, 253);
                    $pdf->SetTextColor(60, 80, 120);
                    // Cabecera sub-fila (solo en primera iteración de pagos de esta cuenta)
                    $pdf->Cell(10, 4, '',0,0,'L',1);
                    $pdf->Cell(22, 4, utf8_decode('Fecha pago'),0,0,'C',1);
                    $pdf->Cell(40, 4, utf8_decode('Medio de pago'),0,0,'L',1);
                    $pdf->Cell(22, 4, 'Monto',0,0,'R',1);
                    $pdf->Cell(45, 4, utf8_decode('N° Operación'),0,0,'L',1);
                    $pdf->Cell(45, 4, 'Voucher',0,0,'L',1);
                    $pdf->Cell(50, 4, utf8_decode('Registrado por'),0,0,'L',1);
                    $pdf->Cell(33, 4, '',0,1,'L',1);
                    foreach ($pagos as $pago) {
                        $pdf->Cell(10, 4, '',0,0,'L',1);
                        $pdf->Cell(22, 4, date('d/m/Y',strtotime($pago->pcp_fecha)),0,0,'C',1);
                        $pdf->Cell(40, 4, utf8_decode(mb_strimwidth($pago->tipo_pago_nombre,0,25,'..')),0,0,'L',1);
                        $pdf->Cell(22, 4, 'S/ '.number_format($pago->pcp_monto,2),0,0,'R',1);
                        $pdf->Cell(45, 4, utf8_decode(mb_strimwidth($pago->pcp_numero_operacion ?? '-',0,28,'..')),0,0,'L',1);
                        $pdf->Cell(45, 4, utf8_decode(mb_strimwidth($pago->pcp_voucher ?? '-',0,28,'..')),0,0,'L',1);
                        $pdf->Cell(50, 4, utf8_decode(mb_strimwidth($pago->nombre_users,0,30,'..')),0,0,'L',1);
                        $pdf->Cell(33, 4, '',0,1,'L',1);
                    }
                    $pdf->SetTextColor(0,0,0);
                }
            }

            $pdf->SetFont('Helvetica','B',8); $pdf->SetFillColor(220,230,241);
            $pdf->Cell(177,5,'TOTAL',1,0,'R',1);
            $pdf->Cell(22,5,'S/ '.number_format($d['total'],2),1,0,'R',1);
            $pdf->Cell(22,5,'S/ '.number_format($d['pagado'],2),1,0,'R',1);
            $pdf->Cell(22,5,'S/ '.number_format($d['saldo'],2),1,0,'R',1);
            $pdf->Cell(12,5,'',1,0,'C',1);
            $pdf->Cell(12,5,'',1,1,'C',1);

            ob_end_clean();
            $pdf->Output('I', 'reporte_cxp_' . date('Ymd') . '.pdf');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteCxPExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteCxP($request);
            $hoy = now()->toDateString();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('CxP');
            $estiloH = ['font'=>['bold'=>true,'color'=>['argb'=>'FFFFFFFF'],'size'=>8,'name'=>'Arial'],
                        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FF46596E']],
                        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font'=>['size'=>8,'name'=>'Arial'],
                        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFD0D0D0']]]];
            $estiloPago = ['font'=>['size'=>7,'name'=>'Arial','color'=>['argb'=>'FF3C5078'],'italic'=>true],
                           'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>'FFE8F4FD']],
                           'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFB8D4EC']]]];

            $row = 1;
            $headers = ['A'=>'Proveedor','B'=>'RUC','C'=>'Tipo doc','D'=>'N° doc',
                        'E'=>'Emisión','F'=>'Vencimiento','G'=>'Total','H'=>'Pagado','I'=>'Saldo','J'=>'Estado','K'=>'Vinculada'];
            foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($estiloH);
            $row++;

            $estados = [1=>'Pendiente',2=>'Parcial',3=>'Pagada'];
            foreach ($d['cuentas'] as $c) {
                $sheet->setCellValue("A{$row}", $c->proveedores_nombre);
                $sheet->setCellValue("B{$row}", $c->proveedores_numero_documento);
                $sheet->setCellValue("C{$row}", $c->cp_tipo_doc);
                $sheet->setCellValue("D{$row}", $c->cp_numero_doc);
                $sheet->setCellValue("E{$row}", date('d/m/Y',strtotime($c->cp_fecha_emision)));
                $sheet->setCellValue("F{$row}", date('d/m/Y',strtotime($c->cp_fecha_vencimiento)));
                $sheet->setCellValue("G{$row}", (float)$c->cp_monto_total);
                $sheet->setCellValue("H{$row}", (float)$c->cp_monto_pagado);
                $sheet->setCellValue("I{$row}", (float)$c->cp_saldo);
                $sheet->setCellValue("J{$row}", $estados[$c->cp_estado] ?? '-');
                $sheet->setCellValue("K{$row}", ($c->es_vinculada ?? false) ? 'Sí' : 'No');
                foreach(['G','H','I'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($estiloD);
                $row++;

                // Sub-filas de pagos
                $pagos = $d['pagosPorCuenta']->get($c->id_cuenta_pagar, collect());
                foreach ($pagos as $pago) {
                    $sheet->setCellValue("A{$row}", '  → ' . date('d/m/Y', strtotime($pago->pcp_fecha)));
                    $sheet->setCellValue("B{$row}", $pago->tipo_pago_nombre);
                    $sheet->setCellValue("C{$row}", '');
                    $sheet->setCellValue("D{$row}", $pago->pcp_numero_operacion ?? '');
                    $sheet->setCellValue("E{$row}", $pago->pcp_voucher ?? '');
                    $sheet->setCellValue("F{$row}", $pago->nombre_users);
                    $sheet->setCellValue("G{$row}", '');
                    $sheet->setCellValue("H{$row}", (float)$pago->pcp_monto);
                    $sheet->setCellValue("I{$row}", '');
                    $sheet->setCellValue("J{$row}", '');
                    $sheet->setCellValue("K{$row}", '');
                    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($estiloPago);
                    $row++;
                }
            }

            foreach(range('A','K') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_cxp_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REPORTE STOCK
    // ─────────────────────────────────────────────────────────────
    public function reporteStock()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_stock');
            return view('reporte.reporte_stock', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosReporteStock(Request $request): array
    {
        $idEmpresa  = $request->empresa   ?? null;
        $idSucursal = $request->sucursal  ?? null;
        $busqueda   = $request->busqueda  ?? null;
        $idCategoria= $request->categoria ?? null;
        $estado     = $request->estado    ?? null;

        $query = DB::table('producto_sucursal as ps')
            ->join('productos as p',  'p.id_pro',    '=', 'ps.id_pro')
            ->join('tiendas as t',    't.id_tienda', '=', 'ps.id_sucursal')
            ->join('empresa as e',    'e.id_empresa','=', 't.id_empresa')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->select(
                'p.pro_codigo',
                'p.pro_nombre',
                'c.ca_nombre as categoria_nombre',
                't.tienda_nombre as sucursal_nombre',
                'e.empresa_nombre',
                'ps.ps_stock',
                'ps.ps_stock_minimo',
                DB::raw("CASE
                    WHEN ps.ps_stock <= 0 THEN 'sin_stock'
                    WHEN ps.ps_stock <= ps.ps_stock_minimo THEN 'critico'
                    ELSE 'ok'
                END as estado_stock")
            );

        if ($idSucursal) $query->where('ps.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $query->where('t.id_empresa', $idEmpresa);

        if ($busqueda) $query->where(function($q) use ($busqueda) {
            $q->where('p.pro_nombre', 'like', "%{$busqueda}%")
              ->orWhere('p.pro_codigo', 'like', "%{$busqueda}%");
        });
        if ($idCategoria) $query->where('p.id_ca', $idCategoria);
        if ($estado) {
            if ($estado === 'sin_stock') $query->where('ps.ps_stock', '<=', 0);
            elseif ($estado === 'critico') $query->whereRaw('ps.ps_stock > 0 AND ps.ps_stock <= ps.ps_stock_minimo');
            elseif ($estado === 'ok') $query->whereRaw('ps.ps_stock > ps.ps_stock_minimo');
        }

        $filas = $query->orderBy('p.pro_nombre')->get();

        $totales = [
            'total'     => $filas->count(),
            'ok'        => $filas->where('estado_stock', 'ok')->count(),
            'critico'   => $filas->where('estado_stock', 'critico')->count(),
            'sin_stock' => $filas->where('estado_stock', 'sin_stock')->count(),
        ];

        return compact('filas', 'totales');
    }

    public function reporteStockPdf(Request $request)
    {
        try {
            $d   = $this->obtenerDatosReporteStock($request);
            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(267, 8, 'REPORTE DE STOCK', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(267, 6, 'Generado: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');

            // Cabecera tabla  30+90+45+50+20+17+15 = 267
            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(30, 6, 'Código',    1, 0, 'C', true);
            $pdf->Cell(90, 6, 'Producto',  1, 0, 'C', true);
            $pdf->Cell(45, 6, 'Categoría', 1, 0, 'C', true);
            $pdf->Cell(50, 6, 'Sucursal',  1, 0, 'C', true);
            $pdf->Cell(20, 6, 'Stock',     1, 0, 'C', true);
            $pdf->Cell(17, 6, 'Mínimo',    1, 0, 'C', true);
            $pdf->Cell(15, 6, 'Estado',    1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7);
            $fill = false;
            foreach ($d['filas'] as $f) {
                if ($f->estado_stock === 'sin_stock')    $pdf->SetFillColor(255, 200, 200);
                elseif ($f->estado_stock === 'critico')  $pdf->SetFillColor(255, 243, 205);
                else                                     $pdf->SetFillColor(240, 248, 240);

                $pdf->Cell(30, 5, $f->pro_codigo,        1, 0, 'L', true);
                $pdf->Cell(90, 5, $f->pro_nombre,        1, 0, 'L', true);
                $pdf->Cell(45, 5, $f->categoria_nombre ?? '-', 1, 0, 'L', true);
                $pdf->Cell(50, 5, $f->sucursal_nombre,   1, 0, 'L', true);
                $pdf->Cell(20, 5, number_format($f->ps_stock, 2), 1, 0, 'R', true);
                $pdf->Cell(17, 5, number_format($f->ps_stock_minimo, 2), 1, 0, 'R', true);
                $estado_label = $f->estado_stock === 'sin_stock' ? 'Sin stock' : ($f->estado_stock === 'critico' ? 'Crítico' : 'OK');
                $pdf->Cell(15, 5, $estado_label, 1, 1, 'C', true);
            }

            ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="reporte_stock_' . date('Ymd_His') . '.pdf"');
            echo $pdf->Output('S', '');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteStockExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteStock($request);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('Stock');

            $estiloH = ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font' => ['size' => 8, 'name' => 'Arial'],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];

            $row = 1;
            $headers = ['A' => 'Código', 'B' => 'Producto', 'C' => 'Categoría', 'D' => 'Sucursal',
                        'E' => 'Stock', 'F' => 'Mínimo', 'G' => 'Estado'];
            foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($estiloH);
            $row++;

            foreach ($d['filas'] as $f) {
                $sheet->setCellValue("A{$row}", $f->pro_codigo);
                $sheet->setCellValue("B{$row}", $f->pro_nombre);
                $sheet->setCellValue("C{$row}", $f->categoria_nombre ?? '-');
                $sheet->setCellValue("D{$row}", $f->sucursal_nombre);
                $sheet->setCellValue("E{$row}", (float)$f->ps_stock);
                $sheet->setCellValue("F{$row}", (float)$f->ps_stock_minimo);
                $estado_label = $f->estado_stock === 'sin_stock' ? 'Sin stock' : ($f->estado_stock === 'critico' ? 'Crítico' : 'OK');
                $sheet->setCellValue("G{$row}", $estado_label);
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($estiloD);
                $row++;
            }

            foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_stock_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REPORTE COMPRAS
    // ─────────────────────────────────────────────────────────────
    public function reporteCompras()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_compras');
            return view('reporte.reporte_compras', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosReporteCompras(Request $request): array
    {
        $idEmpresa  = (int) ($request->empresa  ?? 0);
        $idSucursal = (int) ($request->sucursal ?? 0);
        $desde      = $request->desde      ?? null;
        $hasta      = $request->hasta      ?? null;
        $proveedor  = $request->proveedor  ?? null;
        $estado     = $request->estado     ?? '';
        $estadoOC   = $request->estado_oc  ?? '';
        $tipo       = $request->tipo       ?? 'compras';

        $applyFilters = function ($query) use ($idEmpresa, $idSucursal, $desde, $hasta, $proveedor, $estado, $estadoOC) {
            if ($idSucursal > 0) $query->where('oc.id_sucursal', $idSucursal);
            elseif ($idEmpresa > 0) $query->where('t.id_empresa', $idEmpresa);
            if ($desde) $query->whereDate('oc.orden_compra_fecha', '>=', $desde);
            if ($hasta) $query->whereDate('oc.orden_compra_fecha', '<=', $hasta);
            if ($proveedor) {
                $like = '%' . $proveedor . '%';
                $query->where(fn($q) => $q->where('pr.proveedores_nombre', 'like', $like)
                                          ->orWhere('pr.proveedores_numero_documento', 'like', $like));
            }
            if ($estado !== '') $query->where('oc.orden_compra_activo', $estado === 'activo' ? 1 : 0);
            if ($estadoOC !== '') $query->where('oc.orden_compra_estado', $estadoOC);
        };

        if ($tipo === 'detallado') {
            $query = DB::table('orden_compra as oc')
                ->join('proveedores as pr',            'pr.id_proveedores',   '=', 'oc.id_proveedores')
                ->join('tiendas as t',                 't.id_tienda',         '=', 'oc.id_sucursal')
                ->join('orden_compra_detalle as ocd',  'ocd.id_orden_compra', '=', 'oc.id_orden_compra')
                ->leftJoin('productos as pro',         'pro.id_pro',          '=', 'ocd.id_pro')
                ->select(
                    'oc.orden_compra_codigo', 'oc.orden_compra_numero', 'oc.orden_compra_fecha',
                    'oc.orden_compra_estado',
                    'pr.proveedores_nombre', 'pr.proveedores_numero_documento',
                    't.tienda_nombre as sucursal_nombre',
                    'ocd.detalle_orden_nombre_producto', 'pro.pro_codigo',
                    'ocd.detalle_compra_cantidad', 'ocd.detalle_compra_cantidad_recibida',
                    'ocd.detalle_compra_total_pedido',
                    DB::raw('COALESCE(ocd.flete, 0) as detalle_flete')
                );
            $applyFilters($query);
            $filas = $query->orderByDesc('oc.orden_compra_fecha')->orderBy('pr.proveedores_nombre')->get();
            $totales = [
                'tipo'        => 'detallado',
                'cantidad'    => $filas->count(),
                'total_costo' => $filas->sum('detalle_compra_total_pedido'),
                'total_flete' => $filas->sum('detalle_flete'),
            ];
        } elseif ($tipo === 'resumen') {
            $query = DB::table('orden_compra as oc')
                ->join('proveedores as pr', 'pr.id_proveedores', '=', 'oc.id_proveedores')
                ->join('tiendas as t',      't.id_tienda',       '=', 'oc.id_sucursal')
                ->select(
                    'pr.proveedores_nombre', 'pr.proveedores_numero_documento',
                    DB::raw('COUNT(DISTINCT oc.id_orden_compra) as total_ordenes'),
                    DB::raw('SUM(oc.orden_compra_total) as total_mercaderia'),
                    DB::raw('SUM(COALESCE(oc.orden_compra_flete,0)) as total_flete'),
                    DB::raw('SUM(COALESCE(oc.orden_compra_gastos_operativos,0)) as total_gastos'),
                    DB::raw('SUM(oc.orden_compra_total + COALESCE(oc.orden_compra_flete,0) + COALESCE(oc.orden_compra_gastos_operativos,0)) as gran_total')
                )
                ->groupBy('pr.id_proveedores', 'pr.proveedores_nombre', 'pr.proveedores_numero_documento');
            $applyFilters($query);
            $filas = $query->orderBy('pr.proveedores_nombre')->get();
            $totales = [
                'tipo'       => 'resumen',
                'cantidad'   => $filas->count(),
                'ordenes'    => $filas->sum('total_ordenes'),
                'mercaderia' => $filas->sum('total_mercaderia'),
                'flete'      => $filas->sum('total_flete'),
                'gastos'     => $filas->sum('total_gastos'),
                'gran_total' => $filas->sum('gran_total'),
            ];
        } else {
            $query = DB::table('orden_compra as oc')
                ->join('proveedores as pr', 'pr.id_proveedores', '=', 'oc.id_proveedores')
                ->join('tiendas as t',      't.id_tienda',       '=', 'oc.id_sucursal')
                ->join('empresa as e',      'e.id_empresa',      '=', 't.id_empresa')
                ->select(
                    'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_codigo',
                    'oc.orden_compra_fecha', 'oc.orden_compra_tipo_doc', 'oc.orden_compra_numero_doc',
                    'oc.orden_compra_total', 'oc.orden_compra_flete', 'oc.orden_compra_gastos_operativos',
                    'oc.orden_compra_estado', 'oc.orden_compra_activo',
                    'pr.proveedores_nombre', 'pr.proveedores_numero_documento',
                    't.tienda_nombre as sucursal_nombre', 'e.empresa_nombre',
                    DB::raw('(oc.orden_compra_total + COALESCE(oc.orden_compra_flete,0) + COALESCE(oc.orden_compra_gastos_operativos,0)) as gran_total'),
                    DB::raw('(SELECT COUNT(*) FROM orden_compra_detalle ocd WHERE ocd.id_orden_compra = oc.id_orden_compra) as total_items')
                );
            $applyFilters($query);
            $filas = $query->orderByDesc('oc.orden_compra_fecha')->get();
            $totales = [
                'tipo'       => 'compras',
                'cantidad'   => $filas->count(),
                'mercaderia' => $filas->sum('orden_compra_total'),
                'flete'      => $filas->sum('orden_compra_flete'),
                'gastos'     => $filas->sum('orden_compra_gastos_operativos'),
                'gran_total' => $filas->sum('gran_total'),
            ];
        }

        return compact('filas', 'totales', 'desde', 'hasta', 'tipo');
    }

    public function reporteComprasPdf(Request $request)
    {
        try {
            $d    = $this->obtenerDatosReporteCompras($request);
            $tipo = $d['tipo'];
            $t    = $d['totales'];
            $pdf  = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();

            $titulos = ['compras' => 'REPORTE DE COMPRAS', 'detallado' => 'REPORTE DE COMPRAS DETALLADO', 'resumen' => 'RESUMEN DE COMPRAS POR PROVEEDOR'];
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(267, 8, $titulos[$tipo] ?? 'REPORTE DE COMPRAS', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
            $fechaD = $d['desde'] ? date('d/m/Y', strtotime($d['desde'])) : '-';
            $fechaH = $d['hasta'] ? date('d/m/Y', strtotime($d['hasta'])) : '-';
            $pdf->Cell(267, 5, utf8_decode("Per" . chr(237) . "odo: {$fechaD} al {$fechaH}"), 0, 1, 'C');
            $pdf->Cell(267, 5, 'Generado: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');
            $pdf->Ln(2);

            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 7);
            $estadosLabel = ['pendiente'=>'Pendiente','en_transito'=>'En trans.','recibida'=>'Recibida','cancelada'=>'Cancelada'];

            if ($tipo === 'detallado') {
                // 20+25+52+28+55+18+15+15+24+15 = 267
                $pdf->Cell(20, 6, 'Fecha',       1, 0, 'C', true);
                $pdf->Cell(25, 6, utf8_decode('N°  Orden'),  1, 0, 'C', true);
                $pdf->Cell(52, 6, 'Proveedor',   1, 0, 'C', true);
                $pdf->Cell(28, 6, 'Sucursal',    1, 0, 'C', true);
                $pdf->Cell(55, 6, 'Producto',    1, 0, 'C', true);
                $pdf->Cell(18, 6, utf8_decode('Código'),     1, 0, 'C', true);
                $pdf->Cell(15, 6, 'Cant.P.',     1, 0, 'C', true);
                $pdf->Cell(15, 6, 'Cant.R.',     1, 0, 'C', true);
                $pdf->Cell(24, 6, 'Costo',       1, 0, 'C', true);
                $pdf->Cell(15, 6, 'Flete',       1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0); $pdf->SetFont('Helvetica', '', 7);
                $fill = false;
                foreach ($d['filas'] as $f) {
                    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                    $pdf->Cell(20, 5, date('d/m/Y', strtotime($f->orden_compra_fecha)), 1, 0, 'C', true);
                    $pdf->Cell(25, 5, $f->orden_compra_codigo ?: ($f->orden_compra_numero ?? '-'), 1, 0, 'C', true);
                    $pdf->Cell(52, 5, utf8_decode(mb_strimwidth($f->proveedores_nombre, 0, 28, '..')), 1, 0, 'L', true);
                    $pdf->Cell(28, 5, utf8_decode(mb_strimwidth($f->sucursal_nombre, 0, 15, '..')), 1, 0, 'L', true);
                    $pdf->Cell(55, 5, utf8_decode(mb_strimwidth($f->detalle_orden_nombre_producto ?? '', 0, 30, '..')), 1, 0, 'L', true);
                    $pdf->Cell(18, 5, $f->pro_codigo ?? '-', 1, 0, 'C', true);
                    $pdf->Cell(15, 5, number_format($f->detalle_compra_cantidad, 2), 1, 0, 'R', true);
                    $pdf->Cell(15, 5, $f->detalle_compra_cantidad_recibida !== null ? number_format($f->detalle_compra_cantidad_recibida, 2) : '-', 1, 0, 'R', true);
                    $pdf->Cell(24, 5, 'S/ ' . number_format($f->detalle_compra_total_pedido, 2), 1, 0, 'R', true);
                    $pdf->Cell(15, 5, 'S/ ' . number_format($f->detalle_flete, 2), 1, 1, 'R', true);
                    $fill = !$fill;
                }
                $pdf->SetFont('Helvetica', 'B', 7); $pdf->SetFillColor(220, 230, 241);
                $pdf->Cell(252, 5, utf8_decode('TOTAL (' . $t['cantidad'] . ' ' . chr(237) . 'tems)'), 1, 0, 'R', true);
                $pdf->Cell(24,  5, 'S/ ' . number_format($t['total_costo'], 2), 1, 0, 'R', true);
                $pdf->Cell(15,  5, '',                                           1, 1, 'C', true);

            } elseif ($tipo === 'resumen') {
                // 80+30+25+35+30+30+37 = 267
                $pdf->Cell(80, 6, 'Proveedor',    1, 0, 'C', true);
                $pdf->Cell(30, 6, 'RUC / Doc.',   1, 0, 'C', true);
                $pdf->Cell(25, 6, utf8_decode('N° Órdenes'), 1, 0, 'C', true);
                $pdf->Cell(35, 6, utf8_decode('Mercadería'), 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Flete',         1, 0, 'C', true);
                $pdf->Cell(30, 6, 'G. Oper.',      1, 0, 'C', true);
                $pdf->Cell(37, 6, 'Gran Total',    1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0); $pdf->SetFont('Helvetica', '', 7);
                $fill = false;
                foreach ($d['filas'] as $f) {
                    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                    $pdf->Cell(80, 5, utf8_decode(mb_strimwidth($f->proveedores_nombre, 0, 45, '..')), 1, 0, 'L', true);
                    $pdf->Cell(30, 5, $f->proveedores_numero_documento, 1, 0, 'C', true);
                    $pdf->Cell(25, 5, $f->total_ordenes, 1, 0, 'C', true);
                    $pdf->Cell(35, 5, 'S/ ' . number_format($f->total_mercaderia, 2), 1, 0, 'R', true);
                    $pdf->Cell(30, 5, 'S/ ' . number_format($f->total_flete, 2),      1, 0, 'R', true);
                    $pdf->Cell(30, 5, 'S/ ' . number_format($f->total_gastos, 2),     1, 0, 'R', true);
                    $pdf->Cell(37, 5, 'S/ ' . number_format($f->gran_total, 2),       1, 1, 'R', true);
                    $fill = !$fill;
                }
                $pdf->SetFont('Helvetica', 'B', 7); $pdf->SetFillColor(220, 230, 241);
                $pdf->Cell(135, 5, 'TOTAL (' . $t['cantidad'] . ' proveedores)', 1, 0, 'R', true);
                $pdf->Cell(35,  5, 'S/ ' . number_format($t['mercaderia'], 2),   1, 0, 'R', true);
                $pdf->Cell(30,  5, 'S/ ' . number_format($t['flete'], 2),        1, 0, 'R', true);
                $pdf->Cell(30,  5, 'S/ ' . number_format($t['gastos'], 2),       1, 0, 'R', true);
                $pdf->Cell(37,  5, 'S/ ' . number_format($t['gran_total'], 2),   1, 1, 'R', true);

            } else {
                // compras: 22+28+62+38+20+22+22+20+20+13 = 267
                $pdf->Cell(22, 6, 'Fecha',        1, 0, 'C', true);
                $pdf->Cell(28, 6, utf8_decode('N° Orden'),  1, 0, 'C', true);
                $pdf->Cell(62, 6, 'Proveedor',    1, 0, 'C', true);
                $pdf->Cell(38, 6, 'Sucursal',     1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Estado',       1, 0, 'C', true);
                $pdf->Cell(22, 6, utf8_decode('Mercadería'), 1, 0, 'C', true);
                $pdf->Cell(22, 6, 'Flete',        1, 0, 'C', true);
                $pdf->Cell(20, 6, 'G. Oper.',     1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Total',        1, 0, 'C', true);
                $pdf->Cell(13, 6, 'Activo',       1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0); $pdf->SetFont('Helvetica', '', 7);
                $fill = false;
                foreach ($d['filas'] as $f) {
                    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                    $pdf->Cell(22, 5, date('d/m/Y', strtotime($f->orden_compra_fecha)), 1, 0, 'C', true);
                    $pdf->Cell(28, 5, $f->orden_compra_codigo ?: ($f->orden_compra_numero ?? '-'), 1, 0, 'C', true);
                    $pdf->Cell(62, 5, utf8_decode(mb_strimwidth($f->proveedores_nombre, 0, 34, '..')), 1, 0, 'L', true);
                    $pdf->Cell(38, 5, utf8_decode(mb_strimwidth($f->sucursal_nombre, 0, 20, '..')), 1, 0, 'L', true);
                    $pdf->Cell(20, 5, $estadosLabel[$f->orden_compra_estado ?? ''] ?? '-', 1, 0, 'C', true);
                    $pdf->Cell(22, 5, 'S/ ' . number_format($f->orden_compra_total, 2), 1, 0, 'R', true);
                    $pdf->Cell(22, 5, 'S/ ' . number_format($f->orden_compra_flete ?? 0, 2), 1, 0, 'R', true);
                    $pdf->Cell(20, 5, 'S/ ' . number_format($f->orden_compra_gastos_operativos ?? 0, 2), 1, 0, 'R', true);
                    $pdf->Cell(20, 5, 'S/ ' . number_format($f->gran_total, 2), 1, 0, 'R', true);
                    $pdf->Cell(13, 5, $f->orden_compra_activo ? 'Si' : 'No', 1, 1, 'C', true);
                    $fill = !$fill;
                }
                $pdf->SetFont('Helvetica', 'B', 7); $pdf->SetFillColor(220, 230, 241);
                $pdf->Cell(170, 5, utf8_decode('TOTAL (' . $t['cantidad'] . ' ' . chr(243) . 'rdenes)'), 1, 0, 'R', true);
                $pdf->Cell(22,  5, 'S/ ' . number_format($t['mercaderia'], 2),  1, 0, 'R', true);
                $pdf->Cell(22,  5, 'S/ ' . number_format($t['flete'], 2),       1, 0, 'R', true);
                $pdf->Cell(20,  5, 'S/ ' . number_format($t['gastos'], 2),      1, 0, 'R', true);
                $pdf->Cell(20,  5, 'S/ ' . number_format($t['gran_total'], 2),  1, 0, 'R', true);
                $pdf->Cell(13,  5, '',                                           1, 1, 'C', true);
            }

            ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="reporte_compras_' . $tipo . '_' . date('Ymd_His') . '.pdf"');
            echo $pdf->Output('S', '');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteComprasExcel(Request $request)
    {
        try {
            $d    = $this->obtenerDatosReporteCompras($request);
            $tipo = $d['tipo'];
            $t    = $d['totales'];

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $titulos = ['compras' => 'Compras', 'detallado' => 'Compras Detallado', 'resumen' => 'Resumen Compras'];
            $sheet->setTitle($titulos[$tipo] ?? 'Compras');

            $estiloH = ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font' => ['size' => 8, 'name' => 'Arial'],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];
            $estiloT = ['font' => ['bold' => true, 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFB0B0B0']]]];

            $estadosLabel = ['pendiente'=>'Pendiente','en_transito'=>'En tránsito','recibida'=>'Recibida','cancelada'=>'Cancelada'];
            $row = 1;

            if ($tipo === 'detallado') {
                $headers = ['A'=>'Fecha','B'=>utf8_decode('N° Orden'),'C'=>'Proveedor','D'=>'RUC','E'=>'Sucursal',
                            'F'=>'Producto','G'=>utf8_decode('Código'),'H'=>'Cant. Pedida','I'=>'Cant. Recibida',
                            'J'=>'Costo','K'=>'Flete'];
                $lastCol = 'K';
                foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloH);
                $row++;
                foreach ($d['filas'] as $f) {
                    $sheet->setCellValue("A{$row}", date('d/m/Y', strtotime($f->orden_compra_fecha)));
                    $sheet->setCellValue("B{$row}", $f->orden_compra_codigo ?: ($f->orden_compra_numero ?? '-'));
                    $sheet->setCellValue("C{$row}", $f->proveedores_nombre);
                    $sheet->setCellValue("D{$row}", $f->proveedores_numero_documento);
                    $sheet->setCellValue("E{$row}", $f->sucursal_nombre);
                    $sheet->setCellValue("F{$row}", $f->detalle_orden_nombre_producto ?? '');
                    $sheet->setCellValue("G{$row}", $f->pro_codigo ?? '');
                    $sheet->setCellValue("H{$row}", (float)$f->detalle_compra_cantidad);
                    $sheet->setCellValue("I{$row}", $f->detalle_compra_cantidad_recibida !== null ? (float)$f->detalle_compra_cantidad_recibida : '');
                    $sheet->setCellValue("J{$row}", (float)$f->detalle_compra_total_pedido);
                    $sheet->setCellValue("K{$row}", (float)$f->detalle_flete);
                    foreach (['J', 'K'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloD);
                    $row++;
                }
                $sheet->setCellValue("A{$row}", 'TOTAL (' . $t['cantidad'] . ' ítems)');
                $sheet->setCellValue("J{$row}", (float)$t['total_costo']);
                $sheet->setCellValue("K{$row}", (float)$t['total_flete']);
                foreach (['J', 'K'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloT);

            } elseif ($tipo === 'resumen') {
                $headers = ['A'=>'Proveedor','B'=>'RUC / Doc.','C'=>utf8_decode('N° Órdenes'),
                            'D'=>utf8_decode('Mercadería'),'E'=>'Flete','F'=>'G. Operativos','G'=>'Gran Total'];
                $lastCol = 'G';
                foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloH);
                $row++;
                foreach ($d['filas'] as $f) {
                    $sheet->setCellValue("A{$row}", $f->proveedores_nombre);
                    $sheet->setCellValue("B{$row}", $f->proveedores_numero_documento);
                    $sheet->setCellValue("C{$row}", (int)$f->total_ordenes);
                    $sheet->setCellValue("D{$row}", (float)$f->total_mercaderia);
                    $sheet->setCellValue("E{$row}", (float)$f->total_flete);
                    $sheet->setCellValue("F{$row}", (float)$f->total_gastos);
                    $sheet->setCellValue("G{$row}", (float)$f->gran_total);
                    foreach (['D','E','F','G'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloD);
                    $row++;
                }
                $sheet->setCellValue("A{$row}", 'TOTAL (' . $t['cantidad'] . ' proveedores)');
                $sheet->setCellValue("D{$row}", (float)$t['mercaderia']);
                $sheet->setCellValue("E{$row}", (float)$t['flete']);
                $sheet->setCellValue("F{$row}", (float)$t['gastos']);
                $sheet->setCellValue("G{$row}", (float)$t['gran_total']);
                foreach (['D','E','F','G'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloT);

            } else {
                $headers = ['A'=>'Fecha','B'=>utf8_decode('N° Orden'),'C'=>'Proveedor','D'=>'RUC',
                            'E'=>'Sucursal','F'=>'Estado proceso','G'=>utf8_decode('Mercadería'),
                            'H'=>'Flete','I'=>'G. Operativos','J'=>'Total','K'=>'Activo'];
                $lastCol = 'K';
                foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloH);
                $row++;
                foreach ($d['filas'] as $f) {
                    $sheet->setCellValue("A{$row}", date('d/m/Y', strtotime($f->orden_compra_fecha)));
                    $sheet->setCellValue("B{$row}", $f->orden_compra_codigo ?: ($f->orden_compra_numero ?? '-'));
                    $sheet->setCellValue("C{$row}", $f->proveedores_nombre);
                    $sheet->setCellValue("D{$row}", $f->proveedores_numero_documento);
                    $sheet->setCellValue("E{$row}", $f->sucursal_nombre);
                    $sheet->setCellValue("F{$row}", $estadosLabel[$f->orden_compra_estado ?? ''] ?? '-');
                    $sheet->setCellValue("G{$row}", (float)$f->orden_compra_total);
                    $sheet->setCellValue("H{$row}", (float)($f->orden_compra_flete ?? 0));
                    $sheet->setCellValue("I{$row}", (float)($f->orden_compra_gastos_operativos ?? 0));
                    $sheet->setCellValue("J{$row}", (float)$f->gran_total);
                    $sheet->setCellValue("K{$row}", $f->orden_compra_activo ? 'Activo' : 'Inactivo');
                    foreach (['G','H','I','J'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloD);
                    $row++;
                }
                $sheet->setCellValue("A{$row}", 'TOTAL (' . $t['cantidad'] . ' órdenes)');
                $sheet->setCellValue("G{$row}", (float)$t['mercaderia']);
                $sheet->setCellValue("H{$row}", (float)$t['flete']);
                $sheet->setCellValue("I{$row}", (float)$t['gastos']);
                $sheet->setCellValue("J{$row}", (float)$t['gran_total']);
                foreach (['G','H','I','J'] as $col) $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($estiloT);
            }

            foreach (range('A', $lastCol) as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_compras_' . $tipo . '_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REPORTE TRANSFERENCIAS
    // ─────────────────────────────────────────────────────────────
    public function reporteTransferencias()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_transferencias');
            return view('reporte.reporte_transferencias', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosReporteTransferencias(Request $request): array
    {
        $idEmpresa  = $request->empresa  ?? null;
        $idSucursal = $request->sucursal ?? null;
        $desde      = $request->desde    ?? null;
        $hasta      = $request->hasta    ?? null;

        $query = DB::table('transferencias_stock as ts')
            ->join('tiendas as so', 'so.id_tienda', '=', 'ts.id_almacen_origen')
            ->join('tiendas as sd', 'sd.id_tienda', '=', 'ts.id_tienda_destino')
            ->join('empresa as e',  'e.id_empresa', '=', 'so.id_empresa')
            ->leftJoin('users as u','u.id_users',   '=', 'ts.id_users')
            ->select(
                'ts.id_transferencia',
                'ts.transferencia_numero',
                'ts.transferencia_fecha',
                'ts.transferencia_motivo',
                'so.tienda_nombre as origen_nombre',
                'sd.tienda_nombre as destino_nombre',
                'e.empresa_nombre',
                DB::raw("COALESCE(u.nombre_users,'') as usuario_nombre"),
                DB::raw('(SELECT COUNT(*) FROM transferencias_stock_detalle tsd WHERE tsd.id_transferencia = ts.id_transferencia) as total_items'),
                DB::raw('(SELECT COALESCE(SUM(tsd2.detalle_cantidad),0) FROM transferencias_stock_detalle tsd2 WHERE tsd2.id_transferencia = ts.id_transferencia) as total_unidades')
            );

        if ($idSucursal) {
            $query->where(function($q) use ($idSucursal) {
                $q->where('ts.id_almacen_origen', $idSucursal)
                  ->orWhere('ts.id_tienda_destino', $idSucursal);
            });
        } elseif ($idEmpresa) {
            $query->where('e.id_empresa', $idEmpresa);
        }

        if ($desde) $query->whereDate('ts.transferencia_fecha', '>=', $desde);
        if ($hasta) $query->whereDate('ts.transferencia_fecha', '<=', $hasta);

        $filas = $query->orderByDesc('ts.transferencia_fecha')->get();

        $totales = [
            'cantidad'       => $filas->count(),
            'total_items'    => $filas->sum('total_items'),
            'total_unidades' => $filas->sum('total_unidades'),
        ];

        return compact('filas', 'totales', 'desde', 'hasta');
    }

    public function reporteTransferenciasPdf(Request $request)
    {
        try {
            $d   = $this->obtenerDatosReporteTransferencias($request);
            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(267, 8, 'REPORTE DE TRANSFERENCIAS DE STOCK', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
            if ($d['desde'] || $d['hasta'])
                $pdf->Cell(267, 6, 'Período: ' . ($d['desde'] ?? '-') . ' al ' . ($d['hasta'] ?? '-'), 0, 1, 'C');
            $pdf->Cell(267, 6, 'Generado: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');

            // Cabecera  25+30+60+60+55+15+12+10 = 267
            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(25, 6, 'Fecha',         1, 0, 'C', true);
            $pdf->Cell(30, 6, 'N° Trans.',      1, 0, 'C', true);
            $pdf->Cell(60, 6, 'Origen',         1, 0, 'C', true);
            $pdf->Cell(60, 6, 'Destino',        1, 0, 'C', true);
            $pdf->Cell(55, 6, 'Motivo',         1, 0, 'C', true);
            $pdf->Cell(15, 6, 'Items',          1, 0, 'C', true);
            $pdf->Cell(12, 6, 'Unid.',          1, 0, 'C', true);
            $pdf->Cell(10, 6, 'User',           1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7);
            $fill = false;
            foreach ($d['filas'] as $f) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(25, 5, date('d/m/Y', strtotime($f->transferencia_fecha)), 1, 0, 'C', true);
                $pdf->Cell(30, 5, $f->transferencia_numero ?? '-',                   1, 0, 'C', true);
                $pdf->Cell(60, 5, $f->origen_nombre,                                 1, 0, 'L', true);
                $pdf->Cell(60, 5, $f->destino_nombre,                                1, 0, 'L', true);
                $pdf->Cell(55, 5, $f->transferencia_motivo ?? '-',                   1, 0, 'L', true);
                $pdf->Cell(15, 5, $f->total_items,                                   1, 0, 'C', true);
                $pdf->Cell(12, 5, number_format($f->total_unidades, 2),              1, 0, 'R', true);
                $pdf->Cell(10, 5, trim($f->usuario_nombre),                          1, 1, 'L', true);
                $fill = !$fill;
            }

            // Totales
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(230, 5, 'TOTAL (' . $d['totales']['cantidad'] . ' transferencias)', 1, 0, 'R', true);
            $pdf->Cell(15, 5, $d['totales']['total_items'],                                1, 0, 'C', true);
            $pdf->Cell(12, 5, number_format($d['totales']['total_unidades'], 2),           1, 0, 'R', true);
            $pdf->Cell(10, 5, '',                                                           1, 1, 'R', true);

            ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="reporte_transferencias_' . date('Ymd_His') . '.pdf"');
            echo $pdf->Output('S', '');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteTransferenciasExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosReporteTransferencias($request);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('Transferencias');

            $estiloH = ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font' => ['size' => 8, 'name' => 'Arial'],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];

            $row = 1;
            $headers = ['A' => 'Fecha', 'B' => 'N° Transferencia', 'C' => 'Origen', 'D' => 'Destino',
                        'E' => 'Motivo', 'F' => 'Items', 'G' => 'Unidades', 'H' => 'Usuario'];
            foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($estiloH);
            $row++;

            foreach ($d['filas'] as $f) {
                $sheet->setCellValue("A{$row}", date('d/m/Y', strtotime($f->transferencia_fecha)));
                $sheet->setCellValue("B{$row}", $f->transferencia_numero ?? '-');
                $sheet->setCellValue("C{$row}", $f->origen_nombre);
                $sheet->setCellValue("D{$row}", $f->destino_nombre);
                $sheet->setCellValue("E{$row}", $f->transferencia_motivo ?? '-');
                $sheet->setCellValue("F{$row}", (int)$f->total_items);
                $sheet->setCellValue("G{$row}", (float)$f->total_unidades);
                $sheet->setCellValue("H{$row}", trim($f->usuario_nombre));
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($estiloD);
                $row++;
            }

            foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'reporte_transferencias_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CORTE MENSUAL
    // ─────────────────────────────────────────────────────────────
    public function reporteCorteMensual()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('reporte_corte_mensual');
            return view('reporte.reporte_corte_mensual', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al mostrar contenido.');window.location.href='" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosCorteMensual(Request $request): array
    {
        $idEmpresa  = $request->id_empresa  ?? null;
        $idSucursal = $request->id_sucursal ?? null;
        $anio       = $request->anio        ?? now()->year;

        $nombresMeses = [
            1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
            7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
        ];

        $qCompras = DB::table('orden_compra as oc')
            ->join('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
            ->selectRaw('MONTH(oc.orden_compra_fecha) as mes,
                COUNT(oc.id_orden_compra) as num_ordenes,
                SUM(oc.orden_compra_total) as total_mercaderia,
                SUM(COALESCE(oc.orden_compra_flete,0)) as total_flete,
                SUM(COALESCE(oc.orden_compra_gastos_operativos,0)) as total_gastos,
                SUM(oc.orden_compra_total + COALESCE(oc.orden_compra_flete,0) + COALESCE(oc.orden_compra_gastos_operativos,0)) as gran_total_compras')
            ->whereYear('oc.orden_compra_fecha', $anio)
            ->where('oc.orden_compra_activo', 1);
        if ($idSucursal) $qCompras->where('oc.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $qCompras->where('t.id_empresa', $idEmpresa);
        $comprasPorMes = $qCompras->groupByRaw('MONTH(oc.orden_compra_fecha)')->get()->keyBy('mes');

        $qVentas = DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->selectRaw('MONTH(v.venta_fecha) as mes,
                COUNT(v.id_venta) as num_ventas,
                SUM(v.venta_total) as total_ventas')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereYear('v.venta_fecha', $anio);
        if ($idSucursal) $qVentas->where('v.id_sucursal', $idSucursal);
        elseif ($idEmpresa) $qVentas->where('v.id_empresa', $idEmpresa);
        $ventasPorMes = $qVentas->groupByRaw('MONTH(v.venta_fecha)')->get()->keyBy('mes');

        $meses = collect();
        foreach (range(1, 12) as $m) {
            $c = $comprasPorMes->get($m);
            $v = $ventasPorMes->get($m);
            $meses->push((object)[
                'mes_nombre'        => $nombresMeses[$m],
                'num_ordenes'       => $c->num_ordenes ?? 0,
                'total_mercaderia'  => (float)($c->total_mercaderia ?? 0),
                'total_flete'       => (float)($c->total_flete ?? 0),
                'total_gastos'      => (float)($c->total_gastos ?? 0),
                'gran_total_compras'=> (float)($c->gran_total_compras ?? 0),
                'num_ventas'        => $v->num_ventas ?? 0,
                'total_ventas'      => (float)($v->total_ventas ?? 0),
                'diferencia'        => (float)($v->total_ventas ?? 0) - (float)($c->gran_total_compras ?? 0),
            ]);
        }

        $totales = (object)[
            'num_ordenes'       => $meses->sum('num_ordenes'),
            'total_mercaderia'  => $meses->sum('total_mercaderia'),
            'total_flete'       => $meses->sum('total_flete'),
            'total_gastos'      => $meses->sum('total_gastos'),
            'gran_total_compras'=> $meses->sum('gran_total_compras'),
            'num_ventas'        => $meses->sum('num_ventas'),
            'total_ventas'      => $meses->sum('total_ventas'),
            'diferencia'        => $meses->sum('diferencia'),
        ];

        return compact('meses', 'totales', 'anio');
    }

    public function reporteCorteMensualPdf(Request $request)
    {
        try {
            $d   = $this->obtenerDatosCorteMensual($request);
            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(267, 8, 'CORTE MENSUAL — COMPRAS VS VENTAS ' . $d['anio'], 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(267, 6, 'Generado: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');

            // Cabecera  20+18+30+22+22+30+18+30+27 = 217 (ajustado a 267 con padding)
            // 30+18+35+22+22+35+20+35+50 = 267
            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(30, 6, 'Mes',            1, 0, 'C', true);
            $pdf->Cell(18, 6, 'N° OC',          1, 0, 'C', true);
            $pdf->Cell(35, 6, 'Mercadería',     1, 0, 'C', true);
            $pdf->Cell(22, 6, 'Flete',          1, 0, 'C', true);
            $pdf->Cell(22, 6, 'Otros',          1, 0, 'C', true);
            $pdf->Cell(35, 6, 'Total Compras',  1, 0, 'C', true);
            $pdf->Cell(20, 6, 'N° Ventas',      1, 0, 'C', true);
            $pdf->Cell(35, 6, 'Total Ventas',   1, 0, 'C', true);
            $pdf->Cell(50, 6, 'Diferencia',     1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7);
            $fill = false;
            foreach ($d['meses'] as $m) {
                $sinData = $m->num_ordenes == 0 && $m->num_ventas == 0;
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(30, 5, $m->mes_nombre,      1, 0, 'L', true);
                $pdf->Cell(18, 5, $m->num_ordenes ?: '-', 1, 0, 'C', true);
                $pdf->Cell(35, 5, $m->total_mercaderia > 0 ? 'S/ '.number_format($m->total_mercaderia,2) : '-', 1, 0, 'R', true);
                $pdf->Cell(22, 5, $m->total_flete > 0 ? 'S/ '.number_format($m->total_flete,2) : '-', 1, 0, 'R', true);
                $pdf->Cell(22, 5, $m->total_gastos > 0 ? 'S/ '.number_format($m->total_gastos,2) : '-', 1, 0, 'R', true);
                $pdf->Cell(35, 5, $m->gran_total_compras > 0 ? 'S/ '.number_format($m->gran_total_compras,2) : '-', 1, 0, 'R', true);
                $pdf->Cell(20, 5, $m->num_ventas ?: '-', 1, 0, 'C', true);
                $pdf->Cell(35, 5, $m->total_ventas > 0 ? 'S/ '.number_format($m->total_ventas,2) : '-', 1, 0, 'R', true);
                $dif = !$sinData ? 'S/ '.number_format($m->diferencia,2) : '-';
                $pdf->Cell(50, 5, $dif, 1, 1, 'R', true);
                $fill = !$fill;
            }

            // Totales
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(30, 5, 'TOTAL', 1, 0, 'L', true);
            $pdf->Cell(18, 5, $d['totales']->num_ordenes, 1, 0, 'C', true);
            $pdf->Cell(35, 5, 'S/ '.number_format($d['totales']->total_mercaderia,2), 1, 0, 'R', true);
            $pdf->Cell(22, 5, 'S/ '.number_format($d['totales']->total_flete,2), 1, 0, 'R', true);
            $pdf->Cell(22, 5, 'S/ '.number_format($d['totales']->total_gastos,2), 1, 0, 'R', true);
            $pdf->Cell(35, 5, 'S/ '.number_format($d['totales']->gran_total_compras,2), 1, 0, 'R', true);
            $pdf->Cell(20, 5, $d['totales']->num_ventas, 1, 0, 'C', true);
            $pdf->Cell(35, 5, 'S/ '.number_format($d['totales']->total_ventas,2), 1, 0, 'R', true);
            $pdf->Cell(50, 5, 'S/ '.number_format($d['totales']->diferencia,2), 1, 1, 'R', true);

            ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="corte_mensual_'.$d['anio'].'_'.date('Ymd_His').'.pdf"');
            echo $pdf->Output('S', '');
            exit;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function reporteCorteMensualExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosCorteMensual($request);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('Corte Mensual ' . $d['anio']);

            $estiloH = ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloD = ['font' => ['size' => 8, 'name' => 'Arial'],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];
            $estiloT = ['font' => ['bold' => true, 'size' => 8, 'name' => 'Arial'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];

            $row = 1;
            $headers = ['A'=>'Mes','B'=>'N° Órdenes','C'=>'Mercadería','D'=>'Flete',
                        'E'=>'G. Operativos','F'=>'Total Compras','G'=>'N° Ventas','H'=>'Total Ventas','I'=>'Diferencia'];
            foreach ($headers as $col => $h) $sheet->setCellValue("{$col}{$row}", $h);
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloH);
            $row++;

            foreach ($d['meses'] as $m) {
                $sheet->setCellValue("A{$row}", $m->mes_nombre);
                $sheet->setCellValue("B{$row}", (int)$m->num_ordenes);
                $sheet->setCellValue("C{$row}", (float)$m->total_mercaderia);
                $sheet->setCellValue("D{$row}", (float)$m->total_flete);
                $sheet->setCellValue("E{$row}", (float)$m->total_gastos);
                $sheet->setCellValue("F{$row}", (float)$m->gran_total_compras);
                $sheet->setCellValue("G{$row}", (int)$m->num_ventas);
                $sheet->setCellValue("H{$row}", (float)$m->total_ventas);
                $sheet->setCellValue("I{$row}", (float)$m->diferencia);
                foreach (['C','D','E','F','H','I'] as $col)
                    $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloD);
                $row++;
            }

            // Fila totales
            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->setCellValue("B{$row}", (int)$d['totales']->num_ordenes);
            $sheet->setCellValue("C{$row}", (float)$d['totales']->total_mercaderia);
            $sheet->setCellValue("D{$row}", (float)$d['totales']->total_flete);
            $sheet->setCellValue("E{$row}", (float)$d['totales']->total_gastos);
            $sheet->setCellValue("F{$row}", (float)$d['totales']->gran_total_compras);
            $sheet->setCellValue("G{$row}", (int)$d['totales']->num_ventas);
            $sheet->setCellValue("H{$row}", (float)$d['totales']->total_ventas);
            $sheet->setCellValue("I{$row}", (float)$d['totales']->diferencia);
            foreach (['C','D','E','F','H','I'] as $col)
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($estiloT);

            foreach (range('A','I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $nombreArchivo = 'corte_mensual_'.$d['anio'].'_'.date('Ymd_His').'.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ── Excel Ventas por Mes (Dashboard) ─────────────────────────
    public function excelVentasMes(Request $request)
    {
        try {
            $mes        = (int) $request->mes;
            $anio       = (int) ($request->anio ?? now()->year);
            $idEmpresa  = $request->id_empresa  ? (int) $request->id_empresa  : null;
            $idSucursal = $request->id_sucursal ? (int) $request->id_sucursal : 0;

            $nombresMes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $labelMes   = ($nombresMes[$mes] ?? 'Mes') . ' ' . $anio;

            $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
            $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
            $idsQr       = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'qr')       !== false)->pluck('id_tipo_pago')->toArray();

            // ── Ventas con detalle de pago ────────────────────────
            $qVentas = DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->where('vdp.venta_detalle_pago_estado', 1)
                ->whereYear('v.venta_fecha', $anio)
                ->whereMonth('v.venta_fecha', $mes)
                ->whereIn('vdp.id_tipo_pago', array_merge($idsEfectivo, $idsQr))
                ->select('v.venta_fecha','v.venta_serie','v.venta_correlativo',
                         'c.cliente_nombre','c.cliente_razonsocial',
                         'tp.tipo_pago_nombre','vdp.id_tipo_pago','vdp.venta_detalle_pago_monto')
                ->orderBy('v.venta_fecha')
                ->orderBy('v.venta_serie')
                ->orderBy('v.venta_correlativo');
            if ($idEmpresa)      $qVentas->where('v.id_empresa', $idEmpresa);
            if ($idSucursal > 0) $qVentas->where('v.id_sucursal', $idSucursal);
            $ventas = $qVentas->get();

            $totalEfectivo = $ventas->whereIn('id_tipo_pago', $idsEfectivo)->sum('venta_detalle_pago_monto');
            $totalQr       = $ventas->whereIn('id_tipo_pago', $idsQr)->sum('venta_detalle_pago_monto');

            // ── Notas de crédito ──────────────────────────────────
            $qNC = DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->whereNull('va.id_venta')
                ->where('v.venta_tipo', '07')
                ->whereYear('v.venta_fecha', $anio)
                ->whereMonth('v.venta_fecha', $mes)
                ->select('v.venta_fecha','v.venta_serie','v.venta_correlativo',
                         'v.serie_modificar','v.correlativo_modificar',
                         'c.cliente_nombre','c.cliente_razonsocial','v.venta_total')
                ->orderBy('v.venta_fecha');
            if ($idEmpresa)      $qNC->where('v.id_empresa', $idEmpresa);
            if ($idSucursal > 0) $qNC->where('v.id_sucursal', $idSucursal);
            $notasCredito = $qNC->get();
            $totalNC = $notasCredito->sum('venta_total');

            // ── Gastos ────────────────────────────────────────────
            $qGastos = DB::table('gastos as g')
                ->join('tipo_gasto as tg', 'tg.id_tipo_gasto', '=', 'g.id_tipo_gasto')
                ->where('g.gasto_estado', 1)
                ->whereYear('g.gasto_fecha', $anio)
                ->whereMonth('g.gasto_fecha', $mes)
                ->select('g.gasto_fecha','tg.tipo_gasto_nombre','g.gasto_detalle','g.gasto_monto')
                ->orderBy('g.gasto_fecha');
            if ($idEmpresa)   $qGastos->where('g.id_empresa', $idEmpresa);
            if ($idSucursal < 0) $qGastos->where('g.id_tienda', -$idSucursal);
            $gastos     = $qGastos->get();
            $totalGs    = $gastos->sum('gasto_monto');

            $neto = $totalEfectivo + $totalQr - $totalNC - $totalGs;

            // ── Spreadsheet ───────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Ventas ' . ($nombresMes[$mes] ?? $mes));

            $azul    = 'FF0B1892';
            $blanco  = 'FFFFFFFF';
            $verde   = 'FF1A6B35';
            $azulQR  = 'FF0077B6';
            $rojo    = 'FFB30000';
            $grisF   = 'FFF2F2F2';
            $grisB   = 'FFD0D0D0';

            $stTitulo = [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $azul], 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $stSeccion = [
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $blanco], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $azul]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ];
            $stEnc = [
                'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => $blanco], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF212C3E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $blanco]]],
            ];
            $stFila = [
                'font'      => ['size' => 8, 'name' => 'Arial'],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $grisB]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ];
            $stTotal = [
                'font'      => ['bold' => true, 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $grisF]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $grisB]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            // Título
            $cols = 'G';
            $sheet->mergeCells("A1:{$cols}1");
            $sheet->setCellValue('A1', 'Detalle de Ventas — ' . $labelMes);
            $sheet->getStyle('A1')->applyFromArray($stTitulo);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // ── RESUMEN ───────────────────────────────────────────
            $f = 3;
            $sheet->mergeCells("A{$f}:{$cols}{$f}");
            $sheet->setCellValue("A{$f}", '  RESUMEN DEL MES');
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stSeccion);
            $sheet->getRowDimension($f)->setRowHeight(16);
            $f++;

            $resumen = [
                ['Efectivo',      'S/ ' . number_format($totalEfectivo, 2), $verde],
                ['QR / Digital',  'S/ ' . number_format($totalQr,       2), $azulQR],
                ['Notas Crédito', '− S/ ' . number_format($totalNC,     2), $rojo],
                ['Gastos',        '− S/ ' . number_format($totalGs,     2), $rojo],
                ['NETO',          'S/ ' . number_format($neto,          2), $neto >= 0 ? $azul : $rojo],
            ];
            foreach ($resumen as $i => [$label, $valor, $color]) {
                $sheet->setCellValue("A{$f}", $label);
                $sheet->setCellValue("C{$f}", $valor);
                $sheet->mergeCells("A{$f}:B{$f}");
                $sheet->mergeCells("C{$f}:{$cols}{$f}");
                $isBold = $i === 4;
                $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stFila, [
                    'font' => ['bold' => $isBold, 'size' => 8, 'color' => ['argb' => $color], 'name' => 'Arial'],
                ]));
                $f++;
            }

            // ── VENTAS ───────────────────────────────────────────
            $f++;
            $sheet->mergeCells("A{$f}:{$cols}{$f}");
            $sheet->setCellValue("A{$f}", '  VENTAS DEL MES (Efectivo y QR)');
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stSeccion);
            $sheet->getRowDimension($f)->setRowHeight(16);
            $f++;

            foreach (['A'=>'Fecha','B'=>'Comprobante','C'=>'Cliente','D'=>'Tipo Pago','E'=>'','F'=>'','G'=>'Monto'] as $c => $txt) {
                $sheet->setCellValue("{$c}{$f}", $txt);
            }
            $sheet->mergeCells("E{$f}:F{$f}");
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stEnc);
            $sheet->getRowDimension($f)->setRowHeight(18);
            $f++;

            foreach ($ventas as $i => $v) {
                $cliente = trim($v->cliente_razonsocial ?: $v->cliente_nombre);
                $comp    = $v->venta_serie . '-' . str_pad($v->venta_correlativo, 8, '0', STR_PAD_LEFT);
                $sheet->setCellValue("A{$f}", date('d/m/Y', strtotime($v->venta_fecha)));
                $sheet->setCellValue("B{$f}", $comp);
                $sheet->setCellValue("C{$f}", $cliente);
                $sheet->setCellValue("D{$f}", $v->tipo_pago_nombre);
                $sheet->mergeCells("E{$f}:F{$f}");
                $sheet->setCellValue("G{$f}", 'S/ ' . number_format($v->venta_detalle_pago_monto, 2));
                $style = array_merge($stFila, $i % 2 == 0 ? ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $grisF]]] : []);
                $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($style);
                $sheet->getStyle("G{$f}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $f++;
            }
            // Subtotales ventas
            $sheet->setCellValue("A{$f}", 'Total Efectivo');
            $sheet->mergeCells("A{$f}:F{$f}");
            $sheet->setCellValue("G{$f}", 'S/ ' . number_format($totalEfectivo, 2));
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stTotal, ['font' => ['bold' => true, 'size' => 8, 'color' => ['argb' => $verde], 'name' => 'Arial']]));
            $f++;
            $sheet->setCellValue("A{$f}", 'Total QR / Digital');
            $sheet->mergeCells("A{$f}:F{$f}");
            $sheet->setCellValue("G{$f}", 'S/ ' . number_format($totalQr, 2));
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stTotal, ['font' => ['bold' => true, 'size' => 8, 'color' => ['argb' => $azulQR], 'name' => 'Arial']]));
            $f++;

            // ── NOTAS DE CRÉDITO ──────────────────────────────────
            $f++;
            $sheet->mergeCells("A{$f}:{$cols}{$f}");
            $sheet->setCellValue("A{$f}", '  NOTAS DE CRÉDITO');
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stSeccion);
            $sheet->getRowDimension($f)->setRowHeight(16);
            $f++;

            foreach (['A'=>'Fecha','B'=>'N° NC','C'=>'Referencia','D'=>'Cliente','E'=>'','F'=>'','G'=>'Monto'] as $c => $txt) {
                $sheet->setCellValue("{$c}{$f}", $txt);
            }
            $sheet->mergeCells("E{$f}:F{$f}");
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stEnc);
            $sheet->getRowDimension($f)->setRowHeight(18);
            $f++;

            if ($notasCredito->isEmpty()) {
                $sheet->setCellValue("A{$f}", 'Sin notas de crédito en este período');
                $sheet->mergeCells("A{$f}:{$cols}{$f}");
                $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stFila, ['font' => ['italic' => true, 'color' => ['argb' => 'FF999999'], 'size' => 8, 'name' => 'Arial']]));
                $f++;
            } else {
                foreach ($notasCredito as $i => $nc) {
                    $cliente  = trim($nc->cliente_razonsocial ?: $nc->cliente_nombre);
                    $compNC   = $nc->venta_serie . '-' . str_pad($nc->venta_correlativo, 8, '0', STR_PAD_LEFT);
                    $compRef  = ($nc->serie_modificar ?? '-') . '-' . str_pad($nc->correlativo_modificar ?? '0', 8, '0', STR_PAD_LEFT);
                    $sheet->setCellValue("A{$f}", date('d/m/Y', strtotime($nc->venta_fecha)));
                    $sheet->setCellValue("B{$f}", $compNC);
                    $sheet->setCellValue("C{$f}", $compRef);
                    $sheet->setCellValue("D{$f}", $cliente);
                    $sheet->mergeCells("E{$f}:F{$f}");
                    $sheet->setCellValue("G{$f}", '− S/ ' . number_format($nc->venta_total, 2));
                    $style = array_merge($stFila, $i % 2 == 0 ? ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $grisF]]] : []);
                    $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($style);
                    $sheet->getStyle("G{$f}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $f++;
                }
            }
            $sheet->setCellValue("A{$f}", 'Total Notas de Crédito');
            $sheet->mergeCells("A{$f}:F{$f}");
            $sheet->setCellValue("G{$f}", '− S/ ' . number_format($totalNC, 2));
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stTotal, ['font' => ['bold' => true, 'size' => 8, 'color' => ['argb' => $rojo], 'name' => 'Arial']]));
            $f++;

            // ── GASTOS ────────────────────────────────────────────
            $f++;
            $sheet->mergeCells("A{$f}:{$cols}{$f}");
            $sheet->setCellValue("A{$f}", '  GASTOS');
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stSeccion);
            $sheet->getRowDimension($f)->setRowHeight(16);
            $f++;

            foreach (['A'=>'Fecha','B'=>'Tipo','C'=>'Detalle','D'=>'','E'=>'','F'=>'','G'=>'Monto'] as $c => $txt) {
                $sheet->setCellValue("{$c}{$f}", $txt);
            }
            $sheet->mergeCells("D{$f}:F{$f}");
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($stEnc);
            $sheet->getRowDimension($f)->setRowHeight(18);
            $f++;

            if ($gastos->isEmpty()) {
                $sheet->setCellValue("A{$f}", 'Sin gastos en este período');
                $sheet->mergeCells("A{$f}:{$cols}{$f}");
                $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stFila, ['font' => ['italic' => true, 'color' => ['argb' => 'FF999999'], 'size' => 8, 'name' => 'Arial']]));
                $f++;
            } else {
                foreach ($gastos as $i => $g) {
                    $sheet->setCellValue("A{$f}", date('d/m/Y', strtotime($g->gasto_fecha)));
                    $sheet->setCellValue("B{$f}", $g->tipo_gasto_nombre);
                    $sheet->setCellValue("C{$f}", $g->gasto_detalle);
                    $sheet->mergeCells("D{$f}:F{$f}");
                    $sheet->setCellValue("G{$f}", '− S/ ' . number_format($g->gasto_monto, 2));
                    $style = array_merge($stFila, $i % 2 == 0 ? ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $grisF]]] : []);
                    $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray($style);
                    $sheet->getStyle("G{$f}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $f++;
                }
            }
            $sheet->setCellValue("A{$f}", 'Total Gastos');
            $sheet->mergeCells("A{$f}:F{$f}");
            $sheet->setCellValue("G{$f}", '− S/ ' . number_format($totalGs, 2));
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray(array_merge($stTotal, ['font' => ['bold' => true, 'size' => 8, 'color' => ['argb' => $rojo], 'name' => 'Arial']]));
            $f++;

            // ── NETO FINAL ────────────────────────────────────────
            $f++;
            $sheet->mergeCells("A{$f}:F{$f}");
            $sheet->setCellValue("A{$f}", 'NETO DEL MES');
            $sheet->setCellValue("G{$f}", 'S/ ' . number_format($neto, 2));
            $colorNeto = $neto >= 0 ? $azul : $rojo;
            $sheet->getStyle("A{$f}:{$cols}{$f}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => $blanco], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $colorNeto]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $blanco]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$f}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getRowDimension($f)->setRowHeight(18);

            // Anchos columnas
            foreach (['A'=>12,'B'=>18,'C'=>30,'D'=>16,'E'=>10,'F'=>10,'G'=>14] as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            $nombreArchivo = 'Ventas_' . ($nombresMes[$mes] ?? $mes) . '_' . $anio . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new Xlsx($spreadsheet))->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return response('Error al generar el archivo.', 500);
        }
    }
}
