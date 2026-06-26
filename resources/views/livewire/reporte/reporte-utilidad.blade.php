<div class="container-fluid py-3">

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Producto</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.500ms="buscarProducto" placeholder="Nombre o código">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary btn-sm w-100" wire:click="generar">
                        <i class="fa fa-magnifying-glass"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($buscado)
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button class="btn btn-outline-danger btn-sm" wire:click="imprimirPdf"><i class="fa fa-file-pdf me-1"></i> PDF</button>
            <button class="btn btn-outline-success btn-sm" wire:click="imprimirExcel"><i class="fa fa-file-excel me-1"></i> Excel</button>
        </div>
        {{-- Tarjetas resumen --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">Total ventas</div>
                        <div class="fs-5 fw-bold text-primary">S/ {{ number_format($totales['venta'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">Total costo</div>
                        <div class="fs-5 fw-bold text-warning">S/ {{ number_format($totales['costo'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">Utilidad ({{ $totales['venta'] > 0 ? number_format($totales['utilidad'] / $totales['venta'] * 100, 1) : '0.0' }}%)</div>
                        <div class="fs-5 fw-bold {{ $totales['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">S/ {{ number_format($totales['utilidad'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small"><i class="fa fa-chart-line me-1"></i> Utilidad por producto</span>
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Producto</th>
                                <th>Código</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Venta</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Utilidad</th>
                                <th class="text-end pe-3">Margen %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $f)
                            <tr>
                                <td class="ps-3 small fw-semibold">{{ $f->producto }}</td>
                                <td class="small text-muted">{{ $f->pro_codigo ?? '—' }}</td>
                                <td class="text-center small">{{ number_format($f->cantidad, 2) }}</td>
                                <td class="text-end small text-primary">S/ {{ number_format($f->total_venta, 2) }}</td>
                                <td class="text-end small text-warning">S/ {{ number_format($f->total_costo, 2) }}</td>
                                <td class="text-end small fw-semibold {{ $f->utilidad >= 0 ? 'text-success' : 'text-danger' }}">S/ {{ number_format($f->utilidad, 2) }}</td>
                                <td class="text-end pe-3 small">{{ $f->total_venta > 0 ? number_format($f->utilidad / $f->total_venta * 100, 1) : '0.0' }}%</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron ventas en el período.</td></tr>
                            @endforelse
                        </tbody>
                        @if($filas->count() > 0)
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="ps-3">TOTAL</td>
                                <td class="text-end text-primary">S/ {{ number_format($totales['venta'], 2) }}</td>
                                <td class="text-end text-warning">S/ {{ number_format($totales['costo'], 2) }}</td>
                                <td class="text-end {{ $totales['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">S/ {{ number_format($totales['utilidad'], 2) }}</td>
                                <td class="text-end pe-3">{{ $totales['venta'] > 0 ? number_format($totales['utilidad'] / $totales['venta'] * 100, 1) : '0.0' }}%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @if($filas->hasPages())
            <div class="card-footer py-2">{{ $filas->links() }}</div>
            @endif
        </div>
    @else
        <div class="text-center text-muted py-5">
            <i class="fa fa-chart-line fa-3x mb-3 d-block opacity-50"></i>
            Selecciona los filtros y presiona <strong>Generar</strong>.
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
