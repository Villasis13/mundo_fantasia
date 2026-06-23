<div>
    <style>
        .assu-azul    { background: linear-gradient(135deg, #0b1892, #2257f1); }
        .assu-lima    { background: linear-gradient(135deg, #5a9900, #aadd00); }
        .assu-magenta { background: linear-gradient(135deg, #b3009e, #e600cc); }
        .stock-critico { background: #fde8e8; }
        .stock-bajo    { background: #fff8e1; }
        .periodo-btn-active { background: #0b1892 !important; color: #fff !important; border-color: #0b1892 !important; }
        .sucursal-card-btn { cursor:pointer; transition: all .15s ease; border:2px solid transparent; }
        .sucursal-card-btn:hover { border-color:#0b1892; background:#eef1ff !important; }
    </style>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Selección obligatoria de sucursal (vendedor)     --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSeleccionSucursal" wire:ignore.self
         tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div style="height:5px;background:linear-gradient(90deg,#0b1892,#2257f1);"></div>
                <div class="modal-body px-4 pt-4 pb-2 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:72px;height:72px;background:#eef1ff;">
                        <i class="fa-solid fa-store fa-2x" style="color:#0b1892;"></i>
                    </div>
                    <h5 class="fw-bold mb-1">¿En qué sede trabajarás hoy?</h5>
                    <p class="text-muted mb-4" style="font-size:.875rem;">
                        Selecciona una sede para cargar tus cajas y continuar.
                    </p>
                    <div class="d-flex flex-column gap-2 text-start">
                        @foreach($sucursalesVendedor as $suc)
                            <div class="rounded-2 p-3 sucursal-card-btn"
                                 style="background:#f8f9fa;"
                                 wire:click="seleccionarSucursal({{ $suc->id_sucursal }})"
                                 wire:loading.attr="disabled"
                                 wire:target="seleccionarSucursal({{ $suc->id_sucursal }})">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:36px;height:36px;background:#0b1892;">
                                            <i class="fa-solid fa-store text-white" style="font-size:.75rem;"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $suc->sucursal_nombre }}</span>
                                    </div>
                                    <span wire:loading.remove wire:target="seleccionarSucursal({{ $suc->id_sucursal }})">
                                    <i class="fa-solid fa-chevron-right text-muted opacity-50"></i>
                                </span>
                                    <span wire:loading wire:target="seleccionarSucursal({{ $suc->id_sucursal }})">
                                    <span class="spinner-border spinner-border-sm text-primary"></span>
                                </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4 pt-2">
                    <small class="text-muted">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Esta selección se recordará durante la sesión.
                    </small>
                </div>
            </div>
        </div>
    </div>

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
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                {{ $saludo }}, <span style="color:#0b1892;">{{ $nombreUsuario }}</span> 👋
            </h4>
            <small class="text-muted">Panel de Vendedor</small>
        </div>
        @if($sucursalNombre)
            <span class="badge d-flex align-items-center gap-1 px-3 py-2"
                  style="background:#eef1ff;color:#0b1892;font-size:.8rem;">
                <i class="fa-solid fa-store me-1"></i> {{ $sucursalNombre }}
            </span>
        @endif
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

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 1 — Caja + Alertas SUNAT                           --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Caja del día --}}
        <div class="col-lg-4 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h6 class="fw-bold mb-3" style="color:#0b1892;">
                        <i class="fa-solid fa-cash-register me-2"></i> Caja del Día
                    </h6>

                    @if($necesitaSeleccionarSucursal)
                        {{-- Pendiente de elegir sucursal --}}
                        <div class="text-center text-muted py-4 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="fa-solid fa-store fa-2x opacity-25 mb-2"></i>
                            <p class="mb-3 small">Primero debes seleccionar en qué sede trabajarás hoy.</p>
                            <button class="btn btn-sm fw-semibold text-white"
                                    style="background:#0b1892;"
                                    wire:click="$dispatch('abrirModalSucursal')">
                                <i class="fa-solid fa-store me-1"></i> Seleccionar sede
                            </button>
                        </div>

                    @elseif(!$apertura)
                        {{-- Sucursal activa — formulario apertura --}}
                        @if($sucursalNombre)
                            <div class="mb-3">
                                <span class="badge rounded-pill fw-normal"
                                      style="background:#eef1ff;color:#0b1892;font-size:.72rem;">
                                    <i class="fa-solid fa-store me-1"></i>{{ $sucursalNombre }}
                                </span>
                            </div>
                        @endif

                        @if($cajas->isEmpty())
                            <div class="text-center text-muted py-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-cash-register fa-xl opacity-25 mb-2"></i>
                                <small>No hay cajas activas en esta sede.</small>
                            </div>
                        @else
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-1">Caja</label>
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
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-1">Monto de Apertura</label>
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
                                    <i class="fa-solid fa-lock-open me-1"></i> Aperturar Caja
                                </span>
                                <span wire:loading wire:target="aperturarCaja">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Aperturando...
                                </span>
                            </button>
                        @endif

                    @else
                        {{-- Caja abierta --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-success">Abierta</span>
                            <span class="fw-bold small">{{ $apertura->caja_numero_nombre }}</span>
                        </div>
                        @if($apertura->sucursal_nombre)
                            <div class="mb-2">
                                <span class="badge rounded-pill fw-normal"
                                      style="background:#eef1ff;color:#0b1892;font-size:.72rem;">
                                    <i class="fa-solid fa-store me-1"></i>{{ $apertura->sucursal_nombre }}
                                </span>
                            </div>
                        @endif
                        <div class="rounded p-2 mb-3" style="background:#f5f5f5;">
                            <small class="text-muted d-block" style="font-size:11px;">APERTURA</small>
                            <span class="fw-semibold small">
                                S/ {{ number_format($apertura->caja_apertura, 2) }}
                                — {{ \Carbon\Carbon::parse($apertura->caja_fecha_apertura)->format('H:i') }}
                            </span>
                        </div>
                        <button class="btn btn-danger w-100 fw-semibold mt-auto" wire:click="abrirModalCierre">
                            <i class="fa-solid fa-lock me-1"></i> Cerrar Caja
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Alertas SUNAT --}}
        <div class="col-lg-8 col-md-12">
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
                            Tienes <strong>{{ $pendientesSunat }}</strong> comprobante(s) pendientes de envío a SUNAT.
                        </div>
                    @endif

                    @if($comprobantesConAlerta->count() > 0)
                        <p class="text-muted small mb-2 fw-semibold">⚠️ Respuestas fuera del patrón habitual:</p>
                        <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                <tr class="encabezado_tabla_color">
                                    <th>Comprobante</th>
                                    <th>Fecha</th>
                                    <th>Respuesta SUNAT</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($comprobantesConAlerta as $comp)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ match($comp->venta_tipo) { '01'=>'FAC','03'=>'BOL','07'=>'NC','08'=>'ND',default=>$comp->venta_tipo } }}
                                            </span>
                                            {{ $comp->venta_serie }}-{{ $comp->venta_correlativo }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ \Carbon\Carbon::parse($comp->venta_fecha)->format('d/m/Y') }}
                                        </td>
                                        <td class="text-danger small">
                                            {{ Str::limit($comp->venta_respuesta_sunat, 60) }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($pendientesSunat === 0)
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                            Todos los comprobantes tienen respuesta correcta de SUNAT.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 2 — Cuadre de caja del turno                       --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($apertura && $resumenCajaActual)
    @php $r = (object) $resumenCajaActual; @endphp

    <div class="mb-4">

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
                            <span class="fw-semibold small" style="color:{{ $dif == 0 ? '#1a6b35' : ($dif > 0 ? '#0077b6' : '#b30000') }};">
                                {{ $dif == 0 ? 'Cuadre exacto' : ($dif > 0 ? 'Sobrante' : 'Faltante') }}
                            </span>
                            <span class="fw-bold" style="color:{{ $dif == 0 ? '#1a6b35' : ($dif > 0 ? '#0077b6' : '#b30000') }};font-size:1rem;">
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

        {{-- Últimas ventas del turno --}}
        @if($ventasTurno->count())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-0 fw-semibold small">
                <i class="fa-solid fa-file-invoice me-1 text-primary"></i>
                Comprobantes del turno ({{ $ventasTurno->count() }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Tipo</th>
                                <th>Serie - Correlativo</th>
                                <th>Cliente</th>
                                <th class="text-end pe-3">Total</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($ventasTurno as $v)
                            @php $nomCli = $v->id_tipo_documento == 4 ? $v->cliente_razonsocial : $v->cliente_nombre; @endphp
                            <tr>
                                <td class="ps-3 small">
                                    {{ match($v->venta_tipo) { '01'=>'Factura','03'=>'Boleta','20'=>'Nota de Venta',default=>$v->venta_tipo } }}
                                </td>
                                <td class="small fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                                <td class="small">{{ Str::limit($nomCli, 25) }}</td>
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

    </div>

    @else
    {{-- Caja no abierta: placeholder --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-cash-register fa-3x opacity-25 mb-3 d-block"></i>
                <p class="mb-0">Apertura tu caja para ver el cuadre del turno.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 3 — Stock bajo                                      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Productos con Stock Bajo
                        </h6>
                        @if($totalStockBajo > 0)
                            <span class="badge" style="background:#aadd00;color:#000;">{{ $totalStockBajo }} productos</span>
                        @endif
                    </div>

                    @if($productosStockBajo->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-2">
                                <thead>
                                <tr class="encabezado_tabla_color">
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($productosStockBajo as $i => $p)
                                    <tr class="{{ $p->stock_actual <= 0 ? 'stock-critico' : ($p->stock_actual <= 5 ? 'stock-bajo' : '') }}">
                                        <td class="text-muted">
                                            {{ ($stockPaginaActual - 1) * $stockPorPagina + $i + 1 }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $p->pro_codigo }}</span>
                                        </td>
                                        <td class="fw-semibold">{{ $p->pro_nombre }}</td>
                                        <td class="text-muted small">{{ $p->ca_nombre }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $p->stock_actual <= 0 ? 'bg-danger' : ($p->stock_actual <= 5 ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">
                                                {{ $p->stock_actual }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($p->stock_actual <= 0)
                                                <span class="badge bg-danger">Sin stock</span>
                                            @elseif($p->stock_actual <= 5)
                                                <span class="badge bg-warning text-dark">Crítico</span>
                                            @else
                                                <span class="badge bg-secondary">Bajo</span>
                                            @endif
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
                                    ({{ $totalStockBajo }} productos en total)
                                </small>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary"
                                            wire:click="stockAnterior"
                                        {{ $stockPaginaActual <= 1 ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-chevron-left"></i> Anterior
                                    </button>
                                    <button class="btn btn-outline-secondary"
                                            wire:click="stockSiguiente({{ $totalPaginasStock }})"
                                        {{ $stockPaginaActual >= $totalPaginasStock ? 'disabled' : '' }}>
                                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                            Todos los productos tienen stock suficiente.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@script
<script>
    $wire.on('abrirModalCierre', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCierreCaja')).show();
    });
    $wire.on('cerrarModalCierre', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalCierreCaja'));
        if (m) m.hide();
    });

    $wire.on('abrirModalSucursal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeleccionSucursal')).show();
    });
    $wire.on('cerrarModalSucursal', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalSeleccionSucursal'));
        if (m) m.hide();
    });

    // Abrir automáticamente si el vendedor tiene múltiples sucursales sin elegir
    document.addEventListener('livewire:initialized', () => {
        if (@json($necesitaSeleccionarSucursal)) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeleccionSucursal')).show();
        }
    });
</script>
@endscript
