<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Detalle_compra;
use App\Models\Empresa;
use App\Models\Familia;
use App\Models\General;
use App\Models\Logs;
use App\Models\Menu;
use App\Models\Opciones;
use App\Models\Orden_compra;
use App\Models\PDFBufeo;
use App\Models\Persona;
use App\Models\Productos;
use App\Models\Proveedores;
use App\Models\Submenu;
use App\Models\Tipo_afectacion;
use App\Models\Tipo_documento;
use App\Models\Tipo_pago;
use App\Models\User;
use App\Service\CalcularMontosVenta;
use Carbon\Carbon;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class LogisticaController extends Controller
{
    private $submenu;
    private $logs;
    private $general;
    private $almacen;
    private $productos;
    private $categorias;
    private $proveedores;
    private $tipo_venta;
    private $tipo_pago;
    private $ordenCompra;
    private $ordenCompraDetalle;
    private $empresa;
    public function __construct()
    {
        $this->submenu = new Submenu();
        $this->logs = new Logs();
        $this->general = new General();
        $this->productos = new Productos();
        $this->categorias = new Categoria();
        $this->proveedores = new Proveedores();
        $this->tipo_pago = new Tipo_pago();
        $this->ordenCompra = new Orden_compra();
        $this->ordenCompraDetalle = new Detalle_compra();
        $this->empresa = new Empresa();
    }
    public function gestionar_productos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("gestionar_productos");
            return view('logistica/gestionar_productos', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function compras()
    {
        try {
//            ESTE IF ES PARA VERIFICAR SI ESE ROL TIENE EL PERMISO PARA LA VISTA
            $opciones = $this->submenu->optiones_por_vista("compras");
            return view('logistica/compras', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function ingreso_compra_pdf()
    {
        $id = (int) ($_GET['ordenCompra'] ?? 0);
        if (!$id) {
            return redirect()->route('logistica.compras');
        }

        $orden = DB::table('orden_compra')->where('id_orden_compra', $id)->first();
        if (!$orden) {
            echo "<script>alert('Compra no encontrada.');window.history.back();</script>";
            return;
        }

        $empresa   = $orden->id_empresa ? DB::table('empresa')->where('id_empresa', $orden->id_empresa)->first() : null;
        $proveedor = DB::table('proveedores')->where('id_proveedores', $orden->id_proveedores)->first();

        $detalle = DB::table('orden_compra_detalle as d')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_orden_compra', $id)
            ->where('d.detalle_compra_estado', 1)
            ->select('d.*', 'p.pro_codigo')
            ->get();

        $transportistas = [];
        if (!empty($orden->orden_compra_transportistas)) {
            $dec = json_decode($orden->orden_compra_transportistas, true);
            if (is_array($dec)) {
                $transportistas = array_values(array_filter(array_map(fn($t) => trim($t['nombre'] ?? ''), $dec)));
            }
        }

        $estadoMap = ['pendiente' => 'Pendiente', 'en_transito' => 'En Tránsito', 'recibido' => 'Recibido', 'anulado' => 'Anulado'];
        $simbolo   = ($orden->moneda ?? 'PEN') === 'USD' ? '$' : 'S/';
        $subtotal  = (float) $detalle->sum('detalle_compra_total_pedido');

        $pdf = new PDFBufeo('P', 'mm', 'A4');
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->AliasNbPages();

        if ($empresa && !empty($empresa->empresa_foto_ticket) && file_exists($empresa->empresa_foto_ticket)) {
            $pdf->Image($empresa->empresa_foto_ticket, 10, 10, 28, 0);
        }
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetXY(40, 11);
        $pdf->Cell(110, 6, utf8_decode($empresa->empresa_razon_social ?? 'EMPRESA'), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 8);
        if ($empresa) {
            $pdf->SetX(40);
            $pdf->Cell(110, 4, 'RUC: ' . ($empresa->empresa_ruc ?? '-'), 0, 1, 'L');
            $pdf->SetX(40);
            $pdf->MultiCell(110, 4, utf8_decode($empresa->empresa_domiciliofiscal ?? ''), 0, 'L');
        }
        $pdf->SetXY(150, 10);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(50, 7, utf8_decode('REGISTRO DE INGRESO'), 1, 2, 'C');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(50, 6, utf8_decode($orden->orden_compra_numero), 1, 2, 'C');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(50, 5, utf8_decode('Estado: ' . ($estadoMap[$orden->orden_compra_estado] ?? $orden->orden_compra_estado)), 1, 2, 'C');

        $pdf->SetY(max($pdf->GetY(), 40));
        $pdf->Ln(2);

        $lblW = 32; $valW = 62;
        $fila = function ($l1, $v1, $l2 = null, $v2 = null) use ($pdf, $lblW, $valW) {
            $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell($lblW, 5, utf8_decode($l1), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($valW, 5, utf8_decode($v1), 0, 0, 'L');
            if ($l2 !== null) {
                $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell($lblW, 5, utf8_decode($l2), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($valW, 5, utf8_decode($v2), 0, 1, 'L');
            } else {
                $pdf->Ln();
            }
        };
        $fila('Proveedor:', $proveedor->proveedores_nombre ?? ($orden->orden_compra_nom_prove ?? '-'),
              'RUC/Doc:',  $proveedor->proveedores_numero_documento ?? ($orden->orden_compra_num_document ?? '-'));
        $fila('Comprobante:', trim(($orden->orden_compra_tipo_doc ?? '') . ' ' . ($orden->orden_compra_numero_doc ?? '-')),
              'Condición:', ucfirst($orden->condicion_pago ?? '-'));
        $fila('F. Emisión:', $orden->orden_compra_fecha_emision_doc ? date('d/m/Y', strtotime($orden->orden_compra_fecha_emision_doc)) : '-',
              'F. Almacen.:', $orden->fecha_almacenamiento ? date('d/m/Y', strtotime($orden->fecha_almacenamiento)) : '-');
        $fila('G. Remitente:', $orden->orden_compra_guia_remitente ?? '-',
              'G. Transport.:', $orden->orden_compra_guia_transportista ?? '-');
        $fila('Transportistas:', !empty($transportistas) ? implode(' | ', $transportistas) : '-');
        if (!empty($orden->orden_compra_observacion)) {
            $fila('Observación:', $orden->orden_compra_observacion);
        }
        $pdf->Ln(2);

        $cols  = [10, 26, 70, 22, 18, 22, 22];
        $heads = ['#', utf8_decode('Código'), utf8_decode('Descripción'), 'Present.', 'Cant.', 'P. Compra', 'Total'];
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 7);
        foreach ($heads as $i => $h) {
            $pdf->Cell($cols[$i], 7, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 7);
        $fill = false;
        foreach ($detalle as $i => $d) {
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($cols[0], 6, $i + 1, 'B', 0, 'C', true);
            $pdf->Cell($cols[1], 6, utf8_decode(mb_substr($d->pro_codigo ?? '-', 0, 13)), 'B', 0, 'L', true);
            $pdf->Cell($cols[2], 6, utf8_decode(mb_substr($d->detalle_orden_nombre_producto ?? '-', 0, 42)), 'B', 0, 'L', true);
            $pdf->Cell($cols[3], 6, utf8_decode(mb_substr($d->presentacion ?? '-', 0, 12)), 'B', 0, 'C', true);
            $pdf->Cell($cols[4], 6, number_format((float) $d->detalle_compra_cantidad, 2), 'B', 0, 'C', true);
            $pdf->Cell($cols[5], 6, number_format((float) $d->detalle_compra_precio_compra, 2), 'B', 0, 'R', true);
            $pdf->Cell($cols[6], 6, number_format((float) $d->detalle_compra_total_pedido, 2), 'B', 1, 'R', true);
            $fill = !$fill;
        }

        $pdf->Ln(2);
        $totW = array_sum(array_slice($cols, 0, 5));
        $totales = [
            ['Subtotal',                $subtotal],
            ['Descuento (' . rtrim(rtrim(number_format((float)$orden->orden_compra_descuento_porcentaje, 2), '0'), '.') . '%)', -1 * (float) $orden->orden_compra_descuento_monto],
            ['IGV (' . rtrim(rtrim(number_format((float)$orden->orden_compra_igv_porcentaje, 2), '0'), '.') . '%)', (float) $orden->orden_compra_igv_monto],
            ['Percepción (' . rtrim(rtrim(number_format((float)$orden->orden_compra_percepcion_porcentaje, 2), '0'), '.') . '%)', (float) $orden->orden_compra_percepcion_monto],
            ['Flete',                   (float) $orden->orden_compra_flete],
            ['Gastos operativos',       (float) $orden->orden_compra_gastos_operativos],
        ];
        $pdf->SetFont('Helvetica', '', 8);
        foreach ($totales as $t) {
            $pdf->Cell($totW, 5, '', 0, 0);
            $pdf->Cell($cols[5], 5, utf8_decode($t[0]), 0, 0, 'R');
            $pdf->Cell($cols[6], 5, $simbolo . ' ' . number_format($t[1], 2), 0, 1, 'R');
        }
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($totW, 7, '', 0, 0);
        $pdf->Cell($cols[5], 7, 'TOTAL', 1, 0, 'R');
        $pdf->Cell($cols[6], 7, $simbolo . ' ' . number_format((float) $orden->orden_compra_total, 2), 1, 1, 'R');

        $pdf->Output('I', 'ingreso_' . $orden->orden_compra_numero . '.pdf');
        exit;
    }

    public function ordenCompraDetalle()
    {
        try {
            $id = $_GET['ordenCompra'] ?? null;
            if (!$id) {
                return redirect()->route('logistica.compras');
            }

            $orden_compra = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->join('users as u', 'u.id_users', '=', 'oc.id_solicitante')
                ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'oc.id_tipo_pago')
                ->leftJoin('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
                ->leftJoin('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
                ->leftJoin('almacen as al', 'al.id_almacen', '=', 'oc.id_almacen')
                ->select(
                    'oc.*',
                    'pv.proveedores_nombre', 'pv.proveedores_numero_documento',
                    'u.nombre_users',
                    'tp.tipo_pago_nombre',
                    't.tienda_nombre as sucursal_nombre',
                    'e.empresa_nombrecomercial', 'e.empresa_foto',
                    'al.almacen_nombre', 'al.almacen_direccion'
                )
                ->where('oc.id_orden_compra', $id)
                ->first();

            if (!$orden_compra) {
                return redirect()->route('logistica.compras');
            }

            $detalle_orden_compra = DB::table('orden_compra_detalle as ocd')
                ->join('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
                ->select('ocd.*', 'p.pro_nombre', 'p.pro_codigo')
                ->where('ocd.id_orden_compra', $id)
                ->get();

            $subtotal        = $detalle_orden_compra->sum('detalle_compra_total_pedido');
            $descuentoMonto  = (float) ($orden_compra->orden_compra_descuento_monto  ?? 0);
            $igvMonto        = (float) ($orden_compra->orden_compra_igv_monto        ?? 0);
            $percepcionMonto = (float) ($orden_compra->orden_compra_percepcion_monto ?? 0);
            $flete           = (float) ($orden_compra->orden_compra_flete            ?? 0);
            $gastos          = (float) ($orden_compra->orden_compra_gastos_operativos ?? 0);
            $subtotalNeto    = round($subtotal - $descuentoMonto, 2);
            $total           = round($subtotalNeto + $igvMonto + $percepcionMonto + $flete + $gastos, 2);

            $opciones = $this->submenu->optiones_por_vista("ordenCompraDetalle");

            return view('logistica/ordenCompraDetalle', compact(
                'opciones', 'orden_compra', 'detalle_orden_compra',
                'subtotal', 'descuentoMonto', 'subtotalNeto',
                'igvMonto', 'percepcionMonto', 'flete', 'gastos', 'total'
            ));

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }
    public function stock_establecimiento()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("stock_establecimiento");
            return view('logistica/stock_establecimiento', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\"); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function stock_consolidado()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("stock_consolidado");
            return view('logistica/stock_consolidado', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\"); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function transferencias_stock()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("transferencias_stock");
            return view('logistica/transferencias', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\"); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function mercaderia_transito()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("mercaderia_transito");
            return view('logistica/mercaderia_transito', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\"); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    private function obtenerDatosMercaderiaTransito(Request $request): array
    {
        $idEmpresa  = $request->id_empresa  ?? null;
        $idSucursal = $request->id_sucursal ?? null;

        $compras = DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->leftJoin('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
            ->select('oc.orden_compra_numero', 'oc.orden_compra_estado', 'oc.orden_compra_fecha',
                'oc.orden_compra_total', 'pv.proveedores_nombre', 's.sucursal_nombre')
            ->whereIn('oc.orden_compra_estado', ['pendiente', 'en_transito'])
            ->where('oc.orden_compra_activo', 1);

        if ($idSucursal) {
            $compras->where('oc.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $compras->whereExists(fn($q) => $q->select(DB::raw(1))
                ->from('sucursals')->whereColumn('sucursals.id_sucursal', 'oc.id_sucursal')
                ->where('sucursals.id_empresa', $idEmpresa));
        } else {
            $compras->whereRaw('0 = 1');
        }

        $transferencias = DB::table('transferencias_stock as t')
            ->join('sucursals as so', 'so.id_sucursal', '=', 't.id_sucursal_origen')
            ->join('sucursals as sd', 'sd.id_sucursal', '=', 't.id_sucursal_destino')
            ->leftJoin('users as u', 'u.id_users', '=', 't.id_users')
            ->select('t.transferencia_numero', 't.transferencia_estado', 't.transferencia_fecha',
                't.transferencia_motivo', 'so.sucursal_nombre as origen_nombre',
                'sd.sucursal_nombre as destino_nombre', 'u.nombre_users')
            ->whereIn('t.transferencia_estado', ['pendiente', 'en_transito']);

        if ($idSucursal) {
            $transferencias->where(fn($q) => $q->where('t.id_sucursal_origen', $idSucursal)
                ->orWhere('t.id_sucursal_destino', $idSucursal));
        } elseif ($idEmpresa) {
            $transferencias->where(fn($q) => $q
                ->whereExists(fn($s) => $s->select(DB::raw(1))->from('sucursals')
                    ->whereColumn('sucursals.id_sucursal', 't.id_sucursal_origen')
                    ->where('sucursals.id_empresa', $idEmpresa))
                ->orWhereExists(fn($s) => $s->select(DB::raw(1))->from('sucursals')
                    ->whereColumn('sucursals.id_sucursal', 't.id_sucursal_destino')
                    ->where('sucursals.id_empresa', $idEmpresa)));
        } else {
            $transferencias->whereRaw('0 = 1');
        }

        return [
            'compras'        => $compras->orderByDesc('oc.orden_compra_fecha')->get(),
            'transferencias' => $transferencias->orderByDesc('t.transferencia_fecha')->get(),
        ];
    }

    public function mercaderiaTransitoPdf(Request $request)
    {
        try {
            $d   = $this->obtenerDatosMercaderiaTransito($request);
            $pdf = new PDFBufeo('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(267, 8, 'REPORTE DE MERCADERÍA EN TRÁNSITO', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(267, 6, 'Generado: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');

            // ── Órdenes de compra ──────────────────────────────
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(267, 6, 'ÓRDENES DE COMPRA EN TRÁNSITO (' . $d['compras']->count() . ')', 1, 1, 'L', true);

            // Cabecera: 30+25+90+55+30+22+15 = 267
            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(30, 6, 'N° Orden',   1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Fecha',       1, 0, 'C', true);
            $pdf->Cell(90, 6, 'Proveedor',   1, 0, 'C', true);
            $pdf->Cell(55, 6, 'Sucursal',    1, 0, 'C', true);
            $pdf->Cell(30, 6, 'Total',       1, 0, 'C', true);
            $pdf->Cell(22, 6, 'Estado',      1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7);
            $fill = false;
            foreach ($d['compras'] as $oc) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(30, 5, $oc->orden_compra_numero ?? '-', 1, 0, 'C', true);
                $pdf->Cell(25, 5, date('d/m/Y', strtotime($oc->orden_compra_fecha)), 1, 0, 'C', true);
                $pdf->Cell(90, 5, $oc->proveedores_nombre, 1, 0, 'L', true);
                $pdf->Cell(55, 5, $oc->sucursal_nombre ?? '-', 1, 0, 'L', true);
                $pdf->Cell(30, 5, 'S/ ' . number_format($oc->orden_compra_total ?? 0, 2), 1, 0, 'R', true);
                $estado = $oc->orden_compra_estado === 'pendiente' ? 'Pendiente' : 'En Tránsito';
                $pdf->Cell(22, 5, $estado, 1, 1, 'C', true);
                $fill = !$fill;
            }

            if ($d['compras']->isEmpty()) {
                $pdf->Cell(252, 5, 'Sin órdenes de compra en tránsito.', 1, 1, 'C');
            }

            $pdf->Ln(4);

            // ── Transferencias ─────────────────────────────────
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(267, 6, 'TRANSFERENCIAS EN TRÁNSITO (' . $d['transferencias']->count() . ')', 1, 1, 'L', true);

            // Cabecera: 30+25+70+70+40+17+15 = 267
            $pdf->SetFillColor(70, 89, 110);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(30, 6, 'N° Trans.',  1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Fecha',      1, 0, 'C', true);
            $pdf->Cell(70, 6, 'Origen',     1, 0, 'C', true);
            $pdf->Cell(70, 6, 'Destino',    1, 0, 'C', true);
            $pdf->Cell(40, 6, 'Motivo',     1, 0, 'C', true);
            $pdf->Cell(17, 6, 'Estado',     1, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7);
            $fill = false;
            foreach ($d['transferencias'] as $trf) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(30, 5, $trf->transferencia_numero ?? '-', 1, 0, 'C', true);
                $pdf->Cell(25, 5, date('d/m/Y', strtotime($trf->transferencia_fecha)), 1, 0, 'C', true);
                $pdf->Cell(70, 5, $trf->origen_nombre, 1, 0, 'L', true);
                $pdf->Cell(70, 5, $trf->destino_nombre, 1, 0, 'L', true);
                $pdf->Cell(40, 5, $trf->transferencia_motivo ?? '-', 1, 0, 'L', true);
                $estado = $trf->transferencia_estado === 'pendiente' ? 'Pendiente' : 'En Tránsito';
                $pdf->Cell(17, 5, $estado, 1, 1, 'C', true);
                $fill = !$fill;
            }

            if ($d['transferencias']->isEmpty()) {
                $pdf->Cell(252, 5, 'Sin transferencias en tránsito.', 1, 1, 'C');
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="mercaderia_transito_' . date('Ymd_His') . '.pdf"');
            echo $pdf->Output('S', '');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function mercaderiaTransitoExcel(Request $request)
    {
        try {
            $d = $this->obtenerDatosMercaderiaTransito($request);
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $navy  = 'FF46596E';
            $white = 'FFFFFFFF';
            $estiloH = [
                'font'      => ['bold' => true, 'color' => ['argb' => $white], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $estiloD = [
                'font'    => ['size' => 8, 'name' => 'Arial'],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                               'color'       => ['argb' => 'FFD0D0D0']]],
            ];

            // ── Hoja 1: Compras ────────────────────────────────
            $sheet1 = $spreadsheet->getActiveSheet()->setTitle('Compras Tránsito');
            $row = 1;
            $h1 = ['A' => 'N° Orden', 'B' => 'Fecha', 'C' => 'Proveedor', 'D' => 'Sucursal', 'E' => 'Total', 'F' => 'Estado'];
            foreach ($h1 as $col => $h) $sheet1->setCellValue("{$col}{$row}", $h);
            $sheet1->getStyle("A{$row}:F{$row}")->applyFromArray($estiloH);
            $row++;
            foreach ($d['compras'] as $oc) {
                $sheet1->setCellValue("A{$row}", $oc->orden_compra_numero ?? '-');
                $sheet1->setCellValue("B{$row}", date('d/m/Y', strtotime($oc->orden_compra_fecha)));
                $sheet1->setCellValue("C{$row}", $oc->proveedores_nombre);
                $sheet1->setCellValue("D{$row}", $oc->sucursal_nombre ?? '-');
                $sheet1->setCellValue("E{$row}", (float)($oc->orden_compra_total ?? 0));
                $sheet1->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $sheet1->setCellValue("F{$row}", $oc->orden_compra_estado === 'pendiente' ? 'Pendiente' : 'En Tránsito');
                $sheet1->getStyle("A{$row}:F{$row}")->applyFromArray($estiloD);
                $row++;
            }
            foreach (range('A', 'F') as $col) $sheet1->getColumnDimension($col)->setAutoSize(true);

            // ── Hoja 2: Transferencias ─────────────────────────
            $sheet2 = $spreadsheet->createSheet()->setTitle('Transferencias Tránsito');
            $row = 1;
            $h2 = ['A' => 'N° Trans.', 'B' => 'Fecha', 'C' => 'Origen', 'D' => 'Destino', 'E' => 'Motivo', 'F' => 'Estado'];
            foreach ($h2 as $col => $h) $sheet2->setCellValue("{$col}{$row}", $h);
            $sheet2->getStyle("A{$row}:F{$row}")->applyFromArray($estiloH);
            $row++;
            foreach ($d['transferencias'] as $trf) {
                $sheet2->setCellValue("A{$row}", $trf->transferencia_numero ?? '-');
                $sheet2->setCellValue("B{$row}", date('d/m/Y', strtotime($trf->transferencia_fecha)));
                $sheet2->setCellValue("C{$row}", $trf->origen_nombre);
                $sheet2->setCellValue("D{$row}", $trf->destino_nombre);
                $sheet2->setCellValue("E{$row}", $trf->transferencia_motivo ?? '-');
                $sheet2->setCellValue("F{$row}", $trf->transferencia_estado === 'pendiente' ? 'Pendiente' : 'En Tránsito');
                $sheet2->getStyle("A{$row}:F{$row}")->applyFromArray($estiloD);
                $row++;
            }
            foreach (range('A', 'F') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

            $spreadsheet->setActiveSheetIndex(0);
            $nombreArchivo = 'mercaderia_transito_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ── Excel: Adquisiciones Recientes de un producto ────────────
    public function adquisicionesRecientesExcel(\Illuminate\Http\Request $request)
    {
        try {
            $idPro = (int) $request->id_pro;
            abort_if(!$idPro, 404);
            abort_if(!auth()->user()->can('gestionar_productos.submenu'), 403);

            $producto = DB::table('productos')->where('id_pro', $idPro)->first();
            $nombreProducto = $producto ? $producto->pro_nombre : "Producto #{$idPro}";

            $compras = DB::table('orden_compra_detalle as ocd')
                ->join('orden_compra as oc', 'oc.id_orden_compra', '=', 'ocd.id_orden_compra')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->where('ocd.id_pro', $idPro)
                ->where('oc.orden_compra_activo', 1)
                ->select(
                    'oc.orden_compra_numero', 'oc.orden_compra_fecha',
                    'oc.orden_compra_tipo_doc', 'oc.orden_compra_numero_doc',
                    'oc.orden_compra_guia_transportista', 'oc.orden_compra_guia_remitente',
                    'oc.orden_compra_estado', 'oc.orden_compra_total',
                    'oc.condicion_pago', 'pv.proveedores_nombre',
                    'ocd.detalle_compra_cantidad', 'ocd.detalle_compra_precio_compra',
                    'ocd.detalle_compra_total_pedido'
                )
                ->orderByDesc('oc.orden_compra_fecha')
                ->get();

            $idOrdenes = DB::table('orden_compra_detalle')
                ->where('id_pro', $idPro)
                ->pluck('id_orden_compra')->toArray();

            $notas = empty($idOrdenes) ? collect() :
                DB::table('notas_compra as nc')
                    ->leftJoin('proveedores as pv', 'pv.id_proveedores', '=', 'nc.id_proveedores')
                    ->leftJoin('orden_compra as oc', 'oc.id_orden_compra', '=', 'nc.id_orden_compra')
                    ->whereIn('nc.id_orden_compra', $idOrdenes)
                    ->where('nc.nota_estado', '!=', 'anulado')
                    ->select(
                        'nc.tipo_nota', 'nc.nota_numero', 'nc.nota_numero_doc',
                        'nc.nota_fecha', 'nc.nota_motivo', 'nc.nota_total',
                        'nc.nota_estado', 'pv.proveedores_nombre',
                        'oc.orden_compra_numero'
                    )
                    ->orderByDesc('nc.nota_fecha')
                    ->get();

            $sp  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $navy  = 'FF1E3A5F';
            $white = 'FFFFFFFF';

            $estiloTit = [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $navy], 'name' => 'Arial'],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
            ];
            $estiloSub = [
                'font' => ['size' => 9, 'color' => ['argb' => 'FF555555'], 'name' => 'Arial'],
            ];
            $estiloH = [
                'font'      => ['bold' => true, 'color' => ['argb' => $white], 'size' => 8.5, 'name' => 'Arial'],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                 'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                 'wrapText'   => true],
                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                  'color'       => ['argb' => 'FFFFFFFF']]],
            ];
            $estiloD = [
                'font'    => ['size' => 8.5, 'name' => 'Arial'],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                'color'       => ['argb' => 'FFDDDDDD']]],
            ];
            $estiloAlt = array_merge($estiloD, [
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['argb' => 'FFF5F8FC']],
            ]);

            // ── Hoja 1: Órdenes de compra ─────────────────────────
            $s1 = $sp->getActiveSheet()->setTitle('Órdenes de Compra');

            $s1->setCellValue('A1', 'Adquisiciones Recientes');
            $s1->getStyle('A1')->applyFromArray($estiloTit);
            $s1->setCellValue('A2', "Producto: {$nombreProducto}");
            $s1->getStyle('A2')->applyFromArray($estiloSub);
            $s1->setCellValue('A3', 'Generado: ' . now()->format('d/m/Y H:i'));
            $s1->getStyle('A3')->applyFromArray($estiloSub);

            $heads1 = ['N° Compra', 'Proveedor', 'Fecha', 'Tipo Doc.', 'N° Documento',
                       'Cantidad', 'P. Compra (S/)', 'Subtotal (S/)',
                       'Transportista', 'Guía Remitente', 'Condición', 'Estado'];
            $cols1  = range('A', 'L');
            $fH = 5;
            foreach ($cols1 as $i => $col) {
                $s1->setCellValue("{$col}{$fH}", $heads1[$i]);
            }
            $s1->getStyle("A{$fH}:L{$fH}")->applyFromArray($estiloH);
            $s1->getRowDimension($fH)->setRowHeight(16);

            $estadoMap = ['recibido' => 'Recibido', 'en_transito' => 'En tránsito',
                          'pendiente' => 'Pendiente', 'anulado' => 'Anulado'];
            $fila = $fH + 1;
            foreach ($compras as $i => $r) {
                $style = $i % 2 === 0 ? $estiloD : $estiloAlt;
                $s1->setCellValue("A{$fila}", $r->orden_compra_numero ?? '-');
                $s1->setCellValue("B{$fila}", $r->proveedores_nombre);
                $s1->setCellValue("C{$fila}", $r->orden_compra_fecha ? date('d/m/Y', strtotime($r->orden_compra_fecha)) : '-');
                $s1->setCellValue("D{$fila}", $r->orden_compra_tipo_doc ?? '-');
                $s1->setCellValue("E{$fila}", $r->orden_compra_numero_doc ?? '-');
                $s1->setCellValue("F{$fila}", (float) $r->detalle_compra_cantidad);
                $s1->setCellValue("G{$fila}", (float) $r->detalle_compra_precio_compra);
                $s1->setCellValue("H{$fila}", (float) $r->detalle_compra_total_pedido);
                $s1->setCellValue("I{$fila}", $r->orden_compra_guia_transportista ?? '-');
                $s1->setCellValue("J{$fila}", $r->orden_compra_guia_remitente ?? '-');
                $s1->setCellValue("K{$fila}", ucfirst($r->condicion_pago ?? '-'));
                $s1->setCellValue("L{$fila}", $estadoMap[$r->orden_compra_estado] ?? ucfirst($r->orden_compra_estado ?? '-'));
                $s1->getStyle("G{$fila}:H{$fila}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $s1->getStyle("A{$fila}:L{$fila}")->applyFromArray($style);
                $fila++;
            }
            $widths1 = [18, 30, 11, 10, 18, 9, 14, 14, 22, 18, 11, 12];
            foreach ($cols1 as $i => $col) {
                $s1->getColumnDimension($col)->setWidth($widths1[$i]);
            }

            // ── Hoja 2: Notas NC / DB ─────────────────────────────
            $s2 = $sp->createSheet()->setTitle('Notas NC-DB');

            $s2->setCellValue('A1', 'Notas de Crédito / Débito');
            $s2->getStyle('A1')->applyFromArray($estiloTit);
            $s2->setCellValue('A2', "Producto: {$nombreProducto}");
            $s2->getStyle('A2')->applyFromArray($estiloSub);

            $heads2 = ['Tipo', 'N° Nota', 'Proveedor', 'Fecha', 'N° Documento', 'Motivo', 'Total (S/)', 'Estado', 'Orden Vinculada'];
            $cols2  = range('A', 'I');
            foreach ($cols2 as $i => $col) {
                $s2->setCellValue("{$col}4", $heads2[$i]);
            }
            $s2->getStyle('A4:I4')->applyFromArray($estiloH);

            $fila = 5;
            foreach ($notas as $i => $n) {
                $style = $i % 2 === 0 ? $estiloD : $estiloAlt;
                $s2->setCellValue("A{$fila}", $n->tipo_nota === 'NC' ? 'Nota Crédito' : 'Nota Débito');
                $s2->setCellValue("B{$fila}", $n->nota_numero ?? '-');
                $s2->setCellValue("C{$fila}", $n->proveedores_nombre ?? '-');
                $s2->setCellValue("D{$fila}", $n->nota_fecha ? date('d/m/Y', strtotime($n->nota_fecha)) : '-');
                $s2->setCellValue("E{$fila}", $n->nota_numero_doc ?? '-');
                $s2->setCellValue("F{$fila}", $n->nota_motivo ?? '-');
                $s2->setCellValue("G{$fila}", (float) $n->nota_total);
                $s2->setCellValue("H{$fila}", ucfirst($n->nota_estado ?? '-'));
                $s2->setCellValue("I{$fila}", $n->orden_compra_numero ?? '-');
                $s2->getStyle("G{$fila}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $s2->getStyle("A{$fila}:I{$fila}")->applyFromArray($style);
                $fila++;
            }
            $widths2 = [14, 14, 28, 11, 18, 36, 13, 11, 18];
            foreach ($cols2 as $i => $col) {
                $s2->getColumnDimension($col)->setWidth($widths2[$i]);
            }

            // ── Hoja 3: Ventas ────────────────────────────────────
            $ventas = DB::table('ventas_detalle as vd')
                ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->leftJoin('users as u', 'u.id_users', '=', 'v.id_users')
                ->where('vd.id_pro', $idPro)
                ->whereNull('va.id_venta')
                ->select(
                    'v.venta_serie', 'v.venta_correlativo', 'v.venta_tipo', 'v.venta_fecha',
                    'vd.venta_detalle_cantidad', 'vd.venta_detalle_precio_unitario', 'vd.venta_detalle_importe_total',
                    DB::raw("COALESCE(c.cliente_razonsocial, c.cliente_nombre, 'Sin cliente') as cliente_nombre"),
                    DB::raw("COALESCE(c.cliente_numero, '') as cliente_doc"),
                    'u.nombre_users'
                )
                ->orderByDesc('v.venta_fecha')
                ->get();

            $tipoMap = ['01' => 'Factura', '03' => 'Boleta', '07' => 'N. Crédito', '08' => 'N. Débito'];

            $s3 = $sp->createSheet()->setTitle('Ventas');
            $s3->setCellValue('A1', 'Ventas');
            $s3->getStyle('A1')->applyFromArray($estiloTit);
            $s3->setCellValue('A2', "Producto: {$nombreProducto}");
            $s3->getStyle('A2')->applyFromArray($estiloSub);

            $heads3 = ['Comprobante', 'Tipo', 'Fecha', 'Cliente', 'Doc. Cliente', 'Cantidad', 'P. Unitario (S/)', 'Importe (S/)', 'Vendedor'];
            $cols3  = range('A', 'I');
            foreach ($cols3 as $i => $col) {
                $s3->setCellValue("{$col}4", $heads3[$i]);
            }
            $s3->getStyle('A4:I4')->applyFromArray($estiloH);

            $fila = 5;
            foreach ($ventas as $i => $vt) {
                $style = $i % 2 === 0 ? $estiloD : $estiloAlt;
                $s3->setCellValue("A{$fila}", ($vt->venta_serie ?? '') . '-' . ($vt->venta_correlativo ?? ''));
                $s3->setCellValue("B{$fila}", $tipoMap[$vt->venta_tipo ?? ''] ?? ($vt->venta_tipo ?? '-'));
                $s3->setCellValue("C{$fila}", $vt->venta_fecha ? date('d/m/Y', strtotime($vt->venta_fecha)) : '-');
                $s3->setCellValue("D{$fila}", $vt->cliente_nombre);
                $s3->setCellValue("E{$fila}", $vt->cliente_doc ?: '-');
                $s3->setCellValue("F{$fila}", (float) $vt->venta_detalle_cantidad);
                $s3->setCellValue("G{$fila}", (float) $vt->venta_detalle_precio_unitario);
                $s3->setCellValue("H{$fila}", (float) $vt->venta_detalle_importe_total);
                $s3->setCellValue("I{$fila}", $vt->nombre_users ?? '-');
                $s3->getStyle("G{$fila}:H{$fila}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
                $s3->getStyle("A{$fila}:I{$fila}")->applyFromArray($style);
                $fila++;
            }
            $widths3 = [18, 12, 11, 32, 14, 9, 16, 14, 22];
            foreach ($cols3 as $i => $col) {
                $s3->getColumnDimension($col)->setWidth($widths3[$i]);
            }

            $sp->setActiveSheetIndex(0);
            $nombreArchivo = 'Adquisiciones_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $nombreProducto) . '_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$nombreArchivo}\"");
            header('Cache-Control: max-age=0');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sp))->save('php://output');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return redirect()->route('logistica.gestionar_productos')->with('error', 'Error al generar el Excel.');
        }
    }

    public function notas_compra()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("notas_compra");
            return view('logistica/notas-compra', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function reporte_compras()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("reporte_compras");
            return view('logistica/reporte_compras', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\"); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function reporte_compras_pdf(Request $request)
    {
        try {
            $desde      = $request->desde ?? null;
            $hasta      = $request->hasta ?? null;
            $idProveedor = $request->proveedor ?? null;
            $agrupacion = $request->agrupacion ?? 'mensual';
            $idEmpresa  = $request->id_empresa  ? (int) $request->id_empresa  : null;
            $idSucursal = $request->id_sucursal ? (int) $request->id_sucursal : null;

            $query = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->join('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
                ->join('orden_compra_detalle as ocd', 'ocd.id_orden_compra', '=', 'oc.id_orden_compra')
                ->join('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
                ->where('oc.orden_compra_estado', 'recibido');

            if ($desde && $hasta) {
                $query->whereBetween(DB::raw('DATE(oc.orden_compra_fecha)'), [$desde, $hasta]);
            }
            if ($idProveedor) {
                $query->where('oc.id_proveedores', $idProveedor);
            }
            if ($idSucursal > 0) {
                $query->where('oc.id_sucursal', $idSucursal);
            } elseif ($idEmpresa > 0) {
                $query->where('s.id_empresa', $idEmpresa);
            }

            $detalle = (clone $query)
                ->select(
                    'pv.proveedores_nombre',
                    'p.pro_nombre',
                    'p.pro_codigo',
                    DB::raw('SUM(COALESCE(ocd.detalle_compra_cantidad_recibida, ocd.detalle_compra_cantidad)) as total_cantidad'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido) as total_costo_base'),
                    DB::raw('SUM(COALESCE(ocd.flete, 0)) as total_flete'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo'),
                    DB::raw('MAX(p.pro_precio_venta) as precio_venta_ref')
                )
                ->groupBy('pv.id_proveedores', 'pv.proveedores_nombre', 'p.id_pro', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('pv.proveedores_nombre')
                ->orderByDesc('total_costo')
                ->get();

            $totales = (clone $query)
                ->selectRaw('
                    COUNT(DISTINCT oc.id_orden_compra) as num_ordenes,
                    COUNT(DISTINCT oc.id_proveedores) as num_proveedores,
                    SUM(ocd.detalle_compra_total_pedido) as total_costo_base,
                    SUM(COALESCE(ocd.flete, 0)) as total_flete,
                    SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo
                ')
                ->first();

            [$nR, $nG, $nB] = [30, 58, 95];
            [$gR, $gG, $gB] = [245, 247, 250];
            [$bR, $bG, $bB] = [210, 218, 228];

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
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetXY(10, 13);
            $pdf->Cell($W, 8, utf8_decode('REPORTE DE COMPRAS'), 0, 1, 'C');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(10, 22);
            $periodoTxt = ($desde && $hasta)
                ? 'Período: ' . date('d/m/Y', strtotime($desde)) . ' — ' . date('d/m/Y', strtotime($hasta))
                : 'Sin filtro de fecha';
            $pdf->Cell($W, 5, utf8_decode($periodoTxt), 0, 1, 'C');
            $pdf->SetTextColor(30, 41, 59);

            // Resumen
            $pdf->SetY(33);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetFillColor($gR, $gG, $gB);
            $summaryItems = [
                ['Órdenes recibidas', $totales->num_ordenes ?? 0],
                ['Proveedores',        $totales->num_proveedores ?? 0],
                ['Costo base total',  'S/ ' . number_format($totales->total_costo_base ?? 0, 2)],
                ['Flete total',       'S/ ' . number_format($totales->total_flete ?? 0, 2)],
                ['Costo total',       'S/ ' . number_format($totales->total_costo ?? 0, 2)],
            ];
            $bw = $W / count($summaryItems);
            foreach ($summaryItems as $s) {
                $pdf->SetFont('Helvetica', '', 6);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell($bw, 4, utf8_decode($s[0]), 0, 0, 'C', true);
            }
            $pdf->Ln();
            foreach ($summaryItems as $s) {
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell($bw, 6, (string) $s[1], 0, 0, 'C', true);
            }
            $pdf->Ln(10);

            // Table header
            $cols = [8, 65, 55, 22, 28, 28, 28, 30, 13];
            $heads = ['#', 'PROVEEDOR', utf8_decode('PRODUCTO'), utf8_decode('CÓDIGO'), 'CANT.', 'C. BASE', 'FLETE', 'C. TOTAL', 'P.V.R.'];
            $aligns = ['C', 'L', 'L', 'C', 'C', 'R', 'R', 'R', 'R'];

            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 6);
            foreach ($heads as $i => $h) {
                $pdf->Cell($cols[$i], 6, $h, 0, 0, $aligns[$i], true);
            }
            $pdf->Ln();

            $pdf->SetTextColor(30, 41, 59);
            $n = 1;
            $prevProv = '';
            foreach ($detalle as $idx => $row) {
                $pdf->CheckPageBreak(6);
                $fill = ($idx % 2 === 0);
                $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                $pdf->SetFont('Helvetica', '', 6);
                $provTxt = ($prevProv !== $row->proveedores_nombre)
                    ? utf8_decode(mb_substr($row->proveedores_nombre, 0, 35))
                    : '  ↳';
                $prevProv = $row->proveedores_nombre;
                $pdf->Cell($cols[0], 5, $n++, 'B', 0, 'C', $fill);
                $pdf->Cell($cols[1], 5, $provTxt, 'B', 0, 'L', $fill);
                $pdf->Cell($cols[2], 5, utf8_decode(mb_substr($row->pro_nombre, 0, 38)), 'B', 0, 'L', $fill);
                $pdf->Cell($cols[3], 5, $row->pro_codigo ?? '', 'B', 0, 'C', $fill);
                $pdf->Cell($cols[4], 5, number_format((float)$row->total_cantidad, 2), 'B', 0, 'C', $fill);
                $pdf->Cell($cols[5], 5, 'S/ ' . number_format((float)$row->total_costo_base, 2), 'B', 0, 'R', $fill);
                $pdf->Cell($cols[6], 5, 'S/ ' . number_format((float)$row->total_flete, 2), 'B', 0, 'R', $fill);
                $pdf->Cell($cols[7], 5, 'S/ ' . number_format((float)$row->total_costo, 2), 'B', 0, 'R', $fill);
                $pvr = (float)$row->precio_venta_ref > 0 ? 'S/ ' . number_format((float)$row->precio_venta_ref, 2) : '—';
                $pdf->Cell($cols[8], 5, $pvr, 'B', 1, 'R', $fill);
            }

            // Total row
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 6);
            $totalCols = array_sum(array_slice($cols, 0, 5));
            $pdf->Cell($totalCols, 6, 'TOTAL GENERAL', 0, 0, 'R', true);
            $pdf->Cell($cols[5], 6, 'S/ ' . number_format((float)($totales->total_costo_base ?? 0), 2), 0, 0, 'R', true);
            $pdf->Cell($cols[6], 6, 'S/ ' . number_format((float)($totales->total_flete ?? 0), 2), 0, 0, 'R', true);
            $pdf->Cell($cols[7], 6, 'S/ ' . number_format((float)($totales->total_costo ?? 0), 2), 0, 0, 'R', true);
            $pdf->Cell($cols[8], 6, '', 0, 1, 'R', true);

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

            $pdf->Output('I', 'Reporte-Compras-' . date('Ymd') . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el PDF.'); window.location.href = '" . route('admin') . "';</script>";
        }
    }

    public function reporte_compras_excel(Request $request)
    {
        try {
            $desde      = $request->desde ?? null;
            $hasta      = $request->hasta ?? null;
            $idProveedor = $request->proveedor ?? null;
            $agrupacion = $request->agrupacion ?? 'mensual';
            $idEmpresa  = $request->id_empresa  ? (int) $request->id_empresa  : null;
            $idSucursal = $request->id_sucursal ? (int) $request->id_sucursal : null;

            $query = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->join('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
                ->join('orden_compra_detalle as ocd', 'ocd.id_orden_compra', '=', 'oc.id_orden_compra')
                ->join('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
                ->where('oc.orden_compra_estado', 'recibido');

            if ($desde && $hasta) {
                $query->whereBetween(DB::raw('DATE(oc.orden_compra_fecha)'), [$desde, $hasta]);
            }
            if ($idProveedor) {
                $query->where('oc.id_proveedores', $idProveedor);
            }
            if ($idSucursal > 0) {
                $query->where('oc.id_sucursal', $idSucursal);
            } elseif ($idEmpresa > 0) {
                $query->where('s.id_empresa', $idEmpresa);
            }

            $detalle = (clone $query)
                ->select(
                    'pv.proveedores_nombre',
                    'p.pro_nombre',
                    'p.pro_codigo',
                    DB::raw('SUM(COALESCE(ocd.detalle_compra_cantidad_recibida, ocd.detalle_compra_cantidad)) as total_cantidad'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido) as total_costo_base'),
                    DB::raw('SUM(COALESCE(ocd.flete, 0)) as total_flete'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo'),
                    DB::raw('MAX(p.pro_precio_venta) as precio_venta_ref'),
                    DB::raw('COUNT(DISTINCT oc.id_orden_compra) as num_ordenes')
                )
                ->groupBy('pv.id_proveedores', 'pv.proveedores_nombre', 'p.id_pro', 'p.pro_nombre', 'p.pro_codigo')
                ->orderBy('pv.proveedores_nombre')
                ->orderByDesc('total_costo')
                ->get();

            $totales = (clone $query)
                ->selectRaw('
                    COUNT(DISTINCT oc.id_orden_compra) as num_ordenes,
                    COUNT(DISTINCT oc.id_proveedores) as num_proveedores,
                    SUM(ocd.detalle_compra_total_pedido) as total_costo_base,
                    SUM(COALESCE(ocd.flete, 0)) as total_flete,
                    SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo
                ')
                ->first();

            $groupFormat = $agrupacion === 'diario'
                ? "DATE_FORMAT(oc.orden_compra_fecha, '%Y-%m-%d')"
                : "DATE_FORMAT(oc.orden_compra_fecha, '%Y-%m')";
            $labelFormat = $agrupacion === 'diario'
                ? "DATE_FORMAT(oc.orden_compra_fecha, '%d/%m/%Y')"
                : "DATE_FORMAT(oc.orden_compra_fecha, '%m/%Y')";

            $porPeriodo = (clone $query)
                ->select(
                    DB::raw("{$labelFormat} as periodo_label"),
                    DB::raw('COUNT(DISTINCT oc.id_orden_compra) as num_ordenes'),
                    DB::raw('COUNT(DISTINCT oc.id_proveedores) as num_proveedores'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido) as total_costo_base'),
                    DB::raw('SUM(COALESCE(ocd.flete, 0)) as total_flete'),
                    DB::raw('SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo')
                )
                ->groupByRaw("{$groupFormat}, {$labelFormat}")
                ->orderByRaw($groupFormat)
                ->get();

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $navy = 'FF1E3A5F';
            $white = 'FFFFFFFF';
            $gray = 'FFF5F7FA';

            $estiloTitulo = [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $navy], 'name' => 'Arial'],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $estiloEnc = [
                'font'      => ['bold' => true, 'color' => ['argb' => $white], 'size' => 8, 'name' => 'Arial'],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $white]]],
            ];
            $estiloBorde = [
                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
                'font'      => ['size' => 8, 'name' => 'Arial'],
                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ];

            // ── HOJA 1: Por Proveedor ──────────────────────────────
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Por Proveedor');

            $sheet1->mergeCells('A1:I1');
            $sheet1->setCellValue('A1', 'Reporte de Compras — Por Proveedor');
            $sheet1->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet1->getRowDimension(1)->setRowHeight(20);

            $periodo = ($desde && $hasta)
                ? date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta))
                : 'Sin filtro';
            $sheet1->setCellValue('A2', 'Período:');
            $sheet1->getStyle('A2')->applyFromArray(['font' => ['bold' => true, 'size' => 8]]);
            $sheet1->setCellValue('B2', $periodo);
            $sheet1->getStyle('B2')->applyFromArray(['font' => ['size' => 8]]);

            $encH1 = ['A' => '#', 'B' => 'Proveedor', 'C' => 'Producto', 'D' => 'Código', 'E' => 'Cant.', 'F' => 'Costo base', 'G' => 'Flete', 'H' => 'Costo total', 'I' => 'P.V. Referencial'];
            $fila = 4;
            foreach ($encH1 as $col => $txt) {
                $sheet1->setCellValue("{$col}{$fila}", $txt);
            }
            $sheet1->getStyle("A{$fila}:I{$fila}")->applyFromArray($estiloEnc);
            $sheet1->getRowDimension($fila)->setRowHeight(18);

            $fila = 5;
            foreach ($detalle as $i => $row) {
                $sheet1->setCellValue("A{$fila}", $i + 1);
                $sheet1->setCellValue("B{$fila}", $row->proveedores_nombre);
                $sheet1->setCellValue("C{$fila}", $row->pro_nombre);
                $sheet1->setCellValue("D{$fila}", $row->pro_codigo);
                $sheet1->setCellValue("E{$fila}", (float)$row->total_cantidad);
                $sheet1->setCellValue("F{$fila}", (float)$row->total_costo_base);
                $sheet1->setCellValue("G{$fila}", (float)$row->total_flete);
                $sheet1->setCellValue("H{$fila}", (float)$row->total_costo);
                $sheet1->setCellValue("I{$fila}", (float)$row->precio_venta_ref);
                $sheet1->getStyle("A{$fila}:I{$fila}")->applyFromArray($estiloBorde);
                if ($i % 2 === 0) {
                    $sheet1->getStyle("A{$fila}:I{$fila}")->applyFromArray(['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F7FA']]]);
                }
                $fila++;
            }

            // Total row
            $sheet1->setCellValue("E{$fila}", 'TOTAL');
            $sheet1->setCellValue("F{$fila}", (float)($totales->total_costo_base ?? 0));
            $sheet1->setCellValue("G{$fila}", (float)($totales->total_flete ?? 0));
            $sheet1->setCellValue("H{$fila}", (float)($totales->total_costo ?? 0));
            $sheet1->getStyle("A{$fila}:I{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => $white]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
            ]);

            foreach (['A' => 5, 'B' => 30, 'C' => 35, 'D' => 14, 'E' => 12, 'F' => 14, 'G' => 12, 'H' => 14, 'I' => 14] as $col => $w) {
                $sheet1->getColumnDimension($col)->setWidth($w);
            }
            $sheet1->getStyle("F5:I{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');

            // ── HOJA 2: Por Período ────────────────────────────────
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Por Período');

            $sheet2->mergeCells('A1:H1');
            $sheet2->setCellValue('A1', 'Reporte de Compras — Por ' . ($agrupacion === 'diario' ? 'Día' : 'Mes'));
            $sheet2->getStyle('A1')->applyFromArray($estiloTitulo);
            $sheet2->getRowDimension(1)->setRowHeight(20);

            $encH2 = ['A' => '#', 'B' => ($agrupacion === 'diario' ? 'Fecha' : 'Mes'), 'C' => 'Órdenes', 'D' => 'Proveedores', 'E' => 'Cant. ítems', 'F' => 'Costo base', 'G' => 'Flete', 'H' => 'Costo total'];
            $fila2 = 3;
            foreach ($encH2 as $col => $txt) {
                $sheet2->setCellValue("{$col}{$fila2}", $txt);
            }
            $sheet2->getStyle("A{$fila2}:H{$fila2}")->applyFromArray($estiloEnc);
            $sheet2->getRowDimension($fila2)->setRowHeight(18);

            $fila2 = 4;
            foreach ($porPeriodo as $i => $p) {
                $sheet2->setCellValue("A{$fila2}", $i + 1);
                $sheet2->setCellValue("B{$fila2}", $p->periodo_label);
                $sheet2->setCellValue("C{$fila2}", (int)$p->num_ordenes);
                $sheet2->setCellValue("D{$fila2}", (int)$p->num_proveedores);
                $sheet2->setCellValue("E{$fila2}", (float)$p->total_cantidad);
                $sheet2->setCellValue("F{$fila2}", (float)$p->total_costo_base);
                $sheet2->setCellValue("G{$fila2}", (float)$p->total_flete);
                $sheet2->setCellValue("H{$fila2}", (float)$p->total_costo);
                $sheet2->getStyle("A{$fila2}:H{$fila2}")->applyFromArray($estiloBorde);
                if ($i % 2 === 0) {
                    $sheet2->getStyle("A{$fila2}:H{$fila2}")->applyFromArray(['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F7FA']]]);
                }
                $fila2++;
            }

            $sheet2->setCellValue("E{$fila2}", 'TOTAL');
            $sheet2->setCellValue("F{$fila2}", (float)($totales->total_costo_base ?? 0));
            $sheet2->setCellValue("G{$fila2}", (float)($totales->total_flete ?? 0));
            $sheet2->setCellValue("H{$fila2}", (float)($totales->total_costo ?? 0));
            $sheet2->getStyle("A{$fila2}:H{$fila2}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => $white]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $navy]],
            ]);

            foreach (['A' => 5, 'B' => 15, 'C' => 10, 'D' => 12, 'E' => 12, 'F' => 14, 'G' => 12, 'H' => 14] as $col => $w) {
                $sheet2->getColumnDimension($col)->setWidth($w);
            }
            $sheet2->getStyle("F4:H{$fila2}")->getNumberFormat()->setFormatCode('#,##0.00');

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'Reporte-Compras-' . date('Ymd-His') . '.xlsx';

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

    public function compras_pdf()
    {
        try {
            $id = $_GET['ordenCompra'] ?? null;
            if (!$id) {
                echo "<script>alert('Error'); window.location.href = '" . route('admin') . "';</script>";
                return;
            }

            $oc = DB::table('orden_compra as oc')
                ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
                ->join('users as u', 'u.id_users', '=', 'oc.id_solicitante')
                ->leftJoin('persona as pe', 'pe.id_persona', '=', 'u.id_persona')
                ->leftJoin('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
                ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'oc.id_tipo_pago')
                ->select(
                    'oc.*',
                    'pv.proveedores_nombre', 'pv.proveedores_numero_documento',
                    'u.nombre_users', 'u.email as user_email',
                    'pe.persona_dni', 'pe.persona_telefono',
                    'pe.persona_nombre', 'pe.persona_apellido_paterno', 'pe.persona_apellido_materno',
                    'tp.tipo_pago_nombre',
                    't.tienda_nombre as sucursal_nombre',
                )
                ->where('oc.id_orden_compra', $id)
                ->first();

            if (!$oc) {
                return redirect()->route('logistica.compras');
            }

            $detalle = DB::table('orden_compra_detalle as ocd')
                ->join('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
                ->select('ocd.*', 'p.pro_codigo')
                ->where('ocd.id_orden_compra', $id)
                ->get();

            // oc.* incluye id_empresa de la orden; fallback via tiendas si hay id_sucursal
            $empId = $oc->id_empresa
                ?? DB::table('tiendas')->where('id_tienda', $oc->id_sucursal)->value('id_empresa');
            $emp = $empId ? DB::table('empresa')->where('id_empresa', $empId)->first() : null;

            // ── Cálculo de totales ───────────────────────────────
            $moneda          = $oc->moneda ?? 'PEN';
            $sym             = $moneda === 'USD' ? 'USD' : ($moneda === 'EUR' ? 'EUR' : 'S/');
            $subtotal        = (float) $detalle->sum('detalle_compra_total_pedido');
            $descuentoMonto  = (float) ($oc->orden_compra_descuento_monto         ?? 0);
            $descuentoPct    = (float) ($oc->orden_compra_descuento_porcentaje    ?? 0);
            $igvMonto        = (float) ($oc->orden_compra_igv_monto               ?? 0);
            $igvPct          = (float) ($oc->orden_compra_igv_porcentaje          ?? 0);
            $percepcionMonto = (float) ($oc->orden_compra_percepcion_monto        ?? 0);
            $percepcionPct   = (float) ($oc->orden_compra_percepcion_porcentaje   ?? 0);
            $flete           = (float) ($oc->orden_compra_flete                   ?? 0);
            $gastos          = (float) ($oc->orden_compra_gastos_operativos       ?? 0);
            $subtotalNeto    = round($subtotal - $descuentoMonto, 2);
            $total           = round($subtotalNeto + $igvMonto + $percepcionMonto + $flete + $gastos, 2);

            // ── Transportistas ──────────────────────────────────
            $transportistasList   = !empty($oc->orden_compra_transportistas)
                ? json_decode($oc->orden_compra_transportistas, true)
                : [];
            $transportistasNombres = array_values(array_filter(
                array_column($transportistasList ?? [], 'nombre')
            ));

            // ── Paleta de colores ────────────────────────────────
            [$nR, $nG, $nB] = [30,  58,  95];   // navy  #1E3A5F
            [$gR, $gG, $gB] = [245, 247, 250];  // gray bg
            [$bR, $bG, $bB] = [210, 218, 228];  // border

            $pdf = new PDFBufeo('P', 'mm', 'A4');
            $pdf->SetMargins(12, 12, 12);
            $pdf->SetAutoPageBreak(true, 18);
            $pdf->AddPage();
            $pdf->AliasNbPages();

            $W = 186; // ancho útil (210 - 24)

            // ════════════════════════════════════════════════════
            // BANDA HEADER
            // ════════════════════════════════════════════════════
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->Rect(12, 12, $W, 30, 'F');

            // Logo (izquierda) — public_path para ruta absoluta
            if ($emp && !empty($emp->empresa_foto) && file_exists(public_path($emp->empresa_foto))) {
                $pdf->Image(public_path($emp->empresa_foto), 14, 2, 50, 0);
            }

            // Título (derecha)
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 20);
            $pdf->SetXY(12, 15);
            $pdf->Cell($W - 4, 10, 'ORDEN DE COMPRA', 0, 1, 'R');

            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetXY(12, 26);
            $pdf->Cell($W - 4, 6, utf8_decode($oc->orden_compra_numero), 0, 1, 'R');

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(12, 33);
            $pdf->Cell($W - 4, 5, 'Fecha: ' . date('d/m/Y', strtotime($oc->orden_compra_fecha)) . '   Moneda: ' . $moneda, 0, 1, 'R');

            $pdf->SetTextColor(30, 41, 59);

            // ════════════════════════════════════════════════════
            // BLOQUE: EMPRESA (izq) | INFO OC (der)
            // ════════════════════════════════════════════════════
            $y0   = 47;
            $cL   = 108;
            $cR   = 78;
            $xR   = 12 + $cL;
            $rowH = 5;

            // ── Empresa ──────────────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetTextColor($nR, $nG, $nB);
            $pdf->SetXY(12, $y0);
            $pdf->Cell($cL, 5, 'EMPRESA EMISORA', 0, 1, 'L');
            $pdf->SetDrawColor($nR, $nG, $nB);
            $pdf->Line(12, $pdf->GetY(), 12 + $cL, $pdf->GetY());

            $yEmp = $pdf->GetY() + 1;
            $datosEmp = [
                ['RUC',          $emp->empresa_ruc ?? '-'],
                ['Razon Social', utf8_decode($emp->empresa_razon_social ?? '-')],
                ['Direccion',    utf8_decode($emp->empresa_domiciliofiscal ?? '-')],
                ['Tel.',         trim(($emp->empresa_telefono1 ?? '') . ' ' . ($emp->empresa_telefono2 ?? '')) ?: '-'],
                ['Correo',       $emp->empresa_correo ?? '-'],
            ];
            foreach ($datosEmp as [$lbl, $val]) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->SetXY(12, $yEmp);
                $pdf->Cell(22, $rowH, utf8_decode($lbl) . ':', 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->MultiAlignCell($cL - 22, $rowH, $val, 0, 1, 'L');
                $yEmp += $rowH;
            }

            // ── Info OC (caja sombreada derecha) ─────────────────
            $nombreCompleto = trim(implode(' ', array_filter([
                $oc->persona_nombre,
                $oc->persona_apellido_paterno,
                $oc->persona_apellido_materno,
            ]))) ?: ($oc->nombre_users ?? '-');

            $datosOC = [
                ['N° Compra',   utf8_decode($oc->orden_compra_numero)],
                ['Estado',      strtoupper($oc->orden_compra_estado ?? '-')],
                ['Moneda',      $moneda],
                ['Condicion',   ucfirst($oc->condicion_pago ?? 'contado')],
                ['F. Orden',    date('d/m/Y', strtotime($oc->orden_compra_fecha))],
                ['F. Emision',  $oc->orden_compra_fecha_emision_doc
                    ? date('d/m/Y', strtotime($oc->orden_compra_fecha_emision_doc)) : '-'],
                ['F. Almacen',  $oc->fecha_almacenamiento
                    ? date('d/m/Y', strtotime($oc->fecha_almacenamiento)) : '-'],
                ['F. Vencim.',  $oc->orden_compra_fecha_vencimiento
                    ? date('d/m/Y', strtotime($oc->orden_compra_fecha_vencimiento)) : '-'],
                ['Sucursal',    utf8_decode($oc->sucursal_nombre ?? '-')],
                ['Solicitante', utf8_decode($nombreCompleto)],
            ];

            $ocBoxH = count($datosOC) * $rowH + 4;
            $pdf->SetFillColor($gR, $gG, $gB);
            $pdf->SetDrawColor($bR, $bG, $bB);
            $pdf->Rect($xR, $y0, $cR, $ocBoxH, 'DF');

            $yOC = $y0 + 2;
            foreach ($datosOC as [$lbl, $val]) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->SetXY($xR + 2, $yOC);
                $pdf->Cell(22, $rowH, utf8_decode($lbl) . ':', 0, 0, 'L');
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell($cR - 26, $rowH, $val, 0, 1, 'L');
                $yOC += $rowH;
            }

            // ════════════════════════════════════════════════════
            // BLOQUE: PROVEEDOR (izq) | DATOS DE COMPRA (der)
            // ════════════════════════════════════════════════════
            $y1 = max($yEmp, $y0 + $ocBoxH) + 4;

            // ── Proveedor ─────────────────────────────────────────
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetTextColor($nR, $nG, $nB);
            $pdf->SetXY(12, $y1);
            $pdf->Cell($cL, 5, 'PROVEEDOR', 0, 1, 'L');
            $pdf->SetDrawColor($nR, $nG, $nB);
            $pdf->Line(12, $pdf->GetY(), 12 + $cL, $pdf->GetY());

            $yProv = $pdf->GetY() + 1;
            $datosProv = [
                ['Nombre',      utf8_decode($oc->proveedores_nombre ?? '-')],
                ['RUC / Doc',   $oc->proveedores_numero_documento ?? '-'],
                ['Tipo comp.',  utf8_decode($oc->orden_compra_tipo_doc ?? '-')],
                ['N° Comp.',    $oc->orden_compra_numero_doc ?? '-'],
            ];
            if (!empty($oc->orden_compra_guia_remitente)) {
                $datosProv[] = ['Guia Rem.',   $oc->orden_compra_guia_remitente];
            }
            if (!empty($oc->orden_compra_guia_transportista)) {
                $datosProv[] = ['Guia Trans.', $oc->orden_compra_guia_transportista];
            }
            if (!empty($transportistasNombres)) {
                $datosProv[] = ['Transportistas', utf8_decode(implode(', ', $transportistasNombres))];
            }
            foreach ($datosProv as [$lbl, $val]) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->SetXY(12, $yProv);
                $pdf->Cell(25, $rowH, utf8_decode($lbl) . ':', 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->MultiAlignCell($cL - 25, $rowH, $val, 0, 1, 'L');
                $yProv += $rowH;
            }

            // ── Datos de compra (derecha) ─────────────────────────
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetTextColor($nR, $nG, $nB);
            $pdf->SetXY($xR, $y1);
            $pdf->Cell($cR, 5, 'DATOS DE COMPRA', 0, 1, 'L');
            $pdf->SetDrawColor($nR, $nG, $nB);
            $pdf->Line($xR, $y1 + 5, $xR + $cR, $y1 + 5);

            $yComp = $y1 + 6;
            $datosComp = [
                ['Forma de pago', utf8_decode($oc->tipo_pago_nombre ?? '-')],
            ];
            if ($descuentoMonto > 0) {
                $datosComp[] = ['Descuento (' . number_format($descuentoPct, 2) . '%)', $sym . ' ' . number_format($descuentoMonto, 2)];
            }
            if ($igvMonto > 0) {
                $datosComp[] = ['IGV (' . number_format($igvPct, 2) . '%)', $sym . ' ' . number_format($igvMonto, 2)];
            }
            if ($percepcionMonto > 0) {
                $datosComp[] = ['Percepcion (' . number_format($percepcionPct, 2) . '%)', $sym . ' ' . number_format($percepcionMonto, 2)];
            }
            if ($flete > 0) {
                $datosComp[] = ['Flete', $sym . ' ' . number_format($flete, 2)];
            }
            if ($gastos > 0) {
                $datosComp[] = ['Gastos Op.', $sym . ' ' . number_format($gastos, 2)];
            }
            foreach ($datosComp as [$lbl, $val]) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->SetXY($xR, $yComp);
                $pdf->Cell(28, $rowH, utf8_decode($lbl) . ':', 0, 0, 'L');
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell($cR - 28, $rowH, $val, 0, 1, 'L');
                $yComp += $rowH;
            }

            // ════════════════════════════════════════════════════
            // TABLA DE ITEMS
            // # | Producto | Código | Presentación | C×Unid | Cant | P.Compra | Total = 186
            // ════════════════════════════════════════════════════
            $yTable = max($yProv, $yComp) + 6;
            $pdf->SetY($yTable);

            $cW = [7, 52, 20, 30, 13, 14, 24, 26];
            $cH = ['#', utf8_decode('PRODUCTO / DESCRIPCION'), utf8_decode('CODIGO'), utf8_decode('PRESENTACION'), 'CxUNID', 'CANT.', 'P. COMPRA', 'TOTAL'];
            $cA = ['C', 'L', 'C', 'C', 'C', 'C', 'R', 'R'];

            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 6.5);
            foreach ($cH as $i => $h) {
                $pdf->Cell($cW[$i], 7, $h, 0, 0, $cA[$i], true);
            }
            $pdf->Ln();

            $pdf->SetDrawColor($bR, $bG, $bB);
            $pdf->SetTextColor(30, 41, 59);
            foreach ($detalle as $idx => $item) {
                $pdf->CheckPageBreak(7);
                $fill = ($idx % 2 === 0);
                $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell($cW[0], 6, $idx + 1, 'B', 0, 'C', $fill);
                $pdf->Cell($cW[1], 6, utf8_decode(mb_substr($item->detalle_orden_nombre_producto ?? '', 0, 40)), 'B', 0, 'L', $fill);
                $pdf->Cell($cW[2], 6, $item->pro_codigo ?? '', 'B', 0, 'C', $fill);
                $pdf->Cell($cW[3], 6, utf8_decode(mb_substr($item->presentacion ?? '-', 0, 18)), 'B', 0, 'C', $fill);
                $pdf->Cell($cW[4], 6, $item->cantidad_x_unidad ? number_format((float)$item->cantidad_x_unidad, 2) : '-', 'B', 0, 'C', $fill);
                $pdf->Cell($cW[5], 6, number_format((float)$item->detalle_compra_cantidad, 2), 'B', 0, 'C', $fill);
                $pdf->Cell($cW[6], 6, $sym . ' ' . number_format((float)$item->detalle_compra_precio_compra, 2), 'B', 0, 'R', $fill);
                $pdf->Cell($cW[7], 6, $sym . ' ' . number_format((float)$item->detalle_compra_total_pedido, 2), 'B', 0, 'R', $fill);
                $pdf->Ln();
            }

            // ════════════════════════════════════════════════════
            // TOTALES
            // ════════════════════════════════════════════════════
            $pdf->Ln(4);
            $wLbl = 64;
            $wVal = 34;
            $xTot = 12 + $W - $wLbl - $wVal;

            $lineasTot = [
                ['Subtotal (' . $detalle->count() . ' items)', $sym . ' ' . number_format($subtotal, 2)],
            ];
            if ($descuentoMonto > 0) {
                $lineasTot[] = ['Descuento (' . number_format($descuentoPct, 2) . '%)', '- ' . $sym . ' ' . number_format($descuentoMonto, 2)];
                $lineasTot[] = ['Subtotal Neto', $sym . ' ' . number_format($subtotalNeto, 2)];
            }
            if ($igvMonto > 0) {
                $lineasTot[] = ['IGV (' . number_format($igvPct, 2) . '%)', $sym . ' ' . number_format($igvMonto, 2)];
            }
            if ($percepcionMonto > 0) {
                $lineasTot[] = ['Percepcion IGV (' . number_format($percepcionPct, 2) . '%)', $sym . ' ' . number_format($percepcionMonto, 2)];
            }
            if ($flete > 0) {
                $lineasTot[] = ['Flete', $sym . ' ' . number_format($flete, 2)];
            }
            if ($gastos > 0) {
                $lineasTot[] = ['Gastos operativos', $sym . ' ' . number_format($gastos, 2)];
            }

            $pdf->SetDrawColor($bR, $bG, $bB);
            foreach ($lineasTot as [$lbl, $val]) {
                $pdf->SetXY($xTot, $pdf->GetY());
                $pdf->SetFillColor($gR, $gG, $gB);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->Cell($wLbl, 6, utf8_decode($lbl), 'B', 0, 'R', true);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell($wVal, 6, $val, 'B', 1, 'R', true);
            }

            // Línea total (navy)
            $pdf->SetXY($xTot, $pdf->GetY());
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell($wLbl, 8, 'TOTAL ' . $moneda, 0, 0, 'R', true);
            $pdf->Cell($wVal, 8, $sym . ' ' . number_format($total, 2), 0, 1, 'R', true);

            // ════════════════════════════════════════════════════
            // OBSERVACIÓN
            // ════════════════════════════════════════════════════
            if (!empty($oc->orden_compra_observacion)) {
                $pdf->Ln(5);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor($nR, $nG, $nB);
                $pdf->SetXY(12, $pdf->GetY());
                $pdf->Cell($W, 5, utf8_decode('OBSERVACION'), 0, 1, 'L');
                $pdf->SetDrawColor($nR, $nG, $nB);
                $pdf->Line(12, $pdf->GetY(), 12 + $W, $pdf->GetY());
                $pdf->Ln(1);
                $pdf->SetFillColor($gR, $gG, $gB);
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetX(12);
                $pdf->MultiCell($W, 5, utf8_decode($oc->orden_compra_observacion), 0, 'L', true);
            }

            // ════════════════════════════════════════════════════
            // FOOTER
            // ════════════════════════════════════════════════════
            $pdf->SetAutoPageBreak(false);
            $pdf->SetY(-16);
            $pdf->SetDrawColor($nR, $nG, $nB);
            $pdf->Line(12, $pdf->GetY(), 12 + $W, $pdf->GetY());
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor(130, 130, 130);
            $pdf->SetXY(12, $pdf->GetY() + 2);
            $pdf->Cell($W / 2, 5, utf8_decode('Generado el ') . date('d/m/Y H:i'), 0, 0, 'L');
            $pdf->Cell($W / 2, 5, utf8_decode('Pagina ') . $pdf->PageNo() . ' de {nb}', 0, 0, 'R');

            $pdf->Output('I', 'Compra-' . $oc->orden_compra_numero . '.pdf');
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert('Error al generar el PDF.');
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }








    // =====================================================================
    //  KARDEX VALORIZADO
    // =====================================================================

    public function kardex_valorizado()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("kardex_valorizado");
            return view("logistica/kardex_valorizado", compact("opciones"));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert(\"Error Al Mostrar Contenido.\"); window.location.href = '" . route("admin") . "';</script>";
        }
    }

    public function gastos()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("gastos");
            return view('logistica/gastos', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function autoconsumo()
    {
        try {
            $opciones = $this->submenu->optiones_por_vista("autoconsumo");
            return view('logistica/autoconsumo', compact('opciones'));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>
                alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");
                window.location.href = '" . route('admin') . "';
            </script>";
        }
    }

    public function autoconsumo_pdf(\Illuminate\Http\Request $request)
    {
        $id = (int) ($request->id ?? 0);
        if (!$id) abort(404);

        $ac = DB::table('autoconsumo as a')
            ->leftJoin('almacen as alm',   'alm.id_almacen', '=', 'a.id_almacen')
            ->leftJoin('tiendas as t',     't.id_tienda',    '=', 'a.id_tienda')
            ->leftJoin('empresa as eal',   'eal.id_empresa', '=', 'alm.id_empresa')
            ->leftJoin('empresa as et',    'et.id_empresa',  '=', 't.id_empresa')
            ->leftJoin('users as u',       'u.id_users',     '=', 'a.id_users')
            ->where('a.id_autoconsumo', $id)
            ->select(
                'a.*',
                DB::raw("COALESCE(alm.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                DB::raw("COALESCE(eal.id_empresa, et.id_empresa) as id_empresa_ac"),
                'u.nombre_users',
            )
            ->first();

        if (!$ac) abort(404);

        $emp = $ac->id_empresa_ac
            ? DB::table('empresa as e')
                ->leftJoin('ubigeo as ub', 'ub.id_ubigeo', '=', 'e.id_ubigeo')
                ->where('e.id_empresa', $ac->id_empresa_ac)
                ->select('e.*', 'ub.ubigeo_distrito', 'ub.ubigeo_provincia', 'ub.ubigeo_departamento')
                ->first()
            : null;

        $detalle = DB::table('autoconsumo_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_autoconsumo', $id)
            ->select('p.pro_nombre', 'p.pro_codigo', 'd.detalle_cantidad', 'd.detalle_costo')
            ->get();

        $pdf = new \App\Models\PDFBufeo('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $xL = 15;
        $W  = 180;

        // ── CABECERA ────────────────────────────────────────────────
        // Logo
        $logoW = 0;
        if ($emp && !empty($emp->empresa_foto)) {
            $logoPath = public_path($emp->empresa_foto);
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, $xL, 15, 30, 0);
                $logoW = 34;
            }
        }

        // Datos empresa (izquierda-centro)
        $infoX = $xL + $logoW;
        $rightW = 58;
        $infoW  = $W - $logoW - $rightW - 4;

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY($infoX, 15);
        $pdf->Cell($infoW, 6, utf8_decode($emp->empresa_razon_social ?? ''), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY($infoX, $pdf->GetY());
        $pdf->Cell($infoW, 5, utf8_decode($emp->empresa_domiciliofiscal ?? ''), 0, 1, 'C');
        $cityParts = array_filter([
            $emp->ubigeo_departamento ?? '',
            $emp->ubigeo_provincia    ?? '',
            $emp->ubigeo_distrito     ?? '',
        ]);
        if ($cityParts) {
            $pdf->SetXY($infoX, $pdf->GetY());
            $pdf->Cell($infoW, 5, utf8_decode(implode(' - ', $cityParts)), 0, 1, 'C');
        }

        // Caja derecha: RUC | AUTOCONSUMO | Número
        $xRight = $xL + $W - $rightW;
        $yTop   = 15;
        $rowH   = 10;
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($xRight, $yTop, $rightW, $rowH * 3);
        $pdf->Line($xRight, $yTop + $rowH,     $xRight + $rightW, $yTop + $rowH);
        $pdf->Line($xRight, $yTop + $rowH * 2, $xRight + $rightW, $yTop + $rowH * 2);
        $pdf->SetLineWidth(0.2);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY($xRight, $yTop + 2);
        $pdf->Cell($rightW, 6, 'R.U.C.: ' . ($emp->empresa_ruc ?? ''), 0, 1, 'C');

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($xRight, $yTop + $rowH + 2);
        $pdf->Cell($rightW, 6, 'AUTOCONSUMO', 0, 1, 'C');

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($xRight, $yTop + $rowH * 2 + 2);
        $pdf->Cell($rightW, 6, $ac->autoconsumo_numero ?? '', 0, 1, 'C');

        // ── LÍNEA SEPARADORA ────────────────────────────────────────
        $pdf->SetY(max($pdf->GetY(), $yTop + $rowH * 3) + 4);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($xL, $pdf->GetY(), $xL + $W, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(3);

        // ── DATOS GENERALES ─────────────────────────────────────────
        $lbl = 28;
        $val = $W - $lbl;
        $lineH = 5;

        $fecha = $ac->autoconsumo_fecha
            ? date('d-m-Y', strtotime($ac->autoconsumo_fecha))
            : date('d-m-Y');
        $hora  = $ac->created_at
            ? date('H:i:s', strtotime($ac->created_at))
            : '';

        $campos = [
            ['Fecha',        $fecha . ($hora ? '  ' . $hora : '')],
            ['Autorizado',   $ac->autoconsumo_autorizacion ?? ''],
            ['Por',          ''],
            ['Cargo',        utf8_decode($ac->nombre_users ?? '')],
            ['(*)',          ''],
            ['Beneficiario', mb_strtoupper($ac->autoconsumo_area ?? '')],
        ];

        foreach ($campos as [$k, $v]) {
            $pdf->SetX($xL);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell($lbl, $lineH, utf8_decode($k) . '  :', 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell($val, $lineH, utf8_decode($v), 0, 1, 'L');
        }

        $pdf->Ln(3);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($xL, $pdf->GetY(), $xL + $W, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Ln(3);

        // ── TABLA PRODUCTOS ─────────────────────────────────────────
        $cCant  = 18; $cCod  = 28; $cDesc = 88; $cCU = 23; $cCT = 23;
        $pdf->SetFillColor(210, 210, 210);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetX($xL);
        $pdf->Cell($cCant, 7, 'Cant.',       1, 0, 'C', true);
        $pdf->Cell($cCod,  7, utf8_decode('Código'), 1, 0, 'C', true);
        $pdf->Cell($cDesc, 7, utf8_decode('Descripción'), 1, 0, 'C', true);
        $pdf->Cell($cCU,   7, 'C.U.',        1, 0, 'C', true);
        $pdf->Cell($cCT,   7, 'C.T.',        1, 1, 'C', true);

        $totalGeneral = 0;
        $pdf->SetFont('Helvetica', '', 8);
        foreach ($detalle as $d) {
            $ct = (float)$d->detalle_cantidad * (float)$d->detalle_costo;
            $totalGeneral += $ct;
            $pdf->SetX($xL);
            $pdf->Cell($cCant, 6, number_format((float)$d->detalle_cantidad, 2), 1, 0, 'C');
            $pdf->Cell($cCod,  6, $d->pro_codigo ?? '', 1, 0, 'C');
            $pdf->Cell($cDesc, 6, utf8_decode(mb_substr($d->pro_nombre ?? '', 0, 55)), 1, 0, 'L');
            $pdf->Cell($cCU,   6, number_format((float)$d->detalle_costo, 3), 1, 0, 'R');
            $pdf->Cell($cCT,   6, number_format($ct, 3), 1, 1, 'R');
        }

        // Fila total
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetX($xL);
        $pdf->Cell($cCant + $cCod + $cDesc + $cCU, 6, '', 'LB', 0, 'R');
        $pdf->Cell($cCT, 6, number_format($totalGeneral, 2), 'RB', 1, 'R');

        // ── FIRMAS ──────────────────────────────────────────────────
        $pdf->Ln(14);
        $sigW = $W / 3;
        $yFirma = $pdf->GetY();

        $firmas = [
            'FIRMA DE RECEPCION',
            'GERENTE',
            utf8_decode('RESPONSABLE DE DESPACHO'),
        ];
        foreach ($firmas as $i => $firma) {
            $xF = $xL + $i * $sigW;
            $pdf->SetDrawColor(130, 130, 130);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($xF + 5, $yFirma, $xF + $sigW - 5, $yFirma);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetXY($xF, $yFirma + 1);
            $pdf->Cell($sigW, 4, $firma, 0, 0, 'C');
        }
        $pdf->SetXY($xL + 2 * $sigW, $yFirma + 5);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($sigW, 4, utf8_decode('Nombre y Apellido:'), 0, 1, 'L');

        $filename = 'Autoconsumo-' . ($ac->autoconsumo_numero ?? $id) . '.pdf';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $pdf->Output('S', $filename);
        exit;
    }

    public function autoconsumo_ticket(\Illuminate\Http\Request $request)
    {
        $id = (int) ($request->id ?? 0);
        if (!$id) abort(404);

        $ac = DB::table('autoconsumo as a')
            ->leftJoin('almacen as alm', 'alm.id_almacen', '=', 'a.id_almacen')
            ->leftJoin('tiendas as t',   't.id_tienda',    '=', 'a.id_tienda')
            ->leftJoin('empresa as eal', 'eal.id_empresa', '=', 'alm.id_empresa')
            ->leftJoin('empresa as et',  'et.id_empresa',  '=', 't.id_empresa')
            ->leftJoin('users as u',     'u.id_users',     '=', 'a.id_users')
            ->where('a.id_autoconsumo', $id)
            ->select(
                'a.*',
                DB::raw("COALESCE(alm.almacen_nombre, t.tienda_nombre, '—') as ubicacion_nombre"),
                DB::raw("COALESCE(eal.id_empresa, et.id_empresa) as id_empresa_ac"),
                'u.nombre_users',
            )
            ->first();

        if (!$ac) abort(404);

        $emp = $ac->id_empresa_ac
            ? DB::table('empresa as e')
                ->leftJoin('ubigeo as ub', 'ub.id_ubigeo', '=', 'e.id_ubigeo')
                ->where('e.id_empresa', $ac->id_empresa_ac)
                ->select('e.*', 'ub.ubigeo_distrito', 'ub.ubigeo_provincia', 'ub.ubigeo_departamento')
                ->first()
            : null;

        $detalle = DB::table('autoconsumo_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_autoconsumo', $id)
            ->select('p.pro_nombre', 'p.pro_codigo', 'd.detalle_cantidad', 'd.detalle_costo')
            ->get();

        // ── Altura dinámica ────────────────────────────────────────
        $filas    = count($detalle);
        $altBase  = 210;
        $altTotal = $altBase + (10 * max(0, $filas - 1));

        $pdf = new \App\Models\PDFBufeo('P', 'mm', [80, $altTotal]);
        $pdf->SetMargins(5, 4, 5);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $cw = 70;

        // ══ LOGO ══════════════════════════════════════════════════
        if ($emp && !empty($emp->empresa_foto)) {
            $logoPath = public_path($emp->empresa_foto);
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 27, 4, 26, 0);
                $pdf->Ln(16);
            } else {
                $pdf->Ln(4);
            }
        } else {
            $pdf->Ln(4);
        }

        // ══ CABECERA EMPRESA ══════════════════════════════════════
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 5, utf8_decode($emp->empresa_razon_social ?? ''), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cw, 3.5, 'RUC: ' . ($emp->empresa_ruc ?? ''), 0, 1, 'C');
        $pdf->MultiCell($cw, 3.5, utf8_decode($emp->empresa_domiciliofiscal ?? ''), 0, 'C');
        $cityParts = array_filter([
            $emp->ubigeo_departamento ?? '',
            $emp->ubigeo_provincia    ?? '',
            $emp->ubigeo_distrito     ?? '',
        ]);
        if ($cityParts) {
            $pdf->Cell($cw, 3.5, utf8_decode(implode(' - ', $cityParts)), 0, 1, 'C');
        }

        // ══ LÍNEA GRUESA ══════════════════════════════════════════
        $pdf->Ln(2);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(2);

        // ══ TIPO DOCUMENTO ════════════════════════════════════════
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell($cw, 6, 'AUTOCONSUMO', 0, 1, 'C', true);
        $pdf->Cell($cw, 5, $ac->autoconsumo_numero ?? '', 0, 1, 'C');
        $pdf->SetFillColor(255, 255, 255);

        // ══ LÍNEA FINA ════════════════════════════════════════════
        $pdf->Ln(1);
        $pdf->SetDrawColor(170, 170, 170);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);

        // ══ DATOS ═════════════════════════════════════════════════
        $fecha = $ac->autoconsumo_fecha ? date('d/m/Y', strtotime($ac->autoconsumo_fecha)) : '';
        $hora  = $ac->created_at        ? date('H:i:s', strtotime($ac->created_at))        : '';

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(35, 4, 'Fecha: ' . $fecha, 0, 0, 'L');
        $pdf->Cell(35, 4, 'Hora: '  . $hora,  0, 1, 'R');

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Write(4, utf8_decode('Autorizado: '));
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Write(4, utf8_decode($ac->autoconsumo_autorizacion ?? ''));
        $pdf->Ln(4);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Write(4, 'Cargo: ');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Write(4, utf8_decode($ac->nombre_users ?? ''));
        $pdf->Ln(4);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Write(4, utf8_decode('Área: '));
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Write(4, utf8_decode(mb_strtoupper($ac->autoconsumo_area ?? '')));
        $pdf->Ln(4);

        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Write(4, utf8_decode('Ubicación: '));
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Write(4, utf8_decode($ac->ubicacion_nombre ?? ''));
        $pdf->Ln(4);

        // ══ LÍNEA GRUESA ══════════════════════════════════════════
        $pdf->Ln(1.5);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(1.5);

        // ══ ENCABEZADO PRODUCTOS ══════════════════════════════════
        $pdf->SetFillColor(50, 50, 50);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(25, 5, utf8_decode('Descripción'), 0, 0, 'L', true);
        $pdf->Cell(9,  5, 'Cant.',  0, 0, 'C', true);
        $pdf->Cell(18, 5, 'C.Unit', 0, 0, 'R', true);
        $pdf->Cell(18, 5, 'C.Tot.', 0, 1, 'R', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        // ══ DETALLE ═══════════════════════════════════════════════
        $totalGeneral = 0;
        $pdf->SetFont('Helvetica', '', 7);
        foreach ($detalle as $d) {
            $ct = (float)$d->detalle_cantidad * (float)$d->detalle_costo;
            $totalGeneral += $ct;

            $yAntes = $pdf->GetY();
            $pdf->MultiCell(25, 4, utf8_decode(mb_substr($d->pro_nombre ?? '', 0, 30)), 0, 'L');
            $yDespues = $pdf->GetY();

            $pdf->SetXY(5 + 25, $yAntes);
            $pdf->Cell(9,  4, number_format((float)$d->detalle_cantidad, 2), 0, 0, 'C');
            $pdf->Cell(18, 4, number_format((float)$d->detalle_costo, 3),    0, 0, 'R');
            $pdf->Cell(18, 4, number_format($ct, 3),                         0, 0, 'R');
            $pdf->SetXY(5, $yDespues);
        }

        // ══ SEPARADOR + TOTAL ════════════════════════════════════
        $pdf->Ln(1.5);
        $pdf->SetDrawColor(100, 100, 100);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetDrawColor(60, 60, 60);
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($cw, 6, 'TOTAL:  ' . number_format($totalGeneral, 2), 0, 1, 'R');
        $pdf->SetLineWidth(0.4);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(2);

        // ══ FIRMAS ════════════════════════════════════════════════
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetDrawColor(130, 130, 130);
        foreach (['FIRMA RECEPCION', 'GERENTE', 'RESP. DESPACHO'] as $label) {
            $pdf->Ln(18);
            $yF = $pdf->GetY();
            $pdf->Line(20, $yF, 60, $yF);
            $pdf->Ln(1.5);
            $pdf->Cell($cw, 3.5, $label, 0, 1, 'C');
        }
        $pdf->SetDrawColor(60, 60, 60);

        $filename = 'Ticket-Autoconsumo-' . ($ac->autoconsumo_numero ?? $id) . '.pdf';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $pdf->Output('S', $filename);
        exit;
    }

    public function kardex_valorizado_pdf(\Illuminate\Http\Request $request)
    {
        try {
            $idPro      = (int) $request->id_pro;
            $desde      = $request->desde ?? null;
            $hasta      = $request->hasta ?? null;
            $idEmpresa  = $request->id_empresa  ? (int) $request->id_empresa  : null;
            $idSucursal = $request->id_sucursal ? (int) $request->id_sucursal : null;
            $idAlmacen  = $request->id_almacen  ? (int) $request->id_almacen  : null;
            $idFamilia  = $request->id_familia  ? (int) $request->id_familia  : null;
            $tipoKardex = $request->tipo ?? 'valorizado';

            // Resolver empresa solo para cabecera (sin tocar $idEmpresa que usa calcularKardex)
            $idEmpresaHeader = $idEmpresa;
            if (!$idEmpresaHeader && $idAlmacen > 0) {
                $idEmpresaHeader = \Illuminate\Support\Facades\DB::table("almacen")->where("id_almacen", $idAlmacen)->value("id_empresa") ?: null;
            }

            $empresa       = $idEmpresaHeader ? \Illuminate\Support\Facades\DB::table("empresa")->where("id_empresa", $idEmpresaHeader)->first() : null;
            $tienda        = $idSucursal > 0 ? \Illuminate\Support\Facades\DB::table("tiendas")->where("id_tienda", $idSucursal)->first() : null;
            $almacenRow    = $idAlmacen  > 0 ? \Illuminate\Support\Facades\DB::table("almacen")->where("id_almacen", $idAlmacen)->first() : null;
            $empresaNombre = $empresa ? ($empresa->empresa_razon_social ?? $empresa->empresa_nombrecomercial ?? '') : '';
            $empresaRuc    = $empresa ? ($empresa->empresa_ruc ?? '') : '';
            $sedeNombre    = $almacenRow ? ($almacenRow->almacen_nombre ?? '')
                           : ($tienda    ? ($tienda->tienda_nombre    ?? '') : '');

            // ── Resumido: ruta separada ────────────────────────────────
            if ($tipoKardex === 'resumido') {
                $grupos = $this->calcularKardexResumido($idFamilia, $idPro ?: null, $desde, $hasta, $idEmpresa, $idSucursal, $idAlmacen);
                [$nR, $nG, $nB] = [30, 58, 95];
                [$gR, $gG, $gB] = [245, 247, 250];

                $pdf = new \App\Models\PDFBufeo("L", "mm", "A4");
                $pdf->SetMargins(8, 8, 8);
                $pdf->SetAutoPageBreak(true, 12);
                $pdf->AddPage();
                $pdf->AliasNbPages();
                $W = 281;

                $pdf->SetFillColor($nR, $nG, $nB);
                $pdf->Rect(8, 8, $W, 14, "F");
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont("Helvetica", "B", 12);
                $pdf->SetXY(8, 9);
                $pdf->Cell($W, 6, utf8_decode("KARDEX RESUMIDO"), 0, 1, "C");
                $pdf->SetFont("Helvetica", "", 7);
                $pdf->SetXY(8, 17);
                $pdf->Cell($W, 3.5, utf8_decode("Registro de Inventario Permanente Valorizado (Resumen)"), 0, 1, "C");

                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetFillColor(225, 232, 245);
                $pdf->Rect(8, 23, $W, 4.5, "F");
                $pdf->SetFont("Helvetica", "B", 7);
                $pdf->SetXY(8, 24);
                $pdf->Cell($W, 3.5, utf8_decode("REGISTRO DE INVENTARIO PERMANENTE VALORIZADO (RESUMEN)"), 0, 1, "C");

                $periodoLabel = ($desde && $hasta)
                    ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                    : "Sin filtro de fecha";
                $camposPdf = [
                    ["PERIODO:",                   $periodoLabel],
                    [utf8_decode("NOMBRE Y/O RAZÓN SOCIAL:"), utf8_decode($empresaNombre)],
                    ["RUC:",                       $empresaRuc],
                    ["ESTABLECIMIENTO:",           utf8_decode($sedeNombre)],
                    ["TIPO (TABLA 5):",            "01 MERCADERIA"],
                    [utf8_decode("MÉTODO:"),       utf8_decode("PROMEDIO PONDERADO MÓVIL")],
                ];
                $yInfo = 28;
                foreach ($camposPdf as $i => [$lbl, $val]) {
                    $pdf->SetFillColor($i % 2 === 0 ? 250 : 243, $i % 2 === 0 ? 252 : 247, $i % 2 === 0 ? 255 : 252);
                    $pdf->Rect(8, $yInfo, $W, 3.5, "F");
                    $pdf->SetXY(8, $yInfo + 0.3);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell(52, 3, $lbl, 0, 0, "L");
                    $pdf->SetFont("Helvetica", "", 6);
                    $pdf->Cell($W - 52, 3, $val, 0, 1, "L");
                    $yInfo += 3.5;
                }
                $pdf->SetY($yInfo + 2);

                // Anchos: 22+100+26+24+24+28+20+37 = 281
                [$wCod,$wNom,$wSI,$wIC,$wEC,$wSF,$wCU,$wCT] = [22,100,26,24,24,28,20,37];
                $pdf->SetFillColor($nR, $nG, $nB);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont("Helvetica", "B", 6);
                foreach ([
                    [utf8_decode("COD. EXISTENCIA"), $wCod],
                    ["PRODUCTO", $wNom],
                    ["SALDO INICIAL", $wSI],
                    ["INGRESOS CANT.", $wIC],
                    ["EGRESOS CANT.", $wEC],
                    ["SALDO FINAL", $wSF],
                    ["C.U.", $wCU],
                    ["C.T.", $wCT],
                ] as [$h, $w]) {
                    $pdf->Cell($w, 5, $h, 0, 0, "C", true);
                }
                $pdf->Ln();

                $totalGeneral = 0.0;
                foreach ($grupos as $grupo) {
                    $pdf->CheckPageBreak(5);
                    $pdf->SetFillColor(200, 215, 245);
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell($W, 5, utf8_decode("MARCA: " . $grupo['familia']), "B", 1, "L", true);

                    $pdf->SetFont("Helvetica", "", 6);
                    foreach ($grupo['productos'] as $idx => $prod) {
                        $pdf->CheckPageBreak(5);
                        $fill = ($idx % 2 === 0);
                        $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                        $pdf->SetTextColor(30, 41, 59);
                        $pdf->Cell($wCod, 5, utf8_decode(mb_substr($prod['codigo'], 0, 10)), "B", 0, "C", $fill);
                        $pdf->Cell($wNom, 5, utf8_decode(mb_substr($prod['nombre'], 0, 60)), "B", 0, "L", $fill);
                        $pdf->Cell($wSI,  5, number_format($prod['saldo_ini_cant'], 2),   "B", 0, "R", $fill);
                        $pdf->Cell($wIC,  5, number_format($prod['ingresos_cant'], 2),     "B", 0, "R", $fill);
                        $pdf->Cell($wEC,  5, number_format($prod['egresos_cant'], 2),      "B", 0, "R", $fill);
                        $pdf->Cell($wSF,  5, number_format($prod['saldo_final_cant'], 2),  "B", 0, "R", $fill);
                        $pdf->Cell($wCU,  5, number_format($prod['c_u'], 4),               "B", 0, "R", $fill);
                        $pdf->Cell($wCT,  5, "S/ " . number_format($prod['c_t'], 2),       "B", 1, "R", $fill);
                    }

                    $pdf->CheckPageBreak(5);
                    $pdf->SetFillColor($nR, $nG, $nB);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell($wCod+$wNom+$wSI+$wIC+$wEC+$wSF+$wCU, 5, "TOTALES:", 0, 0, "R", true);
                    $pdf->Cell($wCT, 5, "S/ " . number_format($grupo['total_ct'], 2), 0, 1, "R", true);

                    $totalGeneral += $grupo['total_ct'];
                }

                $pdf->CheckPageBreak(5);
                $pdf->SetFillColor(13, 27, 42);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont("Helvetica", "B", 7);
                $pdf->Cell($wCod+$wNom+$wSI+$wIC+$wEC+$wSF+$wCU, 6, "TOTAL GENERAL:", 0, 0, "R", true);
                $pdf->Cell($wCT, 6, "S/ " . number_format($totalGeneral, 2), 0, 1, "R", true);

                $pdf->SetAutoPageBreak(false);
                $pdf->SetY(-10); $pdf->SetFont("Helvetica", "", 6); $pdf->SetTextColor(130,130,130);
                $pdf->SetX(8);
                $pdf->Cell($W/2, 4, utf8_decode("Generado el ") . date("d/m/Y H:i"), 0, 0, "L");
                $pdf->Cell($W/2, 4, utf8_decode("Pagina ") . $pdf->PageNo() . " de {nb}", 0, 0, "R");

                $pdf->Output("I", "KardexResumido-" . date("Ymd") . ".pdf");
                exit;
            }

            // ── Multi-producto: fisico/valorizado sin producto, por familia ──
            if ($idPro === 0 && $idFamilia > 0) {
                $productos = \Illuminate\Support\Facades\DB::table('productos as p')
                    ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                    ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                    ->where('f.id_fa', $idFamilia)
                    ->where('p.pro_estado', 1)
                    ->orderBy('p.pro_nombre')
                    ->get(['p.id_pro', 'p.pro_nombre', 'p.pro_codigo']);

                $familiaNombre = \Illuminate\Support\Facades\DB::table('familias')
                    ->where('id_fa', $idFamilia)->value('fa_nombre') ?? '';

                [$nR, $nG, $nB] = [30, 58, 95];
                [$gR, $gG, $gB] = [245, 247, 250];

                $pdf = new \App\Models\PDFBufeo("L", "mm", "A4");
                $pdf->SetMargins(8, 8, 8);
                $pdf->SetAutoPageBreak(true, 12);
                $pdf->AddPage();
                $pdf->AliasNbPages();
                $W = 281;

                $pdf->SetFillColor($nR, $nG, $nB);
                $pdf->Rect(8, 8, $W, 14, "F");
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont("Helvetica", "B", 12);
                $pdf->SetXY(8, 9);
                $tituloKardex = $tipoKardex === 'fisico' ? "KARDEX FISICO" : "KARDEX VALORIZADO";
                $pdf->Cell($W, 6, utf8_decode($tituloKardex), 0, 1, "C");
                $pdf->SetFont("Helvetica", "", 7);
                $pdf->SetXY(8, 17);
                $pdf->Cell($W, 3.5, utf8_decode("FAMILIA / MARCA: " . $familiaNombre), 0, 1, "C");

                $sunatTitulo = $tipoKardex === 'fisico'
                    ? utf8_decode("REGISTRO DEL INVENTARIO PERMANENTE EN UNIDADES FÍSICAS")
                    : utf8_decode("REGISTRO DEL INVENTARIO PERMANENTE VALORIZADO");
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetFillColor(225, 232, 245);
                $pdf->Rect(8, 23, $W, 4.5, "F");
                $pdf->SetFont("Helvetica", "B", 7);
                $pdf->SetXY(8, 24);
                $pdf->Cell($W, 3.5, $sunatTitulo, 0, 1, "C");

                $periodoLabel = ($desde && $hasta)
                    ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                    : "Sin filtro de fecha";
                $camposPdf = [
                    ["PERIODO:", $periodoLabel],
                    [utf8_decode("NOMBRE Y/O RAZÓN SOCIAL:"), utf8_decode($empresaNombre)],
                    ["RUC:", $empresaRuc],
                    ["ESTABLECIMIENTO:", utf8_decode($sedeNombre)],
                    ["TIPO (TABLA 5):", "01 MERCADERIA"],
                    ["COD. UND DE MED (TABLA 6):", "07 UND"],
                ];
                if ($tipoKardex === 'valorizado') {
                    $camposPdf[] = [utf8_decode("MÉTODO:"), utf8_decode("PROMEDIO PONDERADO MÓVIL")];
                }
                $yInfo = 28;
                foreach ($camposPdf as $i => [$lbl, $val]) {
                    $pdf->SetFillColor($i % 2 === 0 ? 250 : 243, $i % 2 === 0 ? 252 : 247, $i % 2 === 0 ? 255 : 252);
                    $pdf->Rect(8, $yInfo, $W, 3.5, "F");
                    $pdf->SetXY(8, $yInfo + 0.3);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell(52, 3, $lbl, 0, 0, "L");
                    $pdf->SetFont("Helvetica", "", 6);
                    $pdf->Cell($W - 52, 3, $val, 0, 1, "L");
                    $yInfo += 3.5;
                }
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetY($yInfo + 2);

                if ($tipoKardex === 'fisico') {
                    [$fF,$fD,$fS,$fN,$fMo,$fO,$fE,$fSa,$fSl] = [22,22,15,25,107,25,22,22,21];
                } else {
                    [$colFecha,$colTipo,$colMotivo,$colCant,$colCU,$colTotal,$colSCant,$colSValor]
                        = [20,22,62,16,20,22,16,22];
                }

                foreach ($productos as $prod) {
                    [$lineasP, $saldoInicialP, $totalesP] = $this->calcularKardex($prod->id_pro, $desde, $hasta, $idEmpresa, $idSucursal);
                    if ($saldoInicialP['cantidad'] == 0 && $saldoInicialP['valor'] == 0 && empty($lineasP)) continue;

                    $pdf->CheckPageBreak(10);
                    $pdf->SetFillColor(200, 215, 245);
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont("Helvetica", "B", 7);
                    $pdf->Cell($W, 6, utf8_decode($prod->pro_nombre . " [" . $prod->pro_codigo . "]"), "B", 1, "L", true);

                    if ($tipoKardex === 'fisico') {
                        $pdf->SetFont("Helvetica", "B", 6);
                        $pdf->SetFillColor($nR, $nG, $nB); $pdf->SetTextColor(255,255,255);
                        foreach ([["FECHA",$fF],[utf8_decode("T/DOC (TAB 10)"),$fD],["SERIE",$fS],[utf8_decode("NÚMERO"),$fN],[utf8_decode("CLIENTE Y/O PROVEEDOR"),$fMo],[utf8_decode("TIPO OP. (TAB 12)"),$fO],["ENTRADAS",$fE],["SALIDAS",$fSa],["SALDO FINAL",$fSl]] as [$h,$w]) { $pdf->Cell($w, 5, $h, 0, 0, "C", true); }
                        $pdf->Ln();
                        $pdf->SetFillColor(220,228,245); $pdf->SetTextColor(30,41,59); $pdf->SetFont("Helvetica","B",6);
                        $pdf->Cell($fF,5,"-","B",0,"C",true); $pdf->Cell($fD+$fS+$fN,5,utf8_decode("SALDO INICIAL AL ".($desde?date("d/m/Y",strtotime($desde)):"")),"B",0,"C",true);
                        $pdf->Cell($fMo,5,"","B",0,"L",true); $pdf->Cell($fO,5,"-","B",0,"C",true); $pdf->Cell($fE,5,"-","B",0,"C",true); $pdf->Cell($fSa,5,"-","B",0,"C",true);
                        $pdf->Cell($fSl,5,number_format($saldoInicialP["cantidad"],2),"B",1,"R",true);
                        $pdf->SetFont("Helvetica","",6);
                        foreach ($lineasP as $idx => $ln) {
                            $pdf->CheckPageBreak(5); $fill=($idx%2===0);
                            $pdf->SetFillColor($fill?$gR:255,$fill?$gG:255,$fill?$gB:255); $pdf->SetTextColor(30,41,59);
                            $pdf->Cell($fF,5,date("d/m/Y",strtotime($ln["fecha"])),"B",0,"C",$fill);
                            $pdf->Cell($fD,5,$ln["tdoc"]??"00","B",0,"C",$fill); $pdf->Cell($fS,5,"-","B",0,"C",$fill);
                            $pdf->Cell($fN,5,(string)($ln["id_referencia"]??$ln["id_movimiento"]),"B",0,"C",$fill);
                            $pdf->Cell($fMo,5,utf8_decode(mb_substr($ln["motivo"]??"",0,58)),"B",0,"L",$fill);
                            $pdf->Cell($fO,5,$ln["tipo_op"]??"99","B",0,"C",$fill);
                            $pdf->Cell($fE,5,$ln["entrada_cant"]!==null?number_format($ln["entrada_cant"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($fSa,5,$ln["salida_cant"]!==null?number_format($ln["salida_cant"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($fSl,5,number_format($ln["saldo_cant"],2),"B",1,"R",$fill);
                        }
                        if ($totalesP) {
                            $pdf->SetFillColor($nR,$nG,$nB); $pdf->SetTextColor(255,255,255); $pdf->SetFont("Helvetica","B",6);
                            $pdf->Cell($fF+$fD+$fS+$fN+$fMo+$fO,5,"TOTALES DEL PERIODO",0,0,"R",true);
                            $pdf->Cell($fE,5,number_format($totalesP["entrada_cant"],2),0,0,"R",true);
                            $pdf->Cell($fSa,5,number_format($totalesP["salida_cant"],2),0,0,"R",true);
                            $pdf->Cell($fSl,5,number_format($totalesP["saldo_cant"],2),0,1,"R",true);
                        }
                    } else {
                        $pdf->SetFont("Helvetica","B",6); $pdf->SetFillColor(30,58,95); $pdf->SetTextColor(255,255,255);
                        $pdf->Cell($colFecha+$colTipo+$colMotivo,5,"",0,0,"C",true);
                        $pdf->SetFillColor(26,107,53); $pdf->Cell($colCant+$colCU+$colTotal,5,"ENTRADAS",1,0,"C",true);
                        $pdf->SetFillColor(123,30,30); $pdf->Cell($colCant+$colCU+$colTotal,5,"SALIDAS",1,0,"C",true);
                        $pdf->SetFillColor(26,58,107); $pdf->Cell($colSCant+$colSValor,5,"SALDO",1,1,"C",true);
                        $pdf->SetFillColor($nR,$nG,$nB); $pdf->SetTextColor(255,255,255);
                        foreach ([[$colFecha,"Fecha"],[$colTipo,"Tipo"],[$colMotivo,utf8_decode("Motivo")],[$colCant,"Cant."],[$colCU,"C.Unit."],[$colTotal,"Total"],[$colCant,"Cant."],[$colCU,"C.Unit."],[$colTotal,"Total"],[$colSCant,"Cant."],[$colSValor,"Valorizado"]] as [$w,$h]) { $pdf->Cell($w,5,$h,0,0,"C",true); }
                        $pdf->Ln();
                        $pdf->SetFillColor(220,228,245); $pdf->SetTextColor(30,41,59); $pdf->SetFont("Helvetica","B",6);
                        $pdf->Cell($colFecha,5,"-","B",0,"C",true); $pdf->Cell($colTipo,5,"SALDO INIC.","B",0,"C",true);
                        $pdf->Cell($colMotivo,5,utf8_decode("Saldo acumulado antes del periodo"),"B",0,"L",true);
                        $pdf->Cell($colCant+$colCU+$colTotal,5,"-","B",0,"C",true); $pdf->Cell($colCant+$colCU+$colTotal,5,"-","B",0,"C",true);
                        $pdf->Cell($colSCant,5,number_format($saldoInicialP["cantidad"],2),"B",0,"R",true);
                        $pdf->Cell($colSValor,5,"S/ ".number_format($saldoInicialP["valor"],2),"B",1,"R",true);
                        $pdf->SetFont("Helvetica","",6);
                        foreach ($lineasP as $idx => $ln) {
                            $pdf->CheckPageBreak(5); $fill=($idx%2===0);
                            $pdf->SetFillColor($fill?$gR:255,$fill?$gG:255,$fill?$gB:255); $pdf->SetTextColor(30,41,59);
                            $pdf->Cell($colFecha,5,date("d/m/Y",strtotime($ln["fecha"])),"B",0,"C",$fill);
                            $pdf->Cell($colTipo,5,$ln["tipo"]===1?"INGRESO":"SALIDA","B",0,"C",$fill);
                            $pdf->Cell($colMotivo,5,utf8_decode(mb_substr($ln["motivo"]??"",0,38)),"B",0,"L",$fill);
                            $pdf->Cell($colCant,5,$ln["entrada_cant"]!==null?number_format($ln["entrada_cant"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($colCU,5,$ln["entrada_cu"]!==null?number_format($ln["entrada_cu"],4):"-","B",0,"R",$fill);
                            $pdf->Cell($colTotal,5,$ln["entrada_total"]!==null?"S/ ".number_format($ln["entrada_total"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($colCant,5,$ln["salida_cant"]!==null?number_format($ln["salida_cant"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($colCU,5,$ln["salida_cu"]!==null?number_format($ln["salida_cu"],4):"-","B",0,"R",$fill);
                            $pdf->Cell($colTotal,5,$ln["salida_total"]!==null?"S/ ".number_format($ln["salida_total"],2):"-","B",0,"R",$fill);
                            $pdf->Cell($colSCant,5,number_format($ln["saldo_cant"],2),"B",0,"R",$fill);
                            $pdf->Cell($colSValor,5,"S/ ".number_format($ln["saldo_valor"],2),"B",1,"R",$fill);
                        }
                        if ($totalesP) {
                            $pdf->SetFillColor($nR,$nG,$nB); $pdf->SetTextColor(255,255,255); $pdf->SetFont("Helvetica","B",6);
                            $pdf->Cell($colFecha+$colTipo+$colMotivo,5,"TOTALES DEL PERIODO",0,0,"R",true);
                            $pdf->Cell($colCant,5,number_format($totalesP["entrada_cant"],2),0,0,"R",true); $pdf->Cell($colCU,5,"",0,0,"R",true);
                            $pdf->Cell($colTotal,5,"S/ ".number_format($totalesP["entrada_valor"],2),0,0,"R",true);
                            $pdf->Cell($colCant,5,number_format($totalesP["salida_cant"],2),0,0,"R",true); $pdf->Cell($colCU,5,"",0,0,"R",true);
                            $pdf->Cell($colTotal,5,"S/ ".number_format($totalesP["salida_valor"],2),0,0,"R",true);
                            $pdf->Cell($colSCant,5,number_format($totalesP["saldo_cant"],2),0,0,"R",true);
                            $pdf->Cell($colSValor,5,"S/ ".number_format($totalesP["saldo_valor"],2),0,1,"R",true);
                        }
                    }
                    $pdf->Ln(3);
                }

                $pdf->SetAutoPageBreak(false);
                $pdf->SetY(-10); $pdf->SetFont("Helvetica","",6); $pdf->SetTextColor(130,130,130);
                $pdf->SetX(8);
                $pdf->Cell($W/2,4,utf8_decode("Generado el ").date("d/m/Y H:i"),0,0,"L");
                $pdf->Cell($W/2,4,utf8_decode("Pagina ").$pdf->PageNo()." de {nb}",0,0,"R");
                $pdf->Output("I","Kardex-".date("Ymd").".pdf");
                exit;
            }

            $producto = \Illuminate\Support\Facades\DB::table("productos")->where("id_pro", $idPro)->first(["pro_nombre", "pro_codigo"]);
            [$lineas, $saldoInicial, $totales] = $this->calcularKardex($idPro, $desde, $hasta, $idEmpresa, $idSucursal);

            [$nR, $nG, $nB] = [30, 58, 95];
            [$gR, $gG, $gB] = [245, 247, 250];

            $pdf = new \App\Models\PDFBufeo("L", "mm", "A4");
            $pdf->SetMargins(8, 8, 8);
            $pdf->SetAutoPageBreak(true, 12);
            $pdf->AddPage();
            $pdf->AliasNbPages();
            $W = 281;

            // ── Barra título ──────────────────────────────────────────────
            $pdf->SetFillColor($nR, $nG, $nB);
            $pdf->Rect(8, 8, $W, 14, "F");
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont("Helvetica", "B", 12);
            $pdf->SetXY(8, 9);
            $tituloKardex = $tipoKardex === 'fisico' ? "KARDEX FISICO" : "KARDEX VALORIZADO";
            $pdf->Cell($W, 6, utf8_decode($tituloKardex), 0, 1, "C");
            $pdf->SetFont("Helvetica", "", 7);
            $pdf->SetXY(8, 17);
            $productoTxt = $producto ? utf8_decode($producto->pro_nombre . " [" . $producto->pro_codigo . "]") : "";
            $pdf->Cell($W, 3.5, $productoTxt, 0, 1, "C");

            // ── Cabecera SUNAT ─────────────────────────────────────────────
            $sunatTitulo = $tipoKardex === 'fisico'
                ? utf8_decode("REGISTRO DEL INVENTARIO PERMANENTE EN UNIDADES FÍSICAS")
                : utf8_decode("REGISTRO DEL INVENTARIO PERMANENTE VALORIZADO");
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFillColor(225, 232, 245);
            $pdf->Rect(8, 23, $W, 4.5, "F");
            $pdf->SetFont("Helvetica", "B", 7);
            $pdf->SetXY(8, 24);
            $pdf->Cell($W, 3.5, $sunatTitulo, 0, 1, "C");

            $periodoLabel = ($desde && $hasta)
                ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                : "Sin filtro de fecha";
            $camposPdf = [
                ["PERIODO:",                   $periodoLabel],
                [utf8_decode("NOMBRE Y/O RAZÓN SOCIAL:"), utf8_decode($empresaNombre)],
                ["RUC:",                       $empresaRuc],
                ["ESTABLECIMIENTO:",           utf8_decode($sedeNombre)],
                ["TIPO (TABLA 5):",            "01 MERCADERIA"],
                ["COD. UND DE MED (TABLA 6):", "07 UND"],
            ];
            if ($tipoKardex === 'valorizado') {
                $camposPdf[] = [utf8_decode("MÉTODO:"), utf8_decode("PROMEDIO PONDERADO MÓVIL")];
            }
            $yInfo = 28;
            foreach ($camposPdf as $i => [$lbl, $val]) {
                $pdf->SetFillColor($i % 2 === 0 ? 250 : 243, $i % 2 === 0 ? 252 : 247, $i % 2 === 0 ? 255 : 252);
                $pdf->Rect(8, $yInfo, $W, 3.5, "F");
                $pdf->SetXY(8, $yInfo + 0.3);
                $pdf->SetFont("Helvetica", "B", 6);
                $pdf->Cell(52, 3, $lbl, 0, 0, "L");
                $pdf->SetFont("Helvetica", "", 6);
                $pdf->Cell($W - 52, 3, $val, 0, 1, "L");
                $yInfo += 3.5;
            }
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetY($yInfo + 2);

            if ($tipoKardex === 'fisico') {
                // ── Tabla FÍSICO SUNAT ────────────────────────────────────
                // Anchos: 22+22+15+25+107+25+22+22+21 = 281
                [$fF,$fD,$fS,$fN,$fMo,$fO,$fE,$fSa,$fSl] = [22,22,15,25,107,25,22,22,21];

                $pdf->SetFont("Helvetica", "B", 6);
                $pdf->SetFillColor($nR, $nG, $nB); $pdf->SetTextColor(255,255,255);
                foreach ([
                    ["FECHA",$fF],[utf8_decode("T/DOC (TAB 10)"),$fD],["SERIE",$fS],
                    [utf8_decode("NÚMERO"),$fN],[utf8_decode("CLIENTE Y/O PROVEEDOR"),$fMo],
                    [utf8_decode("TIPO OP. (TAB 12)"),$fO],
                    ["ENTRADAS",$fE],["SALIDAS",$fSa],["SALDO FINAL",$fSl],
                ] as [$h,$w]) { $pdf->Cell($w, 5, $h, 0, 0, "C", true); }
                $pdf->Ln();

                $pdf->SetFillColor(220, 228, 245); $pdf->SetTextColor(30,41,59);
                $pdf->SetFont("Helvetica", "B", 6);
                $siFechaLabel = utf8_decode("SALDO INICIAL AL " . ($desde ? date("d/m/Y", strtotime($desde)) : ""));
                $pdf->Cell($fF, 5, "-", "B", 0, "C", true);
                $pdf->Cell($fD+$fS+$fN, 5, $siFechaLabel, "B", 0, "C", true);
                $pdf->Cell($fMo, 5, "", "B", 0, "L", true);
                $pdf->Cell($fO, 5, "-", "B", 0, "C", true);
                $pdf->Cell($fE, 5, "-", "B", 0, "C", true);
                $pdf->Cell($fSa, 5, "-", "B", 0, "C", true);
                $pdf->Cell($fSl, 5, number_format($saldoInicial["cantidad"], 2), "B", 1, "R", true);

                $pdf->SetFont("Helvetica", "", 6);
                foreach ($lineas as $idx => $ln) {
                    $pdf->CheckPageBreak(5);
                    $fill = ($idx % 2 === 0);
                    $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->Cell($fF,  5, date("d/m/Y", strtotime($ln["fecha"])), "B", 0, "C", $fill);
                    $pdf->Cell($fD,  5, $ln["tdoc"] ?? "00", "B", 0, "C", $fill);
                    $pdf->Cell($fS,  5, "-", "B", 0, "C", $fill);
                    $pdf->Cell($fN,  5, (string)($ln["id_referencia"] ?? $ln["id_movimiento"]), "B", 0, "C", $fill);
                    $pdf->Cell($fMo, 5, utf8_decode(mb_substr($ln["motivo"] ?? "", 0, 58)), "B", 0, "L", $fill);
                    $pdf->Cell($fO,  5, $ln["tipo_op"] ?? "99", "B", 0, "C", $fill);
                    $pdf->Cell($fE,  5, $ln["entrada_cant"] !== null ? number_format($ln["entrada_cant"], 2) : "-", "B", 0, "R", $fill);
                    $pdf->Cell($fSa, 5, $ln["salida_cant"]  !== null ? number_format($ln["salida_cant"], 2)  : "-", "B", 0, "R", $fill);
                    $pdf->Cell($fSl, 5, number_format($ln["saldo_cant"], 2), "B", 1, "R", $fill);
                }
                if ($totales) {
                    $pdf->SetFillColor($nR, $nG, $nB); $pdf->SetTextColor(255,255,255);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell($fF+$fD+$fS+$fN+$fMo+$fO, 5, "TOTALES DEL PERIODO", 0, 0, "R", true);
                    $pdf->Cell($fE,  5, number_format($totales["entrada_cant"], 2), 0, 0, "R", true);
                    $pdf->Cell($fSa, 5, number_format($totales["salida_cant"], 2),  0, 0, "R", true);
                    $pdf->Cell($fSl, 5, number_format($totales["saldo_cant"], 2),   0, 1, "R", true);
                }

            } else {
                // ── Tabla VALORIZADO SUNAT ────────────────────────────────
                $colFecha = 20; $colTipo = 22; $colMotivo = 62;
                $colCant  = 16; $colCU   = 20; $colTotal  = 22;
                $colSCant = 16; $colSValor = 22;

                $pdf->SetFont("Helvetica", "B", 6);
                $pdf->SetFillColor(30, 58, 95); $pdf->SetTextColor(255,255,255);
                $pdf->Cell($colFecha + $colTipo + $colMotivo, 5, "", 0, 0, "C", true);
                $pdf->SetFillColor(26, 107, 53);
                $pdf->Cell($colCant + $colCU + $colTotal, 5, "ENTRADAS", 1, 0, "C", true);
                $pdf->SetFillColor(123, 30, 30);
                $pdf->Cell($colCant + $colCU + $colTotal, 5, "SALIDAS", 1, 0, "C", true);
                $pdf->SetFillColor(26, 58, 107);
                $pdf->Cell($colSCant + $colSValor, 5, "SALDO", 1, 1, "C", true);

                $pdf->SetFillColor($nR, $nG, $nB); $pdf->SetTextColor(255,255,255);
                $heads  = ["Fecha","Tipo",utf8_decode("Motivo"),"Cant.","C.Unit.","Total","Cant.","C.Unit.","Total","Cant.","Valorizado"];
                $widths = [$colFecha,$colTipo,$colMotivo,$colCant,$colCU,$colTotal,$colCant,$colCU,$colTotal,$colSCant,$colSValor];
                foreach ($heads as $hi => $h) { $pdf->Cell($widths[$hi], 5, $h, 0, 0, "C", true); }
                $pdf->Ln();

                $pdf->SetFillColor(220, 228, 245); $pdf->SetTextColor(30,41,59);
                $pdf->SetFont("Helvetica", "B", 6);
                $pdf->Cell($colFecha, 5, "-", "B", 0, "C", true);
                $pdf->Cell($colTipo, 5, "SALDO INIC.", "B", 0, "C", true);
                $pdf->Cell($colMotivo, 5, utf8_decode("Saldo acumulado antes del periodo"), "B", 0, "L", true);
                $pdf->Cell($colCant+$colCU+$colTotal, 5, "-", "B", 0, "C", true);
                $pdf->Cell($colCant+$colCU+$colTotal, 5, "-", "B", 0, "C", true);
                $pdf->Cell($colSCant, 5, number_format($saldoInicial["cantidad"], 2), "B", 0, "R", true);
                $pdf->Cell($colSValor, 5, "S/ ".number_format($saldoInicial["valor"], 2), "B", 1, "R", true);

                $pdf->SetFont("Helvetica", "", 6);
                foreach ($lineas as $idx => $ln) {
                    $pdf->CheckPageBreak(5);
                    $fill = ($idx % 2 === 0);
                    $pdf->SetFillColor($fill ? $gR : 255, $fill ? $gG : 255, $fill ? $gB : 255);
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->Cell($colFecha,  5, date("d/m/Y", strtotime($ln["fecha"])), "B", 0, "C", $fill);
                    $pdf->Cell($colTipo,   5, $ln["tipo"] === 1 ? "INGRESO" : "SALIDA", "B", 0, "C", $fill);
                    $pdf->Cell($colMotivo, 5, utf8_decode(mb_substr($ln["motivo"] ?? "", 0, 38)), "B", 0, "L", $fill);
                    $pdf->Cell($colCant,  5, $ln["entrada_cant"]  !== null ? number_format($ln["entrada_cant"], 2)        : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colCU,    5, $ln["entrada_cu"]    !== null ? number_format($ln["entrada_cu"], 4)          : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colTotal, 5, $ln["entrada_total"] !== null ? "S/ ".number_format($ln["entrada_total"], 2) : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colCant,  5, $ln["salida_cant"]   !== null ? number_format($ln["salida_cant"], 2)         : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colCU,    5, $ln["salida_cu"]     !== null ? number_format($ln["salida_cu"], 4)           : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colTotal, 5, $ln["salida_total"]  !== null ? "S/ ".number_format($ln["salida_total"], 2)  : "-", "B", 0, "R", $fill);
                    $pdf->Cell($colSCant,  5, number_format($ln["saldo_cant"], 2),        "B", 0, "R", $fill);
                    $pdf->Cell($colSValor, 5, "S/ ".number_format($ln["saldo_valor"], 2), "B", 1, "R", $fill);
                }
                if ($totales) {
                    $pdf->SetFillColor($nR, $nG, $nB); $pdf->SetTextColor(255,255,255);
                    $pdf->SetFont("Helvetica", "B", 6);
                    $pdf->Cell($colFecha+$colTipo+$colMotivo, 5, "TOTALES DEL PERIODO", 0, 0, "R", true);
                    $pdf->Cell($colCant,   5, number_format($totales["entrada_cant"], 2),        0, 0, "R", true);
                    $pdf->Cell($colCU,     5, "",                                                 0, 0, "R", true);
                    $pdf->Cell($colTotal,  5, "S/ ".number_format($totales["entrada_valor"], 2), 0, 0, "R", true);
                    $pdf->Cell($colCant,   5, number_format($totales["salida_cant"], 2),         0, 0, "R", true);
                    $pdf->Cell($colCU,     5, "",                                                 0, 0, "R", true);
                    $pdf->Cell($colTotal,  5, "S/ ".number_format($totales["salida_valor"], 2),  0, 0, "R", true);
                    $pdf->Cell($colSCant,  5, number_format($totales["saldo_cant"], 2),          0, 0, "R", true);
                    $pdf->Cell($colSValor, 5, "S/ ".number_format($totales["saldo_valor"], 2),   0, 1, "R", true);
                }
            }

            $pdf->SetAutoPageBreak(false);
            $pdf->SetY(-10); $pdf->SetFont("Helvetica", "", 6); $pdf->SetTextColor(130,130,130);
            $pdf->SetX(8);
            $pdf->Cell($W/2, 4, utf8_decode("Generado el ") . date("d/m/Y H:i"), 0, 0, "L");
            $pdf->Cell($W/2, 4, utf8_decode("Pagina ") . $pdf->PageNo() . " de {nb}", 0, 0, "R");

            $pdf->Output("I", "Kardex-" . date("Ymd") . ".pdf");
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el PDF.'); window.history.back();</script>";
        }
    }

    public function kardex_valorizado_excel(\Illuminate\Http\Request $request)
    {
        try {
            $idPro      = (int) $request->id_pro;
            $desde      = $request->desde ?? null;
            $hasta      = $request->hasta ?? null;
            $idEmpresa  = $request->id_empresa  ? (int) $request->id_empresa  : null;
            $idSucursal = $request->id_sucursal ? (int) $request->id_sucursal : null;
            $idAlmacen  = $request->id_almacen  ? (int) $request->id_almacen  : null;
            $idFamilia  = $request->id_familia  ? (int) $request->id_familia  : null;

            $tipoKardex = $request->tipo ?? 'valorizado';

            // Resolver empresa solo para cabecera (sin tocar $idEmpresa que usa calcularKardex)
            $idEmpresaHeader = $idEmpresa;
            if (!$idEmpresaHeader && $idAlmacen > 0) {
                $idEmpresaHeader = \Illuminate\Support\Facades\DB::table("almacen")->where("id_almacen", $idAlmacen)->value("id_empresa") ?: null;
            }

            $empresa       = $idEmpresaHeader ? \Illuminate\Support\Facades\DB::table("empresa")->where("id_empresa", $idEmpresaHeader)->first() : null;
            $tienda        = $idSucursal > 0 ? \Illuminate\Support\Facades\DB::table("tiendas")->where("id_tienda", $idSucursal)->first() : null;
            $almacenRow    = $idAlmacen  > 0 ? \Illuminate\Support\Facades\DB::table("almacen")->where("id_almacen", $idAlmacen)->first() : null;
            $empresaNombre = $empresa ? ($empresa->empresa_razon_social ?? $empresa->empresa_nombrecomercial ?? '') : '';
            $empresaRuc    = $empresa ? ($empresa->empresa_ruc ?? '') : '';
            $sedeNombre    = $almacenRow ? ($almacenRow->almacen_nombre ?? '')
                           : ($tienda    ? ($tienda->tienda_nombre    ?? '') : '');

            $navy  = "FF1E3A5F"; $white = "FFFFFFFF";
            $green = "FF1A6B35"; $red   = "FF7B1E1E"; $blue  = "FF1A3A6B";

            $estiloEnc = [
                "font"      => ["bold" => true, "color" => ["argb" => $white], "size" => 8, "name" => "Arial"],
                "fill"      => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]],
                "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, "vertical" => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                "borders"   => ["allBorders" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, "color" => ["argb" => $white]]],
            ];
            $estiloBorde = [
                "borders" => ["allBorders" => ["borderStyle" => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, "color" => ["argb" => "FFD0D0D0"]]],
                "font"    => ["size" => 8, "name" => "Arial"],
            ];

            // ── Resumido: ruta separada ────────────────────────────────
            if ($tipoKardex === 'resumido') {
                $grupos = $this->calcularKardexResumido($idFamilia, $idPro ?: null, $desde, $hasta, $idEmpresa, $idSucursal, $idAlmacen);

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle("RIPV Resumido");

                $periodoTxt = ($desde && $hasta)
                    ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                    : "Sin filtro";

                $sheet->mergeCells("A1:H1");
                $sheet->setCellValue("A1", "REGISTRO DE INVENTARIO PERMANENTE VALORIZADO (RESUMEN)");
                $sheet->getStyle("A1")->applyFromArray([
                    "font"      => ["bold" => true, "size" => 13, "color" => ["argb" => $navy], "name" => "Arial"],
                    "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(20);

                $camposXls = [
                    ["PERIODO:",                   $periodoTxt],
                    ["NOMBRE Y/O RAZÓN SOCIAL:",   $empresaNombre],
                    ["RUC:",                       $empresaRuc],
                    ["ESTABLECIMIENTO:",           $sedeNombre],
                    ["TIPO (TABLA 5):",            "01 MERCADERIA"],
                    ["MÉTODO:",                    "PROMEDIO PONDERADO MÓVIL"],
                ];
                $filaInfo = 3;
                foreach ($camposXls as $i => [$lbl, $val]) {
                    $sheet->setCellValue("A{$filaInfo}", $lbl);
                    $sheet->setCellValue("B{$filaInfo}", $val);
                    $sheet->mergeCells("B{$filaInfo}:H{$filaInfo}");
                    $bgArgb = $i % 2 === 0 ? "FFFFFFFF" : "FFF5F7FA";
                    $sheet->getStyle("A{$filaInfo}")->applyFromArray(["font" => ["bold" => true, "size" => 7, "name" => "Arial"], "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bgArgb]]]);
                    $sheet->getStyle("B{$filaInfo}:H{$filaInfo}")->applyFromArray(["font" => ["size" => 7, "name" => "Arial"], "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bgArgb]]]);
                    $sheet->getRowDimension($filaInfo)->setRowHeight(12);
                    $filaInfo++;
                }

                $headRow = $filaInfo;
                foreach (["A" => "COD. EXISTENCIA", "B" => "PRODUCTO", "C" => "SALDO INICIAL",
                          "D" => "INGRESOS CANT.", "E" => "EGRESOS CANT.", "F" => "SALDO FINAL",
                          "G" => "C.U.", "H" => "C.T."] as $col => $txt) {
                    $sheet->setCellValue("{$col}{$headRow}", $txt);
                }
                $sheet->getStyle("A{$headRow}:H{$headRow}")->applyFromArray($estiloEnc);
                $sheet->getRowDimension($headRow)->setRowHeight(16);

                $fila = $headRow + 1;
                $totalGeneral = 0.0;
                foreach ($grupos as $grupo) {
                    $sheet->setCellValue("A{$fila}", "MARCA: " . $grupo['familia']);
                    $sheet->mergeCells("A{$fila}:H{$fila}");
                    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray([
                        "font" => ["bold" => true, "size" => 8, "name" => "Arial"],
                        "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFDCE8FF"]],
                    ]);
                    $fila++;

                    foreach ($grupo['productos'] as $i => $prod) {
                        $sheet->setCellValue("A{$fila}", $prod['codigo']);
                        $sheet->setCellValue("B{$fila}", $prod['nombre']);
                        $sheet->setCellValue("C{$fila}", (float) $prod['saldo_ini_cant']);
                        $sheet->setCellValue("D{$fila}", (float) $prod['ingresos_cant']);
                        $sheet->setCellValue("E{$fila}", (float) $prod['egresos_cant']);
                        $sheet->setCellValue("F{$fila}", (float) $prod['saldo_final_cant']);
                        $sheet->setCellValue("G{$fila}", (float) $prod['c_u']);
                        $sheet->setCellValue("H{$fila}", (float) $prod['c_t']);
                        $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray($estiloBorde);
                        if ($i % 2 === 0) {
                            $sheet->getStyle("A{$fila}:H{$fila}")->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB("FFF5F7FA");
                        }
                        $fila++;
                    }

                    $sheet->setCellValue("G{$fila}", "TOTALES:");
                    $sheet->setCellValue("H{$fila}", (float) $grupo['total_ct']);
                    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray([
                        "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]],
                        "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8],
                    ]);
                    $totalGeneral += $grupo['total_ct'];
                    $fila++;
                }

                $sheet->setCellValue("G{$fila}", "TOTAL GENERAL:");
                $sheet->setCellValue("H{$fila}", (float) $totalGeneral);
                $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray([
                    "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FF0D1B2A"]],
                    "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 9],
                ]);

                foreach (["A" => 14, "B" => 46, "C" => 14, "D" => 14, "E" => 14, "F" => 14, "G" => 14, "H" => 16] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
                $sheet->getStyle("C{$headRow}:F{$fila}")->getNumberFormat()->setFormatCode("#,##0.00");
                $sheet->getStyle("G{$headRow}:G{$fila}")->getNumberFormat()->setFormatCode("#,##0.0000");
                $sheet->getStyle("H{$headRow}:H{$fila}")->getNumberFormat()->setFormatCode("\"S/ \"#,##0.00");

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header("Content-Disposition: attachment;filename=\"KardexResumido-" . date("Ymd") . ".xlsx\"");
                header("Cache-Control: max-age=0");
                $writer->save("php://output");
                exit;
            }

            // ── Multi-producto Excel: fisico/valorizado sin producto, por familia ──
            if ($idPro === 0 && $idFamilia > 0) {
                $productos = \Illuminate\Support\Facades\DB::table('productos as p')
                    ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
                    ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
                    ->where('f.id_fa', $idFamilia)
                    ->where('p.pro_estado', 1)
                    ->orderBy('p.pro_nombre')
                    ->get(['p.id_pro', 'p.pro_nombre', 'p.pro_codigo']);

                $familiaNombre = \Illuminate\Support\Facades\DB::table('familias')
                    ->where('id_fa', $idFamilia)->value('fa_nombre') ?? '';

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $tituloXls = $tipoKardex === 'fisico' ? "KARDEX FISICO" : "KARDEX VALORIZADO";
                $sheet->setTitle(substr($tituloXls, 0, 31));

                $maxCol = $tipoKardex === 'fisico' ? "I" : "K";
                $periodoTxt = ($desde && $hasta)
                    ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                    : "Sin filtro";

                $sheet->mergeCells("A1:{$maxCol}1");
                $sheet->setCellValue("A1", $tituloXls . " — FAMILIA/MARCA: " . $familiaNombre);
                $sheet->getStyle("A1")->applyFromArray(["font" => ["bold" => true, "size" => 13, "name" => "Arial"], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->getRowDimension(1)->setRowHeight(20);

                $camposXls = [
                    ["PERIODO:", $periodoTxt],
                    ["NOMBRE Y/O RAZÓN SOCIAL:", $empresaNombre],
                    ["RUC:", $empresaRuc],
                    ["ESTABLECIMIENTO:", $sedeNombre],
                    ["TIPO (TABLA 5):", "01 MERCADERIA"],
                ];
                $filaInfo = 3;
                foreach ($camposXls as $i => [$lbl, $val]) {
                    $sheet->setCellValue("A{$filaInfo}", $lbl);
                    $sheet->setCellValue("B{$filaInfo}", $val);
                    $sheet->mergeCells("B{$filaInfo}:{$maxCol}{$filaInfo}");
                    $bg = $i % 2 === 0 ? "FFFFFFFF" : "FFF5F7FA";
                    $sheet->getStyle("A{$filaInfo}")->applyFromArray(["font" => ["bold" => true, "size" => 7, "name" => "Arial"], "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bg]]]);
                    $sheet->getStyle("B{$filaInfo}:{$maxCol}{$filaInfo}")->applyFromArray(["font" => ["size" => 7, "name" => "Arial"], "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bg]]]);
                    $sheet->getRowDimension($filaInfo)->setRowHeight(12);
                    $filaInfo++;
                }

                $fila = $filaInfo + 1;

                foreach ($productos as $prod) {
                    [$lineasP, $saldoInicialP, $totalesP] = $this->calcularKardex($prod->id_pro, $desde, $hasta, $idEmpresa, $idSucursal);
                    if ($saldoInicialP['cantidad'] == 0 && $saldoInicialP['valor'] == 0 && empty($lineasP)) continue;

                    // Product band
                    $sheet->setCellValue("A{$fila}", $prod->pro_nombre . " [" . $prod->pro_codigo . "]");
                    $sheet->mergeCells("A{$fila}:{$maxCol}{$fila}");
                    $sheet->getStyle("A{$fila}:{$maxCol}{$fila}")->applyFromArray([
                        "font" => ["bold" => true, "size" => 8, "name" => "Arial"],
                        "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFDCE8FF"]],
                    ]);
                    $fila++;

                    if ($tipoKardex === 'fisico') {
                        $encRow = $fila; $headRow = $fila + 1; $fila += 2;
                        $sheet->mergeCells("A{$encRow}:F{$encRow}"); $sheet->setCellValue("A{$encRow}", "DOCUMENTO"); $sheet->setCellValue("G{$encRow}", "ENTRADAS"); $sheet->setCellValue("H{$encRow}", "SALIDAS"); $sheet->setCellValue("I{$encRow}", "SALDO FINAL");
                        $sheet->getStyle("A{$encRow}:F{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 7], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        $sheet->getStyle("G{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $green]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        $sheet->getStyle("H{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $red]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        $sheet->getStyle("I{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $blue]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        foreach (["A"=>"FECHA","B"=>"T/DOC","C"=>"SERIE","D"=>"NÚMERO","E"=>"CLIENTE/PROVEEDOR","F"=>"TIPO OP.","G"=>"Cant.","H"=>"Cant.","I"=>"Cant."] as $col=>$txt) { $sheet->setCellValue("{$col}{$headRow}", $txt); }
                        $sheet->getStyle("A{$headRow}:I{$headRow}")->applyFromArray($estiloEnc);

                        $sheet->setCellValue("A{$fila}", "-"); $sheet->setCellValue("B{$fila}", "SALDO INICIAL AL ".($desde?date("d/m/Y",strtotime($desde)):""));
                        $sheet->mergeCells("B{$fila}:F{$fila}"); $sheet->setCellValue("G{$fila}", "—"); $sheet->setCellValue("H{$fila}", "—"); $sheet->setCellValue("I{$fila}", (float)$saldoInicialP["cantidad"]);
                        $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFE8EDF8"]], "font" => ["bold" => true, "size" => 8]]);
                        $fila++;

                        foreach ($lineasP as $i => $ln) {
                            $sheet->setCellValue("A{$fila}", $ln["fecha"]); $sheet->setCellValue("B{$fila}", $ln["tdoc"]??"00"); $sheet->setCellValue("C{$fila}", "—");
                            $sheet->setCellValue("D{$fila}", $ln["id_referencia"]??$ln["id_movimiento"]); $sheet->setCellValue("E{$fila}", $ln["motivo"]??""); $sheet->setCellValue("F{$fila}", $ln["tipo_op"]??"99");
                            if ($ln["entrada_cant"]!==null) $sheet->setCellValue("G{$fila}", (float)$ln["entrada_cant"]);
                            if ($ln["salida_cant"]!==null)  $sheet->setCellValue("H{$fila}", (float)$ln["salida_cant"]);
                            $sheet->setCellValue("I{$fila}", (float)$ln["saldo_cant"]);
                            $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray($estiloBorde);
                            if ($i%2===0) { $sheet->getStyle("A{$fila}:I{$fila}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB("FFF5F7FA"); }
                            $fila++;
                        }
                        if ($totalesP) {
                            $sheet->setCellValue("F{$fila}", "TOTALES"); $sheet->setCellValue("G{$fila}", (float)$totalesP["entrada_cant"]); $sheet->setCellValue("H{$fila}", (float)$totalesP["salida_cant"]); $sheet->setCellValue("I{$fila}", (float)$totalesP["saldo_cant"]);
                            $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8]]);
                            $fila++;
                        }
                    } else {
                        $encRow = $fila; $headRow = $fila + 1; $fila += 2;
                        $sheet->mergeCells("D{$encRow}:F{$encRow}"); $sheet->setCellValue("D{$encRow}", "ENTRADAS");
                        $sheet->mergeCells("G{$encRow}:I{$encRow}"); $sheet->setCellValue("G{$encRow}", "SALIDAS");
                        $sheet->mergeCells("J{$encRow}:K{$encRow}"); $sheet->setCellValue("J{$encRow}", "SALDO");
                        $sheet->getStyle("D{$encRow}:F{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $green]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        $sheet->getStyle("G{$encRow}:I{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $red]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        $sheet->getStyle("J{$encRow}:K{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $blue]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                        foreach (["A"=>"Fecha","B"=>"Tipo","C"=>"Motivo / Referencia","D"=>"E.Cant.","E"=>"E.C.Unit.","F"=>"E.Total","G"=>"S.Cant.","H"=>"S.C.Unit.","I"=>"S.Total","J"=>"Saldo Cant.","K"=>"Saldo Valor"] as $col=>$txt) { $sheet->setCellValue("{$col}{$headRow}", $txt); }
                        $sheet->getStyle("A{$headRow}:K{$headRow}")->applyFromArray($estiloEnc);

                        $sheet->setCellValue("A{$fila}", "-"); $sheet->setCellValue("B{$fila}", "SALDO INIC."); $sheet->setCellValue("C{$fila}", "Saldo acumulado antes del periodo");
                        $sheet->setCellValue("J{$fila}", (float)$saldoInicialP["cantidad"]); $sheet->setCellValue("K{$fila}", (float)$saldoInicialP["valor"]);
                        $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFE8EDF8"]], "font" => ["bold" => true, "size" => 8]]);
                        $fila++;

                        foreach ($lineasP as $i => $ln) {
                            $sheet->setCellValue("A{$fila}", $ln["fecha"]); $sheet->setCellValue("B{$fila}", $ln["tipo"]===1?"INGRESO":"SALIDA"); $sheet->setCellValue("C{$fila}", $ln["motivo"]??"");
                            if ($ln["entrada_cant"]!==null) { $sheet->setCellValue("D{$fila}", (float)$ln["entrada_cant"]); $sheet->setCellValue("E{$fila}", (float)$ln["entrada_cu"]); $sheet->setCellValue("F{$fila}", (float)$ln["entrada_total"]); }
                            if ($ln["salida_cant"]!==null)  { $sheet->setCellValue("G{$fila}", (float)$ln["salida_cant"]);  $sheet->setCellValue("H{$fila}", (float)$ln["salida_cu"]);  $sheet->setCellValue("I{$fila}", (float)$ln["salida_total"]); }
                            $sheet->setCellValue("J{$fila}", (float)$ln["saldo_cant"]); $sheet->setCellValue("K{$fila}", (float)$ln["saldo_valor"]);
                            $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray($estiloBorde);
                            if ($i%2===0) { $sheet->getStyle("A{$fila}:K{$fila}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB("FFF5F7FA"); }
                            $fila++;
                        }
                        if ($totalesP) {
                            $sheet->setCellValue("C{$fila}", "TOTALES"); $sheet->setCellValue("D{$fila}", (float)$totalesP["entrada_cant"]); $sheet->setCellValue("F{$fila}", (float)$totalesP["entrada_valor"]);
                            $sheet->setCellValue("G{$fila}", (float)$totalesP["salida_cant"]); $sheet->setCellValue("I{$fila}", (float)$totalesP["salida_valor"]);
                            $sheet->setCellValue("J{$fila}", (float)$totalesP["saldo_cant"]); $sheet->setCellValue("K{$fila}", (float)$totalesP["saldo_valor"]);
                            $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8]]);
                            $fila++;
                        }
                    }
                    $fila++; // blank row between products
                }

                foreach (["A"=>12,"B"=>14,"C"=>10,"D"=>14,"E"=>38,"F"=>14,"G"=>12,"H"=>12,"I"=>12,"J"=>14,"K"=>16] as $col=>$w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header("Content-Disposition: attachment;filename=\"Kardex-".date("Ymd").".xlsx\"");
                header("Cache-Control: max-age=0");
                $writer->save("php://output");
                exit;
            }

            $producto = \Illuminate\Support\Facades\DB::table("productos")->where("id_pro", $idPro)->first(["pro_nombre", "pro_codigo"]);
            [$lineas, $saldoInicial, $totales] = $this->calcularKardex($idPro, $desde, $hasta, $idEmpresa, $idSucursal);

            $sucursalNombre = $idSucursal > 0
                ? (\Illuminate\Support\Facades\DB::table("sucursals")->where("id_sucursal", $idSucursal)->value("sucursal_nombre") ?? "")
                : "";

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Kardex");

            $sheet->mergeCells("A1:K1");
            $tituloExcel = $tipoKardex === 'fisico' ? "KARDEX FISICO" : "KARDEX VALORIZADO";
            $sheet->setCellValue("A1", $tituloExcel);
            $sheet->getStyle("A1")->applyFromArray([
                "font"      => ["bold" => true, "size" => 14, "color" => ["argb" => $navy], "name" => "Arial"],
                "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(22);

            $productoTxt = $producto ? $producto->pro_nombre . " [" . $producto->pro_codigo . "]" : "";
            $periodoTxt  = ($desde && $hasta)
                ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                : "Sin filtro";
            $sheet->mergeCells("A2:K2");
            $sheet->setCellValue("A2", $productoTxt . " | Periodo: " . $periodoTxt . ($sucursalNombre ? " | " . $sucursalNombre : ""));
            $sheet->getStyle("A2")->applyFromArray(["font" => ["size" => 8, "italic" => true]]);

            // ── Cabecera SUNAT ─────────────────────────────────────────────
            $sunatTituloXls = $tipoKardex === 'fisico'
                ? "REGISTRO DEL INVENTARIO PERMANENTE EN UNIDADES FÍSICAS"
                : "REGISTRO DEL INVENTARIO PERMANENTE VALORIZADO";
            $sheet->mergeCells("A4:K4");
            $sheet->setCellValue("A4", $sunatTituloXls);
            $sheet->getStyle("A4")->applyFromArray([
                "font"      => ["bold" => true, "size" => 8, "name" => "Arial"],
                "fill"      => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFE1E8F5"]],
                "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getRowDimension(4)->setRowHeight(14);

            $periodoXls = ($desde && $hasta)
                ? date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta))
                : "Sin filtro";
            $camposXls = [
                ["PERIODO:",                   $periodoXls],
                ["NOMBRE Y/O RAZÓN SOCIAL:",   $empresaNombre],
                ["RUC:",                       $empresaRuc],
                ["ESTABLECIMIENTO:",           $sedeNombre],
                ["TIPO (TABLA 5):",            "01 MERCADERIA"],
                ["COD. UND DE MED (TABLA 6):", "07 UND"],
            ];
            if ($tipoKardex === 'valorizado') {
                $camposXls[] = ["MÉTODO:", "PROMEDIO PONDERADO MÓVIL"];
            }
            $filaInfo = 5;
            foreach ($camposXls as $i => [$lbl, $val]) {
                $sheet->setCellValue("A{$filaInfo}", $lbl);
                $sheet->setCellValue("B{$filaInfo}", $val);
                $sheet->mergeCells("B{$filaInfo}:K{$filaInfo}");
                $bgArgb = $i % 2 === 0 ? "FFFFFFFF" : "FFF5F7FA";
                $sheet->getStyle("A{$filaInfo}")->applyFromArray([
                    "font" => ["bold" => true, "size" => 7, "name" => "Arial"],
                    "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bgArgb]],
                ]);
                $sheet->getStyle("B{$filaInfo}:K{$filaInfo}")->applyFromArray([
                    "font" => ["size" => 7, "name" => "Arial"],
                    "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $bgArgb]],
                ]);
                $sheet->getRowDimension($filaInfo)->setRowHeight(12);
                $filaInfo++;
            }

            $encRow  = $filaInfo;
            $headRow = $filaInfo + 1;
            $fila    = $filaInfo + 2;

            if ($tipoKardex === 'fisico') {
                // ── Tabla FÍSICO Excel ────────────────────────────────────
                $sheet->mergeCells("A{$encRow}:F{$encRow}");
                $sheet->setCellValue("A{$encRow}", "DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO, DOC. INTERNO O SIMILAR");
                $sheet->getStyle("A{$encRow}:F{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 7], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->setCellValue("G{$encRow}", "ENTRADAS");
                $sheet->getStyle("G{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $green]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->setCellValue("H{$encRow}", "SALIDAS");
                $sheet->getStyle("H{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $red]],   "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->setCellValue("I{$encRow}", "SALDO FINAL");
                $sheet->getStyle("I{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $blue]],  "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);

                foreach (["A" => "FECHA", "B" => "T/DOC (TAB 10)", "C" => "SERIE",
                          "D" => "NÚMERO", "E" => "CLIENTE Y/O PROVEEDOR", "F" => "TIPO OP. (TAB 12)",
                          "G" => "Cant.", "H" => "Cant.", "I" => "Cant."] as $col => $txt) {
                    $sheet->setCellValue("{$col}{$headRow}", $txt);
                }
                $sheet->getStyle("A{$headRow}:I{$headRow}")->applyFromArray($estiloEnc);
                $sheet->getRowDimension($headRow)->setRowHeight(16);

                $primeraFilaDatos = $fila;
                $siLabel = "SALDO INICIAL AL " . ($desde ? date("d/m/Y", strtotime($desde)) : "");
                $sheet->setCellValue("A{$fila}", "-");
                $sheet->setCellValue("B{$fila}", $siLabel);
                $sheet->mergeCells("B{$fila}:F{$fila}");
                $sheet->setCellValue("G{$fila}", "—");
                $sheet->setCellValue("H{$fila}", "—");
                $sheet->setCellValue("I{$fila}", (float) $saldoInicial["cantidad"]);
                $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFE8EDF8"]], "font" => ["bold" => true, "size" => 8]]);
                $fila++;

                foreach ($lineas as $i => $ln) {
                    $sheet->setCellValue("A{$fila}", $ln["fecha"]);
                    $sheet->setCellValue("B{$fila}", $ln["tdoc"] ?? "00");
                    $sheet->setCellValue("C{$fila}", "—");
                    $sheet->setCellValue("D{$fila}", $ln["id_referencia"] ?? $ln["id_movimiento"]);
                    $sheet->setCellValue("E{$fila}", $ln["motivo"] ?? "");
                    $sheet->setCellValue("F{$fila}", $ln["tipo_op"] ?? "99");
                    if ($ln["entrada_cant"] !== null) { $sheet->setCellValue("G{$fila}", (float) $ln["entrada_cant"]); }
                    if ($ln["salida_cant"]  !== null) { $sheet->setCellValue("H{$fila}", (float) $ln["salida_cant"]); }
                    $sheet->setCellValue("I{$fila}", (float) $ln["saldo_cant"]);
                    $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray($estiloBorde);
                    if ($i % 2 === 0) {
                        $sheet->getStyle("A{$fila}:I{$fila}")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB("FFF5F7FA");
                    }
                    $fila++;
                }
                if ($totales) {
                    $sheet->setCellValue("F{$fila}", "TOTALES DEL PERIODO");
                    $sheet->setCellValue("G{$fila}", (float) $totales["entrada_cant"]);
                    $sheet->setCellValue("H{$fila}", (float) $totales["salida_cant"]);
                    $sheet->setCellValue("I{$fila}", (float) $totales["saldo_cant"]);
                    $sheet->getStyle("A{$fila}:I{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8]]);
                }
                foreach (["A" => 12, "B" => 14, "C" => 10, "D" => 14, "E" => 42, "F" => 14, "G" => 12, "H" => 12, "I" => 12] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
                $sheet->getStyle("G{$primeraFilaDatos}:I{$fila}")->getNumberFormat()->setFormatCode("#,##0.00");

            } else {
                // ── Tabla VALORIZADO Excel ────────────────────────────────
                $sheet->mergeCells("D{$encRow}:F{$encRow}"); $sheet->setCellValue("D{$encRow}", "ENTRADAS");
                $sheet->mergeCells("G{$encRow}:I{$encRow}"); $sheet->setCellValue("G{$encRow}", "SALIDAS");
                $sheet->mergeCells("J{$encRow}:K{$encRow}"); $sheet->setCellValue("J{$encRow}", "SALDO");
                $sheet->getStyle("D{$encRow}:F{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $green]], "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->getStyle("G{$encRow}:I{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $red]],   "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                $sheet->getStyle("J{$encRow}:K{$encRow}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $blue]],  "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8], "alignment" => ["horizontal" => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);

                foreach (["A" => "Fecha", "B" => "Tipo", "C" => "Motivo / Referencia",
                          "D" => "E.Cant.", "E" => "E.C.Unit.", "F" => "E.Total",
                          "G" => "S.Cant.", "H" => "S.C.Unit.", "I" => "S.Total",
                          "J" => "Saldo Cant.", "K" => "Saldo Valor"] as $col => $txt) {
                    $sheet->setCellValue("{$col}{$headRow}", $txt);
                }
                $sheet->getStyle("A{$headRow}:K{$headRow}")->applyFromArray($estiloEnc);
                $sheet->getRowDimension($headRow)->setRowHeight(16);

                $primeraFilaDatos = $fila;
                $sheet->setCellValue("A{$fila}", "-");
                $sheet->setCellValue("B{$fila}", "SALDO INICIAL");
                $sheet->setCellValue("C{$fila}", "Saldo acumulado antes del periodo");
                $sheet->setCellValue("J{$fila}", (float) $saldoInicial["cantidad"]);
                $sheet->setCellValue("K{$fila}", (float) $saldoInicial["valor"]);
                $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray(["fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => "FFE8EDF8"]], "font" => ["bold" => true, "size" => 8]]);
                $fila++;

                foreach ($lineas as $i => $ln) {
                    $sheet->setCellValue("A{$fila}", $ln["fecha"]);
                    $sheet->setCellValue("B{$fila}", $ln["tipo"] === 1 ? "INGRESO" : "SALIDA");
                    $sheet->setCellValue("C{$fila}", ($ln["motivo"] ?? "") . ($ln["tipo_referencia"] ? " [" . $ln["tipo_referencia"] . "]" : ""));
                    if ($ln["entrada_cant"] !== null) {
                        $sheet->setCellValue("D{$fila}", (float) $ln["entrada_cant"]);
                        $sheet->setCellValue("E{$fila}", (float) $ln["entrada_cu"]);
                        $sheet->setCellValue("F{$fila}", (float) $ln["entrada_total"]);
                    }
                    if ($ln["salida_cant"] !== null) {
                        $sheet->setCellValue("G{$fila}", (float) $ln["salida_cant"]);
                        $sheet->setCellValue("H{$fila}", (float) $ln["salida_cu"]);
                        $sheet->setCellValue("I{$fila}", (float) $ln["salida_total"]);
                    }
                    $sheet->setCellValue("J{$fila}", (float) $ln["saldo_cant"]);
                    $sheet->setCellValue("K{$fila}", (float) $ln["saldo_valor"]);
                    $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray($estiloBorde);
                    if ($i % 2 === 0) {
                        $sheet->getStyle("A{$fila}:K{$fila}")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB("FFF5F7FA");
                    }
                    $fila++;
                }
                if ($totales) {
                    $sheet->setCellValue("C{$fila}", "TOTALES DEL PERIODO");
                    $sheet->setCellValue("D{$fila}", (float) $totales["entrada_cant"]);
                    $sheet->setCellValue("F{$fila}", (float) $totales["entrada_valor"]);
                    $sheet->setCellValue("G{$fila}", (float) $totales["salida_cant"]);
                    $sheet->setCellValue("I{$fila}", (float) $totales["salida_valor"]);
                    $sheet->setCellValue("J{$fila}", (float) $totales["saldo_cant"]);
                    $sheet->setCellValue("K{$fila}", (float) $totales["saldo_valor"]);
                    $sheet->getStyle("A{$fila}:K{$fila}")->applyFromArray([
                        "fill" => ["fillType" => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, "startColor" => ["argb" => $navy]],
                        "font" => ["bold" => true, "color" => ["argb" => $white], "size" => 8],
                    ]);
                }
                foreach (["A" => 12, "B" => 12, "C" => 42, "D" => 10, "E" => 12, "F" => 14,
                          "G" => 10, "H" => 12, "I" => 14, "J" => 12, "K" => 14] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
                $sheet->getStyle("D{$primeraFilaDatos}:D{$fila}")->getNumberFormat()->setFormatCode("#,##0.0000");
                $sheet->getStyle("G{$primeraFilaDatos}:G{$fila}")->getNumberFormat()->setFormatCode("#,##0.0000");
                $sheet->getStyle("J{$primeraFilaDatos}:J{$fila}")->getNumberFormat()->setFormatCode("#,##0.0000");
                foreach (["E","F","H","I","K"] as $col) {
                    $sheet->getStyle("{$col}{$primeraFilaDatos}:{$col}{$fila}")->getNumberFormat()->setFormatCode("\"S/ \"#,##0.00");
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment;filename=\"Kardex-" . date("Ymd") . ".xlsx\"");
            header("Cache-Control: max-age=0");
            $writer->save("php://output");
            exit;

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            echo "<script>alert('Error al generar el Excel.'); window.history.back();</script>";
        }
    }

    private function calcularKardex(int $idPro, ?string $desde, ?string $hasta, ?int $idEmpresa, ?int $idSucursal): array
    {
        $DB = \Illuminate\Support\Facades\DB::class;

        $qSaldo = \Illuminate\Support\Facades\DB::table("movimientos_productos as mp")
            ->join("movimientos_productos_detalle as mpd", "mpd.id_movimientos_productos", "=", "mp.id_movimientos_productos")
            ->where("mpd.id_pro", $idPro)
            ->where("mp.movimientos_productos_estado", 1);
        if ($desde) $qSaldo->where("mp.movimientos_productos_fecha", "<", $desde);
        if ($idSucursal > 0) {
            $qSaldo->where("mp.id_sucursal", $idSucursal);
        } elseif ($idEmpresa) {
            $qSaldo->join("sucursals as s0", "s0.id_sucursal", "=", "mp.id_sucursal")->where("s0.id_empresa", $idEmpresa);
        }
        $saldoRow = $qSaldo->selectRaw("
            SUM(CASE WHEN mp.movimientos_productos_tipo=1 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END)
          - SUM(CASE WHEN mp.movimientos_productos_tipo=2 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) as saldo_cantidad,
            SUM(CASE WHEN mp.movimientos_productos_tipo=1 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END)
          - SUM(CASE WHEN mp.movimientos_productos_tipo=2 THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) as saldo_valor
        ")->first();
        $saldoCant  = (float)($saldoRow->saldo_cantidad ?? 0);
        $saldoValor = (float)($saldoRow->saldo_valor    ?? 0);
        $saldoInicial = ["cantidad" => $saldoCant, "valor" => $saldoValor];

        $qMov = \Illuminate\Support\Facades\DB::table("movimientos_productos as mp")
            ->join("movimientos_productos_detalle as mpd", "mpd.id_movimientos_productos", "=", "mp.id_movimientos_productos")
            ->join("users as u", "u.id_users", "=", "mp.id_users")
            ->where("mpd.id_pro", $idPro)
            ->where("mp.movimientos_productos_estado", 1);
        if ($desde && $hasta) $qMov->whereBetween("mp.movimientos_productos_fecha", [$desde, $hasta]);
        if ($idSucursal > 0) {
            $qMov->where("mp.id_sucursal", $idSucursal);
        } elseif ($idEmpresa) {
            $qMov->join("sucursals as s1", "s1.id_sucursal", "=", "mp.id_sucursal")->where("s1.id_empresa", $idEmpresa);
        }
        $movimientos = $qMov->select(
                "mp.movimientos_productos_fecha as fecha",
                "mp.id_movimientos_productos",
                "mp.movimientos_productos_tipo as tipo",
                "mp.movimientos_productos_motivo as motivo",
                "mpd.movimientos_productos_detalle_cantidad as cantidad",
                "mpd.costo_unitario",
                "mpd.tipo_referencia",
                "mpd.id_referencia",
                "u.nombre_users as usuario"
            )
            ->orderBy("mp.movimientos_productos_fecha")
            ->orderBy("mp.id_movimientos_productos")
            ->get();

        $totalECant = $totalEValor = $totalSCant = $totalSValor = 0.0;
        $lineas = [];
        foreach ($movimientos as $mov) {
            $cant  = (float) $mov->cantidad;
            $cu    = (float) $mov->costo_unitario;
            $total = $cant * $cu;
            if ((int)$mov->tipo === 1) {
                $saldoCant  += $cant;  $saldoValor  += $total;
                $totalECant += $cant;  $totalEValor += $total;
                $lineas[] = ["fecha" => $mov->fecha, "tipo" => 1, "motivo" => $mov->motivo,
                    "id_movimiento"   => $mov->id_movimientos_productos,
                    "id_referencia"   => $mov->id_referencia,
                    "tipo_referencia" => $mov->tipo_referencia,
                    "tdoc"            => self::tdocSunat($mov->tipo_referencia),
                    "tipo_op"         => self::tipoOpSunat($mov->tipo_referencia),
                    "usuario" => $mov->usuario,
                    "entrada_cant" => $cant, "entrada_cu" => $cu, "entrada_total" => $total,
                    "salida_cant" => null, "salida_cu" => null, "salida_total" => null,
                    "saldo_cant" => $saldoCant, "saldo_valor" => $saldoValor];
            } else {
                $saldoCant  -= $cant;  $saldoValor  -= $total;
                $totalSCant += $cant;  $totalSValor += $total;
                $lineas[] = ["fecha" => $mov->fecha, "tipo" => 2, "motivo" => $mov->motivo,
                    "id_movimiento"   => $mov->id_movimientos_productos,
                    "id_referencia"   => $mov->id_referencia,
                    "tipo_referencia" => $mov->tipo_referencia,
                    "tdoc"            => self::tdocSunat($mov->tipo_referencia),
                    "tipo_op"         => self::tipoOpSunat($mov->tipo_referencia),
                    "usuario" => $mov->usuario,
                    "entrada_cant" => null, "entrada_cu" => null, "entrada_total" => null,
                    "salida_cant" => $cant, "salida_cu" => $cu, "salida_total" => $total,
                    "saldo_cant" => $saldoCant, "saldo_valor" => $saldoValor];
            }
        }
        $totales = ["entrada_cant" => $totalECant, "entrada_valor" => $totalEValor,
                    "salida_cant"  => $totalSCant,  "salida_valor"  => $totalSValor,
                    "saldo_cant"   => $saldoCant,   "saldo_valor"   => $saldoValor];

        return [$lineas, $saldoInicial, $totales];
    }

    private function calcularKardexResumido(?int $idFamilia, ?int $idPro, ?string $desde, ?string $hasta, ?int $idEmpresa, ?int $idSucursal, ?int $idAlmacen): array
    {
        $q = \Illuminate\Support\Facades\DB::table('movimientos_productos_detalle as mpd')
            ->join('movimientos_productos as mp', 'mp.id_movimientos_productos', '=', 'mpd.id_movimientos_productos')
            ->join('productos as p', 'p.id_pro', '=', 'mpd.id_pro')
            ->join('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->join('familias as f', 'f.id_fa', '=', 'c.id_fa')
            ->where('mp.movimientos_productos_estado', 1)
            ->where('p.pro_estado', 1);

        if ($idFamilia > 0) {
            $q->where('f.id_fa', $idFamilia);
        }
        if ($idPro > 0) {
            $q->where('mpd.id_pro', $idPro);
        }
        if ($idAlmacen > 0) {
            $q->where('mp.id_almacen', $idAlmacen);
        } elseif ($idSucursal > 0) {
            $q->where('mp.id_sucursal', $idSucursal);
        } elseif ($idEmpresa > 0) {
            $q->join('sucursals as s_res', 's_res.id_sucursal', '=', 'mp.id_sucursal')
              ->where('s_res.id_empresa', $idEmpresa);
        }

        $resultados = $q->selectRaw("
            mpd.id_pro,
            p.pro_codigo,
            p.pro_nombre,
            f.id_fa,
            f.fa_nombre,
            SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 1
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) -
            SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 2
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS saldo_ini_cant,
            SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 1
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) -
            SUM(CASE WHEN mp.movimientos_productos_fecha < ? AND mp.movimientos_productos_tipo = 2
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS saldo_ini_valor,
            SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 1
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS ingresos_cant,
            SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 1
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS ingresos_valor,
            SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 2
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) ELSE 0 END) AS egresos_cant,
            SUM(CASE WHEN mp.movimientos_productos_fecha BETWEEN ? AND ? AND mp.movimientos_productos_tipo = 2
                THEN CAST(mpd.movimientos_productos_detalle_cantidad AS DECIMAL(14,4)) * mpd.costo_unitario ELSE 0 END) AS egresos_valor
        ", [
            $desde, $desde,
            $desde, $desde,
            $desde, $hasta,
            $desde, $hasta,
            $desde, $hasta,
            $desde, $hasta,
        ])
        ->groupBy('mpd.id_pro', 'p.pro_codigo', 'p.pro_nombre', 'f.id_fa', 'f.fa_nombre')
        ->orderBy('f.fa_nombre')
        ->orderBy('p.pro_nombre')
        ->get();

        $grupos = [];
        foreach ($resultados as $r) {
            $saldoIniCant  = (float) $r->saldo_ini_cant;
            $saldoIniValor = (float) $r->saldo_ini_valor;
            $ingresosCant  = (float) $r->ingresos_cant;
            $ingresosValor = (float) $r->ingresos_valor;
            $egresosCant   = (float) $r->egresos_cant;
            $egresosValor  = (float) $r->egresos_valor;

            if ($saldoIniCant == 0 && $ingresosCant == 0 && $egresosCant == 0) continue;

            $saldoFinalCant  = $saldoIniCant  + $ingresosCant  - $egresosCant;
            $saldoFinalValor = $saldoIniValor + $ingresosValor - $egresosValor;
            $cu = $saldoFinalCant != 0 ? $saldoFinalValor / $saldoFinalCant : 0;

            $famNombre = $r->fa_nombre;
            if (!isset($grupos[$famNombre])) {
                $grupos[$famNombre] = ['familia' => $famNombre, 'productos' => [], 'total_ct' => 0.0];
            }
            $grupos[$famNombre]['productos'][] = [
                'codigo'           => $r->pro_codigo,
                'nombre'           => $r->pro_nombre,
                'saldo_ini_cant'   => $saldoIniCant,
                'ingresos_cant'    => $ingresosCant,
                'egresos_cant'     => $egresosCant,
                'saldo_final_cant' => $saldoFinalCant,
                'c_u'              => $cu,
                'c_t'              => $saldoFinalValor,
            ];
            $grupos[$famNombre]['total_ct'] += $saldoFinalValor;
        }

        return array_values($grupos);
    }

    private static function tdocSunat(?string $tipoRef): string
    {
        return match(strtolower((string) $tipoRef)) {
            'compra'              => '01',
            'transferencia'       => '09',
            'merma_transferencia' => '09',
            'nota'                => '07',
            'nc_compra'           => '07',
            'nd_compra'           => '08',
            default               => '00',
        };
    }

    private static function tipoOpSunat(?string $tipoRef): string
    {
        return match(strtolower((string) $tipoRef)) {
            'compra'              => '02',
            'merma_compra'        => '13',
            'anulacion_compra'    => '05',
            'nc_compra'           => '05',
            'nd_compra'           => '02',
            'transferencia'       => '11',
            'merma_transferencia' => '13',
            'inventario'          => '99',
            'autoconsumo'         => '10',
            'nota'                => '01',
            default               => '99',
        };
    }

    // ── PDF Transferencia ─────────────────────────────────────────
    public function transferencia_pdf()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) abort(404);

        $trf = DB::table('transferencias_stock as t')
            ->leftJoin('almacen as ao',  'ao.id_almacen',  '=', 't.id_almacen_origen')
            ->leftJoin('tiendas as tor', 'tor.id_tienda',  '=', 't.id_tienda_origen')
            ->leftJoin('tiendas as td',  'td.id_tienda',   '=', 't.id_tienda_destino')
            ->leftJoin('almacen as ad',  'ad.id_almacen',  '=', 't.id_almacen_destino')
            ->leftJoin('empresa as eo',  'eo.id_empresa',  '=', 'ao.id_empresa')
            ->leftJoin('empresa as eo2', 'eo2.id_empresa', '=', 'tor.id_empresa')
            ->leftJoin('empresa as ed',  'ed.id_empresa',  '=', 'td.id_empresa')
            ->leftJoin('empresa as ead', 'ead.id_empresa', '=', 'ad.id_empresa')
            ->leftJoin('ubigeo as ubo',  'ubo.id_ubigeo',  '=', DB::raw('COALESCE(eo.id_ubigeo, eo2.id_ubigeo)'))
            ->leftJoin('ubigeo as ubd',  'ubd.id_ubigeo',  '=', DB::raw('COALESCE(ed.id_ubigeo, ead.id_ubigeo)'))
            ->join('users as u',         'u.id_users',     '=', 't.id_users')
            ->select(
                't.*',
                DB::raw("COALESCE(ao.almacen_nombre, tor.tienda_nombre, '—') as origen_nombre_corto"),
                DB::raw("COALESCE(ao.almacen_direccion, tor.tienda_direccion, '') as origen_direccion"),
                DB::raw("COALESCE(td.tienda_nombre, ad.almacen_nombre, '—') as destino_nombre_corto"),
                DB::raw("COALESCE(td.tienda_direccion, ad.almacen_direccion, '') as destino_direccion"),
                DB::raw('COALESCE(eo.id_empresa, eo2.id_empresa) as id_empresa_trf'),
                DB::raw('COALESCE(ed.empresa_ruc, ead.empresa_ruc) as destino_ruc'),
                DB::raw('COALESCE(ed.empresa_razon_social, ead.empresa_razon_social, td.tienda_nombre, ad.almacen_nombre) as destino_razon_social'),
                DB::raw("CONCAT_WS(' - ', ubo.ubigeo_distrito, ubo.ubigeo_provincia, ubo.ubigeo_departamento) as origen_ubigeo"),
                DB::raw("CONCAT_WS(' - ', ubd.ubigeo_distrito, ubd.ubigeo_provincia, ubd.ubigeo_departamento) as destino_ubigeo"),
                'u.nombre_users',
            )
            ->where('t.id_transferencia', $id)->first();

        if (!$trf) abort(404);

        $emp = $trf->id_empresa_trf
            ? DB::table('empresa as e')
                ->leftJoin('ubigeo as ub', 'ub.id_ubigeo', '=', 'e.id_ubigeo')
                ->where('e.id_empresa', $trf->id_empresa_trf)
                ->select('e.*', 'ub.ubigeo_distrito', 'ub.ubigeo_provincia', 'ub.ubigeo_departamento')
                ->first()
            : null;

        // Sedes: si el origen es una tienda, excluirla; si es almacén, mostrar todas
        $sedesQuery = DB::table('tiendas')
            ->where('id_empresa', $trf->id_empresa_trf ?? 0)
            ->where('tienda_estado', '!=', 0)
            ->whereIn('tienda_tipo', [1, 2]);
        if (!empty($trf->id_tienda_origen)) {
            $sedesQuery->where('id_tienda', '!=', $trf->id_tienda_origen);
        }
        $sedesTrf = $sedesQuery->orderBy('tienda_tipo')->orderBy('id_tienda')->get();

        $items = DB::table('transferencias_stock_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_transferencia', $id)
            ->select('p.pro_nombre', 'p.pro_codigo', 'd.detalle_cantidad', 'd.detalle_cantidad_recibida')
            ->get();

        $serieTRF   = $trf->id_empresa_trf
            ? DB::table('serie')->where('id_empresa', $trf->id_empresa_trf)->where('tipocomp', 'T')->first()
            : null;
        $guiaSerie  = $serieTRF ? $serieTRF->serie : 'T001';
        $guiaNumero = $guiaSerie . '-' . str_pad((int) $trf->id_transferencia, 8, '0', STR_PAD_LEFT);

        $pdf = new PDFBufeo('P', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->AliasNbPages();

        $xL    = 12;
        $W     = 186;
        $lineH = 4.5;

        // ═══════════════════════════════════════════════════════════════
        // HEADER: Logo | Datos empresa | Caja RUC/Tipo/Número
        // ═══════════════════════════════════════════════════════════════
        $headerH = 38;
        $rightW  = 58;
        $xRight  = $xL + $W - $rightW;
        $yTop    = 12;
        $rowH    = $headerH / 3;

        // Caja derecha (3 filas con bordes)
        $pdf->SetDrawColor(30, 30, 30);
        $pdf->SetLineWidth(0.6);
        $pdf->RoundedRect($xRight, $yTop, $rightW, $headerH, 2);
        $pdf->Line($xRight, $yTop + $rowH,     $xRight + $rightW, $yTop + $rowH);
        $pdf->Line($xRight, $yTop + $rowH * 2, $xRight + $rightW, $yTop + $rowH * 2);
        $pdf->SetLineWidth(0.2);

        $pdf->SetFillColor(225, 225, 225);
        $pdf->Rect($xRight + 0.3, $yTop + $rowH + 0.3, $rightW - 0.6, $rowH - 0.6, 'F');

        $pdf->SetTextColor(0, 0, 0);

        // Fila 1: RUC
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($xRight, $yTop + 2);
        $pdf->Cell($rightW, $rowH - 4, 'RUC N' . chr(186) . ' ' . ($emp->empresa_ruc ?? ''), 0, 0, 'C');

        // Fila 2: Tipo documento
        $pdf->SetFont('Helvetica', 'B', 6.5);
        $pdf->SetXY($xRight, $yTop + $rowH + 2);
        $pdf->Cell($rightW, 4, utf8_decode('GUÍA DE REMISIÓN ELECTRÓNICA'), 0, 1, 'C');
        $pdf->SetXY($xRight, $yTop + $rowH + 7);
        $pdf->Cell($rightW, 4, 'REMITENTE', 0, 0, 'C');

        // Fila 3: Número
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY($xRight, $yTop + $rowH * 2 + 3);
        $pdf->Cell($rightW, $rowH - 5, $guiaNumero, 0, 0, 'C');

        // Logo empresa (izquierda)
        $logoW = 0;
        if ($emp && !empty($emp->empresa_foto)) {
            $logoPath = public_path($emp->empresa_foto);
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, $xL, $yTop + 2, 38, 0);
                $logoW = 40;
            }
        }

        // Datos empresa (centro)
        $infoX = $xL + $logoW + 2;
        $infoW = $xRight - $infoX - 4;
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($infoX, $yTop + 2);
        $pdf->Cell($infoW, 5, utf8_decode($emp->empresa_razon_social ?? ''), 0, 1, 'C');

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($infoX, $pdf->GetY() + 1);
        $pdf->MultiCell($infoW, 4, utf8_decode($emp->empresa_domiciliofiscal ?? ''), 0, 'C');

        $cityParts = array_filter([
            $emp->ubigeo_distrito     ?? '',
            $emp->ubigeo_provincia    ?? '',
            $emp->ubigeo_departamento ?? '',
        ]);
        if ($cityParts) {
            $pdf->SetXY($infoX, $pdf->GetY());
            $pdf->Cell($infoW, 4, utf8_decode(implode(' - ', $cityParts)), 0, 1, 'C');
        }
        if (!empty($emp->empresa_correo)) {
            $pdf->SetXY($infoX, $pdf->GetY());
            $pdf->Cell($infoW, 4, $emp->empresa_correo, 0, 1, 'C');
        }
        $phone = trim(($emp->empresa_telefono1 ?? '') . ($emp->empresa_telefono2 ? '  ' . $emp->empresa_telefono2 : ''));
        if ($phone) {
            $pdf->SetXY($infoX, $pdf->GetY());
            $pdf->Cell($infoW, 4, $phone, 0, 1, 'C');
        }

        foreach ($sedesTrf as $sede) {
            $sedeTexto = $sede->tienda_nombre;
            if (!empty($sede->tienda_direccion)) {
                $sedeTexto .= ' - ' . $sede->tienda_direccion;
            }
            $pdf->SetXY($infoX, $pdf->GetY());
            $pdf->SetFont('Helvetica', '', 6.5);
            $pdf->Cell($infoW, 3.5, utf8_decode($sedeTexto), 0, 1, 'C');
        }

        // Línea bajo header
        $pdf->SetDrawColor(30, 30, 30);
        $pdf->SetLineWidth(0.8);
        $pdf->Line($xL, $yTop + $headerH + 2, $xL + $W, $yTop + $headerH + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);

        // ═══════════════════════════════════════════════════════════════
        // DATOS GENERALES
        // ═══════════════════════════════════════════════════════════════
        $pdf->SetY($yTop + $headerH + 6);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetX($xL);
        $pdf->Cell($W, 5, 'DATOS GENERALES', 0, 1, 'L');

        $colL = round($W * 0.50);
        $colR = $W - $colL;
        $xR   = $xL + $colL;
        $yG   = $pdf->GetY() + 1;

        // ── Punto de partida (izq) ─────────────────────────────────────
        $pdf->SetXY($xL, $yG);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colL, $lineH, utf8_decode('Punto de partida:'), 0, 1, 'L');
        $pdf->SetXY($xL, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $puntoPartida = !empty($emp->empresa_domiciliofiscal)
            ? mb_strtoupper($emp->empresa_domiciliofiscal)
            : mb_strtoupper($trf->origen_direccion ?? $trf->origen_nombre_corto ?? '');
        $pdf->Cell($colL, $lineH, utf8_decode($puntoPartida), 0, 1, 'L');
        if ($cityParts) {
            $pdf->SetXY($xL, $pdf->GetY());
            $pdf->Cell($colL, $lineH, utf8_decode(mb_strtoupper(implode(' - ', $cityParts))), 0, 1, 'L');
        }
        $yMotivoL = $pdf->GetY() + 1;

        $pdf->SetXY($xL, $yMotivoL);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colL, $lineH, 'Motivo Traslado:', 0, 1, 'L');
        $pdf->SetXY($xL, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colL, $lineH, utf8_decode($trf->transferencia_motivo ?: 'Traslado entre establecimientos'), 0, 1, 'L');

        $yFechaL = $pdf->GetY() + 1;
        $pdf->SetXY($xL, $yFechaL);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(28, $lineH, utf8_decode('Fecha de emisión:'), 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colL - 28, $lineH, date('d/m/Y', strtotime($trf->transferencia_fecha)), 0, 1, 'L');
        $yGLeftEnd = $pdf->GetY();

        // ── Punto de llegada (der) ─────────────────────────────────────
        $pdf->SetXY($xR, $yG);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colR, $lineH, utf8_decode('Punto de llegada:'), 0, 1, 'L');
        $pdf->SetXY($xR, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $puntoLlegada = !empty($trf->destino_direccion)
            ? mb_strtoupper($trf->destino_direccion)
            : mb_strtoupper($trf->destino_nombre_corto ?? '');
        $pdf->Cell($colR, $lineH, utf8_decode($puntoLlegada), 0, 1, 'L');
        if (!empty($trf->destino_ubigeo)) {
            $pdf->SetXY($xR, $pdf->GetY());
            $pdf->Cell($colR, $lineH, utf8_decode(mb_strtoupper($trf->destino_ubigeo)), 0, 1, 'L');
        }

        $yMotivoR = max($pdf->GetY(), $yMotivoL) + 1;
        $pdf->SetXY($xR, $yMotivoR);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colR, $lineH, utf8_decode('Modalidad de Transporte:'), 0, 1, 'L');
        $pdf->SetXY($xR, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colR, $lineH, 'Transporte privado', 0, 1, 'L');

        $yFechaR = max($pdf->GetY(), $yFechaL) + 1;
        $pdf->SetXY($xR, $yFechaR);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(28, $lineH, 'Fecha de traslado:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colR - 28, $lineH, date('d/m/Y', strtotime($trf->transferencia_fecha)), 0, 1, 'L');
        $yGRightEnd = $pdf->GetY();

        $ySep1 = max($yGLeftEnd, $yGRightEnd) + 3;
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($xL, $ySep1, $xL + $W, $ySep1);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);

        // ═══════════════════════════════════════════════════════════════
        // DATOS DEL DESTINATARIO
        // ═══════════════════════════════════════════════════════════════
        $pdf->SetY($ySep1 + 4);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetX($xL);
        $pdf->Cell($W, 5, 'DATOS DEL DESTINATARIO', 0, 1, 'L');
        $pdf->Ln(1);

        $yD = $pdf->GetY();

        // Columna izquierda
        $pdf->SetXY($xL, $yD);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(38, $lineH, utf8_decode('Nombre o razón social:'), 0, 1, 'L');
        $pdf->SetXY($xL, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $razonSocDest = mb_strtoupper($trf->destino_razon_social ?? $trf->destino_nombre_corto ?? '');
        $pdf->Cell($colL, $lineH, utf8_decode($razonSocDest), 0, 1, 'L');

        $pdf->SetXY($xL, $pdf->GetY());
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(20, $lineH, utf8_decode('Dirección:'), 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $dirDest = mb_strtoupper($trf->destino_direccion ?? '');
        $pdf->Cell($colL - 20, $lineH, utf8_decode($dirDest), 0, 1, 'L');

        if (!empty($trf->destino_ubigeo)) {
            $pdf->SetXY($xL + 20, $pdf->GetY());
            $pdf->Cell($colL - 20, $lineH, utf8_decode(mb_strtoupper($trf->destino_ubigeo)), 0, 1, 'L');
        }

        $pdf->SetXY($xL, $pdf->GetY());
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(30, $lineH, utf8_decode('Número de bultos:'), 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $nBultos = count($items);
        $pdf->Cell($colL - 30, $lineH, $nBultos . ' ' . ($nBultos === 1 ? 'bulto' : 'bultos'), 0, 1, 'L');
        $yDLeftEnd = $pdf->GetY();

        // Columna derecha
        $pdf->SetXY($xR, $yD);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colR, $lineH, utf8_decode('Tipo y número de identificación:'), 0, 1, 'L');
        $pdf->SetXY($xR, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colR, $lineH, !empty($trf->destino_ruc) ? 'RUC: ' . $trf->destino_ruc : utf8_decode('—'), 0, 1, 'L');

        $pdf->SetXY($xR, $pdf->GetY() + 2);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell($colR, $lineH, 'Peso bruto total:', 0, 1, 'L');
        $pdf->SetXY($xR, $pdf->GetY());
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($colR, $lineH, '0.000 KGM', 0, 1, 'L');
        $yDRightEnd = $pdf->GetY();

        $ySep2 = max($yDLeftEnd, $yDRightEnd) + 3;
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($xL, $ySep2, $xL + $W, $ySep2);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);

        // ═══════════════════════════════════════════════════════════════
        // DATOS DEL TRANSPORTE Y TRASLADO
        // ═══════════════════════════════════════════════════════════════
        $pdf->SetY($ySep2 + 4);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetX($xL);
        $pdf->Cell($W, 5, 'DATOS DEL TRANSPORTE Y TRASLADO', 0, 1, 'L');
        $pdf->Ln(1);

        $yTrans  = $pdf->GetY();
        $cTL     = round($W * 0.60);
        $cTR     = $W - $cTL;
        $xTR     = $xL + $cTL;
        $lblW    = 42;

        $transFields = [
            [utf8_decode('Número de placa del vehículo:')],
            [utf8_decode('Modelo del vehículo:')],
            ['Nombre Conductor:'],
            ['Licencia del conductor:'],
        ];
        $yTC = $yTrans;
        foreach ($transFields as [$lbl]) {
            $pdf->SetXY($xL, $yTC);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->Cell($lblW, $lineH, $lbl, 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell($cTL - $lblW, $lineH, '', 0, 1, 'L');
            $yTC += $lineH + 1;
        }

        $pdf->SetXY($xTR, $yTrans);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(34, $lineH, 'Documento Conductor:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($cTR - 34, $lineH, '', 0, 1, 'L');
        $yTransEnd = max($yTC, $pdf->GetY());

        $ySep3 = $yTransEnd + 3;
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($xL, $ySep3, $xL + $W, $ySep3);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);

        // ═══════════════════════════════════════════════════════════════
        // TABLA DE BIENES
        // Cols: N° | Código | Descripción | Unidad | Cantidad | Peso
        // ═══════════════════════════════════════════════════════════════
        $pdf->SetY($ySep3 + 4);

        $pCols   = [10, 22, 96, 18, 22, 18];
        $pHeads  = [utf8_decode('N°'), utf8_decode('Código'), utf8_decode('Descripción'), 'Unidad', 'Cantidad', 'Peso'];
        $pAligns = ['C', 'C', 'L', 'C', 'C', 'C'];

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetX($xL);
        foreach ($pHeads as $i => $h) {
            $pdf->Cell($pCols[$i], 7, $h, 1, 0, $pAligns[$i], true);
        }
        $pdf->Ln();

        $fill = false;
        foreach ($items as $idx => $item) {
            $pdf->CheckPageBreak(6);
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 247 : 255, $fill ? 247 : 255);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetX($xL);
            $pdf->Cell($pCols[0], 6, $idx + 1,                                                           'LRB', 0, 'C', $fill);
            $pdf->Cell($pCols[1], 6, $item->pro_codigo ?? '',                                             'LRB', 0, 'C', $fill);
            $pdf->Cell($pCols[2], 6, utf8_decode(mb_substr(mb_strtoupper($item->pro_nombre), 0, 65)),     'LRB', 0, 'L', $fill);
            $pdf->Cell($pCols[3], 6, 'UND',                                                               'LRB', 0, 'C', $fill);
            $pdf->Cell($pCols[4], 6, number_format((float) $item->detalle_cantidad, 2),                   'LRB', 0, 'C', $fill);
            $pdf->Cell($pCols[5], 6, '0.00 KGM',                                                          'LRB', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }

        // ═══════════════════════════════════════════════════════════════
        // QR + PIE
        // ═══════════════════════════════════════════════════════════════
        $pdf->SetY($pdf->GetY() + 6);
        $qrSize = 32;
        $yQR    = $pdf->GetY();

        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($xL, $yQR, $qrSize, $qrSize);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Helvetica', 'I', 5.5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY($xL, $yQR + $qrSize / 2 - 2);
        $pdf->Cell($qrSize, 4, utf8_decode('Código QR'), 0, 0, 'C');

        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFont('Helvetica', 'I', 6);
        $pdf->SetXY($xL + $qrSize + 4, $yQR + 6);
        $pdf->Cell($W - $qrSize - 4, 4, utf8_decode('Representación impresa de la Guía de Remisión Electrónica - Remitente'), 0, 1, 'L');
        $pdf->SetXY($xL + $qrSize + 4, $pdf->GetY() + 2);
        $pdf->Cell($W - $qrSize - 4, 4, 'Documento generado por Sistema ASSU  |  ' . date('d/m/Y H:i:s'), 0, 0, 'L');

        $filename = 'Guia-Remision-' . $guiaNumero . '.pdf';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $pdf->Output('S', $filename);
        exit;
    }

}