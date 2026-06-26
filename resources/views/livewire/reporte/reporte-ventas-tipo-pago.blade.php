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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100" wire:click="generar">
                        <i class="fa fa-magnifying-glass me-1"></i> Generar
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
                        <div class="text-muted small">Total recaudado</div>
                        <div class="fs-4 fw-bold text-success">S/ {{ number_format($totalGeneral, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">Operaciones</div>
                        <div class="fs-4 fw-bold text-primary">{{ $totalOper }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="text-muted small">Medios de pago</div>
                        <div class="fs-4 fw-bold text-info">{{ $filas->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 fw-semibold small"><i class="fa fa-money-bill-wave me-1"></i> Ventas por tipo de pago</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tipo de Pago</th>
                                <th class="text-center">N° Operaciones</th>
                                <th class="text-end">Total (S/)</th>
                                <th class="text-end pe-3">% Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $f)
                            <tr>
                                <td class="ps-3 fw-semibold small">{{ $f->tipo_pago_nombre }}</td>
                                <td class="text-center small"><span class="badge bg-light text-dark border">{{ $f->num_operaciones }}</span></td>
                                <td class="text-end small fw-semibold text-success">S/ {{ number_format($f->total, 2) }}</td>
                                <td class="text-end pe-3 small">{{ $totalGeneral > 0 ? number_format($f->total / $totalGeneral * 100, 1) : '0.0' }}%</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted"><i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron pagos en el período.</td></tr>
                            @endforelse
                        </tbody>
                        @if($filas->count() > 0)
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">TOTAL</td>
                                <td class="text-center">{{ $totalOper }}</td>
                                <td class="text-end text-success">S/ {{ number_format($totalGeneral, 2) }}</td>
                                <td class="text-end pe-3">100%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="text-center text-muted py-5">
            <i class="fa fa-money-bill-wave fa-3x mb-3 d-block opacity-50"></i>
            Selecciona los filtros y presiona <strong>Generar</strong>.
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
