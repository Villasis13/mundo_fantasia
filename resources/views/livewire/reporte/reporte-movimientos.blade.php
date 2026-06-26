<div class="container-fluid py-3">

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
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Tipo</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        <option value="1">Ingreso</option>
                        <option value="2">Salida</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary btn-sm w-100" wire:click="generar"><i class="fa fa-magnifying-glass"></i></button>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.500ms="buscarProducto" placeholder="Buscar producto por nombre o código">
                </div>
            </div>
        </div>
    </div>

    @if($buscado)
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button class="btn btn-outline-danger btn-sm" wire:click="imprimirPdf"><i class="fa fa-file-pdf me-1"></i> PDF</button>
            <button class="btn btn-outline-success btn-sm" wire:click="imprimirExcel"><i class="fa fa-file-excel me-1"></i> Excel</button>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="text-muted small">Registros</div><div class="fs-5 fw-bold text-primary">{{ $totales['registros'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="text-muted small">Total Ingresos (uds)</div><div class="fs-5 fw-bold text-success">{{ number_format($totales['ingresos'], 2) }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="text-muted small">Total Salidas (uds)</div><div class="fs-5 fw-bold text-danger">{{ number_format($totales['salidas'], 2) }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small"><i class="fa fa-right-left me-1"></i> Movimientos de productos</span>
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Fecha</th>
                                <th class="text-center">Tipo</th>
                                <th>Motivo</th>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Sucursal</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end pe-3">Costo Unit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movimientos as $m)
                            <tr>
                                <td class="ps-3 small">{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    @if($m->tipo == 1)<span class="badge bg-success">Ingreso</span>
                                    @else<span class="badge bg-danger">Salida</span>@endif
                                </td>
                                <td class="small">{{ $m->motivo }}@if($m->concepto)<div class="text-muted" style="font-size:.7rem">{{ $m->concepto }}</div>@endif</td>
                                <td class="small">{{ $m->pro_nombre ?? '—' }}</td>
                                <td class="small text-muted">{{ $m->pro_codigo ?? '—' }}</td>
                                <td class="small">{{ $m->tienda_nombre ?? '—' }}</td>
                                <td class="text-center small fw-semibold {{ $m->tipo == 1 ? 'text-success' : 'text-danger' }}">{{ number_format($m->cantidad, 2) }}</td>
                                <td class="text-end pe-3 small">S/ {{ number_format($m->costo_unitario, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron movimientos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($movimientos->hasPages())<div class="card-footer py-2">{{ $movimientos->links() }}</div>@endif
        </div>
    @else
        <div class="text-center text-muted py-5"><i class="fa fa-right-left fa-3x mb-3 d-block opacity-50"></i>Selecciona los filtros y presiona <strong>Generar</strong>.</div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
