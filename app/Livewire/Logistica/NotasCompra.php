<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class NotasCompra extends Component
{
    use WithPagination;

    // ── Vista ──────────────────────────────────────────────────
    public string $vista = 'historial';

    // ── Formulario nueva nota ──────────────────────────────────
    public string $tipo          = 'NC';
    public int    $idEmpresa     = 0;
    public int    $idProveedor   = 0;
    public string $idOrdenRef    = '';   // id_orden_compra de referencia (opcional)
    public string $numeroDoc     = '';
    public string $fechaNota     = '';
    public string $motivo        = '';
    public bool   $afectaStock   = false;
    public int    $idAlmacen     = 0;
    public string $observacion   = '';
    public array  $items         = [];
    public float  $total         = 0;

    // ── Búsqueda de productos ──────────────────────────────────
    public string $buscarProducto = '';
    public array  $resultados     = [];

    // ── Filtros historial ──────────────────────────────────────
    public string $filtroTipo      = '';
    public string $filtroEstado    = '';
    public int    $filtroEmpresa   = 0;
    public int    $filtroProveedor = 0;
    public string $filtroDesde     = '';
    public string $filtroHasta     = '';
    public int    $porPagina       = 15;

    // ── Modal acción ───────────────────────────────────────────
    public ?int   $idAccion      = null;
    public string $accionTipo    = '';   // 'aprobar' | 'anular'
    public string $motivoAccion  = '';

    protected int $cachedRoleId = 0;

    protected Logs $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        $this->cachedRoleId = (int) DB::table('model_has_roles')
            ->where('model_id', auth()->user()->id_users)
            ->value('role_id');
    }

    // ── Permisos ───────────────────────────────────────────────
    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function empresaIdDelAdmin(): ?int
    {
        return DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->value('s.id_empresa');
    }

    // ── Navegación ────────────────────────────────────────────
    public function nuevaNota(): void
    {
        $this->limpiarFormulario();
        $this->fechaNota = now()->toDateString();
        $this->vista     = 'nueva';
    }

    public function volverHistorial(): void
    {
        $this->limpiarFormulario();
        $this->vista = 'historial';
        $this->resetPage();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'tipo', 'idEmpresa', 'idProveedor', 'idOrdenRef', 'numeroDoc',
            'fechaNota', 'motivo', 'afectaStock', 'idAlmacen', 'observacion',
            'items', 'total', 'buscarProducto', 'resultados',
            'idAccion', 'accionTipo', 'motivoAccion',
        ]);
        $this->tipo = 'NC';
        $this->resetErrorBag();
    }

    // ── Búsqueda de productos ──────────────────────────────────
    public function updatedBuscarProducto(): void
    {
        $q = trim($this->buscarProducto);
        if (strlen($q) < 2) { $this->resultados = []; return; }

        $idAlm = $this->idAlmacen > 0 ? $this->idAlmacen : null;

        $this->resultados = DB::table('productos as p')
            ->leftJoin('almacen_producto as ap', function ($j) use ($idAlm) {
                $j->on('ap.id_pro', '=', 'p.id_pro');
                if ($idAlm) $j->where('ap.id_almacen', $idAlm);
            })
            ->where('p.pro_estado', 1)
            ->where(fn($sq) => $sq
                ->where('p.pro_nombre', 'like', "%{$q}%")
                ->orWhere('p.pro_codigo', 'like', "%{$q}%"))
            ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                DB::raw('COALESCE(SUM(ap.ap_stock), 0) as stock'),
                'p.pro_costo_total as precio')
            ->groupBy('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'p.pro_costo_total')
            ->orderBy('p.pro_nombre')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function agregarProducto(int $idPro, string $nombre, float $precio, float $stock): void
    {
        foreach ($this->items as $it) {
            if ($it['id_pro'] === $idPro) {
                $this->addError('buscarProducto', 'Producto ya agregado.');
                return;
            }
        }
        $this->items[] = [
            'id_pro'   => $idPro,
            'nombre'   => $nombre,
            'precio'   => round($precio, 2),
            'cantidad' => 1,
            'total'    => round($precio, 2),
            'stock'    => $stock,
        ];
        $this->calcularTotal();
        $this->buscarProducto = '';
        $this->resultados     = [];
        $this->resetErrorBag('buscarProducto');
    }

    public function quitarItem(int $idx): void
    {
        array_splice($this->items, $idx, 1);
        $this->calcularTotal();
    }

    public function updatedItems(): void { $this->calcularTotal(); }

    public function calcularTotal(): void
    {
        $this->total = round(collect($this->items)->sum(fn($i) => (float) $i['precio'] * (float) $i['cantidad']), 2);
        foreach ($this->items as $k => $it) {
            $this->items[$k]['total'] = round((float) $it['precio'] * (float) $it['cantidad'], 2);
        }
    }

    // ── Guardar ────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate([
            'tipo'        => 'required|in:NC,DB',
            'idProveedor' => 'required|integer|min:1',
            'fechaNota'   => 'required|date',
            'motivo'      => 'required|string|min:5|max:500',
            'items'       => 'required|array|min:1',
        ], [
            'idProveedor.min'  => 'Selecciona un proveedor.',
            'motivo.min'       => 'El motivo debe tener al menos 5 caracteres.',
            'items.min'        => 'Agrega al menos un ítem.',
        ]);

        $this->calcularTotal();

        if ($this->total <= 0) {
            $this->addError('total', 'El total debe ser mayor a cero.');
            return;
        }

        DB::beginTransaction();
        try {
            $prefijo  = $this->tipo === 'NC' ? 'NC' : 'DB';
            $count    = DB::table('notas_compra')->where('tipo_nota', $this->tipo)->count() + 1;
            $numero   = $prefijo . '-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $idNota = DB::table('notas_compra')->insertGetId([
                'id_empresa'      => $this->idEmpresa  ?: null,
                'id_proveedores'  => $this->idProveedor,
                'id_orden_compra' => $this->idOrdenRef ? (int) $this->idOrdenRef : null,
                'id_almacen'      => ($this->afectaStock && $this->idAlmacen) ? $this->idAlmacen : null,
                'id_users'        => auth()->user()->id_users,
                'tipo_nota'       => $this->tipo,
                'nota_numero'     => $numero,
                'nota_numero_doc' => $this->numeroDoc  ?: null,
                'nota_fecha'      => $this->fechaNota,
                'nota_motivo'     => $this->motivo,
                'nota_total'      => $this->total,
                'nota_afecta_stock' => $this->afectaStock ? 1 : 0,
                'nota_estado'     => 'pendiente',
                'nota_observacion'=> $this->observacion ?: null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($this->items as $item) {
                DB::table('notas_compra_detalle')->insert([
                    'id_nota_compra'       => $idNota,
                    'id_pro'               => $item['id_pro'],
                    'detalle_descripcion'  => $item['nombre'],
                    'detalle_cantidad'     => (float) $item['cantidad'],
                    'detalle_precio'       => (float) $item['precio'],
                    'detalle_total'        => (float) $item['total'],
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->vista = 'historial';
            $this->resetPage();
            session()->flash('success', "{$prefijo} {$numero} registrada en estado pendiente. Apruébala para aplicar el impacto financiero.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al guardar la nota.');
        }
    }

    // ── Confirmar acción (modal) ───────────────────────────────
    public function abrirAccion(int $id, string $tipo): void
    {
        $this->idAccion     = $id;
        $this->accionTipo   = $tipo;
        $this->motivoAccion = '';
        $this->dispatch('abrirModalAccion');
    }

    public function ejecutarAccion(): void
    {
        if (!$this->idAccion) return;

        if ($this->accionTipo === 'anular') {
            $this->validate(['motivoAccion' => 'required|string|min:5'], [
                'motivoAccion.required' => 'Ingresa el motivo de anulación.',
                'motivoAccion.min'      => 'El motivo debe tener al menos 5 caracteres.',
            ]);
        }

        $nota = DB::table('notas_compra')->where('id_nota_compra', $this->idAccion)->first();
        if (!$nota) { $this->dispatch('cerrarModalAccion'); return; }

        DB::beginTransaction();
        try {
            if ($this->accionTipo === 'aprobar') {
                $this->aplicarNota($nota);
                DB::table('notas_compra')->where('id_nota_compra', $this->idAccion)
                    ->update(['nota_estado' => 'aprobado', 'updated_at' => now()]);
                $msg = "Nota {$nota->nota_numero} aprobada y aplicada.";
            } else {
                if ($nota->nota_estado === 'aprobado') {
                    $this->revertirNota($nota);
                }
                DB::table('notas_compra')->where('id_nota_compra', $this->idAccion)->update([
                    'nota_estado'      => 'anulado',
                    'nota_observacion' => trim(($nota->nota_observacion ?? '') . "\nAnulación: " . $this->motivoAccion),
                    'updated_at'       => now(),
                ]);
                $msg = "Nota {$nota->nota_numero} anulada.";
            }

            DB::commit();
            $this->idAccion = null;
            $this->dispatch('cerrarModalAccion');
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al procesar la acción.');
            $this->dispatch('cerrarModalAccion');
        }
    }

    // ── Lógica de aplicar NC/DB ────────────────────────────────
    private function aplicarNota(object $nota): void
    {
        $detalle = DB::table('notas_compra_detalle')
            ->where('id_nota_compra', $nota->id_nota_compra)->get();

        // ── Impacto en cuentas_pagar ──
        $cp = $nota->id_orden_compra
            ? DB::table('cuentas_pagar')
                ->where('id_orden_compra', $nota->id_orden_compra)
                ->where('cp_estado', '!=', 0)
                ->orderByDesc('id_cuenta_pagar')
                ->first()
            : null;

        if ($cp) {
            if ($nota->tipo_nota === 'NC') {
                $nuevoSaldo = max(0, round($cp->cp_saldo - $nota->nota_total, 2));
                $nuevoTotal = max(0, round($cp->cp_monto_total - $nota->nota_total, 2));
                $estado     = $nuevoSaldo <= 0 ? 3 : ($cp->cp_monto_pagado > 0 ? 2 : 1);
                DB::table('cuentas_pagar')->where('id_cuenta_pagar', $cp->id_cuenta_pagar)->update([
                    'cp_monto_total' => $nuevoTotal,
                    'cp_saldo'       => $nuevoSaldo,
                    'cp_estado'      => $estado,
                    'updated_at'     => now(),
                ]);
            } else {
                $nuevoTotal = round($cp->cp_monto_total + $nota->nota_total, 2);
                $nuevoSaldo = round($cp->cp_saldo       + $nota->nota_total, 2);
                $estado     = $cp->cp_saldo > 0 ? ($cp->cp_monto_pagado > 0 ? 2 : 1) : 1;
                DB::table('cuentas_pagar')->where('id_cuenta_pagar', $cp->id_cuenta_pagar)->update([
                    'cp_monto_total' => $nuevoTotal,
                    'cp_saldo'       => $nuevoSaldo,
                    'cp_estado'      => $estado,
                    'updated_at'     => now(),
                ]);
            }
        }

        // ── Impacto en stock ──
        if ($nota->nota_afecta_stock && $nota->id_almacen) {
            $tipoMov   = $nota->tipo_nota === 'NC' ? 2 : 1; // NC=salida, DB=entrada
            $motivoMov = ($nota->tipo_nota === 'NC' ? 'Nota de Crédito ' : 'Nota de Débito ') . $nota->nota_numero;

            $idMov = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'          => $nota->nota_fecha,
                'id_users'                             => auth()->user()->id_users,
                'id_almacen'                           => $nota->id_almacen,
                'movimientos_productos_fecha_creacion' => now(),
                'movimientos_productos_tipo'           => $tipoMov,
                'movimientos_productos_estado'         => 1,
                'movimientos_productos_motivo'         => $motivoMov,
                'created_at'                           => now(),
                'updated_at'                           => now(),
            ]);

            foreach ($detalle as $item) {
                if (!$item->id_pro) continue;
                $cant = (float) $item->detalle_cantidad;

                $ap = DB::table('almacen_producto')
                    ->where('id_almacen', $nota->id_almacen)
                    ->where('id_pro', $item->id_pro)->first();

                if ($nota->tipo_nota === 'NC') {
                    // devuelve al proveedor → sale del almacén
                    if ($ap) {
                        DB::table('almacen_producto')->where('id_ap', $ap->id_ap)
                            ->update(['ap_stock' => max(0, $ap->ap_stock - $cant), 'updated_at' => now()]);
                    }
                } else {
                    // DB: llegan productos adicionales → entran al almacén
                    if ($ap) {
                        DB::table('almacen_producto')->where('id_ap', $ap->id_ap)
                            ->update(['ap_stock' => $ap->ap_stock + $cant, 'updated_at' => now()]);
                    } else {
                        DB::table('almacen_producto')->insert([
                            'id_almacen'      => $nota->id_almacen,
                            'id_pro'          => $item->id_pro,
                            'ap_stock'        => $cant,
                            'ap_precio_costo' => (float) $item->detalle_precio,
                            'ap_estado'       => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }

                DB::table('movimientos_productos_detalle')->insert([
                    'id_movimientos_productos'               => $idMov,
                    'id_pro'                                 => $item->id_pro,
                    'movimientos_productos_detalle_cantidad' => (string) $cant,
                    'costo_unitario'                         => (float) $item->detalle_precio,
                    'id_referencia'                          => $nota->id_nota_compra,
                    'tipo_referencia'                        => strtolower($nota->tipo_nota) . '_compra',
                    'movimientos_productos_detalle_estado'   => '1',
                    'created_at'                             => now(),
                    'updated_at'                             => now(),
                ]);
            }
        }
    }

    private function revertirNota(object $nota): void
    {
        $detalle = DB::table('notas_compra_detalle')
            ->where('id_nota_compra', $nota->id_nota_compra)->get();

        $cp = $nota->id_orden_compra
            ? DB::table('cuentas_pagar')
                ->where('id_orden_compra', $nota->id_orden_compra)
                ->where('cp_estado', '!=', 0)
                ->orderByDesc('id_cuenta_pagar')->first()
            : null;

        if ($cp) {
            if ($nota->tipo_nota === 'NC') {
                $nuevoTotal = round($cp->cp_monto_total + $nota->nota_total, 2);
                $nuevoSaldo = round($cp->cp_saldo       + $nota->nota_total, 2);
                DB::table('cuentas_pagar')->where('id_cuenta_pagar', $cp->id_cuenta_pagar)->update([
                    'cp_monto_total' => $nuevoTotal,
                    'cp_saldo'       => $nuevoSaldo,
                    'cp_estado'      => $cp->cp_monto_pagado > 0 ? 2 : 1,
                    'updated_at'     => now(),
                ]);
            } else {
                $nuevoTotal = max(0, round($cp->cp_monto_total - $nota->nota_total, 2));
                $nuevoSaldo = max(0, round($cp->cp_saldo       - $nota->nota_total, 2));
                DB::table('cuentas_pagar')->where('id_cuenta_pagar', $cp->id_cuenta_pagar)->update([
                    'cp_monto_total' => $nuevoTotal,
                    'cp_saldo'       => $nuevoSaldo,
                    'cp_estado'      => $nuevoSaldo <= 0 ? 3 : ($cp->cp_monto_pagado > 0 ? 2 : 1),
                    'updated_at'     => now(),
                ]);
            }
        }

        if ($nota->nota_afecta_stock && $nota->id_almacen) {
            foreach ($detalle as $item) {
                if (!$item->id_pro) continue;
                $cant = (float) $item->detalle_cantidad;
                $ap   = DB::table('almacen_producto')
                    ->where('id_almacen', $nota->id_almacen)
                    ->where('id_pro', $item->id_pro)->first();

                if ($nota->tipo_nota === 'NC' && $ap) {
                    DB::table('almacen_producto')->where('id_ap', $ap->id_ap)
                        ->update(['ap_stock' => $ap->ap_stock + $cant, 'updated_at' => now()]);
                } elseif ($nota->tipo_nota === 'DB' && $ap) {
                    DB::table('almacen_producto')->where('id_ap', $ap->id_ap)
                        ->update(['ap_stock' => max(0, $ap->ap_stock - $cant), 'updated_at' => now()]);
                }
            }
        }
    }

    // ── Render ─────────────────────────────────────────────────
    public function render()
    {
        $empresas    = DB::table('empresa')->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_nombrecomercial')->get(['id_empresa', 'empresa_nombrecomercial']);
        $proveedores = DB::table('proveedores')->where('proveedores_estado', 1)
            ->orderBy('proveedores_nombre')->get(['id_proveedores', 'proveedores_nombre', 'proveedores_numero_documento']);
        $almacenes   = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', 1)
            ->select('a.id_almacen', 'a.almacen_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.empresa_nombrecomercial')->get();

        // Órdenes de compra del proveedor seleccionado (para referencia)
        $ordenesRef = $this->idProveedor
            ? DB::table('orden_compra')
                ->where('id_proveedores', $this->idProveedor)
                ->whereIn('orden_compra_estado', ['recibido', 'en_transito'])
                ->orderByDesc('id_orden_compra')
                ->get(['id_orden_compra', 'orden_compra_numero', 'orden_compra_fecha', 'orden_compra_total'])
            : collect();

        // Historial
        $query = DB::table('notas_compra as n')
            ->join('proveedores as p', 'p.id_proveedores', '=', 'n.id_proveedores')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'n.id_empresa')
            ->leftJoin('users as u', 'u.id_users', '=', 'n.id_users')
            ->select('n.*', 'p.proveedores_nombre', 'e.empresa_nombrecomercial',
                'u.nombre_users');

        if (!$this->esSuperAdmin()) {
            $empId = $this->esAdmin() ? $this->empresaIdDelAdmin() : null;
            if ($empId) $query->where('n.id_empresa', $empId);
            else        $query->whereRaw('0=1');
        }

        if ($this->filtroTipo)      $query->where('n.tipo_nota', $this->filtroTipo);
        if ($this->filtroEstado)    $query->where('n.nota_estado', $this->filtroEstado);
        if ($this->filtroEmpresa)   $query->where('n.id_empresa', $this->filtroEmpresa);
        if ($this->filtroProveedor) $query->where('n.id_proveedores', $this->filtroProveedor);
        if ($this->filtroDesde)     $query->whereDate('n.nota_fecha', '>=', $this->filtroDesde);
        if ($this->filtroHasta)     $query->whereDate('n.nota_fecha', '<=', $this->filtroHasta);

        $notas = $query->orderByDesc('n.id_nota_compra')->paginate($this->porPagina);

        return view('livewire.logistica.notas-compra', compact(
            'empresas', 'proveedores', 'almacenes', 'ordenesRef', 'notas'
        ));
    }
}
