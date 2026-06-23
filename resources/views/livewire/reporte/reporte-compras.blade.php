<div>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><span>{{ session('error') }}</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-cart-shopping me-2 text-primary"></i>Reporte de Compras</h5>
            <small class="text-muted">Órdenes de compra por proveedor y rango de fechas</small>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model.live="tipoReporte">
                        <option value="compras">Compras</option>
                        <option value="detallado">Compras Detallado</option>
                        <option value="resumen">Resumen Compras</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Proveedor</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroProveedor" placeholder="Nombre o RUC">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado proceso</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstadoOC">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_transito">En tránsito</option>
                        <option value="recibida">Recibida</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">Activo</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-1 align-items-end">
                    <button class="btn btn-sm btn-primary" wire:click="generar">
                        <i class="fa fa-search me-1"></i>Generar
                    </button>
                    @can('reporte_compras.exportar')
                    @if($buscado && $totales)
                    <button class="btn btn-sm btn-outline-danger fw-semibold"
                            wire:click="exportarPdf"
                            wire:loading.attr="disabled" wire:target="exportarPdf">
                        <span wire:loading.remove wire:target="exportarPdf">
                            <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> PDF
                        </span>
                        <span wire:loading wire:target="exportarPdf">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    <button class="btn btn-sm btn-outline-success fw-semibold"
                            wire:click="exportarExcel"
                            wire:loading.attr="disabled" wire:target="exportarExcel">
                        <span wire:loading.remove wire:target="exportarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Excel
                        </span>
                        <span wire:loading wire:target="exportarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if($buscado && $totales)

    {{-- Totales --}}
    <div class="mb-2 px-1" style="font-size:0.8rem; line-height:1.8;">
        @if($totales['tipo'] === 'compras')
            <span class="text-muted">Órdenes:</span>
            <span class="fw-semibold">{{ number_format($totales['cantidad']) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Mercadería:</span>
            <span class="fw-semibold text-primary">S/ {{ number_format($totales['total'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Flete:</span>
            <span class="fw-semibold text-warning">S/ {{ number_format($totales['flete'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">G. Operativos:</span>
            <span class="fw-semibold text-info">S/ {{ number_format($totales['gastos'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Gran Total:</span>
            <span class="fw-semibold text-success">S/ {{ number_format($totales['gran_total'], 2) }}</span>
        @elseif($totales['tipo'] === 'detallado')
            <span class="text-muted">Ítems:</span>
            <span class="fw-semibold">{{ number_format($totales['cantidad']) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Total costo:</span>
            <span class="fw-semibold text-primary">S/ {{ number_format($totales['total_costo'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Total flete:</span>
            <span class="fw-semibold text-warning">S/ {{ number_format($totales['total_flete'], 2) }}</span>
        @else
            <span class="text-muted">Proveedores:</span>
            <span class="fw-semibold">{{ number_format($totales['cantidad']) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Órdenes:</span>
            <span class="fw-semibold">{{ number_format($totales['ordenes']) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Mercadería:</span>
            <span class="fw-semibold text-primary">S/ {{ number_format($totales['mercaderia'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Flete:</span>
            <span class="fw-semibold text-warning">S/ {{ number_format($totales['flete'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">G. Operativos:</span>
            <span class="fw-semibold text-info">S/ {{ number_format($totales['gastos'], 2) }}</span>
            <span class="text-muted mx-1">|</span>
            <span class="text-muted">Gran Total:</span>
            <span class="fw-semibold text-success">S/ {{ number_format($totales['gran_total'], 2) }}</span>
        @endif
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small">
                <i class="fa fa-list me-1"></i>
                @if($totales['tipo'] === 'detallado') Detalle de productos por orden
                @elseif($totales['tipo'] === 'resumen') Resumen por proveedor
                @else Órdenes de compra
                @endif
            </span>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">

                {{-- TABLA: Compras --}}
                @if($totales['tipo'] === 'compras')
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>N° Orden</th>
                            <th>Proveedor</th>
                            <th>Sucursal</th>
                            <th>Doc.</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Mercadería</th>
                            <th class="text-end">Flete</th>
                            <th class="text-end">G. Oper.</th>
                            <th class="text-end pe-3">Total</th>
                            <th class="text-center">Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $oc)
                        <tr>
                            <td class="ps-3 small">{{ \Carbon\Carbon::parse($oc->orden_compra_fecha)->format('d/m/Y') }}</td>
                            <td class="small"><span class="fw-semibold">{{ $oc->orden_compra_codigo ?: $oc->orden_compra_numero }}</span></td>
                            <td>
                                <div class="fw-semibold small">{{ Str::limit($oc->proveedores_nombre, 25) }}</div>
                                <div class="text-muted" style="font-size:0.7rem">{{ $oc->proveedores_numero_documento }}</div>
                            </td>
                            <td class="small">{{ $oc->sucursal_nombre }}</td>
                            <td class="small text-muted">
                                @if($oc->orden_compra_tipo_doc)
                                    <span class="badge bg-secondary">{{ $oc->orden_compra_tipo_doc }}</span>
                                    {{ $oc->orden_compra_numero_doc }}
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                @php $est = $oc->orden_compra_estado ?? ''; @endphp
                                @if($est === 'recibida') <span class="badge bg-success">Recibida</span>
                                @elseif($est === 'en_transito') <span class="badge bg-info text-dark">En tránsito</span>
                                @elseif($est === 'cancelada') <span class="badge bg-danger">Cancelada</span>
                                @else <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end small">S/ {{ number_format($oc->orden_compra_total, 2) }}</td>
                            <td class="text-end small text-warning">S/ {{ number_format($oc->orden_compra_flete, 2) }}</td>
                            <td class="text-end small text-info">S/ {{ number_format($oc->orden_compra_gastos_operativos, 2) }}</td>
                            <td class="text-end pe-3 small fw-semibold text-success">
                                S/ {{ number_format($oc->orden_compra_total + $oc->orden_compra_flete + $oc->orden_compra_gastos_operativos, 2) }}
                            </td>
                            <td class="text-center small"><span class="badge bg-light text-dark border">{{ $oc->total_items }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron órdenes.</td></tr>
                        @endforelse
                    </tbody>
                    @if($ordenes->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6" class="ps-3">TOTAL</td>
                            <td class="text-end">S/ {{ number_format($totales['total'], 2) }}</td>
                            <td class="text-end text-warning">S/ {{ number_format($totales['flete'], 2) }}</td>
                            <td class="text-end text-info">S/ {{ number_format($totales['gastos'], 2) }}</td>
                            <td class="text-end pe-3 text-success">S/ {{ number_format($totales['gran_total'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                {{-- TABLA: Compras Detallado --}}
                @elseif($totales['tipo'] === 'detallado')
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>N° Orden</th>
                            <th>Proveedor</th>
                            <th>Sucursal</th>
                            <th>Producto</th>
                            <th>Código</th>
                            <th class="text-center">Cant. Ped.</th>
                            <th class="text-center">Cant. Rec.</th>
                            <th class="text-end">Costo</th>
                            <th class="text-end pe-3">Flete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $oc)
                        <tr>
                            <td class="ps-3 small">{{ \Carbon\Carbon::parse($oc->orden_compra_fecha)->format('d/m/Y') }}</td>
                            <td class="small fw-semibold">{{ $oc->orden_compra_codigo ?: $oc->orden_compra_numero }}</td>
                            <td>
                                <div class="fw-semibold small">{{ Str::limit($oc->proveedores_nombre, 22) }}</div>
                                <div class="text-muted" style="font-size:0.7rem">{{ $oc->proveedores_numero_documento }}</div>
                            </td>
                            <td class="small">{{ $oc->sucursal_nombre }}</td>
                            <td class="small">{{ $oc->detalle_orden_nombre_producto }}</td>
                            <td class="small text-muted">{{ $oc->pro_codigo ?? '—' }}</td>
                            <td class="text-center small">{{ number_format($oc->detalle_compra_cantidad, 2) }}</td>
                            <td class="text-center small">{{ $oc->detalle_compra_cantidad_recibida !== null ? number_format($oc->detalle_compra_cantidad_recibida, 2) : '—' }}</td>
                            <td class="text-end small">S/ {{ number_format($oc->detalle_compra_total_pedido, 2) }}</td>
                            <td class="text-end pe-3 small text-warning">S/ {{ number_format($oc->detalle_flete, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron ítems.</td></tr>
                        @endforelse
                    </tbody>
                    @if($ordenes->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="8" class="ps-3">TOTAL</td>
                            <td class="text-end">S/ {{ number_format($totales['total_costo'], 2) }}</td>
                            <td class="text-end pe-3 text-warning">S/ {{ number_format($totales['total_flete'], 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                {{-- TABLA: Resumen --}}
                @else
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Proveedor</th>
                            <th>RUC / Doc.</th>
                            <th class="text-center">N° Órdenes</th>
                            <th class="text-end">Mercadería</th>
                            <th class="text-end">Flete</th>
                            <th class="text-end">G. Oper.</th>
                            <th class="text-end pe-3">Gran Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $oc)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold small">{{ $oc->proveedores_nombre }}</div>
                            </td>
                            <td class="small text-muted">{{ $oc->proveedores_numero_documento }}</td>
                            <td class="text-center small"><span class="badge bg-light text-dark border">{{ $oc->total_ordenes }}</span></td>
                            <td class="text-end small text-primary">S/ {{ number_format($oc->total_mercaderia, 2) }}</td>
                            <td class="text-end small text-warning">S/ {{ number_format($oc->total_flete, 2) }}</td>
                            <td class="text-end small text-info">S/ {{ number_format($oc->total_gastos, 2) }}</td>
                            <td class="text-end pe-3 small fw-semibold text-success">S/ {{ number_format($oc->gran_total, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron proveedores.</td></tr>
                        @endforelse
                    </tbody>
                    @if($ordenes->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="ps-3">TOTAL</td>
                            <td class="text-end text-primary">S/ {{ number_format($totales['mercaderia'], 2) }}</td>
                            <td class="text-end text-warning">S/ {{ number_format($totales['flete'], 2) }}</td>
                            <td class="text-end text-info">S/ {{ number_format($totales['gastos'], 2) }}</td>
                            <td class="text-end pe-3 text-success">S/ {{ number_format($totales['gran_total'], 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
                @endif

            </div>
        </div>
        @if($ordenes->hasPages())
        <div class="card-footer py-2">{{ $ordenes->links() }}</div>
        @endif
    </div>

    @elseif($buscado)
    <div class="text-center text-muted py-5">
        <i class="fa fa-inbox fa-3x mb-3 d-block opacity-50"></i>
        No se encontraron órdenes de compra con los filtros seleccionados.
    </div>
    @endif

    <div wire:loading.flex wire:target="generar" style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <img src="{{ asset('isologo.ico') }}" style="width:70px;height:70px;object-fit:contain;animation:spin-rep 1s linear infinite;filter:drop-shadow(0 6px 18px rgba(0,0,0,.35));" alt="">
            <div style="color:#fff;font-weight:600;font-size:14px;">Cargando...</div>
        </div>
    </div>
    <style>@keyframes spin-rep{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (event) => { window.open(event.url, '_blank'); });
        });
    </script>
</div>
