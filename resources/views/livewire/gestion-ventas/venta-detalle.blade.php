<div>

{{-- ── Modal envío por correo ──────────────────────────────────────────── --}}
<div class="modal fade" id="modalCorreoVenta" wire:ignore.self tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">
                    <i class="fa-solid fa-envelope me-2 text-info"></i>Enviar comprobante
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="enviarComprobante">
                <div class="modal-body pt-2">
                    <label class="form-label small fw-semibold">Correo de destino</label>
                    <input type="email" wire:model.lazy="correoDestino"
                           class="form-control form-control-sm @error('correoDestino') is-invalid @enderror"
                           placeholder="cliente@email.com">
                    @error('correoDestino')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"
                            wire:loading.attr="disabled" wire:target="enviarComprobante">
                            <span wire:loading.remove wire:target="enviarComprobante">
                                <i class="fa-solid fa-paper-plane me-1"></i>Enviar
                            </span>
                            <span wire:loading wire:target="enviarComprobante">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Enviando...
                            </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $monedaSimbolo = $venta->id_moneda == 1 ? 'S/' : '$';
    $monedaCodigo  = $venta->id_moneda == 1 ? 'PEN' : 'USD';
    $clienteNombre = $venta->id_tipo_documento == 4
        ? ($venta->cliente_razonsocial ?? $venta->cliente_nombre)
        : $venta->cliente_nombre;
    $nroOrden = $venta->venta_serie . '-' . $venta->venta_correlativo;
    $esCredito   = (int)$venta->id_formas_pago === 2;
    $tieneCuotas = $cuotas->count() > 0;
    $esNota      = in_array($venta->venta_tipo, ['07','08'], true);
    $tipoDocAfectado = ($venta->tipo_documento_modificar ?? '') === '03' ? 'Boleta' : 'Factura';

    $tipoLabels = [
        '01' => 'Factura Electrónica',
        '03' => 'Boleta de Venta',
        '07' => 'Nota de Crédito',
        '08' => 'Nota de Débito',
        '20' => 'Nota de Venta',
    ];
    $tipoLabel = $tipoLabels[$venta->venta_tipo] ?? $venta->venta_tipo;

    $estadoPago = match((int)$venta->venta_estado_pago) {
        0 => ['label' => 'Sin pago',            'cls' => 'danger'],
        1 => ['label' => 'Pago parcial',        'cls' => 'warning'],
        2 => ['label' => 'Pagado',              'cls' => 'success'],
        default => ['label' => 'Desconocido',   'cls' => 'secondary'],
    };
@endphp

{{-- ══════════════════════════════════════════════════
     BANNER DE CONFIRMACIÓN
══════════════════════════════════════════════════ --}}
<div class="{{$venta->anulado_sunat == 0 ? 'vd-banner' : 'vd-banner-danger'}} mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        @if($venta->anulado_sunat == 0)
            <div class="vd-check-circle">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <div class="fw-bold text-success" style="font-size:1.05rem">Venta registrada correctamente</div>
                <div class="text-muted" style="font-size:.85rem">
                    {{ $tipoLabel }} &nbsp;·&nbsp; <strong>{{ $nroOrden }}</strong>
                    &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($venta->venta_fecha)->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                <span class="vd-badge vd-badge-{{ $estadoPago['cls'] }}">
                    <span class="vd-dot"></span>{{ $estadoPago['label'] }}
                </span>
                <span class="vd-badge vd-badge-secondary">
                    {{ $esCredito ? 'Crédito' : 'Contado' }}
                </span>
            </div>
        @else
            <div class="vd-check-circle bg-danger">
                <i class="fa-solid fa-x"></i>
            </div>
            <div>
                <div class="fw-bold text-danger" style="font-size:1.05rem">Venta anulada</div>
                <div class="text-muted" style="font-size:.85rem">
                    {{ $tipoLabel }} &nbsp;·&nbsp; <strong>{{ $nroOrden }}</strong>
                    &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($venta->venta_fecha)->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                <span class="vd-badge vd-badge-{{ $estadoPago['cls'] }}">
                    <span class="vd-dot"></span>{{ $estadoPago['label'] }}
                </span>
                <span class="vd-badge vd-badge-secondary">
                    {{ $esCredito ? 'Crédito' : 'Contado' }}
                </span>
            </div>
        @endif
    </div>
</div>

