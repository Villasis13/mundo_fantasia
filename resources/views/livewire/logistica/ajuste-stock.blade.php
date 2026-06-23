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
         MODAL — Detalle de Inventario
    ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleInventario" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                        Detalle de Inventario
                        @if($detalleInventario)
                            <span class="fw-normal text-muted small ms-2">— {{ $detalleInventario->inventario_numero }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($detalleInventario)
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ubicación</small>
                            <strong>{{ $detalleInventario->ubicacion_nombre }}</strong>
                            @if($detalleInventario->empresa_nombre)
                                <small class="text-muted d-block">{{ $detalleInventario->empresa_nombre }}</small>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Fecha</small>
                            <strong>{{ \Carbon\Carbon::parse($detalleInventario->inventario_fecha)->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Creado por</small>
                            <strong>{{ $detalleInventario->nombre_users }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Estado</small>
                            <span class="badge {{ $detalleInventario->inventario_estado === 'confirmado' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $detalleInventario->inventario_estado === 'confirmado' ? 'Confirmado' : 'Borrador' }}
                            </span>
                        </div>
                    </div>

                    @php
                        $detSob = $detalleItems->filter(fn($i) => (float)$i->diferencia > 0);
                        $detFal = $detalleItems->filter(fn($i) => (float)$i->diferencia < 0);
                    @endphp

                    @if($detSob->isNotEmpty() || $detFal->isNotEmpty())
                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        @if($detSob->isNotEmpty())
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                            <i class="fa-solid fa-arrow-up me-1"></i> {{ $detSob->count() }} sobrante(s)
                        </span>
                        @endif
                        @if($detFal->isNotEmpty())
                        <span class="badge bg-danger fs-6 px-3 py-2">
                            <i class="fa-solid fa-arrow-down me-1"></i> {{ $detFal->count() }} faltante(s)
                        </span>
                        @endif
                    </div>
                    @endif

                    @if($detalleInventario->inventario_estado === 'borrador' && $detFal->isNotEmpty())
                    <div class="alert alert-warning py-2 small mb-3 d-flex gap-2 align-items-start">
                        <i class="fa-solid fa-triangle-exclamation flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Faltantes:</strong> Los productos marcados en rojo tienen menos stock del registrado en el sistema.
                            Deben regularizarse mediante un <strong>comprobante de venta</strong>.
                            Al confirmar, solo se crearán movimientos de ingreso para los sobrantes.
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-dark encabezado_tabla_color">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th class="text-end" style="width:115px">Stock Sistema</th>
                                    <th class="text-end" style="width:115px">Stock Contado</th>
                                    <th class="text-center" style="width:100px">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleItems as $idx => $item)
                                @php $dif = (float) $item->diferencia; @endphp
                                <tr class="{{ $dif > 0 ? 'table-warning' : ($dif < 0 ? 'table-danger' : '') }}">
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold">{{ $item->pro_nombre }}</td>
                                    <td class="text-muted small">{{ $item->pro_codigo }}</td>
                                    <td class="text-end">{{ number_format($item->stock_sistema, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->stock_contado, 2) }}</td>
                                    <td class="text-center fw-semibold small">
                                        @if($dif > 0)
                                            <span class="text-warning-emphasis">+{{ number_format($dif, 2) }}</span>
                                        @elseif($dif < 0)
                                            <span class="text-danger">{{ number_format($dif, 2) }}</span>
                                        @else
                                            <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <span class="badge bg-warning text-dark border me-1">Amarillo</span> Sobrante &nbsp;
                            <span class="badge bg-danger me-1">Rojo</span> Faltante &nbsp;
                            <span class="badge bg-light text-dark border me-1">Sin color</span> Sin diferencia
                        </small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    @can('ajuste_stock.exportar')
                    @if($detalleInventario)
                    <button type="button" class="btn btn-outline-success fw-semibold"
                            wire:click="exportarExcel({{ $detalleInventario->id_inventario }})"
                            wire:loading.attr="disabled" wire:target="exportarExcel({{ $detalleInventario->id_inventario }})">
                        <span wire:loading.remove wire:target="exportarExcel({{ $detalleInventario->id_inventario }})">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar
                        </span>
                        <span wire:loading wire:target="exportarExcel({{ $detalleInventario->id_inventario }})">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endif
                    @endcan
                    @if($detalleInventario && $detalleInventario->inventario_estado === 'borrador')
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="confirmarInventario({{ $detalleInventario->id_inventario }})"
                            wire:loading.attr="disabled"
                            wire:target="confirmarInventario({{ $detalleInventario->id_inventario }})">
                        <span wire:loading.remove wire:target="confirmarInventario({{ $detalleInventario->id_inventario }})">
                            <i class="fa-solid fa-check me-1"></i> Confirmar Inventario
                        </span>
                        <span wire:loading wire:target="confirmarInventario({{ $detalleInventario->id_inventario }})">
                            <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Procesando...
                        </span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: NUEVO INVENTARIO
    ══════════════════════════════════════════════════════════════ --}}
    @if($vista === 'nuevo')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                    Nueva Toma de Inventario
                </h5>
                <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </button>
            </div>
        </div>
        <div class="card-body">

            {{-- Selector de ubicación --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-warehouse me-1 text-primary"></i>
                        Almacén / Sede <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="ubicacionKey"
                            class="form-select @error('ubicacionKey') is-invalid @enderror">
                        <option value="">— Seleccione ubicación —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}">
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresas as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('ubicacionKey') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if(str_starts_with($ubicacionKey, 'empresa_'))
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Sede <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="idTienda"
                            class="form-select"
                            {{ $sedes->isEmpty() ? 'disabled' : '' }}>
                        <option value="0">— Seleccione sede —</option>
                        @foreach($sedes as $sede)
                        <option value="{{ $sede->id_tienda }}">{{ $sede->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- Buscador de productos --}}
            <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Productos a Inventariar
                    </h6>

                    @php $ubicOk = str_starts_with($ubicacionKey, 'almacen_') || $idTienda > 0; @endphp

                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control bg-white"
                                   wire:model.live.debounce.300ms="buscarProducto"
                                   placeholder="{{ $ubicOk ? 'Escribe nombre o código para buscar...' : 'Primero configura la ubicación' }}"
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
                                 wire:click="agregarProducto({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', '{{ $prod->pro_codigo }}', {{ $prod->stock_actual }})"
                                 wire:key="res-{{ $prod->id_pro }}">
                                <div>
                                    <span class="fw-semibold d-block">{{ $prod->pro_nombre }}</span>
                                    <small class="text-muted">{{ $prod->pro_codigo }}</small>
                                </div>
                                <div class="text-end ms-3 flex-shrink-0">
                                    <small class="text-primary fw-semibold d-block">
                                        Stock: {{ number_format($prod->stock_actual, 2) }}
                                    </small>
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
                                No se encontraron productos para la búsqueda.
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
                                    <th class="text-end" style="width:130px">Stock Sistema</th>
                                    <th style="width:170px">Cantidad Contada</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $idx => $item)
                                <tr wire:key="item-{{ $idx }}">
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold d-block small">{{ $item['nombre'] }}</span>
                                        <small class="text-muted">{{ $item['codigo'] }}</small>
                                    </td>
                                    <td class="text-end text-muted fw-semibold">
                                        {{ number_format($item['stock_actual'], 2) }}
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                               wire:model.live="items.{{ $idx }}.cantidad"
                                               class="form-control form-control-sm text-end"
                                               placeholder="0.00">
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
                        <i class="fa-solid fa-boxes-stacked fa-2x opacity-25 d-block mb-2"></i>
                        <small>Busca y agrega los productos a inventariar.</small>
                    </div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" wire:click="volverHistorial">Cancelar</button>
                <button type="button" class="btn btn-primary fw-semibold"
                        wire:click="guardar"
                        wire:loading.attr="disabled" wire:target="guardar"
                        {{ (!$ubicOk || empty($items)) ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="guardar">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Inventario
                    </span>
                    <span wire:loading wire:target="guardar">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;" role="status"></span> Guardando...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: REVISIÓN — Sobrantes y Faltantes
    ══════════════════════════════════════════════════════════════ --}}
    @elseif($vista === 'revision')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-magnifying-glass-chart me-2 text-primary"></i>
                        Revisión de Inventario
                        @if($revisionInventario)
                            <span class="fw-normal text-muted ms-2 small">— {{ $revisionInventario->inventario_numero }}</span>
                        @endif
                    </h5>
                    @if($revisionInventario)
                    <small class="text-muted">
                        {{ $revisionInventario->ubicacion_nombre }}
                        @if($revisionInventario->empresa_nombre)
                            — {{ $revisionInventario->empresa_nombre }}
                        @endif
                        &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
                    </small>
                    @endif
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @php
                        $cntSob = $revisionItems->filter(fn($i) => (float)$i->diferencia > 0)->count();
                        $cntFal = $revisionItems->filter(fn($i) => (float)$i->diferencia < 0)->count();
                    @endphp
                    @if($cntSob > 0)
                        <span class="badge bg-warning text-dark px-3 py-2">
                            <i class="fa-solid fa-arrow-up me-1"></i> {{ $cntSob }} sobrante(s)
                        </span>
                    @endif
                    @if($cntFal > 0)
                        <span class="badge bg-danger px-3 py-2">
                            <i class="fa-solid fa-arrow-down me-1"></i> {{ $cntFal }} faltante(s)
                        </span>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                        <i class="fa-solid fa-list me-1"></i> Historial
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">

            @if($cntFal > 0)
            <div class="alert alert-warning d-flex gap-2 align-items-start py-2 mb-3">
                <i class="fa-solid fa-triangle-exclamation flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Productos con faltante ({{ $cntFal }}):</strong>
                    Los productos marcados en <strong>rojo</strong> tienen menos stock del registrado en el sistema.
                    Deben regularizarse mediante un <strong>comprobante de venta</strong>.
                    Al confirmar, solo se crearán movimientos de ingreso para los sobrantes.
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-dark encabezado_tabla_color">
                        <tr>
                            <th style="width:32px">#</th>
                            <th>Producto</th>
                            <th>Código</th>
                            <th class="text-end" style="width:120px">Stock Sistema</th>
                            <th class="text-end" style="width:120px">Stock Contado</th>
                            <th class="text-center" style="width:100px">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($revisionItems as $i => $item)
                        @php $dif = (float) $item->diferencia; @endphp
                        <tr class="{{ $dif > 0 ? 'table-warning' : ($dif < 0 ? 'table-danger' : '') }}">
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $item->pro_nombre }}</td>
                            <td class="text-muted small">{{ $item->pro_codigo }}</td>
                            <td class="text-end">{{ number_format($item->stock_sistema, 2) }}</td>
                            <td class="text-end">{{ number_format($item->stock_contado, 2) }}</td>
                            <td class="text-center fw-semibold small">
                                @if($dif > 0)
                                    <span class="text-warning-emphasis">+{{ number_format($dif, 2) }}</span>
                                @elseif($dif < 0)
                                    <span class="text-danger">{{ number_format($dif, 2) }}</span>
                                @else
                                    <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay productos en este inventario.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                <small class="text-muted">
                    <span class="badge bg-warning text-dark border me-1">Amarillo</span> Sobrante &nbsp;
                    <span class="badge bg-danger me-1">Rojo</span> Faltante &nbsp;
                    <span class="badge bg-light text-dark border me-1">Sin color</span> Sin diferencia
                </small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="volverHistorial">
                        <i class="fa-solid fa-list me-1"></i> Ver Historial
                    </button>
                    @can('ajuste_stock.exportar')
                    @if($idInventarioActivo)
                    <button type="button" class="btn btn-outline-success fw-semibold"
                            wire:click="exportarExcel({{ $idInventarioActivo }})"
                            wire:loading.attr="disabled" wire:target="exportarExcel({{ $idInventarioActivo }})">
                        <span wire:loading.remove wire:target="exportarExcel({{ $idInventarioActivo }})">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar
                        </span>
                        <span wire:loading wire:target="exportarExcel({{ $idInventarioActivo }})">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endif
                    @endcan
                    @if($idInventarioActivo && $revisionInventario && $revisionInventario->inventario_estado === 'borrador')
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="confirmarInventario({{ $idInventarioActivo }})"
                            wire:loading.attr="disabled"
                            wire:target="confirmarInventario({{ $idInventarioActivo }})">
                        <span wire:loading.remove wire:target="confirmarInventario({{ $idInventarioActivo }})">
                            <i class="fa-solid fa-check me-1"></i> Confirmar Inventario
                        </span>
                        <span wire:loading wire:target="confirmarInventario({{ $idInventarioActivo }})">
                            <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Procesando...
                        </span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VISTA: HISTORIAL DE INVENTARIOS
    ══════════════════════════════════════════════════════════════ --}}
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                        Inventario de Stock
                    </h5>
                    <small class="text-muted">Historial de tomas de inventario.</small>
                </div>
                <button class="btn btn-primary fw-semibold" wire:click="nuevaInventario"
                        wire:loading.attr="disabled" wire:target="nuevaInventario">
                    <span wire:loading.remove wire:target="nuevaInventario">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Inventario
                    </span>
                    <span wire:loading wire:target="nuevaInventario">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Cargando...
                    </span>
                </button>
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
                            <th>N° Inventario</th>
                            <th>Ubicación</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Sobrantes</th>
                            <th class="text-center">Faltantes</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:120px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventarios as $index => $inv)
                        <tr>
                            <td class="ps-3 text-muted fw-semibold">{{ $inventarios->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $inv->inventario_numero }}</td>
                            <td>
                                <span class="d-block">{{ $inv->ubicacion_nombre }}</span>
                                @if($inv->empresa_nombre)
                                    <small class="text-muted">{{ $inv->empresa_nombre }}</small>
                                @endif
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($inv->inventario_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-muted small">{{ $inv->nombre_users }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $inv->total_productos }}</span>
                            </td>
                            <td class="text-center">
                                @if($inv->total_sobrantes > 0)
                                    <span class="badge bg-warning text-dark">{{ $inv->total_sobrantes }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($inv->total_faltantes > 0)
                                    <span class="badge bg-danger">{{ $inv->total_faltantes }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $inv->inventario_estado === 'confirmado' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $inv->inventario_estado === 'confirmado' ? 'Confirmado' : 'Borrador' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-sm btn-info"
                                            wire:click="verDetalle({{ $inv->id_inventario }})"
                                            wire:loading.attr="disabled"
                                            wire:target="verDetalle({{ $inv->id_inventario }})"
                                            title="Ver detalle">
                                        <span wire:loading.remove wire:target="verDetalle({{ $inv->id_inventario }})">
                                            <i class="fa-solid fa-eye"></i>
                                        </span>
                                        <span wire:loading wire:target="verDetalle({{ $inv->id_inventario }})">
                                            <span class="spinner-border spinner-border-sm"></span>
                                        </span>
                                    </button>
                                    @can('ajuste_stock.exportar')
                                    <button class="btn btn-sm btn-outline-success"
                                            wire:click="exportarExcel({{ $inv->id_inventario }})"
                                            wire:loading.attr="disabled"
                                            wire:target="exportarExcel({{ $inv->id_inventario }})"
                                            title="Exportar Excel">
                                        <span wire:loading.remove wire:target="exportarExcel({{ $inv->id_inventario }})">
                                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:14px;height:14px;vertical-align:middle;">
                                        </span>
                                        <span wire:loading wire:target="exportarExcel({{ $inv->id_inventario }})">
                                            <span class="spinner-border spinner-border-sm"></span>
                                        </span>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa-solid fa-clipboard-list fa-2x mb-2 d-block opacity-25"></i>
                                No hay inventarios en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($inventarios->hasPages())
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $inventarios->firstItem() }}–{{ $inventarios->lastItem() }}
                    de {{ $inventarios->total() }} registros
                </small>
                {{ $inventarios->links() }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('abrirModalDetalleInventario', () => {
                new bootstrap.Modal(document.getElementById('modalDetalleInventario')).show();
            });
            Livewire.on('cerrarModalDetalleInventario', () => {
                bootstrap.Modal.getInstance(document.getElementById('modalDetalleInventario'))?.hide();
            });
        });
    </script>

</div>
