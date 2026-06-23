<?php

namespace App\Livewire\GestionVentas;

use App\Models\General;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class Pedidos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Vista ─────────────────────────────────────────────────
    public string $vista = 'nuevo'; // historial | nuevo

    // ── Contexto ──────────────────────────────────────────────
    public int  $idTienda  = 0;
    public int  $idEmpresa = 0;
    private int $cachedRoleId = 0;

    // ── Filtros historial ─────────────────────────────────────
    public string $buscar       = '';
    public int    $porPagina    = 10;
    public string $filtroEstado = '';

    // ── Formulario nuevo pedido ───────────────────────────────
    public string $buscarProducto      = '';
    public array  $resultadosProductos = [];
    public array  $items               = [];
    public string $clienteNombre           = '';
    public array  $resultadosClientes      = [];
    public string $clienteDoc              = '';
    public string $buscarClienteModal      = '';
    public array  $resultadosClientesModal = [];
    public array  $resultadosClientesFactura = [];

    // ── Edición rápida de cliente ─────────────────────────────
    public bool   $modoEdicionCliente   = false;
    public bool   $editClienteDeFactura = false;
    public int    $editClienteId        = 0;
    public string $editClienteNombre    = '';
    public string $editClienteDoc       = '';
    public string $editClienteDireccion = '';
    public string $observacion         = '';
    public string $tipoComprobante     = '03'; // 03=Boleta, 01=Factura, 20=N.Venta
    public int    $tipoPago            = 1; // 1=Contado, 2=Crédito

    // ── Nuevo cliente (modal) ─────────────────────────────────
    public string $nuevoClienteNombre    = '';
    public string $nuevoClienteDoc       = '';
    public string $nuevoClienteDireccion = '';
    public string $nuevoClienteTipo      = '';

    // ── Proforma rápida ───────────────────────────────────────
    public string $proformaDoc                = '';
    public string $proformaRazonSocial        = '';
    public array  $resultadosClientesProforma = [];
    public int    $proformaFormaPago          = 1;
    public string $proformaLugarEntrega       = 'Previa Coordinación';
    public string $proformaObservacion        = 'Los precios están sujetos a variaciones';
    public string $proformaGuardadoNumero     = '';

    // ── Detalle modal ─────────────────────────────────────────
    public ?int   $idDetalle            = null;
    public array  $detalleItems         = [];
    public string $detallePedidoNumero  = '';

    // ── Selector de presentaciones ────────────────────────────
    public array $presentacionesPendientes = [];
    public array $productoPendienteData    = [];

    // ── Edición ───────────────────────────────────────────────
    public ?int  $idEditar    = null;
    public bool  $esRecuperar = false;

    // ── Recuperar pre-venta ───────────────────────────────────
    public array  $pedidosRecuperables   = [];
    public ?int   $pedidoRecuperableId   = null;
    public array  $detalleRecuperable    = [];
    public string $claveRecuperar        = '';
    public bool   $modoPasswordRecuperar = false;
    public string $errorClaveRecuperar   = '';

    // ── Anulación ─────────────────────────────────────────────
    public ?int $idAnular = null;

    // ── Éxito al guardar ──────────────────────────────────────
    public string $pedidoGuardadoNumero = '';

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

    private function esSuperAdmin(): bool    { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool         { return $this->cachedRoleId === 2; }
    private function esPrivilegiado(): bool  { return in_array($this->cachedRoleId, [1, 2, 3]); }
    private function esRolRestringido(): bool { return !$this->esPrivilegiado(); }

    private function precioMinimo(array $item): float
    {
        if ($this->esPrivilegiado()) return 0.0;
        return (float) ($item['precio_publico'] ?? 0);
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('pedidos.listar'), 403);

        // Resolver tienda/empresa del usuario logueado
        $tienda = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->select('t.id_tienda', 't.id_empresa')
            ->first();

        if ($tienda) {
            $this->idTienda  = (int) $tienda->id_tienda;
            $this->idEmpresa = (int) $tienda->id_empresa;
        } else {
            $primera = DB::table('tiendas')->where('tienda_estado', 1)->orderBy('id_tienda')->first();
            if ($primera) {
                $this->idTienda  = (int) $primera->id_tienda;
                $this->idEmpresa = (int) $primera->id_empresa;
            }
        }
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updated(string $name): void
    {
        if (!$this->esRolRestringido()) return;

        if (!preg_match('/^items\.(\d+)\.precio$/', $name, $m)) return;

        $idx    = (int) $m[1];
        $precio = (float) ($this->items[$idx]['precio'] ?? 0);
        $minimo = $this->precioMinimo($this->items[$idx]);

        if ($precio < $minimo) {
            $this->items[$idx]['precio'] = $minimo;
            $this->addError(
                "precio_item_{$idx}",
                'Precio mínimo permitido: S/ ' . number_format($minimo, 2) . ' (precio de venta).'
            );
        } else {
            $this->resetErrorBag("precio_item_{$idx}");
        }
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    // ── Búsqueda de documento cliente ────────────────────────

    public function updatedClienteDoc(): void
    {
        $doc = trim($this->clienteDoc);
        $len = strlen($doc);

        if ($len !== 8 && $len !== 11) return;

        $cliente = DB::table('clientes')
            ->where('cliente_numero', $doc)
            ->first();

        if ($cliente) {
            $this->clienteNombre = $cliente->cliente_nombre ?? '';
            return;
        }

        $tipo      = $len === 11 ? '4' : '2';
        $resultado = (new General())->consultar_documento_migo($tipo, $doc);

        if (($resultado['success'] ?? false) && !empty($resultado['data']['nombre'])) {
            $this->clienteNombre = $resultado['data']['nombre'];
        }
    }

    // ── Búsqueda de clientes en offcanvas ────────────────────

    public function updatedBuscarClienteModal(): void
    {
        $termino = trim($this->buscarClienteModal);
        if (strlen($termino) < 2) {
            $this->resultadosClientesModal = [];
            return;
        }

        $this->resultadosClientesModal = DB::table('clientes')
            ->where('cliente_estado', 1)
            ->where(function ($q) use ($termino) {
                $q->where('cliente_nombre', 'like', '%' . $termino . '%')
                  ->orWhere('cliente_numero', 'like', '%' . $termino . '%');
            })
            ->select('id_clientes', 'cliente_nombre', 'cliente_numero')
            ->orderBy('cliente_nombre')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function cargarEdicionCliente(int $id, string $tipo = ''): void
    {
        $c = DB::table('clientes')->where('id_clientes', $id)->first();
        if (!$c) return;

        $this->editClienteId        = $id;
        $this->editClienteNombre    = ($c->cliente_razonsocial ?? '') ?: ($c->cliente_nombre ?? '');
        $this->editClienteDoc       = $c->cliente_numero ?? '';
        $this->editClienteDireccion = $c->cliente_direccion ?? '';
        $this->editClienteDeFactura = !empty($tipo);
        $this->modoEdicionCliente   = true;
        $this->resetErrorBag();
        $this->dispatch('abrirEdicionCliente', tipo: $tipo);
    }

    public function guardarEdicionCliente(): void
    {
        $this->validate([
            'editClienteNombre'    => 'required|string|max:500',
            'editClienteDoc'       => 'nullable|string|max:11',
            'editClienteDireccion' => 'nullable|string|max:500',
        ], [
            'editClienteNombre.required' => 'El nombre es obligatorio.',
        ]);

        $data = [
            'cliente_nombre'    => $this->editClienteNombre,
            'cliente_numero'    => $this->editClienteDoc ?: null,
            'cliente_direccion' => $this->editClienteDireccion ?: null,
        ];
        if (strlen(trim($this->editClienteDoc)) === 11) {
            $data['cliente_razonsocial'] = $this->editClienteNombre;
        }

        DB::table('clientes')->where('id_clientes', $this->editClienteId)->update($data);

        $this->modoEdicionCliente = false;
        $this->editClienteId      = 0;

        if ($this->editClienteDeFactura) {
            $this->editClienteDeFactura = false;
            $this->updatedClienteNombre();
            $this->dispatch('cerrarOffcanvasCliente');
        } else {
            $this->updatedBuscarClienteModal();
        }
    }

    public function cancelarEdicionCliente(): void
    {
        $this->modoEdicionCliente   = false;
        $this->editClienteDeFactura = false;
        $this->editClienteId        = 0;
        $this->editClienteNombre    = '';
        $this->editClienteDoc       = '';
        $this->editClienteDireccion = '';
        $this->resetErrorBag();
    }

    // ── Agregar nuevo cliente desde modal comprobante ─────────

    public function abrirNuevoCliente(string $tipo = ''): void
    {
        $this->nuevoClienteNombre    = '';
        $this->nuevoClienteDoc       = '';
        $this->nuevoClienteDireccion = '';
        $this->nuevoClienteTipo      = $tipo;
        $this->resetErrorBag();
        $this->dispatch('abrirModalNuevoCliente', tipo: $tipo);
    }

    public function guardarNuevoCliente(): void
    {
        $this->validate([
            'nuevoClienteNombre' => 'required|string|max:500',
            'nuevoClienteDoc'    => 'nullable|string|max:11',
        ], [
            'nuevoClienteNombre.required' => 'El nombre es obligatorio.',
        ]);

        $doc = trim($this->nuevoClienteDoc);

        if ($this->nuevoClienteTipo === '01' && strlen($doc) !== 11) {
            $this->addError('nuevoClienteDoc', 'Para emitir Factura debe ingresar el RUC (11 dígitos) del cliente.');
            return;
        }

        try {
            $existente = $doc ? DB::table('clientes')->where('cliente_numero', $doc)->first() : null;

            if ($existente) {
                $nombre = ($existente->cliente_razonsocial ?? '') ?: ($existente->cliente_nombre ?? '');
            } else {
                $nombre    = trim($this->nuevoClienteNombre);
                $docLen    = strlen($doc);
                $idTipoDoc = DB::table('tipo_documento')
                    ->where('tipo_documento_identidad_abr', $docLen === 11 ? 'RUC' : 'DNI')
                    ->value('id_tipo_documento') ?? ($docLen === 11 ? 4 : 2);

                DB::table('clientes')->insert([
                    'id_tipo_documento'   => $idTipoDoc,
                    'cliente_razonsocial' => $docLen === 11 ? $nombre : null,
                    'cliente_nombre'      => $nombre,
                    'cliente_numero'      => $doc ?: null,
                    'cliente_direccion'   => trim($this->nuevoClienteDireccion) ?: '',
                    'cliente_telefono'    => '',
                    'cliente_fecha'       => now(),
                    'cliente_estado'      => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $this->clienteNombre = $nombre;
            $this->clienteDoc    = $doc;

            $this->nuevoClienteNombre    = '';
            $this->nuevoClienteDoc       = '';
            $this->nuevoClienteDireccion = '';
            $this->nuevoClienteTipo      = '';

            $this->dispatch('cerrarModalNuevoClienteConExito');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->addError('nuevoClienteNombre', 'Ocurrió un error al guardar el cliente. Intente nuevamente.');
        }
    }

    public function cancelarNuevoCliente(): void
    {
        $this->nuevoClienteNombre    = '';
        $this->nuevoClienteDoc       = '';
        $this->nuevoClienteDireccion = '';
        $this->nuevoClienteTipo      = '';
        $this->resetErrorBag();
    }

    public function seleccionarCliente(string $nombre, string $doc): void
    {
        $this->clienteNombre           = $nombre;
        $this->clienteDoc              = $doc;
        $this->resultadosClientes      = [];
        $this->buscarClienteModal      = '';
        $this->resultadosClientesModal = [];
        $this->dispatch('cerrarOffcanvasCliente');
    }

    // ── Autocomplete Razón Social (Factura) ───────────────────

    public function updatedClienteNombre(): void
    {
        $termino = trim($this->clienteNombre);
        if (strlen($termino) < 2) {
            $this->resultadosClientesFactura = [];
            return;
        }

        $this->resultadosClientesFactura = DB::table('clientes')
            ->where('cliente_estado', 1)
            ->where(function ($q) use ($termino) {
                $q->where('cliente_nombre', 'like', '%' . $termino . '%')
                  ->orWhere('cliente_razonsocial', 'like', '%' . $termino . '%')
                  ->orWhere('cliente_numero', 'like', '%' . $termino . '%');
            })
            ->select('id_clientes', 'cliente_nombre', 'cliente_razonsocial', 'cliente_numero')
            ->orderBy('cliente_nombre')
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function seleccionarClienteFactura(int $index): void
    {
        $cli = $this->resultadosClientesFactura[$index] ?? null;
        if (!$cli) return;

        $doc    = $cli->cliente_numero ?? '';
        $nombre = ($cli->cliente_razonsocial ?? '') ?: ($cli->cliente_nombre ?? '');

        if (strlen(trim($doc)) !== 11) {
            $this->addError('errorFactura', 'Este cliente tiene DNI. Para emitir Factura debe seleccionar un cliente con RUC (11 dígitos).');
            return;
        }

        $this->clienteNombre             = $nombre;
        $this->clienteDoc                = $doc;
        $this->resultadosClientesFactura = [];
        $this->resetErrorBag('errorFactura');
    }

    public function buscarRucFactura(): void
    {
        $ruc = trim($this->clienteNombre);

        if (!preg_match('/^\d{11}$/', $ruc)) {
            $this->addError('errorFactura', 'Para buscar por RUC escribe exactamente 11 dígitos numéricos en el campo.');
            return;
        }

        $this->resetErrorBag('errorFactura');
        $this->resultadosClientesFactura = [];

        $cliente = DB::table('clientes')->where('cliente_numero', $ruc)->first();
        if ($cliente) {
            $this->clienteNombre = ($cliente->cliente_razonsocial ?? '') ?: ($cliente->cliente_nombre ?? '');
            $this->clienteDoc    = $ruc;
            return;
        }

        $resultado = (new General())->consultar_documento_migo('4', $ruc);
        if (($resultado['success'] ?? false) && !empty($resultado['data']['nombre'])) {
            $this->clienteNombre = $resultado['data']['nombre'];
            $this->clienteDoc    = $ruc;
        } else {
            $this->addError('errorFactura', 'RUC no encontrado en base de datos ni en SUNAT. Verifique el número.');
        }
    }

    // ── Caché de productos para búsqueda client-side ─────────

    #[Renderless]
    public function obtenerProductosCache(): array
    {
        if (!$this->idTienda) return [];

        return DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('ps.id_tienda', $this->idTienda)
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1)
            ->where('ps.ps_stock', '>', 0)
            ->select(
                'p.id_pro', 'p.pro_nombre',
                'p.pro_codigo', 'p.pro_codigo_interno',
                'ps.ps_precio_uni_2 as precio_mayorista',
                'ps.ps_precio_uni   as precio_publico',
                'ps.ps_stock',
                'm.medida_codigo_unidad as medida'
            )
            ->orderBy('p.pro_nombre')
            ->get()
            ->toArray();
    }

    // ── Productos del formulario ──────────────────────────────

    public function verificarPresentaciones(int $idPro, string $nombre, float $precioMayorista, float $precioPublico, float $stock, string $medida, string $codigo): void
    {
        $presentaciones = DB::table('producto_presentaciones')
            ->where('id_pro', $idPro)
            ->where('pres_estado', 1)
            ->orderBy('pres_factor')
            ->get();

        if ($presentaciones->count() <= 1) {
            $pres = $presentaciones->first();
            if ($pres && (float)$pres->pres_factor > 0) {
                $factor = (float)$pres->pres_factor;
                if ($stock < $factor) {
                    session()->flash('error', 'Stock insuficiente para "' . $nombre . '" en presentación ' . $pres->pres_nombre . '. Necesitas ' . (int)$factor . ' unidades y solo hay ' . (int)$stock . '.');
                    $this->dispatch('enfocarBuscador');
                    return;
                }
                $this->agregarProducto($idPro, $nombre, (float)$pres->pres_precio_2, (float)$pres->pres_precio_1, $stock, $pres->pres_nombre, $codigo, $factor);
            } else {
                $this->agregarProducto($idPro, $nombre, $precioMayorista, $precioPublico, $stock, $medida, $codigo);
            }
            return;
        }

        $this->productoPendienteData = compact('idPro', 'nombre', 'precioMayorista', 'precioPublico', 'stock', 'medida', 'codigo');
        $this->presentacionesPendientes = $presentaciones->map(fn($p) => [
            'id_pres'          => (int) $p->id_pres,
            'pres_nombre'      => $p->pres_nombre,
            'pres_abreviatura' => $p->pres_abreviatura,
            'pres_factor'      => (float) $p->pres_factor,
            'pres_precio_1'    => (float) $p->pres_precio_1,
            'pres_precio_2'    => (float) $p->pres_precio_2,
        ])->toArray();

        $this->dispatch('abrirModalPresentaciones');
    }

    public function seleccionarPresentacion(int $idPres): void
    {
        $pres = collect($this->presentacionesPendientes)->firstWhere('id_pres', $idPres);
        if (!$pres || empty($this->productoPendienteData)) return;

        $d      = $this->productoPendienteData;
        $factor = (float)$pres['pres_factor'];
        $stock  = (float)$d['stock'];

        if ($stock < $factor) {
            $this->presentacionesPendientes = [];
            $this->productoPendienteData    = [];
            $this->dispatch('cerrarModalPresentaciones');
            session()->flash('error', 'Stock insuficiente para "' . $d['nombre'] . '" en presentación ' . $pres['pres_nombre'] . '. Necesitas ' . (int)$factor . ' unidades y solo hay ' . (int)$stock . '.');
            return;
        }

        $this->agregarProducto(
            (int)   $d['idPro'],
            (string)$d['nombre'],
            (float) $pres['pres_precio_2'],
            (float) $pres['pres_precio_1'],
            $stock,
            (string)$pres['pres_nombre'],
            (string)$d['codigo'],
            $factor
        );

        $this->presentacionesPendientes = [];
        $this->productoPendienteData    = [];
        $this->dispatch('cerrarModalPresentaciones');
    }

    public function agregarProducto(int $idPro, string $nombre, float $precioMayorista, float $precioPublico, float $stock = 0, string $medida = '', string $codigo = '', float $presFactor = 1.0): void
    {
        if ($precioMayorista <= 0 && $precioPublico <= 0) {
            session()->flash('error', 'El producto "' . $nombre . '" no tiene precio configurado. Configura al menos un precio antes de agregarlo.');
            $this->buscarProducto      = '';
            $this->resultadosProductos = [];
            return;
        }

        foreach ($this->items as $item) {
            if ($item['id_pro'] == $idPro) {
                $this->dispatch('enfocarBuscador');
                return;
            }
        }

        $this->items[] = [
            'id_pro'           => $idPro,
            'codigo'           => $codigo,
            'nombre'           => $nombre,
            'precio_mayorista' => $precioMayorista,
            'precio_publico'   => $precioPublico,
            'tipo_precio'      => 'publico',
            'precio'           => $precioPublico,
            'cantidad'         => 1,
            'stock'            => $stock,
            'medida'           => $medida,
            'pres_factor'      => $presFactor,
        ];

        $this->dispatch('enfocarBuscador');
    }

    public function cambiarTipoPrecio(int $idx, string $tipo): void
    {
        if (!isset($this->items[$idx])) return;
        $this->items[$idx]['tipo_precio'] = $tipo;
        $nuevo = $tipo === 'mayorista'
            ? $this->items[$idx]['precio_mayorista']
            : $this->items[$idx]['precio_publico'];
        $this->items[$idx]['precio'] = max($nuevo, $this->precioMinimo($this->items[$idx]));
    }

    public function quitarItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    // ── Ver detalle ───────────────────────────────────────────

    public function verDetalle(int $id): void
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $id)->first();
        if (!$pedido) return;

        $this->idDetalle           = $id;
        $this->detallePedidoNumero = $pedido->pedido_numero;

        $this->detalleItems = DB::table('pedidos_detalle as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->where('pd.id_pedido', $id)
            ->where('pd.pedido_deta_estado', 1)
            ->select(
                'p.pro_nombre', 'p.pro_codigo',
                'pd.pedido_deta_cantidad as cantidad',
                'pd.pedido_deta_precio   as precio',
                'pd.pres_nombre'
            )
            ->get()
            ->toArray();

        $this->dispatch('abrirModalDetallePedido');
    }

    // ── Editar pedido ─────────────────────────────────────────

    public function editarPedido(int $id): void
    {
        if (!auth()->user()->can('pedidos.actualizar')) {
            session()->flash('error', 'No tienes permiso para editar pedidos.');
            return;
        }

        $pedido = DB::table('pedidos')
            ->where('id_pedido', $id)
            ->where('pedido_estado', 0)
            ->first();

        if (!$pedido) {
            session()->flash('error', 'Solo se pueden editar pedidos en estado Pendiente.');
            return;
        }

        $this->resetFormulario();
        $this->idEditar        = $id;
        $this->clienteNombre   = $pedido->pedido_cliente_nombre    ?? '';
        $this->clienteDoc      = $pedido->pedido_cliente_doc       ?? '';
        $this->observacion     = $pedido->pedido_observacion       ?? '';
        $this->tipoComprobante = $pedido->pedido_tipo_comprobante  ?? '03';
        $this->tipoPago        = (int) ($pedido->pedido_tipo_pago  ?? 1);

        $this->items = DB::table('pedidos_detalle as pd')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->leftJoin('producto_sucursal as ps', function ($j) {
                $j->on('ps.id_pro', '=', 'pd.id_pro')
                  ->where('ps.id_tienda', $this->idTienda);
            })
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('pd.id_pedido', $id)
            ->where('pd.pedido_deta_estado', 1)
            ->select(
                'pd.id_pro',
                DB::raw('COALESCE(p.pro_nombre, pd.pedido_deta_nombre) as pro_nombre'),
                'p.pro_codigo',
                'pd.pedido_deta_precio as precio',
                'pd.pedido_deta_cantidad as cantidad',
                'pd.pres_factor',
                'ps.ps_stock',
                'ps.ps_precio_uni_2 as precio_mayorista',
                'ps.ps_precio_uni   as precio_publico',
                'm.medida_codigo_unidad as medida'
            )
            ->get()
            ->map(function ($r) {
                $mayorista = (float) ($r->precio_mayorista ?? 0);
                $publico   = (float) ($r->precio_publico   ?? 0);
                $precio    = (float) $r->precio;
                $tipo      = abs($precio - $mayorista) <= 0.001 ? 'mayorista' : 'publico';
                $factor    = (float) ($r->pres_factor ?? 1.0);
                return [
                    'id_pro'           => $r->id_pro ? (int) $r->id_pro : null,
                    'codigo'           => (string) ($r->pro_codigo ?? ''),
                    'nombre'           => (string) ($r->pro_nombre ?? ''),
                    'precio_mayorista' => $mayorista,
                    'precio_publico'   => $publico,
                    'tipo_precio'      => $tipo,
                    'precio'           => $precio,
                    'cantidad'         => (float) $r->cantidad,
                    'stock'            => $factor > 0 ? round((float) ($r->ps_stock ?? 0) / $factor, 2) : (float) ($r->ps_stock ?? 0),
                    'medida'           => (string) ($r->medida ?? ''),
                    'pres_factor'      => $factor,
                    'tipo'             => $r->id_pro ? 'producto' : 'servicio',
                ];
            })
            ->toArray();

        $this->vista = 'nuevo';
    }

    public function actualizar(): void
    {
        if (!auth()->user()->can('pedidos.actualizar')) {
            session()->flash('error', 'No tienes permiso para editar pedidos.');
            return;
        }

        if (empty($this->items)) {
            session()->flash('error', 'Debe agregar al menos un producto al pedido.');
            return;
        }

        $totalPedido = collect($this->items)->sum(fn($i) => (float)($i['precio'] ?? 0) * (float)($i['cantidad'] ?? 0));
        if ($totalPedido <= 0) {
            session()->flash('error', 'El monto total del pedido debe ser mayor a S/ 0.00.');
            return;
        }

        if ($this->tipoComprobante === '01' && strlen(trim($this->clienteDoc)) !== 11) {
            session()->flash('error', 'Para emitir Factura es obligatorio ingresar el RUC (11 dígitos) del cliente.');
            return;
        }

        if (!$this->validarPreciosMayorista()) return;

        DB::beginTransaction();
        try {
            $pedidoActual = DB::table('pedidos')
                ->where('id_pedido', $this->idEditar)
                ->lockForUpdate()
                ->first();

            // Devolver stock del pedido anterior si estaba reservado
            if ($pedidoActual && $pedidoActual->pedido_stock_reservado) {
                $itemsAnteriores = DB::table('pedidos_detalle')
                    ->where('id_pedido', $this->idEditar)
                    ->where('pedido_deta_estado', 1)
                    ->get();

                foreach ($itemsAnteriores as $old) {
                    if (!$old->id_pro) continue;
                    $factor = (float)($old->pres_factor ?? 1.0);
                    DB::table('producto_sucursal')
                        ->where('id_pro',    (int) $old->id_pro)
                        ->where('id_tienda', $this->idTienda)
                        ->increment('ps_stock', (float) $old->pedido_deta_cantidad * $factor);
                }
            }

            // Validar stock disponible para los nuevos items y adquirir locks
            foreach (collect($this->items)->sortBy('id_pro') as $item) {
                $factor = (float)($item['pres_factor'] ?? 1.0);
                $ps = DB::table('producto_sucursal')
                    ->where('id_pro',    (int) $item['id_pro'])
                    ->where('id_tienda', $this->idTienda)
                    ->lockForUpdate()
                    ->first();

                if (!$ps || (float) $ps->ps_stock < (float) $item['cantidad'] * $factor) {
                    DB::rollBack();
                    $disponible = $ps ? round((float)$ps->ps_stock / max($factor, 1), 2) : 0;
                    session()->flash('error', 'Stock insuficiente para "' . $item['nombre'] . '". Disponible: ' . $disponible);
                    return;
                }
            }

            DB::table('pedidos')
                ->where('id_pedido', $this->idEditar)
                ->update([
                    'pedido_cliente_nombre'   => trim($this->clienteNombre) ?: null,
                    'pedido_cliente_doc'      => trim($this->clienteDoc)    ?: null,
                    'pedido_observacion'      => trim($this->observacion)   ?: null,
                    'pedido_tipo_comprobante' => $this->tipoComprobante,
                    'pedido_stock_reservado'  => 1,
                    'updated_at'              => now(),
                ]);

            DB::table('pedidos_detalle')
                ->where('id_pedido', $this->idEditar)
                ->update(['pedido_deta_estado' => 0, 'updated_at' => now()]);

            foreach ($this->items as $item) {
                $factor = (float)($item['pres_factor'] ?? 1.0);
                DB::table('pedidos_detalle')->insert([
                    'id_pedido'            => $this->idEditar,
                    'id_pro'               => $item['id_pro'],
                    'pedido_deta_nombre'   => $item['nombre'],
                    'pedido_deta_cantidad' => $item['cantidad'],
                    'pedido_deta_precio'   => $item['precio'],
                    'pedido_deta_estado'   => 1,
                    'pres_factor'          => $factor,
                    'pres_nombre'          => $item['medida'] ?? null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                // Reservar stock para los nuevos items
                DB::table('producto_sucursal')
                    ->where('id_pro',    (int) $item['id_pro'])
                    ->where('id_tienda', $this->idTienda)
                    ->decrement('ps_stock', (float) $item['cantidad'] * $factor);
            }

            DB::commit();
            $this->dispatch('cerrarModalComprobante');
            $this->resetFormulario();
            $this->vista = 'nuevo';
            $this->dispatch('vistaNuevo');
            session()->flash('success', 'Pedido actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al actualizar el pedido.');
        }
    }

    // ── Nuevo registro tras éxito ─────────────────────────────

    public function nuevoRegistro(): void
    {
        $this->pedidoGuardadoNumero   = '';
        $this->proformaGuardadoNumero = '';
        $this->dispatch('enfocarBuscador');
    }

    // ── Vista nuevo / historial ───────────────────────────────

    public function nuevoPedido(): void
    {
        $this->resetFormulario();
        $this->vista = 'nuevo';
        $this->dispatch('vistaNuevo');
    }

    public function volverHistorial(): void
    {
        $this->resetFormulario();
        $this->vista = 'historial';
        $this->dispatch('vistaHistorial');
    }

    // ── Guardar pedido ────────────────────────────────────────

    private function validarPreciosMayorista(): bool
    {
        if ($this->esPrivilegiado()) return true;

        foreach ($this->items as $item) {
            if (($item['tipo'] ?? 'producto') === 'servicio') continue;
            $minimo = $this->precioMinimo($item);
            if ((float) $item['precio'] < $minimo) {
                session()->flash('error', 'El precio de "' . $item['nombre'] . '" no puede ser menor al precio de venta (S/ ' . number_format($minimo, 2) . ').');
                return false;
            }
        }
        return true;
    }

    // ── Proforma rápida ──────────────────────────────────────

    public function updatedProformaRazonSocial(): void
    {
        $termino = trim($this->proformaRazonSocial);
        if (strlen($termino) < 2) {
            $this->resultadosClientesProforma = [];
            return;
        }

        $this->resultadosClientesProforma = DB::table('clientes')
            ->where('cliente_estado', 1)
            ->where(function ($q) use ($termino) {
                $q->where('cliente_nombre',      'like', '%' . $termino . '%')
                  ->orWhere('cliente_razonsocial', 'like', '%' . $termino . '%')
                  ->orWhere('cliente_numero',      'like', '%' . $termino . '%');
            })
            ->select('id_clientes', 'cliente_nombre', 'cliente_razonsocial', 'cliente_numero')
            ->orderBy('cliente_nombre')
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function seleccionarClienteProforma(int $index): void
    {
        $cli = $this->resultadosClientesProforma[$index] ?? null;
        if (!$cli) return;

        $this->proformaRazonSocial        = ($cli->cliente_razonsocial ?? '') ?: ($cli->cliente_nombre ?? '');
        $this->proformaDoc                = $cli->cliente_numero ?? '';
        $this->resultadosClientesProforma = [];
        $this->resetErrorBag('errorProforma');
    }

    public function buscarApiProforma(): void
    {
        $termino = trim($this->proformaRazonSocial);
        $len     = strlen($termino);

        if (!preg_match('/^\d{8}$|^\d{11}$/', $termino)) {
            $this->addError('errorProforma', 'Para buscar en API escribe exactamente 8 dígitos (DNI) u 11 dígitos (RUC) en el campo.');
            return;
        }

        $this->resetErrorBag('errorProforma');
        $this->resultadosClientesProforma = [];

        $cliente = DB::table('clientes')->where('cliente_numero', $termino)->first();
        if ($cliente) {
            $this->proformaRazonSocial = ($cliente->cliente_razonsocial ?? '') ?: ($cliente->cliente_nombre ?? '');
            $this->proformaDoc         = $termino;
            return;
        }

        $tipo      = $len === 11 ? '4' : '2';
        $resultado = (new General())->consultar_documento_migo($tipo, $termino);

        if (($resultado['success'] ?? false) && !empty($resultado['data']['nombre'])) {
            $this->proformaRazonSocial = $resultado['data']['nombre'];
            $this->proformaDoc         = $termino;
        } else {
            $this->addError('errorProforma', 'Documento no encontrado en BD ni en la API. Verifica el número.');
        }
    }

    public function abrirModalProforma(): void
    {
        if (!auth()->user()->can('pedidos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar proformas.');
            return;
        }

        if (empty($this->items)) {
            session()->flash('error', 'Debe agregar al menos un producto a la proforma.');
            return;
        }

        $total = collect($this->items)->sum(fn($i) => (float)($i['precio'] ?? 0) * (float)($i['cantidad'] ?? 0));
        if ($total <= 0) {
            session()->flash('error', 'El monto total de la proforma debe ser mayor a S/ 0.00.');
            return;
        }

        $this->proformaDoc                = '';
        $this->proformaRazonSocial        = '';
        $this->resultadosClientesProforma = [];
        $this->proformaFormaPago          = 1;
        $this->proformaLugarEntrega       = 'Previa Coordinación';
        $this->proformaObservacion        = 'Los precios están sujetos a variaciones';
        $this->resetErrorBag('errorProforma');

        $this->dispatch('abrirModalProforma');
    }

    public function confirmarGuardarProforma(): void
    {
        if (!auth()->user()->can('pedidos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar proformas.');
            return;
        }

        $doc   = trim($this->proformaDoc);
        $razon = trim($this->proformaRazonSocial);

        if (!$razon) {
            session()->flash('error', 'El nombre del cliente es obligatorio.');
            return;
        }

        DB::beginTransaction();
        try {
            $cliente = $doc ? DB::table('clientes')->where('cliente_numero', $doc)->first() : null;
            if (!$cliente) {
                $docLen    = strlen($doc);
                $idTipoDoc = DB::table('tipo_documento')
                    ->where('tipo_documento_identidad_abr', $docLen === 11 ? 'RUC' : 'DNI')
                    ->value('id_tipo_documento') ?? ($docLen === 11 ? 4 : 2);

                $idCliente = DB::table('clientes')->insertGetId([
                    'id_tipo_documento'   => $idTipoDoc,
                    'cliente_razonsocial' => $docLen === 11 ? $razon : null,
                    'cliente_nombre'      => $razon,
                    'cliente_numero'      => $doc ?: null,
                    'cliente_direccion'   => '',
                    'cliente_telefono'    => '',
                    'cliente_fecha'       => now(),
                    'cliente_estado'      => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            } else {
                $idCliente = $cliente->id_clientes;
            }

            $idSucursal = DB::table('user_sucursal')
                ->where('id_users', auth()->user()->id_users)
                ->orderBy('id_sucursal')
                ->value('id_sucursal') ?? 0;

            $ultimo      = DB::table('proformas')->where('profo_serie', 'PRO')->orderBy('id_profo', 'desc')->first();
            $correlativo  = $ultimo ? ($ultimo->profo_correlativo + 1) : 1;
            $proformaNumero = 'PRO-' . str_pad($correlativo, 6, '0', STR_PAD_LEFT);

            $idProfo = DB::table('proformas')->insertGetId([
                'id_clientes'         => $idCliente,
                'id_users'            => auth()->user()->id_users,
                'id_sucursal'         => $idSucursal,
                'profo_forma_pago'    => $this->proformaFormaPago,
                'profo_lugar_entrega' => trim($this->proformaLugarEntrega),
                'profo_observacion'   => trim($this->proformaObservacion),
                'profo_serie'         => 'PRO',
                'profo_correlativo'   => $correlativo,
                'profo_fecha_emision' => now()->toDateString(),
                'profo_estado'        => 1,
                'profo_acti_estado'   => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($this->items as $item) {
                DB::table('proformas_detalles')->insert([
                    'id_profo'            => $idProfo,
                    'id_pro'              => $item['id_pro'],
                    'profo_deta_precio'   => $item['precio'],
                    'profo_deta_cantidad' => $item['cantidad'],
                    'profo_deta_estado'   => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::commit();
            $this->resetFormulario();
            $this->proformaGuardadoNumero = $proformaNumero;
            $this->dispatch('abrirModalExitoProforma');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la proforma.');
        }
    }

    public function abrirModalComprobanteEdicion(): void
    {
        if (!auth()->user()->can('pedidos.actualizar')) {
            session()->flash('error', 'No tienes permiso para editar pedidos.');
            return;
        }

        if (empty($this->items)) {
            session()->flash('error', 'Debe agregar al menos un producto al pedido.');
            return;
        }

        $totalPedido = collect($this->items)->sum(fn($i) => (float)($i['precio'] ?? 0) * (float)($i['cantidad'] ?? 0));
        if ($totalPedido <= 0) {
            session()->flash('error', 'El monto total del pedido debe ser mayor a S/ 0.00.');
            return;
        }

        if (!$this->validarPreciosMayorista()) return;

        if (!$this->idTienda) {
            session()->flash('error', 'No tiene tienda asignada. Contacte al administrador.');
            return;
        }

        // No limpiar clienteNombre/clienteDoc — ya vienen cargados del pedido
        $this->dispatch('abrirModalTipoComprobanteEdicion', tipo: $this->tipoComprobante);
    }

    public function actualizarConTipo(string $tipoComprobante): void
    {
        $this->tipoComprobante = $tipoComprobante;
        $this->actualizar();
    }

    public function abrirModalComprobante(): void
    {
        if (!auth()->user()->can('pedidos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar pedidos.');
            return;
        }

        if (empty($this->items)) {
            session()->flash('error', 'Debe agregar al menos un producto al pedido.');
            return;
        }

        $totalPedido = collect($this->items)->sum(fn($i) => (float)($i['precio'] ?? 0) * (float)($i['cantidad'] ?? 0));
        if ($totalPedido <= 0) {
            session()->flash('error', 'El monto total del pedido debe ser mayor a S/ 0.00.');
            return;
        }

        if (!$this->validarPreciosMayorista()) return;

        if (!$this->idTienda) {
            session()->flash('error', 'No tiene tienda asignada. Contacte al administrador.');
            return;
        }

        // Limpiar datos cliente del intento anterior
        $this->clienteNombre      = '';
        $this->clienteDoc         = '';
        $this->resultadosClientes = [];

        $this->dispatch('abrirModalTipoComprobante');
    }

    public function confirmarGuardar(string $tipoComprobante): void
    {
        // Si hay un pedido en edición, derivar al flujo de actualización
        if ($this->idEditar) {
            $this->actualizarConTipo($tipoComprobante);
            return;
        }

        if (!auth()->user()->can('pedidos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar pedidos.');
            return;
        }

        $this->tipoComprobante = $tipoComprobante;

        if ($this->tipoComprobante === '01' && strlen(trim($this->clienteDoc)) !== 11) {
            session()->flash('error', 'Para emitir Factura es obligatorio ingresar el RUC (11 dígitos) del cliente.');
            return;
        }

        DB::beginTransaction();
        try {
            // Generar número de pedido: PED-YYYY-NNNN
            $year  = now()->format('Y');
            $prefix = "PED-{$year}-";

            $ultimo = DB::table('pedidos')
                ->where('pedido_numero', 'like', $prefix . '%')
                ->orderBy('id_pedido', 'desc')
                ->value('pedido_numero');

            $numero = 1;
            if ($ultimo) {
                $partes = explode('-', $ultimo);
                $numero = (int) end($partes) + 1;
            }

            $pedidoNumero = $prefix . str_pad($numero, 4, '0', STR_PAD_LEFT);

            // Validar stock disponible y adquirir locks antes de insertar
            foreach (collect($this->items)->sortBy('id_pro') as $item) {
                $factor = (float)($item['pres_factor'] ?? 1.0);
                $ps = DB::table('producto_sucursal')
                    ->where('id_pro',    (int) $item['id_pro'])
                    ->where('id_tienda', $this->idTienda)
                    ->lockForUpdate()
                    ->first();

                if (!$ps || (float) $ps->ps_stock < (float) $item['cantidad'] * $factor) {
                    DB::rollBack();
                    $disponible = $ps ? round((float)$ps->ps_stock / max($factor, 1), 2) : 0;
                    session()->flash('error', 'Stock insuficiente para "' . $item['nombre'] . '". Disponible: ' . $disponible);
                    return;
                }
            }

            $idPedido = DB::table('pedidos')->insertGetId([
                'id_empresa'              => $this->idEmpresa,
                'id_tienda'               => $this->idTienda,
                'id_users'                => auth()->user()->id_users,
                'id_clientes'             => null,
                'pedido_numero'           => $pedidoNumero,
                'pedido_cliente_nombre'   => trim($this->clienteNombre) ?: null,
                'pedido_cliente_doc'      => trim($this->clienteDoc) ?: null,
                'pedido_observacion'      => trim($this->observacion) ?: null,
                'pedido_tipo_comprobante' => $this->tipoComprobante,
                'pedido_tipo_pago'        => null,
                'pedido_estado'           => 0,
                'pedido_stock_reservado'  => 1,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            foreach ($this->items as $item) {
                $factor = (float)($item['pres_factor'] ?? 1.0);
                DB::table('pedidos_detalle')->insert([
                    'id_pedido'             => $idPedido,
                    'id_pro'                => $item['id_pro'],
                    'pedido_deta_nombre'    => $item['nombre'],
                    'pedido_deta_cantidad'  => $item['cantidad'],
                    'pedido_deta_precio'    => $item['precio'],
                    'pedido_deta_estado'    => 1,
                    'pres_factor'           => $factor,
                    'pres_nombre'           => $item['medida'] ?? null,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                // Reservar stock: descontar de disponible hasta que se cobre o anule
                DB::table('producto_sucursal')
                    ->where('id_pro',    (int) $item['id_pro'])
                    ->where('id_tienda', $this->idTienda)
                    ->decrement('ps_stock', (float) $item['cantidad'] * $factor);
            }

            DB::commit();
            $this->resetFormulario();
            $this->pedidoGuardadoNumero = str_pad($numero, 4, '0', STR_PAD_LEFT);
            $this->dispatch('abrirModalExito');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el pedido.');
        }
    }

    // ── Anular pedido ─────────────────────────────────────────

    public function confirmarAnular(int $idPedido): void
    {
        $this->idAnular = $idPedido;
        $this->dispatch('abrirModalAnularPedido');
    }

    public function anular(): void
    {
        if (!auth()->user()->can('pedidos.cambiar_estado')) {
            $this->dispatch('cerrarModalAnularPedido');
            session()->flash('error', 'No tienes permiso para anular pedidos.');
            return;
        }

        DB::beginTransaction();
        try {
            $pedido = DB::table('pedidos')
                ->where('id_pedido', $this->idAnular)
                ->where('pedido_estado', 0)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                DB::rollBack();
                $this->idAnular = null;
                $this->dispatch('cerrarModalAnularPedido');
                session()->flash('error', 'El pedido no está disponible para anular.');
                return;
            }

            // Devolver stock reservado al disponible
            if ($pedido->pedido_stock_reservado) {
                $detalles = DB::table('pedidos_detalle')
                    ->where('id_pedido', $this->idAnular)
                    ->where('pedido_deta_estado', 1)
                    ->get();

                foreach ($detalles as $det) {
                    if (!$det->id_pro) continue;
                    $factor = (float)($det->pres_factor ?? 1.0);
                    DB::table('producto_sucursal')
                        ->where('id_pro',    (int) $det->id_pro)
                        ->where('id_tienda', $pedido->id_tienda)
                        ->increment('ps_stock', (float) $det->pedido_deta_cantidad * $factor);
                }
            }

            DB::table('pedidos')
                ->where('id_pedido', $this->idAnular)
                ->update(['pedido_estado' => 3, 'updated_at' => now()]);

            DB::commit();
            $this->idAnular = null;
            $this->dispatch('cerrarModalAnularPedido');
            session()->flash('success', 'Pedido anulado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al anular el pedido.');
        }
    }

    // ── Recuperar pre-venta ───────────────────────────────────

    public function abrirModalRecuperar(): void
    {
        $this->pedidoRecuperableId   = null;
        $this->detalleRecuperable    = [];
        $this->claveRecuperar        = '';
        $this->modoPasswordRecuperar = false;
        $this->errorClaveRecuperar   = '';

        $this->pedidosRecuperables = DB::table('pedidos as p')
            ->join('users as u', 'u.id_users', '=', 'p.id_users')
            ->where('p.pedido_estado', 0)
            ->whereDate('p.created_at', now()->toDateString())
            ->select(
                'p.id_pedido', 'p.pedido_numero',
                'p.pedido_cliente_nombre', 'p.pedido_cliente_doc',
                'p.pedido_tipo_comprobante', 'p.created_at',
                'u.nombre_users',
                DB::raw('(SELECT SUM(pd.pedido_deta_precio * pd.pedido_deta_cantidad)
                          FROM pedidos_detalle pd
                          WHERE pd.id_pedido = p.id_pedido AND pd.pedido_deta_estado = 1) as total_pedido')
            )
            ->orderBy('p.id_pedido', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        $this->dispatch('abrirModalRecuperar');
    }

    public function seleccionarRecuperable(int $id): void
    {
        $this->pedidoRecuperableId = $id;
        $this->solicitarClaveRecuperar();
    }

    public function solicitarClaveRecuperar(): void
    {
        if (!$this->pedidoRecuperableId) return;

        $this->claveRecuperar        = '';
        $this->errorClaveRecuperar   = '';
        $this->modoPasswordRecuperar = true;
        $this->dispatch('enfocarInputClaveRecuperar');
    }

    public function confirmarRecuperar(): void
    {
        if (!$this->pedidoRecuperableId) return;

        if (!Hash::check($this->claveRecuperar, auth()->user()->password)) {
            $this->errorClaveRecuperar = 'Contraseña incorrecta. Intente nuevamente.';
            $this->claveRecuperar      = '';
            $this->dispatch('enfocarInputClaveRecuperar');
            return;
        }

        $idPedido = $this->pedidoRecuperableId;

        $this->pedidosRecuperables   = [];
        $this->pedidoRecuperableId   = null;
        $this->detalleRecuperable    = [];
        $this->claveRecuperar        = '';
        $this->modoPasswordRecuperar = false;
        $this->errorClaveRecuperar   = '';

        $this->dispatch('cerrarModalRecuperar');
        $this->editarPedido($idPedido);
        $this->esRecuperar = true;   // setear DESPUÉS de editarPedido() para no ser pisado por resetFormulario()
        $this->dispatch('vistaNuevo');
    }

    public function cancelarPasswordRecuperar(): void
    {
        $this->modoPasswordRecuperar = false;
        $this->claveRecuperar        = '';
        $this->errorClaveRecuperar   = '';
    }

    // ── Reset formulario ──────────────────────────────────────

    private function resetFormulario(): void
    {
        $this->buscarProducto      = '';
        $this->resultadosProductos = [];
        $this->items               = [];
        $this->clienteNombre       = '';
        $this->resultadosClientes  = [];
        $this->clienteDoc          = '';
        $this->observacion         = '';
        $this->tipoComprobante       = '03';
        $this->tipoPago              = 1;
        $this->proformaDoc                = '';
        $this->proformaRazonSocial        = '';
        $this->resultadosClientesProforma = [];
        $this->proformaFormaPago          = 1;
        $this->proformaLugarEntrega  = 'Previa Coordinación';
        $this->proformaObservacion   = 'Los precios están sujetos a variaciones';
        $this->idEditar              = null;
        $this->idDetalle             = null;
        $this->detalleItems          = [];
        $this->detallePedidoNumero   = '';
        $this->pedidoGuardadoNumero  = '';
        $this->proformaGuardadoNumero  = '';
        $this->buscarClienteModal        = '';
        $this->resultadosClientesModal   = [];
        $this->resultadosClientesFactura = [];
        $this->modoEdicionCliente        = false;
        $this->editClienteDeFactura      = false;
        $this->editClienteId             = 0;
        $this->editClienteNombre         = '';
        $this->editClienteDoc            = '';
        $this->editClienteDireccion      = '';
        $this->nuevoClienteNombre        = '';
        $this->nuevoClienteDoc           = '';
        $this->nuevoClienteDireccion     = '';
        $this->nuevoClienteTipo          = '';
        $this->presentacionesPendientes  = [];
        $this->productoPendienteData     = [];
        $this->esRecuperar               = false;
        $this->pedidosRecuperables       = [];
        $this->pedidoRecuperableId       = null;
        $this->detalleRecuperable        = [];
        $this->claveRecuperar            = '';
        $this->modoPasswordRecuperar     = false;
        $this->errorClaveRecuperar       = '';
        $this->resetErrorBag();
    }

    // ── Render ────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        // En vista 'nuevo' no se muestra la tabla de pedidos: omitir la query costosa.
        if ($this->vista !== 'historial') {
            return view('livewire.gestion-ventas.pedidos', [
                'pedidos'       => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->porPagina),
                'esSuperAdmin'  => $this->esSuperAdmin(),
                'esRestringido' => $this->esRolRestringido(),
            ]);
        }

        $query = DB::table('pedidos as p')
            ->join('users as u', 'u.id_users', '=', 'p.id_users')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'p.id_tienda')
            ->select(
                'p.*',
                'u.nombre_users',
                't.tienda_nombre',
                DB::raw('(SELECT COUNT(*) FROM pedidos_detalle pd WHERE pd.id_pedido = p.id_pedido AND pd.pedido_deta_estado = 1) as total_items'),
                DB::raw('(SELECT SUM(pd.pedido_deta_precio * pd.pedido_deta_cantidad) FROM pedidos_detalle pd WHERE pd.id_pedido = p.id_pedido AND pd.pedido_deta_estado = 1) as total_monto')
            );

        if (!$this->esSuperAdmin()) {
            if ($this->idTienda) {
                $query->where('p.id_tienda', $this->idTienda);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($this->filtroEstado !== '') {
            $query->where('p.pedido_estado', (int) $this->filtroEstado);
        }

        if (trim($this->buscar) !== '') {
            $termino = trim($this->buscar);
            $query->where(function ($q) use ($termino) {
                $q->where('p.pedido_numero', 'like', '%' . $termino . '%')
                  ->orWhere('p.pedido_cliente_nombre', 'like', '%' . $termino . '%')
                  ->orWhere('p.pedido_cliente_doc', 'like', '%' . $termino . '%');
            });
        }

        $query->whereDate('p.created_at', now()->toDateString());

        $pedidos = $query->orderBy('p.id_pedido', 'desc')->paginate($this->porPagina);

        return view('livewire.gestion-ventas.pedidos', [
            'pedidos'       => $pedidos,
            'esSuperAdmin'  => $this->esSuperAdmin(),
            'esRestringido' => $this->esRolRestringido(),
        ]);
    }
}
