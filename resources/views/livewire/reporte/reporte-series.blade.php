<div class="container-fluid py-3">

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Todas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Buscar</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.500ms="buscar" placeholder="N° serie, producto o código">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="1">Disponible</option>
                        <option value="2">Vendido</option>
                    </select>
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
                <span class="fw-semibold small"><i class="fa fa-barcode me-1"></i> Registro de series de productos ({{ $total }})</span>
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">N° Serie</th>
                                <th>Producto</th>
                                <th>Código</th>
                                <th class="text-center">Estado</th>
                                <th>Origen</th>
                                <th>Observación</th>
                                <th>Registrado por</th>
                                <th class="pe-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($series as $s)
                            <tr>
                                <td class="ps-3 small fw-semibold text-primary">{{ $s->numero_serie }}</td>
                                <td class="small">{{ $s->pro_nombre }}</td>
                                <td class="small text-muted">{{ $s->pro_codigo ?? '—' }}</td>
                                <td class="text-center">
                                    @if($s->estado == 2)<span class="badge bg-secondary">Vendido</span>
                                    @else<span class="badge bg-success">Disponible</span>@endif
                                </td>
                                <td class="small">
                                    @if($s->id_venta) <span class="badge bg-info text-dark">Venta #{{ $s->id_venta }}</span>
                                    @elseif($s->id_orden_compra) <span class="badge bg-light text-dark border">Compra #{{ $s->id_orden_compra }}</span>
                                    @else — @endif
                                </td>
                                <td class="small text-muted">{{ $s->observacion ?? '—' }}</td>
                                <td class="small">{{ $s->nombre_users ?? '—' }}</td>
                                <td class="pe-3 small">{{ $s->created_at ? \Carbon\Carbon::parse($s->created_at)->format('d/m/Y') : '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron series.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($series->hasPages())<div class="card-footer py-2">{{ $series->links() }}</div>@endif
        </div>
    @else
        <div class="text-center text-muted py-5"><i class="fa fa-barcode fa-3x mb-3 d-block opacity-50"></i>Selecciona los filtros y presiona <strong>Generar</strong>.</div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
