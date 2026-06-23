<?php

namespace App\Livewire\GestionVentas;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Movimientos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Rol y contexto ────────────────────────────────────────
    public int $empresaSeleccionada  = 0;   // 0 = Almacén Principal, >0 = empresa específica
    public int $sucursalSeleccionada = 0;
    public $sucursalesDisponibles    = [];
    private int $cachedRoleId        = 0;

    // ── Filtros historial ─────────────────────────────────────
    public string $desde              = '';
    public string $hasta              = '';
    public int    $porPagina          = 10;
    public bool   $mostrarResultados  = false;

    // ── Formulario de nota ───────────────────────────────────
    public int    $tipo                   = 2;
    public string $concepto               = '';
    public string $motivo                 = '';
    public string $buscarProducto         = '';
    public array  $productosDisponibles   = [];
    public array  $productosSeleccionados = [];

    // ── Detalle ───────────────────────────────────────────────
    public $detalleMovimiento = null;
    public array $detalleItems = [];

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
    private function esVendedor(): bool   { return $this->cachedRoleId === 3; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('movimientos_productos.listar'), 403);

        $this->desde = date('Y-m-d');
        $this->hasta = date('Y-m-d');

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('tiendas')
                    ->where('id_empresa', $empresaId)
                    ->where('tienda_estado', 1)
                    ->orderBy('tienda_nombre')
                    ->get(['id_tienda', 'tienda_nombre']);

                if ($this->sucursalesDisponibles->count() === 1) {
                    $this->sucursalSeleccionada = $this->sucursalesDisponibles->first()->id_tienda;
                }
            }
        } elseif ($this->esVendedor()) {
            $this->sucursalSeleccionada = (int) session('sucursal_activa_id', 0);
        }
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada   = 0;
        $this->mostrarResultados      = false;
        $this->productosDisponibles   = [];
        $this->productosSeleccionados = [];

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get(['id_tienda', 'tienda_nombre'])
            : collect();

        $this->resetPage();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->mostrarResultados      = false;
        $this->productosDisponibles   = [];
        $this->productosSeleccionados = [];
        $this->buscarProducto         = '';
        $this->resetPage();
    }

    public function updatedTipo(): void
    {
        $this->productosDisponibles   = [];
        $this->productosSeleccionados = [];
        $this->buscarProducto         = '';
        $this->concepto               = '';
    }

    public function buscar(): void
    {
        $this->mostrarResultados = true;
        $this->resetPage();
    }

    public function cargarSugerenciasIniciales(): void
    {
        if (!$this->sucursalSeleccionada) {
            $this->productosDisponibles = [];
            return;
        }
        $this->productosDisponibles = $this->consultarProductos();
    }

    public function updatedBuscarProducto(): void
    {
        if (!$this->sucursalSeleccionada) {
            $this->productosDisponibles = [];
            return;
        }
        $this->productosDisponibles = $this->consultarProductos();
    }

    private function consultarProductos(): array
    {
        $termino = trim($this->buscarProducto);

        $query = DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->where('ps.id_tienda', $this->sucursalSeleccionada)
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1);

        if ($this->tipo == 2) {
            $query->where('ps.ps_stock', '>', 0);
        }

        if ($termino !== '') {
            $query->where(function ($q) use ($termino) {
                $q->where('p.pro_nombre', 'like', '%' . $termino . '%')
                  ->orWhere('p.pro_codigo', 'like', '%' . $termino . '%');
            });
        }

        return $query
            ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ps.ps_stock')
            ->orderBy('p.pro_nombre')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function updatingPorPagina(): void
    {
        $this->resetPage();
    }

    // ── Acciones formulario ───────────────────────────────────

    public function agregarProducto(int $idPro, string $nombre, float $stock): void
    {
        foreach ($this->productosSeleccionados as $item) {
            if ($item['id_pro'] == $idPro) return;
        }

        $this->productosSeleccionados[] = [
            'id_pro'     => $idPro,
            'pro_nombre' => $nombre,
            'ps_stock'   => $stock,
            'cantidad'   => 1,
        ];

        $this->buscarProducto       = '';
        $this->productosDisponibles = [];
    }

    public function quitarProducto(int $index): void
    {
        array_splice($this->productosSeleccionados, $index, 1);
        $this->productosSeleccionados = array_values($this->productosSeleccionados);
    }

    public function nuevo(): void
    {
        $this->tipo                   = 2;
        $this->concepto               = '';
        $this->motivo                 = '';
        $this->buscarProducto         = '';
        $this->productosDisponibles   = [];
        $this->productosSeleccionados = [];
        $this->resetErrorBag();
        $this->dispatch('abrirModalMovimiento');
    }

    public function guardar(): void
    {
        if (!auth()->user()->can('movimientos_productos.crear')) {
            $this->dispatch('cerrarModalMovimiento');
            session()->flash('error', 'No tienes permiso para registrar notas.');
            return;
        }

        $this->validate([
            'sucursalSeleccionada'              => 'required|integer|min:1',
            'tipo'                              => 'required|in:1,2',
            'concepto'                          => 'required|string|max:100',
            'motivo'                            => 'nullable|string|max:500',
            'productosSeleccionados'            => 'required|array|min:1',
            'productosSeleccionados.*.cantidad' => 'required|numeric|min:0.01',
        ], [
            'sucursalSeleccionada.min'              => 'Debe seleccionar una sede.',
            'productosSeleccionados.required'       => 'Debe agregar al menos un producto.',
            'productosSeleccionados.min'            => 'Debe agregar al menos un producto.',
            'concepto.required'                     => 'Debe seleccionar un concepto.',
            'productosSeleccionados.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ]);

        try {
            DB::transaction(function () {
                $idTienda = $this->sucursalSeleccionada ?: null;

                if ($this->tipo == 2) {
                    foreach ($this->productosSeleccionados as $item) {
                        $ps = DB::table('producto_sucursal')
                            ->where('id_pro', $item['id_pro'])
                            ->where('id_tienda', $idTienda)
                            ->first();

                        if (!$ps || $ps->ps_stock < $item['cantidad']) {
                            session()->flash('error', "Stock insuficiente para: {$item['pro_nombre']}");
                            return;
                        }
                    }
                }

                $movimiento = new \App\Models\Movimientos_productos();
                $movimiento->movimientos_productos_fecha          = date('Y-m-d');
                $movimiento->id_users                            = auth()->user()->id_users;
                $movimiento->movimientos_productos_fecha_creacion = now();
                $movimiento->movimientos_productos_tipo           = $this->tipo;
                $movimiento->movimientos_productos_estado         = 1;
                $movimiento->concepto                            = $this->concepto;
                $movimiento->movimientos_productos_motivo         = $this->motivo ?: null;
                $movimiento->id_sucursal                         = $idTienda;
                $movimiento->save();

                foreach ($this->productosSeleccionados as $item) {
                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'              => $movimiento->id_movimientos_productos,
                        'id_pro'                               => $item['id_pro'],
                        'movimientos_productos_detalle_cantidad' => $item['cantidad'],
                        'movimientos_productos_detalle_estado'  => 1,
                        'tipo_referencia'                       => 'nota',
                    ]);

                    if ($this->tipo == 1) {
                        DB::table('producto_sucursal')
                            ->where('id_pro', $item['id_pro'])
                            ->where('id_tienda', $idTienda)
                            ->increment('ps_stock', (float) $item['cantidad']);
                    } else {
                        DB::table('producto_sucursal')
                            ->where('id_pro', $item['id_pro'])
                            ->where('id_tienda', $idTienda)
                            ->decrement('ps_stock', (float) $item['cantidad']);
                    }
                }
            });

            $this->productosSeleccionados = [];
            $this->concepto               = '';
            $this->motivo                 = '';
            $this->dispatch('cerrarModalMovimiento');
            session()->flash('success', 'Nota registrada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar la nota.');
        }
    }

    public function verDetalle(int $id): void
    {
        try {
            $this->detalleMovimiento = DB::table('movimientos_productos as m')
                ->join('users as u', 'u.id_users', '=', 'm.id_users')
                ->leftJoin('tiendas as t', 't.id_tienda', '=', 'm.id_sucursal')
                ->where('m.id_movimientos_productos', $id)
                ->select('m.*', 'u.nombre_users', 't.tienda_nombre')
                ->first();

            $this->detalleItems = DB::table('movimientos_productos_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_movimientos_productos', $id)
                ->select('p.pro_nombre', 'p.pro_codigo', 'd.movimientos_productos_detalle_cantidad', 'd.costo_unitario')
                ->get()
                ->toArray();

            $this->dispatch('abrirModalDetalle');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    // ── Render ────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $query = DB::table('movimientos_productos as m')
            ->join('users as u', 'u.id_users', '=', 'm.id_users')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'm.id_sucursal')
            ->where('m.movimientos_productos_estado', 1)
            ->whereDate('m.movimientos_productos_fecha', '>=', $this->desde)
            ->whereDate('m.movimientos_productos_fecha', '<=', $this->hasta)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('movimientos_productos_detalle as d')
                    ->whereColumn('d.id_movimientos_productos', 'm.id_movimientos_productos')
                    ->whereIn('d.tipo_referencia', ['compra', 'transferencia', 'anulacion_compra']);
            })
            ->select('m.*', 'u.nombre_users', 't.tienda_nombre');

        if ($this->esSuperAdmin()) {
            if ($this->empresaSeleccionada === 0) {
                $query->whereNull('m.id_sucursal');
            } elseif ($this->sucursalSeleccionada > 0) {
                $query->where('m.id_sucursal', $this->sucursalSeleccionada);
            } else {
                $query->where('t.id_empresa', $this->empresaSeleccionada);
            }
        } elseif ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($this->sucursalSeleccionada > 0) {
                $query->where('m.id_sucursal', $this->sucursalSeleccionada);
            } elseif ($empresaId) {
                $query->where('t.id_empresa', $empresaId);
            }
        } elseif ($this->esVendedor()) {
            $idTienda = (int) session('sucursal_activa_id', 0);
            if ($idTienda) {
                $query->where('m.id_sucursal', $idTienda);
            }
        }

        $historial = $this->mostrarResultados
            ? $query->orderBy('m.id_movimientos_productos', 'desc')->paginate($this->porPagina)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->porPagina);

        $empresas = $this->esSuperAdmin()
            ? DB::table('empresa')->where('empresa_estado', 1)->orderBy('empresa_razon_social')->get()
            : collect();

        $modoAlmacen = $this->esSuperAdmin() && $this->empresaSeleccionada === 0;

        $ubicacionActual = '';
        if ($modoAlmacen) {
            $ubicacionActual = 'Almacén Principal';
        } elseif ($this->sucursalSeleccionada > 0) {
            $col = collect($this->sucursalesDisponibles instanceof \Illuminate\Support\Collection
                ? $this->sucursalesDisponibles->toArray()
                : $this->sucursalesDisponibles
            )->firstWhere('id_tienda', $this->sucursalSeleccionada);
            $ubicacionActual = $col ? (is_array($col) ? $col['tienda_nombre'] : $col->tienda_nombre) : '';
        }

        return view('livewire.gestion-ventas.movimientos', [
            'historial'          => $historial,
            'empresas'           => $empresas,
            'esAdmin'            => $this->esAdmin(),
            'esSuperAdmin'       => $this->esSuperAdmin(),
            'esVendedor'         => $this->esVendedor(),
            'ubicacionActual'    => $ubicacionActual,
            'modoAlmacen'        => $modoAlmacen,
            'mostrarResultados'  => $this->mostrarResultados,
        ]);
    }
}