<div class="row g-4 align-items-start">

    {{-- ══════════════════════════════════════════════════
         COLUMNA PRINCIPAL
    ══════════════════════════════════════════════════ --}}
    <div class="col-xl-8 col-lg-7">

        {{-- Empresa / Documento --}}
        <div class="vd-card mb-4">
            <div class="vd-card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="fw-bold" style="font-size:1.1rem;color:#111">
                            {{ $empresa->empresa_nombrecomercial }}
                        </div>
                        <div class="text-muted small">{{ $tipoLabel }}</div>
                    </div>
                    <div class="text-end">
                        <div class="vd-order-num">{{ $nroOrden }}</div>
                        <div class="text-muted small">N° de documento</div>
                    </div>
                </div>

                <hr class="vd-divider">

                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">Fecha emisión</span>
                            <span class="vd-value">{{ \Carbon\Carbon::parse($venta->venta_fecha)->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">Moneda</span>
                            <span class="vd-value">{{ $venta->id_moneda == 1 ? 'Soles (PEN)' : 'Dólares (USD)' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">IGV</span>
                            <span class="vd-value">
                                @if($venta->venta_porcentaje_igv)
                                    {{ $venta->venta_porcentaje_igv == 18.0 ? '18 %' : '10.5 %' }}
                                @else —
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">Forma de pago</span>
                            <span class="vd-value">{{ $esCredito ? 'Crédito' : 'Contado' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">Registrado por</span>
                            <span class="vd-value">{{ $venta->nombre_users ?? '—' }}</span>
                        </div>
                    </div>
                    @if($tieneCuotas)
                    <div class="col-sm-4">
                        <div class="vd-field">
                            <span class="vd-label">Cuotas</span>
                            <span class="vd-value">{{ $cuotas->count() }} cuota{{ $cuotas->count() > 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cliente --}}
        <div class="vd-card mb-4">
            <div class="vd-card-header">
                <i class="fa-solid fa-user-circle me-2 text-primary opacity-75"></i>
                <span>Datos del cliente</span>
            </div>
            <div class="vd-card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="vd-field">
                            <span class="vd-label">Nombre / Razón social</span>
                            <span class="vd-value fw-semibold">{{ $clienteNombre }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="vd-field">
                            <span class="vd-label">N° documento</span>
                            <span class="vd-value">{{ $venta->cliente_numero }}</span>
                        </div>
                    </div>
                    @if($venta->cliente_direccion)
                    <div class="col-12">
                        <div class="vd-field">
                            <span class="vd-label">Dirección</span>
                            <span class="vd-value">{{ $venta->cliente_direccion }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Productos / Servicios --}}
        <div class="vd-card mb-4">
            <div class="vd-card-header d-flex align-items-center justify-content-between">
                <span>
                    <i class="fa-solid fa-box me-2 text-primary opacity-75"></i>Productos / Servicios
                </span>
                <span class="badge bg-primary rounded-pill">{{ count($detalleVenta) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 vd-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width:36px">#</th>
                            <th>Descripción</th>
                            <th class="text-end" style="width:110px">P. Unit.</th>
                            <th class="text-center" style="width:72px">Cant.</th>
                            <th class="text-end pe-4" style="width:110px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalleVenta as $i => $item)
                        <tr>
                            <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $item->venta_detalle_nombre_producto }}</td>
                            <td class="text-end text-muted">
                                {{ $monedaSimbolo }} {{ number_format($item->venta_detalle_precio_unitario, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="vd-qty-badge">{{ $item->venta_detalle_cantidad }}</span>
                            </td>
                            <td class="text-end pe-4 fw-semibold">
                                {{ $monedaSimbolo }} {{ number_format($item->venta_detalle_importe_total, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Nota de crédito / débito afectada --}}
        @if($esNota)
            <div class="vd-card vd-card-warning mb-4">
                <div class="vd-card-header">
                    <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                    <span>Documento afectado</span>
                </div>
                <div class="vd-card-body">
                    <div class="row g-3">
                        <div class="col-sm-3">
                            <div class="vd-field">
                                <span class="vd-label">Tipo</span>
                                <span class="vd-value">{{ $tipoDocAfectado }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="vd-field">
                                <span class="vd-label">Serie</span>
                                <span class="vd-value">{{ $venta->serie_modificar }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="vd-field">
                                <span class="vd-label">Correlativo</span>
                                <span class="vd-value">{{ $venta->correlativo_modificar }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="vd-field">
                                <span class="vd-label">Motivo</span>
                                <span class="vd-value">{{ $venta->des ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>{{-- /col principal --}}

    {{-- ══════════════════════════════════════════════════
         COLUMNA LATERAL
    ══════════════════════════════════════════════════ --}}
    <div class="col-xl-4 col-lg-5">

        {{-- Resumen financiero --}}
        <div class="vd-card mb-4">
            <div class="vd-card-header">
                <i class="fa-solid fa-receipt me-2 text-primary opacity-75"></i>
                <span>Resumen financiero</span>
            </div>
            <div class="vd-card-body">

                @if($venta->venta_totalgravada > 0)
                <div class="vd-sum-row">
                    <span>Op. Gravada</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_totalgravada, 2) }}</span>
                </div>
                <div class="vd-sum-row">
                    <span>IGV ({{ $venta->venta_porcentaje_igv }}%)</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_totaligv, 2) }}</span>
                </div>
                @endif

                @if($venta->venta_totalexonerada > 0)
                <div class="vd-sum-row">
                    <span>Op. Exonerada</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_totalexonerada, 2) }}</span>
                </div>
                @endif

                @if($venta->venta_totalinafecta > 0)
                <div class="vd-sum-row">
                    <span>Op. Inafecta</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_totalinafecta, 2) }}</span>
                </div>
                @endif

                @if($venta->venta_totalgratuita > 0)
                <div class="vd-sum-row">
                    <span>Op. Gratuita</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_totalgratuita, 2) }}</span>
                </div>
                @endif

                @if($venta->venta_icbper > 0)
                <div class="vd-sum-row">
                    <span>ICBPER</span>
                    <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_icbper, 2) }}</span>
                </div>
                @endif

                @if($venta->venta_totaldescuento > 0)
                <div class="vd-sum-row" style="color:#dc2626">
                    <span>Descuento</span>
                    <span>− {{ $monedaSimbolo }} {{ number_format($venta->venta_totaldescuento, 2) }}</span>
                </div>
                @endif

                <div class="vd-total-block">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="vd-total-label">TOTAL</span>
                        <div class="text-end">
                            <div class="vd-total-amount">{{ number_format($venta->venta_total, 2) }}</div>
                            <div class="vd-total-currency">{{ $monedaSimbolo }} {{ $monedaCodigo }}</div>
                        </div>
                    </div>
                </div>

                @if(!$esCredito && $venta->venta_pago_cliente > 0)
                <div class="mt-3 pt-3 border-top">
                    <div class="vd-sum-row">
                        <span>Pagó con</span>
                        <span>{{ $monedaSimbolo }} {{ number_format($venta->venta_pago_cliente, 2) }}</span>
                    </div>
                    <div class="vd-sum-row">
                        <span>Vuelto</span>
                        <span class="fw-semibold text-success">{{ $monedaSimbolo }} {{ number_format($venta->venta_vuelto, 2) }}</span>
                    </div>
                </div>
                @endif

            </div>

            {{-- QR --}}
            <div class="vd-card-footer text-center">
                <img src="{{ asset($rutaQr) }}" alt="QR"
                     style="width:96px;height:96px;object-fit:contain;border-radius:8px;">
                <div class="text-muted mt-1" style="font-size:11px">Código QR del comprobante</div>
            </div>
        </div>

        {{-- Cuotas --}}
        @if($tieneCuotas)
            <div class="vd-card mb-4">
                <div class="vd-card-header d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fa-solid fa-calendar-days me-2 text-primary opacity-75"></i>Cuotas
                    </span>
                    <span class="badge bg-primary rounded-pill">{{ $cuotas->count() }}</span>
                </div>
                <div class="p-0">
                    <table class="table mb-0 vd-table">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width:36px">#</th>
                                <th>Monto</th>
                                <th>Vence</th>
                                <th class="text-center pe-4" style="width:90px">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotas as $i => $cu)
                            <tr>
                                <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $monedaSimbolo }} {{ number_format($cu->venta_cuota_importe, 2) }}</td>
                                <td class="text-muted" style="font-size:.84rem">
                                    {{ \Carbon\Carbon::parse($cu->venta_cuota_fecha)->format('d/m/Y') }}
                                </td>
                                <td class="text-center pe-4">
                                    @if((int)$cu->venta_cuota_pago === 1)
                                        <span class="vd-badge vd-badge-success" style="font-size:11px">Pagado</span>
                                    @else
                                        <span class="vd-badge vd-badge-danger" style="font-size:11px">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Acciones --}}
        <div class="d-grid gap-2">
            @can('detalle_venta.exportar')
            <a href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $ventaId]) }}"
               target="_blank" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-2"></i>Imprimir PDF
            </a>
            <a href="{{ route('Gestionventas.imprimir_ticketera_venta', ['venta_id' => $ventaId]) }}"
               target="_blank" class="btn btn-success">
                <i class="fa-solid fa-ticket me-2"></i>Imprimir Ticketera
            </a>
            @endcan
            <button type="button" class="btn btn-info text-white"
                    data-bs-toggle="modal" data-bs-target="#modalCorreoVenta">
                <i class="fa-solid fa-envelope me-2"></i>Enviar por correo
            </button>
            <a href="{{ route('Gestionventas.realizar_ventas') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-plus me-2"></i>Nueva venta
            </a>
        </div>

    </div>{{-- /col lateral --}}

