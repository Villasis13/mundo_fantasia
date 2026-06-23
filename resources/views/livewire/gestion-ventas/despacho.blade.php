<div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Detalle de Venta
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleDespacho" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div style="height:4px" class="bg-primary"></div>
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="fa-solid fa-receipt me-2 text-primary"></i>Detalle de Venta
                        </h6>
                        <div class="d-flex gap-3 mt-1 flex-wrap">
                            <small class="text-muted">
                                <i class="fa-solid fa-hashtag me-1"></i>Pedido: <span class="fw-semibold text-dark">{{ $detallePedido }}</span>
                            </small>
                            <small class="text-muted">
                                <i class="fa-solid fa-file-invoice me-1"></i>Comprobante: <span class="fw-semibold text-dark">{{ $detalleNumero }}</span>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-3 pb-2">
                    @if(count($detalleItems) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2">#</th>
                                    <th class="py-2">Producto</th>
                                    <th class="py-2">Código</th>
                                    <th class="text-center py-2">Cantidad</th>
                                    <th class="text-end py-2">P. Unitario</th>
                                    <th class="text-end py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleItems as $idx => $di)
                                <tr>
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold">{{ $di->pro_nombre }}</td>
                                    <td><small class="text-muted">{{ $di->pro_codigo ?? '—' }}</small></td>
                                    <td class="text-center fw-bold">{{ number_format((float)$di->cantidad, 2) }}</td>
                                    <td class="text-end">S/ {{ number_format((float)$di->precio_unitario, 2) }}</td>
                                    <td class="text-end fw-bold text-primary">S/ {{ number_format((float)$di->cantidad * (float)$di->precio_unitario, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">Total:</td>
                                    <td class="text-end text-primary">S/ {{ number_format($detalleTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-inbox fa-lg d-block mb-2 opacity-25"></i>
                        <small>Sin productos.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Confirmar Despacho
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDespacho" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px" class="bg-success"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1"></button>
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success mb-3"
                         style="width:76px;height:76px">
                        <i class="fa-solid fa-truck-fast fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Confirmar despacho?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem">
                        Se descontará el stock de los productos del pedido.<br>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm fw-semibold px-4"
                            wire:click="despachar" wire:loading.attr="disabled" wire:target="despachar">
                        <span wire:loading.remove wire:target="despachar">
                            <i class="fa-solid fa-truck-fast me-1"></i> Sí, despachar
                        </span>
                        <span wire:loading wire:target="despachar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Despachando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TABLA PRINCIPAL
    ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-truck-fast me-2 text-success"></i>
                        Despacho de Pedidos
                    </h5>
                    <small class="text-muted">Ventas pagadas pendientes de despacho (descuento de stock).</small>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3 flex-wrap">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto">
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
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="buscar"
                           placeholder="Buscar por pedido, cliente..."
                           style="min-width:220px">
                </div>
            </div>
        </div>

        {{-- Alertas --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:50px">#</th>
                            <th>Nro Pedido</th>
                            <th>Nro Comprobante</th>
                            <th>Cliente</th>
                            <th>Tienda</th>
                            <th class="text-end">Total</th>
                            <th>Fecha Venta</th>
                            <th class="text-center" style="width:110px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $idx => $venta)
                        <tr>
                            <td class="ps-3 text-muted small fw-semibold">{{ $ventas->firstItem() + $idx }}</td>
                            <td class="fw-semibold small">{{ $venta->pedido_numero }}</td>
                            <td class="small">
                                {{ $venta->venta_serie }}-{{ $venta->venta_correlativo }}
                            </td>
                            <td>
                                <div class="fw-semibold small">
                                    @if($venta->id_tipo_documento == 4)
                                        {{ $venta->cliente_razonsocial }}
                                    @else
                                        {{ $venta->cliente_nombre }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($venta->tienda_nombre)
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                        <i class="fa-solid fa-store me-1" style="font-size:.6rem;"></i>{{ $venta->tienda_nombre }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">
                                S/ {{ number_format($venta->venta_total, 2) }}
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($venta->venta_fecha)->format('d/m/Y H:i') }}</small></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary"
                                            wire:click="verDetalle({{ $venta->id_venta }})"
                                            title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('despacho.crear')
                                    <button class="btn btn-sm btn-success"
                                            wire:click="confirmarDespacho({{ $venta->id_pedido }})"
                                            title="Despachar pedido">
                                        <i class="fa-solid fa-truck-fast me-1"></i> Despachar
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-truck-fast fa-2x d-block mb-2 opacity-25"></i>
                                No hay pedidos pendientes de despacho.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ventas->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $ventas->firstItem() }}–{{ $ventas->lastItem() }}
                    de {{ $ventas->total() }} registros
                </small>
                {{ $ventas->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>

    <div wire:loading wire:target="despachar, confirmarDespacho">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModalDetalleDespacho', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleDespacho')).show();
    });

    $wire.on('abrirModalDespacho', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDespacho')).show();
    });
    $wire.on('cerrarModalDespacho', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalDespacho'));
        if (m) m.hide();
    });

    document.getElementById('modalDespacho')
        .addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
</script>
@endscript
