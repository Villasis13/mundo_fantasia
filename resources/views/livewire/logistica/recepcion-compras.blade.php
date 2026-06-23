<div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
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

    {{-- ══════════════════════════════════════════════════════
         VISTA: LISTA DE ÓRDENES EN TRÁNSITO
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'lista')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="mb-2">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-warehouse me-2 text-warning"></i>
                    Recepción de Almacén
                </h5>
                <small class="text-muted">Órdenes en tránsito listas para recepcionar.</small>
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-2">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                @if($esSuperAdmin)
                <div class="col-auto">
                    <select wire:model.live="filtroEmpresa" class="form-select form-select-sm" style="min-width:170px;">
                        <option value="0">— Todas las empresas —</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($sucursalesFilter->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroSucursal" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todas las sedes</option>
                        @foreach($sucursalesFilter as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($proveedores->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroProveedor" class="form-select form-select-sm" style="min-width:170px;">
                        <option value="0">Todos los proveedores</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id_proveedores }}">{{ $prov->proveedores_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

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
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:50px;">#</th>
                            <th>N° Orden</th>
                            <th>Proveedor</th>
                            <th>Destino</th>
                            <th>Fecha</th>
                            <th>Documento</th>
                            <th class="text-end">Total</th>
                            <th class="text-center" style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $index => $orden)
                        <tr>
                            <td class="ps-3 text-muted small fw-semibold">
                                {{ $ordenes->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="fw-semibold small">{{ $orden->orden_compra_numero }}</span>
                                @if($orden->orden_compra_estado === 'en_transito')
                                    <span class="badge bg-warning text-dark ms-1 small">En Tránsito</span>
                                @else
                                    <span class="badge bg-secondary ms-1 small">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $orden->proveedores_nombre }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                    {{ $orden->tienda_nombre ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <small>{{ \Carbon\Carbon::parse($orden->orden_compra_fecha)->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                @if($orden->orden_compra_tipo_doc || $orden->orden_compra_numero_doc)
                                    <small class="text-muted">
                                        {{ $orden->orden_compra_tipo_doc }}
                                        {{ $orden->orden_compra_numero_doc }}
                                    </small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">
                                S/ {{ number_format($orden->orden_compra_total ?? 0, 2) }}
                            </td>
                            <td class="text-center">
                                @can('recepcion_almacen.crear')
                                <button class="btn btn-sm btn-success fw-semibold"
                                        wire:click="abrirRecepcion({{ $orden->id_orden_compra }})"
                                        wire:loading.attr="disabled"
                                        wire:target="abrirRecepcion({{ $orden->id_orden_compra }})"
                                        title="Recepcionar en almacén">
                                    <span wire:loading.remove wire:target="abrirRecepcion({{ $orden->id_orden_compra }})">
                                        <i class="fa-solid fa-warehouse me-1"></i> Recepcionar
                                    </span>
                                    <span wire:loading wire:target="abrirRecepcion({{ $orden->id_orden_compra }})">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-truck fa-2x mb-2 d-block opacity-25"></i>
                                    No hay órdenes pendientes de recepción.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ordenes->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $ordenes->firstItem() }}–{{ $ordenes->lastItem() }}
                    de {{ $ordenes->total() }} órdenes
                </small>
                {{ $ordenes->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         VISTA: FORMULARIO DE RECEPCIÓN
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'recibir' && $ordenDetalle)

    {{-- Barra superior --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light border" wire:click="volverLista"
                    wire:loading.attr="disabled" wire:target="volverLista">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </button>
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-warehouse me-2 text-warning"></i>
                Recepcionar Orden — {{ $ordenDetalle->orden_compra_numero }}
            </h5>
        </div>
        @can('recepcion_almacen.crear')
        <button class="btn btn-success fw-semibold px-4"
                wire:click="confirmarRecepcion"
                wire:loading.attr="disabled"
                wire:target="confirmarRecepcion">
            <span wire:loading.remove wire:target="confirmarRecepcion">
                <i class="fa-solid fa-check me-1"></i> Confirmar Recepción
            </span>
            <span wire:loading wire:target="confirmarRecepcion">
                <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
            </span>
        </button>
        @endcan
    </div>

    <div class="row g-3">

        {{-- ── Info de la orden ── --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">N° Orden</small>
                            <span class="fw-semibold">{{ $ordenDetalle->orden_compra_numero }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Proveedor</small>
                            <span class="fw-semibold">{{ $ordenDetalle->proveedores_nombre }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Sede Destino</small>
                            <span class="fw-semibold">{{ $ordenDetalle->tienda_nombre ?? '—' }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Total Orden</small>
                            <span class="fw-semibold">S/ {{ number_format($ordenDetalle->orden_compra_total ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Selector de almacén ── --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted small text-uppercase mb-2">
                        <i class="fa-solid fa-warehouse me-1"></i> Almacén de Destino
                    </h6>

                    <select wire:model="idAlmacenSeleccionado"
                            class="form-select @error('almacen') is-invalid @enderror">
                        <option value="0">— Seleccionar almacén —</option>
                        @foreach($almacenesDisponibles as $alm)
                            <option value="{{ $alm->id_almacen }}">
                                {{ $alm->almacen_nombre }}{{ $alm->almacen_direccion ? ' — ' . $alm->almacen_direccion : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('almacen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($idAlmacenSeleccionado)
                    @php $almSel = $almacenesDisponibles->firstWhere('id_almacen', $idAlmacenSeleccionado); @endphp
                    @if($almSel?->almacen_direccion)
                    <small class="text-muted d-block mt-1">
                        <i class="fa-solid fa-location-dot me-1"></i>{{ $almSel->almacen_direccion }}
                    </small>
                    @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Tabla de productos ── --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Productos de la Orden
                    </h6>

                    @error('cantidades')
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $message }}
                        </div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="encabezado_tabla_color">
                                    <th style="width:40%">Producto</th>
                                    <th style="width:12%">Código</th>
                                    <th class="text-end" style="width:13%">Cant. Pedida</th>
                                    <th class="text-end" style="width:13%">P. Compra</th>
                                    <th style="width:15%">Cant. Recibida</th>
                                    <th class="text-end" style="width:7%">Dif.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleItems as $item)
                                @php
                                    $cantPedida   = (float) $item->detalle_compra_cantidad;
                                    $cantRecibida = (float) ($cantidadesRecibidas[$item->id_detalle_compra] ?? 0);
                                    $diferencia   = $cantRecibida - $cantPedida;
                                @endphp
                                <tr wire:key="item-{{ $item->id_detalle_compra }}">
                                    <td>
                                        <span class="fw-semibold d-block small">{{ $item->pro_nombre }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $item->pro_codigo }}</small>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($cantPedida, 2) }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        S/ {{ number_format($item->detalle_compra_precio_compra, 2) }}
                                    </td>
                                    <td>
                                        <input type="number"
                                               wire:model.live="cantidadesRecibidas.{{ $item->id_detalle_compra }}"
                                               class="form-control form-control-sm text-end"
                                               min="0" step="0.01">
                                    </td>
                                    <td class="text-end fw-semibold small">
                                        @if($cantRecibida > 0)
                                            <span class="{{ $diferencia < 0 ? 'text-danger' : ($diferencia > 0 ? 'text-primary' : 'text-success') }}">
                                                {{ $diferencia >= 0 ? '+' : '' }}{{ number_format($diferencia, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /row --}}
    @endif

    <div wire:loading wire:target="abrirRecepcion, volverLista, confirmarRecepcion">
        <x-loader />
    </div>

</div>
