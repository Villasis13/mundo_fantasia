<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa fa-hand-holding-usd me-2 text-primary"></i>Cuentas por Cobrar
            </h5>
            <small class="text-muted">Cuotas pendientes de cobro a clientes</small>
        </div>
        <div class="d-flex gap-2">
            @can('cuentas_cobrar.exportar')
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
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">

                @if($esSuperAdmin)
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Todas las empresas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">— Todas —</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Cliente</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroCliente"
                           placeholder="Nombre, razón social o RUC/DNI">
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">— Todos —</option>
                        <option value="pendientes">Pendientes</option>
                        <option value="vencidas">Vencidas</option>
                        <option value="por_vencer">Por vencer</option>
                        <option value="pagadas">Pagadas</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>

            </div>
        </div>
    </div>

    {{-- Aging resumen compacto --}}
    <div class="mb-2 px-1" style="font-size:0.8rem; line-height:1.8;">
        <span class="text-muted">Pendiente:</span>
        <span class="fw-semibold">S/ {{ number_format($aging['total_pendiente'],2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Corriente:</span>
        <span class="fw-semibold text-success">S/ {{ number_format($aging['corriente'],2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">1-30d:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($aging['dias_1_30'],2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">31-60d:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($aging['dias_31_60'],2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">61-90d:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($aging['dias_61_90'],2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">+90d:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($aging['dias_mas_90'],2) }}</span>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="fa fa-list me-1"></i>Cuotas por cobrar</span>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 small">Mostrar</label>
                <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">
                                <a href="#" wire:click.prevent="ordenar('cliente_nombre')" class="text-dark text-decoration-none">
                                    Cliente
                                    @if($ordenColumna === 'cliente_nombre')
                                        <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Documento</th>
                            <th>Cuota</th>
                            <th>
                                <a href="#" wire:click.prevent="ordenar('venta_cuota_fecha')" class="text-dark text-decoration-none">
                                    Vencimiento
                                    @if($ordenColumna === 'venta_cuota_fecha')
                                        <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="#" wire:click.prevent="ordenar('venta_cuota_importe')" class="text-dark text-decoration-none">
                                    Importe
                                    @if($ordenColumna === 'venta_cuota_importe')
                                        <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-end">Pagado</th>
                            <th class="text-end">
                                <a href="#" wire:click.prevent="ordenar('saldo')" class="text-dark text-decoration-none">
                                    Saldo
                                    @if($ordenColumna === 'saldo')
                                        <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="#" wire:click.prevent="ordenar('dias_atraso')" class="text-dark text-decoration-none">
                                    Días atraso
                                    @if($ordenColumna === 'dias_atraso')
                                        <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Sede</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cuotas as $cuota)
                        @php
                            $saldo   = (float) $cuota->saldo;
                            $vencida = $cuota->dias_atraso > 0 && $saldo > 0;
                            $pagada  = $saldo <= 0;
                        @endphp
                        <tr class="{{ $vencida ? 'table-danger' : ($pagada ? 'table-success' : '') }}">
                            <td class="ps-3">
                                <div class="fw-semibold small">{{ $cuota->cliente_nombre ?: $cuota->cliente_razonsocial }}
                                    @if($cuota->es_vinculada)
                                        <span class="badge bg-primary ms-1" title="Empresa vinculada del grupo" style="font-size:0.65rem;">Vinculada</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $cuota->cliente_numero }}</div>
                            </td>
                            <td class="small">
                                {{ $cuota->venta_tipo }}-{{ $cuota->venta_serie }}-{{ str_pad($cuota->venta_correlativo,8,'0',STR_PAD_LEFT) }}
                            </td>
                            <td class="text-center small">{{ $cuota->venta_cuota_numero }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($cuota->venta_cuota_fecha)->format('d/m/Y') }}</td>
                            <td class="text-end small">S/ {{ number_format($cuota->venta_cuota_importe, 2) }}</td>
                            <td class="text-end small">S/ {{ number_format($cuota->total_pagado, 2) }}</td>
                            <td class="text-end small fw-semibold {{ $vencida ? 'text-danger' : '' }}">S/ {{ number_format($saldo, 2) }}</td>
                            <td class="text-center small">
                                @if($vencida)
                                    <span class="badge bg-danger">{{ $cuota->dias_atraso }}d</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">
                                @if($cuota->tienda_nombre)
                                    <div class="fw-semibold">{{ $cuota->tienda_nombre }}</div>
                                @endif
                                @if($cuota->empresa_nombrecomercial)
                                    <div class="text-muted" style="font-size:0.75rem">{{ $cuota->empresa_nombrecomercial }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($pagada)
                                    <span class="badge bg-success">Pagada</span>
                                @elseif($vencida)
                                    <span class="badge bg-danger">Vencida</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                No se encontraron cuotas con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cuotas->hasPages())
        <div class="card-footer py-2">
            {{ $cuotas->links() }}
        </div>
        @endif
    </div>

    {{-- Loader grande --}}
    <div wire:loading.flex style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <img src="{{ asset('isologo.ico') }}" style="width:70px;height:70px;object-fit:contain;animation:spin-rep 1s linear infinite;filter:drop-shadow(0 6px 18px rgba(0,0,0,.35));" alt="">
            <div style="color:#fff;font-weight:600;font-size:14px;">Cargando...</div>
        </div>
    </div>
    <style>@keyframes spin-rep{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>
