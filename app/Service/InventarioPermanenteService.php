<?php

namespace App\Service;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lógica del Kardex / Inventario Permanente compartida entre el controlador
 * (Excel/PDF) y el componente Livewire (vista previa).
 */
class InventarioPermanenteService
{
    /**
     * Resuelve el rango de fechas y su etiqueta según el modo (fecha | periodo).
     * @return array{0:Carbon,1:Carbon,2:string}
     */
    public function rango(array $p): array
    {
        if (($p['modo'] ?? 'fecha') === 'periodo') {
            $mes  = (int) ($p['mes']  ?? now()->format('n'));
            $anio = (int) ($p['anio'] ?? now()->format('Y'));
            $ini  = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin  = (clone $ini)->endOfMonth();
            $etiqueta = 'Periodo: ' . $ini->format('m/Y');
        } else {
            $ini = !empty($p['desde']) ? Carbon::parse($p['desde']) : now()->startOfMonth();
            $fin = !empty($p['hasta']) ? Carbon::parse($p['hasta']) : now();
            $etiqueta = 'Del ' . $ini->format('d/m/Y') . ' al ' . $fin->format('d/m/Y');
        }
        return [$ini, $fin, $etiqueta];
    }

    /**
     * Devuelve filas planas del kardex:
     * fecha, codigo, producto, motivo, ent_cant, sal_cant, costo_uni,
     * ent_val, sal_val, saldo_cant, saldo_val.
     */
    public function filas(array $p): array
    {
        [$ini, $fin] = $this->rango($p);
        $famId       = (int) ($p['familia']   ?? 0);
        $catId       = (int) ($p['categoria'] ?? 0);
        $acumulado   = ($p['tipo'] ?? '') === 'acumulado_dia';
        $existencias = $p['existencias'] ?? 'todos';

        $productos = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->where('p.pro_estado', 1)
            ->when($famId > 0, fn($q) => $q->where('c.id_fa', $famId))
            ->when($catId > 0, fn($q) => $q->where('p.id_ca', $catId))
            ->select('p.id_pro', 'p.pro_codigo', 'p.pro_nombre', 'p.pro_costo_total')
            ->orderBy('p.pro_nombre')->get();

        $ids = $productos->pluck('id_pro')->all();
        if (empty($ids)) return [];

        $cantExpr = 'CAST(d.movimientos_productos_detalle_cantidad AS DECIMAL(18,4))';

        // Stock real actual por producto (suma de todas las sucursales)
        $stockActual = DB::table('producto_sucursal')
            ->whereIn('id_pro', $ids)
            ->select('id_pro', DB::raw('SUM(ps_stock) as stock'))
            ->groupBy('id_pro')->pluck('stock', 'id_pro');

        // Neto de movimientos DESDE el inicio del rango (para anclar el saldo inicial
        // al stock real: saldo_inicial = stock_actual - neto(movs con fecha >= inicio))
        $netRows = DB::table('movimientos_productos_detalle as d')
            ->join('movimientos_productos as m', 'm.id_movimientos_productos', '=', 'd.id_movimientos_productos')
            ->whereIn('d.id_pro', $ids)
            ->where('d.movimientos_productos_detalle_estado', '1')
            ->where('m.movimientos_productos_estado', 1)
            ->whereDate('m.movimientos_productos_fecha', '>=', $ini->toDateString())
            ->select('d.id_pro', 'm.movimientos_productos_tipo as tipo', DB::raw("SUM($cantExpr) as cant"))
            ->groupBy('d.id_pro', 'm.movimientos_productos_tipo')->get();

        $netDesdeIni = [];
        foreach ($netRows as $r) {
            $signo = ((int) $r->tipo === 1) ? 1 : -1;
            $netDesdeIni[$r->id_pro] = ($netDesdeIni[$r->id_pro] ?? 0) + $signo * (float) $r->cant;
        }

        $saldoIniCant = []; $saldoIniVal = [];
        foreach ($productos as $prod) {
            $stk   = (float) ($stockActual[$prod->id_pro] ?? 0);
            $ini_c = round($stk - (float) ($netDesdeIni[$prod->id_pro] ?? 0), 4);
            $saldoIniCant[$prod->id_pro] = $ini_c;
            $saldoIniVal[$prod->id_pro]  = round($ini_c * (float) ($prod->pro_costo_total ?? 0), 4);
        }

        // Movimientos dentro del rango
        $movs = DB::table('movimientos_productos_detalle as d')
            ->join('movimientos_productos as m', 'm.id_movimientos_productos', '=', 'd.id_movimientos_productos')
            ->whereIn('d.id_pro', $ids)
            ->where('d.movimientos_productos_detalle_estado', '1')
            ->where('m.movimientos_productos_estado', 1)
            ->whereBetween(DB::raw('DATE(m.movimientos_productos_fecha)'), [$ini->toDateString(), $fin->toDateString()])
            ->select('d.id_pro', 'm.movimientos_productos_fecha as fecha', 'm.movimientos_productos_tipo as tipo',
                'm.movimientos_productos_motivo as motivo',
                DB::raw("$cantExpr as cant"), 'd.costo_unitario')
            ->orderBy('m.movimientos_productos_fecha')
            ->orderBy('m.id_movimientos_productos')
            ->get()
            ->groupBy('id_pro');

        $filas = [];
        foreach ($productos as $prod) {
            $movsPro   = $movs->get($prod->id_pro, collect());
            $tieneMovs = $movsPro->isNotEmpty();
            $saldoC    = (float) ($saldoIniCant[$prod->id_pro] ?? 0);
            $saldoV    = (float) ($saldoIniVal[$prod->id_pro]  ?? 0);

            $filasPro = [];

            if ($acumulado) {
                $porDia = $movsPro->groupBy(fn($m) => date('Y-m-d', strtotime($m->fecha)));
                foreach ($porDia as $dia => $items) {
                    $entC = 0; $salC = 0; $entV = 0; $salV = 0;
                    foreach ($items as $it) {
                        $c = (float) $it->cant; $v = $c * (float) $it->costo_unitario;
                        if ((int) $it->tipo === 1) { $entC += $c; $entV += $v; }
                        else                        { $salC += $c; $salV += $v; }
                    }
                    $saldoC += $entC - $salC;
                    $saldoV += $entV - $salV;
                    $filasPro[] = [
                        'fecha'      => $dia, 'codigo' => $prod->pro_codigo, 'producto' => $prod->pro_nombre,
                        'motivo'     => '', 'ent_cant' => $entC, 'sal_cant' => $salC, 'costo_uni' => 0,
                        'ent_val'    => $entV, 'sal_val' => $salV, 'saldo_cant' => $saldoC, 'saldo_val' => $saldoV,
                    ];
                }
            } else {
                foreach ($movsPro as $it) {
                    $c = (float) $it->cant; $cu = (float) $it->costo_unitario; $v = $c * $cu;
                    $esEnt = (int) $it->tipo === 1;
                    $saldoC += $esEnt ? $c : -$c;
                    $saldoV += $esEnt ? $v : -$v;
                    $filasPro[] = [
                        'fecha'      => date('Y-m-d', strtotime($it->fecha)), 'codigo' => $prod->pro_codigo, 'producto' => $prod->pro_nombre,
                        'motivo'     => $it->motivo ?: '—', 'ent_cant' => $esEnt ? $c : 0, 'sal_cant' => $esEnt ? 0 : $c,
                        'costo_uni'  => $cu, 'ent_val' => $esEnt ? $v : 0, 'sal_val' => $esEnt ? 0 : $v,
                        'saldo_cant' => $saldoC, 'saldo_val' => $saldoV,
                    ];
                }
            }

            $incluir = match ($existencias) {
                'con_saldo_positivo' => $saldoC > 0.0001,
                'con_saldo_negativo' => $saldoC < -0.0001,
                'sin_movimientos'    => !$tieneMovs,
                'con_movimientos'    => $tieneMovs,
                default              => true,
            };
            if (!$incluir) continue;

            if (!$tieneMovs && $existencias !== 'con_movimientos') {
                $filasPro[] = [
                    'fecha' => '', 'codigo' => $prod->pro_codigo, 'producto' => $prod->pro_nombre,
                    'motivo' => 'Sin movimientos', 'ent_cant' => 0, 'sal_cant' => 0, 'costo_uni' => 0,
                    'ent_val' => 0, 'sal_val' => 0, 'saldo_cant' => $saldoC, 'saldo_val' => $saldoV,
                ];
            }

            foreach ($filasPro as $f) $filas[] = $f;
        }

        return $filas;
    }
}
