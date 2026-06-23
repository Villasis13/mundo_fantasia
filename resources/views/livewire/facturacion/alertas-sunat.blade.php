<div>
    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Alertas y Seguimiento SUNAT
            </h5>
            <small class="text-muted">Comprobantes pendientes, atrasados y con errores de envío</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('facturacion.pendiente_declarar') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-paper-plane me-1"></i>Ir a Pendientes
            </a>
            <a href="{{ route('facturacion.historial_envios') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Historial
            </a>
        </div>
    </div>

    {{-- ── Flash ──────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible py-2 mb-3">
            <i class="fa-solid fa-circle-check me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible py-2 mb-3">
            <i class="fa-solid fa-circle-xmark me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filtros empresa/sucursal ───────────────────────────── --}}
    @if($esSuperAdmin || count($sucursalesDisponibles) > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    @if($esSuperAdmin)
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label form-label-sm mb-1">Empresa</label>
                            <select wire:model.live="empresaSeleccionada" class="form-select form-select-sm">
                                <option value="0">— Todas las empresas —</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(count($sucursalesDisponibles) > 0)
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label form-label-sm mb-1">Sucursal</label>
                            <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm">
                                <option value="0">— Todas —</option>
                                @foreach($sucursalesDisponibles as $suc)
                                    <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if(!$idEmpresaActiva && $esSuperAdmin)
        <div class="text-center py-5 text-muted border rounded">
            <i class="fa-solid fa-building fa-2x mb-2"></i>
            <p class="mb-0">Selecciona una empresa para ver las alertas.</p>
        </div>
    @else

    {{-- ── Tarjetas de contadores ─────────────────────────────── --}}
    <div class="row g-3 mb-4">

        {{-- Atrasados (crítico) --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left: 4px solid #dc3545 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Atrasados</div>
                            <div class="fs-2 fw-bold text-danger">{{ $contadores['atrasados'] }}</div>
                            <div class="small text-muted">días anteriores sin declarar</div>
                        </div>
                        <div class="text-danger opacity-25">
                            <i class="fa-solid fa-circle-exclamation fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Con error previo --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left: 4px solid #fd7e14 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Con error previo</div>
                            <div class="fs-2 fw-bold text-warning">{{ $contadores['con_error'] }}</div>
                            <div class="small text-muted">intento fallido registrado</div>
                        </div>
                        <div class="text-warning opacity-25">
                            <i class="fa-solid fa-ban fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pendientes hoy --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left: 4px solid #0d6efd !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Pendientes hoy</div>
                            <div class="fs-2 fw-bold text-primary">{{ $contadores['hoy'] }}</div>
                            <div class="small text-muted">{{ now()->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-primary opacity-25">
                            <i class="fa-solid fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mes actual --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left: 4px solid #198754 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Mes actual</div>
                            <div class="fs-2 fw-bold text-success">{{ $contadores['mes'] }}</div>
                            <div class="small text-muted">total sin declarar {{ now()->format('m/Y') }}</div>
                        </div>
                        <div class="text-success opacity-25">
                            <i class="fa-solid fa-calendar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Sección: Atrasados (PRIORIDAD ALTA) ────────────────── --}}
    @if($contadores['atrasados'] > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 d-flex align-items-center gap-2"
             style="background:#dc3545; color:#fff;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <strong>Comprobantes atrasados — días anteriores sin declarar</strong>
            <span class="badge bg-light text-danger ms-auto">{{ $contadores['atrasados'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Tipo</th>
                            <th>Serie - Número</th>
                            <th>Cliente</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Días atraso</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($atrasados as $v)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $v->venta_tipo === '01' ? 'bg-primary' : ($v->venta_tipo === '03' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                        {{ $v->tipo_label }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                                <td class="text-truncate" style="max-width:180px;">{{ $v->cliente_nombre }}</td>
                                <td class="text-end">S/ {{ number_format($v->venta_total, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $v->dias_pendiente }} día{{ $v->dias_pendiente != 1 ? 's' : '' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button wire:click="confirmarEnvio({{ $v->id_venta }})"
                                                class="btn btn-xs btn-success py-0 px-2" title="Enviar a SUNAT" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </button>
                                        <button wire:click="confirmarMarcarEnviado({{ $v->id_venta }})"
                                                class="btn btn-xs btn-outline-primary py-0 px-2" title="Marcar como enviado" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button wire:click="confirmarIgnorar({{ $v->id_venta }})"
                                                class="btn btn-xs btn-outline-secondary py-0 px-2" title="Ignorar/Anular" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Sección: Con error previo ───────────────────────────── --}}
    @if($contadores['con_error'] > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 d-flex align-items-center gap-2"
             style="background:#fd7e14; color:#fff;">
            <i class="fa-solid fa-ban"></i>
            <strong>Con error de envío previo</strong>
            <span class="badge bg-light text-warning ms-auto">{{ $contadores['con_error'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Tipo</th>
                            <th>Serie - Número</th>
                            <th>Cliente</th>
                            <th class="text-end">Total</th>
                            <th>Último mensaje SUNAT</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conError as $v)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $v->venta_tipo === '01' ? 'bg-primary' : ($v->venta_tipo === '03' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                        {{ $v->tipo_label }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                                <td class="text-truncate" style="max-width:140px;">{{ $v->cliente_nombre }}</td>
                                <td class="text-end">S/ {{ number_format($v->venta_total, 2) }}</td>
                                <td>
                                    <span class="text-warning fw-semibold" style="font-size:0.75rem;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        {{ \Illuminate\Support\Str::limit($v->venta_respuesta_sunat, 60) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button wire:click="confirmarEnvio({{ $v->id_venta }})"
                                                class="btn btn-xs btn-success py-0 px-2" title="Reintentar envío" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-rotate-right"></i>
                                        </button>
                                        <button wire:click="confirmarMarcarEnviado({{ $v->id_venta }})"
                                                class="btn btn-xs btn-outline-primary py-0 px-2" title="Marcar como enviado" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button wire:click="confirmarIgnorar({{ $v->id_venta }})"
                                                class="btn btn-xs btn-outline-secondary py-0 px-2" title="Ignorar" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Sección: Pendientes de hoy ─────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 d-flex align-items-center gap-2"
             style="background:#0d6efd; color:#fff;">
            <i class="fa-solid fa-clock"></i>
            <strong>Pendientes de hoy — {{ now()->format('d/m/Y') }}</strong>
            <span class="badge bg-light text-primary ms-auto">{{ $contadores['hoy'] }}</span>
        </div>
        <div class="card-body p-0">
            @if($contadores['hoy'] === 0)
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-circle-check text-success fa-2x mb-2"></i>
                    <p class="mb-0 fw-semibold text-success">¡Todo al día! No hay comprobantes pendientes para hoy.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tipo</th>
                                <th>Serie - Número</th>
                                <th>Cliente</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hoy as $v)
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge {{ $v->venta_tipo === '01' ? 'bg-primary' : ($v->venta_tipo === '03' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                            {{ $v->tipo_label }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                                    <td class="text-truncate" style="max-width:200px;">{{ $v->cliente_nombre }}</td>
                                    <td class="text-end">S/ {{ number_format($v->venta_total, 2) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button wire:click="confirmarEnvio({{ $v->id_venta }})"
                                                    class="btn btn-xs btn-success py-0 px-2" title="Enviar a SUNAT" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                            <button wire:click="confirmarMarcarEnviado({{ $v->id_venta }})"
                                                    class="btn btn-xs btn-outline-primary py-0 px-2" title="Marcar enviado" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @endif {{-- fin del bloque empresa activa --}}

    {{-- ── Modal de confirmación ──────────────────────────────── --}}
    <div class="modal fade" id="modalAlertasSunat" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2" style="background:#1e3a5f; color:#fff;">
                    <h6 class="modal-title mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Confirmar acción
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-0 small">{{ $mensajeConfirmacion }}</p>
                </div>
                <div class="modal-footer py-2 gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="ejecutarConfirmacion">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@script
<script>
    $wire.on('abrirModalAlertasSunat', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalAlertasSunat'));
        modal.show();
    });
    $wire.on('cerrarModalAlertasSunat', () => {
        const el = document.getElementById('modalAlertasSunat');
        const modal = bootstrap.Modal.getInstance(el);
        if (modal) modal.hide();
    });
</script>
@endscript
