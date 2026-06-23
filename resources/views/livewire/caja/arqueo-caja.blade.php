<div>

    {{-- ── Alertas ──────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Cabecera ─────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-calculator me-2 text-warning"></i>Arqueo de Caja
            </h5>
            <small class="text-muted">Revisión del turno: ventas, movimientos y diferencia vs físico</small>
        </div>
        @if($arqueo && $arqueo['resumen'] && $idCajaNumeroSeleccionada > 0)
        @can('arqueo_caja.exportar')
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-success btn-sm" wire:click="exportarExcel"
                wire:loading.attr="disabled" wire:target="exportarExcel">
                <span wire:loading.remove wire:target="exportarExcel">
                    <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:16px;height:16px;vertical-align:middle;" class="me-1"> Excel
                </span>
                <span wire:loading wire:target="exportarExcel">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                </span>
            </button>
            <button class="btn btn-outline-danger btn-sm" wire:click="exportarPdf"
                wire:loading.attr="disabled" wire:target="exportarPdf">
                <span wire:loading.remove wire:target="exportarPdf">
                    <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:16px;height:16px;vertical-align:middle;" class="me-1"> PDF
                </span>
                <span wire:loading wire:target="exportarPdf">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                </span>
            </button>
            <button class="btn btn-outline-secondary btn-sm" wire:click="exportarTicket"
                wire:loading.attr="disabled" wire:target="exportarTicket">
                <span wire:loading.remove wire:target="exportarTicket">
                    <i class="fa-solid fa-receipt me-1"></i> Ticket
                </span>
                <span wire:loading wire:target="exportarTicket">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                </span>
            </button>
        </div>
        @endcan
        @endif
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                @if($esSuperAdmin)
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Empresa</label>
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
                    <label class="form-label fw-semibold small">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Todas las sedes</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Caja</label>
                    <select class="form-select form-select-sm" wire:model.live="idCajaNumeroSeleccionada">
                        <option value="0">Todas las cajas</option>
                        @foreach($cajas as $c)
                            <option value="{{ $c->id_caja_numero }}">{{ $c->caja_numero_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Fecha</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFecha">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-warning btn-sm w-100" wire:click="generarArqueo"
                        wire:loading.attr="disabled" wire:target="generarArqueo">
                        <span wire:loading wire:target="generarArqueo">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-magnifying-glass me-1" wire:loading.remove wire:target="generarArqueo"></i>
                        Ver Arqueo
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Sin datos ────────────────────────────────────────────── --}}
    @if($buscado && (!$arqueo || $arqueo['turnos']->isEmpty()))
        <div class="alert alert-info d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-info"></i>
            <span>No se encontraron turnos para la caja <strong>{{ $nombreCaja }}</strong> en la fecha seleccionada.</span>
        </div>
    @endif

    @if($arqueo && !$arqueo['turnos']->isEmpty())
    @php $r = $arqueo['resumen']; @endphp

    {{-- ── Info del turno ──────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning bg-opacity-10 border-0 fw-semibold text-white">
{{--            <i class="fa-solid fa-cash-register me-2 text-warning"></i>--}}
            Turno(s) — Caja: <strong>{{ $nombreCaja }}</strong> —
            {{ \Carbon\Carbon::parse($filtroFecha)->format('d/m/Y') }}
        </div>
        <div class="card-body p-0">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Caja</th>
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
                    @foreach($arqueo['turnos'] as $t)
                    <tr>
                        <td class="ps-3 small fw-semibold">{{ $t->caja_numero_nombre }}</td>
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

    {{-- ── Cuadre detallado ────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-scale-balanced me-1 text-warning"></i> Cuadre de caja — <span class="text-muted fw-normal">¿Dónde está cada sol?</span>
        </div>
        <div class="card-body px-3 pt-2 pb-3">
            <div class="row g-3">

                {{-- Columna: Flujo del dinero --}}
                <div class="col-md-7">
                    <p class="text-muted fw-semibold mb-2" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;">Flujo del dinero</p>
                    <table class="table table-sm mb-0" style="font-size:0.82rem;">
                        <tbody>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td class="text-muted ps-0">Apertura</td>
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
                                <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ingresos (Mov.)
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
                                <i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Gastos del día
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
                        <span class="fw-bold small" style="color:#0b1892;">Total sistema (efectivo)</span>
                        <span class="fw-bold" style="color:#0b1892;font-size:1rem;">S/ {{ number_format($r->total_sistema, 2) }}</span>
                    </div>
                    @if(!$r->caja_abierta && $r->monto_cierre !== null)
                    @php $dif = $r->diferencia; @endphp
                    <div class="rounded p-2 mt-1 d-flex justify-content-between align-items-center" style="background:#f5f5f5;">
                        <span class="text-muted small">Declarado al cierre</span>
                        <span class="fw-bold small">S/ {{ number_format($r->monto_cierre, 2) }}</span>
                    </div>
                    <div class="rounded p-2 mt-1 d-flex justify-content-between align-items-center"
                         style="background:{{ $dif == 0 ? '#d4f4e8' : ($dif > 0 ? '#d4eaf4' : '#fde8e8') }};">
                        <span class="fw-semibold small"
                              style="color:{{ $dif == 0 ? '#1a6b35' : ($dif > 0 ? '#0077b6' : '#b30000') }};">
                            {{ $dif == 0 ? 'Cuadre exacto' : ($dif > 0 ? 'Sobrante' : 'Faltante') }}
                        </span>
                        <span class="fw-bold"
                              style="color:{{ $dif == 0 ? '#1a6b35' : ($dif > 0 ? '#0077b6' : '#b30000') }};font-size:1rem;">
                            {{ $dif >= 0 ? '+' : '' }}S/ {{ number_format($dif, 2) }}
                        </span>
                    </div>
                    @elseif($r->caja_abierta)
                    <div class="rounded p-2 mt-1 text-center" style="background:#fff8e1;">
                        <i class="fa-solid fa-lock-open text-warning me-1"></i>
                        <small class="text-muted">Caja aún abierta — cierre pendiente</small>
                    </div>
                    @endif
                </div>

                {{-- Columna: ¿Dónde está el dinero? --}}
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
                                <span style="color:#b30000;"><i class="fa-solid fa-receipt me-1"></i>Gastos del día</span>
                                <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->gastos, 2) }}</span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Gastos registrados en caja</small>
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
                        @if(!$r->caja_abierta && $r->diferencia !== null && $r->diferencia != 0)
                        @php $dif2 = $r->diferencia; @endphp
                        <div class="rounded p-2" style="background:{{ $dif2 > 0 ? '#d4eaf4' : '#fde8e8' }};">
                            <div class="d-flex justify-content-between">
                                <span style="color:{{ $dif2 > 0 ? '#0077b6' : '#b30000' }};">
                                    <i class="fa-solid fa-{{ $dif2 > 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                    {{ $dif2 > 0 ? 'Sobrante' : 'Faltante' }}
                                </span>
                                <span class="fw-bold" style="color:{{ $dif2 > 0 ? '#0077b6' : '#b30000' }};">
                                    {{ $dif2 >= 0 ? '+' : '' }}S/ {{ number_format($dif2, 2) }}
                                </span>
                            </div>
                            <small class="text-muted" style="font-size:0.68rem;">Diferencia declarado vs sistema</small>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Detalle de cobros ────────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Ventas por medio de pago --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 fw-semibold small">
                    <i class="fa-solid fa-receipt me-1 text-primary"></i> Ventas por medio de pago
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($arqueo['ventasPorMedio'] as $vm)
                            <tr>
                                <td class="ps-3 small">{{ $vm->tipo_pago_nombre }}</td>
                                <td class="text-end pe-3 fw-semibold">S/ {{ number_format($vm->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted small py-3">Sin ventas</td></tr>
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

        {{-- Cobros de cuotas --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 fw-semibold small">
                    <i class="fa-solid fa-coins me-1 text-success"></i> Cobros de cuotas (crédito)
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($arqueo['pagosCuotas'] as $pc)
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

    {{-- ── Movimientos manuales del turno ───────────────────────── --}}
    @if($arqueo['movimientos']->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-list me-1"></i> Movimientos manuales del turno
        </div>
        <div class="card-body p-0">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tipo</th>
                        <th>Concepto</th>
                        <th>Medio de pago</th>
                        <th>N° Operación</th>
                        <th class="text-end pe-3">Monto</th>
                        <th>Registrado por</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($arqueo['movimientos'] as $mov)
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
                        <td class="text-end pe-3 fw-semibold {{ $mov->tipo == 1 ? 'text-success' : 'text-danger' }}">
                            S/ {{ number_format($mov->monto, 2) }}
                        </td>
                        <td class="small">{{ $mov->nombre_users }}</td>
                        <td class="small text-muted">{{ \Carbon\Carbon::parse($mov->created_at)->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── Detalle de ventas ────────────────────────────────────── --}}
    @if($arqueo['ventas']->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 fw-semibold small">
            <i class="fa-solid fa-file-invoice me-1 text-primary"></i>
            Comprobantes del turno ({{ $arqueo['ventas']->count() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tipo</th>
                            <th>Serie - Correlativo</th>
                            <th>Cliente</th>
                            <th class="text-end pe-3">Total</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($arqueo['ventas'] as $v)
                        <tr>
                            <td class="ps-3 small">
                                @php
                                    $label = match($v->venta_tipo) {
                                        '01' => 'Factura', '03' => 'Boleta',
                                        '20' => 'Nota de Venta', default => $v->venta_tipo
                                    };
                                @endphp
                                {{ $label }}
                            </td>
                            <td class="small fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                            <td class="small">{{ $v->cliente_razonsocial ?: $v->cliente_nombre }}</td>
                            <td class="text-end pe-3 fw-semibold">S/ {{ number_format($v->venta_total, 2) }}</td>
                            <td class="small text-muted">{{ \Carbon\Carbon::parse($v->venta_fecha)->format('H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @endif {{-- fin @if($arqueo) --}}

    {{-- ── Scripts ──────────────────────────────────────────────── --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>

</div>
