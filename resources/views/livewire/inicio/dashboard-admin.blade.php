<div>
    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  PALETA ASSU: #0b1892 (azul) #2257f1 (azul elec)         --}}
    {{--               #aadd00 (verde lima) #e600cc (magenta)      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}

    <style>
        .assu-card-azul     { background: linear-gradient(135deg, #0b1892, #2257f1); }
        .assu-card-verde    { background: linear-gradient(135deg, #5a9900, #aadd00); }
        .assu-card-magenta  { background: linear-gradient(135deg, #b3009e, #e600cc); }
        .assu-card-gris     { background: linear-gradient(135deg, #3a3a5c, #6c6c9a); }
        .assu-card-cyan     { background: linear-gradient(135deg, #0077b6, #00b4d8); }
        .assu-card-naranja  { background: linear-gradient(135deg, #c05200, #fd7e14); }
        .periodo-btn-active { background: #0b1892 !important; color: #fff !important; border-color: #0b1892 !important; }
        .stock-critico      { background: #fde8e8; }
        .stock-bajo         { background: #fff8e1; }
    </style>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Cierre de Caja                                   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
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
                        <span class="spinner-border spinner-border-sm me-1"></span> Cerrando...
                    </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  ENCABEZADO                                               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                {{ $saludo }}, <span style="color:#0b1892;">{{ $nombreUsuario }}</span> 👋
            </h4>
            <small class="text-muted">Panel de administración — Resumen del negocio</small>
        </div>
        <div class="btn-group shadow-sm" role="group">
            <button type="button" wire:click="cambiarPeriodo('hoy')"
                    class="btn btn-sm {{ $periodo==='hoy' ? 'periodo-btn-active' : 'btn-outline-secondary' }}">
                <i class="fa-solid fa-sun me-1"></i> Hoy
            </button>
            <button type="button" wire:click="cambiarPeriodo('semana')"
                    class="btn btn-sm {{ $periodo==='semana' ? 'periodo-btn-active' : 'btn-outline-secondary' }}">
                <i class="fa-solid fa-calendar-week me-1"></i> Semana
            </button>
            <button type="button" wire:click="cambiarPeriodo('mes')"
                    class="btn btn-sm {{ $periodo==='mes' ? 'periodo-btn-active' : 'btn-outline-secondary' }}">
                <i class="fa-solid fa-calendar me-1"></i> Mes
            </button>
        </div>
    </div>

    {{-- Alertas caja --}}
    @if(session('successCaja'))
        <div class="alert alert-success alert-dismissible mb-3">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('successCaja') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('errorCaja'))
        <div class="alert alert-danger alert-dismissible mb-3">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('errorCaja') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($mensajeCajaCerradaAyer)
        <div class="alert alert-warning alert-dismissible mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation fa-lg flex-shrink-0"></i>
                <div>
                    <strong>Caja del día anterior detectada</strong><br>
                    <small>{{ $mensajeCajaCerradaAyer }}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('aviso_plan_vence'))
        @php
            $aviso = session('aviso_plan_vence');
        @endphp
        <div class="alert alert-plan-vence alert-dismissible fade show mt-3 d-flex align-items-start gap-3"
             role="alert"
             style="background:linear-gradient(135deg,#fff8e1,#fff3cd); border-left:4px solid #f59e0b; border-radius:8px;">
            <div class="flex-shrink-0 mt-1">
                <i class="fa-solid fa-triangle-exclamation fa-lg text-warning"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold" style="color:#92400e;">
                    ⚠️ Tu plan está por vencer
                </div>
                <div class="small mt-1" style="color:#78350f;">
                    El plan <strong>{{ $aviso['plan'] }}</strong> vence el
                    <strong>{{ $aviso['fecha_fin'] }}</strong>.
                    @if($aviso['dias'] == 0)
                        <span class="badge bg-danger ms-1">¡Vence hoy!</span>
                    @elseif($aviso['dias'] == 1)
                        <span class="badge bg-warning text-dark ms-1">Vence mañana</span>
                    @else
                        <span class="badge bg-warning text-dark ms-1">{{ $aviso['dias'] }} días restantes</span>
                    @endif
                    <span class="ms-2 text-muted">— Contacta al administrador para renovarlo.</span>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    {{-- Aviso SuperAdmin sin empresa --}}
    @if($esSuperAdmin && !$idEmpresaActiva)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
            <div>Selecciona una <strong>empresa</strong> en el panel de caja para ver los datos filtrados.</div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 1 — Caja del día                                   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        @if(!$apertura)

        {{-- ── Caja no abierta: formulario de apertura ── --}}
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">

                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa-solid fa-cash-register me-2"></i> Caja del Día
                    </h6>

                    {{-- Paso 1: Empresa (solo superadmin) --}}
                    @if($esSuperAdmin)
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-muted small text-uppercase mb-1">
                                <i class="fa-solid fa-building me-1 opacity-50"></i> Empresa
                            </label>
                            <select wire:model.live="empresaSeleccionada" class="form-select form-select-sm">
                                <option value="0">— Seleccionar empresa —</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Paso 2: Sucursal --}}
                    @if($esAdmin || $esAdministrador || ($esSuperAdmin && $empresaSeleccionada > 0))
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-muted small text-uppercase mb-1">
                                <i class="fa-solid fa-store me-1 opacity-50"></i> Sucursal
                            </label>
                            <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm">
                                <option value="0">— Seleccionar sucursal —</option>
                                @foreach($sucursalesDisponibles as $suc)
                                    <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Paso 3: Caja + apertura --}}
                    @if($sucursalSeleccionada != 0)
                        @if($cajas->isEmpty())
                            <div class="text-center text-muted py-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-cash-register fa-xl opacity-25 mb-2"></i>
                                <small>No hay cajas activas en esta sucursal.</small>
                            </div>
                        @else
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-1">
                                    <i class="fa-solid fa-cash-register me-1 opacity-50"></i> Caja
                                </label>
                                <select wire:model="idCajaSeleccionada"
                                        class="form-select form-select-sm @error('idCajaSeleccionada') is-invalid @enderror">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($cajas as $c)
                                        <option value="{{ $c->id_caja_numero }}">{{ $c->caja_numero_nombre }}</option>
                                    @endforeach
                                </select>
                                @error('idCajaSeleccionada') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-1">Monto Apertura</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" wire:model="montoApertura"
                                           class="form-control @error('montoApertura') is-invalid @enderror"
                                           placeholder="0.00" min="0" step="0.01">
                                    @error('montoApertura') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <button class="btn w-100 fw-semibold text-white mt-auto"
                                    style="background:#0b1892;"
                                    wire:click="aperturarCaja"
                                    wire:loading.attr="disabled" wire:target="aperturarCaja">
                                <span wire:loading.remove wire:target="aperturarCaja">
                                    <i class="fa-solid fa-lock-open me-1"></i> Aperturar
                                </span>
                                <span wire:loading wire:target="aperturarCaja">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Aperturando...
                                </span>
                            </button>
                        @endif
                    @elseif($esSuperAdmin && $empresaSeleccionada === 0)
                        <div class="text-center text-muted py-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="fa-solid fa-building fa-xl opacity-25 mb-2"></i>
                            <small>Selecciona una empresa para continuar.</small>
                        </div>
                    @else
                        <div class="text-center text-muted py-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="fa-solid fa-store fa-xl opacity-25 mb-2"></i>
                            <small>Selecciona una sucursal para ver las cajas.</small>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        @else

        {{-- ── Caja abierta: Cuadre de caja completo ── --}}
        @php $r = (object) $resumenCajaActual; @endphp
        <div class="col-12">

            {{-- Cabecera del turno --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge bg-success">Abierta</span>
                        <span class="fw-bold">{{ $apertura->caja_numero_nombre }}</span>
                        @if($apertura->sucursal_nombre)
                            <span class="badge rounded-pill fw-normal"
                                  style="background:#eef1ff;color:#0b1892;font-size:.72rem;">
                                <i class="fa-solid fa-store me-1"></i>{{ $apertura->sucursal_nombre }}
                            </span>
                        @endif
                        <span class="text-muted small">
                            <i class="fa-solid fa-user me-1 opacity-50"></i>{{ $apertura->persona_nombre }}
                        </span>
                        <span class="text-muted small">
                            <i class="fa-solid fa-clock me-1 opacity-50"></i>
                            Apertura: <strong>S/ {{ number_format($apertura->caja_apertura, 2) }}</strong>
                            a las {{ \Carbon\Carbon::parse($apertura->caja_fecha_apertura)->format('H:i') }}
                        </span>
                    </div>
                    <button class="btn btn-danger btn-sm fw-semibold" wire:click="abrirModalCierre">
                        <i class="fa-solid fa-lock me-1"></i> Cerrar Caja
                    </button>
                </div>
            </div>

            {{-- Cuadre detallado --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 fw-semibold small">
                    <i class="fa-solid fa-scale-balanced me-1 text-warning"></i> Cuadre de caja — <span class="text-muted fw-normal">¿Dónde está cada sol?</span>
                </div>
                <div class="card-body px-3 pt-2 pb-3">
                    <div class="row g-3">

                        {{-- Flujo del dinero --}}
                        <div class="col-md-7">
                            <p class="text-muted fw-semibold mb-2" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;">Flujo del dinero</p>
                            <table class="table table-sm mb-0" style="font-size:0.82rem;">
                                <tbody>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="text-muted ps-0">Apertura</td>
                                    <td class="text-end fw-semibold pe-0">S/ {{ number_format($r->apertura, 2) }}</td>
                                </tr>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0" style="color:#1a6b35;"><i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ventas efectivo</td>
                                    <td class="text-end fw-semibold pe-0" style="color:#1a6b35;">S/ {{ number_format($r->efectivo, 2) }}</td>
                                </tr>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0" style="color:#0077b6;">
                                        <i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ventas QR / digital
                                        <span class="badge ms-1" style="background:#e6f4fb;color:#0077b6;font-size:0.62rem;">banco</span>
                                    </td>
                                    <td class="text-end fw-semibold pe-0" style="color:#0077b6;">S/ {{ number_format($r->qr, 2) }}</td>
                                </tr>
                                @if($r->cobros > 0)
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0" style="color:#5a9900;"><i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Cobros de cuotas</td>
                                    <td class="text-end fw-semibold pe-0" style="color:#5a9900;">S/ {{ number_format($r->cobros, 2) }}</td>
                                </tr>
                                @endif
                                @if($r->ingresos > 0)
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0 text-muted"><i class="fa-solid fa-plus fa-xs me-1 opacity-50"></i>Ingresos manuales</td>
                                    <td class="text-end fw-semibold pe-0 text-muted">S/ {{ number_format($r->ingresos, 2) }}</td>
                                </tr>
                                @endif
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0 text-danger"><i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Notas de crédito</td>
                                    <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->nc, 2) }}</td>
                                </tr>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0 text-danger"><i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Gastos del día</td>
                                    <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->gastos, 2) }}</td>
                                </tr>
                                @if($r->egresos > 0)
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="ps-0 text-danger"><i class="fa-solid fa-minus fa-xs me-1 opacity-50"></i>Egresos manuales</td>
                                    <td class="text-end fw-semibold pe-0 text-danger">− S/ {{ number_format($r->egresos, 2) }}</td>
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

                        {{-- ¿Dónde está el dinero? --}}
                        <div class="col-md-5">
                            <p class="text-muted fw-semibold mb-2" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;">¿Dónde está el dinero?</p>
                            <div class="d-flex flex-column gap-2" style="font-size:0.82rem;">
                                <div class="rounded p-2" style="background:#eaf3de;">
                                    <div class="d-flex justify-content-between">
                                        <span style="color:#1a6b35;"><i class="fa-solid fa-money-bill-wave me-1"></i>En caja (efectivo)</span>
                                        <span class="fw-bold" style="color:#1a6b35;">S/ {{ number_format($r->total_sistema, 2) }}</span>
                                    </div>
                                    <small class="text-muted" style="font-size:0.68rem;">Apertura + ventas ef. + cobros − NC − gastos</small>
                                </div>
                                @if($r->qr > 0)
                                <div class="rounded p-2" style="background:#e6f4fb;">
                                    <div class="d-flex justify-content-between">
                                        <span style="color:#0077b6;"><i class="fa-solid fa-qrcode me-1"></i>En banco / QR</span>
                                        <span class="fw-bold" style="color:#0077b6;">S/ {{ number_format($r->qr, 2) }}</span>
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
                                        <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->nc, 2) }}</span>
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
                                @if($r->egresos > 0)
                                <div class="rounded p-2" style="background:#fde8e8;">
                                    <div class="d-flex justify-content-between">
                                        <span style="color:#b30000;"><i class="fa-solid fa-arrow-down me-1"></i>Egresos manuales</span>
                                        <span class="fw-bold" style="color:#b30000;">S/ {{ number_format($r->egresos, 2) }}</span>
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

            {{-- Ventas por medio + Cobros de cuotas --}}
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light border-0 fw-semibold small">
                            <i class="fa-solid fa-receipt me-1 text-primary"></i> Ventas por medio de pago
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                @forelse($r->ventasPorMedio as $vm)
                                    <tr>
                                        <td class="ps-3 small">{{ $vm->tipo_pago_nombre }}</td>
                                        <td class="text-end pe-3 fw-semibold">S/ {{ number_format($vm->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted small py-3">Sin ventas en este turno</td></tr>
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
                                @forelse($r->pagosCuotasDetalle as $pc)
                                    <tr>
                                        <td class="ps-3 small">{{ $pc->tipo_pago_nombre }}</td>
                                        <td class="text-end pe-3 fw-semibold">S/ {{ number_format($pc->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted small py-3">Sin cobros en este turno</td></tr>
                                @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td class="ps-3 fw-semibold">Total cobros</td>
                                        <td class="text-end pe-3 fw-bold text-success">S/ {{ number_format($r->cobros, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        @endif

    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 2 — Gráfico de ingresos (línea/barras) + Donut     --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Gráfico línea — Ingreso por día/hora --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-1" style="color:#0b1892;">
                        <i class="fa-solid fa-chart-line me-2"></i>
                        {{ $periodo === 'hoy' ? 'Ingresos por hora — Hoy' : 'Ingresos por día' }}
                    </h6>
                    <small class="text-muted d-block mb-3">Incluye ventas brutas, notas, cuotas y débitos</small>
                    <div id="grafico-linea"></div>
                </div>
            </div>
        </div>

        {{-- Gráfico donut — Distribución por tipo --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-1" style="color:#0b1892;">
                        <i class="fa-solid fa-chart-pie me-2"></i> Distribución de Ventas
                    </h6>
                    <small class="text-muted d-block mb-3">Por tipo de comprobante</small>
                    <div id="grafico-donut"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 3 — Gráfico vendedor + Gráfico caja               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-1" style="color:#0b1892;">
                        <i class="fa-solid fa-user-tie me-2"></i> Ventas por Vendedor
                    </h6>
                    <small class="text-muted d-block mb-3">Monto total facturado por vendedor</small>
                    <div id="grafico-vendedor"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-1" style="color:#0b1892;">
                        <i class="fa-solid fa-cash-register me-2"></i> Ventas por Caja
                    </h6>
                    <small class="text-muted d-block mb-3">Monto total por punto de venta</small>
                    <div id="grafico-caja"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 3b — Ventas por Mes (año en curso)                --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-1" style="color:#0b1892;">
                        <i class="fa-solid fa-chart-bar me-2"></i> Ventas por Mes — {{ $ventasPorMes['anio'] }}
                    </h6>
                    <small class="text-muted d-block mb-3">Efectivo, QR y neto mensual (gastos y NC descontados)</small>
                    <div id="grafico-ventas-mes"></div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm align-middle mb-0" style="font-size:0.8rem;">
                            <thead>
                                <tr class="encabezado_tabla_color">
                                    <th>Mes</th>
                                    <th class="text-end">Efectivo</th>
                                    <th class="text-end">QR</th>
                                    <th class="text-end">NC</th>
                                    <th class="text-end">Gastos</th>
                                    <th class="text-end fw-bold">Neto</th>
                                    <th class="text-center">Excel</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($ventasPorMes['meses'] as $mes)
                                <tr class="{{ $mes['futuro'] ? '' : ($mes['actual'] ? 'table-primary' : '') }}"
                                    style="{{ $mes['futuro'] ? 'opacity:.4;' : '' }}">
                                    <td class="fw-semibold">
                                        {{ $mes['nombre'] }}
                                        @if($mes['actual'])
                                            <span class="badge bg-primary ms-1" style="font-size:9px;">Actual</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $mes['futuro'] ? '—' : 'S/ '.number_format($mes['efectivo'], 2) }}</td>
                                    <td class="text-end">{{ $mes['futuro'] ? '—' : 'S/ '.number_format($mes['qr'], 2) }}</td>
                                    <td class="text-end text-danger">{{ $mes['futuro'] ? '—' : 'S/ '.number_format($mes['nc'], 2) }}</td>
                                    <td class="text-end text-danger">{{ $mes['futuro'] ? '—' : 'S/ '.number_format($mes['gastos'], 2) }}</td>
                                    <td class="text-end fw-bold {{ $mes['futuro'] ? 'text-muted' : ($mes['neto'] >= 0 ? 'text-success' : 'text-danger') }}">
                                        {{ $mes['futuro'] ? '—' : 'S/ '.number_format($mes['neto'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if(!$mes['futuro'])
                                        @can('reporte_ventas.exportar')
                                        <button class="btn btn-outline-success btn-sm py-0 px-2"
                                                wire:click="exportarVentasMes({{ $mes['mes'] }})"
                                                title="Exportar Excel {{ $mes['nombre'] }}">
                                            <i class="fa-solid fa-file-excel" style="font-size:11px;"></i>
                                        </button>
                                        @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 4 — Notas crédito/débito + Alertas SUNAT          --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa-solid fa-file-circle-exclamation me-2 text-warning"></i> Notas Emitidas
                    </h6>
                    <div class="rounded p-3 d-flex justify-content-between align-items-center mb-2" style="background:#fde8e8;">
                        <div>
                            <small class="text-muted d-block">Notas de Crédito</small>
                            <span class="fw-bold text-danger fs-5">- S/ {{ number_format($notasCredito, 2) }}</span>
                        </div>
                        <i class="fa-solid fa-arrow-down text-danger fa-lg"></i>
                    </div>
                    <div class="rounded p-3 d-flex justify-content-between align-items-center" style="background:#d4f4e8;">
                        <div>
                            <small class="text-muted d-block">Notas de Débito</small>
                            <span class="fw-bold text-success fs-5">+ S/ {{ number_format($notasDebito, 2) }}</span>
                        </div>
                        <i class="fa-solid fa-arrow-up text-success fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Alertas SUNAT
                        </h6>
                        @if($pendientesSunat > 0)
                            <span class="badge bg-danger">{{ $pendientesSunat }} pendientes</span>
                        @else
                            <span class="badge bg-success">Al día</span>
                        @endif
                    </div>
                    @if($pendientesSunat > 0)
                        <div class="alert alert-warning py-2 mb-3 small">
                            <i class="fa-solid fa-clock me-2"></i>
                            Tienes <strong>{{ $pendientesSunat }}</strong> comprobante(s) pendientes de envío.
                        </div>
                    @endif
                    @if($comprobantesConAlerta->count() > 0)
                        <p class="text-muted small mb-2 fw-semibold">⚠️ Respuestas fuera del patrón habitual:</p>
                        <div class="table-responsive" style="max-height:160px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr class="encabezado_tabla_color"><th>Comprobante</th><th>Fecha</th><th>Respuesta</th></tr></thead>
                                <tbody>
                                @foreach($comprobantesConAlerta as $comp)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ match($comp->venta_tipo) { '01'=>'FAC','03'=>'BOL','07'=>'NC','08'=>'ND',default=>$comp->venta_tipo } }}</span>
                                            {{ $comp->venta_serie }}-{{ $comp->venta_correlativo }}
                                        </td>
                                        <td class="text-muted small">{{ \Carbon\Carbon::parse($comp->venta_fecha)->format('d/m/Y') }}</td>
                                        <td class="text-danger small">{{ Str::limit($comp->venta_respuesta_sunat, 55) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                            Todos los comprobantes tienen respuesta correcta.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 5 — Stock bajo + Cuotas                            --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Stock bajo con paginación --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Stock Bajo
                        </h6>
                        @if($totalStockBajo > 0)
                            <span class="badge" style="background:#aadd00;color:#000;">{{ $totalStockBajo }} productos</span>
                        @endif
                    </div>

                    @if($productosStockBajo->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead>
                                <tr class="encabezado_tabla_color">
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    @isset($productosStockBajo[0]->sucursal_nombre)
                                        <th>Sucursal</th>
                                    @endisset
                                    <th class="text-center">Stock</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($productosStockBajo as $p)
                                    <tr class="{{ $p->stock_actual <= 0 ? 'stock-critico' : ($p->stock_actual <= 5 ? 'stock-bajo' : '') }}">
                                        <td>
                                            <span class="fw-semibold small">{{ $p->pro_nombre }}</span>
                                            <small class="text-muted d-block">{{ $p->pro_codigo }}</small>
                                        </td>
                                        <td class="small text-muted">{{ $p->ca_nombre }}</td>
                                        @if(isset($p->sucursal_nombre))
                                            <td class="small text-muted">{{ $p->sucursal_nombre }}</td>
                                        @endif
                                        <td class="text-center">
                                            <span class="badge {{ $p->stock_actual <= 0 ? 'bg-danger' : ($p->stock_actual <= 5 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                {{ $p->stock_actual }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación stock --}}
                        @if($totalPaginasStock > 1)
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <small class="text-muted">
                                    Pág. {{ $stockPaginaActual }} de {{ $totalPaginasStock }}
                                    ({{ $totalStockBajo }} total)
                                </small>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary"
                                            wire:click="stockAnterior"
                                        {{ $stockPaginaActual <= 1 ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary"
                                            wire:click="stockSiguiente({{ $totalPaginasStock }})"
                                        {{ $stockPaginaActual >= $totalPaginasStock ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                            Todos los productos tienen stock suficiente.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cuotas --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa-solid fa-calendar-xmark text-danger me-2"></i> Control de Cuotas
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="rounded p-3 text-center" style="background:#fde8e8;">
                                <small class="text-danger fw-semibold d-block text-uppercase" style="font-size:10px;">CxC Vencidas</small>
                                <h4 class="fw-bold text-danger mb-0">{{ $cuotasVencidas->count() }}</h4>
                                <small class="text-muted">S/ {{ number_format($totalCuotasVencidas, 2) }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded p-3 text-center" style="background:#fff3cd;">
                                <small class="text-warning fw-semibold d-block text-uppercase" style="font-size:10px;">CxC Próximas</small>
                                <h4 class="fw-bold text-warning mb-0">{{ $cuotasPorVencer->count() }}</h4>
                                <small class="text-muted">S/ {{ number_format($totalCuotasPorVencer, 2) }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <a href="{{ url('cxc/cuentas_pagar') }}" class="text-decoration-none">
                                <div class="rounded p-3 text-center" style="background:#fce8ff;">
                                    <small class="fw-semibold d-block text-uppercase" style="font-size:10px;color:#b300a0;">CxP Vencida</small>
                                    <h4 class="fw-bold mb-0" style="color:#b300a0;">{{ $cantidadCxpVencida }}</h4>
                                    <small class="text-muted">S/ {{ number_format($totalCxpVencida, 2) }}</small>
                                </div>
                            </a>
                        </div>
                    </div>
                    @if($cuotasVencidas->count())
                        <p class="small fw-semibold text-danger mb-2">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Cuotas vencidas:
                        </p>
                        <div class="table-responsive" style="max-height:180px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr class="encabezado_tabla_color"><th>Cliente</th><th>Comprobante</th><th>Cuota</th><th class="text-end">Importe</th><th>Venció</th></tr></thead>
                                <tbody>
                                @foreach($cuotasVencidas as $vc)
                                    @php
                                        $nomCli = $vc->id_tipo_documento == 4 ? $vc->cliente_razonsocial : $vc->cliente_nombre;
                                        $dias   = \Carbon\Carbon::parse($vc->venta_cuota_fecha)->diffInDays(\Carbon\Carbon::today());
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold small">{{ Str::limit($nomCli, 18) }}</td>
                                        <td class="small">{{ $vc->venta_serie }}-{{ $vc->venta_correlativo }}</td>
                                        <td class="text-center"><span class="badge bg-secondary">{{ str_pad($vc->venta_cuota_numero, 3, '0', STR_PAD_LEFT) }}</span></td>
                                        <td class="text-end fw-bold text-danger small">S/ {{ number_format($vc->venta_cuota_importe, 2) }}</td>
                                        <td><span class="badge bg-danger" style="font-size:10px;">hace {{ $dias }}d</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($cuotasPorVencer->count() === 0)
                        <div class="text-center text-muted py-3">
                            <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                            Sin cuotas vencidas ni por vencer.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 6 — Top 5 Clientes + Ingresos por medio de pago   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Top 5 clientes --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa fa-trophy text-warning me-2"></i> Top 5 Clientes del Período
                    </h6>
                    @if($top5Clientes->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Compras</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($top5Clientes as $i => $cli)
                                    @php $nombre = $cli->id_tipo_documento == 4 ? $cli->cliente_razonsocial : $cli->cliente_nombre; @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $i === 0 ? 'bg-warning text-dark' : ($i === 1 ? 'bg-secondary' : 'bg-light text-muted border') }}">
                                                {{ $i + 1 }}
                                            </span>
                                        </td>
                                        <td class="fw-semibold small">{{ Str::limit($nombre, 22) }}</td>
                                        <td class="text-end small text-muted">{{ $cli->cantidad }}</td>
                                        <td class="text-end fw-bold small" style="color:#0b1892;">S/ {{ number_format($cli->total, 2) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-users fa-2x mb-2 d-block opacity-50"></i>
                            Sin ventas en el período seleccionado.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ingresos por medio de pago --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa fa-credit-card text-primary me-2"></i> Ingresos por Medio de Pago
                    </h6>
                    @if($ingresosPorMedio->count())
                        @php $totalMedio = $ingresosPorMedio->sum('total'); @endphp
                        <div class="d-flex flex-column gap-2">
                        @foreach($ingresosPorMedio as $medio)
                            @php
                                $pct = $totalMedio > 0 ? round(($medio->total / $totalMedio) * 100, 1) : 0;
                                $idx = $loop->index % 4;
                                $color = ['#0b1892','#aadd00','#e600cc','#00b4d8'][$idx];
                                $textColor = $idx === 1 ? '#000' : '#fff';
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">{{ $medio->tipo_pago_nombre }}</span>
                                    <span class="text-muted">S/ {{ number_format($medio->total, 2) }} <span class="text-muted">({{ $pct }}%)</span></span>
                                </div>
                                <div class="progress" style="height:10px;border-radius:6px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width:{{ $pct }}%;background:{{ $color }};"
                                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                        <div class="mt-3 pt-2 border-top d-flex justify-content-between small">
                            <span class="text-muted fw-semibold">Total recaudado</span>
                            <span class="fw-bold" style="color:#0b1892;">S/ {{ number_format($totalMedio, 2) }}</span>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-credit-card fa-2x mb-2 d-block opacity-50"></i>
                            Sin pagos registrados en el período.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top productos / Sin rotación / Sin movimiento ─────────────── --}}
    <div class="row g-3 mt-1 mb-4">

        {{-- Top 15 productos más vendidos --}}
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0 pt-3 px-3" style="background:transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#e8f5e9;">
                            <i class="fa-solid fa-trophy" style="color:#1a6b35;font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size:0.88rem;">Top 15 Productos Más Vendidos</h6>
                            <small class="text-muted" style="font-size:0.72rem;">Por cantidad vendida en el período</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-3 pt-2 pb-2">
                    @if($topProductos->count())
                        @php $maxCant = $topProductos->first()->total_cantidad; @endphp
                        <div style="max-height:360px;overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:0.76rem;">
                                <thead style="position:sticky;top:0;background:#fff;z-index:1;">
                                    <tr style="border-bottom:2px solid #e0e0e0;">
                                        <th class="fw-semibold text-muted ps-0" style="width:24px;">#</th>
                                        <th class="fw-semibold text-muted">Producto</th>
                                        <th class="fw-semibold text-muted text-end">Cant.</th>
                                        <th class="fw-semibold text-muted text-end">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProductos as $i => $prod)
                                    <tr style="border-bottom:1px solid #f5f5f5;">
                                        <td class="ps-0 fw-bold" style="color:{{ $i === 0 ? '#f5a623' : ($i === 1 ? '#9e9e9e' : ($i === 2 ? '#cd7f32' : '#bbb')) }};">
                                            {{ $i + 1 }}
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark" style="line-height:1.2;">{{ $prod->pro_nombre }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <div class="rounded" style="height:4px;width:{{ min(100, round(($prod->total_cantidad / $maxCant) * 100)) }}px;background:#1a6b35;min-width:4px;"></div>
                                                <small class="text-muted">[{{ $prod->pro_codigo }}]</small>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold" style="color:#1a6b35;">
                                            {{ number_format($prod->total_cantidad, 0) }}
                                        </td>
                                        <td class="text-end text-dark">
                                            S/ {{ number_format($prod->total_importe, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-box-open fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                            <small>Sin ventas registradas en el período</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Columna derecha: Sin Rotación + Sin Movimiento --}}
        <div class="col-12 col-xl-6 d-flex flex-column gap-3">

            {{-- Productos sin rotación --}}
            <div class="card border-0 shadow-sm flex-grow-1">
                <div class="card-header border-0 pb-0 pt-3 px-3" style="background:transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#fff3e0;">
                            <i class="fa-solid fa-circle-exclamation" style="color:#e65100;font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size:0.88rem;">Productos Sin Rotación</h6>
                            <small class="text-muted" style="font-size:0.72rem;">Con stock disponible pero sin ventas en el período</small>
                        </div>
                        @if($sinRotacion->count())
                            <span class="badge ms-auto" style="background:#e65100;font-size:0.7rem;">
                                {{ $sinRotacion->count() }}{{ $sinRotacion->count() >= 20 ? '+' : '' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body px-3 pt-2 pb-2">
                    @if($sinRotacion->count())
                        <div style="max-height:240px;overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:0.76rem;">
                                <thead style="position:sticky;top:0;background:#fff;z-index:1;">
                                    <tr style="border-bottom:2px solid #e0e0e0;">
                                        <th class="fw-semibold text-muted ps-0">#</th>
                                        <th class="fw-semibold text-muted">Producto</th>
                                        <th class="fw-semibold text-muted text-end">Stock</th>
                                        <th class="fw-semibold text-muted">Establecimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sinRotacion as $i => $prod)
                                    <tr style="border-bottom:1px solid #f5f5f5;">
                                        <td class="ps-0 text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark" style="line-height:1.2;">{{ $prod->pro_nombre }}</div>
                                            <small class="text-muted">[{{ $prod->pro_codigo }}]</small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge" style="background:#fff3e0;color:#e65100;font-size:0.72rem;">
                                                {{ number_format($prod->ps_stock, 0) }} uds.
                                            </span>
                                        </td>
                                        <td class="text-muted" style="font-size:0.72rem;">{{ $prod->sucursal_nombre }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($sinRotacion->count() >= 20)
                            <p class="text-center text-muted mt-2 mb-0" style="font-size:0.72rem;">
                                Mostrando los primeros 20. Ve a <strong>Reporte &gt; Productos más vendidos</strong> para el listado completo.
                            </p>
                        @endif
                    @else
                        <div class="text-center py-3 text-muted">
                            <i class="fa-solid fa-circle-check fa-2x mb-2 d-block" style="opacity:0.3;color:#1a6b35;"></i>
                            <small>¡Todos los productos con stock tuvieron movimiento en el período!</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Productos sin movimiento --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body px-3 py-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:{{ $totalSinMovimiento > 0 ? '#fff8e1' : '#e8f5e9' }};">
                        <i class="fa-solid fa-{{ $totalSinMovimiento > 0 ? 'triangle-exclamation' : 'circle-check' }}"
                           style="font-size:1.2rem;color:{{ $totalSinMovimiento > 0 ? '#f59e0b' : '#1a6b35' }};"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:0.88rem;">Productos Sin Movimiento</div>
                        <div class="text-muted" style="font-size:0.76rem;">Sin ventas en el período seleccionado</div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-bold {{ $totalSinMovimiento > 0 ? 'text-warning' : 'text-success' }}"
                             style="font-size:1.6rem;line-height:1;">
                            {{ number_format($totalSinMovimiento) }}
                        </div>
                        <small class="text-muted" style="font-size:0.7rem;">producto{{ $totalSinMovimiento !== 1 ? 's' : '' }}</small>
                    </div>
                    @if($totalSinMovimiento > 0)
                        @can('productos_mas_vendidos.exportar')
                        <a href="{{ route('reporte.productos_mas_vendidos') }}"
                           class="btn btn-sm btn-outline-warning flex-shrink-0">
                            <i class="fa fa-chart-bar me-1"></i> Ver reporte
                        </a>
                        @endcan
                    @endif
                </div>
            </div>

        </div>

    </div>

    {{-- Loader --}}
    <div wire:loading wire:target="cambiarPeriodo, stockAnterior, stockSiguiente">
        <x-loader />
    </div>

</div>
@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endassets
@script
<script>
    // ── Paleta ASSU ──────────────────────────────────────────────
    const ASSU = {
        azul:    '#0b1892',
        azulElec:'#2257f1',
        lima:    '#aadd00',
        magenta: '#e600cc',
        gris:    '#6c6c9a',
        cyan:    '#00b4d8',
    };

    // ── Instancias de gráficos ───────────────────────────────────
    let chartLinea    = null;
    let chartDonut    = null;
    let chartVendedor = null;
    let chartCaja     = null;
    let chartVentasMes = null;

    // ── Función auxiliar para destruir y recrear ─────────────────
    function destruirGrafico(instancia) {
        if (instancia) { try { instancia.destroy(); } catch(e){} }
        return null;
    }

    // ── Listener principal ───────────────────────────────────────
    $wire.on('actualizarGraficos', (event) => {
        setTimeout(() => {
            const data = Array.isArray(event) ? event[0] : event;
            renderLinea(data.linea, data.periodo);
            renderDonut(data.donut);
            renderVendedor(data.vendedor);
            renderCaja(data.caja);
            if (data.ventasMes) renderVentasMes(data.ventasMes);
        }, 150);
    });

    // ── Gráfico 1 — Línea/Barras de ingresos ─────────────────────
    function renderLinea(data, periodo) {
        const el = document.querySelector('#grafico-linea');
        if (!el) return;

        const opciones = {
            series: [{ name: 'Ingreso Total', data: data.totales }],
            chart: {
                type: data.esPorHora ? 'bar' : 'area',
                height: 280,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'inherit',
            },
            colors: [ASSU.azul],
            fill: {
                type: data.esPorHora ? 'solid' : 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 100],
                },
            },
            stroke: { curve: 'smooth', width: data.esPorHora ? 0 : 3 },
            dataLabels: { enabled: false },
            xaxis: {
                categories: data.labels,
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: {
                labels: {
                    formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            tooltip: {
                y: { formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            plotOptions: {
                bar: { borderRadius: 4, columnWidth: '55%' },
            },
        };

        if (!chartLinea) {
            chartLinea = new ApexCharts(el, opciones);
            chartLinea.render();
        } else {
            chartLinea.updateOptions({ chart: { type: data.esPorHora ? 'bar' : 'area' }, xaxis: { categories: data.labels } });
            chartLinea.updateSeries([{ name: 'Ingreso Total', data: data.totales }]);
        }
    }

    // ── Gráfico 2 — Donut distribución ───────────────────────────
    function renderDonut(data) {
        const el = document.querySelector('#grafico-donut');
        if (!el) return;

        const opciones = {
            series: data.totales,
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            labels: data.labels,
            colors: [ASSU.azul, ASSU.lima, ASSU.magenta],
            legend: { position: 'bottom', fontSize: '12px' },
            dataLabels: {
                enabled: true,
                formatter: (val) => val.toFixed(1) + '%',
            },
            tooltip: {
                y: { formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: (w) => {
                                    const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return 'S/ ' + parseFloat(sum).toLocaleString('es-PE', { minimumFractionDigits: 2 });
                                },
                            },
                        },
                    },
                },
            },
        };

        if (!chartDonut) {
            chartDonut = new ApexCharts(el, opciones);
            chartDonut.render();
        } else {
            chartDonut.updateOptions({ labels: data.labels });
            chartDonut.updateSeries(data.totales);
        }
    }

    // ── Gráfico 3 — Barras vendedores ────────────────────────────
    function renderVendedor(data) {
        const el = document.querySelector('#grafico-vendedor');
        if (!el) return;

        const opciones = {
            series: [{ name: 'Ventas', data: data.totales }],
            chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: [ASSU.azulElec],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            xaxis: {
                categories: data.labels,
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: {
                labels: {
                    formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            tooltip: {
                y: { formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        };

        if (!chartVendedor) {
            chartVendedor = new ApexCharts(el, opciones);
            chartVendedor.render();
        } else {
            chartVendedor.updateOptions({ xaxis: { categories: data.labels } });
            chartVendedor.updateSeries([{ name: 'Ventas', data: data.totales }]);
        }
    }

    // ── Gráfico 4 — Barras horizontales cajas ────────────────────
    function renderCaja(data) {
        const el = document.querySelector('#grafico-caja');
        if (!el) return;

        const opciones = {
            series: [{ name: 'Ventas', data: data.totales }],
            chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: [ASSU.magenta],
            plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '55%' } },
            dataLabels: { enabled: false },
            xaxis: {
                labels: {
                    formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            yaxis: {
                labels: { style: { fontSize: '11px' } },
            },
            tooltip: {
                y: { formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        };

        if (!chartCaja) {
            chartCaja = new ApexCharts(el, opciones);
            chartCaja.render();
        } else {
            chartCaja.updateOptions({ yaxis: { categories: data.labels } });
            chartCaja.updateSeries([{ name: 'Ventas', data: data.totales }]);
        }
    }
    // ── Gráfico 5 — Barras agrupadas ventas por mes ──────────────
    function renderVentasMes(data) {
        const el = document.querySelector('#grafico-ventas-mes');
        if (!el || !data || !data.meses) return;

        const labels   = data.meses.map(m => m.nombre);
        const efectivo = data.meses.map(m => m.efectivo);
        const qr       = data.meses.map(m => m.qr);
        const neto     = data.meses.map(m => m.neto);
        const mesActualIdx = data.meses.findIndex(m => m.actual);

        const opciones = {
            series: [
                { name: 'Efectivo', data: efectivo },
                { name: 'QR',       data: qr },
                { name: 'Neto',     data: neto },
            ],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            colors: [ASSU.azul, ASSU.lima, ASSU.magenta],
            plotOptions: {
                bar: {
                    borderRadius: 3,
                    columnWidth: '65%',
                    grouped: true,
                },
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: labels,
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: {
                labels: {
                    formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 0 }),
                    style: { fontSize: '11px' },
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (v) => 'S/ ' + parseFloat(v).toLocaleString('es-PE', { minimumFractionDigits: 2 }) },
            },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
            annotations: mesActualIdx >= 0 ? {
                xaxis: [{
                    x: labels[mesActualIdx],
                    borderColor: ASSU.azulElec,
                    borderWidth: 2,
                    label: {
                        text: 'Mes actual',
                        style: { color: '#fff', background: ASSU.azulElec, fontSize: '10px' },
                    },
                }],
            } : {},
        };

        if (!chartVentasMes) {
            chartVentasMes = new ApexCharts(el, opciones);
            chartVentasMes.render();
        } else {
            chartVentasMes.updateOptions({ xaxis: { categories: labels } });
            chartVentasMes.updateSeries([
                { name: 'Efectivo', data: efectivo },
                { name: 'QR',       data: qr },
                { name: 'Neto',     data: neto },
            ]);
        }
    }

    $wire.on('abrirModalCierre', () => {
        new bootstrap.Modal(document.getElementById('modalCierreCaja')).show();
    });

    $wire.on('cerrarModalCierre', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCierreCaja'));
        if (modal) modal.hide();
    });
</script>
@endscript
