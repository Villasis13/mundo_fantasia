<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use App\Models\PDFBufeo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Compras extends Component
{
    use WithPagination, WithFileUploads;

    // ── Vista actual ──────────────────────────────────────────
    public string $vista = 'historial';

    // ── Cabecera de la orden ──────────────────────────────────
    public int    $empresaIdCompra       = 0;
    public int    $idProveedor           = 0;
    public string $proveedorRuc          = '';
    public string $proveedorRazonSocial  = '';
    public string $condicionPago         = 'contado';
    public int    $idTipoPago            = 0;
    public string $tipoDoc               = 'FACTURA';
    public string $docSerie              = '';
    public string $docCorrelativo        = '';
    public string $numeroDoc             = '';
    public string $estadoOrden           = 'recibido';
    public string $proximoNumero         = '';
    public string $fechaEmision          = '';
    public string $fechaAlmacenamiento   = '';
    public string $fechaVencimiento      = '';
    public string $moneda                = 'PEN';
    public string $guiaRemitente         = '';
    public string $guiaTransportista     = '';
    public array  $transportistas        = [['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => '']];
    public string $observacion           = '';

    // ── Modal transportista ───────────────────────────────────
    public int    $slotActivo          = 0;
    public string $buscarTransportista = '';
    public string $tabTransportista    = 'seleccionar';
    public string $ntRuc               = '';
    public string $ntNombre            = '';
    public string $ntChofer            = '';
    public string $ntVehiculo          = '';
    public string $ntPlaca             = '';
    public string $ntDireccion         = '';
    public string $ntTelefono          = '';
    public string $ntRucMensaje        = '';
    public string $ntRucMensajeTipo    = '';
    public        $docAdjunto            = null;
    public string $flete                = '0';
    public string $gastosOp             = '0';
    public string $descuentoImporte         = '0';
    public bool   $revertirDesagregarIgv   = false;
    public string $igvPorcentaje        = '0';
    public string $percepcionPorcentaje = '0';

    // ── Búsqueda y items ─────────────────────────────────────
    public string $buscarProducto     = '';
    public array  $resultadosBusqueda = [];
    public array  $items              = [];

    // ── Selector de presentaciones (compra) ───────────────────
    public array $presentacionesPendientes = [];
    public array $productoPendienteData    = [];

    // ── SUNAT lookup ──────────────────────────────────────────
    public bool   $sunatBuscando      = false;
    public string $sunatMensaje       = '';
    public string $sunatTipo          = ''; // success | warning | error
    public bool   $modalSunat         = false;
    public array  $sunatFacturas      = [];

    // ── Nuevo proveedor (modal rápido) ───────────────────────────
    public string $npNombre          = '';
    public int    $npTipoDoc         = 4; // RUC por defecto
    public string $npNumDoc          = '';
    public string $npDireccion       = '';
    public string $npTelefono        = '';
    public string $npCorreo          = '';
    public string $npDocMensaje      = '';
    public string $npDocMensajeTipo  = ''; // success | error

    // ── Anulación / transición de estado ─────────────────────────
    public ?int   $idAnular           = null;
    public ?int   $idEnviar           = null;
    public ?int   $idRecibir          = null;
    public string $motivoAnulacion    = '';
    public array  $detallesRecibir    = [];
    public array  $cantidadesRecibidas = [];

    // ── NC / DB ───────────────────────────────────────────────
    public ?int   $ncDbIdOrden     = null;
    public string $ncDbTipo        = 'NC';
    public string $ncDbNumeroDoc   = '';
    public string $ncDbMotivo      = '';
    public bool   $ncDbAfectaStock = false;
    public int    $ncDbIdAlmacen   = 0;
    public array  $ncDbItems       = [];
    public float  $ncDbTotal       = 0;

    // ── Filtros historial ─────────────────────────────────────
    public int    $filtroEmpresa    = 0;
    public int    $filtroProveedor  = 0;
    public string $filtroEstado      = '';
    public string $filtroCondicion  = '';
    public string $filtroDesde      = '';
    public string $filtroHasta      = '';
    public string $filtroDiferencia = '';
    public int    $porPagina        = 10;

    private ?Logs $logs         = null;
    private int   $cachedRoleId = 0;

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
        abort_if(!auth()->user()->can('historial_compras.listar'), 403);

        $this->filtroDesde  = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta  = now()->format('Y-m-d');

        $this->limpiarFormulario();
        $this->vista = 'nueva';
    }

    private function esAdmin(): bool          { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool     { return $this->cachedRoleId === 1; }
    private function esAdministrador(): bool  { return $this->cachedRoleId === 3; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Lifecycle hooks ───────────────────────────────────────
    public function updatedEmpresaIdCompra(): void
    {
        $this->idProveedor        = 0;
        $this->proveedorRuc       = '';
        $this->items              = [];
        $this->resultadosBusqueda = [];
        $this->buscarProducto     = '';
    }

    public function updatedIdProveedor(): void
    {
        if ($this->idProveedor > 0) {
            $prov = DB::table('proveedores')->where('id_proveedores', $this->idProveedor)->first();
            $this->proveedorRuc         = (string) ($prov?->proveedores_numero_documento ?? '');
            $this->proveedorRazonSocial = (string) ($prov?->proveedores_nombre ?? '');
        } else {
            $this->proveedorRuc         = '';
            $this->proveedorRazonSocial = '';
        }
    }

    public function agregarTransportista(): void
    {
        $this->transportistas[] = ['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => ''];
    }

    public function quitarTransportista(int $idx): void
    {
        array_splice($this->transportistas, $idx, 1);
    }

    public function abrirModalTransportista(int $index): void
    {
        $this->slotActivo          = $index;
        $this->buscarTransportista = '';
        $this->tabTransportista    = 'seleccionar';
        $this->resetNuevoTransportista();
        $this->dispatch('abrirModalTransportista');
    }

    public function abrirTabNuevo(): void
    {
        $this->tabTransportista = 'nuevo';
        $this->resetNuevoTransportista();
    }

    public function abrirTabSeleccionar(): void
    {
        $this->tabTransportista    = 'seleccionar';
        $this->buscarTransportista = '';
    }

    public function seleccionarTransportista(int $id): void
    {
        foreach ($this->transportistas as $i => $slot) {
            if ($i !== $this->slotActivo && ($slot['id'] ?? 0) === $id) return;
        }

        $t = DB::table('transportistas')->where('id_transportista', $id)->first();
        if (!$t) return;

        $slots = $this->transportistas;
        $slots[$this->slotActivo]['id']     = $id;
        $slots[$this->slotActivo]['nombre'] = $t->transportista_nombre;
        $slots[$this->slotActivo]['ruc']    = $t->transportista_ruc ?? '';
        $this->transportistas = $slots;

        $this->dispatch('cerrarModalTransportista');
    }

    public function ntBuscarRuc(): void
    {
        $ruc = trim($this->ntRuc);

        if (strlen($ruc) !== 11 || !ctype_digit($ruc)) {
            $this->ntRucMensaje     = 'El RUC debe tener exactamente 11 dígitos.';
            $this->ntRucMensajeTipo = 'error';
            return;
        }

        $this->ntRucMensaje = $this->ntRucMensajeTipo = '';

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post('https://api.migo.pe/api/v1/ruc', [
                    'token' => config('services.tokens.api_migo'),
                    'ruc'   => $ruc,
                ]);
            $data = $response->json();

            if ($data['success'] ?? false) {
                $this->ntNombre         = strtoupper($data['nombre_o_razon_social'] ?? '');
                $this->ntDireccion      = mb_convert_case(strtolower($data['direccion_simple'] ?? ''), MB_CASE_TITLE, 'UTF-8');
                $this->ntRucMensaje     = 'Datos encontrados.';
                $this->ntRucMensajeTipo = 'success';
            } else {
                $this->ntRucMensaje     = $data['message'] ?? 'RUC no encontrado.';
                $this->ntRucMensajeTipo = 'error';
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->ntRucMensaje     = 'Error al consultar el servicio externo.';
            $this->ntRucMensajeTipo = 'error';
        }
    }

    public function guardarNuevoTransportista(): void
    {
        $this->validate([
            'ntNombre' => 'required|string|max:255',
        ], [
            'ntNombre.required' => 'El nombre / razón social es obligatorio.',
        ]);

        $id = DB::table('transportistas')->insertGetId([
            'transportista_nombre'    => strtoupper(trim($this->ntNombre)),
            'transportista_ruc'       => trim($this->ntRuc) ?: null,
            'transportista_chofer'    => trim($this->ntChofer) ?: null,
            'transportista_vehiculo'  => trim($this->ntVehiculo) ?: null,
            'transportista_placa'     => trim($this->ntPlaca) ?: null,
            'transportista_direccion' => trim($this->ntDireccion) ?: null,
            'transportista_telefono'  => trim($this->ntTelefono) ?: null,
            'transportista_estado'    => 1,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        $this->seleccionarTransportista($id);
    }

    private function resetNuevoTransportista(): void
    {
        $this->ntRuc = $this->ntNombre = $this->ntChofer   = '';
        $this->ntVehiculo = $this->ntPlaca = $this->ntDireccion = $this->ntTelefono = '';
        $this->ntRucMensaje = $this->ntRucMensajeTipo = '';
    }

    public function cargarSugerencias(): void
    {
        $this->resultadosBusqueda = $this->buscarProductosQuery();
    }

    public function cerrarSugerencias(): void
    {
        $this->resultadosBusqueda = [];
    }

    public function updatedBuscarProducto(): void
    {
        $this->resultadosBusqueda = $this->buscarProductosQuery();
    }

    private function buscarProductosQuery(): array
    {
        $yaAgregados = collect($this->items)->pluck('id_pro')->all();

        $idTienda = (int) DB::table('user_tienda')
            ->where('id_users', auth()->user()->id_users)
            ->value('id_tienda');
        if (!$idTienda) {
            $idTienda = (int) DB::table('tiendas')->where('tienda_estado', 1)->orderBy('id_tienda')->value('id_tienda');
        }

        $q = DB::table('productos as p')
            ->leftJoin('producto_sucursal as ps', function ($j) use ($idTienda) {
                $j->on('ps.id_pro', '=', 'p.id_pro')
                  ->where('ps.id_tienda', $idTienda)
                  ->where('ps.ps_estado', 1);
            })
            ->where('p.pro_estado', 1)
            ->whereNotIn('p.id_pro', $yaAgregados);

        if ($this->buscarProducto !== '') {
            $q->where(function ($inner) {
                $inner->where('p.pro_nombre', 'like', "%{$this->buscarProducto}%")
                      ->orWhere('p.pro_codigo', 'like', "%{$this->buscarProducto}%");
            });
        }

        return $q->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'p.id_medida',
                          'p.pro_costo_base', DB::raw('COALESCE(ps.ps_stock, 0) as ps_stock'))
                 ->orderBy('p.pro_nombre')->limit(8)->get()->toArray();
    }

    public function updatedItems($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) < 2) return;
        $idx    = (int) $parts[0];
        if (!isset($this->items[$idx])) return;
        $cant   = (float) ($this->items[$idx]['cantidad']      ?? 0);
        $precio = (float) ($this->items[$idx]['precio_compra'] ?? 0);
        $this->items[$idx]['total'] = round($cant * $precio, 2);
    }

    public function updatedFiltroEmpresa(): void
    {
        $this->filtroProveedor = 0;
        $this->resetPage();
    }

    public function updatedFiltroProveedor(): void  { $this->resetPage(); }
    public function updatedFiltroEstado(): void     { $this->resetPage(); }
    public function updatedFiltroCondicion(): void  { $this->resetPage(); }
    public function updatingPorPagina(): void      { $this->resetPage(); }

    // ── Agregar / quitar items ────────────────────────────────
    public function agregarProducto(int $idPro): void
    {
        $prod = collect($this->resultadosBusqueda)
            ->first(fn($p) => $p->id_pro === $idPro);

        if (!$prod) return;

        $this->buscarProducto     = '';
        $this->resultadosBusqueda = [];

        $presentaciones = DB::table('producto_presentaciones')
            ->where('id_pro', $idPro)
            ->where('pres_estado', 1)
            ->orderBy('pres_factor')
            ->get();

        $pendiente = [
            'id_pro'    => $prod->id_pro,
            'nombre'    => $prod->pro_nombre,
            'codigo'    => $prod->pro_codigo,
            'id_medida' => $prod->id_medida,
            'precio'    => (float) ($prod->pro_costo_base ?? 0),
        ];

        if ($presentaciones->count() === 0) {
            $this->insertarItemCompra($pendiente, '', '');
            return;
        }

        if ($presentaciones->count() === 1) {
            $p = $presentaciones->first();
            $this->insertarItemCompra(
                $pendiente,
                (string) $p->pres_nombre,
                (string) ((float) $p->pres_factor > 0 ? (float) $p->pres_factor : '')
            );
            return;
        }

        // 2+ presentaciones: mostrar modal
        $this->productoPendienteData    = $pendiente;
        $this->presentacionesPendientes = $presentaciones->map(fn($p) => [
            'id_pres'          => (int)   $p->id_pres,
            'pres_nombre'      => (string) $p->pres_nombre,
            'pres_abreviatura' => (string) ($p->pres_abreviatura ?? ''),
            'pres_factor'      => (float)  $p->pres_factor,
        ])->toArray();

        $this->dispatch('abrirModalPresentacionCompra');
    }

    public function seleccionarPresentacionCompra(int $idPres): void
    {
        $pres = collect($this->presentacionesPendientes)->firstWhere('id_pres', $idPres);
        if (!$pres || empty($this->productoPendienteData)) return;

        $this->insertarItemCompra(
            $this->productoPendienteData,
            (string) $pres['pres_nombre'],
            (string) ($pres['pres_factor'] > 0 ? $pres['pres_factor'] : '')
        );

        $this->presentacionesPendientes = [];
        $this->productoPendienteData    = [];
        $this->dispatch('cerrarModalPresentacionCompra');
    }

    private function insertarItemCompra(array $prod, string $presentacion, string $cantXUnidad): void
    {
        $this->items[] = [
            'id_pro'            => $prod['id_pro'],
            'nombre'            => $prod['nombre'],
            'codigo'            => $prod['codigo'],
            'id_medida'         => $prod['id_medida'],
            'cantidad'          => 1,
            'precio_compra'     => $prod['precio'],
            'total'             => $prod['precio'],
            'presentacion'      => $presentacion,
            'cantidad_x_unidad' => $cantXUnidad,
        ];
    }

    public function quitarItem(int $idx): void
    {
        array_splice($this->items, $idx, 1);
    }

    // ── Cambiar vista ─────────────────────────────────────────
    public function nuevaOrden(): void
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

    // ── Validación ────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'empresaIdCompra'        => 'required|integer|min:1',
            'idProveedor'            => 'required|integer|min:1',
            'items'                  => 'required|array|min:1',
            'items.*.cantidad'       => 'required|numeric|min:0.01',
            'items.*.precio_compra'  => 'required|numeric|min:0',
        ];

        if ($this->condicionPago === 'credito') {
            $rules['fechaVencimiento'] = 'required|date';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'empresaIdCompra.min'            => 'Debes seleccionar una empresa.',
            'idSucursal.min'                 => 'Debes seleccionar una sede.',
            'idProveedor.min'                => 'Debes seleccionar un proveedor.',
            'items.required'                 => 'Agrega al menos un producto.',
            'items.min'                      => 'Agrega al menos un producto.',
            'items.*.cantidad.min'           => 'La cantidad debe ser mayor a 0.',
            'items.*.precio_compra.required' => 'El precio de compra es obligatorio.',
            'fechaVencimiento.required'      => 'La fecha de vencimiento es obligatoria para compras a crédito.',
            'fechaVencimiento.date'          => 'La fecha de vencimiento no es válida.',
        ];
    }

    // ── Guardar orden ─────────────────────────────────────────
    public function guardarOrden(): void
    {
        $this->validate();

        if (!auth()->user()->can('registro_compras.crear')) {
            session()->flash('error', 'No tienes permiso para registrar compras.');
            return;
        }

        $flete           = max(0, (float) $this->flete);
        $gastos          = max(0, (float) $this->gastosOp);
        $igvPct          = max(0, (float) $this->igvPorcentaje);
        $rawSubtotal     = round(collect($this->items)->sum('total'), 2);
        $subtotal        = ($this->revertirDesagregarIgv && $igvPct > 0)
            ? floor($rawSubtotal / (1 + $igvPct / 100) * 100) / 100
            : $rawSubtotal;
        $descuentoMonto  = round(max(0, (float) $this->descuentoImporte), 2);
        $subtotalNeto    = round($subtotal - $descuentoMonto, 2);
        $igvMonto        = round($subtotalNeto * $igvPct / 100, 2);
        $percepcionPct   = max(0, (float) $this->percepcionPorcentaje);
        $percepcionMonto = round(($subtotalNeto + $igvMonto) * $percepcionPct / 100, 2);
        $total           = round($subtotalNeto + $igvMonto + $percepcionMonto + $flete + $gastos, 2);

        $transportistasGuardar = array_values(
            array_filter($this->transportistas, fn($t) => !empty(trim($t['nombre'] ?? '')))
        );

        $siguiente = DB::table('orden_compra')->count() + 1;
        $numero    = 'OC-' . date('Y') . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

        $proveedor = DB::table('proveedores')->where('id_proveedores', $this->idProveedor)->first();

        DB::beginTransaction();
        try {
            $docPath = null;
            if ($this->docAdjunto) {
                $ext      = $this->docAdjunto->getClientOriginalExtension();
                $nombre   = 'OC-' . time() . '.' . $ext;
                $directorio = public_path('comprobantes_compras');
                if (!is_dir($directorio)) mkdir($directorio, 0755, true);
                $this->docAdjunto->move($directorio, $nombre);
                $docPath = 'comprobantes_compras/' . $nombre;
            }

            $idOrden = DB::table('orden_compra')->insertGetId([
                'id_solicitante'                 => auth()->user()->id_users,
                'id_proveedores'                 => $this->idProveedor,
                'id_empresa'                     => $this->empresaIdCompra ?: null,
                'id_sucursal'                    => null,
                'condicion_pago'                 => $this->condicionPago,
                'id_tipo_pago'                   => ($this->condicionPago === 'contado' && $this->idTipoPago) ? $this->idTipoPago : null,
                'orden_compra_titulo'            => 'Orden de Compra',
                'orden_compra_numero'            => $numero,
                'orden_compra_codigo'            => $numero,
                'orden_compra_estado'            => $this->estadoOrden,
                'orden_compra_activo'            => 1,
                'orden_compra_fecha'             => now(),
                'orden_compra_tipo_doc'          => $this->tipoDoc ?: null,
                'orden_compra_numero_doc'        => ($this->docSerie && $this->docCorrelativo)
                    ? trim($this->docSerie, '-') . '-' . trim($this->docCorrelativo, '-')
                    : ($this->numeroDoc ?: null),
                'orden_compra_fecha_emision_doc'    => $this->fechaEmision ?: null,
                'orden_compra_fecha_vencimiento'    => $this->fechaVencimiento ?: null,
                'orden_compra_guia_remitente'             => $this->guiaRemitente ?: null,
                'orden_compra_guia_transportista'         => $this->guiaTransportista ?: null,
                'orden_compra_doc_adjuntado'              => $docPath,
                'orden_compra_observacion'                => $this->observacion ?: null,
                'orden_compra_total'                      => $total,
                'orden_compra_flete'                      => $flete,
                'orden_compra_gastos_operativos'          => $gastos,
                'orden_compra_descuento_porcentaje'       => 0,
                'orden_compra_descuento_monto'            => $descuentoMonto,
                'orden_compra_igv_porcentaje'             => $igvPct,
                'orden_compra_igv_monto'                  => $igvMonto,
                'orden_compra_percepcion_porcentaje'      => $percepcionPct,
                'orden_compra_percepcion_monto'           => $percepcionMonto,
                'fecha_almacenamiento'                    => $this->fechaAlmacenamiento ?: null,
                'moneda'                                  => $this->moneda ?: 'PEN',
                'orden_compra_nom_prove'                  => $proveedor?->proveedores_nombre,
                'orden_compra_num_document'               => $proveedor?->proveedores_numero_documento,
                'created_at'                     => now(),
                'updated_at'                     => now(),
            ]);

            // Transportistas
            foreach ($transportistasGuardar as $orden => $t) {
                DB::table('orden_compra_transportistas')->insert([
                    'id_orden_compra'  => $idOrden,
                    'oc_trans_nombre'  => trim($t['nombre'] ?? '') ?: null,
                    'oc_trans_ruc'     => trim($t['ruc']    ?? '') ?: null,
                    'oc_trans_fact'    => trim($t['fact']   ?? '') ?: null,
                    'oc_trans_fecha'   => trim($t['fecha']  ?? '') ?: null,
                    'oc_trans_orden'   => $orden,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Distribuir el flete total proporcionalmente entre ítems
            $totalItems    = collect($this->items)->sum('total');
            $fleteRestante = $flete;
            $lastIdx       = count($this->items) - 1;

            foreach ($this->items as $idx => $item) {
                if ($idx === $lastIdx) {
                    $fleteItem = round($fleteRestante, 2);
                } elseif ($totalItems > 0) {
                    $fleteItem     = round(($item['total'] / $totalItems) * $flete, 2);
                    $fleteRestante = round($fleteRestante - $fleteItem, 2);
                } else {
                    $fleteItem = 0;
                }

                DB::table('orden_compra_detalle')->insert([
                    'id_orden_compra'               => $idOrden,
                    'id_pro'                        => $item['id_pro'],
                    'detalle_orden_nombre_producto' => $item['nombre'],
                    'presentacion'                  => $item['presentacion'] !== '' ? $item['presentacion'] : null,
                    'detalle_compra_cantidad'       => (float) $item['cantidad'],
                    'cantidad_x_unidad'             => $item['cantidad_x_unidad'] !== '' ? (float) $item['cantidad_x_unidad'] : null,
                    'detalle_compra_precio_compra'  => (float) $item['precio_compra'],
                    'detalle_compra_total_pedido'   => (float) $item['total'],
                    'detalle_compra_estado'         => 1,
                    'flete'                         => $fleteItem,
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ]);
            }

            // Si es a crédito, crear la cuenta por pagar automáticamente
            if ($this->condicionPago === 'credito') {
                $idTienda = DB::table('user_tienda')
                    ->where('id_users', auth()->user()->id_users)
                    ->value('id_tienda');

                DB::table('cuentas_pagar')->insert([
                    'id_orden_compra'     => $idOrden,
                    'id_proveedores'      => $this->idProveedor,
                    'id_empresa'          => $this->empresaIdCompra ?: null,
                    'id_sucursal'         => $idTienda ?: null,
                    'id_users_registro'   => auth()->user()->id_users,
                    'cp_numero_doc'       => $this->numeroDoc ?: null,
                    'cp_tipo_doc'         => $this->tipoDoc ?: null,
                    'cp_fecha_emision'    => $this->fechaEmision ?: now()->toDateString(),
                    'cp_fecha_vencimiento'=> $this->fechaVencimiento ?: null,
                    'cp_monto_total'      => $total,
                    'cp_monto_pagado'     => 0,
                    'cp_saldo'            => $total,
                    'cp_estado'           => 1,
                    'cp_observacion'      => $this->observacion ?: null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->vista = 'nueva';
            session()->flash('success', "Compra {$numero} registrada. Recepcione cuando llegue la mercadería.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la orden.');
        }
    }

    // ── Enviar orden (pendiente → en_transito) ───────────────
    public function confirmarEnviar(int $id): void
    {
        $this->idEnviar = $id;
        $this->dispatch('abrirModalEnviar');
    }

    public function enviarOrden(): void
    {
        if (!$this->idEnviar) return;

        if (!auth()->user()->can('historial_compras.cambiar_estado')) {
            $this->dispatch('cerrarModalEnviar');
            session()->flash('error', 'No tienes permiso para actualizar órdenes.');
            return;
        }

        $orden = DB::table('orden_compra')->where('id_orden_compra', $this->idEnviar)->first();
        if (!$orden || $orden->orden_compra_estado !== 'pendiente') {
            session()->flash('error', 'Solo se puede enviar una orden en estado Pendiente.');
            $this->idEnviar = null;
            $this->dispatch('cerrarModalEnviar');
            return;
        }

        DB::table('orden_compra')
            ->where('id_orden_compra', $this->idEnviar)
            ->update(['orden_compra_estado' => 'en_transito', 'updated_at' => now()]);

        $this->idEnviar = null;
        $this->dispatch('cerrarModalEnviar');
        session()->flash('success', 'Orden marcada como en tránsito.');
    }

    // ── Recepcionar compra (en_transito → recibido + stock Almacén Principal) ──
    public function confirmarRecibir(int $id): void
    {
        $this->idRecibir           = $id;
        $this->cantidadesRecibidas = [];
        $this->resetErrorBag();

        $this->detallesRecibir = DB::table('orden_compra_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_orden_compra', $id)
            ->where('d.detalle_compra_estado', 1)
            ->select('d.id_detalle_compra', 'd.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                     'd.detalle_compra_cantidad as cantidad_pedida',
                     'd.detalle_compra_precio_compra as precio_compra')
            ->get()
            ->map(function ($r) {
                $this->cantidadesRecibidas[$r->id_detalle_compra] = (float) $r->cantidad_pedida;
                return (array) $r;
            })->toArray();

        $this->dispatch('abrirModalRecibir');
    }

    public function recibirOrden(): void
    {
        if (!$this->idRecibir) return;

        if (!auth()->user()->can('historial_compras.cambiar_estado')) {
            $this->dispatch('cerrarModalRecibir');
            session()->flash('error', 'No tienes permiso para recepcionar compras.');
            return;
        }

        // Validar que al menos un ítem tenga cantidad > 0
        $hayAlguna = collect($this->cantidadesRecibidas)->filter(fn($v) => (float) $v > 0)->isNotEmpty();
        if (!$hayAlguna) {
            $this->addError('cantidades', 'Ingresa al menos una cantidad mayor a 0.');
            return;
        }


        $orden = DB::table('orden_compra')->where('id_orden_compra', $this->idRecibir)->first();
        if (!$orden || $orden->orden_compra_estado !== 'en_transito') {
            session()->flash('error', 'Solo se puede recepcionar una compra en tránsito.');
            $this->idRecibir = null;
            $this->dispatch('cerrarModalRecibir');
            return;
        }

        $idEmpresaOrden = $orden->id_empresa;
        if (!$idEmpresaOrden && $orden->id_sucursal) {
            $idEmpresaOrden = DB::table('tiendas')->where('id_tienda', $orden->id_sucursal)->value('id_empresa');
        }

        $idTienda = $idEmpresaOrden
            ? (int) DB::table('tiendas')->where('id_empresa', $idEmpresaOrden)->where('tienda_estado', 1)->orderBy('id_tienda')->value('id_tienda')
            : (int) DB::table('tiendas')->where('tienda_estado', 1)->orderBy('id_tienda')->value('id_tienda');

        if (!$idTienda) {
            session()->flash('error', 'No hay sede disponible para recepcionar la compra.');
            $this->idRecibir = null;
            $this->dispatch('cerrarModalRecibir');
            return;
        }

        DB::beginTransaction();
        try {
            $detalle = DB::table('orden_compra_detalle')
                ->where('id_orden_compra', $this->idRecibir)
                ->where('detalle_compra_estado', 1)
                ->get();

            $idMovimiento = DB::table('movimientos_productos')->insertGetId([
                'movimientos_productos_fecha'          => now()->toDateString(),
                'id_users'                             => auth()->user()->id_users,
                'id_sucursal'                          => $idTienda,
                'id_almacen'                           => null,
                'movimientos_productos_fecha_creacion' => now(),
                'movimientos_productos_tipo'           => 1,
                'movimientos_productos_estado'         => 1,
                'movimientos_productos_motivo'         => 'Recepción Compra ' . $orden->orden_compra_numero,
                'created_at'                           => now(),
                'updated_at'                           => now(),
            ]);

            $mermas = [];

            foreach ($detalle as $item) {
                $cantidadPedida   = (float) $item->detalle_compra_cantidad;
                $cantidadRecibida = max(0, (float) ($this->cantidadesRecibidas[$item->id_detalle_compra] ?? $cantidadPedida));
                $mermaQty         = $cantidadPedida - $cantidadRecibida;
                $costoUni         = (float) $item->detalle_compra_precio_compra;

                // Guardar cantidad recibida en el detalle
                DB::table('orden_compra_detalle')
                    ->where('id_detalle_compra', $item->id_detalle_compra)
                    ->update(['detalle_compra_cantidad_recibida' => $cantidadRecibida, 'updated_at' => now()]);

                if ($cantidadRecibida > 0) {
                    $ps = DB::table('producto_sucursal')
                        ->where('id_tienda', $idTienda)->where('id_pro', $item->id_pro)->first();

                    if ($ps) {
                        DB::table('producto_sucursal')->where('id_ps', $ps->id_ps)
                            ->increment('ps_stock', $cantidadRecibida, ['updated_at' => now()]);
                    } else {
                        DB::table('producto_sucursal')->insert([
                            'id_pro'             => $item->id_pro,
                            'id_sucursal'        => null,
                            'id_tienda'          => $idTienda,
                            'id_tipo_afectacion' => 1,
                            'ps_precio_uni'      => $costoUni,
                            'ps_precio_uni_2'    => 0,
                            'ps_precio_uni_3'    => 0,
                            'ps_stock'           => $cantidadRecibida,
                            'ps_stock_minimo'    => 0,
                            'ps_porcen_igv'      => 18,
                            'ps_estado'          => 1,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }

                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'               => $idMovimiento,
                        'id_pro'                                 => $item->id_pro,
                        'movimientos_productos_detalle_cantidad' => (string) $cantidadRecibida,
                        'costo_unitario'                         => $costoUni,
                        'id_referencia'                          => $this->idRecibir,
                        'tipo_referencia'                        => 'compra',
                        'movimientos_productos_detalle_estado'   => '1',
                        'created_at'                             => now(),
                        'updated_at'                             => now(),
                    ]);
                }

                if ($mermaQty > 0) {
                    $mermas[] = ['id_pro' => $item->id_pro, 'cantidad' => $mermaQty, 'costo' => $costoUni];
                }
            }

            // Registrar mermas si las hay
            if (!empty($mermas)) {
                $idMovMerma = DB::table('movimientos_productos')->insertGetId([
                    'movimientos_productos_fecha'          => now()->toDateString(),
                    'id_users'                             => auth()->user()->id_users,
                    'id_sucursal'                          => $idTienda,
                    'id_almacen'                           => null,
                    'movimientos_productos_fecha_creacion' => now(),
                    'movimientos_productos_tipo'           => 2,
                    'movimientos_productos_estado'         => 1,
                    'movimientos_productos_motivo'         => 'Merma recepción compra ' . $orden->orden_compra_numero,
                    'created_at'                           => now(),
                    'updated_at'                           => now(),
                ]);
                foreach ($mermas as $m) {
                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'               => $idMovMerma,
                        'id_pro'                                 => $m['id_pro'],
                        'movimientos_productos_detalle_cantidad' => (string) $m['cantidad'],
                        'costo_unitario'                         => $m['costo'],
                        'id_referencia'                          => $this->idRecibir,
                        'tipo_referencia'                        => 'merma_compra',
                        'movimientos_productos_detalle_estado'   => '1',
                        'created_at'                             => now(),
                        'updated_at'                             => now(),
                    ]);
                }
            }

            DB::table('orden_compra')
                ->where('id_orden_compra', $this->idRecibir)
                ->update([
                    'orden_compra_estado'          => 'recibido',
                    'orden_compra_fecha_recibida'   => now(),
                    'orden_compra_usuario_recibido' => auth()->user()->nombre_users ?? (string) auth()->id(),
                    'id_sucursal'                   => $idTienda,
                    'id_almacen'                    => null,
                    'updated_at'                    => now(),
                ]);

            DB::commit();
            $mermaMsg = !empty($mermas) ? ' Se registraron ' . count($mermas) . ' ítem(s) con merma.' : '';
            $this->idRecibir           = null;
            $this->detallesRecibir     = [];
            $this->cantidadesRecibidas = [];
            $this->dispatch('cerrarModalRecibir');
            session()->flash('success', 'Compra recepcionada. Stock actualizado en sede.' . $mermaMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al recepcionar la compra.');
        }
    }

    // ── Anular orden ─────────────────────────────────────────
    public function confirmarAnular(int $id): void
    {
        $this->idAnular        = $id;
        $this->motivoAnulacion = '';
        $this->resetValidation('motivoAnulacion');
        $this->dispatch('abrirModalAnular');
    }

    public function anularOrden(): void
    {
        if (!$this->idAnular) return;

        $this->validate([
            'motivoAnulacion' => 'required|string|min:5|max:500',
        ], [
            'motivoAnulacion.required' => 'Debes ingresar el motivo de anulación.',
            'motivoAnulacion.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if (!auth()->user()->can('historial_compras.cambiar_estado')) {
            $this->dispatch('cerrarModalAnular');
            session()->flash('error', 'No tienes permiso para anular órdenes de compra.');
            return;
        }

        $orden = DB::table('orden_compra')->where('id_orden_compra', $this->idAnular)->first();
        if (!$orden || $orden->orden_compra_estado === 'anulado') {
            session()->flash('error', 'La orden ya está anulada o no existe.');
            $this->idAnular = null;
            $this->dispatch('cerrarModalAnular');
            return;
        }

        DB::beginTransaction();
        try {
            // Solo revertir stock y kardex si ya fue recepcionada
            if ($orden->orden_compra_estado === 'recibido') {
                $detalle = DB::table('orden_compra_detalle')
                    ->where('id_orden_compra', $this->idAnular)->get();

                // Si tiene almacen asignado, revertir desde almacen_producto; si no, desde producto_sucursal (legado)
                $idAlmacenRev = $orden->id_almacen ?: null;

                $idMovimiento = DB::table('movimientos_productos')->insertGetId([
                    'movimientos_productos_fecha'           => now()->toDateString(),
                    'id_users'                              => auth()->id(),
                    'id_sucursal'                           => $idAlmacenRev ? null : $orden->id_sucursal,
                    'id_almacen'                            => $idAlmacenRev ?: null,
                    'movimientos_productos_fecha_creacion'  => now(),
                    'movimientos_productos_tipo'            => 2,
                    'movimientos_productos_estado'          => 1,
                    'movimientos_productos_motivo'          => 'Anulación OC ' . $orden->orden_compra_numero,
                    'created_at'                            => now(),
                    'updated_at'                            => now(),
                ]);

                foreach ($detalle as $item) {
                    $cantidad = (float) ($item->detalle_compra_cantidad_recibida ?? $item->detalle_compra_cantidad);

                    if ($idAlmacenRev) {
                        DB::table('almacen_producto')
                            ->where('id_almacen', $idAlmacenRev)
                            ->where('id_pro', $item->id_pro)
                            ->decrement('ap_stock', $cantidad, ['updated_at' => now()]);
                    } else {
                        DB::table('producto_sucursal')
                            ->where('id_pro', $item->id_pro)
                            ->where('id_tienda', $orden->id_sucursal)
                            ->decrement('ps_stock', $cantidad, ['updated_at' => now()]);
                    }

                    DB::table('movimientos_productos_detalle')->insert([
                        'id_movimientos_productos'               => $idMovimiento,
                        'id_pro'                                 => $item->id_pro,
                        'movimientos_productos_detalle_cantidad' => (string) $cantidad,
                        'costo_unitario'                         => (float) $item->detalle_compra_precio_compra,
                        'id_referencia'                          => $this->idAnular,
                        'tipo_referencia'                        => 'anulacion_compra',
                        'movimientos_productos_detalle_estado'   => '1',
                        'created_at'                             => now(),
                        'updated_at'                             => now(),
                    ]);
                }
            }

            DB::table('orden_compra')
                ->where('id_orden_compra', $this->idAnular)
                ->update([
                    'orden_compra_estado'            => 'anulado',
                    'orden_compra_motivo_anulacion'  => trim($this->motivoAnulacion),
                    'updated_at'                     => now(),
                ]);

            DB::commit();
            $this->idAnular        = null;
            $this->motivoAnulacion = '';
            $this->dispatch('cerrarModalAnular');
            session()->flash('success', 'Orden anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al anular la orden.');
        }
    }

    // ── NC / DB ───────────────────────────────────────────────
    public function abrirModalNcDb(int $idOrden, string $tipo): void
    {
        $this->reset(['ncDbNumeroDoc', 'ncDbMotivo', 'ncDbAfectaStock', 'ncDbIdAlmacen', 'ncDbItems', 'ncDbTotal']);
        $this->ncDbIdOrden = $idOrden;
        $this->ncDbTipo    = $tipo;

        // Pre-seleccionar el almacén de la empresa de la compra
        $orden = DB::table('orden_compra')->where('id_orden_compra', $idOrden)->first();
        if ($orden?->id_empresa) {
            $idAlm = DB::table('almacen')
                ->where('id_empresa', $orden->id_empresa)
                ->where('almacen_estado', 1)
                ->value('id_almacen');
            if ($idAlm) $this->ncDbIdAlmacen = (int) $idAlm;
        }

        $this->ncDbItems = DB::table('orden_compra_detalle as d')
            ->join('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->where('d.id_orden_compra', $idOrden)
            ->where('d.detalle_compra_estado', 1)
            ->get(['d.id_pro', 'p.pro_nombre as nombre',
                   'd.detalle_compra_precio_compra as precio',
                   'd.detalle_compra_cantidad as cantidad'])
            ->map(fn($r) => [
                'id_pro'   => $r->id_pro,
                'nombre'   => $r->nombre,
                'precio'   => (float) $r->precio,
                'cantidad' => (float) $r->cantidad,
                'total'    => round((float) $r->precio * (float) $r->cantidad, 2),
            ])->toArray();

        $this->calcularTotalNcDb();
        $this->resetErrorBag();
        $this->dispatch('abrirModalNcDb');
    }

    public function calcularTotalNcDb(): void
    {
        foreach ($this->ncDbItems as $k => $it) {
            $this->ncDbItems[$k]['total'] = round((float) $it['precio'] * (float) $it['cantidad'], 2);
        }
        $this->ncDbTotal = round(collect($this->ncDbItems)->sum('total'), 2);
    }

    public function quitarItemNcDb(int $idx): void
    {
        array_splice($this->ncDbItems, $idx, 1);
        $this->calcularTotalNcDb();
    }

    public function guardarNcDb(): void
    {
        $this->validate([
            'ncDbMotivo' => 'required|string|min:5|max:500',
            'ncDbItems'  => 'required|array|min:1',
        ], [
            'ncDbMotivo.required' => 'El motivo es obligatorio.',
            'ncDbMotivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
            'ncDbItems.min'       => 'Debe haber al menos un ítem.',
        ]);

        $this->calcularTotalNcDb();
        if ($this->ncDbTotal <= 0) {
            $this->addError('ncDbTotal', 'El total debe ser mayor a cero.');
            return;
        }

        $orden = DB::table('orden_compra')->where('id_orden_compra', $this->ncDbIdOrden)->first();

        DB::beginTransaction();
        try {
            $prefijo = $this->ncDbTipo === 'NC' ? 'NC' : 'DB';
            $count   = DB::table('notas_compra')->where('tipo_nota', $this->ncDbTipo)->count() + 1;
            $numero  = $prefijo . '-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $idNota = DB::table('notas_compra')->insertGetId([
                'id_empresa'       => $orden->id_empresa ?? null,
                'id_proveedores'   => $orden->id_proveedores,
                'id_orden_compra'  => $this->ncDbIdOrden,
                'id_almacen'       => ($this->ncDbAfectaStock && $this->ncDbIdAlmacen) ? $this->ncDbIdAlmacen : null,
                'id_users'         => auth()->user()->id_users,
                'tipo_nota'        => $this->ncDbTipo,
                'nota_numero'      => $numero,
                'nota_numero_doc'  => $this->ncDbNumeroDoc ?: null,
                'nota_fecha'       => now()->toDateString(),
                'nota_motivo'      => $this->ncDbMotivo,
                'nota_total'       => $this->ncDbTotal,
                'nota_afecta_stock'=> $this->ncDbAfectaStock ? 1 : 0,
                'nota_estado'      => 'pendiente',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            foreach ($this->ncDbItems as $item) {
                DB::table('notas_compra_detalle')->insert([
                    'id_nota_compra'      => $idNota,
                    'id_pro'              => $item['id_pro'],
                    'detalle_descripcion' => $item['nombre'],
                    'detalle_cantidad'    => (float) $item['cantidad'],
                    'detalle_precio'      => (float) $item['precio'],
                    'detalle_total'       => (float) $item['total'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::commit();
            $this->reset(['ncDbIdOrden', 'ncDbNumeroDoc', 'ncDbMotivo', 'ncDbAfectaStock', 'ncDbIdAlmacen', 'ncDbItems', 'ncDbTotal']);
            $this->dispatch('cerrarModalNcDb');
            session()->flash('success', "{$prefijo} {$numero} creada en estado pendiente. Apruébala desde el módulo NC/DB Compras.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al guardar la nota.');
        }
    }

    // ── Nuevo proveedor ───────────────────────────────────────
    public function npBuscarDoc(): void
    {
        $numero = trim($this->npNumDoc);
        $longitud = strlen($numero);

        if (!in_array($longitud, [8, 11])) {
            $this->npDocMensaje     = 'Ingresa un RUC (11 dígitos) o DNI (8 dígitos).';
            $this->npDocMensajeTipo = 'error';
            return;
        }

        $this->npDocMensaje     = '';
        $this->npDocMensajeTipo = '';

        try {
            if ($longitud === 11) {
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->asForm()
                    ->post('https://api.migo.pe/api/v1/ruc', [
                        'token' => config('services.tokens.api_migo'),
                        'ruc'   => $numero,
                    ]);
                $data = $response->json();

                if ($data['success'] ?? false) {
                    $this->npNombre    = strtoupper($data['nombre_o_razon_social'] ?? '');
                    $this->npDireccion = mb_convert_case(strtolower($data['direccion_simple'] ?? ''), MB_CASE_TITLE, 'UTF-8');
                    $this->npTipoDoc   = 4;
                    $this->npDocMensaje     = 'Datos del RUC encontrados.';
                    $this->npDocMensajeTipo = 'success';
                } else {
                    $this->npDocMensaje     = $data['message'] ?? 'RUC no encontrado.';
                    $this->npDocMensajeTipo = 'error';
                }
            } else {
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->asForm()
                    ->post('https://api.migo.pe/api/v1/dni', [
                        'token' => config('services.tokens.api_migo'),
                        'dni'   => $numero,
                    ]);
                $data = $response->json();

                if ($data['success'] ?? false) {
                    $nombre = trim(($data['nombres'] ?? '') . ' ' . ($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''));
                    $this->npNombre         = strtoupper($nombre);
                    $this->npTipoDoc        = 2;
                    $this->npDocMensaje     = 'Datos del DNI encontrados.';
                    $this->npDocMensajeTipo = 'success';
                } else {
                    $this->npDocMensaje     = $data['message'] ?? 'DNI no encontrado.';
                    $this->npDocMensajeTipo = 'error';
                }
            }
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->npDocMensaje     = 'Error al consultar el servicio externo.';
            $this->npDocMensajeTipo = 'error';
        }
    }

    public function guardarNuevoProveedor(): void
    {
        $this->validate([
            'npNombre' => 'required|string|max:255',
            'npNumDoc' => 'required|string|max:20',
        ], [
            'npNombre.required' => 'El nombre / razón social es obligatorio.',
            'npNumDoc.required' => 'El número de documento es obligatorio.',
        ]);

        $id = DB::table('proveedores')->insertGetId([
            'id_tipo_documento'           => $this->npTipoDoc,
            'proveedores_nombre'          => strtoupper(trim($this->npNombre)),
            'proveedores_numero_documento'=> trim($this->npNumDoc),
            'proveedores_direccion'       => trim($this->npDireccion) ?: null,
            'proveedores_telefono'        => trim($this->npTelefono) ?: null,
            'proveedores_correo'          => trim($this->npCorreo) ?: null,
            'proveedores_estado'          => 1,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        $this->idProveedor = $id;
        $this->updatedIdProveedor();

        $this->reset(['npNombre', 'npNumDoc', 'npDireccion', 'npTelefono', 'npCorreo', 'npDocMensaje', 'npDocMensajeTipo']);
        $this->npTipoDoc = 4;

        $this->dispatch('cerrarModalNuevoProveedor');
    }

    public function toggleRevertirIgv(): void
    {
        $this->revertirDesagregarIgv = !$this->revertirDesagregarIgv;
    }

    // ── Limpiar ───────────────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->docAdjunto = null;
        $this->reset([
            'empresaIdCompra', 'idProveedor', 'idTipoPago', 'proveedorRuc', 'proveedorRazonSocial',
            'docSerie', 'docCorrelativo', 'numeroDoc', 'fechaVencimiento',
            'guiaRemitente', 'guiaTransportista', 'transportistas',
            'observacion', 'flete', 'gastosOp',
            'descuentoImporte', 'revertirDesagregarIgv', 'igvPorcentaje', 'percepcionPorcentaje',
            'items', 'buscarProducto', 'resultadosBusqueda',
        ]);
        $this->empresaIdCompra      = 1;
        $this->condicionPago        = 'contado';
        $this->estadoOrden          = 'recibido';
        $this->tipoDoc              = 'FACTURA';
        $this->moneda               = 'PEN';
        $this->flete                = '0';
        $this->gastosOp             = '0';
        $this->descuentoImporte     = '0';
        $this->igvPorcentaje        = '0';
        $this->percepcionPorcentaje = '0';
        $this->transportistas       = [['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => '']];
        $this->fechaEmision         = now()->format('Y-m-d');
        $this->fechaAlmacenamiento  = now()->endOfMonth()->format('Y-m-d');
        $this->proximoNumero = (string) ((int) DB::table('orden_compra')->max('id_orden_compra') + 1);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ── Query reutilizable del historial ──────────────────────
    private function buildHistorialQuery()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        return DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'oc.id_empresa')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
            ->select(
                'oc.id_orden_compra', 'oc.orden_compra_numero', 'oc.orden_compra_estado',
                'oc.orden_compra_fecha', 'oc.orden_compra_numero_doc', 'oc.orden_compra_tipo_doc',
                'oc.orden_compra_total', 'oc.id_sucursal', 'oc.condicion_pago',
                'pv.proveedores_nombre', 't.tienda_nombre', 'e.empresa_nombrecomercial',
                DB::raw("(SELECT COUNT(*) FROM notas_compra WHERE id_orden_compra = oc.id_orden_compra AND tipo_nota = 'NC' AND nota_estado != 'anulado') as notas_nc"),
                DB::raw("(SELECT COUNT(*) FROM notas_compra WHERE id_orden_compra = oc.id_orden_compra AND tipo_nota = 'DB' AND nota_estado != 'anulado') as notas_db"),
                DB::raw("(SELECT COUNT(*) FROM orden_compra_detalle d WHERE d.id_orden_compra = oc.id_orden_compra AND d.detalle_compra_cantidad_recibida IS NOT NULL AND d.detalle_compra_cantidad_recibida != d.detalle_compra_cantidad) as items_con_diferencia")
            )
            ->where('oc.orden_compra_activo', 1)
            ->when(($esSuperAdmin || $esAdmin) && $this->filtroEmpresa > 0, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('oc.id_empresa', $this->filtroEmpresa)
                          ->orWhereExists(fn($sub) => $sub
                              ->select(DB::raw(1))->from('tiendas')
                              ->whereColumn('tiendas.id_tienda', 'oc.id_sucursal')
                              ->where('tiendas.id_empresa', $this->filtroEmpresa));
                });
            })
            ->when($this->filtroProveedor > 0,
                fn($q) => $q->where('oc.id_proveedores', $this->filtroProveedor))
            ->when($this->filtroEstado !== '',
                fn($q) => $q->where('oc.orden_compra_estado', $this->filtroEstado))
            ->when($this->filtroCondicion !== '',
                fn($q) => $q->where('oc.condicion_pago', $this->filtroCondicion))
            ->when($this->filtroDesde,
                fn($q) => $q->whereDate('oc.orden_compra_fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta,
                fn($q) => $q->whereDate('oc.orden_compra_fecha', '<=', $this->filtroHasta))
            ->when($this->filtroDiferencia !== '', fn($q) => $q->whereExists(fn($sub) =>
                $sub->select(DB::raw(1))->from('orden_compra_detalle as df')
                    ->whereColumn('df.id_orden_compra', 'oc.id_orden_compra')
                    ->whereNotNull('df.detalle_compra_cantidad_recibida')
                    ->whereColumn('df.detalle_compra_cantidad_recibida', '!=', 'df.detalle_compra_cantidad')
            ))
            ->when($this->filtroDiferencia === 'sin_nota', fn($q) => $q
                ->whereRaw("(SELECT COUNT(*) FROM notas_compra WHERE id_orden_compra = oc.id_orden_compra AND nota_estado != 'anulado') = 0")
            )
            ->orderByDesc('oc.id_orden_compra');
    }

    // ── Exportar Excel ────────────────────────────────────────
    public function exportarExcel(): mixed
    {
        if (!auth()->user()->can('historial_compras.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return null;
        }

        $rows = $this->buildHistorialQuery()->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compras');

        $headers = ['#', 'N° Compra', 'Proveedor', 'Empresa', 'Fecha', 'Tipo Doc.', 'N° Doc.', 'Condición', 'Total (S/)', 'Estado', 'Dif. Recep.'];
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $estadoMap = ['pendiente' => 'Pendiente', 'en_transito' => 'En Tránsito', 'recibido' => 'Recibido', 'anulado' => 'Anulado'];

        $data = [];
        foreach ($rows as $i => $r) {
            $data[] = [
                $i + 1,
                $r->orden_compra_numero,
                $r->proveedores_nombre,
                $r->empresa_nombrecomercial ?? $r->tienda_nombre ?? '—',
                \Carbon\Carbon::parse($r->orden_compra_fecha)->format('d/m/Y'),
                $r->orden_compra_tipo_doc ?? '—',
                $r->orden_compra_numero_doc ?? '—',
                ucfirst($r->condicion_pago ?? '—'),
                (float) $r->orden_compra_total,
                $estadoMap[$r->orden_compra_estado] ?? $r->orden_compra_estado,
                $r->items_con_diferencia > 0 ? 'Sí ('.$r->items_con_diferencia.' ítems)' : '—',
            ];
        }

        $sheet->fromArray($data, null, 'A2');

        // Formato moneda columna I
        $sheet->getStyle("I2:I".(count($data)+1))
            ->getNumberFormat()->setFormatCode('"S/ "#,##0.00');

        // Colorear filas con diferencias
        foreach ($rows as $i => $r) {
            if ($r->items_con_diferencia > 0) {
                $row   = $i + 2;
                $color = ($r->notas_nc + $r->notas_db) == 0 ? 'FFF8E1' : 'FFF8E1';
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($color);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'historial_compras_'.now()->format('Ymd_His').'.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    // ── Exportar PDF ──────────────────────────────────────────
    public function exportarPdf(): mixed
    {
        if (!auth()->user()->can('historial_compras.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return null;
        }

        $rows = $this->buildHistorialQuery()->get();

        $pdf = new PDFBufeo('L', 'mm', 'A4');
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->AliasNbPages();

        // Título
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->Cell(0, 8, utf8_decode('Historial de Compras'), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 7.5);
        $periodo = ($this->filtroDesde && $this->filtroHasta)
            ? 'Período: '.date('d/m/Y', strtotime($this->filtroDesde)).' — '.date('d/m/Y', strtotime($this->filtroHasta))
            : 'Sin filtro de fecha';
        $pdf->Cell(0, 5, utf8_decode($periodo), 0, 1, 'C');
        $pdf->Ln(3);

        // Cabecera tabla
        $cols  = [8, 32, 55, 42, 22, 22, 22, 22, 26, 22];
        $heads = ['#', utf8_decode('N° Compra'), 'Proveedor', 'Empresa', 'Fecha', 'Tipo Doc.', utf8_decode('N° Doc.'), utf8_decode('Condición'), 'Total (S/)', 'Estado'];

        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 7);
        foreach ($heads as $i => $h) {
            $pdf->Cell($cols[$i], 7, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 6.5);
        $estadoMap = ['pendiente' => 'Pendiente', 'en_transito' => 'En Transito', 'recibido' => 'Recibido', 'anulado' => 'Anulado'];
        $empresaFallback = fn($r) => utf8_decode(mb_substr($r->empresa_nombrecomercial ?? $r->tienda_nombre ?? '-', 0, 22));
        $fill = false;

        foreach ($rows as $i => $r) {
            $conDif = $r->items_con_diferencia > 0;
            if ($conDif) {
                $pdf->SetFillColor(255, 248, 225);
            } else {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            }

            $pdf->Cell($cols[0],  6, $i + 1, 'B', 0, 'C', true);
            $pdf->Cell($cols[1],  6, utf8_decode($r->orden_compra_numero), 'B', 0, 'L', true);
            $pdf->Cell($cols[2],  6, utf8_decode(mb_substr($r->proveedores_nombre, 0, 28)), 'B', 0, 'L', true);
            $pdf->Cell($cols[3],  6, $empresaFallback($r), 'B', 0, 'L', true);
            $pdf->Cell($cols[4],  6, date('d/m/Y', strtotime($r->orden_compra_fecha)), 'B', 0, 'C', true);
            $pdf->Cell($cols[5],  6, utf8_decode(mb_substr($r->orden_compra_tipo_doc ?? '-', 0, 12)), 'B', 0, 'C', true);
            $pdf->Cell($cols[6],  6, utf8_decode(mb_substr($r->orden_compra_numero_doc ?? '-', 0, 12)), 'B', 0, 'C', true);
            $pdf->Cell($cols[7],  6, ucfirst($r->condicion_pago ?? '-'), 'B', 0, 'C', true);
            $pdf->Cell($cols[8],  6, 'S/ '.number_format($r->orden_compra_total, 2), 'B', 0, 'R', true);
            $pdf->Cell($cols[9],  6, utf8_decode($estadoMap[$r->orden_compra_estado] ?? $r->orden_compra_estado), 'B', 1, 'C', true);

            $fill = !$fill;
        }

        // Total
        $total = $rows->sum('orden_compra_total');
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetFillColor(240, 240, 240);
        $sumW = array_sum(array_slice($cols, 0, 8));
        $pdf->Cell($sumW, 6, utf8_decode('TOTAL'), 'B', 0, 'R', true);
        $pdf->Cell($cols[8], 6, 'S/ '.number_format($total, 2), 'B', 1, 'R', true);

        $filename = 'historial_compras_'.now()->format('Ymd_His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('S', '');
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $esAdmin         = $this->esAdmin();
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdministrador = $this->esAdministrador();

        $empresas = ($esSuperAdmin || $esAdmin || $esAdministrador)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        $proveedores = DB::table('proveedores')
            ->where('proveedores_estado', 1)
            ->orderBy('proveedores_nombre')
            ->get();

        $tiposPago = DB::table('tipo_pago')
            ->where('tipo_pago_estado', 1)
            ->orderBy('id_tipo_pago')
            ->get();

        // Historial paginado
        $ordenes = $this->buildHistorialQuery()->paginate($this->porPagina);

        $proveedoresHistorial = DB::table('proveedores')
            ->where('proveedores_estado', 1)
            ->orderBy('proveedores_nombre')
            ->get();

        $igvPct             = max(0, (float) $this->igvPorcentaje);
        $rawSubtotal        = round(collect($this->items)->sum('total'), 2);
        $subtotal           = ($this->revertirDesagregarIgv && $igvPct > 0)
            ? floor($rawSubtotal / (1 + $igvPct / 100) * 100) / 100
            : $rawSubtotal;
        $descuentoMonto     = round(max(0, (float) $this->descuentoImporte), 2);
        $subtotalNeto       = round($subtotal - $descuentoMonto, 2);
        $igvMonto           = round($subtotalNeto * $igvPct / 100, 2);
        $percepcionPct      = max(0, (float) $this->percepcionPorcentaje);
        $percepcionMonto    = round(($subtotalNeto + $igvMonto) * $percepcionPct / 100, 2);
        $fleteGlobal        = max(0, (float) $this->flete);
        $gastosGlobal       = max(0, (float) $this->gastosOp);
        $totalOrden         = round($subtotalNeto + $igvMonto + $percepcionMonto + $fleteGlobal + $gastosGlobal, 2);

        $almacenes = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', 1)
            ->select('a.id_almacen', 'a.almacen_nombre', 'e.empresa_nombrecomercial')
            ->orderBy('e.empresa_nombrecomercial')->get();

        $listaTransportistas = DB::table('transportistas')
            ->where('transportista_estado', 1)
            ->when($this->buscarTransportista, fn($q) =>
                $q->where('transportista_nombre', 'like', "%{$this->buscarTransportista}%")
                  ->orWhere('transportista_ruc', 'like', "%{$this->buscarTransportista}%")
            )
            ->orderBy('transportista_nombre')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        return view('livewire.logistica.compras', compact(
            'empresas', 'proveedores', 'tiposPago',
            'ordenes', 'proveedoresHistorial', 'almacenes',
            'esAdmin', 'esSuperAdmin', 'esAdministrador',
            'subtotal', 'descuentoMonto', 'subtotalNeto',
            'igvMonto', 'percepcionMonto', 'fleteGlobal', 'totalOrden',
            'listaTransportistas'
        ));
    }

    // ── SUNAT SIRE: buscar comprobantes ──────────────────────
    public function buscarEnSunat(): void
    {
        $this->sunatMensaje  = '';
        $this->sunatTipo     = '';
        $this->sunatFacturas = [];

        if ($this->empresaIdCompra <= 0) {
            $this->sunatMensaje = 'Primero selecciona una empresa.';
            $this->sunatTipo    = 'warning';
            return;
        }

        $empresa = DB::table('empresa')->where('id_empresa', $this->empresaIdCompra)->first();
        if (!$empresa || !trim($empresa->empresa_usuario_sol ?? '') || !trim($empresa->empresa_clave_sol ?? '')) {
            $this->sunatMensaje = 'La empresa no tiene credenciales SOL configuradas. Verifica en Configuración → Empresas.';
            $this->sunatTipo    = 'error';
            return;
        }

        $this->sunatBuscando = true;

        try {
            // ── Paso 5.1: obtener token ──────────────────────────
            if (empty($empresa->empresa_sire_client_id) || empty($empresa->empresa_sire_client_secret)) {
                $this->sunatMensaje  = 'Esta empresa no tiene credenciales SIRE configuradas (Client ID / Client Secret). Configúralas en Configuración → Empresas.';
                $this->sunatTipo     = 'error';
                $this->sunatBuscando = false;
                return;
            }

            $token = $this->sireToken(
                $empresa->empresa_ruc,
                $empresa->empresa_usuario_sol,
                $empresa->empresa_clave_sol,
                $empresa->empresa_sire_client_id,
                $empresa->empresa_sire_client_secret
            );

            if (!$token) {
                $this->sunatMensaje  = 'No se pudo autenticar con SUNAT SIRE. Verifica usuario SOL, clave SOL y que el Client ID esté habilitado en api.sunat.gob.pe.';
                $this->sunatTipo     = 'error';
                $this->sunatBuscando = false;
                return;
            }

            // Parámetros de búsqueda
            $periodo = now()->format('Ym');  // Ej: 202605

            // Fechas dentro del periodo actual en formato dd/mm/yyyy (requerido por SUNAT SIRE)
            $fecIni = now()->startOfMonth()->format('d/m/Y');
            $fecFin = now()->format('d/m/Y');

            $serie  = null;
            $numCDP = null;

            $numDoc = trim($this->numeroDoc);
            if ($numDoc && preg_match('/^([A-Za-z]{1,2}\d{3})-(\d+)$/i', $numDoc, $m)) {
                $serie  = strtoupper($m[1]);
                $numCDP = $m[2];
            }

            // ── Paso 5.34: solicitar propuesta → obtener ticket ──
            // Debug: log URL y token para diagnóstico
            \Log::info('SIRE 5.34 token prefix: ' . substr($token, 0, 20) . '... length=' . strlen($token));

            [$ticket, $httpCode, $httpBody] = $this->sireSolicitarPropuesta(
                $token, $empresa->empresa_ruc, $periodo,
                $fecIni, $fecFin, $serie, $numCDP
            );

            if (!$ticket) {
                $this->sunatMensaje = match(true) {
                    $httpCode === 401 => 'Acceso denegado (401): el cliente SIRE no tiene permisos para este RUC. Verifica en api.sunat.gob.pe.',
                    $httpCode === 422 => 'Parámetros inválidos (422): ' . mb_substr(strip_tags($httpBody), 0, 300),
                    $httpCode === 502 => 'Servicio SUNAT no disponible (502). Intenta nuevamente en unos minutos.',
                    default           => "SUNAT propuesta HTTP {$httpCode}: " . mb_substr(strip_tags($httpBody), 0, 200),
                };
                $this->sunatTipo     = 'error';
                $this->sunatBuscando = false;
                return;
            }

            // ── Paso 5.31: esperar que el ticket esté listo (hasta 8 intentos) ──
            // El estado final según el manual SIRE es "Terminado"
            $nomArchivo  = null;
            $codTipoArch = null;

            for ($i = 0; $i < 8; $i++) {
                sleep(2);
                $estado = $this->sireConsultarTicket($token, $empresa->empresa_ruc, $ticket, $periodo);
                if (!$estado) continue;

                foreach ($estado['registros'] ?? [] as $reg) {
                    if (($reg['numTicket'] ?? '') !== $ticket) continue;

                    $codEstado = $reg['codEstadoProceso'] ?? '';
                    $desEstado = $reg['desEstadoProceso'] ?? '';

                    // El manual indica que el estado listo es "Terminado"
                    if (strtolower(trim($codEstado)) === 'terminado' ||
                        strtolower(trim($desEstado)) === 'terminado') {

                        // Priorizar archivoReporte[]
                        foreach ($reg['archivoReporte'] ?? [] as $arch) {
                            $nomArchivo  = $arch['nomArchivoReporte']    ?? null;
                            $codTipoArch = $arch['codTipoAchivoReporte'] ?? null; // typo del API de SUNAT
                            if ($nomArchivo) break;
                        }
                        // Fallback: detalleTicket[]
                        if (!$nomArchivo) {
                            foreach ($reg['detalleTicket'] ?? [] as $det) {
                                if (!empty($det['nomArchivoReporte'])) {
                                    $nomArchivo  = $det['nomArchivoReporte'];
                                    $codTipoArch = $det['codTipoAchivoReporte'] ?? null;
                                    break;
                                }
                            }
                        }
                        break 2;
                    }
                }
            }

            if (!$nomArchivo) {
                $this->sunatMensaje  = 'SUNAT está procesando la solicitud. Intenta de nuevo en unos segundos.';
                $this->sunatTipo     = 'warning';
                $this->sunatBuscando = false;
                return;
            }

            // ── Paso 5.32: descargar archivo ─────────────────────
            $archivoContenido = $this->sireDescargarArchivo($token, $empresa->empresa_ruc, $nomArchivo, $codTipoArch);

            if (!$archivoContenido) {
                $this->sunatMensaje  = 'No se pudo descargar el archivo de SUNAT.';
                $this->sunatTipo     = 'error';
                $this->sunatBuscando = false;
                return;
            }

            // ── Parsear CSV/TXT ───────────────────────────────────
            $facturas = $this->sireParsearArchivo($archivoContenido);

            if (empty($facturas)) {
                $this->sunatMensaje = 'No se encontraron comprobantes en el periodo consultado.';
                $this->sunatTipo    = 'warning';
            } else {
                $this->sunatFacturas = $facturas;
                $this->modalSunat    = true;
                $this->dispatch('abrirModalSunat');
            }

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->sunatMensaje = 'Error al consultar SUNAT: ' . $e->getMessage();
            $this->sunatTipo    = 'error';
        }

        $this->sunatBuscando = false;
    }

    public function aplicarFacturaSunat(int $idx): void
    {
        $f = $this->sunatFacturas[$idx] ?? null;
        if (!$f) return;

        $this->numeroDoc    = ($f['serie'] ?? '') . '-' . ($f['numero'] ?? '');
        $this->tipoDoc      = match($f['tipoComprobante'] ?? '01') {
            '01' => 'FACTURA', '03' => 'BOLETA', '07' => 'NOTA DE CRÉDITO', '08' => 'NOTA DE DÉBITO', default => 'FACTURA',
        };
        $this->fechaEmision = $f['fechaEmision'] ?? $this->fechaEmision;

        $emisor             = ($f['razonSocial'] ?? '') ?: ($f['numRuc'] ?? '');
        $total              = number_format((float)($f['mtoTotal'] ?? 0), 2);
        $this->sunatMensaje  = "Aplicado: {$emisor} — S/ {$total}";
        $this->sunatTipo     = 'success';
        $this->modalSunat    = false;
        $this->sunatFacturas = [];
    }

    public function cerrarModalSunat(): void
    {
        $this->modalSunat    = false;
        $this->sunatFacturas = [];
    }

    // ── Helpers SIRE ─────────────────────────────────────────
    private function sireCurl(string $url, array $headers = [], ?array $postData = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (config('services.facturacion.cacert') == 1) {
            curl_setopt($ch, CURLOPT_CAINFO, app_path('Models/cacert.pem'));
        }

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$httpCode, $response ?: ''];
    }

    private function sireToken(string $ruc, string $usuario, string $clave, string $clientId, string $clientSecret): ?string
    {
        [$code, $resp] = $this->sireCurl(
            "https://api-seguridad.sunat.gob.pe/v1/clientessol/{$clientId}/oauth2/token/",
            ['Content-Type: application/x-www-form-urlencoded'],
            [
                'grant_type'    => 'password',
                'scope'         => 'https://api-sire.sunat.gob.pe',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'username'      => $ruc . $usuario,
                'password'      => $clave,
            ]
        );

        if ($code === 200) {
            $token = json_decode($resp, true)['access_token'] ?? null;
            return $token ? trim($token) : null;
        }
        return null;
    }

    private function sireSolicitarPropuesta(
        string $token, string $ruc, string $periodo,
        string $fecIni, string $fecFin,
        ?string $serie, ?string $numCDP
    ): array {
        $params = [
            'codTipoArchivo' => 0,
            'codOrigenEnvio' => '2',
        ];

        if ($fecIni !== '') $params['fecEmisionIni'] = $fecIni;
        if ($fecFin !== '') $params['fecEmisionFin'] = $fecFin;
        if ($serie  !== null && $serie  !== '') $params['numSerieCDP'] = $serie;
        if ($numCDP !== null && $numCDP !== '') $params['numCDP']      = $numCDP;

        $url = "https://api-sire.sunat.gob.pe/v1/contribuyente/migeigv/libros/rce/propuesta/web/propuesta/{$periodo}/exportacioncomprobantepropuesta?" . http_build_query($params);

        \Log::info('SIRE 5.34 URL: ' . $url);

        [$code, $resp] = $this->sireCurl($url, [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        $ticket = null;
        if ($code === 200) {
            $ticket = json_decode($resp, true)['numTicket'] ?? null;
        }

        return [$ticket, $code, $resp];
    }

    private function sireConsultarTicket(string $token, string $ruc, string $ticket, string $periodo): ?array
    {
        $qs = http_build_query([
            'perIni'    => $periodo,
            'perFin'    => $periodo,
            'numTicket' => $ticket,
            'page'      => 1,
            'perPage'   => 20,
        ]);

        [$code, $resp] = $this->sireCurl(
            "https://api-sire.sunat.gob.pe/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets?{$qs}",
            [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
                'Accept: application/json',
            ]
        );

        return ($code === 200) ? json_decode($resp, true) : null;
    }

    private function sireDescargarArchivo(string $token, string $ruc, string $nomArchivo, ?string $codTipo): ?string
    {
        $qs = http_build_query(array_filter([
            'nomArchivoReporte'     => $nomArchivo,
            'codTipoArchivoReporte' => $codTipo,
        ], fn($v) => $v !== null));

        [$code, $resp] = $this->sireCurl(
            "https://api-sire.sunat.gob.pe/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/archivoreporte?{$qs}",
            [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
                'Accept: application/json',
            ]
        );

        if ($code !== 200 || !$resp) return null;

        // Si viene zipeado lo extraemos en memoria
        $tmpZip = tempnam(sys_get_temp_dir(), 'sire_') . '.zip';
        file_put_contents($tmpZip, $resp);

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip) === true) {
            $contenido = '';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $contenido .= $zip->getFromIndex($i);
            }
            $zip->close();
            unlink($tmpZip);
            return $contenido ?: null;
        }

        unlink($tmpZip);
        // Si no era zip, el resp ya es el CSV directo
        return $resp;
    }

    private function sireParsearArchivo(string $contenido): array
    {
        $facturas = [];
        $lineas   = preg_split('/\r?\n/', trim($contenido));

        // Detectar separador: | para TXT/PLE, , para CSV
        $sep = str_contains($lineas[0] ?? '', '|') ? '|' : ',';

        // Saltar cabecera si la primera línea no es numérica (empieza con número de período)
        $inicio = 0;
        if (!preg_match('/^\d{6}/', $lineas[0] ?? '')) $inicio = 1;

        foreach (array_slice($lineas, $inicio) as $linea) {
            $linea = trim($linea);
            if (!$linea) continue;

            $cols = str_getcsv($linea, $sep);

            // Formato PLE 8.1 (Registro de Compras):
            // [0] Período  [1] CUO  [2] Correlativo  [3] FechaEmisión
            // [5] TipoComprobante  [6] Serie  [7] Número
            // [9] RUC_Proveedor  [10] RazonSocial
            // [11..] montos
            if (count($cols) < 11) continue;

            $fecRaw = trim($cols[3] ?? '');
            // Normalizar fecha a Y-m-d
            $fecha = $fecRaw;
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecRaw, $fm)) {
                $fecha = "{$fm[3]}-{$fm[2]}-{$fm[1]}";
            }

            // Sumar montos para obtener total (columnas variables según tipo)
            $mtoTotal = 0;
            for ($c = 11; $c < min(count($cols), 20); $c++) {
                $v = (float) str_replace(',', '.', trim($cols[$c] ?? '0'));
                if ($v > 0) $mtoTotal += $v;
            }

            $facturas[] = [
                'tipoComprobante' => trim($cols[5] ?? ''),
                'serie'           => trim($cols[6] ?? ''),
                'numero'          => trim($cols[7] ?? ''),
                'fechaEmision'    => $fecha,
                'numRuc'          => trim($cols[9]  ?? ''),
                'razonSocial'     => trim($cols[10] ?? ''),
                'mtoTotal'        => $mtoTotal,
                'estado'          => 'ACEPTADO',
            ];
        }

        return array_slice($facturas, 0, 10); // máximo 10 resultados
    }
}
