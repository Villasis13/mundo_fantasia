<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Transferencias extends Component
{
    use WithPagination;

    public string $vista = 'historial';

    // ── Formulario nueva transferencia ───────────────────────────
    // Formato: 'almacen_{id}' | 'empresa_{id}'  (vacío = sin selección)
    public string $origenKey      = '';
    public int    $idTiendaOrigen  = 0;

    public string $destinoKey     = '';
    public int    $idTiendaDestino = 0;

    public string $motivo         = '';
    public string $buscarProducto = '';
    public array  $resultados     = [];
    public array  $items          = [];

    // ── Vista recepción ──────────────────────────────────────────
    public ?int   $idTransferenciaRecibir = null;
    public array  $cantidadesRecibidas    = [];

    // ── Confirmar acciones ────────────────────────────────────────
    public ?int   $idConfirmar    = null;
    public string $accionConfirm  = '';
    public string $motivoAnulacion = '';

    // ── Ver detalle ───────────────────────────────────────────────
    public $detalleTransferencia = null;
    public array $detalleItems   = [];

    // ── Filtros historial ─────────────────────────────────────────
    public string $filtroOrigenKey       = '';
    public int    $filtroIdTiendaOrigen  = 0;
    public string $filtroDestinoKey      = '';
    public int    $filtroIdTiendaDestino = 0;
    public string $filtroEstado          = '';
    public string $filtroDesde           = '';
    public string $filtroHasta           = '';
    public int    $porPagina             = 10;

    private int   $cachedRoleId = 0;
    private ?Logs $logs         = null;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('transferencias_stock.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Helpers clave origen/destino ─────────────────────────────
    private function origenAlmacenId(): ?int
    {
        return str_starts_with($this->origenKey, 'almacen_')
            ? (int) substr($this->origenKey, 8) ?: null
            : null;
    }

    private function origenEmpresaId(): ?int
    {
        return str_starts_with($this->origenKey, 'empresa_')
            ? (int) substr($this->origenKey, 8) ?: null
            : null;
    }

    private function origenTiendaId(): ?int
    {
        return $this->origenEmpresaId() !== null && $this->idTiendaOrigen > 0
            ? $this->idTiendaOrigen
            : null;
    }

    private function origenConfigurado(): bool
    {
        return $this->origenAlmacenId() !== null || $this->origenTiendaId() !== null;
    }

    private function destinoAlmacenId(): ?int
    {
        return str_starts_with($this->destinoKey, 'almacen_')
            ? (int) substr($this->destinoKey, 8) ?: null
            : null;
    }

    private function destinoEmpresaId(): ?int
    {
        return str_starts_with($this->destinoKey, 'empresa_')
            ? (int) substr($this->destinoKey, 8) ?: null
            : null;
    }

    private function destinoTiendaId(): ?int
    {
        return $this->destinoEmpresaId() !== null && $this->idTiendaDestino > 0
            ? $this->idTiendaDestino
            : null;
    }

    private function destinoConfigurado(): bool
    {
        return $this->destinoAlmacenId() !== null || $this->destinoTiendaId() !== null;
    }

    // ── Helpers clave filtros historial ──────────────────────────
    private function filtroOrigenAlmacenId(): ?int
    {
        return str_starts_with($this->filtroOrigenKey, 'almacen_')
            ? ((int) substr($this->filtroOrigenKey, 8) ?: null)
            : null;
    }

    private function filtroOrigenEmpresaId(): ?int
    {
        return str_starts_with($this->filtroOrigenKey, 'empresa_')
            ? ((int) substr($this->filtroOrigenKey, 8) ?: null)
            : null;
    }

    private function filtroDestinoAlmacenId(): ?int
    {
        return str_starts_with($this->filtroDestinoKey, 'almacen_')
            ? ((int) substr($this->filtroDestinoKey, 8) ?: null)
            : null;
    }

    private function filtroDestinoEmpresaId(): ?int
    {
        return str_starts_with($this->filtroDestinoKey, 'empresa_')
            ? ((int) substr($this->filtroDestinoKey, 8) ?: null)
            : null;
    }

    // ── Watchers – filtros historial ─────────────────────────────
    public function updatedFiltroOrigenKey(): void       { $this->filtroIdTiendaOrigen  = 0; $this->resetPage(); }
    public function updatedFiltroIdTiendaOrigen(): void  { $this->resetPage(); }
    public function updatedFiltroDestinoKey(): void      { $this->filtroIdTiendaDestino = 0; $this->resetPage(); }
    public function updatedFiltroIdTiendaDestino(): void { $this->resetPage(); }
    public function updatedFiltroEstado(): void          { $this->resetPage(); }
    public function updatingPorPagina(): void            { $this->resetPage(); }

    // ── Watchers – formulario ────────────────────────────────────
    public function updatedOrigenKey(): void
    {
        $this->idTiendaOrigen = 0;
        $this->items          = [];
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function updatedIdTiendaOrigen(): void
    {
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function updatedDestinoKey(): void
    {
        $this->idTiendaDestino = 0;
        $this->buscarProducto  = '';
        $this->resultados      = [];
    }

    public function updatedIdTiendaDestino(): void
    {
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function updatedBuscarProducto(): void
    {
        if (!$this->origenConfigurado() || !$this->destinoConfigurado() || strlen($this->buscarProducto) < 2) {
            $this->resultados = [];
            return;
        }

        $yaAgregados = collect($this->items)->pluck('id_pro')->all();
        $b = $this->buscarProducto;

        $almOrig = $this->origenAlmacenId();
        $tndOrig = $this->origenTiendaId();

        if ($almOrig) {
            $this->resultados = DB::table('almacen_producto as ap')
                ->join('productos as p',      'p.id_pro', '=', 'ap.id_pro')
                ->leftJoin('categorias as c', 'c.id_ca',  '=', 'p.id_ca')
                ->leftJoin('familias as f',   'f.id_fa',  '=', 'c.id_fa')
                ->where('ap.id_almacen', $almOrig)
                ->where('ap.ap_estado', 1)
                ->where('ap.ap_stock', '>', 0)
                ->where('p.pro_estado', 1)
                ->whereNotIn('p.id_pro', $yaAgregados)
                ->where(function ($q) use ($b) {
                    $q->where('p.pro_nombre',  'like', "%{$b}%")
                      ->orWhere('p.pro_codigo', 'like', "%{$b}%")
                      ->orWhere('c.ca_nombre',  'like', "%{$b}%")
                      ->orWhere('f.fa_nombre',  'like', "%{$b}%");
                })
                ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                         DB::raw('ap.ap_stock as stock_origen'),
                         'c.ca_nombre', 'f.fa_nombre')
                ->orderBy('p.pro_nombre')->limit(10)->get()->toArray();
        } else {
            $this->resultados = DB::table('producto_sucursal as ps')
                ->join('productos as p',      'p.id_pro', '=', 'ps.id_pro')
                ->leftJoin('categorias as c', 'c.id_ca',  '=', 'p.id_ca')
                ->leftJoin('familias as f',   'f.id_fa',  '=', 'c.id_fa')
                ->where('ps.id_tienda', $tndOrig)
                ->where('ps.ps_estado', 1)
                ->where('ps.ps_stock', '>', 0)
                ->where('p.pro_estado', 1)
                ->whereNotIn('p.id_pro', $yaAgregados)
                ->where(function ($q) use ($b) {
                    $q->where('p.pro_nombre',  'like', "%{$b}%")
                      ->orWhere('p.pro_codigo', 'like', "%{$b}%")
                      ->orWhere('c.ca_nombre',  'like', "%{$b}%")
                      ->orWhere('f.fa_nombre',  'like', "%{$b}%");
                })
                ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                         DB::raw('ps.ps_stock as stock_origen'),
                         'c.ca_nombre', 'f.fa_nombre')
                ->orderBy('p.pro_nombre')->limit(10)->get()->toArray();
        }
    }

    // ── Formulario ────────────────────────────────────────────────
    public function agregarProducto(int $idPro, string $nombre, string $codigo, float $stock): void
    {
        foreach ($this->items as $item) {
            if ($item['id_pro'] == $idPro) return;
        }
        $this->items[] = [
            'id_pro'    => $idPro,
            'nombre'    => $nombre,
            'codigo'    => $codigo,
            'stock_max' => $stock,
            'cantidad'  => 1,
        ];
        $this->buscarProducto = '';
        $this->resultados     = [];
    }

    public function quitarItem(int $idx): void
    {
        array_splice($this->items, $idx, 1);
        $this->items = array_values($this->items);
    }

    public function nuevaTransferencia(): void
    {
        $this->limpiarFormulario();
        $this->vista = 'nueva';
    }

    public function volverHistorial(): void
    {
        $this->limpiarFormulario();
        $this->vista = 'historial';
        $this->resetPage();
    }

    public function guardar(): void
    {
        if (!auth()->user()->can('transferencias_stock.crear')) {
            session()->flash('error', 'Sin permiso para crear transferencias.');
            return;
        }

        if (!$this->origenConfigurado()) {
            $this->addError('origenKey', 'Seleccione el origen de la transferencia.');
            return;
        }
        if (!$this->destinoConfigurado()) {
            $this->addError('destinoKey', 'Seleccione el destino de la transferencia.');
            return;
        }

        $almOrig = $this->origenAlmacenId();
        $tndOrig = $this->origenTiendaId();
        $almDest = $this->destinoAlmacenId();
        $tndDest = $this->destinoTiendaId();

        if ($almOrig && $almDest && $almOrig === $almDest) {
            session()->flash('error', 'El origen y el destino no pueden ser el mismo almacén.');
            return;
        }
        if ($tndOrig && $tndDest && $tndOrig === $tndDest) {
            session()->flash('error', 'El origen y el destino no pueden ser la misma sede.');
            return;
        }

        $this->validate([
            'items'            => 'required|array|min:1',
            'items.*.cantidad' => 'required|numeric|min:0.01',
        ], [
            'items.required'       => 'Agrega al menos un producto.',
            'items.min'            => 'Agrega al menos un producto.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        DB::beginTransaction();
        try {
            $srcMap = [];
            foreach ($this->items as $item) {
                if ($almOrig) {
                    $src       = DB::table('almacen_producto')
                        ->where('id_almacen', $almOrig)->where('id_pro', $item['id_pro'])->first();
                    $stockDisp = $src ? (float) $src->ap_stock : 0;
                } else {
                    $src       = DB::table('producto_sucursal')
                        ->where('id_tienda', $tndOrig)->where('id_pro', $item['id_pro'])->first();
                    $stockDisp = $src ? (float) $src->ps_stock : 0;
                }
                if (!$src || $stockDisp < $item['cantidad']) {
                    DB::rollBack();
                    session()->flash('error', "Stock insuficiente para: {$item['nombre']}");
                    return;
                }
                $srcMap[$item['id_pro']] = $src;
            }

            $numero = 'TRF-' . date('Y') . '-' . str_pad(
                DB::table('transferencias_stock')->count() + 1, 5, '0', STR_PAD_LEFT
            );

            $destinoNombre = $almDest
                ? DB::table('almacen')->where('id_almacen', $almDest)->value('almacen_nombre')
                : DB::table('tiendas')->where('id_tienda', $tndDest)->value('tienda_nombre');

            $idTransferencia = DB::table('transferencias_stock')->insertGetId([
                'transferencia_numero' => $numero,
                'id_almacen_origen'    => $almOrig,
                'id_tienda_origen'     => $tndOrig,
                'id_tienda_destino'    => $tndDest,
                'id_almacen_destino'   => $almDest,
                'id_users'             => auth()->user()->id_users,
                'transferencia_fecha'  => now()->format('Y-m-d'),
                'transferencia_estado' => 'en_transito',
                'transferencia_motivo' => $this->motivo ?: null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $idMovSalida = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'          => now()->toDateString(),
                'id_users'                             => auth()->user()->id_users,
                'id_sucursal'                          => $tndOrig,
                'id_almacen'                           => $almOrig,
                'movimientos_productos_fecha_creacion' => now(),
                'movimientos_productos_tipo'           => 2,
                'movimientos_productos_estado'         => 1,
                'movimientos_productos_motivo'         => "Salida transferencia {$numero} → {$destinoNombre}",
                'created_at'                           => now(),
                'updated_at'                           => now(),
            ]);

            foreach ($this->items as $item) {
                DB::table('transferencias_stock_detalle')->insert([
                    'id_transferencia'         => $idTransferencia,
                    'id_pro'                   => $item['id_pro'],
                    'detalle_cantidad'          => (float) $item['cantidad'],
                    'detalle_cantidad_recibida' => null,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);

                if ($almOrig) {
                    DB::table('almacen_producto')
                        ->where('id_almacen', $almOrig)->where('id_pro', $item['id_pro'])
                        ->decrement('ap_stock', (float) $item['cantidad'], ['updated_at' => now()]);
                    $costo = (float) ($srcMap[$item['id_pro']]->ap_precio_costo ?? 0);
                } else {
                    DB::table('producto_sucursal')
                        ->where('id_tienda', $tndOrig)->where('id_pro', $item['id_pro'])
                        ->decrement('ps_stock', (float) $item['cantidad'], ['updated_at' => now()]);
                    $costo = (float) ($srcMap[$item['id_pro']]->ps_precio_uni ?? 0);
                }

                DB::table('movimientos_productos_detalle')->insert([
                    'id_movimientos_productos'               => $idMovSalida,
                    'id_pro'                                 => $item['id_pro'],
                    'movimientos_productos_detalle_cantidad' => (string) $item['cantidad'],
                    'costo_unitario'                         => $costo,
                    'id_referencia'                          => $idTransferencia,
                    'tipo_referencia'                        => 'transferencia',
                    'movimientos_productos_detalle_estado'   => 1,
                    'created_at'                             => now(),
                    'updated_at'                             => now(),
                ]);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->vista = 'historial';
            $this->resetPage();
            session()->flash('success', "Transferencia {$numero} creada correctamente.");
            $this->dispatch('abrirPdf', url: route('logistica.transferencia_pdf', ['id' => $idTransferencia]));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al crear la transferencia.');
        }
    }

    // ── Confirmar acciones ────────────────────────────────────────
    public function confirmarAccion(int $id, string $accion): void
    {
        $this->idConfirmar    = $id;
        $this->accionConfirm  = $accion;
        $this->motivoAnulacion = '';
        $this->resetErrorBag('motivoAnulacion');
        $this->dispatch('abrirModalConfirmar');
    }

    public function ejecutarAccion(): void
    {
        if (!$this->idConfirmar) return;

        $trf = DB::table('transferencias_stock')->where('id_transferencia', $this->idConfirmar)->first();

        if (!$trf) {
            $this->dispatch('cerrarModalConfirmar');
            session()->flash('error', 'Transferencia no encontrada.');
            return;
        }

        DB::beginTransaction();
        try {
            switch ($this->accionConfirm) {
                case 'enviar':
                    if ($trf->transferencia_estado !== 'pendiente') {
                        throw new \Exception('Solo se puede enviar una transferencia pendiente.');
                    }
                    DB::table('transferencias_stock')
                        ->where('id_transferencia', $this->idConfirmar)
                        ->update(['transferencia_estado' => 'en_transito', 'updated_at' => now()]);
                    $msg = 'Transferencia marcada como en tránsito.';
                    break;

                case 'anular':
                    if ($trf->transferencia_estado === 'anulado') {
                        throw new \Exception('La transferencia ya está anulada.');
                    }
                    $this->validate(
                        ['motivoAnulacion' => 'required|string|min:5|max:500'],
                        ['motivoAnulacion.required' => 'El motivo de anulación es obligatorio.',
                         'motivoAnulacion.min'      => 'El motivo debe tener al menos 5 caracteres.']
                    );
                    if (in_array($trf->transferencia_estado, ['pendiente', 'en_transito'])) {
                        $detalles = DB::table('transferencias_stock_detalle')
                            ->where('id_transferencia', $this->idConfirmar)->get();

                        foreach ($detalles as $d) {
                            if ($trf->id_tienda_origen) {
                                DB::table('producto_sucursal')
                                    ->where('id_tienda', $trf->id_tienda_origen)->where('id_pro', $d->id_pro)
                                    ->increment('ps_stock', (float) $d->detalle_cantidad, ['updated_at' => now()]);
                            } else {
                                DB::table('almacen_producto')
                                    ->where('id_almacen', $trf->id_almacen_origen)->where('id_pro', $d->id_pro)
                                    ->increment('ap_stock', (float) $d->detalle_cantidad, ['updated_at' => now()]);
                            }
                        }

                        $idMov = DB::table('movimientos_productos_detalle as mpd')
                            ->join('movimientos_productos as mp', 'mp.id_movimientos_productos', '=', 'mpd.id_movimientos_productos')
                            ->where('mpd.id_referencia', $this->idConfirmar)
                            ->where('mpd.tipo_referencia', 'transferencia')
                            ->where('mp.movimientos_productos_tipo', 2)
                            ->value('mp.id_movimientos_productos');
                        if ($idMov) {
                            DB::table('movimientos_productos')
                                ->where('id_movimientos_productos', $idMov)
                                ->update(['movimientos_productos_estado' => 0, 'updated_at' => now()]);
                        }
                    }
                    DB::table('transferencias_stock')
                        ->where('id_transferencia', $this->idConfirmar)
                        ->update([
                            'transferencia_estado'           => 'anulado',
                            'transferencia_motivo_anulacion' => $this->motivoAnulacion,
                            'updated_at'                     => now(),
                        ]);
                    $msg = 'Transferencia anulada y stock restaurado.';
                    break;

                default:
                    throw new \Exception('Acción no válida.');
            }

            DB::commit();
            $this->idConfirmar   = null;
            $this->accionConfirm = '';
            $this->dispatch('cerrarModalConfirmar');
            session()->flash('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->dispatch('cerrarModalConfirmar');
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Recepción ─────────────────────────────────────────────────
    public function abrirRecepcion(int $id): void
    {
        $trf = DB::table('transferencias_stock')
            ->where('id_transferencia', $id)->where('transferencia_estado', 'en_transito')->first();
        if (!$trf) return;

        $this->idTransferenciaRecibir = $id;
        $this->resetErrorBag();

        $detalles   = DB::table('transferencias_stock_detalle')->where('id_transferencia', $id)->get();
        $cantidades = [];
        foreach ($detalles as $d) {
            $cantidades[$d->id_transferencia_detalle] = (float) $d->detalle_cantidad;
        }
        $this->cantidadesRecibidas = $cantidades;
        $this->dispatch('abrirModalRecepcion');
    }

    public function volverDesdeRecepcion(): void
    {
        $this->idTransferenciaRecibir = null;
        $this->cantidadesRecibidas    = [];
        $this->resetErrorBag();
        $this->dispatch('cerrarModalRecepcion');
    }

    public function confirmarRecepcion(): void
    {
        if (!$this->idTransferenciaRecibir) return;

        if (!auth()->user()->can('transferencias_stock.actualizar')) {
            session()->flash('error', 'Sin permiso para recepcionar transferencias.');
            return;
        }

        $trf = DB::table('transferencias_stock')
            ->where('id_transferencia', $this->idTransferenciaRecibir)
            ->where('transferencia_estado', 'en_transito')->first();

        if (!$trf) {
            session()->flash('error', 'La transferencia no está en tránsito.');
            return;
        }

        $detalles = DB::table('transferencias_stock_detalle')
            ->where('id_transferencia', $this->idTransferenciaRecibir)->get();

        foreach ($detalles as $d) {
            if ((float) ($this->cantidadesRecibidas[$d->id_transferencia_detalle] ?? 0) > (float) $d->detalle_cantidad) {
                $this->addError('cantidades', 'La cantidad recibida no puede superar la cantidad enviada.');
                return;
            }
        }

        $hayAlguna = collect($this->cantidadesRecibidas)->filter(fn($v) => (float) $v > 0)->isNotEmpty();
        if (!$hayAlguna) {
            $this->addError('cantidades', 'Ingrese al menos una cantidad mayor a 0.');
            return;
        }

        DB::beginTransaction();
        try {
            $idMovEntrada = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'          => now()->toDateString(),
                'id_users'                             => auth()->user()->id_users,
                'id_sucursal'                          => $trf->id_tienda_destino,
                'id_almacen'                           => $trf->id_almacen_destino,
                'movimientos_productos_fecha_creacion' => now(),
                'movimientos_productos_tipo'           => 1,
                'movimientos_productos_estado'         => 1,
                'movimientos_productos_motivo'         => "Recepción transferencia {$trf->transferencia_numero}",
                'created_at'                           => now(),
                'updated_at'                           => now(),
            ]);

            $mermas = [];

            foreach ($detalles as $d) {
                $cantidad  = max(0, (float) ($this->cantidadesRecibidas[$d->id_transferencia_detalle] ?? 0));
                $mermaQty  = (float) $d->detalle_cantidad - $cantidad;

                DB::table('transferencias_stock_detalle')
                    ->where('id_transferencia_detalle', $d->id_transferencia_detalle)
                    ->update(['detalle_cantidad_recibida' => $cantidad, 'updated_at' => now()]);

                if ($cantidad <= 0) {
                    if ($mermaQty > 0) $mermas[] = ['id_pro' => $d->id_pro, 'cantidad' => $mermaQty, 'costo' => 0];
                    continue;
                }

                $costo = $trf->id_almacen_origen
                    ? (float) (DB::table('almacen_producto')
                        ->where('id_almacen', $trf->id_almacen_origen)->where('id_pro', $d->id_pro)
                        ->value('ap_precio_costo') ?? 0)
                    : (float) (DB::table('producto_sucursal')
                        ->where('id_tienda', $trf->id_tienda_origen)->where('id_pro', $d->id_pro)
                        ->value('ps_precio_uni') ?? 0);

                if ($trf->id_almacen_destino) {
                    $ap = DB::table('almacen_producto')
                        ->where('id_almacen', $trf->id_almacen_destino)->where('id_pro', $d->id_pro)->first();
                    if ($ap) {
                        DB::table('almacen_producto')
                            ->where('id_almacen', $trf->id_almacen_destino)->where('id_pro', $d->id_pro)
                            ->increment('ap_stock', $cantidad, ['updated_at' => now()]);
                    } else {
                        DB::table('almacen_producto')->insert([
                            'id_almacen'      => $trf->id_almacen_destino,
                            'id_pro'          => $d->id_pro,
                            'ap_stock'        => $cantidad,
                            'ap_precio_costo' => $costo,
                            'ap_estado'       => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                } else {
                    $ps = DB::table('producto_sucursal')
                        ->where('id_pro', $d->id_pro)->where('id_tienda', $trf->id_tienda_destino)->first();
                    if ($ps) {
                        DB::table('producto_sucursal')
                            ->where('id_pro', $d->id_pro)->where('id_tienda', $trf->id_tienda_destino)
                            ->increment('ps_stock', $cantidad, ['updated_at' => now()]);
                    } else {
                        DB::table('producto_sucursal')->insert([
                            'id_pro'          => $d->id_pro,
                            'id_tienda'       => $trf->id_tienda_destino,
                            'id_sucursal'     => null,
                            'ps_precio_uni'   => $costo,
                            'ps_precio_uni_2' => 0,
                            'ps_precio_uni_3' => 0,
                            'ps_stock'        => $cantidad,
                            'ps_stock_minimo' => 0,
                            'ps_porcen_igv'   => 18,
                            'ps_estado'       => 1,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }

                DB::table('movimientos_productos_detalle')->insert([
                    'id_movimientos_productos'               => $idMovEntrada,
                    'id_pro'                                 => $d->id_pro,
                    'movimientos_productos_detalle_cantidad' => (string) $cantidad,
                    'costo_unitario'                         => $costo,
                    'id_referencia'                          => $this->idTransferenciaRecibir,
                    'tipo_referencia'                        => 'transferencia',
                    'movimientos_productos_detalle_estado'   => 1,
                    'created_at'                             => now(),
                    'updated_at'                             => now(),
                ]);

                if ($mermaQty > 0) {
                    $mermas[] = ['id_pro' => $d->id_pro, 'cantidad' => $mermaQty, 'costo' => $costo];
                }
            }

            if (!empty($mermas)) {
                $idMovMerma = DB::table('movimientos_productos')->insertGetId([
                    'movimientos_productos_fecha'          => now()->toDateString(),
                    'id_users'                             => auth()->user()->id_users,
                    'id_sucursal'                          => $trf->id_tienda_destino,
                    'id_almacen'                           => $trf->id_almacen_destino,
                    'movimientos_productos_fecha_creacion' => now(),
                    'movimientos_productos_tipo'           => 2,
                    'movimientos_productos_estado'         => 1,
                    'movimientos_productos_motivo'         => "Merma recepcion transferencia {$trf->transferencia_numero}",
                    'created_at'                           => now(),
                    'updated_at'                           => now(),
                ]);
                foreach ($mermas as $m) {
                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'               => $idMovMerma,
                        'id_pro'                                 => $m['id_pro'],
                        'movimientos_productos_detalle_cantidad' => (string) $m['cantidad'],
                        'costo_unitario'                         => $m['costo'],
                        'id_referencia'                          => $this->idTransferenciaRecibir,
                        'tipo_referencia'                        => 'merma_transferencia',
                        'movimientos_productos_detalle_estado'   => 1,
                        'created_at'                             => now(),
                        'updated_at'                             => now(),
                    ]);
                }
            }

            DB::table('transferencias_stock')
                ->where('id_transferencia', $this->idTransferenciaRecibir)
                ->update(['transferencia_estado' => 'recibido', 'updated_at' => now()]);

            DB::commit();
            $this->idTransferenciaRecibir = null;
            $this->cantidadesRecibidas    = [];
            $this->resetErrorBag();
            $this->dispatch('cerrarModalRecepcion');
            session()->flash('success', 'Recepción confirmada. Stock actualizado en el destino.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al confirmar la recepción.');
        }
    }

    // ── Ver detalle ───────────────────────────────────────────────
    public function verDetalle(int $id): void
    {
        try {
            $this->detalleTransferencia = DB::table('transferencias_stock as t')
                ->leftJoin('almacen as ao',  'ao.id_almacen', '=', 't.id_almacen_origen')
                ->leftJoin('tiendas as to2', 'to2.id_tienda', '=', 't.id_tienda_origen')
                ->leftJoin('tiendas as td',  'td.id_tienda',  '=', 't.id_tienda_destino')
                ->leftJoin('almacen as ad',  'ad.id_almacen', '=', 't.id_almacen_destino')
                ->leftJoin('empresa as eo',  'eo.id_empresa',  '=', 'ao.id_empresa')
                ->leftJoin('empresa as eo2', 'eo2.id_empresa', '=', 'to2.id_empresa')
                ->leftJoin('empresa as ed',  'ed.id_empresa',  '=', 'td.id_empresa')
                ->leftJoin('empresa as ead', 'ead.id_empresa', '=', 'ad.id_empresa')
                ->join('users as u',         'u.id_users',    '=', 't.id_users')
                ->select(
                    't.*',
                    DB::raw("CONCAT(COALESCE(ao.almacen_nombre, to2.tienda_nombre, '—'), IFNULL(CONCAT(' - ', COALESCE(eo.empresa_nombrecomercial, eo2.empresa_nombrecomercial)), '')) as origen_nombre"),
                    DB::raw("CONCAT(COALESCE(td.tienda_nombre, ad.almacen_nombre, '—'), IFNULL(CONCAT(' — ', COALESCE(ed.empresa_nombrecomercial, ead.empresa_nombrecomercial)), '')) as destino_nombre"),
                    'u.nombre_users'
                )
                ->where('t.id_transferencia', $id)->first();

            $this->detalleItems = DB::table('transferencias_stock_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_transferencia', $id)
                ->select('p.pro_nombre', 'p.pro_codigo', 'd.detalle_cantidad', 'd.detalle_cantidad_recibida')
                ->get()->toArray();

            $this->dispatch('abrirModalDetalle');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'origenKey', 'idTiendaOrigen',
            'destinoKey', 'idTiendaDestino',
            'motivo', 'buscarProducto', 'resultados', 'items',
            'idTransferenciaRecibir', 'cantidadesRecibidas',
            'idConfirmar', 'accionConfirm', 'motivoAnulacion',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────
    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $adminEmpId   = $esAdmin ? $this->adminEmpresaId() : null;

        $almacenes = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', 1)
            ->orderBy('e.empresa_nombrecomercial')
            ->orderBy('a.almacen_nombre')
            ->select('a.id_almacen', 'a.almacen_nombre', 'e.empresa_nombrecomercial')
            ->get();
        $empresas  = DB::table('empresa')->where('empresa_estado', '!=', 0)->orderBy('empresa_razon_social')->get();

        // Sedes para origen (empresa seleccionada)
        $sedesOrigen = $this->origenEmpresaId()
            ? DB::table('tiendas')
                ->where('id_empresa', $this->origenEmpresaId())
                ->whereIn('tienda_tipo', [1, 2])->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();

        // Sedes para destino (empresa seleccionada)
        $sedesDestino = $this->destinoEmpresaId()
            ? DB::table('tiendas')
                ->where('id_empresa', $this->destinoEmpresaId())
                ->whereIn('tienda_tipo', [1, 2])->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();

        // Filtros historial – sedes para origen/destino
        $sedesOrigenFiltro = $this->filtroOrigenEmpresaId() !== null
            ? DB::table('tiendas')
                ->where('id_empresa', $this->filtroOrigenEmpresaId())
                ->whereIn('tienda_tipo', [1, 2])->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();

        $sedesDestinoFiltro = $this->filtroDestinoEmpresaId() !== null
            ? DB::table('tiendas')
                ->where('id_empresa', $this->filtroDestinoEmpresaId())
                ->whereIn('tienda_tipo', [1, 2])->whereNull('id_tienda_padre')
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();

        // Modal recepción
        $ordenRecibir    = null;
        $detallesRecibir = collect();
        if ($this->idTransferenciaRecibir) {
            $ordenRecibir = DB::table('transferencias_stock as t')
                ->leftJoin('almacen as ao',  'ao.id_almacen', '=', 't.id_almacen_origen')
                ->leftJoin('tiendas as to2', 'to2.id_tienda', '=', 't.id_tienda_origen')
                ->leftJoin('tiendas as td',  'td.id_tienda',  '=', 't.id_tienda_destino')
                ->leftJoin('almacen as ad',  'ad.id_almacen', '=', 't.id_almacen_destino')
                ->leftJoin('empresa as eo',  'eo.id_empresa',  '=', 'ao.id_empresa')
                ->leftJoin('empresa as eo2', 'eo2.id_empresa', '=', 'to2.id_empresa')
                ->leftJoin('empresa as ed',  'ed.id_empresa',  '=', 'td.id_empresa')
                ->leftJoin('empresa as ead', 'ead.id_empresa', '=', 'ad.id_empresa')
                ->select(
                    't.*',
                    DB::raw("CONCAT(COALESCE(ao.almacen_nombre, to2.tienda_nombre, '—'), IFNULL(CONCAT(' - ', COALESCE(eo.empresa_nombrecomercial, eo2.empresa_nombrecomercial)), '')) as origen_nombre"),
                    DB::raw("CONCAT(COALESCE(td.tienda_nombre, ad.almacen_nombre, '—'), IFNULL(CONCAT(' — ', COALESCE(ed.empresa_nombrecomercial, ead.empresa_nombrecomercial)), '')) as destino_nombre")
                )
                ->where('t.id_transferencia', $this->idTransferenciaRecibir)->first();

            $detallesRecibir = DB::table('transferencias_stock_detalle as d')
                ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
                ->where('d.id_transferencia', $this->idTransferenciaRecibir)
                ->select('d.id_transferencia_detalle', 'd.detalle_cantidad', 'p.pro_nombre', 'p.pro_codigo')
                ->get();
        }

        $transferencias = DB::table('transferencias_stock as t')
            ->leftJoin('almacen as ao',  'ao.id_almacen', '=', 't.id_almacen_origen')
            ->leftJoin('tiendas as to2', 'to2.id_tienda', '=', 't.id_tienda_origen')
            ->leftJoin('tiendas as td',  'td.id_tienda',  '=', 't.id_tienda_destino')
            ->leftJoin('almacen as ad',  'ad.id_almacen', '=', 't.id_almacen_destino')
            ->leftJoin('empresa as eo',  'eo.id_empresa',  '=', 'ao.id_empresa')
            ->leftJoin('empresa as eo2', 'eo2.id_empresa', '=', 'to2.id_empresa')
            ->leftJoin('empresa as ed',  'ed.id_empresa',  '=', 'td.id_empresa')
            ->leftJoin('empresa as ead', 'ead.id_empresa', '=', 'ad.id_empresa')
            ->join('users as u',         'u.id_users',    '=', 't.id_users')
            ->select(
                't.*',
                DB::raw("CONCAT(COALESCE(ao.almacen_nombre, to2.tienda_nombre, '—'), IFNULL(CONCAT(' - ', COALESCE(eo.empresa_nombrecomercial, eo2.empresa_nombrecomercial)), '')) as origen_nombre"),
                DB::raw("CONCAT(COALESCE(td.tienda_nombre, ad.almacen_nombre, '—'), IFNULL(CONCAT(' — ', COALESCE(ed.empresa_nombrecomercial, ead.empresa_nombrecomercial)), '')) as destino_nombre"),
                'u.nombre_users'
            )
            ->when($this->filtroOrigenAlmacenId() !== null,
                fn($q) => $q->where('t.id_almacen_origen', $this->filtroOrigenAlmacenId()))
            ->when($this->filtroOrigenEmpresaId() !== null, function ($q) {
                if ($this->filtroIdTiendaOrigen > 0) {
                    $q->where('t.id_tienda_origen', $this->filtroIdTiendaOrigen);
                } else {
                    $q->where('to2.id_empresa', $this->filtroOrigenEmpresaId());
                }
            })
            ->when($this->filtroDestinoAlmacenId() !== null,
                fn($q) => $q->where('t.id_almacen_destino', $this->filtroDestinoAlmacenId()))
            ->when($this->filtroDestinoEmpresaId() !== null, function ($q) {
                if ($this->filtroIdTiendaDestino > 0) {
                    $q->where('t.id_tienda_destino', $this->filtroIdTiendaDestino);
                } else {
                    $q->where('td.id_empresa', $this->filtroDestinoEmpresaId());
                }
            })
            ->when($this->filtroEstado !== '',
                fn($q) => $q->where('t.transferencia_estado', $this->filtroEstado))
            ->when($this->filtroDesde,
                fn($q) => $q->whereDate('t.transferencia_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta,
                fn($q) => $q->whereDate('t.transferencia_fecha', '<=', $this->filtroHasta))
            ->orderByDesc('t.id_transferencia')
            ->paginate($this->porPagina);

        return view('livewire.logistica.transferencias', compact(
            'transferencias', 'sedesOrigenFiltro', 'sedesDestinoFiltro',
            'almacenes', 'empresas', 'sedesOrigen', 'sedesDestino',
            'ordenRecibir', 'detallesRecibir',
            'esSuperAdmin', 'esAdmin'
        ));
    }
}
