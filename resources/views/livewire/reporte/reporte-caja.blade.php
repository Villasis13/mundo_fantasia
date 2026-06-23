<div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
        <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-cash-register me-2 text-primary"></i>Reporte de Caja</h5>
            <small class="text-muted">Apertura, ventas, gastos, movimientos y cuadre por período</small>
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
                        <option value="0">Seleccionar empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas las sedes</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Caja</label>
                    <select class="form-select form-select-sm" wire:model.live="idCajaNumeroSeleccionada">
                        <option value="0">Todas las cajas</option>
                        @foreach($cajas as $c)
                            <option value="{{ $c->id_caja_numero }}">{{ $c->caja_numero_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>

                <div class="col-md-auto d-flex gap-1 align-items-end">
                    <button class="btn btn-sm btn-primary" wire:click="generar"
                        wire:loading.attr="disabled" wire:target="generar" title="Generar reporte">
                        <span wire:loading.remove wire:target="generar"><i class="fa fa-search"></i></span>
                        <span wire:loading wire:target="generar"><i class="fa fa-spinner fa-spin"></i></span>
                    </button>
                    @can('reporte_caja.exportar')
                    <button class="btn btn-sm btn-outline-danger" wire:click="exportarPdf" title="Exportar PDF"
                        wire:loading.attr="disabled" wire:target="exportarPdf">
                        <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:18px;height:18px;vertical-align:middle;">
                    </button>
                    <button class="btn btn-sm btn-outline-success" wire:click="exportarExcel" title="Exportar Excel"
                        wire:loading.attr="disabled" wire:target="exportarExcel">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;">
                    </button>
                    @endcan
                </div>

            </div>
        </div>
    </div>

    {{-- Sin datos --}}
    @if($buscado && !$reporte)
    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-info"></i>
        <span>No se encontraron turnos de caja para <strong>{{ $nombreCaja }}</strong> en el período seleccionado.</span>
    </div>
    @endif

    @if($reporte)
    @php $r = $reporte['resumen']; @endphp

    {{-- Turnos --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary bg-opacity-10 border-0 fw-semibold small">
            <i class="fa fa-cash-register me-2 text-primary"></i>
            Turnos — {{ $nombreCaja }} &nbsp;·&nbsp;
            {{ \Carbon\Carbon::parse($filtroDesde)->format('d/m/Y') }}
            @if($filtroDesde !== $filtroHasta) al {{ \Carbon\Carbon::parse($filtroHasta)->format('d/m/Y') }} @endif
            <span class="text-muted fw-normal ms-1">({{ $reporte['turnos']->count() }} turno(s))</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Caja</th>
                            <th>Fecha</th>
                            <th>Apertura por</th>
                            <th>Hora apertura</th>
                            <th>Monto apertura</th>
                            <th>Cierre por</th>
                            <th>Hora cierre</th>
                            <th>Monto cierre</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte['turnos'] as $t)
                        <tr>
                            <td class="ps-3 small fw-semibold">{{ $t->caja_numero_nombre }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($t->caja_fecha)->format('d/m/Y') }}</td>
                            <td class="small">{{ $t->nombre_apertura }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($t->caja_fecha_apertura)->format('H:i') }}</td>
                            <td class="fw-semibold">S/ {{ number_format($t->caja_apertura, 2) }}</td>
                            <td class="small">{{ $t->nombre_cierre ?? '—' }}</td>
                            <td class="small">{{ $t->caja_fecha_cierre ? \Carbon\Carbon::parse($t->caja_fecha_cierre)->format('H:i') : '—' }}</td>
                            <td class="fw-semibold">{{ $t->caja_cierre ? 'S/ '.number_format($t->caja_cierre, 2) : '—' }}</td>
                            <td>
                                @if($t->caja_estado == 1)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Abierta</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Cerrada</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Cuadre --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-scale-balanced me-1 text-primary"></i> Cuadre del período —
            <span class="text-muted fw-normal">¿Dónde está cada sol?</span>
        </div>
        <div class="card-body px-3 pt-2 pb-3">
            <div class="row g-3">

                {{-- Flujo del dinero --}}
                <div class="col-md-7">
                    <p class="text-muted fw-semibold mb-2" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;">Flujo del dinero</p>
                    <table class="table table-sm mb-0" style="font-size:0.82rem;">
                        <tbody>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="text-muted ps-0">Apertura (suma de turnos)</td>
                            <td class="text-end fw-semibold pe-0">S/ {{ number_format($r->monto_apertura, 2) }}</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#1a6b35;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ventas efectivo
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#1a6b35;">S/ {{ number_format($r->ventas_efectivo, 2) }}</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#00a86b;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ventas Yape
                                <span class="badge ms-1" style="background:#e0f7ee;color:#00a86b;font-size:0.62rem;">banco</span>
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#00a86b;">S/ {{ number_format($r->ventas_yape, 2) }}</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#0077b6;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ventas Plin
                                <span class="badge ms-1" style="background:#e6f4fb;color:#0077b6;font-size:0.62rem;">banco</span>
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#0077b6;">S/ {{ number_format($r->ventas_plin, 2) }}</td>
                        </tr>
                        @if($r->total_pagos_cuotas > 0)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#5a9900;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Cobros de cuotas
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#5a9900;">S/ {{ number_format($r->total_pagos_cuotas, 2) }}</td>
                        </tr>
                        @endif
                        @if($r->total_ingresos > 0)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#1a6b35;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ingresos manuales (Mov.)
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#1a6b35;">S/ {{ number_format($r->total_ingresos, 2) }}</td>
                        </tr>
                        @endif
                        @if($r->ingresos_gastos > 0)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#1a6b35;">
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ingresos
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#1a6b35;">S/ {{ number_format($r->ingresos_gastos, 2) }}</td>
                        </tr>
                        @endif
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0 text-danger">
                                <i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Notas de crédito
                            </td>
                            <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->notas_credito, 2) }}</td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0 text-danger">
                                <i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Gastos
                            </td>
                            <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->gastos, 2) }}</td>
                        </tr>
                        @if($r->total_egresos > 0)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0 text-danger">
                                <i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Egresos manuales
                            </td>
                            <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->total_egresos, 2) }}</td>
                        </tr>
                        @endif
                        @if($r->ventas_credito > 0)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="ps-0" style="color:#e600cc;">
                                <i class="fa-solid fa-clock fa-xs me-1 opacity-50"></i>Ventas a crédito (CxC)
                                <span class="badge ms-1" style="background:#fce8ff;color:#b300a0;font-size:0.62rem;">pendiente</span>
                            </td>
                            <td class="text-end fw-semibold pe-0" style="color:#e600cc;">S/ {{ number_format($r->ventas_credito, 2) }}</td>
                        </tr>
                        @endif
                        </tbody>
                    </table>
                    <div class="rounded p-2 mt-2 d-flex justify-content-between align-items-center" style="background:#eef1ff;">
                        <span class="fw-bold small" style="color:#0b1892;">Total sistema (efectivo en caja)</span>
                        <span class="fw-bold" style="color:#0b1892;font-size:1rem;">S/ {{ number_format($r->total_sistema, 2) }}</span>
                    </div>
                </div>

                {{-- ¿Dónde está el dinero? --}}
                <div class="col-md-5">
                    <p class="text-muted fw-semibold mb-2" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;">¿Dónde está el dinero?</p>
                    <div class="d-flex flex-column gap-2" style="font-size:0.82rem;">
                        <div class="rounded p-2" style="background:#eaf3de;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#1a6b35;"><i class="fa-solid fa-money-bill-wave me-1"></i>En caja (efectivo)</span>
                                <span class="fw-bold" style="color:#1a6b35;">S/ {{ number_format($r->total_sistema, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Apertura + ventas ef. + cobros + ingresos − NC − gastos</small>
                        </div>
                        @if($r->ventas_yape > 0)
                        <div class="rounded p-2" style="background:#e0f7ee;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#00a86b;"><i class="fa-solid fa-mobile-screen me-1"></i>En banco / Yape</span>
                                <span class="fw-bold" style="color:#00a86b;">S/ {{ number_format($r->ventas_yape, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">No es efectivo físico</small>
                        </div>
                        @endif
                        @if($r->ventas_plin > 0)
                        <div class="rounded p-2" style="background:#e6f4fb;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#0077b6;"><i class="fa-solid fa-mobile-screen me-1"></i>En banco / Plin</span>
                                <span class="fw-bold" style="color:#0077b6;">S/ {{ number_format($r->ventas_plin, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">No es efectivo físico</small>
                        </div>
                        @endif
                        @if($r->ventas_credito > 0)
                        <div class="rounded p-2" style="background:#fce8ff;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#b300a0;"><i class="fa-solid fa-clock me-1"></i>En CxC (crédito)</span>
                                <span class="fw-bold" style="color:#b300a0;">S/ {{ number_format($r->ventas_credito, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Por cobrar a clientes</small>
                        </div>
                        @endif
                        <div class="rounded p-2" style="background:#fde8e8;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#b30000;"><i class="fa-solid fa-file-circle-minus me-1"></i>Notas de crédito</span>
                                <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->notas_credito, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Devuelto / descontado</small>
                        </div>
                        <div class="rounded p-2" style="background:#fde8e8;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#b30000;"><i class="fa-solid fa-receipt me-1"></i>Gastos</span>
                                <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->gastos, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Gastos del período</small>
                        </div>
                        @if($r->total_egresos > 0)
                        <div class="rounded p-2" style="background:#fde8e8;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#b30000;"><i class="fa-solid fa-arrow-down me-1"></i>Egresos manuales</span>
                                <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->total_egresos, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Salidas manuales de caja</small>
                        </div>
                        @endif
                        @if($r->ingresos_gastos > 0)
                        <div class="rounded p-2" style="background:#eaf3de;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#1a6b35;"><i class="fa-solid fa-arrow-up me-1"></i>Ingresos</span>
                                <span class="fw-bold" style="color:#1a6b35;">S/ {{ number_format($r->ingresos_gastos, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Ingresos registrados en caja</small>
                        </div>
                        @endif
                        @if($r->total_ingresos > 0)
                        <div class="rounded p-2" style="background:#eaf3de;">
                            <div class="d-flex justify-content-between">
                                <span style="color:#1a6b35;"><i class="fa-solid fa-plus-circle me-1"></i>Ingresos manuales (Mov.)</span>
                                <span class="fw-bold" style="color:#1a6b35;">S/ {{ number_format($r->total_ingresos, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Movimientos de ingreso en caja</small>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Ventas por medio + Cobros de cuotas --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 fw-semibold small">
                    <i class="fa-solid fa-receipt me-1 text-primary"></i> Ventas por medio de pago
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($reporte['ventasPorMedio'] as $vm)
                            <tr>
                                <td class="ps-3 small">{{ $vm->tipo_pago_nombre }}</td>
                                <td class="text-end pe-3 fw-semibold">S/ {{ number_format($vm->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted small py-3">Sin ventas en el período</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td class="ps-3 fw-semibold">Total cobrado</td>
                                <td class="text-end pe-3 fw-bold text-primary">S/ {{ number_format($r->total_ventas, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 fw-semibold small">
                    <i class="fa-solid fa-coins me-1 text-success"></i> Cobros de cuotas (crédito)
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($reporte['pagosCuotas'] as $pc)
                            <tr>
                                <td class="ps-3 small">{{ $pc->tipo_pago_nombre }}</td>
                                <td class="text-end pe-3 fw-semibold">S/ {{ number_format($pc->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted small py-3">Sin cobros</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td class="ps-3 fw-semibold">Total cobros</td>
                                <td class="text-end pe-3 fw-bold text-success">S/ {{ number_format($r->total_pagos_cuotas, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Movimientos manuales --}}
    @if($reporte['movimientos']->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-list me-1"></i> Movimientos manuales del período
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tipo</th>
                            <th>Concepto</th>
                            <th>Medio de pago</th>
                            <th>N° Operación</th>
                            <th>Registrado por</th>
                            <th>Fecha / Hora</th>
                            <th class="text-end pe-3">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte['movimientos'] as $mov)
                        <tr>
                            <td class="ps-3">
                                @if($mov->tipo == 1)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Ingreso</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Egreso</span>
                                @endif
                            </td>
                            <td class="small">{{ $mov->concepto }}</td>
                            <td class="small">{{ $mov->tipo_pago_nombre ?? '—' }}</td>
                            <td class="small text-muted">{{ $mov->numero_operacion ?? '—' }}</td>
                            <td class="small">{{ $mov->nombre_users }}</td>
                            <td class="small text-muted">{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end pe-3 fw-semibold {{ $mov->tipo == 1 ? 'text-success' : 'text-danger' }}">
                                S/ {{ number_format($mov->monto, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Comprobantes --}}
    @if($reporte['ventas']->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-file-invoice me-1 text-primary"></i>
            Comprobantes del período ({{ $reporte['ventas']->count() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Caja</th>
                            <th>Tipo</th>
                            <th>Serie - Correlativo</th>
                            <th>Cliente</th>
                            <th class="text-end pe-3">Total</th>
                            <th>Fecha / Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte['ventas'] as $v)
                        <tr>
                            <td class="ps-3 small text-muted">{{ $v->caja_numero_nombre }}</td>
                            <td class="small">
                                @php $label = match($v->venta_tipo) { '01'=>'Factura','03'=>'Boleta','20'=>'Nota Venta',default=>$v->venta_tipo }; @endphp
                                {{ $label }}
                            </td>
                            <td class="small fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                            <td class="small">{{ $v->cliente_razonsocial ?: $v->cliente_nombre }}</td>
                            <td class="text-end pe-3 fw-semibold">S/ {{ number_format($v->venta_total, 2) }}</td>
                            <td class="small text-muted">{{ \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @endif {{-- fin @if($reporte) --}}

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
