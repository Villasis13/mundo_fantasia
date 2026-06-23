<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\PDFBufeo;
use App\Models\Submenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CajaController extends Controller
{
    private $logs;
    private $submenu;

    public function __construct()
    {
        $this->logs    = new Logs();
        $this->submenu = new Submenu();
    }

    public function movimientosCaja()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('movimientos_caja');
            return view('caja.movimientos_caja', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert('Error al mostrar el contenido. Redireccionando al inicio.');
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function arqueoCaja()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista('arqueo_caja');
            return view('caja.arqueo_caja', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert('Error al mostrar el contenido. Redireccionando al inicio.');
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function arqueoCajaPdf(Request $request)
    {
        try {
            $idCajaNumero = (int) $request->id_caja_numero;
            $fecha        = $request->fecha ?? now()->toDateString();

            // ── Datos del turno ───────────────────────────────
            $nombreCaja = DB::table('caja_numero')
                ->where('id_caja_numero', $idCajaNumero)
                ->value('caja_numero_nombre');

            $turnos = DB::table('caja as c')
                ->select('c.*', 'u_ap.nombre_users as nombre_apertura', 'u_ci.nombre_users as nombre_cierre')
                ->join('users as u_ap', 'u_ap.id_users', '=', 'c.id_users_apertura')
                ->leftJoin('users as u_ci', 'u_ci.id_users', '=', 'c.id_users_cierre')
                ->where('c.id_caja_numero', $idCajaNumero)
                ->whereDate('c.caja_fecha', $fecha)
                ->orderBy('c.caja_fecha_apertura')
                ->get();

            $idsCaja = $turnos->pluck('id_caja')->toArray();

            $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
                ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.id_caja', $idsCaja)
                ->where('vdp.venta_detalle_pago_estado', 1)
                ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
                ->get();

            $pagosCuotas = DB::table('pagos_cuotas as pc')
                ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
                ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
                ->whereNull('pc.deleted_at')
                ->whereIn('v.id_caja', $idsCaja)
                ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
                ->get();

            $movimientos = DB::table('caja_movimientos as cm')
                ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
                ->join('users as u', 'u.id_users', '=', 'cm.id_users')
                ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
                ->whereNull('cm.deleted_at')
                ->whereIn('cm.id_caja', $idsCaja)
                ->orderBy('cm.created_at')
                ->get();

            $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
            $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
            $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
            $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

            $baseVdp = fn() => DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereIn('v.id_caja', $idsCaja)
                ->where('vdp.venta_detalle_pago_estado', 1);

            $ventasEfectivo = !empty($idsEfectivo)
                ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsEfectivo)->sum('vdp.venta_detalle_pago_monto')
                : 0.0;
            $ventasYape = !empty($idsYape)
                ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsYape)->sum('vdp.venta_detalle_pago_monto')
                : 0.0;
            $ventasPlin = !empty($idsPlin)
                ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsPlin)->sum('vdp.venta_detalle_pago_monto')
                : 0.0;

            $notasCredito = (float) DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->where('v.venta_tipo', '07')
                ->whereIn('v.id_caja', $idsCaja)
                ->sum('v.venta_total');

            $gastos = (float) DB::table('gastos')
                ->where('gasto_estado', 1)
                ->where('gasto_tipo', 1)
                ->where('id_caja_numero', $idCajaNumero)
                ->whereDate('gasto_fecha', $fecha)
                ->sum('gasto_monto');

            $ingresosGastos = (float) DB::table('gastos')
                ->where('gasto_estado', 1)
                ->where('gasto_tipo', 2)
                ->where('id_caja_numero', $idCajaNumero)
                ->whereDate('gasto_fecha', $fecha)
                ->sum('gasto_monto');

            $totalVentas      = (float) $ventasPorMedio->sum('total');
            $totalPagosCuotas = (float) $pagosCuotas->sum('total');
            $totalIngresos    = (float) $movimientos->where('tipo', 1)->sum('monto');
            $totalEgresos     = (float) $movimientos->where('tipo', 2)->sum('monto');
            $montoApertura    = (float) $turnos->sum('caja_apertura');
            $montoCierre      = (float) $turnos->whereNotNull('caja_fecha_cierre')->sum('caja_cierre');
            $cajaAbierta      = $turnos->whereNull('caja_fecha_cierre')->isNotEmpty();
            $totalSistema     = $montoApertura + $ventasEfectivo + $totalPagosCuotas + $totalIngresos + $ingresosGastos - $totalEgresos - $notasCredito - $gastos;
            $diferencia       = $cajaAbierta ? null : round($montoCierre - $totalSistema, 2);

            // ── Generar PDF ───────────────────────────────────
            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(190, 8, utf8_decode('Arqueo de Caja'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(190, 6, utf8_decode('Caja: ' . ($nombreCaja ?? 'N/A') . '   Fecha: ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y')), 0, 1, 'C');
            $pdf->Cell(190, 0, '', 'T', 1);
            $pdf->Ln(3);

            // Cuadre financiero
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(190, 6, utf8_decode('CUADRE DE CAJA'), 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 9);

            $filas = [
                ['Monto de apertura',       'S/ '   . number_format($montoApertura,    2)],
                ['+ Ventas efectivo',        'S/ '   . number_format($ventasEfectivo,   2)],
                ['+ Ventas Yape',            'S/ '   . number_format($ventasYape,       2)],
                ['+ Ventas Plin',            'S/ '   . number_format($ventasPlin,       2)],
                ['+ Cobros de cuotas',       'S/ '   . number_format($totalPagosCuotas, 2)],
                ['+ Ingresos (Mov.)',         'S/ '   . number_format($totalIngresos,    2)],
                ['+ Ingresos',               'S/ '   . number_format($ingresosGastos,   2)],
                ['- Notas de credito',       '- S/ ' . number_format($notasCredito,     2)],
                ['- Gastos del dia',         '- S/ ' . number_format($gastos,           2)],
                ['- Egresos manuales',       '- S/ ' . number_format($totalEgresos,     2)],
                ['= Total sistema (efect.)', 'S/ '   . number_format($totalSistema,     2)],
            ];
            foreach ($filas as $f) {
                $pdf->Cell(140, 5, utf8_decode($f[0]), 1, 0, 'L');
                $pdf->Cell(50,  5, utf8_decode($f[1]), 1, 1, 'R');
            }

            if (!$cajaAbierta) {
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(140, 5, utf8_decode('Monto declarado al cierre'), 1, 0, 'L');
                $pdf->Cell(50,  5, utf8_decode('S/ ' . number_format($montoCierre, 2)), 1, 1, 'R');
                $label = $diferencia == 0 ? 'Cuadre exacto' : ($diferencia > 0 ? 'Sobrante' : 'Faltante');
                $pdf->Cell(140, 5, utf8_decode('Diferencia (' . $label . ')'), 1, 0, 'L');
                $pdf->Cell(50,  5, utf8_decode(($diferencia >= 0 ? '+' : '') . 'S/ ' . number_format($diferencia, 2)), 1, 1, 'R');
            }

            // Ventas por medio de pago
            if ($ventasPorMedio->isNotEmpty()) {
                $pdf->Ln(4);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(190, 6, utf8_decode('VENTAS POR MEDIO DE PAGO'), 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 9);
                foreach ($ventasPorMedio as $vm) {
                    $pdf->Cell(140, 5, utf8_decode($vm->tipo_pago_nombre), 1, 0, 'L');
                    $pdf->Cell(50,  5, utf8_decode('S/ ' . number_format($vm->total, 2)), 1, 1, 'R');
                }
            }

            // Movimientos manuales
            if ($movimientos->isNotEmpty()) {
                $pdf->Ln(4);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(190, 6, utf8_decode('MOVIMIENTOS MANUALES'), 1, 1, 'C', true);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(15, 5, utf8_decode('Tipo'),    1, 0, 'C', true);
                $pdf->Cell(90, 5, utf8_decode('Concepto'),1, 0, 'L', true);
                $pdf->Cell(45, 5, utf8_decode('Medio'),   1, 0, 'L', true);
                $pdf->Cell(40, 5, utf8_decode('Monto'),   1, 1, 'R', true);
                $pdf->SetFont('Arial', '', 8);
                foreach ($movimientos as $m) {
                    $tipo = $m->tipo == 1 ? 'Ingreso' : 'Egreso';
                    $pdf->Cell(15, 5, utf8_decode($tipo), 1, 0, 'C');
                    $pdf->Cell(90, 5, utf8_decode(\Illuminate\Support\Str::limit($m->concepto, 45)), 1, 0, 'L');
                    $pdf->Cell(45, 5, utf8_decode($m->tipo_pago_nombre ?? '—'), 1, 0, 'L');
                    $pdf->Cell(40, 5, utf8_decode('S/ ' . number_format($m->monto, 2)), 1, 1, 'R');
                }
            }

            $pdf->Output('I', 'arqueo_caja_' . $fecha . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            abort(500, 'Error al generar el PDF del arqueo.');
        }
    }

    // ── Datos comunes para Excel y Ticket ─────────────────────────
    private function calcularDatosArqueo(int $idCajaNumero, string $fecha): array
    {
        $nombreCaja = DB::table('caja_numero')->where('id_caja_numero', $idCajaNumero)->value('caja_numero_nombre');

        $turnos = DB::table('caja as c')
            ->select('c.*', 'u_ap.nombre_users as nombre_apertura', 'u_ci.nombre_users as nombre_cierre')
            ->join('users as u_ap', 'u_ap.id_users', '=', 'c.id_users_apertura')
            ->leftJoin('users as u_ci', 'u_ci.id_users', '=', 'c.id_users_cierre')
            ->where('c.id_caja_numero', $idCajaNumero)
            ->whereDate('c.caja_fecha', $fecha)
            ->orderBy('c.caja_fecha_apertura')
            ->get();

        $idsCaja = $turnos->pluck('id_caja')->toArray();

        $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $pagosCuotas = DB::table('pagos_cuotas as pc')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
            ->whereNull('pc.deleted_at')
            ->whereIn('v.id_caja', $idsCaja)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $movimientos = DB::table('caja_movimientos as cm')
            ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
            ->join('users as u', 'u.id_users', '=', 'cm.id_users')
            ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
            ->whereNull('cm.deleted_at')
            ->whereIn('cm.id_caja', $idsCaja)
            ->orderBy('cm.created_at')
            ->get();

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        $baseVdp = fn() => DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1);

        $ventasEfectivo = !empty($idsEfectivo) ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsEfectivo)->sum('vdp.venta_detalle_pago_monto') : 0.0;
        $ventasYape     = !empty($idsYape)     ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsYape)->sum('vdp.venta_detalle_pago_monto')     : 0.0;
        $ventasPlin     = !empty($idsPlin)     ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsPlin)->sum('vdp.venta_detalle_pago_monto')     : 0.0;
        $notasCredito     = (float) DB::table('ventas as v')->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')->whereNull('va.id_venta')->where('v.venta_tipo','07')->whereIn('v.id_caja',$idsCaja)->sum('v.venta_total');
        $gastos           = (float) DB::table('gastos')->where('gasto_estado',1)->where('gasto_tipo',1)->where('id_caja_numero',$idCajaNumero)->whereDate('gasto_fecha',$fecha)->sum('gasto_monto');
        $ingresosGastos   = (float) DB::table('gastos')->where('gasto_estado',1)->where('gasto_tipo',2)->where('id_caja_numero',$idCajaNumero)->whereDate('gasto_fecha',$fecha)->sum('gasto_monto');
        $totalVentas      = (float) $ventasPorMedio->sum('total');
        $totalPagosCuotas = (float) $pagosCuotas->sum('total');
        $totalIngresos    = (float) $movimientos->where('tipo', 1)->sum('monto');
        $totalEgresos     = (float) $movimientos->where('tipo', 2)->sum('monto');
        $montoApertura    = (float) $turnos->sum('caja_apertura');
        $montoCierre      = (float) $turnos->whereNotNull('caja_fecha_cierre')->sum('caja_cierre');
        $cajaAbierta      = $turnos->whereNull('caja_fecha_cierre')->isNotEmpty();
        $totalSistema     = $montoApertura + $ventasEfectivo + $totalPagosCuotas + $totalIngresos + $ingresosGastos - $totalEgresos - $notasCredito - $gastos;
        $diferencia       = $cajaAbierta ? null : round($montoCierre - $totalSistema, 2);

        return compact(
            'nombreCaja','turnos','ventasPorMedio','pagosCuotas','movimientos',
            'ventasEfectivo','ventasYape','ventasPlin','notasCredito','gastos','ingresosGastos',
            'totalVentas','totalPagosCuotas','totalIngresos','totalEgresos',
            'montoApertura','montoCierre','cajaAbierta','totalSistema','diferencia'
        );
    }

    public function arqueoCajaExcel(Request $request)
    {
        try {
            $idCajaNumero = (int) $request->id_caja_numero;
            $fecha        = $request->fecha ?? now()->toDateString();
            $d            = $this->calcularDatosArqueo($idCajaNumero, $fecha);

            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet()->setTitle('Arqueo');

            $estiloTitulo = ['font' => ['bold' => true, 'size' => 11, 'name' => 'Arial'],
                             'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF46596E']],
                             'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
                             'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloSeccion = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['argb' => 'FFFFFFFF']],
                              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                              'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
            $estiloNormal  = ['font' => ['size' => 9, 'name' => 'Arial'],
                              'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]]];
            $estiloBold    = ['font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
                              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCE6F1']],
                              'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFB0C4DE']]]];

            $row = 1;
            $sheet->setCellValue("A{$row}", 'ARQUEO DE CAJA');
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloTitulo);
            $sheet->mergeCells("A{$row}:C{$row}");
            $row++;
            $sheet->setCellValue("A{$row}", 'Caja: ' . ($d['nombreCaja'] ?? 'N/A'));
            $sheet->setCellValue("C{$row}", 'Fecha: ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y'));
            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            $row += 2;

            // Cuadre financiero
            $sheet->setCellValue("A{$row}", 'CUADRE DE CAJA');
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloSeccion);
            $sheet->mergeCells("A{$row}:C{$row}");
            $row++;
            $filas = [
                ['Monto de apertura',       $d['montoApertura']],
                ['+ Ventas efectivo',        $d['ventasEfectivo']],
                ['+ Ventas Yape',             $d['ventasYape']],
                ['+ Ventas Plin',             $d['ventasPlin']],
                ['+ Cobros de cuotas',       $d['totalPagosCuotas']],
                ['+ Ingresos (Mov.)',         $d['totalIngresos']],
                ['+ Ingresos',               $d['ingresosGastos']],
                ['- Notas de crédito',       -$d['notasCredito']],
                ['- Gastos del día',         -$d['gastos']],
                ['- Egresos manuales',       -$d['totalEgresos']],
            ];
            foreach ($filas as $f) {
                $sheet->setCellValue("A{$row}", $f[0]);
                $sheet->setCellValue("C{$row}", $f[1]);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloNormal);
                $row++;
            }
            $sheet->setCellValueExplicit("A{$row}", '= Total sistema (efectivo)', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $d['totalSistema']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloBold);
            $row++;
            if (!$d['cajaAbierta']) {
                $sheet->setCellValue("A{$row}", 'Declarado al cierre');
                $sheet->setCellValue("C{$row}", $d['montoCierre']);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloNormal);
                $row++;
                $dif   = $d['diferencia'];
                $label = $dif == 0 ? 'Cuadre exacto' : ($dif > 0 ? 'Sobrante' : 'Faltante');
                $sheet->setCellValue("A{$row}", 'Diferencia (' . $label . ')');
                $sheet->setCellValue("C{$row}", $dif);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloBold);
                $row++;
            }
            $row++;

            // Ventas por medio de pago
            if ($d['ventasPorMedio']->isNotEmpty()) {
                $sheet->setCellValue("A{$row}", 'VENTAS POR MEDIO DE PAGO');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloSeccion);
                $sheet->mergeCells("A{$row}:C{$row}");
                $row++;
                foreach ($d['ventasPorMedio'] as $vm) {
                    $sheet->setCellValue("A{$row}", $vm->tipo_pago_nombre);
                    $sheet->setCellValue("C{$row}", (float) $vm->total);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloNormal);
                    $row++;
                }
                $sheet->setCellValue("A{$row}", 'Total');
                $sheet->setCellValue("C{$row}", (float) $d['totalVentas']);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloBold);
                $row += 2;
            }

            // Cobros de cuotas
            if ($d['pagosCuotas']->isNotEmpty()) {
                $sheet->setCellValue("A{$row}", 'COBROS DE CUOTAS (CRÉDITO)');
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloSeccion);
                $sheet->mergeCells("A{$row}:C{$row}");
                $row++;
                foreach ($d['pagosCuotas'] as $pc) {
                    $sheet->setCellValue("A{$row}", $pc->tipo_pago_nombre);
                    $sheet->setCellValue("C{$row}", (float) $pc->total);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($estiloNormal);
                    $row++;
                }
                $row++;
            }

            // Movimientos manuales
            if ($d['movimientos']->isNotEmpty()) {
                $sheet->setCellValue("A{$row}", 'MOVIMIENTOS MANUALES');
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($estiloSeccion);
                $sheet->mergeCells("A{$row}:E{$row}");
                $row++;
                foreach (['A' => 'Tipo', 'B' => 'Concepto', 'C' => 'Medio de Pago', 'D' => 'Monto', 'E' => 'Registrado por'] as $col => $h) {
                    $sheet->setCellValue("{$col}{$row}", $h);
                }
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($estiloBold);
                $row++;
                foreach ($d['movimientos'] as $m) {
                    $sheet->setCellValue("A{$row}", $m->tipo == 1 ? 'Ingreso' : 'Egreso');
                    $sheet->setCellValue("B{$row}", $m->concepto);
                    $sheet->setCellValue("C{$row}", $m->tipo_pago_nombre ?? '—');
                    $sheet->setCellValue("D{$row}", (float) $m->monto);
                    $sheet->setCellValue("E{$row}", $m->nombre_users);
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($estiloNormal);
                    $row++;
                }
            }

            foreach (['A' => 42, 'B' => 55, 'C' => 20, 'D' => 18, 'E' => 25] as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            $nombreArchivo = 'arqueo_caja_' . $fecha . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            ob_end_clean();
            (new Xlsx($spreadsheet))->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            abort(500, 'Error al generar el Excel del arqueo.');
        }
    }

    public function arqueoCajaTicket(Request $request)
    {
        try {
            $idCajaNumero = (int) $request->id_caja_numero;
            $fecha        = $request->fecha ?? now()->toDateString();
            $d            = $this->calcularDatosArqueo($idCajaNumero, $fecha);

            $ancho = 72; // mm para ticket térmico 80mm
            $pdf   = new PDFBufeo('P', 'mm', [$ancho, 250]);
            $pdf->SetMargins(3, 3, 3);
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 3);

            $w  = $ancho - 6; // ancho útil
            $wL = $w * 0.70;
            $wR = $w * 0.30;

            // Título
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell($w, 5, utf8_decode('ARQUEO DE CAJA'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell($w, 4, utf8_decode('Caja: ' . ($d['nombreCaja'] ?? 'N/A')), 0, 1, 'C');
            $pdf->Cell($w, 4, utf8_decode('Fecha: ' . \Carbon\Carbon::parse($fecha)->format('d/m/Y')), 0, 1, 'C');
            $pdf->Cell($w, 4, utf8_decode('Impreso: ' . now()->format('d/m/Y H:i')), 0, 1, 'C');
            $pdf->Cell($w, 1, '', 'T', 1);
            $pdf->Ln(1);

            // Cuadre
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell($w, 4, 'CUADRE DE CAJA', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 7);

            $filas = [
                ['Apertura',              $d['montoApertura'],      false],
                ['+ Ventas efectivo',     $d['ventasEfectivo'],     false],
                ['+ Ventas Yape',          $d['ventasYape'],         false],
                ['+ Ventas Plin',          $d['ventasPlin'],         false],
                ['+ Cobros cuotas',       $d['totalPagosCuotas'],   false],
                ['+ Ingresos (Mov.)',      $d['totalIngresos'],      false],
                ['+ Ingresos',            $d['ingresosGastos'],     false],
                ['- Notas crédito',       $d['notasCredito'],       false],
                ['- Gastos del día',      $d['gastos'],             false],
                ['- Egresos manuales',    $d['totalEgresos'],       false],
                ['= Total sistema',       $d['totalSistema'],       true],
            ];
            foreach ($filas as [$label, $monto, $bold]) {
                if ($bold) {
                    $pdf->SetFont('Arial', 'B', 7);
                    $pdf->Cell($w, 0.3, '', 'T', 1);
                }
                $pdf->Cell($wL, 4, utf8_decode($label), 0, 0, 'L');
                $pdf->Cell($wR, 4, 'S/ ' . number_format((float)$monto, 2), 0, 1, 'R');
                if ($bold) $pdf->SetFont('Arial', '', 7);
            }

            if (!$d['cajaAbierta']) {
                $dif   = $d['diferencia'];
                $label = $dif == 0 ? 'Cuadre exacto' : ($dif > 0 ? 'Sobrante' : 'Faltante');
                $pdf->Cell($wL, 4, utf8_decode('Declarado al cierre'), 0, 0, 'L');
                $pdf->Cell($wR, 4, 'S/ ' . number_format($d['montoCierre'], 2), 0, 1, 'R');
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell($wL, 4, utf8_decode('Diferencia (' . $label . ')'), 0, 0, 'L');
                $pdf->Cell($wR, 4, ($dif >= 0 ? '+' : '') . 'S/ ' . number_format($dif, 2), 0, 1, 'R');
                $pdf->SetFont('Arial', '', 7);
            } else {
                $pdf->Ln(1);
                $pdf->SetFont('Arial', 'I', 6);
                $pdf->Cell($w, 4, utf8_decode('Caja aún abierta — cierre pendiente'), 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
            }

            // Ventas por medio
            if ($d['ventasPorMedio']->isNotEmpty()) {
                $pdf->Ln(1);
                $pdf->Cell($w, 0.3, '', 'T', 1);
                $pdf->Ln(1);
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell($w, 4, 'VENTAS POR MEDIO DE PAGO', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
                foreach ($d['ventasPorMedio'] as $vm) {
                    $pdf->Cell($wL, 4, utf8_decode($vm->tipo_pago_nombre), 0, 0, 'L');
                    $pdf->Cell($wR, 4, 'S/ ' . number_format($vm->total, 2), 0, 1, 'R');
                }
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell($wL, 4, 'Total', 0, 0, 'L');
                $pdf->Cell($wR, 4, 'S/ ' . number_format($d['totalVentas'], 2), 0, 1, 'R');
                $pdf->SetFont('Arial', '', 7);
            }

            // Cobros de cuotas
            if ($d['totalPagosCuotas'] > 0 && $d['pagosCuotas']->isNotEmpty()) {
                $pdf->Ln(1);
                $pdf->Cell($w, 0.3, '', 'T', 1);
                $pdf->Ln(1);
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell($w, 4, utf8_decode('COBROS DE CUOTAS'), 0, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
                foreach ($d['pagosCuotas'] as $pc) {
                    $pdf->Cell($wL, 4, utf8_decode($pc->tipo_pago_nombre), 0, 0, 'L');
                    $pdf->Cell($wR, 4, 'S/ ' . number_format($pc->total, 2), 0, 1, 'R');
                }
            }

            // Movimientos
            if ($d['movimientos']->isNotEmpty()) {
                $pdf->Ln(1);
                $pdf->Cell($w, 0.3, '', 'T', 1);
                $pdf->Ln(1);
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell($w, 4, 'MOVIMIENTOS MANUALES', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 6);
                foreach ($d['movimientos'] as $m) {
                    $tipo = $m->tipo == 1 ? '[+]' : '[-]';
                    $linea = $tipo . ' ' . \Illuminate\Support\Str::limit($m->concepto, 22) . ' S/ ' . number_format($m->monto, 2);
                    $pdf->Cell($w, 4, utf8_decode($linea), 0, 1, 'L');
                }
            }

            $pdf->Ln(2);
            $pdf->Cell($w, 0.3, '', 'T', 1);

            ob_end_clean();
            $pdf->Output('I', 'ticket_arqueo_' . $fecha . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            abort(500, 'Error al generar el ticket del arqueo.');
        }
    }
}
