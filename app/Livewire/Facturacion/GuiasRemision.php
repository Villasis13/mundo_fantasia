<?php

namespace App\Livewire\Facturacion;

use App\Models\apiFacturacion;
use App\Models\GeneradorXML;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GuiasRemision extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];
    public      $empresasDisponibles   = [];

    private $logs;

    // ── Filtros ───────────────────────────────────────────────
    public string $desde        = '';
    public string $hasta        = '';
    public string $filtroEstado = '';
    public string $filtroMotivo = '';

    // ── Lista ─────────────────────────────────────────────────
    public $guias = [];

    // ── Modal crear ───────────────────────────────────────────
    public bool $modalAbierto = false;

    public string $guia_serie              = 'T001';
    public string $guia_correlativo        = '';
    public string $guia_fecha_emision      = '';
    public string $guia_fecha_traslado     = '';
    public string $guia_motivo_traslado    = '01';
    public string $guia_modalidad_traslado = '01';
    public string $guia_observaciones      = '';
    public string $guia_peso_bruto         = '0';

    public string $guia_dest_tipo_doc    = '6';
    public string $guia_dest_numero_doc  = '';
    public string $guia_dest_nombre      = '';
    public string $guia_dest_direccion   = '';

    public string $guia_partida_ubigeo    = '';
    public string $guia_partida_direccion = '';
    public string $partida_ubigeo_texto   = '';
    public string $guia_llegada_ubigeo    = '';
    public string $guia_llegada_direccion = '';
    public string $llegada_ubigeo_texto   = '';

    public string $guia_transportista_ruc    = '';
    public string $guia_transportista_nombre = '';
    public string $guia_transportista_mtt    = '';

    public string $guia_vehiculo_placa       = '';
    public string $guia_conductor_tipo_doc   = '1';
    public string $guia_conductor_numero_doc = '';
    public string $guia_conductor_nombre     = '';
    public string $guia_conductor_licencia   = '';

    public array $items = [];

    // Búsqueda ubigeo
    public string $busquedaUbigeoPartida = '';
    public string $busquedaUbigeoLlegada = '';
    public        $sugerenciasPartida    = [];
    public        $sugerenciasLlegada    = [];

    // ── Modal confirmación ────────────────────────────────────
    public ?int   $idGuiaConfirmacion  = null;
    public string $mensajeConfirmacion = '';

    // ── Modal detalle ─────────────────────────────────────────
    public bool $modalDetalleAbierto = false;
    public      $guiaDetalle         = null;
    public      $itemsDetalle        = [];

    // ── Boot / Mount ──────────────────────────────────────────

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

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        if ($this->esAdmin()) {
            return $this->adminEmpresaId();
        }
        $idSucursal = (int) session('sucursal_activa_id', 0);
        if (!$idSucursal) return null;
        $id = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdSucursal(): int
    {
        if ($this->sucursalSeleccionada > 0) return $this->sucursalSeleccionada;
        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            return (int) session('sucursal_activa_id', 0);
        }
        return 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('guias_remision.submenu'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta  = now()->format('Y-m-d');

        if ($this->esSuperAdmin()) {
            $this->empresasDisponibles = DB::table('empresa')
                ->where('empresa_estado', 1)
                ->orderBy('empresa_razon_social')
                ->get();
        } elseif ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();
            }
        }

        $this->buscar();
    }

    // ── Watchers ──────────────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = [];
        }
        $this->buscar();
    }

    public function updatedSucursalSeleccionada(): void { $this->buscar(); }
    public function updatedDesde(): void                { $this->buscar(); }
    public function updatedHasta(): void                { $this->buscar(); }
    public function updatedFiltroEstado(): void         { $this->buscar(); }
    public function updatedFiltroMotivo(): void         { $this->buscar(); }

    // ── Búsqueda ──────────────────────────────────────────────

    public function buscar(): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $q = DB::table('guias_remision as g')
            ->join('empresa as e', 'e.id_empresa', '=', 'g.id_empresa')
            ->select('g.*', 'e.empresa_razon_social', 'e.empresa_ruc')
            ->whereBetween('g.guia_fecha_emision', [$this->desde, $this->hasta])
            ->orderBy('g.guia_fecha_emision', 'desc')
            ->orderBy('g.id_guia', 'desc');

        if ($idSucursal > 0) {
            $q->where('g.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $q->where('g.id_empresa', $idEmpresa);
        }

        if ($this->filtroEstado !== '') {
            $q->where('g.guia_estado', $this->filtroEstado);
        }
        if ($this->filtroMotivo !== '') {
            $q->where('g.guia_motivo_traslado', $this->filtroMotivo);
        }

        $this->guias = $q->get();
    }

    // ── Modal crear ───────────────────────────────────────────

    public function abrirModalCrear(): void
    {
        $this->resetForm();
        $this->guia_fecha_emision  = now()->format('Y-m-d');
        $this->guia_fecha_traslado = now()->format('Y-m-d');
        $this->items = [
            ['id_pro' => '', 'detalle_descripcion' => '', 'detalle_codigo' => '', 'detalle_cantidad' => '1', 'detalle_unidad_medida' => 'NIU', 'detalle_peso_unitario' => ''],
        ];

        $idEmpresa = $this->resolverIdEmpresa();
        if ($idEmpresa) {
            $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
            if ($empresa) {
                $this->guia_partida_ubigeo    = $empresa->empresa_ubigeo ?? '';
                $this->guia_partida_direccion = $empresa->empresa_direccion ?? '';
                $ubigeo = DB::table('ubigeo')->where('ubigeo_cod', $this->guia_partida_ubigeo)->first();
                $this->partida_ubigeo_texto = $ubigeo
                    ? "{$ubigeo->ubigeo_cod} - {$ubigeo->ubigeo_departamento}/{$ubigeo->ubigeo_provincia}/{$ubigeo->ubigeo_distrito}"
                    : $this->guia_partida_ubigeo;
            }
        }

        $this->guia_correlativo = $this->siguienteCorrelativo();
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->guia_serie              = 'T001';
        $this->guia_correlativo        = '';
        $this->guia_fecha_emision      = '';
        $this->guia_fecha_traslado     = '';
        $this->guia_motivo_traslado    = '01';
        $this->guia_modalidad_traslado = '01';
        $this->guia_observaciones      = '';
        $this->guia_peso_bruto         = '0';
        $this->guia_dest_tipo_doc      = '6';
        $this->guia_dest_numero_doc    = '';
        $this->guia_dest_nombre        = '';
        $this->guia_dest_direccion     = '';
        $this->guia_partida_ubigeo     = '';
        $this->guia_partida_direccion  = '';
        $this->partida_ubigeo_texto    = '';
        $this->guia_llegada_ubigeo     = '';
        $this->guia_llegada_direccion  = '';
        $this->llegada_ubigeo_texto    = '';
        $this->guia_transportista_ruc  = '';
        $this->guia_transportista_nombre = '';
        $this->guia_transportista_mtt  = '';
        $this->guia_vehiculo_placa     = '';
        $this->guia_conductor_tipo_doc = '1';
        $this->guia_conductor_numero_doc = '';
        $this->guia_conductor_nombre   = '';
        $this->guia_conductor_licencia = '';
        $this->items                   = [];
        $this->busquedaUbigeoPartida   = '';
        $this->busquedaUbigeoLlegada   = '';
        $this->sugerenciasPartida      = [];
        $this->sugerenciasLlegada      = [];
    }

    private function siguienteCorrelativo(): string
    {
        $idEmpresa = $this->resolverIdEmpresa();
        if (!$idEmpresa) return '00000001';

        $last = DB::table('guias_remision')
            ->where('id_empresa', $idEmpresa)
            ->where('guia_serie', $this->guia_serie)
            ->max('guia_correlativo');

        $next = $last ? (int) $last + 1 : 1;
        return str_pad($next, 8, '0', STR_PAD_LEFT);
    }

    // ── Detalle de items ──────────────────────────────────────

    public function agregarItem(): void
    {
        $this->items[] = ['id_pro' => '', 'detalle_descripcion' => '', 'detalle_codigo' => '', 'detalle_cantidad' => '1', 'detalle_unidad_medida' => 'NIU', 'detalle_peso_unitario' => ''];
    }

    public function eliminarItem(int $index): void
    {
        if (count($this->items) > 1) {
            array_splice($this->items, $index, 1);
        }
    }

    // ── Búsqueda de ubigeo ────────────────────────────────────

    public function buscarUbigeoPartida(): void
    {
        if (strlen($this->busquedaUbigeoPartida) >= 3) {
            $term = $this->busquedaUbigeoPartida;
            $this->sugerenciasPartida = DB::table('ubigeo')
                ->where(function ($q) use ($term) {
                    $q->where('ubigeo_cod', 'like', $term . '%')
                      ->orWhere('ubigeo_distrito', 'like', '%' . $term . '%')
                      ->orWhere('ubigeo_provincia', 'like', '%' . $term . '%');
                })
                ->limit(10)
                ->get();
        } else {
            $this->sugerenciasPartida = [];
        }
    }

    public function seleccionarUbigeoPartida(string $cod, string $texto): void
    {
        $this->guia_partida_ubigeo   = $cod;
        $this->partida_ubigeo_texto  = $texto;
        $this->busquedaUbigeoPartida = '';
        $this->sugerenciasPartida    = [];
    }

    public function buscarUbigeoLlegada(): void
    {
        if (strlen($this->busquedaUbigeoLlegada) >= 3) {
            $term = $this->busquedaUbigeoLlegada;
            $this->sugerenciasLlegada = DB::table('ubigeo')
                ->where(function ($q) use ($term) {
                    $q->where('ubigeo_cod', 'like', $term . '%')
                      ->orWhere('ubigeo_distrito', 'like', '%' . $term . '%')
                      ->orWhere('ubigeo_provincia', 'like', '%' . $term . '%');
                })
                ->limit(10)
                ->get();
        } else {
            $this->sugerenciasLlegada = [];
        }
    }

    public function seleccionarUbigeoLlegada(string $cod, string $texto): void
    {
        $this->guia_llegada_ubigeo   = $cod;
        $this->llegada_ubigeo_texto  = $texto;
        $this->busquedaUbigeoLlegada = '';
        $this->sugerenciasLlegada    = [];
    }

    // ── Guardar guía ──────────────────────────────────────────

    public function guardarGuia(): void
    {
        if (!auth()->user()->can('guias_remision.crear')) {
            session()->flash('error', 'No tienes permiso para crear guías de remisión.');
            return;
        }

        if (empty($this->guia_serie) || empty($this->guia_correlativo)) {
            session()->flash('error', 'La serie y correlativo son obligatorios.');
            return;
        }
        if (empty($this->guia_fecha_emision) || empty($this->guia_fecha_traslado)) {
            session()->flash('error', 'Las fechas son obligatorias.');
            return;
        }
        if (empty($this->guia_dest_numero_doc) || empty($this->guia_dest_nombre)) {
            session()->flash('error', 'Los datos del destinatario son obligatorios.');
            return;
        }
        if (empty($this->guia_partida_direccion) || empty($this->guia_llegada_direccion)) {
            session()->flash('error', 'Las direcciones de partida y llegada son obligatorias.');
            return;
        }
        if (empty($this->items)) {
            session()->flash('error', 'Debe ingresar al menos un producto.');
            return;
        }

        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if (!$idEmpresa) {
            session()->flash('error', 'No se pudo determinar la empresa activa.');
            return;
        }

        try {
            DB::beginTransaction();

            $idGuia = DB::table('guias_remision')->insertGetId([
                'id_empresa'               => $idEmpresa,
                'id_sucursal'              => $idSucursal ?: null,
                'id_users'                 => auth()->user()->id_users,
                'guia_serie'               => strtoupper($this->guia_serie),
                'guia_correlativo'         => $this->guia_correlativo,
                'guia_fecha_emision'       => $this->guia_fecha_emision,
                'guia_fecha_traslado'      => $this->guia_fecha_traslado,
                'guia_motivo_traslado'     => $this->guia_motivo_traslado,
                'guia_modalidad_traslado'  => $this->guia_modalidad_traslado,
                'guia_observaciones'       => $this->guia_observaciones ?: null,
                'guia_peso_bruto'          => (float) $this->guia_peso_bruto,
                'guia_dest_tipo_doc'       => $this->guia_dest_tipo_doc,
                'guia_dest_numero_doc'     => $this->guia_dest_numero_doc,
                'guia_dest_nombre'         => $this->guia_dest_nombre,
                'guia_dest_direccion'      => $this->guia_dest_direccion ?: null,
                'guia_partida_ubigeo'      => $this->guia_partida_ubigeo ?: null,
                'guia_partida_direccion'   => $this->guia_partida_direccion,
                'guia_llegada_ubigeo'      => $this->guia_llegada_ubigeo ?: null,
                'guia_llegada_direccion'   => $this->guia_llegada_direccion,
                'guia_transportista_ruc'   => $this->guia_modalidad_traslado === '01' ? ($this->guia_transportista_ruc ?: null) : null,
                'guia_transportista_nombre'=> $this->guia_modalidad_traslado === '01' ? ($this->guia_transportista_nombre ?: null) : null,
                'guia_transportista_mtt'   => $this->guia_modalidad_traslado === '01' ? ($this->guia_transportista_mtt ?: null) : null,
                'guia_vehiculo_placa'      => $this->guia_modalidad_traslado === '02' ? ($this->guia_vehiculo_placa ?: null) : null,
                'guia_conductor_tipo_doc'  => $this->guia_modalidad_traslado === '02' ? ($this->guia_conductor_tipo_doc ?: null) : null,
                'guia_conductor_numero_doc'=> $this->guia_modalidad_traslado === '02' ? ($this->guia_conductor_numero_doc ?: null) : null,
                'guia_conductor_nombre'    => $this->guia_modalidad_traslado === '02' ? ($this->guia_conductor_nombre ?: null) : null,
                'guia_conductor_licencia'  => $this->guia_modalidad_traslado === '02' ? ($this->guia_conductor_licencia ?: null) : null,
                'guia_estado_sunat'        => 0,
                'guia_estado'              => 'borrador',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            foreach ($this->items as $item) {
                if (empty($item['detalle_descripcion'])) continue;
                DB::table('guias_remision_detalle')->insert([
                    'id_guia'               => $idGuia,
                    'id_pro'                => $item['id_pro'] ?: null,
                    'detalle_descripcion'   => $item['detalle_descripcion'],
                    'detalle_codigo'        => $item['detalle_codigo'] ?: null,
                    'detalle_cantidad'      => (float) $item['detalle_cantidad'],
                    'detalle_unidad_medida' => $item['detalle_unidad_medida'] ?: 'NIU',
                    'detalle_peso_unitario' => isset($item['detalle_peso_unitario']) && $item['detalle_peso_unitario'] !== '' ? (float) $item['detalle_peso_unitario'] : null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            DB::commit();
            $this->modalAbierto = false;
            $this->resetForm();
            session()->flash('success', 'Guía de remisión creada correctamente.');
            $this->buscar();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la guía: ' . $e->getMessage());
        }
    }

    // ── Envío a SUNAT ─────────────────────────────────────────

    public function confirmarEnviarSunat(int $idGuia): void
    {
        $this->idGuiaConfirmacion  = $idGuia;
        $this->mensajeConfirmacion = '¿Está seguro que desea enviar esta guía de remisión a SUNAT?';
        $this->dispatch('abrirModalConfirmacionGuia');
    }

    public function ejecutarEnviarSunat(): void
    {
        $this->dispatch('cerrarModalConfirmacionGuia');

        if (!$this->idGuiaConfirmacion) return;

        if (!auth()->user()->can('guias_remision.crear')) {
            session()->flash('error', 'No tienes permiso para enviar guías a SUNAT.');
            return;
        }

        $idGuia = $this->idGuiaConfirmacion;
        $this->idGuiaConfirmacion = null;

        try {
            $guia = DB::table('guias_remision')->where('id_guia', $idGuia)->first();
            if (!$guia) {
                session()->flash('error', 'Guía no encontrada.');
                return;
            }

            $emisor = DB::table('empresa')->where('id_empresa', $guia->id_empresa)->first();
            if (!$emisor || empty($emisor->empresa_ruta_certificado)) {
                session()->flash('error', 'La empresa no tiene configurado el certificado digital.');
                return;
            }

            $detalle = DB::table('guias_remision_detalle')->where('id_guia', $idGuia)->get();
            if ($detalle->isEmpty()) {
                session()->flash('error', 'La guía no tiene productos en el detalle.');
                return;
            }

            $ruta = 'ApiFacturacion/xml/';
            if (!is_dir($ruta)) {
                @mkdir($ruta, 0775, true);
            }

            $nombre = "{$emisor->empresa_ruc}-09-{$guia->guia_serie}-{$guia->guia_correlativo}";
            $nom    = rtrim($ruta, '/\\') . DIRECTORY_SEPARATOR . $nombre;

            GeneradorXML::CrearXMLGuiaRemision($nom, $emisor, $guia, $detalle);

            $result = apiFacturacion::EnviarGuiaRemision($emisor, $nombre, 'ApiFacturacion/xml/', 'ApiFacturacion/cdr/', $idGuia);

            $messages = [
                apiFacturacion::ERROR_GENERAL       => 'No se pudo generar o procesar la guía electrónica.',
                apiFacturacion::ERROR_SUNAT_RECHAZO => 'SUNAT rechazó la guía. Revise el detalle del rechazo.',
                apiFacturacion::ERROR_COMUNICACION  => 'No se pudo conectar con SUNAT. Intente nuevamente.',
                apiFacturacion::ERROR_BD            => 'No se pudo guardar la información en la base de datos.',
            ];

            if ($result !== apiFacturacion::OK) {
                session()->flash('error', $messages[$result] ?? 'Error desconocido al enviar guía.');
            } else {
                session()->flash('success', '¡Guía de remisión enviada a SUNAT correctamente!');
            }

            $this->buscar();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error inesperado al enviar la guía.');
        }
    }

    // ── Ver detalle ────────────────────────────────────────────

    public function verDetalle(int $idGuia): void
    {
        $this->guiaDetalle = DB::table('guias_remision as g')
            ->join('empresa as e', 'e.id_empresa', '=', 'g.id_empresa')
            ->select('g.*', 'e.empresa_razon_social', 'e.empresa_ruc')
            ->where('g.id_guia', $idGuia)
            ->first();

        $this->itemsDetalle = DB::table('guias_remision_detalle as d')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->select('d.*', 'p.pro_nombre')
            ->where('d.id_guia', $idGuia)
            ->get();

        $this->modalDetalleAbierto = true;
    }

    public function cerrarDetalle(): void
    {
        $this->modalDetalleAbierto = false;
        $this->guiaDetalle = null;
        $this->itemsDetalle = [];
    }

    public function render()
    {
        return view('livewire.facturacion.guias-remision');
    }
}
