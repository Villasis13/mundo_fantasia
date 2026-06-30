<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class DistribucionFlete extends Component
{
    private Logs $logs;

    // Slots dinámicos de transportistas
    public array $transportistaSlots = [
        ['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => '', 'guia' => '', 'flete' => ''],
    ];

    // Modal selección/creación de transportista
    public int    $slotActivo          = 0;
    public string $buscarTransportista = '';
    public string $tabTransportista    = 'seleccionar';
    // Formulario nuevo transportista
    public string $ntRuc            = '';
    public string $ntNombre         = '';
    public string $ntChofer         = '';
    public string $ntVehiculo       = '';
    public string $ntPlaca          = '';
    public string $ntDireccion      = '';
    public string $ntTelefono       = '';
    public string $ntRucMensaje     = '';
    public string $ntRucMensajeTipo = '';

    // Modal búsqueda de comprobante
    public string $buscarCorrelativo  = '';
    public array  $resultadosBusqueda = [];

    // Comprobantes cargados
    public array  $ordenesIds    = [];
    public array  $detallesOrden = [];
    public bool   $calculado     = false;

    // Feedback guardado
    public string $mensajeGuardado     = '';
    public string $mensajeGuardadoTipo = '';

    // Registro cargado para edición
    public int    $idDistribucionActual = 0;
    public string $buscarRegistro       = '';

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    // ── Slots dinámicos ───────────────────────────────────────
    public function agregarSlot(): void
    {
        $this->transportistaSlots[] = [
            'id' => 0, 'nombre' => '', 'ruc' => '',
            'fact' => '', 'fecha' => '', 'guia' => '', 'flete' => '',
        ];
        $this->calculado = false;
    }

    public function quitarSlot(int $index): void
    {
        if (count($this->transportistaSlots) <= 1) return;
        $slots = $this->transportistaSlots;
        array_splice($slots, $index, 1);
        $this->transportistaSlots = array_values($slots);
        $this->calculado = false;
    }

    // ── Modal transportista ───────────────────────────────────
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
        // No permitir el mismo transportista en otro slot
        foreach ($this->transportistaSlots as $i => $slot) {
            if ($i !== $this->slotActivo && ($slot['id'] ?? 0) === $id) return;
        }

        $t = DB::table('transportistas')->where('id_transportista', $id)->first();
        if (!$t) return;

        $slots = $this->transportistaSlots;
        $slots[$this->slotActivo]['id']     = $id;
        $slots[$this->slotActivo]['nombre'] = $t->transportista_nombre;
        $slots[$this->slotActivo]['ruc']    = $t->transportista_ruc ?? '';
        $this->transportistaSlots = $slots;

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
        $this->ntRuc = $this->ntNombre = $this->ntChofer  = '';
        $this->ntVehiculo = $this->ntPlaca = $this->ntDireccion = $this->ntTelefono = '';
        $this->ntRucMensaje = $this->ntRucMensajeTipo = '';
    }

    // ── Búsqueda de comprobante ───────────────────────────────
    public function buscarFactura(): void
    {
        $term = trim($this->buscarCorrelativo);
        if ($term === '') { $this->resultadosBusqueda = []; return; }

        $this->resultadosBusqueda = DB::table('orden_compra as oc')
            ->where('oc.orden_compra_estado', '!=', 'anulado')
            ->where(function ($q) use ($term) {
                $q->where('oc.orden_compra_numero_doc', 'like', "%{$term}%")
                  ->orWhere('oc.orden_compra_nom_prove', 'like', "%{$term}%");
            })
            ->select(
                'oc.id_orden_compra',
                'oc.orden_compra_tipo_doc',
                'oc.orden_compra_numero_doc',
                'oc.orden_compra_nom_prove',
                'oc.orden_compra_total',
                'oc.orden_compra_fecha_emision_doc',
                'oc.orden_compra_estado'
            )
            ->orderByDesc('oc.id_orden_compra')
            ->limit(20)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function seleccionarOrden(int $id): void
    {
        if (in_array($id, $this->ordenesIds)) return;

        $orden = DB::table('orden_compra')->where('id_orden_compra', $id)->first();
        if (!$orden) return;

        $partes      = explode('-', $orden->orden_compra_numero_doc ?? '', 2);
        $serie       = $partes[0] ?? '';
        $correlativo = $partes[1] ?? '';

        $nuevos = DB::table('orden_compra_detalle as ocd')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('ocd.id_orden_compra', $id)
            ->select(
                'ocd.id_detalle_compra',
                'p.pro_codigo',
                'ocd.detalle_orden_nombre_producto',
                'ocd.detalle_compra_precio_compra',
                'm.medida_codigo_unidad as unidad',
                'ocd.detalle_compra_cantidad',
                'ocd.detalle_compra_total_pedido'
            )
            ->get()
            ->map(fn($r) => array_merge((array) $r, [
                '_id_orden'    => $id,
                '_tipo'        => strtoupper($orden->orden_compra_tipo_doc ?? ''),
                '_serie'       => $serie,
                '_correlativo' => $correlativo,
                '_proveedor'   => $orden->orden_compra_nom_prove ?? '',
                '_total'       => $orden->orden_compra_total ?? 0,
                '_fecha'       => $orden->orden_compra_fecha_emision_doc ?? '',
            ]))
            ->toArray();

        $this->ordenesIds    = [...$this->ordenesIds, $id];
        $this->detallesOrden = [...$this->detallesOrden, ...$nuevos];

        // Auto-poblar slots con transportistas registrados en esta orden
        $transOrden = DB::table('orden_compra_transportistas')
            ->where('id_orden_compra', $id)
            ->orderBy('oc_trans_orden')
            ->get();

        foreach ($transOrden as $trans) {
            $nombre = trim($trans->oc_trans_nombre ?? '');
            $ruc    = trim($trans->oc_trans_ruc    ?? '');

            // Saltar si ya existe un slot con el mismo RUC o el mismo nombre
            $esDuplicado = false;
            foreach ($this->transportistaSlots as $slot) {
                if ($ruc && ($slot['ruc'] ?? '') === $ruc) { $esDuplicado = true; break; }
                if (!$ruc && $nombre && mb_strtolower($slot['nombre'] ?? '') === mb_strtolower($nombre)) {
                    $esDuplicado = true; break;
                }
            }
            if ($esDuplicado || (!$nombre && !$ruc)) continue;

            // Intentar resolver el id en la tabla maestra de transportistas
            $tMaster = DB::table('transportistas')
                ->where('transportista_estado', 1)
                ->when($ruc, fn($q) => $q->where('transportista_ruc', $ruc))
                ->when(!$ruc, fn($q) => $q->where('transportista_nombre', $nombre))
                ->first();

            $nuevoSlot = [
                'id'     => $tMaster ? $tMaster->id_transportista : 0,
                'nombre' => $tMaster ? $tMaster->transportista_nombre : $nombre,
                'ruc'    => $tMaster ? ($tMaster->transportista_ruc ?? '') : $ruc,
                'fact'   => trim($trans->oc_trans_fact  ?? ''),
                'fecha'  => trim($trans->oc_trans_fecha ?? ''),
                'guia'   => '',
                'flete'  => '',
            ];

            // Llenar el primer slot vacío; si no hay, agregar uno nuevo
            $slots  = $this->transportistaSlots;
            $filled = false;
            foreach ($slots as $i => $slot) {
                if (($slot['id'] ?? 0) === 0 && trim($slot['nombre'] ?? '') === '') {
                    $slots[$i] = $nuevoSlot;
                    $filled    = true;
                    break;
                }
            }
            if (!$filled) {
                $slots[] = $nuevoSlot;
            }
            $this->transportistaSlots = $slots;
        }

        $this->calculado = false;
    }

    public function quitarOrden(int $id): void
    {
        $this->detallesOrden = array_values(
            array_filter($this->detallesOrden, fn($d) => ($d['_id_orden'] ?? null) !== $id)
        );
        $this->ordenesIds = array_values(array_filter($this->ordenesIds, fn($oid) => $oid !== $id));
        $this->calculado  = false;
    }

    // ── Registros guardados ───────────────────────────────────
    public function abrirModalRegistros(): void
    {
        $this->buscarRegistro = '';
        $this->dispatch('abrirModalRegistros');
    }

    public function cargarDistribucion(int $id): void
    {
        $dist = DB::table('distribucion_fletes')->where('id_distribucion_flete', $id)->first();
        if (!$dist) return;

        // Slots de transportistas
        $transRows = DB::table('distribucion_flete_transportistas')
            ->where('id_distribucion_flete', $id)
            ->orderBy('dist_orden')
            ->get();

        $slots = [];
        foreach ($transRows as $t) {
            $tMaster = null;
            if ($t->dist_transportista_ruc) {
                $tMaster = DB::table('transportistas')
                    ->where('transportista_ruc', $t->dist_transportista_ruc)
                    ->where('transportista_estado', 1)->first();
            }
            if (!$tMaster && $t->dist_transportista_nombre) {
                $tMaster = DB::table('transportistas')
                    ->where('transportista_nombre', $t->dist_transportista_nombre)
                    ->where('transportista_estado', 1)->first();
            }
            $slots[] = [
                'id'     => $tMaster ? $tMaster->id_transportista : 0,
                'nombre' => $t->dist_transportista_nombre ?? '',
                'ruc'    => $t->dist_transportista_ruc ?? '',
                'fact'   => $t->dist_fact ?? '',
                'fecha'  => $t->dist_fecha ?? '',
                'guia'   => $t->dist_guia ?? '',
                'flete'  => (string) ($t->dist_flete ?? ''),
            ];
        }
        $this->transportistaSlots = !empty($slots) ? $slots : [
            ['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => '', 'guia' => '', 'flete' => ''],
        ];

        // Detalles
        $detRows = DB::table('distribucion_flete_detalles')
            ->where('id_distribucion_flete', $id)
            ->get();

        $detallesOrden = [];
        $ordenesIds    = [];

        foreach ($detRows as $det) {
            $numDoc  = trim($det->dist_serie ?? '') . '-' . trim($det->dist_correlativo ?? '');
            $orden   = DB::table('orden_compra')
                ->where('orden_compra_numero_doc', $numDoc)
                ->first();
            $idOrden = $orden ? $orden->id_orden_compra : (-1 * $det->id_dist_detalle);

            if (!in_array($idOrden, $ordenesIds)) {
                $ordenesIds[] = $idOrden;
            }

            $detallesOrden[] = [
                'id_detalle_compra'             => 'saved_' . $det->id_dist_detalle,
                'pro_codigo'                    => $det->dist_pro_codigo ?? '',
                'detalle_orden_nombre_producto' => $det->dist_producto_nombre ?? '',
                'detalle_compra_precio_compra'  => (float) $det->dist_costo_inicial,
                'unidad'                        => $det->dist_unidad ?? '',
                'detalle_compra_cantidad'       => (float) $det->dist_cantidad,
                'detalle_compra_total_pedido'   => round((float) $det->dist_costo_inicial * (float) $det->dist_cantidad, 4),
                '_id_orden'                     => $idOrden,
                '_tipo'                         => $det->dist_tipo ?? '',
                '_serie'                        => $det->dist_serie ?? '',
                '_correlativo'                  => $det->dist_correlativo ?? '',
                '_proveedor'                    => $det->dist_proveedor ?? '',
                '_total'                        => (float) $det->dist_total_comprobante,
                '_fecha'                        => $det->dist_fecha_emision ?? '',
            ];
        }

        $this->detallesOrden        = $detallesOrden;
        $this->ordenesIds           = $ordenesIds;
        $this->idDistribucionActual = $id;
        $this->calculado            = true;
        $this->mensajeGuardado      = '';
        $this->mensajeGuardadoTipo  = '';

        $this->dispatch('cerrarModalRegistros');
    }

    public function calcular(): void
    {
        $this->calculado          = true;
        $this->mensajeGuardado    = '';
        $this->mensajeGuardadoTipo = '';
    }

    public function guardar(): void
    {
        // Validar cada slot
        foreach ($this->transportistaSlots as $idx => $slot) {
            $n = $idx + 1;
            if (empty(trim($slot['fact'] ?? ''))) {
                $this->mensajeGuardado     = "Transportista {$n}: el N° de factura es obligatorio.";
                $this->mensajeGuardadoTipo = 'error';
                return;
            }
            if (empty(trim($slot['fecha'] ?? ''))) {
                $this->mensajeGuardado     = "Transportista {$n}: la fecha es obligatoria.";
                $this->mensajeGuardadoTipo = 'error';
                return;
            }
            if (empty(trim($slot['flete'] ?? '')) || (float) $slot['flete'] <= 0) {
                $this->mensajeGuardado     = "Transportista {$n}: el flete debe ser mayor a 0.";
                $this->mensajeGuardadoTipo = 'error';
                return;
            }
        }

        $totalFlete = round(
            collect($this->transportistaSlots)->sum(
                fn($s) => max(0, (float) str_replace(',', '.', $s['flete'] ?? ''))
            ),
            2
        );

        $subtotalOrden = collect($this->detallesOrden)->sum(function ($det) {
            $total = (float) ($det['detalle_compra_total_pedido'] ?? 0);
            if ($total == 0) {
                $total = (float)($det['detalle_compra_precio_compra'] ?? 0)
                       * (float)($det['detalle_compra_cantidad'] ?? 0);
            }
            return $total;
        });

        $esActualizacion = $this->idDistribucionActual > 0;

        try {
            DB::transaction(function () use ($totalFlete, $subtotalOrden, $esActualizacion) {
                if ($esActualizacion) {
                    DB::table('distribucion_fletes')
                        ->where('id_distribucion_flete', $this->idDistribucionActual)
                        ->update(['distribucion_flete_total' => $totalFlete, 'updated_at' => now()]);
                    DB::table('distribucion_flete_transportistas')
                        ->where('id_distribucion_flete', $this->idDistribucionActual)->delete();
                    DB::table('distribucion_flete_detalles')
                        ->where('id_distribucion_flete', $this->idDistribucionActual)->delete();
                    $id = $this->idDistribucionActual;
                } else {
                    $id = DB::table('distribucion_fletes')->insertGetId([
                        'distribucion_flete_total'  => $totalFlete,
                        'distribucion_flete_estado' => 1,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    ]);
                }

                foreach ($this->transportistaSlots as $idx => $slot) {
                    DB::table('distribucion_flete_transportistas')->insert([
                        'id_distribucion_flete'     => $id,
                        'dist_transportista_nombre' => $slot['nombre'] ?: null,
                        'dist_transportista_ruc'    => $slot['ruc'] ?: null,
                        'dist_fact'                 => trim($slot['fact']),
                        'dist_fecha'                => $slot['fecha'] ?: null,
                        'dist_guia'                 => trim($slot['guia'] ?? '') ?: null,
                        'dist_flete'                => (float) str_replace(',', '.', $slot['flete']),
                        'dist_orden'                => $idx,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    ]);
                }

                foreach ($this->detallesOrden as $det) {
                    $costoIni  = (float) ($det['detalle_compra_precio_compra'] ?? 0);
                    $totalItem = (float) ($det['detalle_compra_total_pedido'] ?? 0);
                    if ($totalItem == 0) {
                        $totalItem = $costoIni * (float)($det['detalle_compra_cantidad'] ?? 0);
                    }
                    $cantidad   = max(1, (float) ($det['detalle_compra_cantidad'] ?? 1));
                    $fleteTotal = $subtotalOrden > 0
                        ? round($totalFlete * $totalItem / $subtotalOrden, 2) : 0;
                    $fleteUni   = round($fleteTotal / $cantidad, 2);
                    $costoFinal = round($costoIni + $fleteUni, 4);

                    DB::table('distribucion_flete_detalles')->insert([
                        'id_distribucion_flete'  => $id,
                        'dist_tipo'              => $det['_tipo'] ?? '',
                        'dist_serie'             => $det['_serie'] ?? '',
                        'dist_correlativo'       => $det['_correlativo'] ?? '',
                        'dist_proveedor'         => $det['_proveedor'] ?? '',
                        'dist_total_comprobante' => $det['_total'] ?? 0,
                        'dist_fecha_emision'     => !empty($det['_fecha']) ? $det['_fecha'] : null,
                        'dist_pro_codigo'        => $det['pro_codigo'] ?? null,
                        'dist_producto_nombre'   => $det['detalle_orden_nombre_producto'] ?? '',
                        'dist_costo_inicial'     => $costoIni,
                        'dist_unidad'            => $det['unidad'] ?? null,
                        'dist_cantidad'          => $cantidad,
                        'dist_flete_total'       => $fleteTotal,
                        'dist_flete_uni'         => $fleteUni,
                        'dist_costo_final'       => $costoFinal,
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);
                }
            });

            // Limpiar formulario tras guardar/actualizar exitoso
            $this->transportistaSlots = [
                ['id' => 0, 'nombre' => '', 'ruc' => '', 'fact' => '', 'fecha' => '', 'guia' => '', 'flete' => ''],
            ];
            $this->ordenesIds           = [];
            $this->detallesOrden        = [];
            $this->calculado            = false;
            $this->idDistribucionActual = 0;
            $this->mensajeGuardado      = $esActualizacion ? 'Distribución actualizada correctamente.' : 'Distribución guardada correctamente.';
            $this->mensajeGuardadoTipo  = 'success';

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $this->mensajeGuardado     = 'Error al guardar. Intente nuevamente.';
            $this->mensajeGuardadoTipo = 'error';
        }
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $totalFlete = round(
            collect($this->transportistaSlots)->sum(
                fn($s) => max(0, (float) str_replace(',', '.', $s['flete'] ?? ''))
            ),
            2
        );

        $subtotalOrden = collect($this->detallesOrden)->sum(function ($det) {
            $total = (float) ($det['detalle_compra_total_pedido'] ?? 0);
            if ($total == 0) {
                $total = (float)($det['detalle_compra_precio_compra'] ?? 0)
                       * (float)($det['detalle_compra_cantidad'] ?? 0);
            }
            return $total;
        });

        $detalles = collect($this->detallesOrden)->map(function ($det) use ($totalFlete, $subtotalOrden) {
            $det      = (array) $det;
            $costoIni = (float) ($det['detalle_compra_precio_compra'] ?? 0);

            if (!$this->calculado) {
                $det['flete_total'] = 0;
                $det['flete_uni']   = 0;
                $det['costo_final'] = $costoIni;
                return $det;
            }

            $totalItem = (float) ($det['detalle_compra_total_pedido'] ?? 0);
            if ($totalItem == 0) {
                $totalItem = $costoIni * (float)($det['detalle_compra_cantidad'] ?? 0);
            }

            $cantidad   = max(1, (float) ($det['detalle_compra_cantidad'] ?? 1));
            $fleteTotal = $subtotalOrden > 0
                ? round($totalFlete * $totalItem / $subtotalOrden, 2)
                : 0;
            $fleteUni   = round($fleteTotal / $cantidad, 2);
            $costoFinal = round($costoIni + $fleteUni, 4);

            $det['flete_total'] = $fleteTotal;
            $det['flete_uni']   = $fleteUni;
            $det['costo_final'] = $costoFinal;
            return $det;
        })->toArray();

        $transportistas = DB::table('transportistas')
            ->where('transportista_estado', 1)
            ->when($this->buscarTransportista, fn($q) =>
                $q->where('transportista_nombre', 'like', "%{$this->buscarTransportista}%")
                  ->orWhere('transportista_ruc', 'like', "%{$this->buscarTransportista}%")
            )
            ->orderBy('transportista_nombre')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $buscarRegistro          = $this->buscarRegistro;
        $registrosDistribucion   = DB::table('distribucion_fletes as df')
            ->when($buscarRegistro, function ($q) use ($buscarRegistro) {
                $q->whereExists(function ($sub) use ($buscarRegistro) {
                    $sub->from('distribucion_flete_transportistas')
                        ->whereColumn('id_distribucion_flete', 'df.id_distribucion_flete')
                        ->where(function ($q2) use ($buscarRegistro) {
                            $q2->where('dist_transportista_nombre', 'like', "%{$buscarRegistro}%")
                               ->orWhere('dist_fact', 'like', "%{$buscarRegistro}%");
                        });
                });
            })
            ->orderByDesc('df.id_distribucion_flete')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                $trans = DB::table('distribucion_flete_transportistas')
                    ->where('id_distribucion_flete', $r->id_distribucion_flete)
                    ->orderBy('dist_orden')
                    ->get(['dist_transportista_nombre', 'dist_fact'])
                    ->map(fn($t) => ['nombre' => $t->dist_transportista_nombre, 'fact' => $t->dist_fact])
                    ->toArray();
                return [
                    'id_distribucion_flete'    => $r->id_distribucion_flete,
                    'distribucion_flete_total' => $r->distribucion_flete_total,
                    'created_at'               => $r->created_at,
                    'transportistas'           => $trans,
                ];
            })
            ->toArray();

        return view('livewire.logistica.distribucion-flete', compact(
            'totalFlete', 'detalles', 'transportistas', 'registrosDistribucion'
        ));
    }
}
