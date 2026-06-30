<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-truck-moving me-2 text-primary"></i>
                Distribución del Flete y Vinculación de Transportista
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalBuscarComprobante">
                    <i class="fa-solid fa-file-arrow-up me-1"></i>Cargar Comprobante
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        wire:click="abrirModalRegistros"
                        wire:loading.attr="disabled" wire:target="abrirModalRegistros">
                    <span wire:loading wire:target="abrirModalRegistros" class="spinner-border spinner-border-sm me-1"></span>
                    <i wire:loading.remove wire:target="abrirModalRegistros" class="fa-solid fa-clock-rotate-left me-1"></i>Registros
                </button>
                <button type="button" class="btn btn-success btn-sm"
                        wire:click="guardar"
                        wire:loading.attr="disabled" wire:target="guardar"
                        @disabled(!$calculado)>
                    <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                    <span wire:loading.remove wire:target="guardar">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        {{ $idDistribucionActual > 0 ? 'Actualizar' : 'Guardar' }}
                    </span>
                </button>
            </div>
        </div>

        <div class="card-body">
            @if($mensajeGuardado)
            <div class="alert alert-{{ $mensajeGuardadoTipo }} py-2 mb-3 d-flex align-items-center gap-2" role="alert">
                <i class="fa-solid {{ $mensajeGuardadoTipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                <div class="small fw-medium flex-fill">{{ $mensajeGuardado }}</div>
                <button type="button" class="btn-close btn-sm" wire:click="$set('mensajeGuardado', '')"></button>
            </div>
            @endif
            <div class="d-flex gap-3 align-items-stretch">

                {{-- Slots dinámicos de transportistas --}}
                <div class="flex-fill d-flex flex-column gap-2">

                    @foreach($transportistaSlots as $idx => $slot)
                    <div class="border rounded-2 p-3" style="background:#f8f9fb;" wire:key="slot-{{ $idx }}">
                        <div class="row g-2 align-items-center">

                            {{-- Botón seleccionar transportista --}}
                            <div class="col-auto">
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        wire:click="abrirModalTransportista({{ $idx }})"
                                        wire:loading.attr="disabled" wire:target="abrirModalTransportista({{ $idx }})"
                                        title="Transportista {{ $idx + 1 }}">
                                    <span wire:loading wire:target="abrirModalTransportista({{ $idx }})" class="spinner-border spinner-border-sm"></span>
                                    <i wire:loading.remove wire:target="abrirModalTransportista({{ $idx }})" class="fa-solid fa-truck"></i>
                                </button>
                            </div>

                            {{-- Nombre (readonly, poblado por modal) --}}
                            <div class="col-auto" style="width:220px;">
                                <input type="text" class="form-control form-control-sm"
                                       value="{{ $slot['nombre'] }}" readonly placeholder="Nombre">
                            </div>

                            {{-- RUC (readonly) --}}
                            <div class="col-auto" style="width:110px;">
                                <input type="text" class="form-control form-control-sm"
                                       value="{{ $slot['ruc'] }}" readonly placeholder="RUC">
                            </div>

                            {{-- Factura --}}
                            <div class="col-auto" style="width:110px;">
                                <input type="text" class="form-control form-control-sm"
                                       wire:model.blur="transportistaSlots.{{ $idx }}.fact"
                                       placeholder="N° Fact.">
                            </div>

                            {{-- Fecha --}}
                            <div class="col-auto" style="min-width:140px;">
                                <input type="date" class="form-control form-control-sm"
                                       wire:model.blur="transportistaSlots.{{ $idx }}.fecha">
                            </div>

                            {{-- Guía --}}
                            <div class="col-auto" style="width:110px;">
                                <input type="text" class="form-control form-control-sm"
                                       wire:model.blur="transportistaSlots.{{ $idx }}.guia"
                                       placeholder="N° Guía">
                            </div>

                            {{-- Flete --}}
                            <div class="col-auto" style="width:80px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" inputmode="decimal" class="form-control"
                                           wire:model.live.debounce.600ms="transportistaSlots.{{ $idx }}.flete"
                                           placeholder="0.00">
                                </div>
                            </div>

                            {{-- Quitar (solo si hay más de 1 slot) --}}
                            @if(count($transportistaSlots) > 1)
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1"
                                        wire:click="quitarSlot({{ $idx }})"
                                        wire:loading.attr="disabled" wire:target="quitarSlot({{ $idx }})"
                                        title="Quitar transportista">
                                    <span wire:loading wire:target="quitarSlot({{ $idx }})"><span class="spinner-border spinner-border-sm"></span></span>
                                    <span wire:loading.remove wire:target="quitarSlot({{ $idx }})"><i class="fa-solid fa-xmark"></i></span>
                                </button>
                            </div>
                            @endif

                        </div>
                    </div>
                    @endforeach

                    {{-- Agregar nuevo slot --}}
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="agregarSlot"
                                wire:loading.attr="disabled" wire:target="agregarSlot">
                            <span wire:loading wire:target="agregarSlot"><span class="spinner-border spinner-border-sm me-1"></span></span>
                            <span wire:loading.remove wire:target="agregarSlot"><i class="fa-solid fa-plus me-1"></i></span>
                            Agregar transportista
                        </button>
                    </div>

                </div>

                {{-- Total Flete + Calcular --}}
                <div class="d-flex flex-column align-items-center justify-content-center gap-1 border rounded-2 px-3 py-2" style="min-width:130px;background:#f0f4ff;">
                    <span class="fw-semibold small text-secondary text-center" style="font-size:.75rem;">Total Flete S/</span>
                    <input type="text" class="form-control form-control-sm text-center fw-bold"
                           value="{{ number_format($totalFlete, 2) }}" readonly
                           style="color:#1a5276;border:2px solid #aec6f5;font-size:.95rem;">
                    @php
                        $fleteListo = count($transportistaSlots) > 0
                            && collect($transportistaSlots)->every(fn($s) => trim($s['flete'] ?? '') !== '' && (float)$s['flete'] > 0)
                            && !empty($detallesOrden);
                    @endphp
                    <button type="button" class="btn btn-primary btn-sm w-100 mt-1"
                            wire:click="calcular"
                            wire:loading.attr="disabled" wire:target="calcular"
                            @disabled(!$fleteListo)>
                        <span wire:loading wire:target="calcular"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="calcular"><i class="fa-solid fa-calculator me-1"></i>Calcular</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Tabla de productos --}}
    @if(!empty($detalles))
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.8rem;min-width:1200px;">
                    @php
                        $rowspans   = [];
                        $seenGroups = [];
                        foreach ($detalles as $d) {
                            $oid = $d['_id_orden'];
                            $rowspans[$oid] = ($rowspans[$oid] ?? 0) + 1;
                        }
                    @endphp
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Tipo</th><th>Serie</th><th>N°</th><th>Proveedor</th>
                            <th class="text-end">Total Comprobante</th><th>Fecha Emisión</th>
                            <th>Código</th><th>Producto</th>
                            <th class="text-end">Costo Inicial</th><th>Unid</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Flete Total</th><th class="text-end">Flete Uni</th>
                            <th class="text-end">Costo Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalles as $det)
                        @php $oid = $det['_id_orden']; $isFirst = !in_array($oid, $seenGroups); if($isFirst) $seenGroups[] = $oid; @endphp
                        <tr wire:key="det-{{ $det['id_detalle_compra'] }}">
                            @if($isFirst)
                            <td rowspan="{{ $rowspans[$oid] }}" class="text-center align-middle" style="white-space:nowrap;">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger py-0 px-2"
                                        wire:click="quitarOrden({{ $oid }})"
                                        wire:loading.attr="disabled" wire:target="quitarOrden({{ $oid }})"
                                        title="Quitar comprobante">
                                    <span wire:loading wire:target="quitarOrden({{ $oid }})"><span class="spinner-border spinner-border-sm"></span></span>
                                    <span wire:loading.remove wire:target="quitarOrden({{ $oid }})"><i class="fa-solid fa-xmark"></i></span>
                                </button>
                            </td>
                            @endif
                            <td><span class="badge bg-secondary">{{ $det['_tipo'] ?? 'FAC' }}</span></td>
                            <td class="fw-semibold">{{ $det['_serie'] ?? '' }}</td>
                            <td class="fw-semibold">{{ $det['_correlativo'] ?? '' }}</td>
                            <td class="text-truncate" style="max-width:140px;" title="{{ $det['_proveedor'] ?? '' }}">{{ $det['_proveedor'] ?? '—' }}</td>
                            <td class="text-end">S/ {{ number_format($det['_total'] ?? 0, 2) }}</td>
                            <td>{{ !empty($det['_fecha']) ? \Carbon\Carbon::parse($det['_fecha'])->format('d/m/Y') : '—' }}</td>
                            <td class="text-muted">{{ $det['pro_codigo'] ?? '—' }}</td>
                            <td>{{ $det['detalle_orden_nombre_producto'] }}</td>
                            <td class="text-end">{{ number_format($det['detalle_compra_precio_compra'], 4) }}</td>
                            <td class="text-muted">{{ $det['unidad'] ?? '—' }}</td>
                            <td class="text-end">{{ number_format($det['detalle_compra_cantidad'], 2) }}</td>
                            <td class="text-end text-info fw-semibold">{{ number_format($det['flete_total'], 2) }}</td>
                            <td class="text-end text-info">{{ number_format($det['flete_uni'], 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($det['costo_final'], 4) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ══ Modal: Registros de Distribución ══ --}}
    <div class="modal fade" id="modalRegistros" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div style="height:4px;" class="bg-secondary"></div>
                <div class="modal-header border-0 pb-0 pt-3 px-4">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>
                        Registros de Distribución
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4 pt-3">
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="buscarRegistro"
                               placeholder="Buscar por transportista o N° factura...">
                    </div>
                    <div style="max-height:420px;overflow-y:auto;">
                        @if(!empty($registrosDistribucion))
                        <div class="list-group list-group-flush">
                            @foreach($registrosDistribucion as $reg)
                            <button type="button"
                                    class="list-group-item list-group-item-action py-2 px-3 {{ $idDistribucionActual === $reg['id_distribucion_flete'] ? 'active' : '' }}"
                                    wire:click="cargarDistribucion({{ $reg['id_distribucion_flete'] }})"
                                    wire:loading.attr="disabled" wire:target="cargarDistribucion({{ $reg['id_distribucion_flete'] }})"
                                    wire:key="reg-{{ $reg['id_distribucion_flete'] }}">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div class="flex-fill">
                                        @foreach($reg['transportistas'] as $rt)
                                        <span class="d-block lh-sm" style="font-size:.82rem;">
                                            <i class="fa-solid fa-truck me-1 text-primary opacity-75" style="font-size:.72rem;"></i>
                                            <span class="fw-semibold">{{ $rt['nombre'] }}</span>
                                            @if($rt['fact'])<span class="text-muted fw-normal"> &mdash; Fact. {{ $rt['fact'] }}</span>@endif
                                        </span>
                                        @endforeach
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="d-block fw-bold" style="font-size:.85rem;">S/ {{ number_format($reg['distribucion_flete_total'], 2) }}</span>
                                        <small class="text-muted opacity-75">{{ \Carbon\Carbon::parse($reg['created_at'])->format('d/m/Y') }}</small>
                                    </div>
                                    <span wire:loading wire:target="cargarDistribucion({{ $reg['id_distribucion_flete'] }})">
                                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                                    </span>
                                    <i wire:loading.remove wire:target="cargarDistribucion({{ $reg['id_distribucion_flete'] }})"
                                       class="fa-solid fa-chevron-right text-muted opacity-50"></i>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-folder-open fa-2x opacity-25 d-block mb-2"></i>
                            <small>No se encontraron registros.</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal: Seleccionar / Crear Transportista ══ --}}
    <div class="modal fade" id="modalTransportista" tabindex="-1" wire:ignore.self
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div style="height:4px;" class="bg-primary"></div>
                <div class="modal-header border-0 pb-0 pt-3 px-4">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-truck me-2 text-primary"></i>
                        Transportista {{ $slotActivo + 1 }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- Tabs --}}
                <div class="px-4 pt-2">
                    <ul class="nav nav-tabs nav-tabs-sm">
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 {{ $tabTransportista === 'seleccionar' ? 'active' : '' }}"
                                    wire:click="abrirTabSeleccionar"
                                    wire:loading.attr="disabled" wire:target="abrirTabSeleccionar">
                                <span wire:loading wire:target="abrirTabSeleccionar" class="spinner-border spinner-border-sm me-1"></span>
                                <i wire:loading.remove wire:target="abrirTabSeleccionar" class="fa-solid fa-list me-1"></i>Seleccionar
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 {{ $tabTransportista === 'nuevo' ? 'active' : '' }}"
                                    wire:click="abrirTabNuevo"
                                    wire:loading.attr="disabled" wire:target="abrirTabNuevo">
                                <span wire:loading wire:target="abrirTabNuevo" class="spinner-border spinner-border-sm me-1"></span>
                                <i wire:loading.remove wire:target="abrirTabNuevo" class="fa-solid fa-plus me-1"></i>Nuevo
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body px-4 pb-4 pt-3">

                    {{-- Tab: Seleccionar --}}
                    @if($tabTransportista === 'seleccionar')
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="buscarTransportista"
                               placeholder="Buscar por nombre o RUC...">
                    </div>

                    <div style="max-height:360px;overflow-y:auto;">
                        @if(!empty($transportistas))
                        <div class="list-group list-group-flush">
                            @foreach($transportistas as $tr)
                            @php
                                $tid       = $tr['id_transportista'];
                                $slotOtro  = null;
                                foreach ($transportistaSlots as $_i => $_s) {
                                    if ($_i !== $slotActivo && ($_s['id'] ?? 0) === $tid) { $slotOtro = $_i; break; }
                                }
                                $enOtro    = $slotOtro !== null;
                                $labelOtro = $enOtro ? 'T' . ($slotOtro + 1) : null;
                            @endphp
                            <button type="button"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 {{ $enOtro ? 'disabled opacity-50' : '' }}"
                                    @if(!$enOtro)
                                        wire:click="seleccionarTransportista({{ $tid }})"
                                        wire:loading.attr="disabled" wire:target="seleccionarTransportista({{ $tid }})"
                                    @endif
                                    wire:key="tr-{{ $tid }}">
                                <div>
                                    <span class="fw-semibold d-block lh-sm">{{ $tr['transportista_nombre'] }}</span>
                                    <small class="text-muted">
                                        RUC: {{ $tr['transportista_ruc'] ?: '—' }}
                                        @if($tr['transportista_chofer'])· Chofer: {{ $tr['transportista_chofer'] }}@endif
                                    </small>
                                </div>
                                @if($enOtro)
                                    <span class="badge bg-warning text-dark">Asignado {{ $labelOtro }}</span>
                                @else
                                    <span wire:loading wire:target="seleccionarTransportista({{ $tid }})"><span class="spinner-border spinner-border-sm text-primary"></span></span>
                                    <i wire:loading.remove wire:target="seleccionarTransportista({{ $tid }})" class="fa-solid fa-chevron-right text-muted opacity-50"></i>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-truck fa-2x opacity-25 d-block mb-2"></i>
                            <small>No se encontraron transportistas.</small>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Tab: Nuevo --}}
                    @if($tabTransportista === 'nuevo')
                    <div class="row g-3">
                        {{-- RUC + lupa --}}
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-secondary mb-1">RUC</label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       wire:model="ntRuc"
                                       wire:keydown.enter="ntBuscarRuc"
                                       placeholder="20512345678" maxlength="11">
                                <button type="button" class="btn btn-outline-secondary px-2"
                                        wire:click="ntBuscarRuc"
                                        wire:loading.attr="disabled" wire:target="ntBuscarRuc"
                                        title="Consultar RUC">
                                    <span wire:loading wire:target="ntBuscarRuc"><span class="spinner-border spinner-border-sm"></span></span>
                                    <span wire:loading.remove wire:target="ntBuscarRuc"><i class="fa-solid fa-magnifying-glass"></i></span>
                                </button>
                            </div>
                            @if($ntRucMensaje)
                            <div class="mt-1 small {{ $ntRucMensajeTipo === 'success' ? 'text-success' : 'text-danger' }}">
                                <i class="fa-solid {{ $ntRucMensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' }} me-1"></i>{{ $ntRucMensaje }}
                            </div>
                            @endif
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-semibold small text-secondary mb-1">Nombre / Razón Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ntNombre') is-invalid @enderror"
                                   wire:model="ntNombre" placeholder="Ej. TRANSPORTES SAC">
                            @error('ntNombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Chofer</label>
                            <input type="text" class="form-control" wire:model="ntChofer" placeholder="Nombre del chofer">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Vehículo</label>
                            <input type="text" class="form-control" wire:model="ntVehiculo" placeholder="Tipo de vehículo">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Placa</label>
                            <input type="text" class="form-control" wire:model="ntPlaca" placeholder="ABC-123">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Teléfono</label>
                            <input type="text" class="form-control" wire:model="ntTelefono" placeholder="(opcional)">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Dirección</label>
                            <input type="text" class="form-control" wire:model="ntDireccion" placeholder="(opcional)">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-primary fw-semibold px-4"
                                wire:click="guardarNuevoTransportista"
                                wire:loading.attr="disabled" wire:target="guardarNuevoTransportista">
                            <span wire:loading wire:target="guardarNuevoTransportista"><span class="spinner-border spinner-border-sm me-1"></span>Guardando...</span>
                            <span wire:loading.remove wire:target="guardarNuevoTransportista"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar y Seleccionar</span>
                        </button>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal: Buscar Comprobante ══ --}}
    <div class="modal fade" id="modalBuscarComprobante" tabindex="-1" wire:ignore.self
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div style="height:4px;" class="bg-primary"></div>
                <div class="modal-header border-0 pb-1 pt-3 px-4">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>Buscar Comprobante de Compra
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control"
                               wire:model="buscarCorrelativo"
                               wire:keydown.enter="buscarFactura"
                               placeholder="N° correlativo o nombre del proveedor...">
                        <button type="button" class="btn btn-primary px-3"
                                wire:click="buscarFactura"
                                wire:loading.attr="disabled" wire:target="buscarFactura">
                            <span wire:loading wire:target="buscarFactura"><span class="spinner-border spinner-border-sm"></span></span>
                            <span wire:loading.remove wire:target="buscarFactura"><i class="fa-solid fa-magnifying-glass"></i></span>
                        </button>
                    </div>

                    @if(!empty($resultadosBusqueda))
                    <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
                        <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Tipo</th><th>N° Documento</th><th>Proveedor</th>
                                    <th class="text-end">Total</th><th>F. Emisión</th><th>Estado</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultadosBusqueda as $res)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ strtoupper($res['orden_compra_tipo_doc'] ?? '') }}</span></td>
                                    <td class="fw-semibold">{{ $res['orden_compra_numero_doc'] ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width:180px;">{{ $res['orden_compra_nom_prove'] ?? '—' }}</td>
                                    <td class="text-end">S/ {{ number_format($res['orden_compra_total'] ?? 0, 2) }}</td>
                                    <td>{{ !empty($res['orden_compra_fecha_emision_doc']) ? \Carbon\Carbon::parse($res['orden_compra_fecha_emision_doc'])->format('d/m/Y') : '—' }}</td>
                                    <td><span class="badge {{ $res['orden_compra_estado'] === 'recibido' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($res['orden_compra_estado'] ?? '') }}</span></td>
                                    <td>
                                        @if(in_array($res['id_orden_compra'], $ordenesIds))
                                            <span class="badge bg-success py-1 px-2">
                                                <i class="fa-solid fa-check me-1"></i>Agregado
                                            </span>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                                    wire:click="seleccionarOrden({{ $res['id_orden_compra'] }})"
                                                    wire:loading.attr="disabled" wire:target="seleccionarOrden({{ $res['id_orden_compra'] }})">
                                                <span wire:loading wire:target="seleccionarOrden({{ $res['id_orden_compra'] }})"><span class="spinner-border spinner-border-sm"></span></span>
                                                <span wire:loading.remove wire:target="seleccionarOrden({{ $res['id_orden_compra'] }})">Seleccionar</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($buscarCorrelativo !== '')
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-circle-info fa-lg mb-2 d-block opacity-50"></i>
                        <small>No se encontraron comprobantes.</small>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>
                        <small>Escribe el correlativo o nombre del proveedor y presiona Enter.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('abrirModalRegistros', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegistros')).show();
        });
        $wire.on('cerrarModalRegistros', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalRegistros'));
            if (m) m.hide();
        });

        $wire.on('abrirModalTransportista', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTransportista')).show();
        });
        $wire.on('cerrarModalTransportista', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalTransportista'));
            if (m) m.hide();
        });
        $wire.on('cerrarModalBusqueda', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalBuscarComprobante'));
            if (m) m.hide();
        });
    </script>
    @endscript
</div>
