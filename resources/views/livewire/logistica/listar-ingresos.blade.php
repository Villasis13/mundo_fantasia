<div>

    {{-- Card único: título + filtros + tabla (estándar Gestión de Productos) --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-boxes-packing me-2 text-primary"></i>Listado de ingreso de compras
                    </h5>
                    <small class="text-muted">Consulta los ingresos de compras registrados.</small>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="row g-3 align-items-end mt-1">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Proveedor</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroProveedor">
                        <option value="0">Todos</option>
                        @foreach($proveedores as $pv)
                            <option value="{{ $pv->id_proveedores }}">{{ $pv->proveedores_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="en_transito">En Tránsito</option>
                        <option value="recibido">Recepcionado</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Condición</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCondicion">
                        <option value="">Todos</option>
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Fecha desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Fecha hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">#</th>
                            <th>Proveedor</th>
                            <th class="text-center">Fecha de emisión</th>
                            <th class="text-center">Tipo de comprobante</th>
                            <th class="text-center">N° de comprobante</th>
                            <th class="text-center">Condición</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Importe total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ingresos as $i => $r)
                            <tr>
                                <td class="text-center">{{ $ingresos->firstItem() + $i }}</td>
                                <td>{{ $r->proveedores_nombre }}</td>
                                <td class="text-center">
                                    {{ $r->orden_compra_fecha_emision_doc
                                        ? \Carbon\Carbon::parse($r->orden_compra_fecha_emision_doc)->format('d/m/Y')
                                        : '—' }}
                                </td>
                                <td class="text-center">{{ $r->orden_compra_tipo_doc ?? '—' }}</td>
                                <td class="text-center">{{ $r->orden_compra_numero_doc ?? '—' }}</td>
                                <td class="text-center">
                                    @if($r->condicion_pago === 'credito')
                                        <span class="badge bg-warning text-dark">Crédito</span>
                                    @else
                                        <span class="badge bg-success">Contado</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $estadoMap = ['pendiente'=>['Pendiente','secondary'],'en_transito'=>['En Tránsito','info'],'recibido'=>['Recibido','primary'],'anulado'=>['Anulado','danger']];
                                        $e = $estadoMap[$r->orden_compra_estado] ?? [ucfirst($r->orden_compra_estado),'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $e[1] }}">{{ $e[0] }}</span>
                                </td>
                                <td class="text-end fw-semibold">S/ {{ number_format($r->orden_compra_total, 2) }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            wire:click="verDetalle({{ $r->id_orden_compra }})" title="Ver detalle">
                                        <i class="fa-solid fa-eye me-1"></i> Ver detalle
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-inbox me-1"></i> No se encontraron ingresos con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($ingresos->hasPages())
                <div class="p-3">{{ $ingresos->links(data: ['scrollTo' => false]) }}</div>
            @endif
        </div>
    </div>

    {{-- Modal Detalle --}}
    <div class="modal fade" id="modalDetalleIngreso" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-eye me-2 text-primary"></i>Detalle del ingreso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" style="background:#f8f9fb;">
                    @if($detalleOrden)
                        @php
                            $estadoMap = ['pendiente'=>['Pendiente','#6c757d'],'en_transito'=>['En Tránsito','#e0a800'],'recibido'=>['Recepcionado','#6610f2'],'anulado'=>['Anulado','#dc3545']];
                            $est = $estadoMap[$detalleOrden['estado']] ?? [ucfirst($detalleOrden['estado']),'#6c757d'];
                            $fmt = fn($f) => $f ? \Carbon\Carbon::parse($f)->format('d/m/Y') : '—';
                        @endphp

                        {{-- Cabecera --}}
                        <div class="d-flex flex-wrap align-items-start gap-2 pb-3 mb-3 border-bottom">
                            <div>
                                <div class="fw-bold" style="font-size:1.25rem;">
                                    {{ $detalleOrden['tipo_doc'] }} {{ $detalleOrden['numero_doc'] }}
                                </div>
                                <small class="text-muted">Orden N.° {{ $detalleOrden['numero'] }}</small>
                            </div>
                            <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                                <span class="badge {{ $detalleOrden['condicion'] === 'credito' ? 'text-dark' : '' }}"
                                      style="background:{{ $detalleOrden['condicion'] === 'credito' ? '#ffc107' : '#28a745' }};font-size:.8rem;padding:.45em .8em;">
                                    {{ strtoupper($detalleOrden['condicion']) }}
                                </span>
                                <span class="badge" style="background:{{ $est[1] }};font-size:.8rem;padding:.45em .8em;">{{ strtoupper($est[0]) }}</span>
                                <span class="badge bg-white text-dark border" style="font-size:.8rem;padding:.45em .8em;">
                                    <i class="fa-regular fa-calendar me-1"></i>{{ $fmt($detalleOrden['fecha_emision']) }}
                                </span>
                            </div>
                        </div>

                        {{-- Proveedor + Información del ingreso --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="bg-white border rounded p-3 h-100">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-solid fa-building me-1 text-primary"></i> Proveedor</h6>
                                    <div class="small mb-1"><span class="text-muted">Nombre comercial:</span> <span class="fw-semibold">{{ $detalleProveedor['nombre'] ?? '—' }}</span></div>
                                    <div class="small mb-1"><span class="text-muted">Razón social:</span> <span class="fw-semibold">{{ $detalleProveedor['nombre'] ?? '—' }}</span></div>
                                    <div class="small"><span class="text-muted">RUC:</span> <span class="fw-semibold">{{ $detalleProveedor['ruc'] ?? '—' }}</span></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="bg-white border rounded p-3 h-100">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-regular fa-calendar-days me-1 text-primary"></i> Información del ingreso</h6>
                                    <div class="small mb-1"><span class="text-muted">Fecha de emisión:</span> <span class="fw-semibold">{{ $fmt($detalleOrden['fecha_emision']) }}</span></div>
                                    <div class="small mb-1"><span class="text-muted">Fecha de almacenamiento:</span> <span class="fw-semibold">{{ $fmt($detalleOrden['fecha_almacenamiento']) }}</span></div>
                                    <div class="small"><span class="text-muted">Fecha de recepción:</span> <span class="fw-semibold">{{ $fmt($detalleOrden['fecha_recepcion']) }}</span></div>
                                </div>
                            </div>
                        </div>

                        {{-- Productos --}}
                        <div class="bg-white border rounded p-3 mb-3">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-solid fa-box me-1 text-primary"></i> Productos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Código</th><th>Descripción</th><th>Presentación</th>
                                            <th>Cant.<br>comprada</th><th>Cant.<br>ingresada</th>
                                            <th>Costo unit.</th><th>Flete</th><th>IGV</th><th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($detalleItems as $it)
                                            <tr>
                                                <td>{{ $it['codigo'] }}</td>
                                                <td>{{ $it['descripcion'] }}</td>
                                                <td class="text-center">{{ $it['presentacion'] }}</td>
                                                <td class="text-center">{{ number_format($it['cantidad'], 2) }}</td>
                                                <td class="text-center">{{ $it['cantidad_recibida'] !== null ? number_format($it['cantidad_recibida'], 2) : '—' }}</td>
                                                <td class="text-center">{{ number_format($it['costo_unitario'], 2) }}</td>
                                                <td class="text-center">{{ number_format($it['flete'], 2) }}</td>
                                                <td class="text-center">{{ number_format($it['igv'], 2) }}</td>
                                                <td class="text-end fw-semibold">{{ number_format($it['total'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="9" class="text-center text-muted py-3">Sin productos registrados.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Transportistas --}}
                        <div class="bg-white border rounded p-3 mb-3">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-solid fa-truck me-1 text-primary"></i> Transportistas</h6>
                            @if(count($detalleTransportistas))
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:40px;">#</th><th>Nombre / Razón social</th>
                                                <th>RUC</th><th>N.° documento</th><th class="text-center">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($detalleTransportistas as $i => $t)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>{{ $t['oc_trans_nombre'] ?: '—' }}</td>
                                                    <td>{{ $t['oc_trans_ruc'] ?: '—' }}</td>
                                                    <td>{{ $t['oc_trans_fact'] ?: '—' }}</td>
                                                    <td class="text-center">{{ $t['oc_trans_fecha'] ? \Carbon\Carbon::parse($t['oc_trans_fecha'])->format('d/m/Y') : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info me-1"></i>No se registró transportista para esta compra.</p>
                            @endif
                        </div>

                        {{-- Observaciones + Resumen --}}
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="bg-white border rounded p-3 h-100">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-regular fa-comment me-1 text-primary"></i> Observaciones</h6>
                                    <div class="border rounded p-2 bg-light small" style="min-height:120px;">
                                        {{ trim($detalleObservacion) !== '' ? $detalleObservacion : '' }}
                                        @if(trim($detalleObservacion) === '')
                                            <span class="text-muted fst-italic">Sin observaciones registradas.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="bg-white border rounded p-3 h-100">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fa-regular fa-file-lines me-1 text-primary"></i> Resumen de importes</h6>
                                    @if($detalleResumen)
                                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Subtotal de productos:</span><span>S/ {{ number_format($detalleResumen['subtotal'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Descuento:</span><span>S/ {{ number_format($detalleResumen['descuento'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Subtotal después del descuento:</span><span>S/ {{ number_format($detalleResumen['subtotal_neto'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">IGV:</span><span>S/ {{ number_format($detalleResumen['igv'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Percepción IGV:</span><span>S/ {{ number_format($detalleResumen['percepcion'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between small mb-2 pb-2 border-bottom"><span class="text-muted">Flete:</span><span>S/ {{ number_format($detalleResumen['flete'], 2) }}</span></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">Total del ingreso:</span>
                                            <span class="fw-bold" style="font-size:1.4rem;color:#4b3fd4;">S/ {{ number_format($detalleResumen['total'], 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted py-4 text-center">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>
                            No se pudo cargar el detalle del ingreso.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('abrirModalDetalleIngreso', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleIngreso')).show();
        });
    </script>
    @endscript
</div>
