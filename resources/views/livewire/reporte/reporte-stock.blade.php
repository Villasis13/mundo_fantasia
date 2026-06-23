<div>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><span>{{ session('error') }}</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-boxes-stacked me-2 text-primary"></i>Reporte de Stock</h5>
            <small class="text-muted">Stock actual por producto y establecimiento</small>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Buscar producto</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroBusqueda" placeholder="Nombre o código">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Categoría</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCategoria">
                        <option value="">Todas</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id_ca }}">{{ $cat->ca_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado stock</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="ok">OK</option>
                        <option value="critico">Crítico</option>
                        <option value="sin_stock">Sin stock</option>
                    </select>
                </div>
                @can('reporte_stock.exportar')
                <div class="col-auto d-flex gap-1">
                    <button class="btn btn-sm btn-outline-danger" wire:click="exportarPdf" title="PDF"><i class="fa fa-file-pdf"></i></button>
                    <button class="btn btn-sm btn-outline-success" wire:click="exportarExcel" title="Excel"><i class="fa fa-file-excel"></i></button>
                </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-start border-dark border-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Total productos</div>
                    <div class="fw-bold fs-6">{{ number_format($resumen['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-start border-success border-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Stock OK</div>
                    <div class="fw-bold fs-6 text-success">{{ number_format($resumen['ok']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-start border-warning border-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Stock crítico</div>
                    <div class="fw-bold fs-6 text-warning">{{ number_format($resumen['critico']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-start border-danger border-3 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Sin stock</div>
                    <div class="fw-bold fs-6 text-danger">{{ number_format($resumen['sin_stock']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small"><i class="fa fa-list me-1"></i>Detalle de stock</span>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 small">Mostrar</label>
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="25">25</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Sucursal</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Mín.</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $p)
                        @php
                            $rowClass = $p->estado_stock === 'sin_stock' ? 'table-danger' : ($p->estado_stock === 'critico' ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="ps-3 small text-muted">{{ $p->pro_codigo }}</td>
                            <td class="small fw-semibold">{{ $p->pro_nombre }}</td>
                            <td class="small">{{ $p->categoria }}</td>
                            <td class="small">{{ $p->sucursal_nombre }}</td>
                            <td class="text-end small fw-bold {{ $p->ps_stock <= 0 ? 'text-danger' : ($p->ps_stock <= $p->ps_stock_minimo ? 'text-warning' : 'text-success') }}">
                                {{ number_format($p->ps_stock, 0) }}
                            </td>
                            <td class="text-end small text-muted">{{ number_format($p->ps_stock_minimo, 0) }}</td>
                            <td class="text-center">
                                @if($p->estado_stock === 'sin_stock')
                                    <span class="badge bg-danger">Sin stock</span>
                                @elseif($p->estado_stock === 'critico')
                                    <span class="badge bg-warning text-dark">Crítico</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                No se encontraron productos con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($productos->hasPages())
        <div class="card-footer py-2">{{ $productos->links() }}</div>
        @endif
    </div>

    <div wire:loading.flex style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
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
