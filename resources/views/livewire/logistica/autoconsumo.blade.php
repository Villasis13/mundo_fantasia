<div>

    {{-- ═══════════════ Alertas ═══════════════ --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-start gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0 mt-1"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Detalle de Autoconsumo
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleAutoconsumo" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-eye me-2 text-primary"></i>Detalle de la salida
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="background:#f8f9fb;">
                    @if($detalleAutoconsumo)
                    @php
                        $estadoBadge = ['registrado'=>'bg-success','anulado'=>'bg-danger'][$detalleAutoconsumo->autoconsumo_estado] ?? 'bg-secondary';
                        $totUnid = $detalleItems->sum('detalle_cantidad');
                        $totCosto = $detalleItems->sum(fn($i) => $i->detalle_cantidad * $i->detalle_costo);
                    @endphp

                    {{-- Cabecera --}}
                    <div class="d-flex flex-wrap align-items-start gap-2 pb-3 mb-3 border-bottom">
                        <div>
                            <div class="fw-bold" style="font-size:1.1rem;">GUÍA INTERNA N.° {{ $detalleAutoconsumo->autoconsumo_numero }}</div>
                            <small class="text-muted">Salida de control interno</small>
                        </div>
                        <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $estadoBadge }}" style="font-size:.8rem;padding:.45em .8em;">{{ strtoupper($detalleAutoconsumo->autoconsumo_estado) }}</span>
                            <span class="badge bg-white text-dark border" style="font-size:.8rem;padding:.45em .8em;">
                                <i class="fa-regular fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($detalleAutoconsumo->autoconsumo_fecha)->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    {{-- Información documento + movimiento --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="bg-white border rounded p-3 h-100">
                                <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-regular fa-file-lines me-1 text-primary"></i> Información del documento</h6>
                                <div class="small mb-1"><span class="text-muted">N.° de orden:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->autoconsumo_orden ?? '—' }}</span></div>
                                <div class="small mb-1"><span class="text-muted">N.° de guía:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->autoconsumo_numero }}</span></div>
                                <div class="small mb-1"><span class="text-muted">Fecha:</span> <span class="fw-semibold">{{ \Carbon\Carbon::parse($detalleAutoconsumo->autoconsumo_fecha)->format('d/m/Y') }}</span></div>
                                <div class="small mb-1"><span class="text-muted">Documento:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->autoconsumo_documento ?: 'Guía interna' }}</span></div>
                                <div class="small"><span class="text-muted">Tipo:</span> <span class="fw-semibold">{{ ucfirst($detalleAutoconsumo->autoconsumo_tipo_mov ?? 'salida') }}</span></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="bg-white border rounded p-3 h-100">
                                <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-solid fa-arrow-right-from-bracket me-1 text-primary"></i> Información del movimiento</h6>
                                <div class="small mb-1"><span class="text-muted">Motivo:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->autoconsumo_motivo ?: '—' }}</span></div>
                                <div class="small mb-1"><span class="text-muted">Código SUNAT:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->autoconsumo_cod_sunat ?: '—' }}</span></div>
                                <div class="small"><span class="text-muted">Estado:</span> <span class="badge {{ $estadoBadge }}">{{ ucfirst($detalleAutoconsumo->autoconsumo_estado) }}</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Productos retirados --}}
                    <div class="bg-white border rounded p-3 mb-3">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-solid fa-box me-1 text-primary"></i> Productos retirados</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width:40px;">#</th><th>Código</th><th>Producto</th>
                                        <th>Cantidad</th><th>Costo</th><th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detalleItems as $idx => $item)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>{{ $item->pro_codigo }}</td>
                                        <td>{{ $item->pro_nombre }}</td>
                                        <td class="text-center">{{ number_format($item->detalle_cantidad, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->detalle_costo, 2) }}</td>
                                        <td class="text-end fw-semibold">S/ {{ number_format($item->detalle_cantidad * $item->detalle_costo, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">Sin productos</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column align-items-end mt-3 me-2">
                            <div class="small"><span class="text-muted me-2">Total de unidades:</span> <span class="fw-semibold">{{ number_format($totUnid, 2) }}</span></div>
                            <div><span class="text-muted me-2">Costo total:</span> <span class="fw-bold" style="font-size:1.15rem;color:#4b3fd4;">S/ {{ number_format($totCosto, 2) }}</span></div>
                        </div>
                    </div>

                    {{-- Información de registro --}}
                    <div class="bg-white border rounded p-3">
                        <h6 class="fw-bold text-muted small text-uppercase mb-2"><i class="fa-regular fa-circle-user me-1 text-primary"></i> Información de registro</h6>
                        <div class="small mb-1"><span class="text-muted">Registrado por:</span> <span class="fw-semibold">{{ $detalleAutoconsumo->nombre_users }}</span></div>
                        <div class="small"><span class="text-muted">Fecha y hora:</span> <span class="fw-semibold">{{ \Carbon\Carbon::parse($detalleAutoconsumo->created_at)->format('d/m/Y - h:i a') }}</span></div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: NUEVO AUTOCONSUMO
    ══════════════════════════════════════════════════════════════ --}}
    @if($vista === 'nuevo')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-dolly me-2 text-warning"></i>
                    {{ $idEditando ? 'Editar guía interna' : 'Nueva guía interna' }}
                </h5>
                <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </button>
            </div>
        </div>
        <div class="card-body">

            {{-- Campos del formulario --}}
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-hashtag me-1 text-secondary"></i>
                        Número de orden <span class="text-danger">*</span>
                    </label>
                    <input type="text" wire:model="numeroOrden" maxlength="15" inputmode="numeric"
                           oninput="this.value=this.value.replace(/\D/g,'')"
                           class="form-control fw-semibold @error('numeroOrden') is-invalid @enderror"
                           placeholder="Solo números">
                    @error('numeroOrden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-calendar-day me-1 text-secondary"></i>
                        Fecha <span class="text-danger">*</span>
                    </label>
                    <input type="date" wire:model="fechaEmision"
                           class="form-control @error('fechaEmision') is-invalid @enderror">
                    @error('fechaEmision') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-regular fa-file-lines me-1 text-secondary"></i>
                        Documento <span class="text-danger">*</span>
                    </label>
                    <select wire:model="documento" class="form-select">
                        <option value="Guía interna">Guía interna</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-right-left me-1 text-secondary"></i>
                        Tipo <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="tipoMov" class="form-select @error('tipoMov') is-invalid @enderror">
                        <option value="salida">Salida</option>
                        <option value="ingreso">Ingreso</option>
                    </select>
                    @error('tipoMov') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-hashtag me-1 text-secondary"></i>
                        Serie <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="serie" class="form-select @error('serie') is-invalid @enderror">
                        @foreach($series as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('serie') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-list-ol me-1 text-secondary"></i>
                        Correlativo
                    </label>
                    <input type="text" class="form-control fw-semibold" value="{{ $correlativo }}" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-comment me-1 text-secondary"></i>
                        Motivo <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <select wire:model.live="motivo" class="form-select @error('motivo') is-invalid @enderror">
                            <option value="">— Seleccione motivo —</option>
                            @foreach($motivos as $m)
                                <option value="{{ $m->id_motivo_guia }}">{{ $m->motivo_guia_concepto }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" wire:click="abrirModalMotivos" title="Gestionar motivos">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-barcode me-1 text-secondary"></i>
                        Código SUNAT
                    </label>
                    <input type="text" class="form-control text-center fw-semibold"
                           value="{{ $codSunat !== '' ? $codSunat : '—' }}" readonly>
                </div>
            </div>

            {{-- Buscador de productos --}}
            <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Productos a Consumir
                    </h6>

                    @php $ubicOk = $idTienda > 0 || str_starts_with($ubicacionKey, 'almacen_'); @endphp

                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control bg-white"
                                   wire:model.live.debounce.300ms="buscarProducto"
                                   placeholder="{{ $ubicOk ? 'Escribe nombre o código del producto...' : 'Primero configura la ubicación' }}"
                                   {{ !$ubicOk ? 'disabled' : '' }}
                                   autocomplete="off">
                            <div wire:loading wire:target="buscarProducto" class="input-group-text bg-white">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </div>
                        </div>

                        @if(!empty($resultados))
                        <div class="position-absolute w-100 shadow-lg border rounded-2 bg-white"
                             style="z-index:999; top:100%; max-height:280px; overflow-y:auto;">
                            @foreach($resultados as $prod)
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                                 style="cursor:pointer;"
                                 wire:click="verificarPresentaciones({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', '{{ $prod->pro_codigo }}', {{ $prod->stock_actual }}, {{ $prod->costo }})"
                                 wire:key="res-{{ $prod->id_pro }}">
                                <div>
                                    <span class="fw-semibold d-block">{{ $prod->pro_nombre }}</span>
                                    <small class="text-muted">{{ $prod->pro_codigo }}</small>
                                </div>
                                <div class="text-end ms-3 flex-shrink-0">
                                    <small class="text-primary fw-semibold d-block">
                                        Stock: {{ number_format($prod->stock_actual, 2) }}
                                    </small>
                                    <small class="text-muted">Costo: {{ number_format($prod->costo, 4) }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($ubicOk && strlen(trim($buscarProducto)) >= 2 && empty($resultados))
                        <div class="position-absolute w-100 shadow border rounded-2 bg-white px-3 py-2"
                             style="z-index:999; top:100%;">
                            <small class="text-muted">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                No se encontraron productos.
                            </small>
                        </div>
                        @endif
                    </div>

                    @error('items')
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $message }}
                        </div>
                    @enderror

                    @if(count($items) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle bg-white mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:32px">#</th>
                                    <th>Producto</th>
                                    <th class="text-end" style="width:110px">Costo Unit.</th>
                                    <th style="width:160px">Cantidad</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $idx => $item)
                                @php
                                    $errKey = "items_{$idx}";
                                @endphp
                                <tr wire:key="item-{{ $idx }}">
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold d-block small">{{ $item['nombre'] }}</span>
                                        <small class="text-muted">{{ $item['codigo'] }}</small>
                                        <div class="mt-1">
                                            @if(!empty($item['pres_nombre']))
                                                <span class="badge bg-primary fw-normal me-1" style="font-size:.68rem;">{{ $item['pres_nombre'] }}</span>
                                            @endif
                                            <span class="badge bg-secondary fw-normal" style="font-size:.68rem;">Stock: {{ (int)($item['stock_raw'] ?? $item['stock_actual']) }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ number_format($item['costo'], 4) }}
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal"
                                               wire:model.live="items.{{ $idx }}.cantidad"
                                               class="form-control form-control-sm text-end @error($errKey) is-invalid @enderror"
                                               placeholder="0.00">
                                        @error($errKey)
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="quitarItem({{ $idx }})" title="Quitar">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4 border border-dashed rounded-2 bg-white">
                        <i class="fa-solid fa-fire-burner fa-2x opacity-25 d-block mb-2"></i>
                        <small>Busca y agrega los productos a consumir.</small>
                    </div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" wire:click="volverHistorial">Cancelar</button>
                <button type="button" class="btn btn-warning fw-semibold text-dark"
                        wire:click="guardar"
                        wire:loading.attr="disabled" wire:target="guardar"
                        {{ (!$ubicOk || empty($items)) ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="guardar">
                        <i class="fa-solid fa-floppy-disk me-1"></i> {{ $idEditando ? 'Actualizar' : 'Registrar guía interna' }}
                    </span>
                    <span wire:loading wire:target="guardar">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;" role="status"></span> Guardando...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: REVISIÓN — Detalle del autoconsumo guardado
    ══════════════════════════════════════════════════════════════ --}}
    @elseif($vista === 'revision')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-circle-check me-2 text-success"></i>
                        Guia Control Interno Registrado
                        @if($revisionAutoconsumo)
                            <span class="fw-normal text-muted ms-2 small">— {{ $revisionAutoconsumo->autoconsumo_numero }}</span>
                        @endif
                    </h5>
                    @if($revisionAutoconsumo)
                    <small class="text-muted">
                        {{ $revisionAutoconsumo->ubicacion_nombre }}
                        @if($revisionAutoconsumo->empresa_nombre) — {{ $revisionAutoconsumo->empresa_nombre }} @endif
                        &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($revisionAutoconsumo->autoconsumo_fecha)->format('d/m/Y') }}
                    </small>
                    @endif
                </div>
                <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                    <i class="fa-solid fa-list me-1"></i> Historial
                </button>
            </div>
        </div>

        <div class="card-body">
            @if($revisionAutoconsumo)
            <div class="alert alert-success d-flex gap-2 align-items-center py-2 mb-3">
                <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                <div>
                    La guía de control interno fue guardada correctamente y el stock fue actualizado.
                    El movimiento es visible en el <strong>Kardex Valorizado</strong>.
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Área</small>
                    <strong>{{ $revisionAutoconsumo->autoconsumo_area }}</strong>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block">Motivo</small>
                    <strong>{{ $revisionAutoconsumo->autoconsumo_motivo ?: '—' }}</strong>
                </div>
                <div class="col-sm-6 col-md-2">
                    <small class="text-muted d-block">Estado</small>
                    <span class="badge bg-success">Registrado</span>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-dark encabezado_tabla_color">
                        <tr>
                            <th style="width:32px">#</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th class="text-end" style="width:100px">Cantidad</th>
                            <th class="text-end" style="width:110px">Costo Unit.</th>
                            <th class="text-end" style="width:110px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($revisionItems as $i => $item)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td class="text-muted small">{{ $item->pro_codigo }}</td>
                            <td class="fw-semibold">{{ $item->pro_nombre }}</td>
                            <td class="text-end">{{ number_format($item->detalle_cantidad, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format($item->detalle_costo, 4) }}</td>
                            <td class="text-end fw-semibold">
                                S/ {{ number_format($item->detalle_cantidad * $item->detalle_costo, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Sin productos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($revisionItems->count() > 0)
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold">Total:</td>
                            <td class="text-end fw-bold">
                                S/ {{ number_format($revisionItems->sum(fn($i) => $i->detalle_cantidad * $i->detalle_costo), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-outline-secondary" wire:click="volverHistorial">
                    <i class="fa-solid fa-list me-1"></i> Ver Historial
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: HISTORIAL DE AUTOCONSUMOS
    ══════════════════════════════════════════════════════════════ --}}
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-dolly me-2 text-warning"></i>
                        Guía de Control Interno
                    </h5>
                    <small class="text-muted">Historial de salidas internas.</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('logistica.autoconsumo_excel_consolidado', ['desde' => $filtroDesde, 'hasta' => $filtroHasta]) }}"
                       target="_blank" class="btn btn-outline-success fw-semibold">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Consolidado
                    </a>
                    <a href="{{ route('logistica.autoconsumo_excel_detallado', ['desde' => $filtroDesde, 'hasta' => $filtroHasta]) }}"
                       target="_blank" class="btn btn-outline-success fw-semibold">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Detallado
                    </a>
                    @can('autoconsumo.crear')
                    <button class="btn btn-warning fw-semibold text-dark" wire:click="nuevoAutoconsumo"
                            wire:loading.attr="disabled" wire:target="nuevoAutoconsumo">
                        <span wire:loading.remove wire:target="nuevoAutoconsumo">
                            <i class="fa-solid fa-plus me-1"></i> Nueva guía interna
                        </span>
                        <span wire:loading wire:target="nuevoAutoconsumo">
                            <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Cargando...
                        </span>
                    </button>
                    @endcan
                </div>
            </div>

            <div class="row g-2 align-items-end mt-3">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Desde</span>
                        <input type="date" class="form-control" wire:model.live="filtroDesde">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Hasta</span>
                        <input type="date" class="form-control" wire:model.live="filtroHasta">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3">N.° de orden</th>
                            <th class="text-center">Tipo</th>
                            <th>Fecha</th>
                            <th>Documento</th>
                            <th>Motivo</th>
                            <th class="text-center">Cód. SUNAT</th>
                            <th class="text-center">Productos</th>
                            <th class="text-end">Costo total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:90px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($autoconsumos as $index => $ac)
                        @php
                            $estadoBadge = ['registrado'=>'bg-success','anulado'=>'bg-danger'][$ac->autoconsumo_estado] ?? 'bg-secondary';
                        @endphp
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $ac->autoconsumo_orden ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge {{ ($ac->autoconsumo_tipo_mov ?? 'salida') === 'ingreso' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($ac->autoconsumo_tipo_mov ?? 'salida') }}
                                </span>
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($ac->autoconsumo_fecha)->format('d/m/Y') }}</small></td>
                            <td>
                                {{ $ac->autoconsumo_documento ?: 'Guía interna' }}
                                <small class="text-muted d-block">{{ $ac->autoconsumo_numero }}</small>
                            </td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($ac->autoconsumo_motivo, 40) ?: '—' }}</td>
                            <td class="text-center">{{ $ac->autoconsumo_cod_sunat ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $ac->total_productos }}</span>
                            </td>
                            <td class="text-end fw-semibold">S/ {{ number_format($ac->costo_total, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $estadoBadge }}">{{ ucfirst($ac->autoconsumo_estado) }}</span>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-info"
                                        wire:click="verDetalle({{ $ac->id_autoconsumo }})"
                                        wire:loading.attr="disabled"
                                        wire:target="verDetalle({{ $ac->id_autoconsumo }})"
                                        title="Ver detalle">
                                    <span wire:loading.remove wire:target="verDetalle({{ $ac->id_autoconsumo }})">
                                        <i class="fa-solid fa-eye"></i>
                                    </span>
                                    <span wire:loading wire:target="verDetalle({{ $ac->id_autoconsumo }})">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                @can('autoconsumo.actualizar')
                                <button class="btn btn-sm btn-warning" wire:click="editar({{ $ac->id_autoconsumo }})" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endcan
                                @can('autoconsumo.eliminar')
                                <button class="btn btn-sm btn-danger" wire:click="confirmarEliminar({{ $ac->id_autoconsumo }})" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa-solid fa-fire-burner fa-2x mb-2 d-block opacity-25"></i>
                                No hay autoconsumos en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($autoconsumos->hasPages())
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $autoconsumos->firstItem() }}–{{ $autoconsumos->lastItem() }}
                    de {{ $autoconsumos->total() }} registros
                </small>
                {{ $autoconsumos->links() }}
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Selección de Presentación
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalPresentacionesAutoconsumo" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                        Seleccionar Presentación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($productoPendienteData))
                    <p class="text-muted small mb-3">
                        <strong>{{ $productoPendienteData['nombre'] ?? '' }}</strong>
                        — elige cómo deseas registrar la cantidad:
                    </p>
                    @endif
                    <div class="d-grid gap-2">
                        @foreach($presentacionesPendientes as $pres)
                        <button type="button"
                                class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between"
                                wire:click="seleccionarPresentacion({{ $pres['id_pres'] }})"
                                data-bs-dismiss="modal">
                            <span class="fw-semibold">{{ $pres['pres_nombre'] }}</span>
                            <span class="text-muted small ms-2">
                                × {{ number_format($pres['pres_factor'], 2) }} unid.
                            </span>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ MODAL — Confirmar eliminación ══════════ --}}
    <div class="modal fade" id="modalEliminarAutoconsumo" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3" style="width:70px;height:70px;">
                        <i class="fa-solid fa-trash text-white" style="font-size:1.8rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Estás seguro de eliminar este registro?</h6>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-semibold" wire:click="eliminar"
                            wire:loading.attr="disabled" wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar"><i class="fa-solid fa-trash me-1"></i> Eliminar</span>
                        <span wire:loading wire:target="eliminar"><span class="spinner-border spinner-border-sm me-1"></span> Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ MODAL — Gestión de Motivos (CRUD) ══════════ --}}
    <div class="modal fade" id="modalMotivos" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-tags me-2 text-primary"></i>Gestión de motivos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="resetMotivoForm"></button>
                </div>
                <div class="modal-body">
                    {{-- Formulario crear/editar --}}
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Motivo <span class="text-danger">*</span></label>
                            <input type="text" wire:model="mgConcepto" class="form-control form-control-sm @error('mgConcepto') is-invalid @enderror" placeholder="Nombre del motivo">
                            @error('mgConcepto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Cód. SUNAT</label>
                            <input type="text" wire:model="mgCodigo" maxlength="10" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-6 col-md-3 d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-sm flex-grow-1" wire:click="guardarMotivo">
                                <i class="fa-solid {{ $mgEditId ? 'fa-pen' : 'fa-plus' }} me-1"></i>{{ $mgEditId ? 'Actualizar' : 'Agregar' }}
                            </button>
                            @if($mgEditId)
                            <button type="button" class="btn btn-light btn-sm" wire:click="resetMotivoForm" title="Cancelar edición">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Listado --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Motivo</th>
                                    <th class="text-center" style="width:110px;">Cód. SUNAT</th>
                                    <th class="text-center" style="width:100px;">Estado</th>
                                    <th class="text-center" style="width:120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($motivosTodos as $mt)
                                <tr class="{{ $mt->motivo_guia_estado ? '' : 'text-muted' }}">
                                    <td>{{ $mt->motivo_guia_concepto }}</td>
                                    <td class="text-center">{{ $mt->motivo_guia_codigo !== '' ? $mt->motivo_guia_codigo : '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $mt->motivo_guia_estado ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $mt->motivo_guia_estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-warning" wire:click="editarMotivo({{ $mt->id_motivo_guia }})" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm {{ $mt->motivo_guia_estado ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                wire:click="toggleMotivoEstado({{ $mt->id_motivo_guia }})"
                                                title="{{ $mt->motivo_guia_estado ? 'Deshabilitar' : 'Habilitar' }}">
                                            <i class="fa-solid {{ $mt->motivo_guia_estado ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Sin motivos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetMotivoForm">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('abrirModalMotivos', () => {
                new bootstrap.Modal(document.getElementById('modalMotivos')).show();
            });
            Livewire.on('abrirModalDetalle', () => {
                new bootstrap.Modal(document.getElementById('modalDetalleAutoconsumo')).show();
            });
            Livewire.on('abrirModalPresentacionesAutoconsumo', () => {
                new bootstrap.Modal(document.getElementById('modalPresentacionesAutoconsumo')).show();
            });
            Livewire.on('cerrarModalPresentacionesAutoconsumo', () => {
                const el = document.getElementById('modalPresentacionesAutoconsumo');
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            });
            Livewire.on('abrirModalEliminarAutoconsumo', () => {
                new bootstrap.Modal(document.getElementById('modalEliminarAutoconsumo')).show();
            });
            Livewire.on('cerrarModalEliminarAutoconsumo', () => {
                const el = document.getElementById('modalEliminarAutoconsumo');
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            });
        });
    </script>

</div>
