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
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-fire-burner me-2 text-warning"></i>
                        Detalle de Autoconsumo
                        @if($detalleAutoconsumo)
                            <span class="fw-normal text-muted small ms-2">— {{ $detalleAutoconsumo->autoconsumo_numero }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($detalleAutoconsumo)
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ubicación</small>
                            <strong>{{ $detalleAutoconsumo->ubicacion_nombre }}</strong>
                            @if($detalleAutoconsumo->empresa_nombre)
                                <small class="text-muted d-block">{{ $detalleAutoconsumo->empresa_nombre }}</small>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Área</small>
                            <strong>{{ $detalleAutoconsumo->autoconsumo_area }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Autorizado por</small>
                            <strong>{{ $detalleAutoconsumo->autoconsumo_autorizacion }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Fecha</small>
                            <strong>{{ \Carbon\Carbon::parse($detalleAutoconsumo->autoconsumo_fecha)->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Registrado por</small>
                            <strong>{{ $detalleAutoconsumo->nombre_users }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Estado</small>
                            <span class="badge bg-success">Registrado</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-dark encabezado_tabla_color">
                                <tr>
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="text-end" style="width:100px">Cantidad</th>
                                    <th class="text-end" style="width:100px">Costo Unit.</th>
                                    <th class="text-end" style="width:110px">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detalleItems as $idx => $item)
                                <tr>
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td class="text-muted small">{{ $item->pro_codigo }}</td>
                                    <td class="fw-semibold">{{ $item->pro_nombre }}</td>
                                    <td class="text-end">{{ number_format($item->detalle_cantidad, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->detalle_costo, 4) }}</td>
                                    <td class="text-end fw-semibold">
                                        S/ {{ number_format($item->detalle_cantidad * $item->detalle_costo, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Sin productos</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($detalleItems->count() > 0)
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">
                                        S/ {{ number_format($detalleItems->sum(fn($i) => $i->detalle_cantidad * $i->detalle_costo), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
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
                    <i class="fa-solid fa-fire-burner me-2 text-warning"></i>
                    Nuevo Autoconsumo
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
                        <i class="fa-solid fa-tag me-1 text-secondary"></i>
                        Área <span class="text-danger">*</span>
                    </label>
                    <select wire:model="area" class="form-select">
                        <option value="Administración">Administración</option>
                        <option value="Almacén">Almacén</option>
                        <option value="Ventas">Ventas</option>
                    </select>
                </div>


                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-calendar-day me-1 text-secondary"></i>
                        Fecha de Emisión <span class="text-danger">*</span>
                    </label>
                    <input type="date" wire:model="fechaEmision"
                           class="form-control @error('fechaEmision') is-invalid @enderror">
                    @error('fechaEmision') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        <i class="fa-solid fa-floppy-disk me-1"></i> Registrar Autoconsumo
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
                        Autoconsumo Registrado
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
                    El autoconsumo fue registrado correctamente y el stock fue actualizado.
                    El movimiento es visible en el <strong>Kardex Valorizado</strong>.
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Área</small>
                    <strong>{{ $revisionAutoconsumo->autoconsumo_area }}</strong>
                </div>
                <div class="col-sm-6 col-md-4">
                    <small class="text-muted d-block">Autorizado por</small>
                    <strong>{{ $revisionAutoconsumo->autoconsumo_autorizacion }}</strong>
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
                        <i class="fa-solid fa-fire-burner me-2 text-warning"></i>
                        Autoconsumo
                    </h5>
                    <small class="text-muted">Historial de salidas por autoconsumo.</small>
                </div>
                @can('autoconsumo.crear')
                <button class="btn btn-warning fw-semibold text-dark" wire:click="nuevoAutoconsumo"
                        wire:loading.attr="disabled" wire:target="nuevoAutoconsumo">
                    <span wire:loading.remove wire:target="nuevoAutoconsumo">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Autoconsumo
                    </span>
                    <span wire:loading wire:target="nuevoAutoconsumo">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Cargando...
                    </span>
                </button>
                @endcan
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
                            <th class="ps-3">#</th>
                            <th>N° Autoconsumo</th>
                            <th>Ubicación</th>
                            <th>Área</th>
                            <th>Autorizado por</th>
                            <th>Fecha</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:120px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($autoconsumos as $index => $ac)
                        <tr>
                            <td class="ps-3 text-muted fw-semibold">{{ $autoconsumos->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $ac->autoconsumo_numero }}</td>
                            <td>
                                <span class="d-block">{{ $ac->ubicacion_nombre }}</span>
                                @if($ac->empresa_nombre)
                                    <small class="text-muted">{{ $ac->empresa_nombre }}</small>
                                @endif
                            </td>
                            <td>{{ $ac->autoconsumo_area }}</td>
                            <td class="text-muted">{{ $ac->autoconsumo_autorizacion }}</td>
                            <td><small>{{ \Carbon\Carbon::parse($ac->autoconsumo_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $ac->total_productos }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">Registrado</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info me-1"
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
                                <a href="{{ route('logistica.autoconsumo_pdf', ['id' => $ac->id_autoconsumo]) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-danger me-1"
                                   title="Descargar PDF A4">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                <a href="{{ route('logistica.autoconsumo_ticket', ['id' => $ac->id_autoconsumo]) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-warning"
                                   title="Descargar Ticket">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
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

    <script>
        document.addEventListener('livewire:init', () => {
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
        });
    </script>

</div>
