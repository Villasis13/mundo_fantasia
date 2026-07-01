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
                    <label class="form-label small fw-semibold mb-1">Vendedor</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroVendedor">
                        <option value="0">Todos</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->id_users }}">{{ $v->nombre_users }}</option>
                        @endforeach
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
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th class="text-center">Moneda</th>
                            <th class="text-center">Condición</th>
                            <th>Tipo de pago</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Descuento</th>
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
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold text-primary">{{ $vta->venta_serie }}-{{ str_pad($vta->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</span>
                                    <span class="badge bg-light text-dark border ms-1">{{ $tipoLbl }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($vta->venta_fecha)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($vta->venta_fecha)->format('H:i:s') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($clienteNom, 28) }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $vta->cliente_numero }}</div>
                                </td>
                                <td>{{ $vta->nombre_users ?? '—' }}</td>
                                <td class="text-center">{{ $vta->moneda_abrev ?: ($vta->moneda_simbolo ?: 'PEN') }}</td>
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
                                <td class="text-end">S/ {{ number_format($subtotal, 2) }}</td>
                                <td class="text-end text-danger">S/ {{ number_format($vta->venta_totaldescuento, 2) }}</td>
                                <td class="text-end fw-bold text-primary">S/ {{ number_format($vta->venta_total, 2) }}</td>
                                <td class="text-center pe-3">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Imprimir comprobante"
                                                wire:click="reimprimir({{ $vta->id_venta }})"
                                                wire:loading.attr="disabled" wire:target="reimprimir({{ $vta->id_venta }})">
                                            <span wire:loading.remove wire:target="reimprimir({{ $vta->id_venta }})"><i class="fa-solid fa-print"></i></span>
                                            <span wire:loading wire:target="reimprimir({{ $vta->id_venta }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        @can('registro_ventas.actualizar')
                                        <button type="button" class="btn btn-sm btn-outline-warning" title="Rectificar / Editar"
                                                wire:click="abrirRectificar({{ $vta->id_venta }})"
                                                wire:loading.attr="disabled" wire:target="abrirRectificar({{ $vta->id_venta }})">
                                            <span wire:loading.remove wire:target="abrirRectificar({{ $vta->id_venta }})"><i class="fa-solid fa-pen"></i></span>
                                            <span wire:loading wire:target="abrirRectificar({{ $vta->id_venta }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        @endcan
                                        @can('generar_nota.listar')
                                        <a href="{{ route('facturacion.generar_nota', ['id' => $vta->id_venta, 'tipo' => '07', 'motivo' => '01']) }}"
                                           class="btn btn-sm btn-outline-danger" title="Nota de Crédito por anulación de la operación">
                                            <i class="fa-solid fa-file-circle-minus"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-4"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron ventas con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ventas->hasPages())<div class="card-footer py-2">{{ $ventas->links() }}</div>@endif
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
