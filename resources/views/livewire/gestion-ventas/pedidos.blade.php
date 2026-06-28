<div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Recuperar Pre-venta (F9)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalRecuperar" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3" style="background:#fffbea">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-rotate-left me-2 text-warning"></i>
                        Recuperar Pre-venta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                @if($modoPasswordRecuperar)
                {{-- ── Paso 2: confirmar contraseña ── --}}
                <div class="modal-body py-4 text-center">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning mb-3"
                              style="width:64px;height:64px">
                            <i class="fa-solid fa-lock fa-xl text-white"></i>
                        </span>
                        <h6 class="fw-bold mb-1">Confirmar identidad</h6>
                        <p class="text-muted mb-0" style="font-size:.87rem">
                            Ingresa tu contraseña para continuar
                        </p>
                        <div class="fw-semibold mt-1">{{ auth()->user()->nombre_users }}</div>
                    </div>
                    <div class="mx-auto" style="max-width:300px">
                        <input type="password" id="input-clave-recuperar"
                               class="form-control text-center @if($errorClaveRecuperar) is-invalid @endif"
                               wire:model="claveRecuperar"
                               placeholder="Contraseña..."
                               wire:keydown.enter="confirmarRecuperar"
                               autocomplete="current-password">
                        @if($errorClaveRecuperar)
                        <div class="invalid-feedback d-block">{{ $errorClaveRecuperar }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-top py-2">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            wire:click="cancelarPasswordRecuperar">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver
                    </button>
                    <button type="button" class="btn btn-warning fw-semibold px-4"
                            wire:click="confirmarRecuperar"
                            wire:loading.attr="disabled" wire:target="confirmarRecuperar">
                        <span wire:loading wire:target="confirmarRecuperar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-unlock me-1" wire:loading.remove wire:target="confirmarRecuperar"></i>
                        Confirmar y recuperar
                    </button>
                </div>

                @else
                {{-- ── Paso 1: lista + detalle ── --}}
                <div class="modal-body p-0 d-flex flex-column" style="height:72vh;min-height:0"
                     x-data="{ buscarRec: '' }">

                    {{-- Buscador --}}
                    <div class="px-3 py-2 border-bottom bg-white">
                        <div class="input-group input-group-sm" style="max-width:280px">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:.8rem"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-1"
                                   x-model="buscarRec"
                                   placeholder="Buscar por N° pedido..."
                                   id="input-buscar-recuperar"
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button"
                                    x-show="buscarRec" @click="buscarRec = ''" style="display:none">
                                <i class="fa-solid fa-xmark" style="font-size:.8rem"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Lista de pedidos (mitad superior) --}}
                    <div class="overflow-auto border-bottom" style="flex:1;min-height:0">
                        <table class="table table-hover table-sm align-middle mb-0" style="font-size:.85rem">
                            <thead style="position:sticky;top:0;z-index:1;background:#495057;color:#fff">
                                <tr>
                                    <th class="ps-3" style="width:110px">N° Pedido</th>
                                    <th>Cliente</th>
                                    <th class="text-center" style="width:90px">Tipo doc.</th>
                                    <th style="width:130px">Fecha</th>
                                    <th style="width:150px">Vendedor</th>
                                    <th class="text-end" style="width:100px">Total</th>
                                    <th style="width:90px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pedidosRecuperables as $pr)
                                @php $numSufijo = ltrim(substr($pr->pedido_numero, strrpos($pr->pedido_numero, '-') + 1), '0') ?: '0'; @endphp
                                <tr x-show="!buscarRec ||
                                            '{{ strtolower($pr->pedido_numero) }}'.includes(buscarRec.toLowerCase()) ||
                                            '{{ $numSufijo }}'.startsWith(buscarRec.replace(/^0+/,'') || '0')">
                                    <td class="ps-3 fw-bold">{{ substr($pr->pedido_numero, strrpos($pr->pedido_numero, '-') + 1) }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $pr->pedido_cliente_nombre ?? 'Sin cliente' }}</div>
                                        @if($pr->pedido_cliente_doc)
                                        <small class="text-muted">{{ $pr->pedido_cliente_doc }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pr->pedido_tipo_comprobante === '01')
                                            <span class="badge bg-success">Factura</span>
                                        @elseif($pr->pedido_tipo_comprobante === '20')
                                            <span class="badge bg-secondary">N.Venta</span>
                                        @else
                                            <span class="badge bg-info text-dark">Boleta</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($pr->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $pr->nombre_users }}</td>
                                    <td class="text-end fw-bold text-primary">
                                        S/ {{ number_format((float)($pr->total_pedido ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm fw-semibold px-3"
                                                wire:click="seleccionarRecuperable({{ $pr->id_pedido }})"
                                                wire:loading.attr="disabled" wire:target="seleccionarRecuperable({{ $pr->id_pedido }})">
                                            <span wire:loading wire:target="seleccionarRecuperable({{ $pr->id_pedido }})">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>
                                            <span wire:loading.remove wire:target="seleccionarRecuperable({{ $pr->id_pedido }})">
                                                <i class="fa-solid fa-pencil"></i> Editar
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-inbox fa-lg d-block mb-2 opacity-25"></i>
                                        No hay pre-ventas pendientes del día.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Detalle del Pedido
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetallePedido" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                        Detalle del Pedido
                        @if($detallePedidoNumero)
                            <span class="text-primary">{{ $detallePedidoNumero }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if(count($detalleItems) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleItems as $idx => $di)
                                <tr>
                                    <td class="text-muted small">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ $di->pro_nombre }}
                                        @if(!empty($di->pres_nombre))
                                        <span class="badge bg-info text-dark fw-normal ms-1" style="font-size:.68rem;">{{ $di->pres_nombre }}</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $di->pro_codigo }}</small></td>
                                    <td class="text-center fw-bold">{{ number_format($di->cantidad, 0) }}</td>
                                    <td class="text-end">S/ {{ number_format($di->precio, 2) }}</td>
                                    <td class="text-end fw-bold text-primary">S/ {{ number_format((float)$di->cantidad * (float)$di->precio, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">Total:</td>
                                    <td class="text-end text-primary">
                                        S/ {{ number_format(array_sum(array_map(fn($d) => (float)$d->cantidad * (float)$d->precio, $detalleItems)), 2) }}
                                    </td>
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
                <div class="modal-footer border-top-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Confirmar Anulación
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAnularPedido" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px" class="bg-danger"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1"></button>
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3"
                         style="width:76px;height:76px">
                        <i class="fa-solid fa-ban fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Anular este pedido?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem">
                        El pedido quedará como <strong>Anulado</strong> y no podrá reactivarse.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold px-4"
                            wire:click="anular" wire:loading.attr="disabled" wire:target="anular">
                        <span wire:loading.remove wire:target="anular">
                            <i class="fa-solid fa-ban me-1"></i> Sí, anular
                        </span>
                        <span wire:loading wire:target="anular">
                            <span class="spinner-border spinner-border-sm me-1"></span> Anulando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         VISTA: HISTORIAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'historial')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
                        Pedidos
                    </h5>
                    <small class="text-muted">Historial de pedidos registrados.</small>
                </div>
                @can('pedidos.crear')
                <button id="btn-nuevo-pedido" class="btn btn-success fw-semibold" wire:click="nuevoPedido">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Pedido
                </button>
                @endcan
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
                    <select wire:model.live="filtroEstado" class="form-select form-select-sm" style="min-width:150px">
                        <option value="">Todos los estados</option>
                        <option value="0">En Caja</option>
                        <option value="1">En Despacho</option>
                        <option value="2">Entregado</option>
                        <option value="3">Anulado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="buscar"
                           placeholder="Buscar por N° pedido o cliente..."
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
                <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="min-width:160px;">Pedido</th>
                            <th style="min-width:180px;">Cliente / Observación</th>
                            <th class="text-center" style="width:80px;">Items</th>
                            <th class="text-end" style="width:110px;">Total</th>
                            <th class="text-center" style="width:120px;">Estado</th>
                            <th class="text-center" style="width:120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pedidos as $idx => $pedido)
                        <tr>
                            {{-- Pedido: # + Nro + Tienda + Fecha --}}
                            <td class="ps-3">
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="text-muted" style="font-size:.72rem;">{{ $pedidos->firstItem() + $idx }}.</span>
                                    <span class="fw-bold">{{ $pedido->pedido_numero }}</span>
                                </div>
                                @if($pedido->tienda_nombre)
                                <div class="mt-1">
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal" style="font-size:.68rem;">
                                        <i class="fa-solid fa-store me-1"></i>{{ $pedido->tienda_nombre }}
                                    </span>
                                </div>
                                @endif
                                <div class="text-muted mt-1" style="font-size:.73rem;">
                                    <i class="fa-regular fa-clock me-1"></i>{{ \Carbon\Carbon::parse($pedido->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </td>

                            {{-- Cliente + Observación --}}
                            <td>
                                @if($pedido->pedido_cliente_nombre)
                                    <div class="fw-semibold text-truncate" style="max-width:200px;" title="{{ $pedido->pedido_cliente_nombre }}">
                                        {{ $pedido->pedido_cliente_nombre }}
                                    </div>
                                    @if($pedido->pedido_cliente_doc)
                                        <div class="text-muted" style="font-size:.75rem;">
                                            <i class="fa-solid fa-id-card me-1 opacity-50"></i>{{ $pedido->pedido_cliente_doc }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted small">Sin cliente</span>
                                @endif
                                @if($pedido->pedido_observacion)
                                    <div class="text-muted fst-italic text-truncate mt-1" style="font-size:.73rem;max-width:200px;" title="{{ $pedido->pedido_observacion }}">
                                        <i class="fa-solid fa-comment-dots me-1 opacity-40"></i>{{ $pedido->pedido_observacion }}
                                    </div>
                                @endif
                            </td>

                            {{-- Items --}}
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary px-2 py-0"
                                        style="font-size:.78rem;"
                                        wire:click="verDetalle({{ $pedido->id_pedido }})"
                                        title="Ver productos del pedido">
                                    <i class="fa-solid fa-boxes-stacked me-1" style="font-size:.68rem;"></i>{{ $pedido->total_items }}
                                </button>
                            </td>

                            {{-- Total --}}
                            <td class="text-end fw-bold text-primary">
                                S/ {{ number_format($pedido->total_monto ?? 0, 2) }}
                            </td>

                            {{-- Estado --}}
                            <td class="text-center">
                                @php
                                    $estadoMap = [
                                        0 => ['label' => 'En Caja',     'class' => 'bg-primary'],
                                        1 => ['label' => 'En Despacho', 'class' => 'bg-warning'],
                                        2 => ['label' => 'Entregado',   'class' => 'bg-success'],
                                        3 => ['label' => 'Anulado',     'class' => 'bg-danger'],
                                    ];
                                    $est = $estadoMap[$pedido->pedido_estado] ?? ['label' => '?', 'class' => 'bg-secondary'];
                                @endphp
                                <span class="badge {{ $est['class'] }} text-white">{{ $est['label'] }}</span>
                            </td>

                            {{-- Acciones --}}
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            wire:click="verDetalle({{ $pedido->id_pedido }})"
                                            title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('pedidos.actualizar')
                                    @if($pedido->pedido_estado == 0)
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="editarPedido({{ $pedido->id_pedido }})"
                                            title="Editar">
                                        <i class="fa-solid fa-pencil text-white"></i>
                                    </button>
                                    @endif
                                    @endcan
                                    <a href="{{ route('Gestionventas.imprimir_ticket_pedido') }}?data={{ $pedido->id_pedido }}"
                                       class="btn btn-sm btn-outline-secondary" title="Imprimir" target="_blank">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    @can('pedidos.cambiar_estado')
                                    @if($pedido->pedido_estado == 0)
                                    <button class="btn btn-sm btn-outline-danger"
                                            wire:click="confirmarAnular({{ $pedido->id_pedido }})"
                                            title="Anular">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-clipboard-list fa-2x d-block mb-2 opacity-25"></i>
                                No se encontraron pedidos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pedidos->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $pedidos->firstItem() }}–{{ $pedidos->lastItem() }}
                    de {{ $pedidos->total() }} pedidos
                </small>
                {{ $pedidos->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         VISTA: NUEVO PEDIDO
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'nuevo')

    {{-- Alertas --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
        <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Banner recuperar --}}
    @if($esRecuperar)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
        <i class="fa-solid fa-rotate-left fa-lg flex-shrink-0"></i>
        <span class="fw-semibold">Recuperando pre-venta</span>
        <span class="text-muted small ms-1">— Edita los productos y datos, luego guarda para actualizar.</span>
    </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>
            @if($esRecuperar)
                Recuperar Pre-venta
            @elseif($idEditar)
                Editar Pedido
            @else
                Nuevo Pedido
            @endif
        </h5>
        <div class="d-flex gap-2 flex-wrap">
            @if($idEditar)
            <button type="button" class="btn btn-warning fw-semibold"
                    wire:click="abrirModalComprobanteEdicion" wire:loading.attr="disabled" wire:target="abrirModalComprobanteEdicion">
                <span wire:loading wire:target="abrirModalComprobanteEdicion">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                </span>
                <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="abrirModalComprobanteEdicion"></i>
                {{ $esRecuperar ? 'Guardar Pre-venta' : 'Actualizar Pedido' }}
            </button>
            @else
            <button type="button" class="btn btn-outline-warning fw-semibold" id="btn-recuperar"
                    wire:click="abrirModalRecuperar" wire:loading.attr="disabled" wire:target="abrirModalRecuperar">
                <span wire:loading wire:target="abrirModalRecuperar">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                </span>
                <i class="fa-solid fa-rotate-left me-1" wire:loading.remove wire:target="abrirModalRecuperar"></i>
                Recuperar
                <kbd class="ms-2" style="font-size:.65rem;background:rgba(0,0,0,.07);color:inherit;border:1px solid rgba(0,0,0,.2);border-radius:3px;padding:0 5px">F9</kbd>
            </button>
            <button type="button" class="btn btn-outline-success fw-semibold" id="btn-guardar-proforma"
                    wire:click="abrirModalProforma" wire:loading.attr="disabled" wire:target="abrirModalProforma">
                <span wire:loading wire:target="abrirModalProforma">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                </span>
                <i class="fa-solid fa-file-contract me-1" wire:loading.remove wire:target="abrirModalProforma"></i>
                Guardar Proforma
                <kbd class="ms-2" style="font-size:.65rem;background:rgba(0,0,0,.07);color:inherit;border:1px solid rgba(0,0,0,.2);border-radius:3px;padding:0 5px">F6</kbd>
            </button>
            <button type="button" class="btn btn-primary fw-semibold" id="btn-guardar-pedido"
                    wire:click="abrirModalComprobante" wire:loading.attr="disabled" wire:target="abrirModalComprobante"
                    style="transition:box-shadow .15s,transform .1s;"
                    onfocus="this.style.boxShadow='0 0 0 4px rgba(11,24,146,.35)';this.style.transform='scale(1.03)';"
                    onblur="this.style.boxShadow='';this.style.transform='';">
                <span wire:loading wire:target="abrirModalComprobante">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                </span>
                <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="abrirModalComprobante"></i>
                Guardar Pedido
                <kbd class="ms-2" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(255,255,255,.4);border-radius:3px;padding:0 5px">F3</kbd>
            </button>
            @endif
        </div>
    </div>

    <div class="row g-3">

        {{-- Buscador de productos / Servicio --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm position-relative">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Productos del Pedido
                    </h6>
                </div>
                <div class="card-body p-4">

                    {{-- Buscador de productos --}}
                    <style>
                        tr.resultado-producto:focus {
                            background-color: #0b1892 !important;
                            color: #fff;
                            outline: none;
                        }
                        tr.resultado-producto:focus .badge {
                            filter: brightness(1.3);
                        }
                    </style>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar Producto
                            <span class="text-muted fw-normal ms-2" style="font-size:.72rem;">
                                ↓ navegar · Enter seleccionar · Esc volver al buscador
                            </span>
                        </label>
                        <input type="text"
                               id="input-buscar-producto"
                               class="form-control"
                               placeholder="Escriba nombre o código del producto..."
                               autocomplete="off">

                        <div class="table-responsive mt-2 border rounded shadow-sm" id="tabla-resultados-wrapper" style="display:none">
                            <table class="table table-hover mb-0" style="font-size:1rem;">
                                <thead class="table-light">
                                    <tr style="font-size:.9rem;">
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-end">Mayorista</th>
                                        <th class="text-end">Público</th>
                                    </tr>
                                </thead>
                                <tbody id="resultados-productos-tbody"></tbody>
                            </table>
                        </div>
                        <div id="sin-resultados-prod" class="mt-2 text-muted small" style="display:none"></div>
                    </div>
                    @error('items')
                    <div class="alert alert-warning py-2 small mb-2">{{ $message }}</div>
                    @enderror

                    {{-- Tabla de items --}}
                    @if(count($items) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:180px">Precio (S/)</th>
                                    <th style="width:110px">Cantidad</th>
                                    <th class="text-end" style="width:110px">Subtotal</th>
                                    <th style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                <tr wire:key="item-{{ $i }}">
                                    <td>
                                        <div class="fw-semibold small">
                                            {{ $item['nombre'] }}
                                            @if(($item['tipo'] ?? 'producto') === 'servicio')
                                            <span class="badge bg-warning text-dark ms-1 fw-normal" style="font-size:.65rem;">Servicio</span>
                                            @endif
                                        </div>
                                        @if(!empty($item['codigo']))
                                        <div class="text-muted" style="font-size:.72rem;">{{ $item['codigo'] }}</div>
                                        @endif
                                        @if(($item['tipo'] ?? 'producto') === 'producto')
                                        <div class="d-flex gap-1 mt-1 flex-wrap">
                                            @if(!empty($item['medida']))
                                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.7rem;">
                                                {{ $item['medida'] }}
                                            </span>
                                            @endif
                                            <span class="badge bg-secondary fw-normal" style="font-size:.7rem;">
                                                Stock: {{ (int)($item['stock'] ?? 0) }}
                                            </span>
                                            <span class="badge bg-success fw-normal" style="font-size:.7rem;">
                                                May: S/ {{ number_format($item['precio_mayorista'] ?? 0, 2) }}
                                            </span>
                                            <span class="badge bg-primary fw-normal" style="font-size:.7rem;">
                                                Púb: S/ {{ number_format($item['precio_publico'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($item['tipo'] ?? 'producto') === 'producto')
                                        <div class="btn-group btn-group-sm w-100 mb-1" role="group">
                                            <button type="button"
                                                    class="btn btn-sm {{ ($item['tipo_precio'] ?? '') === 'mayorista' ? 'btn-success' : 'btn-outline-success' }}"
                                                    wire:click="cambiarTipoPrecio({{ $i }}, 'mayorista')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="cambiarTipoPrecio({{ $i }}, 'mayorista'), cambiarTipoPrecio({{ $i }}, 'publico')">
                                                <span wire:loading.remove wire:target="cambiarTipoPrecio({{ $i }}, 'mayorista')">Mayorista</span>
                                                <span wire:loading wire:target="cambiarTipoPrecio({{ $i }}, 'mayorista')">
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                </span>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm {{ ($item['tipo_precio'] ?? '') === 'publico' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                    wire:click="cambiarTipoPrecio({{ $i }}, 'publico')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="cambiarTipoPrecio({{ $i }}, 'mayorista'), cambiarTipoPrecio({{ $i }}, 'publico')">
                                                <span wire:loading.remove wire:target="cambiarTipoPrecio({{ $i }}, 'publico')">Público</span>
                                                <span wire:loading wire:target="cambiarTipoPrecio({{ $i }}, 'publico')">
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                </span>
                                            </button>
                                        </div>
                                        @endif {{-- fin tipo producto --}}
                                        <input type="number" step="0.01"
                                               wire:key="precio-input-{{ $i }}-{{ $item['tipo_precio'] ?? 'publico' }}"
                                               min="{{ ($item['tipo'] ?? 'producto') === 'producto' && $esRestringido ? ($item['precio_mayorista'] ?? 0) : 0 }}"
                                               class="form-control form-control-sm @error('precio_item_'.$i) is-invalid @enderror"
                                               wire:model.live="items.{{ $i }}.precio">
                                        @error('precio_item_'.$i)
                                            <div class="invalid-feedback d-block" style="font-size:.7rem;">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1"
                                               class="form-control form-control-sm"
                                               wire:model.live="items.{{ $i }}.cantidad">
                                    </td>
                                    <td class="text-end fw-semibold">
                                        S/ {{ number_format((float)($item['precio'] ?? 0) * (float)($item['cantidad'] ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm"
                                                wire:click="quitarItem({{ $i }})">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-primary">
                                        S/ {{ number_format(collect($items)->sum(fn($i) => (float)($i['precio'] ?? 0) * (float)($i['cantidad'] ?? 0)), 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                </div>
            </div>
        </div>


    </div>
    @endif

    {{-- Modal: pedido guardado con éxito --}}
    <div class="modal fade" id="modalExitoPedido" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:340px">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div style="height:5px" class="bg-success"></div>
                <div class="modal-body text-center px-4 pt-4 pb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success mb-3"
                         style="width:72px;height:72px">
                        <i class="fa-solid fa-check fa-2x text-white"></i>
                    </div>
                    <h5 class="fw-bold mb-1">¡Pedido guardado!</h5>
                    <p class="text-muted small mb-2">Número de pedido:</p>
                    <div class="badge bg-primary px-4 py-2 mb-4"
                         style="font-size:1.1rem;letter-spacing:.06em;font-family:monospace">
                        {{ $pedidoGuardadoNumero }}
                    </div>
                    <br>
                    <button type="button" id="btn-nuevo-pedido-exito" class="btn btn-success fw-semibold px-5"
                            wire:click="nuevoRegistro"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-plus me-1"></i>Nuevo Pedido
                        <kbd class="ms-2" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(255,255,255,.4);border-radius:3px;padding:0 5px">Enter</kbd>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="confirmarGuardar, confirmarGuardarProforma, anular, nuevoPedido, volverHistorial">
        <x-loader />
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Buscar/seleccionar/editar cliente
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalBuscarCliente" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        @if($modoEdicionCliente)
                            <i class="fa-solid fa-user-pen me-2 text-warning"></i>Editar Cliente
                        @else
                            <i class="fa-solid fa-users me-2 text-primary"></i>Seleccionar Cliente
                        @endif
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    @if($modoEdicionCliente)
                    {{-- ── Formulario edición ─────────────────────── --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Nombre / Razón Social <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="modal-editar-cliente-nombre"
                               class="form-control @error('editClienteNombre') is-invalid @enderror"
                               wire:model="editClienteNombre"
                               placeholder="Nombre completo o razón social..."
                               autocomplete="off">
                        @error('editClienteNombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">N° Documento</label>
                        <input type="text"
                               class="form-control"
                               wire:model="editClienteDoc"
                               placeholder="DNI (8 dígitos) o RUC (11 dígitos)"
                               maxlength="11"
                               autocomplete="off">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Dirección</label>
                        <input type="text"
                               class="form-control"
                               wire:model="editClienteDireccion"
                               placeholder="Dirección del cliente..."
                               autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-semibold"
                            wire:click="guardarEdicionCliente"
                            wire:loading.attr="disabled" wire:target="guardarEdicionCliente">
                        <span wire:loading wire:target="guardarEdicionCliente">
                            <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                        </span>
                        <span wire:loading.remove wire:target="guardarEdicionCliente">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios
                        </span>
                    </button>
                    @else
                    {{-- ── Lista de búsqueda ──────────────────────── --}}
                    <input type="text" class="form-control mb-3"
                           wire:model.live.debounce.300ms="buscarClienteModal"
                           placeholder="Nombre, apellido o N° documento..."
                           id="input-buscar-cliente-modal"
                           autocomplete="off">
                    <div style="max-height:320px;overflow-y:auto">
                        @if(count($resultadosClientesModal) > 0)
                            @foreach($resultadosClientesModal as $cli)
                            <div class="border rounded p-2 mb-2 d-flex align-items-center gap-2"
                                 style="font-size:.85rem"
                                 onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                                <div style="cursor:pointer;flex:1;min-width:0"
                                     wire:click="seleccionarCliente('{{ addslashes($cli->cliente_nombre) }}', '{{ $cli->cliente_numero ?? '' }}')">
                                    <div class="fw-semibold text-truncate">{{ $cli->cliente_nombre }}</div>
                                    @if($cli->cliente_numero)
                                    <div class="text-muted" style="font-size:.73rem;">
                                        <i class="fa-solid fa-id-card me-1 opacity-50"></i>{{ $cli->cliente_numero }}
                                    </div>
                                    @endif
                                </div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        wire:click="cargarEdicionCliente({{ $cli->id_clientes }})"
                                        title="Editar datos de este cliente">
                                    <i class="fa-solid fa-pen-to-square" style="font-size:.75rem"></i>
                                </button>
                            </div>
                            @endforeach
                        @elseif(strlen($buscarClienteModal) >= 2)
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-user-slash fa-lg d-block mb-2 opacity-25"></i>
                                <small>Sin resultados para "{{ $buscarClienteModal }}".</small>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-magnifying-glass fa-lg d-block mb-2 opacity-25"></i>
                                <small>Escriba al menos 2 caracteres para buscar.</small>
                            </div>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0">
                    @if($modoEdicionCliente)
                    <button type="button" class="btn btn-outline-secondary"
                            wire:click="cancelarEdicionCliente">
                        <i class="fa-solid fa-arrow-left me-1"></i>Cancelar
                    </button>
                    @else
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-arrow-left me-1"></i>Volver
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Modal: guardar pedido (comprobante + datos del cliente) --}}
    <div class="modal fade" id="modalTipoComprobante" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" wire:ignore.self
         x-data="{ tipo: '' }"
         x-on:reset-tipo-modal.window="tipo = ''"
         x-on:restaurar-tipo-modal.window="tipo = $event.detail">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa-solid fa-floppy-disk me-2 text-primary"></i>Guardar Pedido
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="tipo=''"></button>
                </div>
                <div class="modal-body pt-3 pb-4 px-4">

                    {{-- Selector Boleta / Factura --}}
                    <p class="small text-muted mb-2">Selecciona el tipo de comprobante:</p>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="btn-comprobante-boleta"
                                class="btn flex-fill fw-semibold py-3"
                                :class="tipo === '03' ? 'btn-primary' : 'btn-outline-primary'"
                                @click="tipo='03'; $nextTick(() => document.getElementById('modal-cliente-nombre')?.focus())">
                            <i class="fa-solid fa-receipt d-block mb-1" style="font-size:1.25rem"></i>
                            Boleta
                            <kbd class="ms-1" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(0,0,0,.15);border-radius:3px;padding:0 5px">F1</kbd>
                        </button>
                        <button type="button" id="btn-comprobante-factura"
                                class="btn flex-fill fw-semibold py-3"
                                :class="tipo === '01' ? 'btn-success' : 'btn-outline-success'"
                                @click="tipo='01'; $nextTick(() => document.getElementById('modal-cliente-doc')?.focus())">
                            <i class="fa-solid fa-file-invoice d-block mb-1" style="font-size:1.25rem"></i>
                            Factura
                            <kbd class="ms-1" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(0,0,0,.15);border-radius:3px;padding:0 5px">F2</kbd>
                        </button>
                    </div>

                    {{-- Datos del cliente (se muestra al elegir comprobante) --}}
                    <div x-show="tipo !== ''" x-transition.opacity style="display:none">
                        <hr class="my-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <p class="small fw-semibold mb-0"
                               x-text="tipo === '01' ? 'Datos del cliente (obligatorio)' : 'Datos del cliente (opcional)'"></p>
                            <button type="button" class="btn btn-sm btn-outline-success"
                                    @click="$wire.abrirNuevoCliente(tipo)">
                                <i class="fa-solid fa-user-plus me-1"></i>Agregar cliente
                            </button>
                        </div>

                        {{-- Nombre — solo Boleta --}}
                        <div class="mb-3" x-show="tipo === '03'">
                            <label class="form-label small fw-semibold">Nombre</label>
                            <div class="input-group">
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        title="Buscar cliente registrado"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('abrir-buscar-cliente', { detail: { tipo } }))">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                                <input type="text" id="modal-cliente-nombre" class="form-control"
                                       wire:model="clienteNombre"
                                       placeholder="Nombre del cliente (opcional)"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- Razón Social con autocomplete y lupa RUC — solo Factura --}}
                        <div class="mb-4" x-show="tipo === '01'">
                            <label class="form-label small fw-semibold">Razón Social <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       wire:model.live.debounce.300ms="clienteNombre"
                                       placeholder="Escribe nombre, razón social o RUC..."
                                       autocomplete="off">
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        wire:click="buscarRucFactura"
                                        wire:loading.attr="disabled"
                                        wire:target="buscarRucFactura"
                                        title="Buscar RUC en BD y SUNAT (solo 11 dígitos)">
                                    <span wire:loading wire:target="buscarRucFactura">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                    <span wire:loading.remove wire:target="buscarRucFactura">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                </button>
                            </div>
                            @if(count($resultadosClientesFactura) > 0)
                            <div class="border rounded shadow-sm mt-1" style="max-height:200px;overflow-y:auto;background:#fff">
                                @foreach($resultadosClientesFactura as $i => $cli)
                                @php
                                    $cliNombre = ($cli->cliente_razonsocial ?? '') ?: ($cli->cliente_nombre ?? '');
                                    $cliDoc    = $cli->cliente_numero ?? '';
                                    $tieneRuc  = strlen($cliDoc) === 11;
                                @endphp
                                <div class="d-flex align-items-center"
                                     style="font-size:.85rem;border-bottom:1px solid #f0f0f0"
                                     onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                                    <div style="cursor:pointer;flex:1;min-width:0;padding:.45rem .75rem"
                                         wire:click="seleccionarClienteFactura({{ $i }})">
                                        <span class="fw-semibold">{{ $cliNombre }}</span>
                                        @if($cliDoc)
                                        <small class="{{ $tieneRuc ? 'text-success' : 'text-warning' }} fw-semibold ms-2">
                                            {{ $cliDoc }}
                                            @unless($tieneRuc)
                                            <i class="fa-solid fa-triangle-exclamation ms-1" title="DNI — no válido para Factura"></i>
                                            @endunless
                                        </small>
                                        @endif
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-link text-secondary flex-shrink-0 pe-2"
                                            @click.stop="$wire.cargarEdicionCliente({{ $cli->id_clientes }}, tipo)"
                                            title="Editar datos de este cliente">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            @error('errorFactura')
                            <div class="text-danger small mt-1">
                                <i class="fa-solid fa-circle-xmark me-1"></i>{{ $message }}
                            </div>
                            @enderror
                        </div>

                        {{-- Botón guardar --}}
                        <button type="button" id="btn-confirmar-guardar-pedido" class="btn w-100 fw-semibold"
                                :class="tipo === '01' ? 'btn-success' : 'btn-primary'"
                                @click="$wire.confirmarGuardar(tipo)"
                                wire:loading.attr="disabled" wire:target="confirmarGuardar">
                            <span wire:loading wire:target="confirmarGuardar">
                                <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                            </span>
                            <span wire:loading.remove wire:target="confirmarGuardar">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Pedido
                                <kbd class="ms-2" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(255,255,255,.4);border-radius:3px;padding:0 5px">F3</kbd>
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Guardar como Proforma
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalProforma" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa-solid fa-file-contract me-2 text-success"></i>Guardar como Proforma
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3 pb-4 px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Razón Social / Nombre <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="modal-proforma-razon" class="form-control"
                                   wire:model.live.debounce.300ms="proformaRazonSocial"
                                   placeholder="Nombre, razón social o escribe DNI/RUC para buscar..."
                                   autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary"
                                    wire:click="buscarApiProforma"
                                    wire:loading.attr="disabled"
                                    wire:target="buscarApiProforma"
                                    title="Buscar en API por DNI (8 dígitos) o RUC (11 dígitos)">
                                <span wire:loading wire:target="buscarApiProforma">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </span>
                                <span wire:loading.remove wire:target="buscarApiProforma">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                            </button>
                        </div>
                        @if(count($resultadosClientesProforma) > 0)
                        <div class="border rounded shadow-sm mt-1" style="max-height:200px;overflow-y:auto;background:#fff">
                            @foreach($resultadosClientesProforma as $i => $cli)
                            @php
                                $cliNombre = ($cli->cliente_razonsocial ?? '') ?: ($cli->cliente_nombre ?? '');
                                $cliDoc    = $cli->cliente_numero ?? '';
                            @endphp
                            <div class="d-flex align-items-center gap-2"
                                 style="font-size:.85rem;border-bottom:1px solid #f0f0f0;cursor:pointer;padding:.45rem .75rem"
                                 onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''"
                                 wire:click="seleccionarClienteProforma({{ $i }})">
                                <span class="fw-semibold flex-grow-1">{{ $cliNombre }}</span>
                                @if($cliDoc)
                                <small class="text-muted flex-shrink-0">{{ $cliDoc }}</small>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                        @error('errorProforma')
                        <div class="text-danger small mt-1">
                            <i class="fa-solid fa-circle-xmark me-1"></i>{{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Forma de pago</label>
                        <select class="form-select" wire:model="proformaFormaPago">
                            <option value="1">Contado</option>
                            <option value="2">Crédito</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lugar de entrega</label>
                        <input type="text" class="form-control" wire:model="proformaLugarEntrega">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Observaciones</label>
                        <textarea class="form-control" rows="2" wire:model="proformaObservacion"></textarea>
                    </div>
                    <button type="button" id="btn-confirmar-proforma" class="btn btn-success w-100 fw-semibold"
                            wire:click="confirmarGuardarProforma"
                            wire:loading.attr="disabled" wire:target="confirmarGuardarProforma">
                        <span wire:loading wire:target="confirmarGuardarProforma">
                            <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                        </span>
                        <span wire:loading.remove wire:target="confirmarGuardarProforma">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Proforma
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: proforma guardada con éxito --}}
    <div class="modal fade" id="modalExitoProforma" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:340px">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div style="height:5px" class="bg-success"></div>
                <div class="modal-body text-center px-4 pt-4 pb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success mb-3"
                         style="width:72px;height:72px">
                        <i class="fa-solid fa-check fa-2x text-white"></i>
                    </div>
                    <h5 class="fw-bold mb-1">¡Proforma guardada!</h5>
                    <p class="text-muted small mb-2">Número de proforma:</p>
                    <div class="badge bg-success px-4 py-2 mb-4"
                         style="font-size:1.1rem;letter-spacing:.06em;font-family:monospace">
                        {{ $proformaGuardadoNumero }}
                    </div>
                    <br>
                    <button type="button" id="btn-nuevo-pedido-exito-proforma" class="btn btn-success fw-semibold px-5"
                            wire:click="nuevoRegistro"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-plus me-1"></i>Nuevo Pedido
                        <kbd class="ms-2" style="font-size:.65rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(255,255,255,.4);border-radius:3px;padding:0 5px">Enter</kbd>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Seleccionar Presentación
    ═══════════════════════════════════════════════════════════ --}}
    <style>
        .btn-pres {
            background: #fff;
            border: 1.5px solid #dee2e6;
            border-radius: .5rem;
            transition: background .15s, border-color .15s, color .15s;
            cursor: pointer;
        }
        .btn-pres:hover, .btn-pres:focus {
            background: #0b1892;
            border-color: #0b1892;
            color: #fff !important;
            outline: none;
        }
        .btn-pres:hover *,
        .btn-pres:focus * {
            color: #fff !important;
        }
        .btn-pres:hover .badge-pres,
        .btn-pres:focus .badge-pres {
            background: rgba(255,255,255,.25) !important;
            color: #fff !important;
        }
    </style>
    <div class="modal fade" id="modalPresentaciones" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
            <div class="modal-content border-0 shadow-lg">
                <div style="height:4px" class="bg-primary rounded-top"></div>
                <div class="modal-header border-0 pb-1 pt-3 px-4">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>Seleccionar Presentación
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="$set('presentacionesPendientes', [])"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">
                        Este producto tiene varias presentaciones. ¿En qué presentación deseas venderlo?
                    </p>
                    <div class="d-grid gap-2">
                        @foreach($presentacionesPendientes as $pres)
                        <button type="button"
                                class="btn-pres text-start py-3 px-3 w-100"
                                wire:click="seleccionarPresentacion({{ $pres['id_pres'] }})"
                                wire:loading.attr="disabled">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-bold fs-6">{{ $pres['pres_nombre'] }}</span>
                                    @if($pres['pres_abreviatura'])
                                    <span class="text-muted small ms-1">({{ $pres['pres_abreviatura'] }})</span>
                                    @endif
                                    @if($pres['pres_factor'] != 1)
                                    <div class="text-muted" style="font-size:.75rem;">
                                        <i class="fa-solid fa-cubes me-1 opacity-50"></i>
                                        Factor: {{ number_format($pres['pres_factor'], 0) }} unidades
                                    </div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if($pres['pres_precio_1'] > 0)
                                    <div class="badge-pres badge bg-primary fw-normal" style="font-size:.8rem;">
                                        S/ {{ number_format($pres['pres_precio_1'], 2) }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal"
                            wire:click="$set('presentacionesPendientes', [])">
                        <i class="fa-solid fa-xmark me-1"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Agregar Nuevo Cliente
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-user-plus me-2 text-success"></i>Agregar Cliente
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if($nuevoClienteTipo === '01')
                    <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                        Para Factura el N° RUC (11 dígitos) es obligatorio.
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Nombre / Razón Social <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="modal-nuevo-cliente-nombre"
                               class="form-control @error('nuevoClienteNombre') is-invalid @enderror"
                               wire:model="nuevoClienteNombre"
                               placeholder="Nombre completo o razón social..."
                               autocomplete="off">
                        @error('nuevoClienteNombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            N° Documento
                            @if($nuevoClienteTipo === '01')<span class="text-danger">*</span>@endif
                        </label>
                        <input type="text"
                               class="form-control @error('nuevoClienteDoc') is-invalid @enderror"
                               wire:model="nuevoClienteDoc"
                               placeholder="DNI (8 dígitos) o RUC (11 dígitos)"
                               maxlength="11"
                               autocomplete="off">
                        @error('nuevoClienteDoc')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Dirección</label>
                        <input type="text"
                               class="form-control"
                               wire:model="nuevoClienteDireccion"
                               placeholder="Dirección del cliente..."
                               autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-success w-100 fw-semibold"
                            wire:click="guardarNuevoCliente"
                            wire:loading.attr="disabled" wire:target="guardarNuevoCliente">
                        <span wire:loading wire:target="guardarNuevoCliente">
                            <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                        </span>
                        <span wire:loading.remove wire:target="guardarNuevoCliente">
                            <i class="fa-solid fa-user-check me-1"></i>Registrar Cliente
                        </span>
                    </button>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-arrow-left me-1"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@script
<script>
    const modalComprobante  = document.getElementById('modalTipoComprobante');
    const modalExito        = document.getElementById('modalExitoPedido');
    const modalProforma     = document.getElementById('modalProforma');
    const modalExitoProforma = document.getElementById('modalExitoProforma');

    // ── Modal: Comprobante pedido ─────────────────────────────
    $wire.on('abrirModalTipoComprobante', () => {
        window.dispatchEvent(new CustomEvent('reset-tipo-modal'));
        bootstrap.Modal.getOrCreateInstance(modalComprobante).show();
    });

    $wire.on('abrirModalTipoComprobanteEdicion', ({ tipo }) => {
        window.dispatchEvent(new CustomEvent('restaurar-tipo-modal', { detail: tipo ?? '03' }));
        bootstrap.Modal.getOrCreateInstance(modalComprobante).show();
    });

    $wire.on('cerrarModalComprobante', () => {
        const mc = bootstrap.Modal.getInstance(modalComprobante);
        if (mc) mc.hide();
    });

    modalComprobante.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    $wire.on('abrirModalExito', () => {
        const mc = bootstrap.Modal.getInstance(modalComprobante);
        const abrirExito = () => bootstrap.Modal.getOrCreateInstance(modalExito).show();
        if (mc && mc._isShown) {
            modalComprobante.addEventListener('hidden.bs.modal', abrirExito, { once: true });
            mc.hide();
        } else {
            abrirExito();
        }
    });

    modalExito.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    // ── Modal: Proforma ───────────────────────────────────────
    $wire.on('abrirModalProforma', () => {
        bootstrap.Modal.getOrCreateInstance(modalProforma).show();
        $nextTick(() => document.getElementById('modal-proforma-razon')?.focus());
    });

    modalProforma.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    $wire.on('abrirModalExitoProforma', () => {
        const mp = bootstrap.Modal.getInstance(modalProforma);
        const abrirExito = () => bootstrap.Modal.getOrCreateInstance(modalExitoProforma).show();
        if (mp && mp._isShown) {
            modalProforma.addEventListener('hidden.bs.modal', abrirExito, { once: true });
            mp.hide();
        } else {
            abrirExito();
        }
    });

    modalExitoProforma.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    // ── Atajos de teclado ─────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (modalExito.classList.contains('show')) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-nuevo-pedido-exito').click(); }
            return;
        }
        if (modalExitoProforma.classList.contains('show')) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-nuevo-pedido-exito-proforma').click(); }
            return;
        }
        if (modalComprobante.classList.contains('show')) {
            if (e.key === 'F1') { e.preventDefault(); document.getElementById('btn-comprobante-boleta').click(); }
            if (e.key === 'F2') { e.preventDefault(); document.getElementById('btn-comprobante-factura').click(); }
            if (e.key === 'F3') { e.preventDefault(); document.getElementById('btn-confirmar-guardar-pedido').click(); }
            return;
        }
        if (modalProforma.classList.contains('show')) {
            if (e.key === 'F6') { e.preventDefault(); document.getElementById('btn-confirmar-proforma').click(); }
            return;
        }
        if (e.key === 'F3') { e.preventDefault(); $wire.abrirModalComprobante(); return; }
        if (e.key === 'F6') { e.preventDefault(); $wire.abrirModalProforma(); return; }
        if (e.key === 'F9') { e.preventDefault(); $wire.abrirModalRecuperar(); return; }
    });

    // ── Modal buscar cliente ──────────────────────────────────
    const modalBuscarCliente = document.getElementById('modalBuscarCliente');
    let tipoAlBuscarCliente = '';

    // Lupa: cierra el modal comprobante y abre el de búsqueda
    window.addEventListener('abrir-buscar-cliente', (e) => {
        tipoAlBuscarCliente = e.detail?.tipo ?? '';
        const mc = bootstrap.Modal.getInstance(modalComprobante);
        if (mc) {
            modalComprobante.addEventListener('hidden.bs.modal', () => {
                bootstrap.Modal.getOrCreateInstance(modalBuscarCliente).show();
                setTimeout(() => document.getElementById('input-buscar-cliente-modal')?.focus(), 300);
            }, { once: true });
            mc.hide();
        }
    });

    // Al cerrar el modal de búsqueda (por cualquier motivo) regresa al comprobante
    modalBuscarCliente.addEventListener('hidden.bs.modal', () => {
        $wire.cancelarEdicionCliente(); // reset edit state por si se cerró con X en modo edición
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        window.dispatchEvent(new CustomEvent('restaurar-tipo-modal', { detail: tipoAlBuscarCliente }));
        bootstrap.Modal.getOrCreateInstance(modalComprobante).show();
    });

    // Cliente seleccionado desde PHP: cierra el modal de búsqueda (el hidden.bs.modal reabre el comprobante)
    $wire.on('cerrarOffcanvasCliente', () => {
        const mbc = bootstrap.Modal.getInstance(modalBuscarCliente);
        if (mbc) mbc.hide();
    });

    // Edición de cliente disparada desde Factura: si modalBuscarCliente no está abierto, hacer el switch
    $wire.on('abrirEdicionCliente', (params) => {
        const tipo = (params && params.tipo) ? params.tipo : '';

        if (bootstrap.Modal.getInstance(modalBuscarCliente)) {
            // Ya estamos en modalBuscarCliente (Boleta) — Livewire renderizó el form, enfocar
            setTimeout(() => document.getElementById('modal-editar-cliente-nombre')?.focus(), 100);
            return;
        }

        // Contexto Factura: cerrar modalTipoComprobante y abrir modalBuscarCliente
        tipoAlBuscarCliente = tipo;
        const mc = bootstrap.Modal.getInstance(modalComprobante);
        if (mc) {
            modalComprobante.addEventListener('hidden.bs.modal', () => {
                bootstrap.Modal.getOrCreateInstance(modalBuscarCliente).show();
                setTimeout(() => document.getElementById('modal-editar-cliente-nombre')?.focus(), 300);
            }, { once: true });
            mc.hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(modalBuscarCliente).show();
        }
    });

    // ── Modal: Agregar nuevo cliente ──────────────────────────
    const modalNuevoCliente = document.getElementById('modalNuevoCliente');
    let tipoNuevoCliente = '';
    let nuevoClienteGuardadoExito = false;

    $wire.on('abrirModalNuevoCliente', (params) => {
        tipoNuevoCliente = (params && params.tipo) ? params.tipo : '';
        const mc = bootstrap.Modal.getInstance(modalComprobante);
        if (mc && mc._isShown) {
            modalComprobante.addEventListener('hidden.bs.modal', () => {
                bootstrap.Modal.getOrCreateInstance(modalNuevoCliente).show();
                setTimeout(() => document.getElementById('modal-nuevo-cliente-nombre')?.focus(), 300);
            }, { once: true });
            mc.hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(modalNuevoCliente).show();
            setTimeout(() => document.getElementById('modal-nuevo-cliente-nombre')?.focus(), 300);
        }
    });

    $wire.on('cerrarModalNuevoClienteConExito', () => {
        nuevoClienteGuardadoExito = true;
        const mnc = bootstrap.Modal.getInstance(modalNuevoCliente);
        if (mnc) mnc.hide();
    });

    modalNuevoCliente.addEventListener('hidden.bs.modal', () => {
        if (!nuevoClienteGuardadoExito) {
            $wire.cancelarNuevoCliente();
        }
        nuevoClienteGuardadoExito = false;
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        window.dispatchEvent(new CustomEvent('restaurar-tipo-modal', { detail: tipoNuevoCliente }));
        bootstrap.Modal.getOrCreateInstance(modalComprobante).show();
    });

    // ── Modal: Seleccionar Presentación ──────────────────────
    const modalPresentaciones = document.getElementById('modalPresentaciones');

    $wire.on('abrirModalPresentaciones', () => {
        bootstrap.Modal.getOrCreateInstance(modalPresentaciones).show();
    });

    $wire.on('cerrarModalPresentaciones', () => {
        const m = bootstrap.Modal.getInstance(modalPresentaciones);
        if (m) m.hide();
    });

    modalPresentaciones.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        // Enfocar buscador al cerrar sin seleccionar
        $nextTick(() => document.getElementById('input-buscar-producto')?.focus());
    });

    // ── Modal Recuperar ───────────────────────────────────────
    const modalRecuperar = document.getElementById('modalRecuperar');

    $wire.on('abrirModalRecuperar', () => {
        bootstrap.Modal.getOrCreateInstance(modalRecuperar).show();
        setTimeout(() => document.getElementById('input-buscar-recuperar')?.focus(), 350);
    });

    $wire.on('cerrarModalRecuperar', () => {
        const m = bootstrap.Modal.getInstance(modalRecuperar);
        if (m) m.hide();
    });

    $wire.on('enfocarInputClaveRecuperar', () => {
        $nextTick(() => document.getElementById('input-clave-recuperar')?.focus());
    });

    modalRecuperar.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    // ── Otros modales ─────────────────────────────────────────
    $wire.on('abrirModalDetallePedido', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetallePedido')).show();
    });

    $wire.on('abrirModalAnularPedido', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAnularPedido')).show();
    });
    $wire.on('cerrarModalAnularPedido', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalAnularPedido'));
        if (m) m.hide();
    });

    document.getElementById('modalAnularPedido').addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    });

    // ── Búsqueda client-side ──────────────────────────────────
    let PRODS = [];

    $wire.obtenerProductosCache().then(data => { PRODS = data || []; });

    const wrapperProd   = document.getElementById('tabla-resultados-wrapper');
    const tbodyProd     = document.getElementById('resultados-productos-tbody');
    const msgSinProd    = document.getElementById('sin-resultados-prod');

    function esc(s) {
        return (s || '').toString()
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function norm(s) {
        return (s || '').toString().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g,'');
    }

    function renderResultados(termino) {
        const q = norm(termino.trim());
        if (q.length < 2) {
            wrapperProd.style.display = 'none';
            msgSinProd.style.display  = 'none';
            tbodyProd.innerHTML = '';
            return;
        }
        const filtrados = PRODS.filter(p =>
            norm(p.pro_nombre).includes(q) ||
            norm(p.pro_codigo).includes(q) ||
            norm(p.pro_codigo_interno).includes(q)
        ).slice(0, 30);

        if (!filtrados.length) {
            wrapperProd.style.display = 'none';
            msgSinProd.innerHTML = `<i class="fa-solid fa-circle-info me-1"></i>Sin resultados para "${esc(termino)}".`;
            msgSinProd.style.display = '';
            tbodyProd.innerHTML = '';
            return;
        }

        msgSinProd.style.display = 'none';
        tbodyProd.innerHTML = filtrados.map(p => {
            const stock  = parseFloat(p.ps_stock || 0).toFixed(2);
            const mayor  = parseFloat(p.precio_mayorista || 0).toFixed(2);
            const publi  = parseFloat(p.precio_publico  || 0).toFixed(2);
            const medida = esc(p.medida) || '—';
            return `<tr class="resultado-producto" tabindex="0" style="cursor:pointer;outline:none;"
                        data-id="${p.id_pro}"
                        data-nombre="${esc(p.pro_nombre)}"
                        data-mayorista="${p.precio_mayorista || 0}"
                        data-publico="${p.precio_publico || 0}"
                        data-stock="${p.ps_stock || 0}"
                        data-medida="${esc(p.medida)}"
                        data-codigo="${esc(p.pro_codigo)}">
                        <td><div class="fw-bold" style="font-size:1rem;">${esc(p.pro_nombre)}</div></td>
                        <td>${p.pro_codigo ? `<span class="fw-semibold" style="font-size:.9rem;">${esc(p.pro_codigo)}</span>` : ''}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border fw-semibold" style="font-size:.82rem;">${medida}</span></td>
                        <td class="text-center"><span class="badge bg-secondary fw-bold" style="font-size:.88rem;">${stock}</span></td>
                        <td class="text-end"><span class="badge bg-success fw-bold" style="font-size:.88rem;">S/ ${mayor}</span></td>
                        <td class="text-end"><span class="badge bg-primary fw-bold" style="font-size:.88rem;">S/ ${publi}</span></td>
                    </tr>`;
        }).join('');

        wrapperProd.style.display = '';

        tbodyProd.querySelectorAll('tr.resultado-producto').forEach(tr => {
            tr.addEventListener('click', () => {
                $wire.verificarPresentaciones(
                    parseInt(tr.dataset.id),
                    tr.dataset.nombre,
                    parseFloat(tr.dataset.mayorista),
                    parseFloat(tr.dataset.publico),
                    parseFloat(tr.dataset.stock),
                    tr.dataset.medida,
                    tr.dataset.codigo
                );
            });
        });
    }

    const inputBuscador = document.getElementById('input-buscar-producto');
    if (inputBuscador) {
        inputBuscador.addEventListener('input', e => renderResultados(e.target.value));
    }

    // ── Foco en buscador ──────────────────────────────────────
    const enfocarBuscador = () => {
        $nextTick(() => {
            const input = document.getElementById('input-buscar-producto');
            if (input) {
                input.value = '';
                input.focus();
                renderResultados('');
            }
        });
    };

    $wire.on('vistaNuevo',      enfocarBuscador);
    $wire.on('enfocarBuscador', enfocarBuscador);
    $nextTick(enfocarBuscador);

    $wire.on('vistaHistorial', () => {
        const btn = document.getElementById('btn-nuevo-pedido');
        if (btn) btn.focus();
    });

    // ── Navegación por teclado en tabla de productos ──────────
    document.addEventListener('keydown', (e) => {
        const inputProd = document.getElementById('input-buscar-producto');
        if (!inputProd) return;

        const wrapper = document.getElementById('tabla-resultados-wrapper');
        const rows    = wrapper ? Array.from(wrapper.querySelectorAll('tr.resultado-producto')) : [];

        if (document.activeElement === inputProd) {
            if (e.key === 'ArrowDown' && rows.length) {
                e.preventDefault();
                rows[0].focus();
            }
            return;
        }

        const idx = rows.indexOf(document.activeElement);
        if (idx === -1) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (idx < rows.length - 1) rows[idx + 1].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx > 0) rows[idx - 1].focus();
            else         inputProd.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            rows[idx].click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            inputProd.focus();
        }
    }, true);
</script>
@endscript
