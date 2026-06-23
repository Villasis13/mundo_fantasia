<div>

    <style>
        .selector-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: .5rem;
        }
    </style>

    {{-- Saludo --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm"
                 style="background: linear-gradient(135deg, #0b1892, #2257f1);">
                <div class="card-body py-2 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold text-white mb-1">
                            {{ $saludo }}, <span style="color:#aadd00;">{{ $nombreUsuario }}</span> 👋
                        </h4>
                        <p class="text-white opacity-75 mb-0" style="font-size: 14px;">
                            Hoy es {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        </p>
                    </div>
                    <i class="fa-solid fa-house fa-3x text-white opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Selector empresa / sede --}}
    @if($esSuperAdmin || $esAdmin || $esAdministrador)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="position:relative;">

                {{-- Overlay de carga --}}
                <div class="selector-overlay" wire:loading wire:target="empresaSeleccionada,sucursalSeleccionada">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-2" style="width:3rem;height:3rem;" role="status"></div>
                        <p class="mb-0 fw-semibold text-primary" style="font-size:14px;">Cargando datos…</p>
                    </div>
                </div>

                <div class="card-body py-3 px-4">
                    <div class="row g-3 align-items-end">

                        {{-- Empresa --}}
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#0b1892;">
                                <i class="fa-solid fa-building me-1"></i> Empresa
                            </label>
                            <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                                <option value="0">— Todas las empresas —</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sede --}}
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#0b1892;">
                                <i class="fa-solid fa-store me-1"></i> Sede / Tienda
                            </label>
                            <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                                <option value="0">— Todas las sedes —</option>
                                @foreach($sucursalesDisponibles as $sede)
                                    <option value="{{ $sede->id_sucursal }}">{{ $sede->sucursal_nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Indicador sede activa --}}
                        @if($sucursalSeleccionada != 0)
                        <div class="col-auto">
                            <span class="badge rounded-pill px-3 py-2" style="background:#eef1ff;color:#0b1892;font-size:12px;">
                                <i class="fa-solid fa-circle-check me-1" style="color:#0b1892;"></i>
                                {{ $sucursalNombre ?? 'Sede seleccionada' }}
                            </span>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal cierre de caja --}}
    <div class="modal fade" id="modalCierreCaja" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-cash-register me-2 text-danger"></i> Cierre de Caja
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if($apertura)
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Cerrando: <strong>{{ $apertura->caja_numero_nombre }}</strong>
                            — Apertura: <strong>S/ {{ number_format($apertura->caja_apertura, 2) }}</strong>
                        </div>
                    @endif
                    <label class="form-label fw-semibold text-muted small text-uppercase">
                        Monto de Cierre <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" wire:model="montoCierre"
                               class="form-control @error('montoCierre') is-invalid @enderror"
                               placeholder="0.00" min="0" step="0.01">
                        @error('montoCierre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold"
                            wire:click="cerrarCaja" wire:loading.attr="disabled" wire:target="cerrarCaja">
                        <span wire:loading.remove wire:target="cerrarCaja">
                            <i class="fa-solid fa-lock me-1"></i> Confirmar Cierre
                        </span>
                        <span wire:loading wire:target="cerrarCaja">
                            <span class="spinner-border spinner-border-sm me-1"></span> Cerrando…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── CAJA (col-5) ─────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">

                    @if(!$apertura)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-cash-register fa-lg" style="color:#0b1892;"></i>
                            <h6 class="fw-bold mb-0" style="color:#0b1892;">Apertura de Caja</h6>
                        </div>

                        @if($mensajeCajaCerradaAyer)
                            <div class="alert alert-warning py-2 small mb-3">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                {{ $mensajeCajaCerradaAyer }}
                            </div>
                        @endif
                        @if(session('errorCaja'))
                            <div class="alert alert-danger py-2 small mb-3">{{ session('errorCaja') }}</div>
                        @endif
                        @if(session('successCaja'))
                            <div class="alert alert-success py-2 small mb-3">{{ session('successCaja') }}</div>
                        @endif

                        @if(!$sucursalSeleccionada)
                            <div class="text-center text-muted py-4 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-store fa-2x mb-2 opacity-25"></i>
                                <p class="small mb-0">Selecciona una sede para ver las cajas disponibles.</p>
                            </div>
                        @elseif(empty($cajas))
                            <div class="text-center text-muted py-4 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-box-open fa-2x mb-2 opacity-25"></i>
                                <p class="small mb-0">No hay cajas activas para la sede seleccionada.</p>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#0b1892;">
                                    <i class="fa-solid fa-cash-register me-1"></i> Caja
                                </label>
                                <select class="form-select form-select-sm" wire:model.live="idCajaSeleccionada">
                                    @foreach($cajas as $caja)
                                        <option value="{{ $caja->id_caja_numero }}"
                                                @if($caja->ya_abierta) disabled @endif>
                                            {{ $caja->caja_numero_nombre }}{{ $caja->ya_abierta ? ' — Ya abierta' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#0b1892;">
                                    <i class="fa-solid fa-coins me-1"></i> Monto de Apertura (S/)
                                </label>
                                <input type="number" min="0" step="0.01"
                                       class="form-control form-control-sm @error('montoApertura') is-invalid @enderror"
                                       wire:model="montoApertura"
                                       placeholder="0.00">
                                @error('montoApertura')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mt-auto">
                                <button class="btn btn-primary w-100" wire:click="aperturarCaja" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="aperturarCaja">
                                        <i class="fa-solid fa-lock-open me-1"></i> Aperturar Caja
                                    </span>
                                    <span wire:loading wire:target="aperturarCaja">
                                        <span class="spinner-border spinner-border-sm me-1"></span> Abriendo…
                                    </span>
                                </button>
                            </div>
                        @endif

                    @else
                        @php $r = (object) $resumenCajaActual; @endphp

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">Abierta</span>
                                    <span class="fw-semibold" style="color:#0b1892;font-size:14px;">
                                        {{ $apertura->caja_numero_nombre }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    Apertura: S/ {{ number_format($apertura->caja_apertura, 2) }}
                                </small>
                            </div>
                            <div class="d-flex gap-3" style="font-size:12px;color:#6c757d;">
                                <span>
                                    <i class="fa-solid fa-user me-1"></i>
                                    {{ $apertura->persona_nombre }}
                                </span>
                                <span>
                                    <i class="fa-solid fa-clock me-1"></i>
                                    {{ \Carbon\Carbon::parse($apertura->caja_fecha_apertura)->format('H:i') }}
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive flex-grow-1">
                            <table class="table table-sm mb-0" style="font-size:13px;">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Apertura</td>
                                        <td class="text-end fw-semibold">S/ {{ number_format($r->apertura, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ventas Efectivo</td>
                                        <td class="text-end fw-semibold">+ S/ {{ number_format($r->efectivo, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ventas Yape</td>
                                        <td class="text-end fw-semibold">+ S/ {{ number_format($r->yape, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ventas Plin</td>
                                        <td class="text-end fw-semibold">+ S/ {{ number_format($r->plin, 2) }}</td>
                                    </tr>
                                    @if($r->cobros > 0)
                                    <tr class="text-muted">
                                        <td>Cobros (cuotas)</td>
                                        <td class="text-end fw-semibold">+ S/ {{ number_format($r->cobros, 2) }}</td>
                                    </tr>
                                    @endif
                                    @if($r->ingresos > 0)
                                    <tr class="text-success">
                                        <td>Ingresos (Mov.)</td>
                                        <td class="text-end fw-semibold">+ S/ {{ number_format($r->ingresos, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr @class(['text-success' => $r->ingresos_gastos > 0, 'text-muted' => $r->ingresos_gastos == 0])>
                                        <td>Ingresos</td>
                                        <td class="text-end fw-semibold">
                                            @if($r->ingresos_gastos > 0)+ @endif
                                            S/ {{ number_format($r->ingresos_gastos, 2) }}
                                        </td>
                                    </tr>
                                    @if($r->egresos > 0)
                                    <tr class="text-danger">
                                        <td>Egresos (Mov.)</td>
                                        <td class="text-end fw-semibold">- S/ {{ number_format($r->egresos, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr class="text-danger">
                                        <td>Notas de Crédito</td>
                                        <td class="text-end fw-semibold">- S/ {{ number_format($r->nc, 2) }}</td>
                                    </tr>
                                    <tr class="text-danger">
                                        <td>Gastos</td>
                                        <td class="text-end fw-semibold">- S/ {{ number_format($r->gastos, 2) }}</td>
                                    </tr>
                                    <tr style="border-top:2px solid #0b1892;">
                                        <td class="fw-bold" style="color:#0b1892;">Saldo en Caja</td>
                                        <td class="text-end fw-bold" style="color:#0b1892;">
                                            S/ {{ number_format($r->total_sistema, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-danger w-100" wire:click="abrirModalCierre">
                                <i class="fa-solid fa-lock me-1"></i> Cerrar Caja
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ── VENTAS POR MES (tabla + gráfico) ────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-chart-bar me-2"></i>
                            Ventas por Mes — {{ $ventasPorMes['anio'] }}
                        </h6>
                    </div>

                    <div class="row g-4 align-items-start">

                        {{-- Tabla --}}
                        <div class="col-lg-6">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0" style="font-size:13px;">
                                    <thead>
                                        <tr class="encabezado_tabla_color">
                                            <th>Mes</th>
                                            <th class="text-end">Efectivo</th>
                                            <th class="text-end">Yape</th>
                                            <th class="text-end">Plin</th>
                                            <th class="text-end">Notas Créd.</th>
                                            <th class="text-end">Gastos</th>
                                            <th class="text-end">Ingresos</th>
                                            <th class="text-end fw-bold">Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalEf = $totalYape = $totalPlin = $totalNc = $totalGs = $totalIng = $totalNeto = 0; @endphp
                                        @foreach($ventasPorMes['meses'] as $m)
                                        @php
                                            $totalEf   += $m['efectivo'];
                                            $totalYape += $m['yape'];
                                            $totalPlin += $m['plin'];
                                            $totalNc   += $m['nc'];
                                            $totalGs   += $m['gastos'];
                                            $totalIng  += $m['ingresos'];
                                            $totalNeto += $m['neto'];
                                        @endphp
                                        <tr @class([
                                            'table-primary fw-semibold' => $m['actual'],
                                            'opacity-50'                => $m['futuro'],
                                        ])>
                                            <td>
                                                {{ $m['nombre'] }}
                                                @if($m['actual'])
                                                    <span class="badge ms-1" style="background:#0b1892;font-size:9px;">Actual</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $m['efectivo'] > 0 ? 'S/ '.number_format($m['efectivo'],2) : '—' }}</td>
                                            <td class="text-end">{{ $m['yape']     > 0 ? 'S/ '.number_format($m['yape'],2)     : '—' }}</td>
                                            <td class="text-end">{{ $m['plin']     > 0 ? 'S/ '.number_format($m['plin'],2)     : '—' }}</td>
                                            <td class="text-end text-danger">{{ $m['nc']       > 0 ? 'S/ '.number_format($m['nc'],2)       : '—' }}</td>
                                            <td class="text-end text-danger">{{ $m['gastos']   > 0 ? 'S/ '.number_format($m['gastos'],2)   : '—' }}</td>
                                            <td class="text-end text-success">{{ $m['ingresos'] > 0 ? 'S/ '.number_format($m['ingresos'],2) : '—' }}</td>
                                            <td class="text-end fw-semibold @if($m['neto'] < 0) text-danger @endif">
                                                S/ {{ number_format($m['neto'], 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#eef1ff;font-weight:700;">
                                            <td>Total {{ $ventasPorMes['anio'] }}</td>
                                            <td class="text-end">S/ {{ number_format($totalEf,   2) }}</td>
                                            <td class="text-end">S/ {{ number_format($totalYape, 2) }}</td>
                                            <td class="text-end">S/ {{ number_format($totalPlin, 2) }}</td>
                                            <td class="text-end text-danger">S/ {{ number_format($totalNc,  2) }}</td>
                                            <td class="text-end text-danger">S/ {{ number_format($totalGs,  2) }}</td>
                                            <td class="text-end text-success">S/ {{ number_format($totalIng, 2) }}</td>
                                            <td class="text-end @if($totalNeto < 0) text-danger @endif">
                                                S/ {{ number_format($totalNeto, 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Gráfico --}}
                        <div class="col-lg-6">
                            <div id="grafico-ventas-mes" style="min-height:340px;"></div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── VENTAS POR VENDEDOR + TIPO DE PAGO ─────────────────────────── --}}
    @if($esSuperAdmin || $esAdmin || $esAdministrador)
    <div class="row mb-4 g-3">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-users me-2"></i>
                            Ventas por Vendedor
                        </h6>
                    </div>
                    <div id="grafico-ventas-vendedor" style="min-height:260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-money-bill-wave me-2"></i>
                            Ventas por Tipo de Pago
                        </h6>
                    </div>
                    <div id="grafico-tipo-pago" style="min-height:260px;"></div>
                </div>
            </div>
        </div>

    </div>
    @endif

    {{-- ── ROTACIÓN DE PRODUCTOS ───────────────────────────────────────── --}}
    @if($esSuperAdmin || $esAdmin || $esAdministrador)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="d-flex align-items-center mb-4">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-rotate me-2"></i>
                            Rotación de Productos
                        </h6>
                        <span class="badge ms-3 rounded-pill" style="background:#eef1ff;color:#0b1892;font-size:11px;">
                            Período seleccionado
                        </span>
                    </div>

                    {{-- Resumen KPI --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#fff5f5;border:1px solid #fcc;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-danger rounded-pill px-2" style="font-size:11px;">
                                        {{ $totalSinMovimiento }}
                                    </span>
                                    <span class="fw-semibold" style="color:#dc3545;font-size:13px;">Sin Rotación</span>
                                </div>
                                <p class="text-muted mb-0" style="font-size:11px;">
                                    Tienen stock pero no registraron ventas en el período.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#fffbf0;border:1px solid #fde68a;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge rounded-pill px-2" style="background:#f59e0b;font-size:11px;">
                                        {{ $totalRotacionBaja }}
                                    </span>
                                    <span class="fw-semibold" style="color:#b45309;font-size:13px;">Baja Rotación</span>
                                </div>
                                <p class="text-muted mb-0" style="font-size:11px;">
                                    Vendieron pero por debajo del promedio del período.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 p-3 h-100" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-success rounded-pill px-2" style="font-size:11px;">
                                        {{ $totalRotacionAlta }}
                                    </span>
                                    <span class="fw-semibold" style="color:#15803d;font-size:13px;">Alta Rotación</span>
                                </div>
                                <p class="text-muted mb-0" style="font-size:11px;">
                                    Vendieron por encima del promedio del período.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Listas de productos --}}
                    <div class="row g-3">

                        {{-- Sin rotación --}}
                        <div class="col-md-4">
                            <p class="fw-semibold mb-2" style="font-size:12px;color:#dc3545;">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Sin Rotación
                            </p>
                            @forelse($sinRotacion->take(8) as $prod)
                            <div class="d-flex align-items-center justify-content-between py-1"
                                 style="border-bottom:1px solid #f5f5f5;font-size:12px;">
                                <span class="text-truncate me-2" title="{{ $prod->pro_nombre }}" style="max-width:160px;">
                                    {{ $prod->pro_nombre }}
                                </span>
                                <span class="text-muted flex-shrink-0">Stock: {{ $prod->ps_stock }}</span>
                            </div>
                            @empty
                            <p class="text-muted" style="font-size:12px;">Sin productos en esta categoría.</p>
                            @endforelse
                        </div>

                        {{-- Baja rotación --}}
                        <div class="col-md-4">
                            <p class="fw-semibold mb-2" style="font-size:12px;color:#b45309;">
                                <i class="fa-solid fa-circle-minus me-1"></i> Baja Rotación
                            </p>
                            @forelse($rotacionBaja as $prod)
                            <div class="d-flex align-items-center justify-content-between py-1"
                                 style="border-bottom:1px solid #f5f5f5;font-size:12px;">
                                <span class="text-truncate me-2" title="{{ $prod->pro_nombre }}" style="max-width:160px;">
                                    {{ $prod->pro_nombre }}
                                </span>
                                <span class="badge rounded-pill" style="background:#fde68a;color:#92400e;font-size:10px;">
                                    {{ (int)$prod->total_qty }} uds
                                </span>
                            </div>
                            @empty
                            <p class="text-muted" style="font-size:12px;">Sin productos en esta categoría.</p>
                            @endforelse
                        </div>

                        {{-- Alta rotación --}}
                        <div class="col-md-4">
                            <p class="fw-semibold mb-2" style="font-size:12px;color:#15803d;">
                                <i class="fa-solid fa-circle-check me-1"></i> Alta Rotación
                            </p>
                            @forelse($rotacionAlta as $prod)
                            <div class="d-flex align-items-center justify-content-between py-1"
                                 style="border-bottom:1px solid #f5f5f5;font-size:12px;">
                                <span class="text-truncate me-2" title="{{ $prod->pro_nombre }}" style="max-width:160px;">
                                    {{ $prod->pro_nombre }}
                                </span>
                                <span class="badge bg-success rounded-pill" style="font-size:10px;">
                                    {{ (int)$prod->total_qty }} uds
                                </span>
                            </div>
                            @empty
                            <p class="text-muted" style="font-size:12px;">Sin productos en esta categoría.</p>
                            @endforelse
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@script
<script>
    const ASSU = {
        azul:    '#0b1892',
        lima:    '#aadd00',
        magenta: '#e600cc',
        naranja: '#fd7e14',
        gris:    '#6c6c9a',
        cyan:    '#00b4d8',
    };

    let chartVentasMes      = null;
    let chartVentasVendedor = null;
    let chartTipoPago       = null;

    function renderVentasMes(data) {
        const el = document.querySelector('#grafico-ventas-mes');
        if (!el || !data || !data.labels) return;

        const opciones = {
            series: [
                { name: 'Efectivo',      data: data.efectivo },
                { name: 'Yape',          data: data.yape },
                { name: 'Plin',          data: data.plin },
                { name: 'Notas Créd.',   data: data.nc },
                { name: 'Gastos',        data: data.gastos },
                { name: 'Ingresos',      data: data.ingresos },
                { name: 'Neto',          data: data.neto },
            ],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            colors: [ASSU.azul, ASSU.lima, ASSU.cyan, ASSU.magenta, ASSU.naranja, ASSU.gris, '#8b5cf6'],
            plotOptions: {
                bar: { borderRadius: 3, columnWidth: '75%', grouped: true },
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: data.labels,
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: {
                labels: {
                    formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
            annotations: typeof data.mesActual === 'number' ? {
                xaxis: [{
                    x: data.labels[data.mesActual],
                    borderColor: ASSU.azul,
                    borderWidth: 2,
                    label: {
                        text: 'Mes actual',
                        style: { color: '#fff', background: ASSU.azul, fontSize: '10px' },
                    },
                }],
            } : {},
        };

        if (!chartVentasMes) {
            chartVentasMes = new ApexCharts(el, opciones);
            chartVentasMes.render();
        } else {
            chartVentasMes.updateOptions({ xaxis: { categories: data.labels } });
            chartVentasMes.updateSeries(opciones.series);
        }
    }

    function renderVentasVendedor(data) {
        const el = document.querySelector('#grafico-ventas-vendedor');
        if (!el || !data || !data.labels || !data.labels.length) {
            if (el) el.innerHTML = '<div class="text-center text-muted py-5"><i class="fa-solid fa-users fa-2x mb-2 opacity-25 d-block"></i><p class="small mb-0">Sin datos de ventas para el período seleccionado.</p></div>';
            return;
        }

        const altura = Math.max(220, data.labels.length * 48);
        const opciones = {
            series: [{ name: 'Ventas', data: data.totales }],
            chart: {
                type: 'bar',
                height: altura,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            plotOptions: {
                bar: { horizontal: true, borderRadius: 4, barHeight: '55%' },
            },
            colors: [ASSU.azul],
            dataLabels: {
                enabled: true,
                formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }),
                style: { fontSize: '11px', colors: ['#fff'] },
            },
            xaxis: {
                categories: data.labels,
                labels: {
                    formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            yaxis: {
                labels: { style: { fontSize: '12px' } },
            },
            tooltip: {
                y: { formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { show: false },
        };

        if (!chartVentasVendedor) {
            chartVentasVendedor = new ApexCharts(el, opciones);
            chartVentasVendedor.render();
        } else {
            chartVentasVendedor.updateOptions({
                chart: { height: altura },
                xaxis: { categories: data.labels },
            });
            chartVentasVendedor.updateSeries(opciones.series);
        }
    }

    function renderTipoPago(data) {
        const el = document.querySelector('#grafico-tipo-pago');
        if (!el) return;

        if (!data || !data.labels || !data.labels.length) {
            el.innerHTML = '<div class="text-center text-muted py-5"><i class="fa-solid fa-credit-card fa-2x mb-2 opacity-25 d-block"></i><p class="small mb-0">Sin datos para el período seleccionado.</p></div>';
            if (chartTipoPago) { chartTipoPago.destroy(); chartTipoPago = null; }
            return;
        }

        const colores = data.labels.map(l =>
            l.toLowerCase().includes('efectivo') ? ASSU.azul : ASSU.lima
        );

        const total = data.totales.reduce((a, b) => a + b, 0);

        const opciones = {
            series: data.totales,
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            labels: data.labels,
            colors: colores,
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    const s = opts.w.globals.series[opts.seriesIndex];
                    return 'S/ ' + parseFloat(s).toLocaleString('es-PE', { minimumFractionDigits: 2 });
                },
                style: { fontSize: '12px' },
                dropShadow: { enabled: false },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '13px',
                                fontWeight: 600,
                                color: '#0b1892',
                                formatter: () => 'S/ ' + parseFloat(total).toLocaleString('es-PE', { minimumFractionDigits: 2 }),
                            },
                        },
                    },
                },
            },
            legend: { position: 'bottom', fontSize: '13px' },
            tooltip: {
                y: { formatter: v => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
        };

        if (!chartTipoPago) {
            chartTipoPago = new ApexCharts(el, opciones);
            chartTipoPago.render();
        } else {
            chartTipoPago.updateOptions({ labels: data.labels, colors: colores });
            chartTipoPago.updateSeries(data.totales);
        }
    }

    $wire.on('actualizarGraficos', (event) => {
        setTimeout(() => {
            const data = Array.isArray(event) ? event[0] : event;
            if (data.ventasMes) renderVentasMes(data.ventasMes);
            if (data.vendedor)  renderVentasVendedor(data.vendedor);
            if (data.tipoPago)  renderTipoPago(data.tipoPago);
        }, 150);
    });

    $wire.on('abrirModalCierre', () => {
        new bootstrap.Modal(document.getElementById('modalCierreCaja')).show();
    });

    $wire.on('cerrarModalCierre', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCierreCaja'));
        if (modal) modal.hide();
    });
</script>
@endscript
