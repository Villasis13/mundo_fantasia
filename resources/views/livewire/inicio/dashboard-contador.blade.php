<div>
    <style>
        .assu-azul    { background: linear-gradient(135deg, #0b1892, #2257f1); }
        .assu-magenta { background: linear-gradient(135deg, #b3009e, #e600cc); }
        .assu-lima    { background: linear-gradient(135deg, #5a9900, #aadd00); }
        .assu-gris    { background: linear-gradient(135deg, #3a3a5c, #6c6c9a); }
    </style>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  ENCABEZADO                                               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <h4 class="fw-bold mb-0">
            {{ $saludo }}, <span style="color:#0b1892;">{{ $nombreUsuario }}</span> 👋
        </h4>
        <small class="text-muted">Panel de Contador — Estado de comprobantes SUNAT</small>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 1 — Tarjetas resumen SUNAT                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Total pendientes --}}
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm text-white h-100 {{ $pendientesSunat > 0 ? 'assu-magenta' : 'assu-lima' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="opacity-75 text-uppercase fw-semibold" style="font-size:10px;">
                                Pendientes SUNAT
                            </small>
                            <h3 class="fw-bold mb-0 mt-1 text-white">{{ number_format($pendientesSunat) }}</h3>
                            <small class="opacity-75">
                                {{ $pendientesSunat > 0 ? 'Requieren atención' : 'Todo al día' }}
                            </small>
                        </div>
                        <i class="fa-solid {{ $pendientesSunat > 0 ? 'fa-clock' : 'fa-circle-check' }} fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Facturas pendientes --}}
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm text-white h-100 assu-azul">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="opacity-75 text-uppercase fw-semibold" style="font-size:10px;">Facturas</small>
                            <h3 class="fw-bold mb-0 mt-1 text-white">{{ $resumenPorTipo->get('01')?->cantidad ?? 0 }}</h3>
                            <small class="opacity-75">Pendientes</small>
                        </div>
                        <i class="fa-solid fa-file-invoice fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Boletas pendientes --}}
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm text-white h-100 assu-gris">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="opacity-75 text-uppercase fw-semibold" style="font-size:10px;">Boletas</small>
                            <h3 class="fw-bold mb-0 mt-1 text-white">{{ $resumenPorTipo->get('03')?->cantidad ?? 0 }}</h3>
                            <small class="opacity-75">Pendientes</small>
                        </div>
                        <i class="fa-solid fa-receipt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notas pendientes --}}
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm text-white h-100"
                 style="background:linear-gradient(135deg,#c05200,#fd7e14);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="opacity-75 text-uppercase fw-semibold" style="font-size:10px;">Notas (NC/ND)</small>
                            <h3 class="fw-bold mb-0 mt-1 text-white">
                                {{ ($resumenPorTipo->get('07')?->cantidad ?? 0) + ($resumenPorTipo->get('08')?->cantidad ?? 0) }}
                            </h3>
                            <small class="opacity-75">Pendientes</small>
                        </div>
                        <i class="fa-solid fa-file-circle-exclamation fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 2 — Resumen CxC y CxP                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- CxC --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa fa-file-invoice-dollar me-2 text-primary"></i> Cuentas por Cobrar
                        </h6>
                        <a href="{{ url('cxc/cuentas_cobrar') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-eye me-1"></i> Ver
                        </a>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="rounded p-3 text-center" style="background:#eef1ff;">
                                <small class="fw-semibold d-block text-uppercase" style="font-size:10px;color:#0b1892;">Pendiente total</small>
                                <h5 class="fw-bold mb-0" style="color:#0b1892;">{{ $cantidadCxcPendiente }}</h5>
                                <small class="text-muted">S/ {{ number_format($totalCxcPendiente, 2) }}</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-3 text-center" style="background:#fde8e8;">
                                <small class="text-danger fw-semibold d-block text-uppercase" style="font-size:10px;">Vencido</small>
                                <h5 class="fw-bold text-danger mb-0">{{ $cantidadCxcVencida }}</h5>
                                <small class="text-muted">S/ {{ number_format($totalCxcVencida, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    @if($cantidadCxcVencida > 0)
                        <div class="alert alert-danger py-2 small mt-3 mb-0">
                            <i class="fa fa-triangle-exclamation me-1"></i>
                            Hay <strong>{{ $cantidadCxcVencida }}</strong> cuota(s) vencidas por cobrar.
                        </div>
                    @else
                        <div class="alert alert-success py-2 small mt-3 mb-0">
                            <i class="fa fa-circle-check me-1"></i> Sin cuotas vencidas.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- CxP --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:#0b1892;">
                            <i class="fa fa-file-invoice me-2 text-warning"></i> Cuentas por Pagar
                        </h6>
                        <a href="{{ url('cxc/cuentas_pagar') }}" class="btn btn-sm btn-outline-warning">
                            <i class="fa fa-eye me-1"></i> Ver
                        </a>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="rounded p-3 text-center" style="background:#fff8e1;">
                                <small class="fw-semibold d-block text-uppercase" style="font-size:10px;color:#856404;">Pendiente total</small>
                                <h5 class="fw-bold mb-0" style="color:#856404;">{{ $cantidadCxpPendiente }}</h5>
                                <small class="text-muted">S/ {{ number_format($totalCxpPendiente, 2) }}</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-3 text-center" style="background:#fde8e8;">
                                <small class="text-danger fw-semibold d-block text-uppercase" style="font-size:10px;">Vencido</small>
                                <h5 class="fw-bold text-danger mb-0">{{ $cantidadCxpVencida }}</h5>
                                <small class="text-muted">S/ {{ number_format($totalCxpVencida, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    @if($cantidadCxpVencida > 0)
                        <div class="alert alert-danger py-2 small mt-3 mb-0">
                            <i class="fa fa-triangle-exclamation me-1"></i>
                            Hay <strong>{{ $cantidadCxpVencida }}</strong> cuenta(s) vencidas por pagar.
                        </div>
                    @else
                        <div class="alert alert-success py-2 small mt-3 mb-0">
                            <i class="fa fa-circle-check me-1"></i> Sin cuentas vencidas.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 3 — Comprobantes pendientes de envío               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($pendientesSunat > 0)
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-clock text-warning fa-lg"></i>
                            <h6 class="fw-bold mb-0" style="color:#0b1892;">
                                Comprobantes pendientes de envío a SUNAT
                            </h6>
                            <span class="badge bg-warning text-dark ms-auto">{{ $pendientesSunat }} total</span>
                        </div>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            Estos comprobantes aún no han sido enviados a SUNAT
                            (<code>venta_estado_sunat = 0</code>). Verifica que el proceso de envío automático
                            esté funcionando correctamente.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  FILA 4 — Comprobantes con respuesta anómala (prioridad) --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm {{ $comprobantesConAlerta->count() > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation {{ $comprobantesConAlerta->count() > 0 ? 'text-danger' : 'text-success' }} fa-lg"></i>
                            <h6 class="fw-bold mb-0" style="color:#0b1892;">
                                Respuestas fuera del patrón habitual
                            </h6>
                        </div>
                        @if($comprobantesConAlerta->count() > 0)
                            <span class="badge bg-danger">
                            {{ $comprobantesConAlerta->count() }} requieren revisión
                        </span>
                        @else
                            <span class="badge bg-success">Sin anomalías</span>
                        @endif
                    </div>

                    @if($comprobantesConAlerta->count() > 0)
                        <div class="alert alert-danger py-2 small mb-3">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            Los siguientes comprobantes recibieron una respuesta de SUNAT que no coincide
                            con el patrón de aceptación habitual. Revísalos con prioridad.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr class="encabezado_tabla_color">
                                    <th>#</th>
                                    <th>Tipo</th>
                                    <th>Comprobante</th>
                                    <th>Fecha Emisión</th>
                                    <th>Respuesta SUNAT</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($comprobantesConAlerta as $i => $comp)
                                    <tr>
                                        <td class="text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="badge
                                                {{ match($comp->venta_tipo) {
                                                    '01' => 'bg-primary',
                                                    '03' => 'bg-secondary',
                                                    '07' => 'bg-warning text-dark',
                                                    '08' => 'bg-info text-dark',
                                                    default => 'bg-dark'
                                                } }}">
                                                {{ match($comp->venta_tipo) {
                                                    '01' => 'FACTURA',
                                                    '03' => 'BOLETA',
                                                    '07' => 'N. CRÉDITO',
                                                    '08' => 'N. DÉBITO',
                                                    default => $comp->venta_tipo
                                                } }}
                                            </span>
                                        </td>
                                        <td class="fw-semibold">
                                            {{ $comp->venta_serie }}-{{ $comp->venta_correlativo }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ \Carbon\Carbon::parse($comp->venta_fecha)->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <span class="text-danger small fw-semibold">
                                                {{ $comp->venta_respuesta_sunat }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $comp->id_venta]) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-primary"
                                               title="Ver detalle">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-circle-check text-success fa-3x mb-3 d-block"></i>
                            <h6 class="fw-semibold">Todo en orden</h6>
                            <p class="small mb-0">
                                Todos los comprobantes enviados a SUNAT tienen respuesta de aceptación correcta.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
