<div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-hand-holding-dollar me-2 text-primary"></i>Reporte CxC — Aging</h5>
            <small class="text-muted">Cuentas por cobrar con antigüedad por cliente</small>
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
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Seleccionar sucursal</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Cliente</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="filtroCliente" placeholder="Nombre o RUC/DNI">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tipo cliente</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroVinculada">
                        <option value="">— Todos —</option>
                        <option value="1">Solo vinculadas</option>
                        <option value="0">Excluir vinculadas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vence desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vence hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill" wire:click="generar">
                        <i class="fa fa-search me-1"></i>Generar
                    </button>
                    @can('reporte_cxc.exportar')
                    @if($buscado && $reporte)
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

    @if($buscado && $reporte)
    @php $t = $reporte['totales']; @endphp

    {{-- Aging totales --}}
    <div class="mb-2 px-1" style="font-size:0.8rem; line-height:1.8;">
        <span class="text-muted">Pendiente:</span>
        <span class="fw-semibold">S/ {{ number_format($t['total'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Corriente:</span>
        <span class="fw-semibold text-success">S/ {{ number_format($t['corriente'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">1-30d:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($t['dias_1_30'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">31-60d:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($t['dias_31_60'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">61-90d:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($t['dias_61_90'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">+90d:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($t['dias_mas_90'], 2) }}</span>
    </div>

    {{-- Tabla por cliente --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Cliente</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Corriente</th>
                            <th class="text-end">1-30d</th>
                            <th class="text-end">31-60d</th>
                            <th class="text-end">61-90d</th>
                            <th class="text-end pe-3">+90d</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reporte['clientes'] as $cl)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold small">{{ $cl['nombre'] }}
                                    @if($cl['es_vinculada'] ?? false)
                                        <span class="badge bg-primary ms-1" style="font-size:0.65rem;">Vinculada</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $cl['numero'] }}</div>
                            </td>
                            <td class="text-end small fw-semibold">S/ {{ number_format($cl['total'], 2) }}</td>
                            <td class="text-end small text-success">S/ {{ number_format($cl['corriente'], 2) }}</td>
                            <td class="text-end small {{ $cl['dias_1_30'] > 0 ? 'text-warning' : 'text-muted' }}">
                                S/ {{ number_format($cl['dias_1_30'], 2) }}
                            </td>
                            <td class="text-end small {{ $cl['dias_31_60'] > 0 ? 'text-warning' : 'text-muted' }}">
                                S/ {{ number_format($cl['dias_31_60'], 2) }}
                            </td>
                            <td class="text-end small {{ $cl['dias_61_90'] > 0 ? 'text-danger' : 'text-muted' }}">
                                S/ {{ number_format($cl['dias_61_90'], 2) }}
                            </td>
                            <td class="text-end pe-3 small {{ $cl['dias_mas_90'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                S/ {{ number_format($cl['dias_mas_90'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No hay cuentas pendientes con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($reporte['clientes']))
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3">TOTAL</td>
                            <td class="text-end">S/ {{ number_format($t['total'], 2) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($t['corriente'], 2) }}</td>
                            <td class="text-end text-warning">S/ {{ number_format($t['dias_1_30'], 2) }}</td>
                            <td class="text-end text-warning">S/ {{ number_format($t['dias_31_60'], 2) }}</td>
                            <td class="text-end text-danger">S/ {{ number_format($t['dias_61_90'], 2) }}</td>
                            <td class="text-end pe-3 text-danger">S/ {{ number_format($t['dias_mas_90'], 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @elseif($buscado)
    <div class="text-center text-muted py-5">
        <i class="fa fa-inbox fa-3x mb-3 d-block opacity-50"></i>
        No se encontraron cuentas pendientes con los filtros seleccionados.
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
