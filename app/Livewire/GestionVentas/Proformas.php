<?php

namespace App\Livewire\GestionVentas;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Proformas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Vista ─────────────────────────────────────────────────
    public string $vista = 'historial'; // historial | nueva

    // ── Rol y contexto ────────────────────────────────────────
    public int $empresaSeleccionada  = 0;
    public int $sucursalSeleccionada = 0;
    public $sucursalesDisponibles    = [];
    private int $cachedRoleId        = 0;

    // ── Filtros historial ─────────────────────────────────────
    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public int    $porPagina   = 10;

    // ── Formulario nueva proforma ─────────────────────────────
    public int    $idTipoDocumento = 2;
    public string $numDocumento    = '';
    public string $razonSocial     = '';
    public string $telefono        = '';
    public string $direccion       = '';
    public int    $formaPago       = 1;
    public string $lugarEntrega    = 'Previa Coordinación';
    public string $observaciones   = 'Los precios están sujetos a variaciones';
    public string $buscarProducto  = '';
    public array  $resultadosBusqueda = [];
    public array  $items              = [];

    // ── Mensajes de consulta ───────────────────────────────────
    public string $mensajeConsulta      = '';
    public string $tipoMensajeConsulta  = '';

    // ── Aprobación / Anulación ────────────────────────────────
    public ?int $idAprobar = null;
    public ?int $idAnular  = null;

    private $logs;
    private $general;

    public function boot(): void
    {
        $this->logs    = new Logs();
        $this->general = new \App\Models\General();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool   { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool        { return $this->cachedRoleId === 2; }
    private function esVendedor(): bool     { return $this->cachedRoleId === 3; }
    private function esPrivilegiado(): bool { return in_array($this->cachedRoleId, [1, 2, 3]); }

    private function resolverSucursal(): int
    {
        return $this->sucursalSeleccionada;
    }

    private function cargarSucursales(): void
    {
        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = collect();
        }
    }

    private function preSeleccionarUbicacion(): void
    {
        if ($this->esSuperAdmin()) {
            $tienda = DB::table('tiendas as t')
                ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
                ->where('t.tienda_estado', 1)
                ->where('e.empresa_estado', '!=', 0)
                ->orderBy('t.id_tienda')
                ->select('t.id_tienda', 't.id_empresa')
                ->first();

            if ($tienda) {
                $this->empresaSeleccionada  = (int) $tienda->id_empresa;
                $this->cargarSucursales();
                $this->sucursalSeleccionada = (int) $tienda->id_tienda;
            }
            return;
        }

        $idTienda = 0;
        $sesion   = (int) session('sucursal_activa_id', 0);
        if ($sesion < 0) { $idTienda = abs($sesion); }
        if (!$idTienda) {
            $idTienda = (int) DB::table('user_tienda')
                ->where('id_users', auth()->user()->id_users)
                ->orderBy('id_tienda')->value('id_tienda');
        }
        if ($idTienda > 0) {
            $empId = (int) DB::table('tiendas')->where('id_tienda', $idTienda)->value('id_empresa');
            if ($empId) {
                $this->empresaSeleccionada = $empId;
                $this->cargarSucursales();
                $this->sucursalSeleccionada = $idTienda;
            }
        }
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_proformas.listar'), 403);

        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $this->preSeleccionarUbicacion();
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->cargarSucursales();
        $this->resetPage();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->resetPage();
    }

    public function updatedBuscarProducto(): void
    {
        $this->resultadosBusqueda = $this->consultarProductos();
    }

    // ── Consulta automática de documento ──────────────────────

    public function updatedNumDocumento(): void
    {
        $this->mensajeConsulta     = '';
        $this->tipoMensajeConsulta = '';

        $doc = trim($this->numDocumento);
        $len = strlen($doc);

        if ($len !== 8 && $len !== 11) return;

        $tipo = $len === 11 ? 4 : 2;
        $this->idTipoDocumento = $tipo;

        // Buscar primero en la BD local
        $cliente = DB::table('clientes')
            ->where('cliente_numero', $doc)
            ->where('cliente_estado', 1)
            ->first();

        if ($cliente) {
            $this->razonSocial         = $cliente->cliente_razonsocial ?? $cliente->cliente_nombre ?? '';
            $this->direccion           = $cliente->cliente_direccion ?? '';
            $this->telefono            = $cliente->cliente_telefono ?? '';
            $this->mensajeConsulta     = 'Cliente encontrado en el sistema.';
            $this->tipoMensajeConsulta = 'success';
            return;
        }

        try {
            $resp = $this->general->consultar_documento_migo($tipo, $doc);

            if (($resp['success'] ?? false) === true && !empty($resp['data'])) {
                $data              = $resp['data'];
                $this->razonSocial = $data['nombre']    ?? '';
                $this->direccion   = $data['direccion'] ?? '';

                if (isset($resp['warning'])) {
                    $this->mensajeConsulta     = $resp['warning'];
                    $this->tipoMensajeConsulta = 'warning';
                } else {
                    $this->mensajeConsulta     = $resp['message'] ?? 'Datos encontrados.';
                    $this->tipoMensajeConsulta = 'success';
                }
            } else {
                $this->mensajeConsulta     = $resp['message'] ?? 'No se encontró información para el documento.';
                $this->tipoMensajeConsulta = 'error';
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->mensajeConsulta     = 'Error al consultar el documento.';
            $this->tipoMensajeConsulta = 'error';
        }
    }

    private function consultarProductos(): array
    {
        $idSucursal = $this->resolverSucursal();

        if (!$idSucursal) return [];

        $termino = trim($this->buscarProducto);
        if (strlen($termino) < 2) return [];

        return DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('ps.id_tienda', $idSucursal)
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1)
            ->where('ps.ps_stock', '>', 0)
            ->where(function ($q) use ($termino) {
                $q->where('p.pro_nombre',          'like', '%' . $termino . '%')
                  ->orWhere('p.pro_codigo',         'like', '%' . $termino . '%')
                  ->orWhere('p.pro_codigo_interno', 'like', '%' . $termino . '%');
            })
            ->select(
                'p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'p.pro_codigo_interno',
                'ps.ps_precio_uni as precio_venta',
                'ps.ps_stock',
                'm.medida_codigo_unidad as medida'
            )
            ->orderBy('p.pro_nombre')
            ->limit(30)
            ->get()
            ->toArray();
    }

    // ── Vista nueva ───────────────────────────────────────────

    public function nuevaProforma(): void
    {
        $this->resetFormulario();
        $this->vista = 'nueva';
        $this->dispatch('vistaNueva');
    }

    public function volverHistorial(): void
    {
        $this->resetFormulario();
        $this->vista = 'historial';
    }

    // ── Productos del formulario ──────────────────────────────

    public function agregarProducto(int $idPro, string $nombre, float $precioVenta, string $codigo = ''): void
    {
        foreach ($this->items as $item) {
            if ($item['id_pro'] == $idPro) {
                $this->buscarProducto     = '';
                $this->resultadosBusqueda = [];
                $this->dispatch('enfocarBuscador');
                return;
            }
        }

        $this->items[] = [
            'id_pro'   => $idPro,
            'codigo'   => $codigo,
            'nombre'   => $nombre,
            'precio'   => $precioVenta,
            'cantidad' => 1,
        ];

        $this->buscarProducto     = '';
        $this->resultadosBusqueda = [];
        $this->dispatch('enfocarBuscador');
    }

    public function quitarItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    // ── Guardar proforma ──────────────────────────────────────

    public function guardar(): void
    {
        if (!auth()->user()->can('gestion_proformas.crear')) {
            session()->flash('error', 'No tienes permiso para registrar proformas.');
            return;
        }

        $this->validate([
            'numDocumento'     => 'required|string|max:20',
            'razonSocial'      => 'required|string|max:255',
            'formaPago'        => 'required|in:1,2',
            'lugarEntrega'     => 'required|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.cantidad' => 'required|numeric|min:1',
            'items.*.precio'   => 'required|numeric|min:0',
        ], [
            'numDocumento.required' => 'El número de documento es obligatorio.',
            'razonSocial.required'  => 'La razón social es obligatoria.',
            'lugarEntrega.required' => 'El lugar de entrega es obligatorio.',
            'items.required'        => 'Debe agregar al menos un producto.',
            'items.min'             => 'Debe agregar al menos un producto.',
        ]);

        $idSucursal = $this->resolverSucursal() ?: null;

        DB::beginTransaction();
        try {
            $cliente = DB::table('clientes')
                ->where('cliente_numero', $this->numDocumento)
                ->where('cliente_estado', 1)
                ->first();

            $microtime = microtime(true);
            if (!$cliente) {
                DB::table('clientes')->insert([
                    'id_tipo_documento'   => $this->idTipoDocumento,
                    'cliente_razonsocial' => $this->razonSocial,
                    'cliente_nombre'      => $this->razonSocial,
                    'cliente_numero'      => $this->numDocumento,
                    'cliente_direccion'   => $this->direccion,
                    'cliente_telefono'    => $this->telefono,
                    'cliente_fecha'       => now(),
                    'cliente_estado'      => 1,
                    'cliente_codigo'      => $microtime,
                ]);
                $cliente = DB::table('clientes')->where('cliente_codigo', $microtime)->first();
            } else {
                DB::table('clientes')
                    ->where('id_clientes', $cliente->id_clientes)
                    ->update([
                        'id_tipo_documento'   => $this->idTipoDocumento,
                        'cliente_razonsocial' => $this->razonSocial,
                        'cliente_nombre'      => $this->razonSocial,
                        'cliente_direccion'   => $this->direccion,
                        'cliente_telefono'    => $this->telefono,
                    ]);
            }

            $correlativo = 1;
            $ultimo = DB::table('proformas')->orderBy('id_profo', 'desc')->first();
            if ($ultimo) {
                $correlativo = $ultimo->profo_correlativo + 1;
            }

            $idProfo = DB::table('proformas')->insertGetId([
                'id_clientes'         => $cliente->id_clientes,
                'id_users'            => auth()->user()->id_users,
                'id_sucursal'         => $idSucursal,
                'profo_forma_pago'    => $this->formaPago,
                'profo_lugar_entrega' => $this->lugarEntrega,
                'profo_observacion'   => $this->observaciones,
                'profo_serie'         => 'PRO',
                'profo_correlativo'   => $correlativo,
                'profo_fecha_emision' => now()->toDateString(),
                'profo_estado'        => 1,
                'profo_acti_estado'   => 0,
                'profo_microtime'     => microtime(true),
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
            $this->vista = 'historial';
            session()->flash('success', 'Proforma registrada correctamente (pendiente de aprobación).');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la proforma.');
        }
    }

    // ── Aprobar proforma ──────────────────────────────────────

    public function confirmarAprobar(int $id): void
    {
        $this->idAprobar = $id;
        $this->dispatch('abrirModalAprobar');
    }

    public function aprobar(): void
    {
        if (!auth()->user()->can('gestion_proformas.aprobar')) {
            $this->dispatch('cerrarModalAprobar');
            session()->flash('error', 'No tienes permiso para aprobar proformas.');
            return;
        }

        try {
            DB::table('proformas')
                ->where('id_profo', $this->idAprobar)
                ->update(['profo_acti_estado' => 1, 'updated_at' => now()]);

            $this->idAprobar = null;
            $this->dispatch('cerrarModalAprobar');
            session()->flash('success', 'Proforma aprobada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al aprobar la proforma.');
        }
    }

    // ── Anular proforma ───────────────────────────────────────

    public function confirmarAnular(int $id): void
    {
        $this->idAnular = $id;
        $this->dispatch('abrirModalAnular');
    }

    public function anular(): void
    {
        if (!auth()->user()->can('gestion_proformas.cambiar_estado')) {
            $this->dispatch('cerrarModalAnular');
            session()->flash('error', 'No tienes permiso para anular proformas.');
            return;
        }

        try {
            DB::table('proformas')
                ->where('id_profo', $this->idAnular)
                ->where('profo_acti_estado', 0)
                ->update(['profo_estado' => 0, 'updated_at' => now()]);

            $this->idAnular = null;
            $this->dispatch('cerrarModalAnular');
            session()->flash('success', 'Proforma anulada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al anular la proforma.');
        }
    }

    private function resetFormulario(): void
    {
        $this->idTipoDocumento     = 2;
        $this->numDocumento        = '';
        $this->razonSocial         = '';
        $this->telefono            = '';
        $this->direccion           = '';
        $this->formaPago           = 1;
        $this->lugarEntrega        = 'Previa Coordinación';
        $this->observaciones       = 'Los precios están sujetos a variaciones';
        $this->buscarProducto      = '';
        $this->resultadosBusqueda  = [];
        $this->items               = [];
        $this->mensajeConsulta     = '';
        $this->tipoMensajeConsulta = '';
        $this->resetErrorBag();
    }

    // ── Render ────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $query = DB::table('proformas as p')
            ->join('clientes as c', 'c.id_clientes', '=', 'p.id_clientes')
            ->join('users as u', 'u.id_users', '=', 'p.id_users')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'p.id_sucursal')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
            ->where('p.profo_estado', 1)
            ->whereDate('p.profo_fecha_emision', '>=', $this->filtroDesde)
            ->whereDate('p.profo_fecha_emision', '<=', $this->filtroHasta)
            ->select(
                'p.*',
                'c.cliente_nombre', 'c.cliente_numero',
                'u.nombre_users',
                't.tienda_nombre',
                DB::raw("COALESCE(e.empresa_nombrecomercial, e.empresa_razon_social) as empresa_nombre"),
                DB::raw('(SELECT SUM(pd.profo_deta_cantidad * pd.profo_deta_precio)
                          FROM proformas_detalles pd
                          WHERE pd.id_profo = p.id_profo) as total')
            );

        if ($this->sucursalSeleccionada > 0) {
            $query->where('p.id_sucursal', $this->sucursalSeleccionada);
        } elseif ($this->empresaSeleccionada > 0) {
            $query->whereIn('p.id_sucursal', function ($sub) {
                $sub->select('id_tienda')->from('tiendas')
                    ->where('id_empresa', $this->empresaSeleccionada)
                    ->where('tienda_estado', 1);
            });
        }

        $proformas = $query->orderBy('p.id_profo', 'desc')->paginate($this->porPagina);

        $empresas = DB::table('empresa')
            ->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_nombrecomercial')
            ->get(['id_empresa', 'empresa_nombrecomercial', 'empresa_razon_social']);

        $tiposDocumento = DB::table('tipo_documento')
            ->where('tipo_documento_estado', 1)
            ->get();

        $sucursalNombre = '';
        if ($this->sucursalSeleccionada) {
            $suc = collect($this->sucursalesDisponibles instanceof \Illuminate\Support\Collection
                ? $this->sucursalesDisponibles->toArray()
                : $this->sucursalesDisponibles
            )->firstWhere('id_tienda', $this->sucursalSeleccionada);
            $sucursalNombre = $suc ? (is_array($suc) ? $suc['tienda_nombre'] : $suc->tienda_nombre) : '';
        }

        $empresaNombre = '';
        if ($this->empresaSeleccionada) {
            $emp = $empresas->firstWhere('id_empresa', $this->empresaSeleccionada);
            if ($emp) {
                $empresaNombre = $emp->empresa_nombrecomercial ?? $emp->empresa_razon_social ?? '';
            }
        }

        return view('livewire.gestion-ventas.proformas', [
            'proformas'      => $proformas,
            'empresas'       => $empresas,
            'tiposDocumento' => $tiposDocumento,
            'esAdmin'        => $this->esAdmin(),
            'esSuperAdmin'   => $this->esSuperAdmin(),
            'esVendedor'     => $this->esVendedor(),
            'esPrivilegiado' => $this->esPrivilegiado(),
            'sucursalNombre' => $sucursalNombre,
            'empresaNombre'  => $empresaNombre,
        ]);
    }
}
