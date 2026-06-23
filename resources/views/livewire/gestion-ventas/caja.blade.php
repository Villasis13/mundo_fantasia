{{-- styles moved inside root div --}}
<div>
<style>
    /* ── Encabezado ──────────────────────── */
    .rv-header-icon { width:36px; height:36px; background:#EAF3DE; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#3B6D11; font-size:16px; flex-shrink:0; }
    .rv-header-title { font-size:15px; font-weight:700; color:#1a1a1a; }
    .rv-header-sub   { font-size:12px; color:#6c757d; display:flex; align-items:center; gap:4px; }
    .rv-header-sep   { font-size:12px; color:#adb5bd; }
    .rv-header-caja  { font-size:12px; font-weight:700; color:#185FA5; background:#E6F1FB; padding:2px 8px; border-radius:10px; display:flex; align-items:center; gap:4px; }
    .rv-icon-btn { background:transparent; border:none; cursor:pointer; padding:0; display:flex; align-items:center; }
    .rv-sticky-resumen { position:sticky; top:16px; }

    /* ── Card ──────────────────────────────── */
    .rv-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; }
    .rv-ch   { padding:10px 16px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; gap:8px; background:#fafafa; }
    .rv-ic   { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rv-lbl  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6c757d; }
    .rv-cb   { padding:14px 16px; }

    /* ── Inputs ────────────────────────────── */
    .rv-fl { font-size:11px; color:#6c757d; margin-bottom:4px; display:block; font-weight:600; }
    .rv-input, .rv-select {
        width:100%; background:#f8f9fa; border:1px solid #dee2e6;
        border-radius:8px; padding:7px 10px; font-size:13px; color:#212529;
        outline:none; transition:border-color .15s, box-shadow .15s; font-family:inherit;
    }
    .rv-input:focus, .rv-select:focus { border-color:#378ADD; box-shadow:0 0 0 3px rgba(55,138,221,.12); }
    .rv-corr   { font-weight:700; text-align:center; color:#185FA5; letter-spacing:.04em; background:#f0f4f8; cursor:default; }
    .rv-warn   { color:#854F0B; background:#FAEEDA; border-color:#FAC775; }
    .rv-prefix { background:#f0f4f8; border:1px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px; padding:7px 10px; font-size:13px; color:#6c757d; font-weight:700; flex-shrink:0; }
    .rv-money  { border-radius:0 8px 8px 0 !important; border-left:none !important; }

    /* ── Botones tipo radio ─────────────────── */
    .rv-btn-group { display:flex; gap:4px; }
    .rv-rh        { display:none; }
    .rv-btn { flex:1; padding:7px 6px; border-radius:8px; border:1px solid #dee2e6; background:#fff; font-size:11px; font-weight:700; cursor:pointer; color:#6c757d; text-align:center; transition:all .15s; user-select:none; white-space:nowrap; }
    .rv-btn:hover { background:#f8f9fa; }
    .rv-btn:focus { outline:none; box-shadow:0 0 0 3px rgba(55,138,221,.4); border-color:#378ADD; background:#dbeeff; color:#0C447C; z-index:1; }
    .rv-bt-blue  { background:#E6F1FB !important; border-color:#378ADD !important; color:#0C447C !important; }
    .rv-bt-green { background:#EAF3DE !important; border-color:#639922 !important; color:#3B6D11 !important; }
    .rv-bt-amber { background:#FAEEDA !important; border-color:#BA7517 !important; color:#633806 !important; }
    .rv-cobrar-btn:focus { outline:none; box-shadow:0 0 0 4px rgba(25,135,84,.45); background:#146c43 !important; transform:scale(1.02); }

    /* ── Totales resumen ────────────────────── */
    .rv-tr-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0f0f0; }
    .rv-tr-row:last-of-type { border-bottom:none; }
    .rv-tr-row span:first-child { font-size:12px; color:#6c757d; }
    .rv-tr-row span:last-child  { font-size:12px; font-weight:700; color:#212529; }
    .rv-total-final { display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:2px solid #dee2e6; }
    .rv-total-final span:first-child { font-size:15px; font-weight:700; color:#A32D2D; }
    .rv-total-final span:last-child  { font-size:20px; font-weight:700; color:#A32D2D; }
    .rv-cobrar-btn { width:100%; padding:11px; background:#198754; border:none; border-radius:8px; color:#fff; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; margin-top:12px; transition:background .15s; }
    .rv-cobrar-btn:hover    { background:#146c43; }
    .rv-cobrar-btn:disabled { background:#adb5bd; cursor:not-allowed; }

    /* ── Badges ─────────────────────────────── */
    .rv-badge  { display:inline-flex; align-items:center; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
    .rv-b-blue { background:#E6F1FB; color:#0C447C; }
    .rv-b-green{ background:#EAF3DE; color:#3B6D11; }
    .rv-b-amber{ background:#FAEEDA; color:#633806; }
    .rv-b-gray { background:#f0f0ee; color:#444; }

    /* ── Tabla ──────────────────────────────── */
    .rv-tbl { width:100%; border-collapse:collapse; font-size:13px; }
    .rv-th  { padding:9px 14px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; background:#fafafa; border-bottom:1px solid #e9ecef; white-space:nowrap; }
    .rv-tr  { border-bottom:1px solid #f0f0f0; }
    .rv-tr:last-child { border-bottom:none; }
    .rv-tr:hover { background:#fafcff; }
    .rv-td  { padding:10px 14px; vertical-align:middle; }
</style>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Detalle del Pedido
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleCaja" wire:ignore.self tabindex="-1" aria-hidden="true">
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
         MODAL — Seleccionar Pedido / Proforma
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSeleccionarPedido" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-2 px-3">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <h6 class="modal-title fw-bold mb-0 me-auto">
                            <i class="fa-solid fa-cash-register me-2 text-success"></i>Seleccionar Pedido / Proforma
                            @if($nombreTienda) <span class="text-muted fw-normal" style="font-size:13px;">— {{ $nombreTienda }}</span> @endif
                            @if($nombreCaja) <span class="rv-header-caja ms-2">{{ $nombreCaja }}</span> @endif
                        </h6>
                        <input type="text"
                               id="buscarModalInput"
                               wire:model.live.debounce.350ms="buscarModal"
                               class="form-control form-control-sm"
                               placeholder="Buscar por N°, cliente, doc..."
                               style="max-width:230px;">
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="px-3 py-2 border-bottom" style="background:#f0f2f5;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        {{-- Grupo principal: Pedidos / Proformas --}}
                        <div class="d-flex align-items-center rounded-3 p-1" style="background:#e2e5ea;gap:3px;">
                            <button type="button" wire:click="$set('tabModal','pedidos')"
                                    class="btn btn-sm d-flex align-items-center gap-1 fw-semibold"
                                    style="font-size:12.5px;border-radius:6px;padding:5px 13px;border:none;transition:all .15s;
                                           {{ $tabModal === 'pedidos'
                                               ? 'background:#fff;color:#0d6efd;box-shadow:0 1px 4px rgba(0,0,0,.15);'
                                               : 'background:transparent;color:#6c757d;' }}">
                                <i class="fa-solid fa-clipboard-list" style="font-size:11px;"></i>
                                Pedidos
                                <span class="ms-1 rounded px-1" style="font-size:10px;font-family:monospace;
                                      {{ $tabModal === 'pedidos' ? 'background:#e7f0ff;color:#0d6efd;' : 'background:#d1d5db;color:#6c757d;' }}">F1</span>
                            </button>
                            <button type="button" wire:click="$set('tabModal','proformas')"
                                    class="btn btn-sm d-flex align-items-center gap-1 fw-semibold"
                                    style="font-size:12.5px;border-radius:6px;padding:5px 13px;border:none;transition:all .15s;
                                           {{ $tabModal === 'proformas'
                                               ? 'background:#fff;color:#0d6efd;box-shadow:0 1px 4px rgba(0,0,0,.15);'
                                               : 'background:transparent;color:#6c757d;' }}">
                                <i class="fa-solid fa-file-contract" style="font-size:11px;"></i>
                                Proformas
                                <span class="ms-1 rounded px-1" style="font-size:10px;font-family:monospace;
                                      {{ $tabModal === 'proformas' ? 'background:#e7f0ff;color:#0d6efd;' : 'background:#d1d5db;color:#6c757d;' }}">F2</span>
                            </button>
                        </div>

                        @if($validarCaja)
                        {{-- Divisor --}}
                        <div style="width:1px;height:28px;background:#ced4da;flex-shrink:0;"></div>

                        {{-- Resumen de Ventas --}}
                        <button type="button" wire:click="$set('tabModal','resumen_ventas')"
                                class="btn btn-sm d-flex align-items-center gap-1 fw-semibold"
                                style="font-size:12.5px;border-radius:6px;padding:5px 13px;border:none;transition:all .15s;
                                       {{ $tabModal === 'resumen_ventas'
                                           ? 'background:#0C447C;color:#fff;box-shadow:0 1px 4px rgba(12,68,124,.35);'
                                           : 'background:#dce8f5;color:#0C447C;' }}">
                            <i class="fa-solid fa-chart-bar" style="font-size:11px;"></i>
                            Resumen
                            <span class="ms-1 rounded px-1" style="font-size:10px;font-family:monospace;
                                  {{ $tabModal === 'resumen_ventas' ? 'background:rgba(255,255,255,.25);color:#fff;' : 'background:#b8d4f0;color:#0C447C;' }}">F3</span>
                        </button>

                        {{-- Cierre de Caja --}}
                        <button type="button" wire:click="$set('tabModal','cierre_caja')"
                                class="btn btn-sm d-flex align-items-center gap-1 fw-semibold"
                                style="font-size:12.5px;border-radius:6px;padding:5px 13px;border:none;transition:all .15s;
                                       {{ $tabModal === 'cierre_caja'
                                           ? 'background:#dc3545;color:#fff;box-shadow:0 1px 4px rgba(220,53,69,.35);'
                                           : 'background:#fde8ea;color:#dc3545;' }}">
                            <i class="fa-solid fa-door-closed" style="font-size:11px;"></i>
                            Cierre de Caja
                            <span class="ms-1 rounded px-1" style="font-size:10px;font-family:monospace;
                                  {{ $tabModal === 'cierre_caja' ? 'background:rgba(255,255,255,.25);color:#fff;' : 'background:#f9c0c5;color:#dc3545;' }}">F4</span>
                        </button>
                        @endif

                    </div>
                </div>

                <div class="modal-body p-0" style="max-height:480px;overflow-y:auto;">

                    {{-- Apertura caja (cuando cerrada) --}}
                    @if(!$validarCaja)
                    @if($cajaCerradaHoy)
                    {{-- Ya cerró caja hoy: bloquear apertura --}}
                    <div class="p-4 border-bottom text-center" style="background:#fff8f8;">
                        <div style="width:52px;height:52px;background:#FEE2E2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                            <i class="fa-solid fa-ban" style="color:#dc3545;font-size:22px;"></i>
                        </div>
                        <div class="fw-bold mb-1" style="font-size:14px;color:#dc3545;">Caja ya cerrada hoy</div>
                        <div class="text-muted" style="font-size:12px;max-width:340px;margin:0 auto;">
                            Ya aperturaste y cerraste una caja el día de hoy.<br>
                            No es posible abrir otra caja hasta el día siguiente.
                        </div>
                        <div class="mt-3 d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2"
                             style="background:#f0f4f8;border:1px solid #dee2e6;font-size:12px;color:#6c757d;">
                            <i class="fa-solid fa-clock"></i>
                            Vuelve mañana para aperturar una nueva caja
                        </div>
                    </div>
                    @else
                    {{-- Formulario de apertura --}}
                    <div class="p-3 border-bottom" style="background:#fffdf5;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div style="width:34px;height:34px;background:#FEF3C7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid fa-key" style="color:#92400E;font-size:14px;"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:13px;color:#92400E;">Caja cerrada</div>
                                <div class="text-muted" style="font-size:11px;">Apertura tu caja para comenzar a cobrar</div>
                            </div>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-sm-5">
                                <label class="rv-fl">Seleccionar caja</label>
                                <select class="rv-select" wire:model="idCajaParaAbrir">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach($cajasDisponibles as $cj)
                                    <option value="{{ $cj['id_caja_numero'] }}" {{ $cj['ya_abierta'] ? 'disabled' : '' }}>
                                        {{ $cj['caja_numero_nombre'] }}{{ $cj['ya_abierta'] ? ' (ya abierta)' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('idCajaParaAbrir') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="rv-fl">Monto apertura (S/)</label>
                                <div class="d-flex">
                                    <span class="rv-prefix">S/</span>
                                    <input type="text" inputmode="decimal" class="rv-input rv-money"
                                           placeholder="0.00" wire:model="montoAperturaForm"
                                           wire:keydown.enter="aperturarCajaDesdeModal">
                                </div>
                                @error('montoAperturaForm') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-sm-3">
                                <button type="button" class="btn btn-success w-100 fw-semibold"
                                        style="font-size:12px;padding:7px 10px;"
                                        wire:click="aperturarCajaDesdeModal"
                                        wire:loading.attr="disabled"
                                        wire:target="aperturarCajaDesdeModal">
                                    <span wire:loading wire:target="aperturarCajaDesdeModal">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i>...
                                    </span>
                                    <span wire:loading.remove wire:target="aperturarCajaDesdeModal">
                                        <i class="fa-solid fa-key me-1"></i>Aperturar
                                    </span>
                                </button>
                            </div>
                        </div>
                        @if(session('errorCaja'))
                        <div class="alert alert-danger py-1 px-2 mt-2 mb-0" style="font-size:12px;">
                            <i class="fa-solid fa-circle-xmark me-1"></i>{{ session('errorCaja') }}
                        </div>
                        @endif
                        @if(session('successCaja'))
                        <div class="alert alert-success py-1 px-2 mt-2 mb-0" style="font-size:12px;">
                            <i class="fa-solid fa-circle-check me-1"></i>{{ session('successCaja') }}
                        </div>
                        @endif
                    </div>
                    @endif
                    @else
                    @if(session('successCaja'))
                    <div class="alert alert-success alert-dismissible py-1 px-3 mb-0" style="font-size:12px;border-radius:0;">
                        <i class="fa-solid fa-circle-check me-1"></i>{{ session('successCaja') }}
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" style="padding:0.35rem;"></button>
                    </div>
                    @endif
                    @endif

                    {{-- Tabla Pedidos --}}
                    @if($tabModal === 'pedidos')
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 py-2" style="font-size:11px;">N° Pedido</th>
                                    <th class="py-2" style="font-size:11px;">Cliente</th>
                                    <th class="text-center py-2" style="font-size:11px;">Ítems</th>
                                    <th class="text-end py-2" style="font-size:11px;">Total</th>
                                    <th class="text-center py-2" style="font-size:11px;">Pago</th>
                                    <th class="py-2" style="font-size:11px;">Fecha</th>
                                    <th class="text-center py-2" style="font-size:11px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pedidos as $pedido)
                                <tr wire:key="msp-{{ $pedido->id_pedido }}" style="cursor:default;">
                                    <td class="ps-3 py-2">
                                        <span class="fw-semibold text-primary" style="font-size:13px;">{{ last(explode('-', $pedido->pedido_numero)) }}</span>
                                    </td>
                                    <td class="py-2">
                                        <div style="font-size:12px;font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $pedido->pedido_cliente_nombre ?: '— Sin datos —' }}
                                        </div>
                                        @if($pedido->pedido_cliente_doc)
                                        <small class="text-muted">{{ $pedido->pedido_cliente_doc }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center py-2">
                                        <span class="badge bg-secondary fw-normal">{{ $pedido->total_items }}</span>
                                    </td>
                                    <td class="text-end py-2 fw-semibold text-primary" style="font-size:13px;">
                                        S/ {{ number_format($pedido->total_pedido ?? 0, 2) }}
                                    </td>
                                    <td class="text-center py-2">
                                        @if($pedido->pedido_tipo_pago == 2)
                                            <span class="badge" style="background:#FAEEDA;color:#633806;font-size:0.65rem;">Crédito</span>
                                        @else
                                            <span class="badge" style="background:#EAF3DE;color:#3B6D11;font-size:0.65rem;">Contado</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($pedido->created_at)->format('d/m H:i') }}</small>
                                    </td>
                                    <td class="text-center py-2 pe-3">
                                        @if($validarCaja)
                                        @can('caja_pedidos.crear')
                                        <button type="button"
                                                class="btn btn-success btn-sm fw-semibold px-3"
                                                wire:click="seleccionarPedido({{ $pedido->id_pedido }})"
                                                wire:loading.attr="disabled"
                                                wire:target="seleccionarPedido({{ $pedido->id_pedido }})">
                                            <span wire:loading wire:target="seleccionarPedido({{ $pedido->id_pedido }})"><span class="spinner-border spinner-border-sm"></span></span>
                                            <span wire:loading.remove wire:target="seleccionarPedido({{ $pedido->id_pedido }})"><i class="fa-solid fa-cash-register me-1"></i>Cobrar</span>
                                        </button>
                                        @endcan
                                        @else
                                        <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Caja cerrada</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                                        <small>No hay pedidos pendientes de cobro.</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Tabla Proformas --}}
                    @if($tabModal === 'proformas')
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 py-2" style="font-size:11px;">N° Proforma</th>
                                    <th class="py-2" style="font-size:11px;">Cliente</th>
                                    <th class="text-center py-2" style="font-size:11px;">Ítems</th>
                                    <th class="text-end py-2" style="font-size:11px;">Total</th>
                                    <th class="py-2" style="font-size:11px;">Fecha</th>
                                    <th class="text-center py-2" style="font-size:11px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proformas as $proforma)
                                <tr wire:key="mspf-{{ $proforma->id_profo }}">
                                    <td class="ps-3 py-2">
                                        <span class="fw-semibold" style="color:#198754;font-size:13px;">
                                            {{ $proforma->profo_serie }}-{{ str_pad($proforma->profo_correlativo, 6, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>
                                    <td class="py-2">
                                        <div style="font-size:12px;font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $proforma->cliente_razonsocial ?: $proforma->cliente_nombre }}
                                        </div>
                                        @if($proforma->cliente_numero)
                                        <small class="text-muted">{{ $proforma->cliente_numero }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center py-2">
                                        <span class="badge bg-secondary fw-normal">{{ $proforma->total_items }}</span>
                                    </td>
                                    <td class="text-end py-2 fw-semibold" style="color:#198754;font-size:13px;">
                                        S/ {{ number_format($proforma->total_proforma ?? 0, 2) }}
                                    </td>
                                    <td class="py-2">
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($proforma->created_at)->format('d/m H:i') }}</small>
                                    </td>
                                    <td class="text-center py-2 pe-3">
                                        @if($validarCaja)
                                        @can('caja_pedidos.crear')
                                        <button type="button"
                                                class="btn btn-success btn-sm fw-semibold px-3"
                                                wire:click="seleccionarProforma({{ $proforma->id_profo }})"
                                                wire:loading.attr="disabled"
                                                wire:target="seleccionarProforma({{ $proforma->id_profo }})">
                                            <span wire:loading wire:target="seleccionarProforma({{ $proforma->id_profo }})"><span class="spinner-border spinner-border-sm"></span></span>
                                            <span wire:loading.remove wire:target="seleccionarProforma({{ $proforma->id_profo }})"><i class="fa-solid fa-cash-register me-1"></i>Cobrar</span>
                                        </button>
                                        @endcan
                                        @else
                                        <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Caja cerrada</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-file-contract fa-2x d-block mb-2 opacity-25"></i>
                                        <small>No hay proformas pendientes de cobro.</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Resumen de Ventas --}}
                    @if($tabModal === 'resumen_ventas' && $validarCaja)
                    <div class="px-1 pt-2 pb-1">

                        {{-- Botón descargar resumen --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-semibold text-muted small">
                                {{ count($ventasResumen) }} venta(s) en esta caja
                            </span>
                            <a href="{{ route('Gestionventas.imprimir_resumen_caja') }}?caja_id={{ $idCaja }}"
                               target="_blank"
                               class="btn btn-primary btn-sm fw-semibold px-3">
                                <i class="fa-solid fa-download me-1"></i>Descargar Resumen
                            </a>
                        </div>

                        {{-- Tabla de ventas --}}
                        <div class="table-responsive" style="max-height:380px;overflow-y:auto">
                            <table class="table table-hover table-sm align-middle mb-0" style="font-size:.84rem">
                                <thead class="table-light" style="position:sticky;top:0;z-index:1">
                                    <tr>
                                        <th class="ps-2">Comprobante</th>
                                        <th>Cliente</th>
                                        <th style="width:80px">Hora</th>
                                        <th class="text-end" style="width:90px">Total</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ventasResumen as $vr)
                                    <tr>
                                        <td class="ps-2">
                                            <span class="fw-semibold">{{ $vr->venta_serie }}-{{ str_pad($vr->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</span>
                                            <span class="badge ms-1 {{ $vr->venta_tipo === '01' ? 'bg-success' : ($vr->venta_tipo === '20' ? 'bg-secondary' : 'bg-info text-dark') }}" style="font-size:.65rem">
                                                {{ $vr->venta_tipo === '01' ? 'F' : ($vr->venta_tipo === '20' ? 'NV' : 'B') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width:160px" title="{{ $vr->cliente_nombre }}">{{ $vr->cliente_nombre }}</div>
                                            @if($vr->cliente_doc)
                                            <small class="text-muted">{{ $vr->cliente_doc }}</small>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ \Carbon\Carbon::parse($vr->created_at)->format('H:i') }}</td>
                                        <td class="text-end fw-bold text-primary">S/ {{ number_format((float)$vr->venta_total, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('Gestionventas.imprimir_ticketera_venta') }}?venta_id={{ $vr->id_venta }}"
                                               target="_blank"
                                               class="btn btn-outline-secondary btn-sm"
                                               title="Reimprimir comprobante">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-receipt fa-lg d-block mb-2 opacity-25"></i>
                                            No hay ventas registradas en esta caja.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                    @endif

                    {{-- Cierre de caja --}}
                    @if($tabModal === 'cierre_caja' && $validarCaja)
                    <div class="p-3">
                        @if(!empty($resumenCierre))
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-sm-3 d-flex">
                                <div class="text-center p-2 rounded-2 w-100 d-flex flex-column justify-content-center" style="background:#f8f9fa;border:1px solid #e9ecef;">
                                    <div class="rv-lbl mb-1">Apertura</div>
                                    <div style="font-size:14px;font-weight:700;color:#212529;">S/ {{ number_format($resumenCierre['apertura'], 2) }}</div>
                                    <div style="font-size:10px;color:transparent;line-height:1.2;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3 d-flex">
                                <div class="text-center p-2 rounded-2 w-100 d-flex flex-column justify-content-center" style="background:#E6F1FB;border:1px solid #b8d4f0;">
                                    <div class="rv-lbl mb-1" style="color:#0C447C;">Total ventas</div>
                                    <div style="font-size:14px;font-weight:700;color:#0C447C;">S/ {{ number_format($resumenCierre['total_ventas'], 2) }}</div>
                                    <div style="font-size:10px;color:#6c757d;line-height:1.2;">{{ $resumenCierre['num_ventas'] }} comprobante{{ $resumenCierre['num_ventas'] !== 1 ? 's' : '' }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3 d-flex">
                                <div class="text-center p-2 rounded-2 w-100 d-flex flex-column justify-content-center" style="background:#FEF3C7;border:1px solid #fcd34d;">
                                    <div class="rv-lbl mb-1" style="color:#92400E;">Gastos</div>
                                    <div style="font-size:14px;font-weight:700;color:#92400E;">S/ {{ number_format($resumenCierre['gastos'], 2) }}</div>
                                    <div style="font-size:10px;color:transparent;line-height:1.2;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3 d-flex">
                                <div class="text-center p-2 rounded-2 w-100 d-flex flex-column justify-content-center" style="background:#EAF3DE;border:1px solid #b7d98e;">
                                    <div class="rv-lbl mb-1" style="color:#3B6D11;">Total sistema</div>
                                    <div style="font-size:14px;font-weight:700;color:#3B6D11;">S/ {{ number_format($resumenCierre['total_sistema'], 2) }}</div>
                                    <div style="font-size:10px;color:transparent;line-height:1.2;">&nbsp;</div>
                                </div>
                            </div>
                        </div>

                        @if(!empty($resumenCierre['ventasPorMedio']))
                        <div class="mb-3">
                            <div class="rv-lbl mb-2">Ventas por medio de pago</div>
                            <div class="rv-card" style="border-radius:8px;">
                                @foreach($resumenCierre['ventasPorMedio'] as $vm)
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span style="font-size:12px;color:#444;">{{ $vm['tipo_pago_nombre'] }}</span>
                                    <span class="fw-bold" style="font-size:13px;color:#212529;">S/ {{ number_format($vm['total'], 2) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($resumenCierre['cobros'] > 0)
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 mb-2 rounded-2" style="background:#f0f4f8;border:1px solid #dee2e6;">
                            <span style="font-size:12px;color:#6c757d;">Cobros de cuotas</span>
                            <span class="fw-bold" style="font-size:13px;">S/ {{ number_format($resumenCierre['cobros'], 2) }}</span>
                        </div>
                        @endif

                        <button type="button"
                                class="btn btn-danger fw-semibold px-4"
                                onclick="document.dispatchEvent(new Event('abrirConfirmCierre'))">
                            <i class="fa-solid fa-door-closed me-2"></i>Cerrar Caja
                        </button>

                        @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-spinner fa-spin fa-2x d-block mb-2 opacity-50"></i>
                            <small>Cargando resumen...</small>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>{{-- /modal-body --}}
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Confirmar Cierre de Caja
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalConfirmCierre" wire:ignore.self tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3" style="background:#fff5f5;">
                    <h5 class="modal-title fw-bold mb-0" style="color:#dc3545;">
                        <i class="fa-solid fa-door-closed me-2"></i>Confirmar Cierre de Caja
                    </h5>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-3" style="font-size:13px;">
                        ¿Estás seguro que deseas cerrar la caja
                        @if($nombreCaja) <strong>{{ $nombreCaja }}</strong> @endif?
                        Esta acción no se puede deshacer.
                    </p>
                    @if(!empty($resumenCierre))
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-2 mb-3"
                         style="background:#EAF3DE;border:1px solid #b7d98e;">
                        <span style="font-size:12px;color:#3B6D11;font-weight:600;">Total sistema esperado:</span>
                        <span style="font-size:15px;font-weight:700;color:#3B6D11;">S/ {{ number_format($resumenCierre['total_sistema'], 2) }}</span>
                    </div>
                    @endif
                    <div>
                        <label class="rv-fl">Monto contado en caja (S/)</label>
                        <div class="d-flex">
                            <span class="rv-prefix">S/</span>
                            <input type="text" inputmode="decimal" class="rv-input rv-money"
                                   placeholder="0.00" wire:model="montoCierreForm"
                                   wire:keydown.enter="cerrarCaja"
                                   autofocus>
                        </div>
                        @error('montoCierreForm') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
                    </div>
                    @if(session('errorCaja'))
                    <div class="alert alert-danger py-1 px-2 mt-2 mb-0" style="font-size:12px;">
                        <i class="fa-solid fa-circle-xmark me-1"></i>{{ session('errorCaja') }}
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-3 px-4 gap-2">
                    <button type="button" class="btn btn-light px-4" id="btn-volver-cierre">
                        <i class="fa-solid fa-arrow-left me-1"></i>Volver
                    </button>
                    <button type="button" class="btn btn-danger px-4 fw-semibold"
                            wire:click="cerrarCaja"
                            wire:loading.attr="disabled"
                            wire:target="cerrarCaja">
                        <span wire:loading wire:target="cerrarCaja">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Cerrando...
                        </span>
                        <span wire:loading.remove wire:target="cerrarCaja">
                            <i class="fa-solid fa-door-closed me-1"></i>Confirmar cierre
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         VISTA: COBRAR PEDIDO / PROFORMA
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'cobrar')

    {{-- Flash messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="rv-header-icon">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-1">
                <div class="rv-header-title me-2">
                    @if($esProforma) Cobrar Proforma @else Cobrar Pedido @endif
                </div>
                @if($esProforma)
                    <span class="badge ms-1 me-2" style="background:#D1FAE5;color:#065F46;font-size:0.65rem;">
                        <i class="fa-solid fa-file-contract me-1"></i>Proforma
                    </span>
                @endif
                <span class="rv-header-sub">
                    <i class="fa-solid fa-hashtag" style="font-size:10px;"></i>
                    {{ $esProforma ? $proformaNumero : $pedidoNumero }}
                </span>
                @if($nombreTienda)
                    <span class="rv-header-sep">·</span>
                    <span class="rv-header-sub">
                        <i class="fa-solid fa-store" style="font-size:10px;"></i>
                        {{ $nombreTienda }}
                    </span>
                @endif
                @if($nombreCaja)
                    <span class="rv-header-sep">·</span>
                    <span class="rv-header-caja">
                        <i class="fa-solid fa-cash-register" style="font-size:10px;"></i>
                        {{ $nombreCaja }}
                    </span>
                @endif
            </div>
        </div>
        <button type="button" id="btn-otro-pedido"
                class="btn btn-outline-primary btn-sm fw-semibold"
                wire:click="volverLista">
            <i class="fa-solid fa-magnifying-glass me-1"></i>Otro pedido
        </button>
    </div>

    {{-- Placeholder cuando no hay pedido/proforma seleccionado --}}
    @if(empty($items))
    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center"
         style="min-height:340px;">
        <div style="width:72px;height:72px;background:#EAF3DE;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
            <i class="fa-solid fa-cash-register" style="font-size:32px;color:#3B6D11;"></i>
        </div>
        <h6 class="fw-semibold text-secondary mb-1">Ningún pedido seleccionado</h6>
        <p class="text-muted mb-4" style="font-size:13px;">Selecciona un pedido o proforma para comenzar el cobro.</p>
        <button type="button" class="btn btn-success fw-semibold px-4"
                id="btn-abrir-modal-inicio"
                wire:click="volverLista">
            <i class="fa-solid fa-magnifying-glass me-2"></i>Buscar pedido / proforma
        </button>
    </div>
    @else

    <div class="row g-3">

        {{-- ══ COLUMNA IZQUIERDA ══ --}}
        <div class="col-12 col-lg-8 d-flex flex-column gap-3">

            {{-- ── CLIENTE + COMPROBANTE (compact) ── --}}
            <div class="rv-card">
                <div class="rv-ch">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rv-ic" style="background:#E6F1FB;">
                            <i class="fa-solid fa-user" style="color:#185FA5;font-size:12px;"></i>
                        </div>
                        <span class="rv-lbl">Cliente y comprobante</span>
                        @php
                            $cpLabel = match($tipoComprobante) { '01' => 'Factura', '20' => 'N.Venta', default => 'Boleta' };
                            $cpIcon  = match($tipoComprobante) { '01' => 'fa-file-invoice', '20' => 'fa-file-lines', default => 'fa-receipt' };
                        @endphp
                        <span class="rv-badge rv-b-blue ms-1">
                            <i class="fa-solid {{ $cpIcon }}" style="font-size:9px;margin-right:3px;"></i>{{ $cpLabel }}
                        </span>
                    </div>
                </div>
                <div class="rv-cb" style="padding:10px 16px;">
                    @php
                        $serieActual = '';
                        foreach($series as $s) {
                            if ($s->id_serie == $idSerie) { $serieActual = $s->serie; break; }
                        }
                    @endphp
                    {{-- Tipo doc + Número --}}
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <span style="font-size:12px;color:#6c757d;font-weight:600;flex-shrink:0;">
                            {{ $idTipoDocumento == '4' ? 'RUC' : 'DNI' }}
                        </span>
                        <span style="font-size:19px;font-weight:700;color:#185FA5;letter-spacing:.04em;">
                            {{ $numDocumento ?: '—' }}
                        </span>
                    </div>
                    {{-- Nombre / Razón social --}}
                    <div class="mb-2" style="line-height:1.2;">
                        <span style="font-size:17px;font-weight:600;color:#212529;">
                            {{ $nombreCliente ?: 'Cliente genérico' }}
                        </span>
                    </div>
                    {{-- Serie-Correlativo + IGV --}}
                    <div class="d-flex align-items-center gap-2" style="border-top:1px solid #f0f0f0;padding-top:8px;">
                        @if($serieActual)
                            <span style="font-size:18px;font-weight:700;color:#3B6D11;">
                                {{ $serieActual }}-{{ $correlativo }}
                            </span>
                        @else
                            <span style="font-size:17px;font-weight:600;color:#dc3545;">Sin series</span>
                        @endif
                        <span style="font-size:13px;color:#6c757d;margin-left:auto;white-space:nowrap;">
                            IGV {{ $porcentajeIgv == 0 ? 'Sin IGV' : $porcentajeIgv.'%' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── PAGO ── --}}
            <div class="rv-card">
                <div class="rv-ch">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rv-ic" style="background:{{ $esGratuita ? '#FFF3CD' : '#FAEEDA' }};">
                            <i class="fa-solid {{ $esGratuita ? 'fa-hand-holding-heart' : 'fa-money-bill-wave' }}"
                               style="color:{{ $esGratuita ? '#856404' : '#854F0B' }};font-size:12px;"></i>
                        </div>
                        <span class="rv-lbl">Pago</span>
                        @if($esGratuita)
                            <span class="rv-badge ms-1" style="background:#FFF3CD;color:#664d03;border:1px solid #FFC107;">
                                <i class="fa-solid fa-hand-holding-heart" style="font-size:9px;margin-right:3px;"></i>Gratuita
                            </span>
                        @endif
                    </div>
                </div>
                <div class="rv-cb">

                    {{-- Toggle Transferencia a título gratuito --}}
                    <div class="mb-3 p-2 rounded-2"
                         style="{{ $esGratuita ? 'background:#FFF3CD;border:1px solid #FFC107;' : 'background:#f8f9fa;border:1px solid #dee2e6;' }}">
                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="switchGratuita" style="width:2.4em;height:1.25em;cursor:pointer;"
                                   wire:model.live="esGratuita">
                            <label class="form-check-label d-flex align-items-center gap-1 fw-semibold"
                                   for="switchGratuita" style="font-size:14px;cursor:pointer;">
                                <i class="fa-solid fa-hand-holding-heart" style="color:#856404;font-size:13px;"></i>
                                Transferencia a título gratuito
                                <span class="text-muted fw-normal">(donación — importe total: <strong>S/ 0.00</strong>)</span>
                            </label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-start">
                        {{-- Contado / Crédito (deshabilitado en modo gratuita) --}}
                        @if(!$esGratuita)
                        <div class="col-12 col-sm-auto">
                            <label class="rv-fl" style="font-size:13px;">Forma de pago</label>
                            <div class="rv-btn-group" style="gap:3px;">
                                <button type="button" id="btn-contado"
                                        class="rv-btn {{ $idFormasPago == 1 ? 'rv-bt-green' : '' }}"
                                        style="padding:7px 14px;font-size:13px;min-width:0;"
                                        wire:click="cambiarFormaPago(1)"
                                        wire:loading.attr="disabled"
                                        wire:target="cambiarFormaPago">
                                    <i class="fa-solid fa-money-bill" style="font-size:11px;"></i> Contado
                                </button>
                                <button type="button" id="btn-credito"
                                        class="rv-btn {{ $idFormasPago == 2 ? 'rv-bt-amber' : '' }}"
                                        style="padding:7px 14px;font-size:13px;min-width:0;"
                                        wire:click="cambiarFormaPago(2)"
                                        wire:loading.attr="disabled"
                                        wire:target="cambiarFormaPago">
                                    <i class="fa-solid fa-calendar-days" style="font-size:11px;"></i> Crédito
                                </button>
                            </div>
                        </div>
                        @endif

                        @if($esGratuita)
                            <div class="col-12">
                                <div class="alert mb-0 py-2 px-3"
                                     style="background:#FFF3CD;border:1px solid #FFC107;color:#664d03;font-size:14px;">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Transferencia gratuita: no se registra ningún cobro. El comprobante mostrará los
                                    precios unitarios de referencia con <strong>importe total S/ 0.00</strong>.
                                </div>
                            </div>
                        @elseif($idFormasPago == 1)
                            <div class="col-12 col-sm">
                                <label class="rv-fl" style="font-size:13px;">Medios de pago</label>

                                @foreach($pagos as $i => $pago)
                                @php
                                    $idPagoActual = (int)($pago['id_tipo_pago'] ?? 0);
                                    $esTarjeta    = false;
                                    foreach ($tiposPago as $t) {
                                        $tid = (int)($t->id_tipo_pago ?? $t['id_tipo_pago'] ?? 0);
                                        if ($tid === $idPagoActual) {
                                            $esTarjeta = str_contains(strtoupper((string)($t->tipo_pago_nombre ?? $t['tipo_pago_nombre'] ?? '')), 'TARJETA');
                                            break;
                                        }
                                    }
                                @endphp
                                <div class="mb-2" wire:key="pago-line-{{ $i }}">
                                    <div class="d-flex align-items-center gap-2">

                                        {{-- Select medio de pago --}}
                                        <select class="rv-select flex-grow-1" style="font-size:15px;"
                                                wire:change="cambiarTipoPago({{ $i }}, $event.target.value)">
                                            @foreach($tiposPago as $tp)
                                            <option value="{{ $tp->id_tipo_pago }}"
                                                    {{ ($pago['id_tipo_pago'] ?? null) == $tp->id_tipo_pago ? 'selected' : '' }}>
                                                {{ $tp->tipo_pago_nombre }}
                                            </option>
                                            @endforeach
                                        </select>

                                        {{-- Monto --}}
                                        <div class="d-flex" style="width:130px;flex-shrink:0;">
                                            <span class="rv-prefix" style="font-size:15px;">S/</span>
                                            <input type="text" inputmode="decimal"
                                                   class="rv-input rv-money"
                                                   style="font-size:15px;"
                                                   placeholder="0.00"
                                                   wire:model.live="pagos.{{ $i }}.monto">
                                        </div>

                                        {{-- Quitar línea --}}
                                        @if(count($pagos) > 1)
                                        <button type="button" tabindex="-1"
                                                class="rv-icon-btn text-danger"
                                                style="flex-shrink:0;font-size:18px;"
                                                wire:click="quitarPago({{ $i }})">
                                            <i class="fa-solid fa-circle-minus"></i>
                                        </button>
                                        @endif
                                    </div>

                                    {{-- Selector de marca de tarjeta --}}
                                    @if($esTarjeta)
                                    <div class="d-flex gap-1 flex-wrap mt-1 ps-1">
                                        @foreach(['Visa', 'Mastercard', 'American Express', 'UnionPay'] as $marca)
                                        @php $seleccionada = ($pago['marca_tarjeta'] ?? '') === $marca; @endphp
                                        <button type="button"
                                                class="btn btn-sm {{ $seleccionada ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                style="font-size:.85rem;padding:3px 12px;border-radius:20px;"
                                                wire:click="cambiarMarcaTarjeta({{ $i }}, '{{ $marca }}')">
                                            {{ $marca }}
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach

                                {{-- Agregar línea --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary mt-1"
                                        style="font-size:13px;"
                                        wire:click="agregarPago">
                                    <i class="fa-solid fa-plus me-1"></i>Agregar medio de pago
                                </button>

                                {{-- Resumen pagado / faltante --}}
                                @php
                                    $totalPagadoLineas = collect($pagos)->sum(fn($p) => (float)($p['monto'] ?? 0));
                                    $totalVentaLineas  = $this->totales['total'];
                                    $faltanteLineas    = max(0, $totalVentaLineas - $totalPagadoLineas);
                                    $vueltoLineas      = max(0, $totalPagadoLineas - $totalVentaLineas);
                                @endphp
                                @if($totalPagadoLineas > 0)
                                <div class="d-flex gap-3 flex-wrap mt-3" style="font-size:14px;">
                                    <span style="color:#6c757d;">
                                        Total: <strong>S/ {{ number_format($totalVentaLineas, 2) }}</strong>
                                    </span>
                                    <span style="color:{{ $totalPagadoLineas >= $totalVentaLineas ? '#3B6D11' : '#dc3545' }};">
                                        Pagado: <strong>S/ {{ number_format($totalPagadoLineas, 2) }}</strong>
                                    </span>
                                    @if($faltanteLineas > 0)
                                        <span style="color:#dc3545;font-weight:700;">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Falta: S/ {{ number_format($faltanteLineas, 2) }}
                                        </span>
                                    @else
                                        <span style="color:#3B6D11;font-weight:700;">
                                            <i class="fa-solid fa-circle-check me-1"></i>Vuelto: S/ {{ number_format($vueltoLineas, 2) }}
                                        </span>
                                    @endif
                                </div>
                                @endif
                            </div>
                        @endif

                        @if(!$esGratuita && $idFormasPago == 2)
                            <div class="col-12 col-sm-8">
                                <div class="alert alert-info mb-0 py-2 px-3" style="font-size:14px;">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    La venta a crédito se registrará sin pago inmediato. Gestione las cuotas en <strong>Cuentas por Cobrar</strong>.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── PRODUCTOS DEL PEDIDO ── --}}
            <div class="rv-card">
                <div class="rv-ch">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rv-ic" style="background:#EAF3DE;">
                            <i class="fa-solid fa-boxes-stacked" style="color:#3B6D11;font-size:12px;"></i>
                        </div>
                        <span class="rv-lbl">Productos del pedido</span>
                        <span class="rv-badge rv-b-blue ms-1">{{ count($items) }} ítem{{ count($items) !== 1 ? 's' : '' }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="rv-tbl" style="font-size:15px;">
                        <thead>
                        <tr>
                            <th class="rv-th" style="font-size:12px;min-width:180px;">Producto</th>
                            <th class="rv-th text-center" style="font-size:12px;width:90px;">Cantidad</th>
                            <th class="rv-th text-end" style="font-size:12px;min-width:90px;">Precio unit.</th>
                            <th class="rv-th text-end" style="font-size:12px;min-width:90px;">Total</th>
                            <th class="rv-th" style="width:40px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $idx => $item)
                            @php
                                $precio   = (float)($item['precio_venta'] ?? 0);
                                $cantidad = (float)($item['cantidad'] ?? 0);
                                $tipoAfec = (int)($item['id_tipo_afectacion'] ?? 1);
                                $esBolsa  = (int)($item['impuesto_bolsa'] ?? 0) === 1;
                                $tasa     = $porcentajeIgv / 100;
                                $sub      = $esBolsa ? 0 : round($precio * $cantidad, 2);
                                $total    = ($tipoAfec === 1 && !$esBolsa) ? round($sub + $sub * $tasa, 2) : $sub;
                            @endphp
                            <tr class="rv-tr" wire:key="cp-item-{{ $item['id_pro'] }}">
                                <td class="rv-td">
                                    <div style="font-size:15px;font-weight:700;color:#212529;">{{ $item['pro_nombre'] }}</div>
                                    <div class="d-flex gap-1 flex-wrap mt-1">
                                        @if($esGratuita)
                                            <span class="rv-badge" style="background:#FFF3CD;color:#664d03;border:1px solid #FFC107;">
                                                <i class="fa-solid fa-hand-holding-heart" style="font-size:8px;margin-right:2px;"></i>Gratuito
                                            </span>
                                        @elseif($esBolsa)
                                            <span class="rv-badge rv-b-amber">ICBPER</span>
                                        @elseif($tipoAfec === 1)
                                            <span class="rv-badge rv-b-blue">Gravado</span>
                                        @elseif($tipoAfec === 2)
                                            <span class="rv-badge rv-b-green">Exonerado</span>
                                        @else
                                            <span class="rv-badge rv-b-gray">Inafecto</span>
                                        @endif
                                        @if(!empty($item['pro_codigo']))
                                            <span class="rv-badge rv-b-gray">{{ $item['pro_codigo'] }}</span>
                                        @endif
                                        @if(!empty($item['pres_nombre']))
                                            <span class="rv-badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc;">{{ $item['pres_nombre'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="rv-td text-center">
                                    <input type="number" min="1" step="1"
                                           class="rv-input text-center"
                                           style="width:75px;padding:5px 6px;font-size:15px;"
                                           data-cantidad-cobrar
                                           wire:model.live="items.{{ $idx }}.cantidad">
                                </td>
                                <td class="rv-td text-end">
                                    <span style="font-size:15px;">S/ {{ number_format($precio, 2) }}</span>
                                </td>
                                <td class="rv-td text-end">
                                    <span style="font-size:15px;font-weight:700;color:#212529;">S/ {{ number_format($total, 2) }}</span>
                                </td>
                                <td class="rv-td text-center">
                                    @if(count($items) > 1)
                                    <button type="button" tabindex="-1"
                                            class="rv-icon-btn text-danger"
                                            style="font-size:16px;"
                                            wire:click="quitarItemCobrar({{ $idx }})"
                                            title="Quitar producto">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox fa-lg d-block mb-2 opacity-25"></i>
                                    <small>No hay productos en este pedido.</small>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        @if(count($items) > 0)
                        @php
                            $totalFila = 0;
                            foreach ($items as $it) {
                                $p  = (float)($it['precio_venta'] ?? 0);
                                $c  = (float)($it['cantidad'] ?? 0);
                                $ta = (int)($it['id_tipo_afectacion'] ?? 1);
                                $eb = (int)($it['impuesto_bolsa'] ?? 0) === 1;
                                $ts = $porcentajeIgv / 100;
                                $sb = $eb ? 0 : round($p * $c, 2);
                                $totalFila += ($ta === 1 && !$eb) ? round($sb + $sb * $ts, 2) : $sb;
                            }
                        @endphp
                        <tfoot>
                            <tr style="background:#f8f9fa;border-top:2px solid #dee2e6;">
                                <td colspan="4" class="rv-td text-end fw-bold" style="font-size:16px;color:#6c757d;padding:14px 14px;">
                                    Total a pagar:
                                </td>
                                <td class="rv-td text-end fw-bold" style="font-size:22px;color:#A32D2D;padding:14px 14px;">
                                    S/ {{ number_format($totalFila, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>{{-- /col izquierda --}}

        {{-- ══ COLUMNA DERECHA: Resumen ══ --}}
        <div class="col-12 col-lg-4">
            <div class="rv-card rv-sticky-resumen">
                <div class="rv-ch">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rv-ic" style="background:#f0f0ee;">
                            <i class="fa-solid fa-calculator" style="color:#5F5E5A;font-size:12px;"></i>
                        </div>
                        <span class="rv-lbl" style="font-size:15px;">Resumen</span>
                    </div>
                </div>
                <div class="rv-cb">
                    <div class="rv-tr-row"><span style="font-size:16px;">Op. gravada</span><span style="font-size:16px;">S/ {{ number_format($this->totales['gravada'], 2) }}</span></div>
                    <div class="rv-tr-row"><span style="font-size:16px;">IGV {{ $porcentajeIgv }}%</span><span style="font-size:16px;">S/ {{ number_format($this->totales['igv'], 2) }}</span></div>
                    <div class="rv-tr-row"><span style="font-size:16px;">Exonerada</span><span style="font-size:16px;">S/ {{ number_format($this->totales['exonerada'], 2) }}</span></div>
                    <div class="rv-tr-row"><span style="font-size:16px;">Inafectada</span><span style="font-size:16px;">S/ {{ number_format($this->totales['inafecta'], 2) }}</span></div>
                    <div class="rv-tr-row"><span style="font-size:16px;">Gratuitas</span><span style="font-size:16px;">S/ {{ number_format($this->totales['gratuita'], 2) }}</span></div>
                    <div class="rv-tr-row"><span style="font-size:16px;">ICBPER</span><span style="font-size:16px;">S/ {{ number_format($this->totales['impuesto'], 2) }}</span></div>
                    @if($idFormasPago == 1)
                        @php $totalPagadoResumen = collect($pagos)->sum(fn($p) => (float)($p['monto'] ?? 0)); @endphp
                        <div class="rv-tr-row" style="border-top:1px solid #e9ecef;margin-top:4px;padding-top:8px;">
                            <span style="font-size:16px;">Pagado</span>
                            <span style="font-size:16px;color:{{ $totalPagadoResumen >= $this->totales['total'] ? '#3B6D11' : '#dc3545' }};font-weight:700;">
                                S/ {{ number_format($totalPagadoResumen, 2) }}
                            </span>
                        </div>
                        <div class="rv-tr-row">
                            <span style="font-size:16px;">Vuelto</span>
                            <span style="font-size:16px;color:{{ $this->vuelto > 0 ? '#3B6D11' : '#6c757d' }};font-weight:700;">
                                S/ {{ number_format($this->vuelto, 2) }}
                            </span>
                        </div>
                    @endif
                    <div class="rv-total-final" style="{{ $esGratuita ? 'border-top-color:#FFC107;' : '' }}">
                        <span style="font-size:20px;{{ $esGratuita ? 'color:#664d03;' : '' }}">
                            @if($esGratuita)
                                <i class="fa-solid fa-hand-holding-heart me-1" style="font-size:16px;"></i>
                            @endif
                            Total
                        </span>
                        <span style="font-size:30px;{{ $esGratuita ? 'color:#664d03;' : '' }}">
                            S/ {{ number_format($this->totales['total'], 2) }}
                        </span>
                    </div>
                    <button class="rv-cobrar-btn" type="button" id="btn-registrar-venta"
                            style="font-size:17px;padding:14px;{{ $esGratuita ? 'background:#856404;' : '' }}"
                            onmouseover="{{ $esGratuita ? 'this.style.background=\"#664d03\"' : '' }}"
                            onmouseout="{{ $esGratuita ? 'this.style.background=\"#856404\"' : '' }}"
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            wire:target="guardar"
                            @if(empty($series)) disabled @endif>
                        <span wire:loading wire:target="guardar">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            @if($esGratuita)
                                <i class="fa-solid fa-hand-holding-heart me-2"></i>REGISTRAR DONACIÓN
                            @else
                                <i class="fa-solid fa-cash-register me-2"></i>REGISTRAR VENTA
                            @endif
                            <kbd style="font-size:.75rem;background:rgba(255,255,255,.2);color:inherit;border:1px solid rgba(255,255,255,.4);border-radius:3px;padding:0 5px;margin-left:6px;">F2</kbd>
                        </span>
                    </button>
                    @if(empty($series))
                        <p class="text-center mt-2 mb-0" style="font-size:13px;color:#dc3545;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Configure series para esta caja.
                        </p>
                    @else
                        <p class="text-center mt-2 mb-0" style="font-size:13px;color:#adb5bd;">
                            <i class="fa-solid fa-circle-info me-1"></i>Ventas ≥ S/ 700 requieren datos del cliente.
                        </p>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- /row --}}

    @endif {{-- /items --}}

    @endif {{-- /cobrar --}}


    {{-- ══════════════════════════════════════════════════════
         VISTA: DESPACHAR PEDIDO (tras cobrar)
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'despachar')

    <div class="d-flex flex-column align-items-center" style="max-width:640px;margin:0 auto;">

        {{-- Banner de éxito --}}
        <div class="w-100 mb-4 rounded-3 p-4 text-center"
             style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:2px solid #6ee7b7;">
            <div class="mb-2" style="font-size:2.5rem;line-height:1;">
                <i class="fa-solid fa-circle-check text-success"></i>
            </div>
            <div class="fw-bold text-success" style="font-size:1.1rem;">Venta registrada correctamente</div>
            <div class="mt-1" style="font-size:0.95rem;color:#065f46;">
                Comprobante: <strong>{{ $ventaNumeroReciente }}</strong>
            </div>
        </div>

        {{-- Resumen de productos --}}
        <div class="card border-0 shadow-sm w-100 mb-4">
            <div class="rv-ch">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-ic" style="background:#EAF3DE;">
                        <i class="fa-solid fa-boxes-stacked" style="color:#3B6D11;font-size:12px;"></i>
                    </div>
                    <span class="rv-lbl">Productos despachados</span>
                    <span class="rv-badge rv-b-blue ms-1">{{ count($itemsDespacho) }} ítem{{ count($itemsDespacho) !== 1 ? 's' : '' }}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="rv-tbl">
                    <thead>
                    <tr>
                        <th class="rv-th" style="min-width:180px;">Producto</th>
                        <th class="rv-th text-center" style="width:90px;">Cantidad</th>
                        <th class="rv-th text-end" style="width:110px;">Precio unit.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($itemsDespacho as $item)
                        <tr class="rv-tr">
                            <td class="rv-td">
                                <div style="font-size:13px;font-weight:700;">{{ $item['pro_nombre'] }}</div>
                                <div class="d-flex gap-1 flex-wrap mt-1">
                                    @if(!empty($item['pro_codigo']))
                                        <small class="text-muted">{{ $item['pro_codigo'] }}</small>
                                    @endif
                                    @if(!empty($item['pres_nombre']))
                                        <span class="rv-badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc;">{{ $item['pres_nombre'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="rv-td text-center fw-bold">{{ number_format((float)$item['cantidad'], 0) }}</td>
                            <td class="rv-td text-end">S/ {{ number_format((float)$item['precio_venta'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pregunta despacho --}}
        <div class="card border-0 shadow-sm w-100">
            <div class="card-body p-4 text-center">
                <p class="mb-1 fw-semibold" style="font-size:1rem;">¿Deseas despachar este pedido ahora?</p>
                <p class="text-muted mb-4" style="font-size:0.82rem;">
                    Al despachar se descontará el stock de <strong>{{ $nombreTienda ?: 'la tienda' }}</strong>.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <button type="button"
                            class="btn btn-success px-5 py-2 fw-bold"
                            style="font-size:0.95rem;border-radius:10px;"
                            wire:click="despacharPedido"
                            wire:loading.attr="disabled"
                            wire:target="despacharPedido">
                        <span wire:loading wire:target="despacharPedido">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Despachando...
                        </span>
                        <span wire:loading.remove wire:target="despacharPedido">
                            <i class="fa-solid fa-truck me-2"></i>Despachar ahora
                        </span>
                    </button>
                    <button type="button"
                            class="btn btn-outline-secondary px-4 py-2"
                            style="font-size:0.95rem;border-radius:10px;"
                            wire:click="saltarDespacho"
                            wire:loading.attr="disabled"
                            wire:target="saltarDespacho">
                        <i class="fa-solid fa-forward me-2"></i>Omitir
                    </button>
                </div>
            </div>
        </div>

    </div>

    @endif {{-- /despachar --}}

</div>

@script
<script>
    const modalSeleccion   = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeleccionarPedido'));
    const modalDetalleCaja = document.getElementById('modalDetalleCaja');

    // Foco en el primer botón Cobrar dentro del modal
    const enfocarPrimerCobrar = () => {
        $nextTick(() => {
            const modal = document.getElementById('modalSeleccionarPedido');
            const btn   = modal ? modal.querySelector('tbody button.btn-success') : null;
            if (btn) {
                btn.focus();
            }
        });
    };

    // Abrir modal de selección
    const abrirSeleccion = () => {
        modalSeleccion.show();
        // Pequeño delay para que el modal termine su animación y Livewire renderice la tabla
        setTimeout(enfocarPrimerCobrar, 320);
    };

    // Al montar: abrir modal automáticamente
    $nextTick(abrirSeleccion);

    $wire.on('abrirModalSeleccion', abrirSeleccion);

    $wire.on('cerrarModalSeleccion', () => {
        modalSeleccion.hide();
        // limpiar backdrop
        $nextTick(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
    });

    // Limpiar backdrop si el modal se cierra manualmente (esc deshabilitado con data-bs-keyboard=false)
    document.getElementById('modalSeleccionarPedido').addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
    });

    $wire.on('abrirModalDetalleCaja', () => {
        bootstrap.Modal.getOrCreateInstance(modalDetalleCaja).show();
    });

    // Abrir modal de confirmación: cerrar primero el modal principal para que Bootstrap
    // cree un backdrop limpio al mostrar el modal de confirmación
    document.addEventListener('abrirConfirmCierre', () => {
        const selEl     = document.getElementById('modalSeleccionarPedido');
        const confirmEl = document.getElementById('modalConfirmCierre');
        selEl.addEventListener('hidden.bs.modal', () => {
            bootstrap.Modal.getOrCreateInstance(confirmEl).show();
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(selEl).hide();
    });

    // Botón Volver: cerrar confirmación → reabrir modal principal
    document.getElementById('btn-volver-cierre').addEventListener('click', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmCierre')).hide();
    });

    // Al cerrar el modal de confirmación → reabrir modal principal
    document.getElementById('modalConfirmCierre').addEventListener('hidden.bs.modal', () => {
        $nextTick(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeleccionarPedido')).show();
        });
    });

    $wire.on('cerrarModalConfirmCierre', () => {
        const m = document.getElementById('modalConfirmCierre');
        if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
        // El listener hidden.bs.modal se encarga de reabrir el modal principal
    });

    $wire.on('abrirComprobanteCaja', ({ idVenta }) => {
        /*fetch('{{ route('Gestionventas.imprimir_ticketera_escpos') }}?venta_id=' + idVenta)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    alert('Error al imprimir ticket: ' + (data.error ?? 'Error desconocido'));
                }
            })
            .catch(err => {
                alert('No se pudo conectar con la impresora. Verifique que la impresora "Ticketera" esté disponible.');
                console.error('ESC/POS error:', err);
            });*/
        window.open('{{ route('Gestionventas.imprimir_ticketera_venta') }}?venta_id=' + idVenta, '_blank');
    });

    // Auto-foco en Contado al entrar a cobrar
    $wire.on('vistaCobrando', () => {
        $nextTick(() => {
            const btn = document.getElementById('btn-contado');
            if (btn) btn.focus();
        });
    });

    // Navegación por teclado
    document.addEventListener('keydown', (e) => {
        const modalAbierto = document.getElementById('modalSeleccionarPedido').classList.contains('show');

        // F1/F2/F3/F4 dentro del modal: cambiar tabs
        if (modalAbierto) {
            if (e.key === 'F1') {
                e.preventDefault();
                $wire.set('tabModal', 'pedidos').then(() => enfocarPrimerCobrar());
                return;
            }
            if (e.key === 'F2') {
                e.preventDefault();
                $wire.set('tabModal', 'proformas').then(() => enfocarPrimerCobrar());
                return;
            }
            if (e.key === 'F3') {
                e.preventDefault();
                $wire.set('tabModal', 'resumen_ventas');
                return;
            }
            if (e.key === 'F4') {
                e.preventDefault();
                $wire.set('tabModal', 'cierre_caja');
                return;
            }
            return; // si el modal está abierto no procesar más atajos
        }

        // F2: registrar venta (solo cuando el modal está cerrado)
        if (e.key === 'F2') {
            const btnRegistrar = document.getElementById('btn-registrar-venta');
            if (btnRegistrar && !btnRegistrar.disabled) {
                e.preventDefault();
                btnRegistrar.click();
            }
            return;
        }

        // Tab en Registrar Venta vuelve a Forma de pago (ciclo)
        const btnRegistrar = document.getElementById('btn-registrar-venta');
        if (document.activeElement === btnRegistrar && e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            const btnContado = document.getElementById('btn-contado');
            if (btnContado) btnContado.focus();
            return;
        }

        // Tab desde Crédito salta a primera cantidad cuando no hay medios de pago
        const btnCredito = document.getElementById('btn-credito');
        if (document.activeElement === btnCredito && e.key === 'Tab' && !e.shiftKey) {
            const cantidades = document.querySelectorAll('[data-cantidad-cobrar]');
            if (cantidades.length) {
                e.preventDefault();
                cantidades[0].focus();
            }
        }
    }, true);
</script>
@endscript
