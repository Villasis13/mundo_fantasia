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
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Producto</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.500ms="buscarProducto" placeholder="Nombre o código">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100" wire:click="generar"><i class="fa fa-magnifying-glass me-1"></i> Generar</button>
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
            <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="text-muted small">Productos en alerta (≤ mínimo)</div><div class="fs-5 fw-bold text-warning">{{ $totalAlertas }}</div></div></div></div>
            <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="text-muted small">Sin stock (0 o menos)</div><div class="fs-5 fw-bold text-danger">{{ $sinStock }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small"><i class="fa fa-triangle-exclamation me-1"></i> Productos con stock menor o igual al mínimo</span>
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
                                <th>Sucursal</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-center pe-3">Faltante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $f)
                            <tr class="{{ $f->ps_stock <= 0 ? 'table-danger' : '' }}">
                                <td class="ps-3 small fw-semibold">{{ $f->pro_nombre }}</td>
                                <td class="small text-muted">{{ $f->pro_codigo ?? '—' }}</td>
                                <td class="small">{{ $f->tienda_nombre }}</td>
                                <td class="text-center small fw-semibold {{ $f->ps_stock <= 0 ? 'text-danger' : 'text-warning' }}">{{ number_format($f->ps_stock, 2) }}</td>
                                <td class="text-center small">{{ number_format($f->ps_stock_minimo, 2) }}</td>
                                <td class="text-center pe-3 small fw-semibold text-danger">{{ number_format($f->faltante, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa fa-circle-check fa-2x d-block mb-2 opacity-50 text-success"></i>No hay productos por debajo del mínimo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($filas->hasPages())<div class="card-footer py-2">{{ $filas->links() }}</div>@endif
        </div>
    @else
        <div class="text-center text-muted py-5"><i class="fa fa-triangle-exclamation fa-3x mb-3 d-block opacity-50"></i>Selecciona los filtros y presiona <strong>Generar</strong>.</div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
