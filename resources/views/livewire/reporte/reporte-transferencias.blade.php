<div>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><span>{{ session('error') }}</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-arrows-rotate me-2 text-primary"></i>Reporte de Transferencias</h5>
            <small class="text-muted">Movimientos de stock entre establecimientos</small>
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
                    <label class="form-label fw-semibold small">Sucursal (origen o destino)</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-auto d-flex gap-1 align-items-end">
                    <button class="btn btn-sm btn-primary" wire:click="generar">
                        <i class="fa fa-search me-1"></i>Generar
                    </button>
                    @can('reporte_transferencias.exportar')
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
        <span class="text-muted">Transferencias:</span>
        <span class="fw-semibold">{{ number_format($totales['cantidad']) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Líneas de detalle:</span>
        <span class="fw-semibold text-primary">{{ number_format($totales['total_items']) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Unidades transferidas:</span>
        <span class="fw-semibold text-success">{{ number_format($totales['total_unidades']) }}</span>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small"><i class="fa fa-list me-1"></i>Transferencias</span>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="20">20</option><option value="50">50</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>N° Transferencia</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Motivo</th>
                            <th class="text-center">Items</th>
                            <th class="text-center">Unidades</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferencias as $t)
                        <tr>
                            <td class="ps-3 small">{{ \Carbon\Carbon::parse($t->transferencia_fecha)->format('d/m/Y') }}</td>
                            <td class="small fw-semibold">{{ $t->transferencia_numero ?: '—' }}</td>
                            <td class="small">
                                <span class="badge bg-secondary">{{ $t->origen_nombre }}</span>
                            </td>
                            <td class="small">
                                <span class="badge bg-primary">{{ $t->destino_nombre }}</span>
                            </td>
                            <td class="small text-muted">{{ Str::limit($t->transferencia_motivo, 30) ?: '—' }}</td>
                            <td class="text-center small">{{ $t->total_items }}</td>
                            <td class="text-center small fw-semibold">{{ number_format($t->total_unidades) }}</td>
                            <td class="small">{{ $t->nombre_users }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                No se encontraron transferencias con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transferencias->hasPages())
        <div class="card-footer py-2">{{ $transferencias->links() }}</div>
        @endif
    </div>

    @elseif($buscado)
    <div class="text-center text-muted py-5">
        <i class="fa fa-inbox fa-3x mb-3 d-block opacity-50"></i>
        No se encontraron transferencias con los filtros seleccionados.
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
