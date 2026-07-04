<div class="container-fluid py-3">

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Título fuera del card (igual que "Registro de Ingresos - Compras") --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Registro de Ventas
            </h5>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Serie</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroSerie" placeholder="Ej. F001">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Número</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroNumero" placeholder="Correlativo">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroCliente" placeholder="Nombre o doc.">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Punto de venta</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroPuntoVenta">
                        <option value="0">Todos</option>
                        @foreach($puntosVenta as $pv)
                            <option value="{{ $pv->id_users }}">{{ $pv->nombre_users }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Seleccionar</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="anulado">Anulado</option>
                        <option value="enviado">Enviado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small">{{ $ventas->total() }} comprobante(s)</span>
            <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                <option value="20">20</option><option value="50">50</option><option value="100">100</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Comprobante</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th class="text-center">Condición</th>
                            <th>Tipo de pago</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $vta)
                            @php
                                $tipoLbl = ['01'=>'Factura','03'=>'Boleta','20'=>'Nota de Venta','07'=>'N. Crédito','08'=>'N. Débito'][$vta->venta_tipo] ?? $vta->venta_tipo;
                                $subtotal = (float)$vta->venta_totalgravada + (float)$vta->venta_totalexonerada + (float)$vta->venta_totalinafecta;
                                $clienteNom = $vta->id_tipo_documento == 4 ? ($vta->cliente_razonsocial ?: $vta->cliente_nombre) : ($vta->cliente_nombre ?: $vta->cliente_razonsocial);
                                $pagos = $pagosPorVenta[$vta->id_venta] ?? [];
                            @endphp
                            <tr @if($vta->tiene_nc ?? 0) style="background-color:#f8d7da;" @endif>
                                <td class="ps-3">
                                    <span class="fw-semibold text-primary">{{ $vta->venta_serie }}-{{ str_pad($vta->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</span>
                                    <span class="badge bg-light text-dark border ms-1">{{ $tipoLbl }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($vta->venta_fecha)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($clienteNom, 28) }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $vta->cliente_numero }}</div>
                                </td>
                                <td class="text-center">
                                    @if($vta->id_formas_pago == 2)
                                        <span class="badge bg-warning text-dark">Crédito</span>
                                    @else
                                        <span class="badge bg-success">Contado</span>
                                    @endif
                                </td>
                                <td>
                                    @if(count($pagos))
                                        @if(count($pagos) === 1)
                                            <span class="badge bg-info text-dark">{{ $pagos[0] }}</span>
                                        @else
                                            <ul class="mb-0 ps-3" style="font-size:.74rem;">
                                                @foreach($pagos as $tp)<li>{{ $tp }}</li>@endforeach
                                            </ul>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($vta->tiene_nc ?? 0)
                                        <span class="badge bg-danger">Anulado por crédito</span>
                                    @elseif(($vta->venta_estado_sunat ?? 0) == 1)
                                        <span class="badge bg-success">Enviado</span>
                                    @elseif($vta->venta_tipo == '20')
                                        <span class="badge bg-secondary">Nuevo</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-primary">S/ {{ number_format($vta->venta_total, 2) }}</td>
                                <td class="text-center pe-3">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-info" title="Ver detalle"
                                                wire:click="verDetalle({{ $vta->id_venta }})"
                                                wire:loading.attr="disabled" wire:target="verDetalle({{ $vta->id_venta }})">
                                            <span wire:loading.remove wire:target="verDetalle({{ $vta->id_venta }})"><i class="fa-solid fa-eye"></i></span>
                                            <span wire:loading wire:target="verDetalle({{ $vta->id_venta }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Imprimir comprobante"
                                                wire:click="reimprimir({{ $vta->id_venta }})"
                                                wire:loading.attr="disabled" wire:target="reimprimir({{ $vta->id_venta }})">
                                            <span wire:loading.remove wire:target="reimprimir({{ $vta->id_venta }})"><i class="fa-solid fa-print"></i></span>
                                            <span wire:loading wire:target="reimprimir({{ $vta->id_venta }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        @if(!($vta->tiene_nc ?? 0))
                                        @can('registro_ventas.actualizar')
                                        <button type="button" class="btn btn-sm btn-outline-warning" title="Rectificar / Editar"
                                                wire:click="abrirRectificar({{ $vta->id_venta }})"
                                                wire:loading.attr="disabled" wire:target="abrirRectificar({{ $vta->id_venta }})">
                                            <span wire:loading.remove wire:target="abrirRectificar({{ $vta->id_venta }})"><i class="fa-solid fa-pen"></i></span>
                                            <span wire:loading wire:target="abrirRectificar({{ $vta->id_venta }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        @endcan
                                        @if(($vta->venta_estado_sunat ?? 0) == 1)
                                        @can('generar_nota.listar')
                                        <a href="{{ route('facturacion.generar_nota', ['id' => $vta->id_venta, 'tipo' => '07', 'motivo' => '01']) }}"
                                           class="btn btn-sm btn-outline-danger" title="Nota de Crédito por anulación de la operación">
                                            <i class="fa-solid fa-file-circle-minus"></i>
                                        </a>
                                        @endcan
                                        @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">
                                <i class="fa fa-filter fa-2x d-block mb-2 opacity-50"></i>
                                @if($filtrado)
                                    No se encontraron ventas con los filtros seleccionados.
                                @else
                                    Aplique un filtro para mostrar los comprobantes.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ventas->hasPages())<div class="card-footer py-2">{{ $ventas->links() }}</div>@endif
    </div>

    {{-- ══════ MODAL VER DETALLE ══════ --}}
    <div class="modal fade" id="modalDetalleVenta" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background:#1e3a8a;">
                    <h6 class="modal-title fw-bold mb-0 text-white">
                        <i class="fa-solid fa-file-invoice me-2"></i>{{ $detalle['tipo'] ?? 'Comprobante' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="background:#f8fafc;">
                    @if($detalle)
                    <div class="text-center fw-bold py-2 border-bottom" style="background:#e8eefb;color:#1e3a8a;letter-spacing:.05em;font-size:.85rem;">
                        REPORTE DE COMPROBANTE ELECTRÓNICO
                    </div>

                    {{-- Datos del comprobante --}}
                    <div class="px-3 pt-3">
                        <div class="fw-bold text-primary border-bottom pb-1 mb-2" style="font-size:.82rem;">DATOS DEL COMPROBANTE</div>
                        <div class="row g-2" style="font-size:.82rem;">
                            <div class="col-md-6"><span class="text-muted">Tipo de comprobante:</span> <span class="fw-semibold">{{ $detalle['tipo'] }}</span></div>
                            <div class="col-md-6"><span class="text-muted">Número:</span> <span class="fw-semibold">{{ $detalle['numero'] }}</span></div>
                            <div class="col-md-6"><span class="text-muted">Fecha de emisión:</span> <span class="fw-semibold">{{ $detalle['fecha'] }} {{ $detalle['hora'] }}</span></div>
                            <div class="col-md-6"><span class="text-muted">Condición:</span> <span class="fw-semibold">{{ $detalle['condicion'] }}</span></div>
                        </div>
                    </div>

                    {{-- Datos del emisor --}}
                    <div class="px-3 pt-3">
                        <div class="fw-bold text-primary border-bottom pb-1 mb-2" style="font-size:.82rem;">DATOS DEL EMISOR</div>
                        <div class="row g-2" style="font-size:.82rem;">
                            <div class="col-md-6"><span class="text-muted">RUC:</span> <span class="fw-semibold">{{ $detalle['emisor_ruc'] }}</span></div>
                            <div class="col-md-6"><span class="text-muted">Razón social:</span> <span class="fw-semibold">{{ $detalle['emisor_razon'] }}</span></div>
                            <div class="col-12"><span class="text-muted">Domicilio fiscal:</span> <span class="fw-semibold">{{ $detalle['emisor_dom'] }}</span></div>
                        </div>
                    </div>

                    {{-- Datos del comprador --}}
                    <div class="px-3 pt-3">
                        <div class="fw-bold text-primary border-bottom pb-1 mb-2" style="font-size:.82rem;">DATOS DEL COMPRADOR</div>
                        <div class="row g-2" style="font-size:.82rem;">
                            <div class="col-md-6"><span class="text-muted">Tipo documento:</span> <span class="fw-semibold">{{ $detalle['comp_tipo_doc'] }}</span></div>
                            <div class="col-md-6"><span class="text-muted">N.º de documento:</span> <span class="fw-semibold">{{ $detalle['comp_num_doc'] }}</span></div>
                            <div class="col-12"><span class="text-muted">Razón social / Nombre:</span> <span class="fw-semibold">{{ $detalle['comp_razon'] }}</span></div>
                        </div>
                    </div>

                    {{-- Detalle de productos --}}
                    <div class="px-3 pt-3 pb-3">
                        <div class="fw-bold text-primary border-bottom pb-1 mb-2" style="font-size:.82rem;">DETALLE</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 bg-white" style="font-size:.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:70px;">Cant.</th>
                                        <th style="width:90px;">U.M.</th>
                                        <th style="width:90px;">Código</th>
                                        <th>Descripción</th>
                                        <th class="text-end" style="width:90px;">P. Unit.</th>
                                        <th class="text-end" style="width:100px;">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detalleItems as $it)
                                    <tr>
                                        <td class="text-center">{{ number_format($it['cantidad'], 2) }}</td>
                                        <td>{{ $it['um'] }}</td>
                                        <td>{{ $it['codigo'] }}</td>
                                        <td>{{ $it['descripcion'] }}</td>
                                        <td class="text-end">{{ number_format($it['precio'], 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($it['importe'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-2">Sin productos.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    @if($detalle['descuento'] > 0)
                                    <tr><td colspan="5" class="text-end text-muted">Descuento</td><td class="text-end text-danger">- S/ {{ number_format($detalle['descuento'], 2) }}</td></tr>
                                    @endif
                                    <tr><td colspan="5" class="text-end text-muted">Op. Gravada</td><td class="text-end">S/ {{ number_format($detalle['gravada'], 2) }}</td></tr>
                                    @if($detalle['exonerada'] > 0)
                                    <tr><td colspan="5" class="text-end text-muted">Op. Exonerada</td><td class="text-end">S/ {{ number_format($detalle['exonerada'], 2) }}</td></tr>
                                    @endif
                                    @if($detalle['inafecta'] > 0)
                                    <tr><td colspan="5" class="text-end text-muted">Op. Inafecta</td><td class="text-end">S/ {{ number_format($detalle['inafecta'], 2) }}</td></tr>
                                    @endif
                                    <tr><td colspan="5" class="text-end text-muted">IGV (18%)</td><td class="text-end">S/ {{ number_format($detalle['igv'], 2) }}</td></tr>
                                    <tr class="table-primary"><td colspan="5" class="text-end fw-bold">TOTAL</td><td class="text-end fw-bold text-primary">S/ {{ number_format($detalle['total'], 2) }}</td></tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL RECTIFICAR (Editar) ══════ --}}
    <div class="modal fade" id="modalRectificarComprobante" wire:ignore.self tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold mb-0" style="font-size:16px;">
                        <i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Rectifica Datos de Comprobante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Vendedor</label>
                            <select class="form-select form-select-sm" wire:model="rectVendedor">
                                @foreach($rectUsuariosVendedor as $u)<option value="{{ $u->id_users }}">{{ $u->nombre_users }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Cobrador</label>
                            <select class="form-select form-select-sm" wire:model="rectCobrador">
                                @foreach($rectUsuariosCobrador as $u)<option value="{{ $u->id_users }}">{{ $u->nombre_users }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Forma de pago</label>
                            <select class="form-select form-select-sm" wire:model.live="rectFormasPago">
                                <option value="1">Contado</option>
                                <option value="2">Crédito</option>
                            </select>
                        </div>
                    </div>

                    @if((int)$rectFormasPago !== 2)
                    <hr class="my-2">
                    <div class="fw-semibold mb-2" style="font-size:13px;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Medios de pago</div>
                    @foreach($rectMedios as $idx => $medio)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <label class="mb-0 flex-grow-1" style="font-size:14px;">{{ $medio['label'] }}</label>
                        <div class="input-group input-group-sm" style="max-width:140px;">
                            <span class="input-group-text" style="font-size:13px;background:#f8f9fa;">S/</span>
                            <input type="text" inputmode="decimal" class="form-control text-end" style="font-size:15px;font-weight:600;"
                                   wire:model.live="rectMedios.{{ $idx }}.monto">
                        </div>
                    </div>
                    @endforeach
                    @endif

                    <div id="rectificar-alerta" class="alert alert-danger py-2 mt-2 mb-0" style="font-size:14px;display:none;"></div>
                </div>

                @if((int)$rectFormasPago !== 2)
                @php
                    $rectSumaMedios = collect($rectMedios)->sum(fn($m) => (float) str_replace(',', '.', $m['monto'] ?? '0'));
                    $rectDiff = round($rectSumaMedios, 2) - round($rectTotalVenta, 2);
                @endphp
                <div class="d-flex border-top border-bottom px-4 py-2" style="background:#f8f9fa;">
                    <div class="flex-fill text-center border-end pe-3">
                        <div style="font-size:12px;color:#6c757d;text-transform:uppercase;">Total Comprobante</div>
                        <div style="font-size:22px;font-weight:700;color:#1a1a1a;">S/ {{ number_format($rectTotalVenta, 2) }}</div>
                    </div>
                    <div class="flex-fill text-center ps-3">
                        <div style="font-size:12px;color:#6c757d;text-transform:uppercase;">Total Ingresado</div>
                        <div style="font-size:22px;font-weight:700;color:{{ $rectDiff == 0 ? '#166534' : '#dc3545' }};">S/ {{ number_format($rectSumaMedios, 2) }}</div>
                        @if($rectDiff != 0)<div style="font-size:12px;color:#dc3545;">{{ $rectDiff > 0 ? '+' : '' }}{{ number_format($rectDiff, 2) }}</div>@endif
                    </div>
                </div>
                @endif

                <div class="modal-footer px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-bold px-4" wire:click="guardarRectificar"
                            wire:loading.attr="disabled" wire:target="guardarRectificar">
                        <span wire:loading wire:target="guardarRectificar"><span class="spinner-border spinner-border-sm me-1"></span>Guardando...</span>
                        <span wire:loading.remove wire:target="guardarRectificar"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('abrirModalDetalle', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleVenta')).show();
        });
        $wire.on('abrirModalRectificar', () => {
            document.getElementById('rectificar-alerta').style.display = 'none';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRectificarComprobante')).show();
        });
        $wire.on('cerrarModalRectificar', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalRectificarComprobante'));
            if (m) m.hide();
        });
        $wire.on('rectificar-error', (e) => {
            const d = Array.isArray(e) ? e[0] : e;
            const a = document.getElementById('rectificar-alerta');
            a.textContent = d.mensaje || 'Error.'; a.style.display = 'block';
        });
        // Imprimir: mismo flujo que Caja → ticketera ESC/POS
        $wire.on('abrirComprobanteCaja', ({ idVenta }) => {
            fetch('{{ route('Gestionventas.imprimir_ticketera_escpos') }}?venta_id=' + idVenta)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        alert('Error al imprimir ticket: ' + (data.error ?? 'Error desconocido'));
                    }
                })
                .catch(err => {
                    alert('No se pudo conectar con la impresora. Verifique que la impresora "Ticketera" esté disponible.');
                    console.error('ESC/POS error:', err);
                });
        });
    </script>
    @endscript
</div>
