<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class UnirCodigos extends Component
{
    // Búsqueda
    public string $codigoReceptor = '';
    public string $codigoEliminar = '';

    // Producto seleccionado (o lista de coincidencias si el código está duplicado)
    public ?int   $receptorId = null;
    public ?int   $eliminarId = null;
    public ?array $receptor   = null;
    public ?array $eliminar   = null;
    public array  $receptorMatches = [];
    public array  $eliminarMatches = [];

    // Tablas que referencian id_pro SIN índice único → repunte directo (update)
    private array $tablasRepunte = [
        'autoconsumo_detalle', 'guias_remision_detalle',
        'inventario_detalle', 'movimientos_productos_detalle', 'notas_compra_detalle',
        'orden_compra_detalle', 'pedidos_detalle', 'proformas_detalles',
        'transferencias_stock_detalle', 'ventas_detalle',
    ];

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('unir_codigos.listar'), 403);
    }

    public function buscarReceptor(): void
    {
        [$this->receptorMatches, $this->receptorId, $this->receptor] = $this->buscar($this->codigoReceptor);
    }

    public function buscarEliminar(): void
    {
        [$this->eliminarMatches, $this->eliminarId, $this->eliminar] = $this->buscar($this->codigoEliminar);
    }

    public function seleccionarReceptor(int $id): void
    {
        $this->receptorId = $id;
        $this->receptor   = $this->cargarProducto($id);
    }

    public function seleccionarEliminar(int $id): void
    {
        $this->eliminarId = $id;
        $this->eliminar   = $this->cargarProducto($id);
    }

    /** Busca productos activos por código exacto (incluye código interno). */
    private function buscar(string $codigo): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return [[], null, null];
        }

        $ids = DB::table('productos')
            ->where('pro_estado', 1)
            ->where(function ($q) use ($codigo) {
                $q->where('pro_codigo', $codigo)->orWhere('pro_codigo_interno', $codigo);
            })
            ->orderBy('id_pro')
            ->pluck('id_pro')
            ->toArray();

        if (empty($ids)) {
            return [[], null, null];
        }

        $matches = array_map(fn($id) => $this->cargarProducto($id), $ids);

        // Si hay 1 coincidencia, se selecciona sola; si hay varias, el usuario elige.
        if (count($ids) === 1) {
            return [$matches, (int) $ids[0], $matches[0]];
        }
        return [$matches, null, null];
    }

    private function cargarProducto(int $id): array
    {
        $p = DB::table('productos as p')
            ->leftJoin('categorias as c', 'c.id_ca', '=', 'p.id_ca')
            ->leftJoin('familias as f', 'f.id_fa', '=', 'c.id_fa')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('p.id_pro', $id)
            ->select(
                'p.id_pro', 'p.pro_codigo', 'p.pro_codigo_interno', 'p.pro_nombre',
                'p.pro_costo_total', 'p.pro_precio_venta',
                'm.medida_codigo_unidad', 'm.medida_nombre',
                'f.fa_nombre', 'c.ca_nombre'
            )->first();

        $stock = (float) DB::table('producto_sucursal')->where('id_pro', $id)->sum('ps_stock');

        $pres = DB::table('producto_presentaciones')
            ->where('id_pro', $id)->where('pres_estado', 1)->get();

        $filas = [];
        if ($pres->count()) {
            foreach ($pres as $r) {
                $filas[] = [
                    'unidad'    => $r->pres_nombre,
                    'cant'      => (float) $r->pres_factor,
                    'costo'     => (float) $r->pres_precio_costo,
                    'publico'   => (float) $r->pres_precio_1,
                    'mayorista' => (float) $r->pres_precio_2,
                ];
            }
        } else {
            $ps = DB::table('producto_sucursal')->where('id_pro', $id)->first();
            $filas[] = [
                'unidad'    => $p->medida_nombre ?? '-',
                'cant'      => 1,
                'costo'     => (float) ($p->pro_costo_total ?? 0),
                'publico'   => (float) ($ps->ps_precio_uni ?? $p->pro_precio_venta ?? 0),
                'mayorista' => (float) ($ps->ps_precio_uni_3 ?? 0),
            ];
        }

        return [
            'id_pro'       => (int) $p->id_pro,
            'codigo'       => $p->pro_codigo,
            'nombre'       => $p->pro_nombre,
            'codigo_sunat' => $p->medida_codigo_unidad ?? '-',
            'unidad'       => $p->medida_nombre ?? '-',
            'familia'      => $p->fa_nombre ?? '-',
            'subfamilia'   => $p->ca_nombre ?? '-',
            'stock'        => $stock,
            'filas'        => $filas,
        ];
    }

    public function procesar(): void
    {
        if (!auth()->user()->can('unir_codigos.crear')) {
            session()->flash('error', 'No tienes permiso para unir códigos.');
            return;
        }

        if (!$this->receptorId || !$this->eliminarId) {
            session()->flash('error', 'Debes seleccionar el código receptor y el código a eliminar.');
            return;
        }
        if ($this->receptorId === $this->eliminarId) {
            session()->flash('error', 'El código receptor y el código a eliminar no pueden ser el mismo producto.');
            return;
        }

        $rec = $this->receptorId;
        $del = $this->eliminarId;

        DB::beginTransaction();
        try {
            // 1. Repuntar historial/referencias sin índice único (update directo)
            foreach ($this->tablasRepunte as $tabla) {
                DB::table($tabla)->where('id_pro', $del)->update(['id_pro' => $rec]);
            }

            // 1b. almacen_producto: UNIQUE (id_almacen, id_pro) → sumar stock por almacén
            foreach (DB::table('almacen_producto')->where('id_pro', $del)->get() as $row) {
                $recRow = DB::table('almacen_producto')
                    ->where('id_pro', $rec)->where('id_almacen', $row->id_almacen)->first();
                if ($recRow) {
                    DB::table('almacen_producto')->where('id_ap', $recRow->id_ap)
                        ->increment('ap_stock', (float) $row->ap_stock);
                    DB::table('almacen_producto')->where('id_ap', $row->id_ap)->delete();
                } else {
                    DB::table('almacen_producto')->where('id_ap', $row->id_ap)->update(['id_pro' => $rec]);
                }
            }

            // 1c. producto_series: UNIQUE (id_pro, numero_serie) → evitar series duplicadas
            foreach (DB::table('producto_series')->where('id_pro', $del)->get() as $row) {
                $existe = DB::table('producto_series')
                    ->where('id_pro', $rec)->where('numero_serie', $row->numero_serie)->exists();
                if ($existe) {
                    DB::table('producto_series')->where('id_producto_serie', $row->id_producto_serie)->delete();
                } else {
                    DB::table('producto_series')->where('id_producto_serie', $row->id_producto_serie)->update(['id_pro' => $rec]);
                }
            }

            // 2. Mover stock (producto_sucursal) por tienda
            $psDel = DB::table('producto_sucursal')->where('id_pro', $del)->get();
            foreach ($psDel as $row) {
                $recRow = DB::table('producto_sucursal')
                    ->where('id_pro', $rec)
                    ->where('id_tienda', $row->id_tienda)
                    ->first();

                if ($recRow) {
                    // El receptor ya existe en esa tienda: sumar stock y borrar la fila del eliminado
                    DB::table('producto_sucursal')
                        ->where('id_ps', $recRow->id_ps)
                        ->increment('ps_stock', (float) $row->ps_stock);
                    DB::table('producto_sucursal')->where('id_ps', $row->id_ps)->delete();
                } else {
                    // El receptor no existe en esa tienda: mover la fila al receptor
                    DB::table('producto_sucursal')->where('id_ps', $row->id_ps)->update(['id_pro' => $rec]);
                }
            }

            // 3. Borrar datos propios del producto eliminado
            DB::table('producto_presentaciones')->where('id_pro', $del)->delete();
            DB::table('producto_sucursal')->where('id_pro', $del)->delete();

            // 4. Eliminar el producto duplicado
            DB::table('productos')->where('id_pro', $del)->delete();

            DB::commit();

            session()->flash('success', 'Códigos unidos correctamente. Se eliminó el producto "' . ($this->eliminar['codigo'] ?? $del) . '" y su información se trasladó al receptor.');
            $this->resetTodo();
            $this->dispatch('unionProcesada');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al unir los códigos: ' . $e->getMessage());
            $this->dispatch('unionProcesada');
        }
    }

    public function resetTodo(): void
    {
        $this->reset([
            'codigoReceptor', 'codigoEliminar', 'receptorId', 'eliminarId',
            'receptor', 'eliminar', 'receptorMatches', 'eliminarMatches',
        ]);
    }

    public function render()
    {
        return view('livewire.logistica.unir-codigos');
    }
}
