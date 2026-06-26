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
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.500ms="buscarProducto" placeholder="Nombre, código o marca">
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
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small"><i class="fa fa-tags me-1"></i> Lista de precios ({{ $totalProductos }} productos)</span>
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
                                <th>Marca</th>
                                <th>Sucursal</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">P. Público</th>
                                <th class="text-end">P. Mayorista</th>
                                <th class="text-end">P. 3</th>
                                <th class="text-center pe-3">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $f)
                            <tr>
                                <td class="ps-3 small fw-semibold">{{ $f->pro_nombre }}</td>
                                <td class="small text-muted">{{ $f->pro_codigo ?? '—' }}</td>
                                <td class="small">{{ $f->pro_marca ?? '—' }}</td>
                                <td class="small">{{ $f->tienda_nombre }}</td>
                                <td class="text-end small text-warning">S/ {{ number_format($f->pro_costo_total, 2) }}</td>
                                <td class="text-end small fw-semibold text-success">S/ {{ number_format($f->ps_precio_uni, 2) }}</td>
                                <td class="text-end small text-primary">S/ {{ number_format($f->ps_precio_uni_2, 2) }}</td>
                                <td class="text-end small text-muted">S/ {{ number_format($f->ps_precio_uni_3, 2) }}</td>
                                <td class="text-center pe-3 small"><span class="badge {{ $f->ps_stock > 0 ? 'bg-light text-dark border' : 'bg-danger' }}">{{ number_format($f->ps_stock, 2) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron productos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($filas->hasPages())<div class="card-footer py-2">{{ $filas->links() }}</div>@endif
        </div>
    @else
        <div class="text-center text-muted py-5"><i class="fa fa-tags fa-3x mb-3 d-block opacity-50"></i>Selecciona los filtros y presiona <strong>Generar</strong>.</div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
