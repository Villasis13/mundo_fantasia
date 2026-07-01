<?php

namespace App\Livewire\GestionVentas;

use App\Models\General;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GenerarGuia extends Component
{
    // ── Contexto ──────────────────────────────────────────────
    public int $idTienda  = 0;
    public int $idEmpresa = 0;

    // ── Sección 1: Información de la guía ──────────────────────
    public string $guiaTipo        = '09'; // 09 Remitente | 31 Transportista
    public string $guiaEmision     = '';
    public string $guiaTraslado    = '';
    public string $guiaMotivo      = '01';
    public string $guiaObservacion = '';

    // ── Sección 2: Cliente / Destinatario ─────────────────────
    public ?int   $idClientes   = null;
    public ?int   $idVenta      = null;
    public string $facturaVinculada = '';
    public string $cliTipoDoc   = '';
    public string $cliNumDoc    = '';
    public string $cliNombre    = '';
    public string $cliDireccion = '';
    public string $cliDistrito     = '';
    public string $cliProvincia    = '';
    public string $cliDepartamento = '';

    // ── Sección 3: Transporte ─────────────────────────────────
    public string $tipoTrans     = '02'; // 02 privado | 01 público
    public string $transRuc      = '';
    public string $transNombre   = '';
    public string $vehPlaca      = '';
    public string $vehMarca      = '';
    public string $vehCarreta    = '';
    public string $certMtc       = '';
    public string $pesoBruto     = '0';
    public string $unidadMedida  = 'KGM';
    public string $nroBultos     = '1';
    // Conductor
    public string $condTipoDoc   = '1';
    public string $condNumDoc    = '';
    public string $condNombre    = '';
    public string $condApellidos = '';
    public string $condLicencia  = '';

    // ── Sección 4: Puntos ─────────────────────────────────────
    public string $partidaKey      = '';  // 'almacen_{id}' | 'empresa_{id}'
    public int    $idTiendaPartida = 0;
    public string $dirPartida    = '';
    public string $ubigeoPartida = '';
    public string $dirLlegada    = '';
    public string $ubigeoLlegada = '';

    // ── Sección 5: Bienes ─────────────────────────────────────
    public array  $items               = [];
    public string $buscarProducto       = '';
    public array  $resultadosProductos  = [];

    // ── Vincular factura (modal) ──────────────────────────────
    public string $factSerie       = '';
    public string $factCorrelativo = '';
    public array  $factResultados  = [];
    public string $factMensaje     = '';

    // ── Buscar cliente (modal) ────────────────────────────────
    public string $buscarCliente      = '';
    public array  $resultadosClientes = [];

    private $logs;

    public function boot(): void { $this->logs = new Logs(); }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('guias_remision.listar'), 403);

        $tienda = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->select('t.id_tienda', 't.id_empresa')
            ->first();
        if ($tienda) {
            $this->idTienda  = (int) $tienda->id_tienda;
            $this->idEmpresa = (int) $tienda->id_empresa;
        }

        $this->guiaEmision  = now()->format('Y-m-d');
        $this->guiaTraslado = now()->format('Y-m-d');
    }

    public function getSerieProperty(): string
    {
        return $this->guiaTipo === '31' ? 'V001' : 'T001';
    }

    // ── Punto de partida (almacén / empresa-sede) ─────────────
    public function updatedPartidaKey(): void
    {
        $this->idTiendaPartida = 0;

        if (str_starts_with($this->partidaKey, 'almacen_')) {
            $id = (int) substr($this->partidaKey, 8);
            $this->dirPartida = (string) (DB::table('almacen')->where('id_almacen', $id)->value('almacen_direccion') ?? '');
        } elseif (!str_starts_with($this->partidaKey, 'empresa_')) {
            $this->dirPartida = '';
        }
    }

    public function updatedIdTiendaPartida(): void
    {
        if ($this->idTiendaPartida > 0) {
            $this->dirPartida = (string) (DB::table('tiendas')->where('id_tienda', $this->idTiendaPartida)->value('tienda_direccion') ?? '');
        }
    }

    public function partidaEmpresaId(): ?int
    {
        if (str_starts_with($this->partidaKey, 'empresa_')) {
            $id = (int) substr($this->partidaKey, 8);
            return $id > 0 ? $id : null;
        }
        return null;
    }

    // ── Buscar producto ───────────────────────────────────────
    public function updatedBuscarProducto(): void
    {
        $t = trim($this->buscarProducto);
        if (strlen($t) < 2) { $this->resultadosProductos = []; return; }

        $this->resultadosProductos = DB::table('productos as p')
            ->where('p.pro_estado', 1)
            ->where(function ($q) use ($t) {
                $q->where('p.pro_nombre', 'like', "%{$t}%")
                  ->orWhere('p.pro_codigo', 'like', "%{$t}%");
            })
            ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo')
            ->orderBy('p.pro_nombre')
            ->limit(15)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function agregarProducto(int $idPro, string $codigo, string $nombre): void
    {
        $this->items[] = [
            'id_pro'      => $idPro,
            'codigo'      => $codigo,
            'descripcion' => $nombre,
            'um'          => 'NIU',
            'cantidad'    => '1',
            'peso'        => '',
            'observacion' => '',
        ];
        $this->buscarProducto      = '';
        $this->resultadosProductos = [];
    }

    public function agregarItemManual(): void
    {
        $this->items[] = [
            'id_pro' => null, 'codigo' => '', 'descripcion' => '',
            'um' => 'NIU', 'cantidad' => '1', 'peso' => '', 'observacion' => '',
        ];
    }

    public function quitarItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function getPesoTotalProperty(): float
    {
        $total = 0;
        foreach ($this->items as $it) {
            $total += (float) ($it['cantidad'] ?? 0) * (float) ($it['peso'] ?? 0);
        }
        return round($total, 3);
    }

    // ── Autocompletar cliente por documento ───────────────────
    public function updatedCliNumDoc(): void
    {
        $doc = trim($this->cliNumDoc);
        $len = strlen($doc);
        if ($len !== 8 && $len !== 11) return;

        $cli = DB::table('clientes')->where('cliente_numero', $doc)->first();
        if ($cli) {
            $this->idClientes = (int) $cli->id_clientes;
            $this->cliTipoDoc = (string) $cli->id_tipo_documento;
            $this->cliNombre  = $cli->cliente_razonsocial ?: ($cli->cliente_nombre ?? '');
            $this->cliDireccion = $cli->cliente_direccion ?? '';
            return;
        }

        $tipo = $len === 11 ? '4' : '2';
        $r = (new General())->consultar_documento_migo($tipo, $doc);
        if (($r['success'] ?? false) && !empty($r['data']['nombre'])) {
            $this->cliNombre = $r['data']['nombre'];
        }
    }

    // ── Buscar cliente (modal) ────────────────────────────────
    public function updatedBuscarCliente(): void
    {
        $t = trim($this->buscarCliente);
        if (strlen($t) < 2) { $this->resultadosClientes = []; return; }

        $this->resultadosClientes = DB::table('clientes')
            ->where('cliente_estado', 1)
            ->where(function ($q) use ($t) {
                $q->where('cliente_nombre', 'like', "%{$t}%")
                  ->orWhere('cliente_razonsocial', 'like', "%{$t}%")
                  ->orWhere('cliente_numero', 'like', "%{$t}%");
            })
            ->select('id_clientes', 'id_tipo_documento', 'cliente_nombre', 'cliente_razonsocial', 'cliente_numero', 'cliente_direccion')
            ->orderBy('cliente_nombre')->limit(15)->get()->map(fn($r) => (array) $r)->toArray();
    }

    public function seleccionarCliente($id = 0): void
    {
        $id = (int) $id;
        $c = DB::table('clientes')->where('id_clientes', $id)->first();
        if (!$c) return;
        $this->idClientes  = $id;
        $this->cliTipoDoc  = (string) $c->id_tipo_documento;
        $this->cliNumDoc   = $c->cliente_numero ?? '';
        $this->cliNombre   = $c->cliente_razonsocial ?: ($c->cliente_nombre ?? '');
        $this->cliDireccion = $c->cliente_direccion ?? '';
        $this->buscarCliente = '';
        $this->resultadosClientes = [];
        $this->dispatch('cerrarModalClienteGuia');
    }

    // ── Vincular factura ──────────────────────────────────────
    public function buscarFactura(): void
    {
        $this->factMensaje = '';
        $serie = trim($this->factSerie);
        $corr  = trim($this->factCorrelativo);

        $q = DB::table('ventas as v')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            // Solo ventas que aún NO están vinculadas a una guía (activa)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('guias_remision as gx')
                    ->whereColumn('gx.id_venta', 'v.id_venta')
                    ->where('gx.guia_estado', '!=', 0);
            })
            ->select(
                'v.id_venta', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_total', 'v.id_clientes',
                'v.venta_tipo', 'v.venta_fecha',
                'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_direccion'
            );
        if ($serie !== '') $q->where('v.venta_serie', 'like', "%{$serie}%");
        if ($corr  !== '') $q->where('v.venta_correlativo', 'like', "%{$corr}%");

        $ventas = $q->orderByDesc('v.id_venta')->limit(5)->get();

        if ($ventas->isEmpty()) {
            $this->factResultados = [];
            $this->factMensaje = ($serie !== '' || $corr !== '')
                ? 'No se encontraron facturas con esos datos.'
                : 'No hay ventas pendientes de vincular.';
            return;
        }

        $tipos = ['01' => 'Factura', '03' => 'Boleta', '20' => 'Nota de Venta', '07' => 'N. Crédito', '08' => 'N. Débito'];
        $this->factResultados = $ventas->map(fn($v) => [
            'id_venta'          => $v->id_venta,
            'tipo'              => $tipos[$v->venta_tipo] ?? $v->venta_tipo,
            'comprobante'       => $v->venta_serie . '-' . str_pad((string)$v->venta_correlativo, 8, '0', STR_PAD_LEFT),
            'fecha'             => \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y'),
            'serie'             => $v->venta_serie,
            'correlativo'       => str_pad((string)$v->venta_correlativo, 8, '0', STR_PAD_LEFT),
            'total'             => number_format((float)$v->venta_total, 2),
            'id_clientes'       => $v->id_clientes,
            'id_tipo_documento' => $v->id_tipo_documento ?? '',
            'cliente_numero'    => $v->cliente_numero,
            'cliente_nombre'    => $v->cliente_razonsocial ?: $v->cliente_nombre,
            'cliente_direccion' => $v->cliente_direccion ?? '',
        ])->toArray();
    }

    public function cargarFacturasIniciales(): void
    {
        // Al abrir el modal: mostrar las últimas 5 ventas sin vincular (sin filtros)
        $this->factSerie = '';
        $this->factCorrelativo = '';
        $this->factMensaje = '';
        $this->buscarFactura();
    }

    public function vincularFactura($idVenta = 0): void
    {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) return;

        $v = DB::table('ventas as v')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->where('v.id_venta', $idVenta)
            ->select('v.*', 'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_direccion')
            ->first();
        if (!$v) return;

        $this->idVenta          = $idVenta;
        $this->facturaVinculada = $v->venta_serie . '-' . str_pad((string) $v->venta_correlativo, 8, '0', STR_PAD_LEFT);
        $this->idClientes  = (int) $v->id_clientes;
        $this->cliTipoDoc  = (string) $v->id_tipo_documento;
        $this->cliNumDoc   = $v->cliente_numero ?? '';
        $this->cliNombre   = $v->cliente_razonsocial ?: ($v->cliente_nombre ?? '');
        $this->cliDireccion = $v->cliente_direccion ?? '';

        // Cargar productos de la venta como ítems
        $det = DB::table('ventas_detalle as vd')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->where('vd.id_venta', $idVenta)
            ->select('vd.id_pro', 'vd.venta_detalle_nombre_producto', 'vd.venta_detalle_cantidad', 'p.pro_codigo')
            ->get();
        $this->items = $det->map(fn($d) => [
            'id_pro'      => $d->id_pro,
            'codigo'      => $d->pro_codigo ?? '',
            'descripcion' => $d->venta_detalle_nombre_producto,
            'um'          => 'NIU',
            'cantidad'    => (string) (int) $d->venta_detalle_cantidad,
            'peso'        => '',
            'observacion' => '',
        ])->toArray();

        $this->factResultados = [];
        $this->factSerie = '';
        $this->factCorrelativo = '';
        $this->dispatch('cerrarModalFacturaGuia');
        session()->flash('success', 'Factura ' . $v->venta_serie . '-' . $v->venta_correlativo . ' vinculada.');
    }

    public function desvincularFactura(): void
    {
        $this->idVenta = null;
        $this->facturaVinculada = '';
        $this->items = [];
    }

    // ── Guardar ───────────────────────────────────────────────
    public function guardar(): void
    {
        if (!auth()->user()->can('guias_remision.crear')) {
            session()->flash('error', 'No tienes permiso para registrar guías.');
            return;
        }

        $this->validate([
            'guiaEmision'   => 'required|date',
            'guiaTraslado'  => 'required|date',
            'guiaTipo'      => 'required|in:09,31',
            'guiaMotivo'    => 'required|string',
            'cliNombre'     => 'required|string|max:300',
            'dirPartida'    => 'required|string|max:300',
            'ubigeoPartida' => 'required|string',
            'dirLlegada'    => 'required|string|max:300',
            'ubigeoLlegada' => 'required|string',
            'vehPlaca'      => 'required|string|max:10',
            'items'         => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad'    => 'required|numeric|min:0.01',
        ], [
            'cliNombre.required'     => 'El nombre del destinatario es obligatorio.',
            'items.required'         => 'Agregue al menos un bien a trasladar.',
            'items.min'              => 'Agregue al menos un bien a trasladar.',
            'vehPlaca.required'      => 'La placa del vehículo es obligatoria.',
            'ubigeoPartida.required' => 'Seleccione el ubigeo de partida.',
            'ubigeoLlegada.required' => 'Seleccione el ubigeo de llegada.',
        ]);

        DB::beginTransaction();
        try {
            $serie = $this->serie;
            $ultimo = DB::table('guias_remision')->where('guia_serie', $serie)->max('guia_correlativo');
            $correlativo = (int) $ultimo + 1;

            $idGuia = DB::table('guias_remision')->insertGetId([
                'id_empresa'              => $this->idEmpresa,
                'id_sucursal'             => $this->idTienda ?: null,
                'id_users'                => auth()->user()->id_users,
                'id_venta'                => $this->idVenta,
                'id_orden_compra'         => null,
                'id_transferencia'        => null,
                'guia_serie'              => $serie,
                'guia_correlativo'        => $correlativo,
                'guia_tipo'               => $this->guiaTipo,
                'guia_fecha_emision'      => $this->guiaEmision,
                'guia_fecha_traslado'     => $this->guiaTraslado,
                'guia_motivo_traslado'    => $this->guiaMotivo,
                'guia_modalidad_traslado' => $this->tipoTrans,
                'guia_observaciones'      => trim($this->guiaObservacion) ?: null,
                'guia_peso_bruto'         => (float) $this->pesoBruto,
                'guia_unidad_medida'      => $this->unidadMedida,
                'guia_nro_bultos'         => (int) $this->nroBultos,
                'guia_dest_tipo_doc'      => $this->cliTipoDoc ?: null,
                'guia_dest_numero_doc'    => trim($this->cliNumDoc) ?: null,
                'guia_dest_nombre'        => trim($this->cliNombre),
                'guia_dest_direccion'     => trim($this->cliDireccion) ?: null,
                'guia_partida_ubigeo'     => $this->ubigeoPartida,
                'guia_partida_direccion'  => trim($this->dirPartida),
                'guia_llegada_ubigeo'     => $this->ubigeoLlegada,
                'guia_llegada_direccion'  => trim($this->dirLlegada),
                'guia_transportista_ruc'  => trim($this->transRuc) ?: null,
                'guia_transportista_nombre' => trim($this->transNombre) ?: null,
                'guia_transportista_mtt'  => trim($this->certMtc) ?: null,
                'guia_vehiculo_placa'     => strtoupper(trim($this->vehPlaca)),
                'guia_vehiculo_marca'     => trim($this->vehMarca) ?: null,
                'guia_vehiculo_carreta'   => trim($this->vehCarreta) ?: null,
                'guia_conductor_tipo_doc' => $this->condTipoDoc ?: null,
                'guia_conductor_numero_doc' => trim($this->condNumDoc) ?: null,
                'guia_conductor_nombre'   => trim($this->condApellidos . ' ' . $this->condNombre) ?: null,
                'guia_conductor_licencia' => trim($this->condLicencia) ?: null,
                'guia_estado_sunat'       => 0,
                'guia_estado'             => 1,
                'created_at'              => now(),
                'updated_at'              => now(),
            ], 'id_guia');

            foreach ($this->items as $it) {
                DB::table('guias_remision_detalle')->insert([
                    'id_guia'               => $idGuia,
                    'id_pro'                => $it['id_pro'] ?: null,
                    'detalle_descripcion'   => trim($it['descripcion']),
                    'detalle_codigo'        => trim($it['codigo'] ?? '') ?: null,
                    'detalle_cantidad'      => (float) $it['cantidad'],
                    'detalle_unidad_medida' => $it['um'] ?? 'NIU',
                    'detalle_peso_unitario' => (float) ($it['peso'] ?? 0),
                    'detalle_observacion'   => trim($it['observacion'] ?? '') ?: null,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            DB::commit();
            $numeroMostrar = $serie . '-' . str_pad((string) $correlativo, 8, '0', STR_PAD_LEFT);
            session()->flash('success', "Guía {$numeroMostrar} registrada correctamente.");
            $this->resetFormulario();
            // 1) limpia formulario  2) abre el PDF en pestaña nueva  3) redirige al listado
            $this->dispatch('guiaGuardada',
                pdf:   route('Gestionventas.imprimir_guia_pdf', ['id_guia' => $idGuia]),
                lista: route('Gestionventas.guias_remision')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar la guía: ' . $e->getMessage());
        }
    }

    private function resetFormulario(): void
    {
        $this->reset([
            'guiaObservacion', 'idClientes', 'idVenta', 'facturaVinculada', 'cliTipoDoc', 'cliNumDoc', 'cliNombre', 'cliDireccion',
            'cliDistrito', 'cliProvincia', 'cliDepartamento',
            'transRuc', 'transNombre', 'vehPlaca', 'vehMarca', 'vehCarreta', 'certMtc',
            'condNumDoc', 'condNombre', 'condApellidos', 'condLicencia',
            'partidaKey', 'idTiendaPartida', 'dirPartida', 'ubigeoPartida', 'dirLlegada', 'ubigeoLlegada',
            'items', 'buscarProducto', 'resultadosProductos',
        ]);
        $this->pesoBruto = '0';
        $this->nroBultos = '1';
        $this->resetErrorBag();
    }

    public function render()
    {
        $tipoDocs = DB::table('tipo_documento')->where('tipo_documento_estado', 1)
            ->get(['id_tipo_documento', 'tipo_documento_identidad']);
        $ubigeos = DB::table('ubigeo')
            ->orderBy('ubigeo_departamento')->orderBy('ubigeo_provincia')->orderBy('ubigeo_distrito')
            ->get(['ubigeo_cod', 'ubigeo_departamento', 'ubigeo_provincia', 'ubigeo_distrito']);

        // Punto de partida (como el Origen de Nueva Transferencia)
        $almacenes = DB::table('almacen as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            ->where('a.almacen_estado', 1)
            ->orderBy('e.empresa_nombrecomercial')->orderBy('a.almacen_nombre')
            ->get(['a.id_almacen', 'a.almacen_nombre', 'a.almacen_direccion', 'e.empresa_nombrecomercial']);

        $empresasPartida = DB::table('empresa')->where('empresa_estado', '!=', 0)
            ->orderBy('empresa_razon_social')->get(['id_empresa', 'empresa_nombrecomercial']);

        // Siguiente número de guía para la serie actual
        $nextNumero = (int) DB::table('guias_remision')->where('guia_serie', $this->serie)->max('guia_correlativo') + 1;

        return view('livewire.gestion-ventas.generar-guia', compact('tipoDocs', 'ubigeos', 'nextNumero'));
    }
}