</div>{{-- /row --}}

    <style>
    .vd-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; }
    .vd-card-warning { border-color:#fde68a; }
    .vd-card-header {
        display:flex; align-items:center;
        padding:.75rem 1.25rem;
        background:#f8f9fa;
        border-bottom:1px solid #e9ecef;
        font-weight:600; font-size:.82rem;
        text-transform:uppercase; letter-spacing:.05em; color:#6b7280;
    }
    .vd-card-body { padding:1.25rem; }
    .vd-card-footer {
        padding:.75rem 1.25rem;
        background:#f8f9fa;
        border-top:1px solid #e9ecef;
    }
    .vd-banner {
        padding:1.1rem 1.4rem;
        background:#f0fdf4;
        border:1px solid #bbf7d0;
        border-radius:12px;
    }
    .vd-banner-danger{
        padding:1.1rem 1.4rem;
        background: #fdf0f3;
        border:1px solid #f7bbc2;
        border-radius:12px;
    }
    .vd-check-circle {
        width:38px; height:38px; border-radius:50%;
        background:#22c55e; color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-size:1rem; flex-shrink:0;
    }
    .vd-badge {
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.3rem .75rem; border-radius:20px;
        font-size:.78rem; font-weight:600; letter-spacing:.01em;
        border:1px solid transparent;
    }
    .vd-badge-success  { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
    .vd-badge-danger   { background:#fff1f2; color:#dc2626; border-color:#fecaca; }
    .vd-badge-warning  { background:#fffbeb; color:#b45309; border-color:#fde68a; }
    .vd-badge-secondary{ background:#f3f4f6; color:#374151; border-color:#d1d5db; }
    .vd-dot { width:6px; height:6px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .vd-order-num { font-size:1.15rem; font-weight:800; color:#1e293b; letter-spacing:.02em; }
    .vd-divider { border:none; border-top:1px dashed #e2e8f0; margin:1rem 0; }
    .vd-field { display:flex; flex-direction:column; gap:.15rem; }
    .vd-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; }
    .vd-value { font-size:.9rem; font-weight:500; color:#111827; }
    .vd-table { font-size:.88rem; }
    .vd-table thead tr { background:#f8f9fa; }
    .vd-table thead th {
        padding:.65rem 0; font-size:11px; font-weight:600;
        text-transform:uppercase; letter-spacing:.06em; color:#9ca3af;
        border-bottom:1px solid #e9ecef; border-top:none;
    }
    .vd-table tbody tr { transition:background .1s; }
    .vd-table tbody tr:hover { background:#fafbff; }
    .vd-table tbody td {
        padding:.7rem 0; vertical-align:middle;
        border-bottom:1px solid #f3f4f6; color:#374151;
    }
    .vd-table tbody tr:last-child td { border-bottom:none; }
    .vd-qty-badge {
        display:inline-block; padding:.15rem .55rem;
        background:#f3f4f6; border:1px solid #e5e7eb;
        border-radius:6px; font-size:.8rem; font-weight:600; color:#374151;
    }
    .vd-sum-row {
        display:flex; justify-content:space-between; align-items:center;
        padding:.3rem 0; font-size:.88rem; color:#374151;
    }
    .vd-sum-row span:first-child { color:#6b7280; }
    .vd-sum-row span:last-child  { font-weight:600; }
    .vd-total-block { margin-top:.9rem; padding-top:.9rem; border-top:2px solid #e9ecef; }
    .vd-total-label  { font-weight:800; font-size:.95rem; color:#374151; letter-spacing:.04em; }
    .vd-total-amount { font-size:2rem; font-weight:800; color:#dc2626; line-height:1; }
    .vd-total-currency{ font-size:.78rem; font-weight:600; color:#9ca3af; margin-top:.1rem; }
    </style>

    @script
        <script>
            $wire.on('correoEnviado', ({ mensaje }) => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalCorreoVenta'));
                if (m) m.hide();
                if (typeof respuesta === 'function') respuesta(mensaje, 'success');
            });

            $wire.on('notificar', ({ mensaje, tipo }) => {
                if (typeof respuesta === 'function') respuesta(mensaje, tipo);
            });

            document.getElementById('modalCorreoVenta').addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });
        </script>
    @endscript

</div>
