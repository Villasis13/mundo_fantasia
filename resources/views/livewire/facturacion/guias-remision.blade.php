<div>
    {{-- ══════════════════════════════════════════════════════════════
         ALERTAS
    ══════════════════════════════════════════════════════════════ --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         FILTROS + BOTÓN CREAR
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">

                {{-- Empresa (solo superadmin) --}}
                @if (count($empresasDisponibles) > 0)
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-semibold">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">— Todas —</option>
                        @foreach ($empresasDisponibles as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Sucursal --}}
                @if (count($sucursalesDisponibles) > 0)
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-semibold">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">— Todas —</option>
                        @foreach ($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Desde / Hasta --}}
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-semibold">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="desde">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-semibold">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="hasta">
                </div>

                {{-- Motivo --}}
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-semibold">Motivo</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroMotivo">
                        <option value="">— Todos —</option>
                        <option value="01">01 - Venta</option>
                        <option value="02">02 - Compra</option>
                        <option value="04">04 - Traslado entre establecimientos</option>
                        <option value="08">08 - Importación</option>
                        <option value="09">09 - Exportación</option>
                        <option value="99">99 - Otros</option>
                    </select>
                </div>

                {{-- Estado --}}
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-semibold">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">— Todos —</option>
                        <option value="borrador">Borrador</option>
                        <option value="enviado">Enviado</option>
                        <option value="aceptado">Aceptado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </div>

                {{-- Botón crear --}}
                @can('guias_remision.crear')
                <div class="col-md-auto ms-auto">
                    <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                        <i class="fas fa-plus me-1"></i> Nueva Guía
                    </button>
                </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABLA DE GUÍAS
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Empresa</th>
                            <th>Número</th>
                            <th>F. Emisión</th>
                            <th>F. Traslado</th>
                            <th>Motivo</th>
                            <th>Modalidad</th>
                            <th>Destinatario</th>
                            <th>Peso (KG)</th>
                            <th>Estado</th>
                            <th>SUNAT</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($guias as $guia)
                            @php
                                $rowClass = match($guia->guia_estado) {
                                    'aceptado'  => 'table-success',
                                    'rechazado' => 'table-danger',
                                    'anulado'   => 'table-warning',
                                    default     => '',
                                };
                                $motivoLabel = match($guia->guia_motivo_traslado) {
                                    '01' => 'Venta',
                                    '02' => 'Compra',
                                    '04' => 'Traslado',
                                    '08' => 'Importación',
                                    '09' => 'Exportación',
                                    '99' => 'Otros',
                                    default => $guia->guia_motivo_traslado,
                                };
                                $modalidadLabel = $guia->guia_modalidad_traslado === '01' ? 'Público' : 'Privado';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $guia->id_guia }}</td>
                                <td class="small">{{ $guia->empresa_razon_social }}</td>
                                <td class="fw-semibold">{{ $guia->guia_serie }}-{{ $guia->guia_correlativo }}</td>
                                <td>{{ $guia->guia_fecha_emision }}</td>
                                <td>{{ $guia->guia_fecha_traslado }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $guia->guia_motivo_traslado }}</span>
                                    <small>{{ $motivoLabel }}</small>
                                </td>
                                <td><small>{{ $modalidadLabel }}</small></td>
                                <td class="small">
                                    {{ $guia->guia_dest_nombre }}
                                    <br><span class="text-muted">{{ $guia->guia_dest_numero_doc }}</span>
                                </td>
                                <td class="text-end">{{ number_format($guia->guia_peso_bruto, 3) }}</td>
                                <td>
                                    @php
                                        $estadoBadge = match($guia->guia_estado) {
                                            'borrador'  => 'secondary',
                                            'enviado'   => 'primary',
                                            'aceptado'  => 'success',
                                            'rechazado' => 'danger',
                                            'anulado'   => 'warning',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $estadoBadge }}">{{ ucfirst($guia->guia_estado) }}</span>
                                </td>
                                <td class="small">
                                    @if ($guia->guia_respuesta_sunat)
                                        <span title="{{ $guia->guia_respuesta_sunat }}">
                                            {{ Str::limit($guia->guia_respuesta_sunat, 40) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <button class="btn btn-outline-info btn-sm py-0 px-1" wire:click="verDetalle({{ $guia->id_guia }})" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if ($guia->guia_estado === 'borrador' || $guia->guia_estado === 'rechazado')
                                        @can('guias_remision.crear')
                                        <button class="btn btn-outline-success btn-sm py-0 px-1 ms-1"
                                                wire:click="confirmarEnviarSunat({{ $guia->id_guia }})"
                                                title="Enviar a SUNAT">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">No se encontraron guías de remisión en el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer py-1 small text-muted">
            Total: {{ count($guias) }} guía(s)
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL CREAR GUÍA
    ══════════════════════════════════════════════════════════════ --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title mb-0"><i class="fas fa-truck me-2"></i>Nueva Guía de Remisión</h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body">

                    {{-- ── Sección 1: Datos generales ───────────────── --}}
                    <h6 class="text-primary border-bottom pb-1 mb-3"><i class="fas fa-file-alt me-1"></i> Datos Generales</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Serie *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_serie" placeholder="T001" maxlength="10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Correlativo *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_correlativo" maxlength="10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">F. Emisión *</label>
                            <input type="date" class="form-control form-control-sm" wire:model="guia_fecha_emision">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">F. Traslado *</label>
                            <input type="date" class="form-control form-control-sm" wire:model="guia_fecha_traslado">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Motivo *</label>
                            <select class="form-select form-select-sm" wire:model="guia_motivo_traslado">
                                <option value="01">01 - Venta</option>
                                <option value="02">02 - Compra</option>
                                <option value="04">04 - Traslado establec.</option>
                                <option value="08">08 - Importación</option>
                                <option value="09">09 - Exportación</option>
                                <option value="99">99 - Otros</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Modalidad *</label>
                            <select class="form-select form-select-sm" wire:model.live="guia_modalidad_traslado">
                                <option value="01">01 - Transporte público</option>
                                <option value="02">02 - Transporte privado</option>
                            </select>
                        </div>
                        <div class="col-md-10">
                            <label class="form-label small fw-semibold">Observaciones</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_observaciones" maxlength="500">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Peso bruto (KG) *</label>
                            <input type="number" class="form-control form-control-sm" wire:model="guia_peso_bruto" step="0.001" min="0">
                        </div>
                    </div>

                    {{-- ── Sección 2: Destinatario ──────────────────── --}}
                    <h6 class="text-primary border-bottom pb-1 mb-3"><i class="fas fa-user me-1"></i> Destinatario</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tipo Doc. *</label>
                            <select class="form-select form-select-sm" wire:model="guia_dest_tipo_doc">
                                <option value="6">RUC</option>
                                <option value="1">DNI</option>
                                <option value="4">CE</option>
                                <option value="7">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Número Doc. *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_dest_numero_doc" maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nombre / Razón Social *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_dest_nombre" maxlength="200">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Dirección</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_dest_direccion" maxlength="300">
                        </div>
                    </div>

                    {{-- ── Sección 3: Partida / Llegada ─────────────── --}}
                    <h6 class="text-primary border-bottom pb-1 mb-3"><i class="fas fa-map-marker-alt me-1"></i> Puntos de Traslado</h6>
                    <div class="row g-3 mb-3">
                        {{-- Partida --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ubigeo Partida</label>
                            <div class="input-group input-group-sm mb-1">
                                <input type="text" class="form-control" placeholder="Buscar por código o distrito..."
                                       wire:model.live.debounce.400ms="busquedaUbigeoPartida"
                                       wire:keyup="buscarUbigeoPartida">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            @if (count($sugerenciasPartida) > 0)
                                <div class="list-group" style="max-height:160px;overflow-y:auto;position:relative;z-index:1000;">
                                    @foreach ($sugerenciasPartida as $ub)
                                        <button type="button" class="list-group-item list-group-item-action py-1 small"
                                                wire:click="seleccionarUbigeoPartida('{{ $ub->ubigeo_cod }}', '{{ $ub->ubigeo_cod }} - {{ $ub->ubigeo_departamento }}/{{ $ub->ubigeo_provincia }}/{{ $ub->ubigeo_distrito }}')">
                                            <strong>{{ $ub->ubigeo_cod }}</strong> — {{ $ub->ubigeo_departamento }} / {{ $ub->ubigeo_provincia }} / {{ $ub->ubigeo_distrito }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($guia_partida_ubigeo)
                                <small class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $partida_ubigeo_texto }}</small>
                            @endif
                            <label class="form-label small fw-semibold mt-2">Dirección Partida *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_partida_direccion" maxlength="300">
                        </div>

                        {{-- Llegada --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ubigeo Llegada</label>
                            <div class="input-group input-group-sm mb-1">
                                <input type="text" class="form-control" placeholder="Buscar por código o distrito..."
                                       wire:model.live.debounce.400ms="busquedaUbigeoLlegada"
                                       wire:keyup="buscarUbigeoLlegada">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            @if (count($sugerenciasLlegada) > 0)
                                <div class="list-group" style="max-height:160px;overflow-y:auto;position:relative;z-index:1000;">
                                    @foreach ($sugerenciasLlegada as $ub)
                                        <button type="button" class="list-group-item list-group-item-action py-1 small"
                                                wire:click="seleccionarUbigeoLlegada('{{ $ub->ubigeo_cod }}', '{{ $ub->ubigeo_cod }} - {{ $ub->ubigeo_departamento }}/{{ $ub->ubigeo_provincia }}/{{ $ub->ubigeo_distrito }}')">
                                            <strong>{{ $ub->ubigeo_cod }}</strong> — {{ $ub->ubigeo_departamento }} / {{ $ub->ubigeo_provincia }} / {{ $ub->ubigeo_distrito }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($guia_llegada_ubigeo)
                                <small class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $llegada_ubigeo_texto }}</small>
                            @endif
                            <label class="form-label small fw-semibold mt-2">Dirección Llegada *</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_llegada_direccion" maxlength="300">
                        </div>
                    </div>

                    {{-- ── Sección 4: Transporte ────────────────────── --}}
                    <h6 class="text-primary border-bottom pb-1 mb-3"><i class="fas fa-truck me-1"></i> Transporte
                        <small class="text-muted fw-normal">({{ $guia_modalidad_traslado === '01' ? 'Público — datos del transportista' : 'Privado — vehículo y conductor' }})</small>
                    </h6>

                    @if ($guia_modalidad_traslado === '01')
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">RUC Transportista</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_transportista_ruc" maxlength="15">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nombre Transportista</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_transportista_nombre" maxlength="200">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">N° MTC</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_transportista_mtt" maxlength="20">
                        </div>
                    </div>
                    @else
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Placa Vehículo</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_vehiculo_placa" maxlength="10" placeholder="ABC-123">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tipo Doc. Conductor</label>
                            <select class="form-select form-select-sm" wire:model="guia_conductor_tipo_doc">
                                <option value="1">DNI</option>
                                <option value="4">CE</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">N° Doc. Conductor</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_conductor_numero_doc" maxlength="15">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nombre Conductor</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_conductor_nombre" maxlength="200">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">N° Licencia</label>
                            <input type="text" class="form-control form-control-sm" wire:model="guia_conductor_licencia" maxlength="20">
                        </div>
                    </div>
                    @endif

                    {{-- ── Sección 5: Detalle de productos ─────────── --}}
                    <h6 class="text-primary border-bottom pb-1 mb-2"><i class="fas fa-boxes me-1"></i> Detalle de Productos</h6>
                    <div class="table-responsive mb-2">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Descripción *</th>
                                    <th style="width:100px;">Código</th>
                                    <th style="width:90px;">Cantidad *</th>
                                    <th style="width:90px;">Unidad</th>
                                    <th style="width:100px;">Peso unit. (KG)</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $idx => $item)
                                <tr>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                               wire:model="items.{{ $idx }}.detalle_descripcion"
                                               placeholder="Descripción del producto">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                               wire:model="items.{{ $idx }}.detalle_codigo"
                                               placeholder="Cód.">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                               wire:model="items.{{ $idx }}.detalle_cantidad"
                                               step="0.001" min="0.001">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="items.{{ $idx }}.detalle_unidad_medida">
                                            <option value="NIU">NIU</option>
                                            <option value="KGM">KGM</option>
                                            <option value="ZZ">ZZ</option>
                                            <option value="GLL">GLL</option>
                                            <option value="MTR">MTR</option>
                                            <option value="LTR">LTR</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                               wire:model="items.{{ $idx }}.detalle_peso_unitario"
                                               step="0.001" min="0" placeholder="Opcional">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1"
                                                wire:click="eliminarItem({{ $idx }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="agregarItem">
                        <i class="fas fa-plus me-1"></i> Agregar producto
                    </button>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="guardarGuia">
                        <i class="fas fa-save me-1"></i> Guardar Guía
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         MODAL VER DETALLE
    ══════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalleAbierto && $guiaDetalle)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Guía {{ $guiaDetalle->guia_serie }}-{{ $guiaDetalle->guia_correlativo }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="cerrarDetalle"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Empresa</small>
                            <p class="mb-1 fw-semibold">{{ $guiaDetalle->empresa_razon_social }}</p>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">F. Emisión</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_fecha_emision }}</p>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">F. Traslado</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_fecha_traslado }}</p>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Destinatario</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_dest_nombre }}<br>
                            <span class="text-muted small">{{ $guiaDetalle->guia_dest_numero_doc }}</span></p>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Punto de Partida</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_partida_ubigeo }} — {{ $guiaDetalle->guia_partida_direccion }}</p>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Punto de Llegada</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_llegada_ubigeo }} — {{ $guiaDetalle->guia_llegada_direccion }}</p>
                        </div>
                        @if ($guiaDetalle->guia_modalidad_traslado === '01')
                        <div class="col-md-6">
                            <small class="text-muted">Transportista</small>
                            <p class="mb-1">{{ $guiaDetalle->guia_transportista_nombre }}
                            @if ($guiaDetalle->guia_transportista_ruc) ({{ $guiaDetalle->guia_transportista_ruc }}) @endif
                            @if ($guiaDetalle->guia_transportista_mtt) — MTC: {{ $guiaDetalle->guia_transportista_mtt }} @endif
                            </p>
                        </div>
                        @else
                        <div class="col-md-6">
                            <small class="text-muted">Vehículo / Conductor</small>
                            <p class="mb-1">Placa: {{ $guiaDetalle->guia_vehiculo_placa }} — {{ $guiaDetalle->guia_conductor_nombre }}
                            @if ($guiaDetalle->guia_conductor_numero_doc) ({{ $guiaDetalle->guia_conductor_numero_doc }}) @endif
                            </p>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <small class="text-muted">Peso Bruto</small>
                            <p class="mb-1">{{ number_format($guiaDetalle->guia_peso_bruto, 3) }} KG</p>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Estado</small>
                            <p class="mb-1">
                                @php $estadoBadge = match($guiaDetalle->guia_estado) { 'aceptado'=>'success','rechazado'=>'danger','anulado'=>'warning','enviado'=>'primary',default=>'secondary' }; @endphp
                                <span class="badge bg-{{ $estadoBadge }}">{{ ucfirst($guiaDetalle->guia_estado) }}</span>
                            </p>
                        </div>
                        @if ($guiaDetalle->guia_respuesta_sunat)
                        <div class="col-12">
                            <small class="text-muted">Respuesta SUNAT</small>
                            <p class="mb-0 small text-info">{{ $guiaDetalle->guia_respuesta_sunat }}</p>
                        </div>
                        @endif
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">Productos</h6>
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Descripción</th>
                                <th>Código</th>
                                <th class="text-end">Cantidad</th>
                                <th>Unidad</th>
                                <th class="text-end">Peso unit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($itemsDetalle as $item)
                            <tr>
                                <td>{{ $item->detalle_descripcion }}</td>
                                <td>{{ $item->detalle_codigo ?? '—' }}</td>
                                <td class="text-end">{{ number_format($item->detalle_cantidad, 3) }}</td>
                                <td>{{ $item->detalle_unidad_medida }}</td>
                                <td class="text-end">{{ $item->detalle_peso_unitario ? number_format($item->detalle_peso_unitario, 3) : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="cerrarDetalle">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         MODAL CONFIRMACIÓN ENVÍO
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalConfirmacionGuia" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Confirmar</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ $mensajeConfirmacion }}</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm" wire:click="ejecutarEnviarSunat" data-bs-dismiss="modal">
                        <i class="fas fa-paper-plane me-1"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('abrirModalConfirmacionGuia', () => {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmacionGuia'));
            modal.show();
        });
        $wire.on('cerrarModalConfirmacionGuia', () => {
            const el = document.getElementById('modalConfirmacionGuia');
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        });
    </script>
    @endscript
</div>
