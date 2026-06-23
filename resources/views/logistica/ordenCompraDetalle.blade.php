@extends('layouts.plantilla')
@section('title', 'Detalle de Compra')
@section('content')

<div class="tab-content">
    @can($opciones[0]->opciones_funcion . '.opcion')
    <div id="vista_para_opciones_{{ $opciones[0]->id_opciones }}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">

        {{-- Barra superior --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('logistica.compras') }}" class="btn btn-sm btn-light border">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </a>
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                    Detalle de Compra
                </h5>
            </div>
            <a href="{{ route('logistica.compras_pdf') }}?ordenCompra={{ $orden_compra->id_orden_compra }}"
               class="btn btn-danger btn-sm" target="_blank">
                <i class="fa-solid fa-file-pdf me-1"></i> PDF
            </a>
        </div>

        @php
            $moneda    = $orden_compra->moneda ?? 'PEN';
            $sym       = $moneda === 'USD' ? '$' : ($moneda === 'EUR' ? '€' : 'S/');
            $monedaBadgeClass = $moneda === 'USD' ? 'bg-success' : ($moneda === 'EUR' ? 'bg-primary' : 'bg-secondary');
            $fueRecibida = $orden_compra->orden_compra_estado === 'recibido';
            $transportistasList = !empty($orden_compra->orden_compra_transportistas)
                ? json_decode($orden_compra->orden_compra_transportistas, true)
                : [];
        @endphp

        <div class="row g-3">

            {{-- ── Columna izquierda --}}
            <div class="col-lg-8">

                {{-- Identificación de la orden --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            <div>
                                <p class="text-muted small mb-1">N° de Compra</p>
                                <h5 class="fw-bold mb-0">
                                    {{ $orden_compra->orden_compra_numero }}
                                    @if($orden_compra->orden_compra_estado === 'anulado')
                                        <span class="badge bg-danger ms-1" style="font-size:.7rem;">ANULADA</span>
                                    @endif
                                </h5>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($orden_compra->orden_compra_fecha)->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            <span class="badge {{ $monedaBadgeClass }} fs-6 px-3">{{ $moneda }}</span>
                        </div>
                    </div>
                </div>

                {{-- Proveedor + Comprobante --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3">
                            <i class="fa-solid fa-file-lines me-1"></i> Comprobante
                        </h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <p class="text-muted small mb-1">Proveedor</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->proveedores_nombre }}</p>
                                <small class="text-muted">{{ $orden_compra->proveedores_numero_documento }}</small>
                            </div>
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Condición</p>
                                @php $cond = $orden_compra->condicion_pago ?? 'contado'; @endphp
                                @if($cond === 'contado')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal">
                                        <i class="fa-solid fa-money-bill-wave me-1" style="font-size:.65rem;"></i>Contado
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-normal">
                                        <i class="fa-solid fa-calendar-days me-1" style="font-size:.65rem;"></i>Crédito
                                    </span>
                                @endif
                            </div>
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Tipo de Pago</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->tipo_pago_nombre ?? '—' }}</p>
                            </div>

                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Tipo Doc.</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->orden_compra_tipo_doc ?? '—' }}</p>
                            </div>
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">N° Documento</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->orden_compra_numero_doc ?? '—' }}</p>
                            </div>
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Fecha Emisión</p>
                                <p class="fw-semibold mb-0">
                                    {{ $orden_compra->orden_compra_fecha_emision_doc
                                        ? \Carbon\Carbon::parse($orden_compra->orden_compra_fecha_emision_doc)->format('d/m/Y')
                                        : '—' }}
                                </p>
                            </div>
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Fecha Almacenamiento</p>
                                <p class="fw-semibold mb-0">
                                    {{ $orden_compra->fecha_almacenamiento
                                        ? \Carbon\Carbon::parse($orden_compra->fecha_almacenamiento)->format('d/m/Y')
                                        : '—' }}
                                </p>
                            </div>

                            @if($cond === 'credito')
                            <div class="col-sm-3">
                                <p class="text-muted small mb-1">Fecha Vencimiento</p>
                                <p class="fw-semibold mb-0 {{ $orden_compra->orden_compra_fecha_vencimiento && \Carbon\Carbon::parse($orden_compra->orden_compra_fecha_vencimiento)->isPast() ? 'text-danger' : '' }}">
                                    {{ $orden_compra->orden_compra_fecha_vencimiento
                                        ? \Carbon\Carbon::parse($orden_compra->orden_compra_fecha_vencimiento)->format('d/m/Y')
                                        : '—' }}
                                </p>
                            </div>
                            @endif

                            @if($orden_compra->orden_compra_guia_remitente)
                            <div class="col-sm-4">
                                <p class="text-muted small mb-1">Guía Remitente</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->orden_compra_guia_remitente }}</p>
                            </div>
                            @endif
                            @if($orden_compra->orden_compra_guia_transportista)
                            <div class="col-sm-4">
                                <p class="text-muted small mb-1">Guía Transportista</p>
                                <p class="fw-semibold mb-0">{{ $orden_compra->orden_compra_guia_transportista }}</p>
                            </div>
                            @endif

                            @if(!empty($transportistasList))
                            <div class="col-12">
                                <p class="text-muted small mb-1">Transportistas</p>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($transportistasList as $tr)
                                        @if(!empty($tr['nombre']))
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-normal px-2 py-1">
                                            <i class="fa-solid fa-truck me-1" style="font-size:.65rem;"></i>{{ $tr['nombre'] }}
                                        </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if($orden_compra->orden_compra_doc_adjuntado)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Documento adjunto</p>
                                <a href="{{ asset($orden_compra->orden_compra_doc_adjuntado) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-paperclip me-1"></i> Ver adjunto
                                </a>
                            </div>
                            @endif
                            @if($orden_compra->orden_compra_observacion)
                            <div class="col-12">
                                <p class="text-muted small mb-1">Observaciones</p>
                                <p class="mb-0">{{ $orden_compra->orden_compra_observacion }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabla de productos --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                            Productos
                        </h6>
                        @if($fueRecibida)
                        <span class="badge bg-success-subtle text-success border border-success-subtle small fw-semibold">
                            <i class="fa-solid fa-warehouse me-1"></i> Con recepción registrada
                        </span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th class="ps-3" style="width:36px;">#</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width:130px;">Presentación</th>
                                        <th class="text-center" style="width:75px;">C×Unid</th>
                                        <th class="text-center" style="width:90px;">Pedido</th>
                                        @if($fueRecibida)
                                        <th class="text-center" style="width:90px;">Recibido</th>
                                        <th class="text-center" style="width:80px;">Diferencia</th>
                                        @endif
                                        <th class="text-end" style="width:110px;">P. Compra</th>
                                        <th class="text-end" style="width:100px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detalle_orden_compra as $i => $det)
                                    @php
                                        $pedido   = (float) $det->detalle_compra_cantidad;
                                        $recibido = (float) ($det->detalle_compra_cantidad_recibida ?? 0);
                                        $diff     = $recibido - $pedido;
                                        $hayDiff  = $fueRecibida && $diff != 0;
                                        $presentacion = $det->presentacion ?? null;
                                        $cantXUnid    = $det->cantidad_x_unidad ?? null;
                                    @endphp
                                    <tr class="{{ $fueRecibida && $recibido > 0 ? ($diff < 0 ? 'table-danger' : ($diff > 0 ? 'table-info' : '')) : '' }}">
                                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="fw-semibold d-block">
                                                {{ $det->detalle_orden_nombre_producto ?? $det->pro_nombre }}
                                            </span>
                                            <small class="text-muted">{{ $det->pro_codigo }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($presentacion)
                                                <span class="badge bg-primary text-white px-2 py-1" style="font-size:.75rem;">
                                                    {{ $presentacion }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center small">
                                            {{ $cantXUnid ? number_format($cantXUnid, 2) : '—' }}
                                        </td>
                                        <td class="text-center">{{ number_format($pedido, 2) }}</td>
                                        @if($fueRecibida)
                                        <td class="text-center fw-semibold">
                                            {{ $recibido > 0 ? number_format($recibido, 2) : '—' }}
                                        </td>
                                        <td class="text-center fw-semibold small">
                                            @if($recibido > 0)
                                                @if($diff == 0)
                                                    <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                                @elseif($diff < 0)
                                                    <span class="text-danger">{{ number_format($diff, 2) }}</span>
                                                @else
                                                    <span class="text-primary">+{{ number_format($diff, 2) }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        @endif
                                        <td class="text-end">{{ $sym }} {{ number_format($det->detalle_compra_precio_compra, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ $sym }} {{ number_format($det->detalle_compra_total_pedido, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($fueRecibida)
                        <div class="px-3 py-2 border-top">
                            <small class="text-muted">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">Fila roja</span> Entrega incompleta &nbsp;
                                <span class="badge bg-info-subtle text-info border border-info-subtle me-2">Fila azul</span> Exceso en entrega &nbsp;
                                <span class="badge bg-light text-dark border me-2">Sin color</span> Cantidad exacta
                            </small>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- ── Columna derecha: destino + resumen --}}
            <div class="col-lg-4">

                {{-- Destino --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3">
                            <i class="fa-solid fa-building me-1"></i> Destino
                        </h6>
                        @if($orden_compra->empresa_nombrecomercial)
                        <p class="text-muted small mb-1">Empresa</p>
                        <p class="fw-semibold mb-2">{{ $orden_compra->empresa_nombrecomercial }}</p>
                        @endif
                        <p class="text-muted small mb-1">Sede</p>
                        <p class="fw-semibold mb-2">
                            @if($orden_compra->sucursal_nombre)
                                <span class="badge bg-primary">{{ $orden_compra->sucursal_nombre }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-75 fw-normal">Todos</span>
                            @endif
                        </p>
                        <p class="text-muted small mb-1">Solicitante</p>
                        <p class="fw-semibold mb-0">{{ $orden_compra->nombre_users }}</p>
                    </div>
                </div>

                {{-- Recepción --}}
                @if($orden_compra->orden_compra_estado === 'recibido')
                <div class="card border-0 shadow-sm mb-3 border-start border-success border-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3">
                            <i class="fa-solid fa-warehouse me-1 text-success"></i> Recepción
                        </h6>
                        <p class="text-muted small mb-1">Almacén</p>
                        <p class="fw-semibold mb-2">
                            {{ $orden_compra->almacen_nombre ?? '—' }}
                            @if($orden_compra->almacen_direccion)
                                <br><small class="text-muted fw-normal">
                                    <i class="fa-solid fa-location-dot me-1"></i>{{ $orden_compra->almacen_direccion }}
                                </small>
                            @endif
                        </p>
                        @if($orden_compra->orden_compra_fecha_recibida)
                        <p class="text-muted small mb-1">Fecha de recepción</p>
                        <p class="fw-semibold mb-2">
                            {{ \Carbon\Carbon::parse($orden_compra->orden_compra_fecha_recibida)->format('d/m/Y H:i') }}
                        </p>
                        @endif
                        @if($orden_compra->orden_compra_usuario_recibido)
                        <p class="text-muted small mb-1">Recepcionado por</p>
                        <p class="fw-semibold mb-0">{{ $orden_compra->orden_compra_usuario_recibido }}</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Motivo de anulación --}}
                @if($orden_compra->orden_compra_estado === 'anulado')
                <div class="card border-0 shadow-sm mb-3 border-start border-danger border-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-danger small text-uppercase mb-2">
                            <i class="fa-solid fa-ban me-1"></i> Motivo de Anulación
                        </h6>
                        @if($orden_compra->orden_compra_motivo_anulacion)
                            <p class="mb-0 small">{{ $orden_compra->orden_compra_motivo_anulacion }}</p>
                        @else
                            <p class="mb-0 small text-muted fst-italic">Sin motivo registrado.</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Resumen de costos --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa-solid fa-receipt me-2 text-primary"></i>
                            Resumen
                            <span class="badge {{ $monedaBadgeClass }} ms-2" style="font-size:.7rem;">{{ $moneda }}</span>
                        </h6>
                    </div>
                    <div class="card-body px-3 py-2">

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">
                                Subtotal
                                <span class="badge bg-secondary ms-1">{{ $detalle_orden_compra->count() }} ítem(s)</span>
                            </span>
                            <span class="fw-semibold">{{ $sym }} {{ number_format($subtotal, 2) }}</span>
                        </div>

                        @if($descuentoMonto > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">
                                Descuento
                                @if(($orden_compra->orden_compra_descuento_porcentaje ?? 0) > 0)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">
                                        {{ number_format($orden_compra->orden_compra_descuento_porcentaje, 2) }}%
                                    </span>
                                @endif
                            </span>
                            <span class="text-danger">− {{ $sym }} {{ number_format($descuentoMonto, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Subtotal Neto</span>
                            <span class="fw-semibold">{{ $sym }} {{ number_format($subtotalNeto, 2) }}</span>
                        </div>
                        @endif

                        @if($igvMonto > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">
                                IGV
                                @if(($orden_compra->orden_compra_igv_porcentaje ?? 0) > 0)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle ms-1">
                                        {{ number_format($orden_compra->orden_compra_igv_porcentaje, 2) }}%
                                    </span>
                                @endif
                            </span>
                            <span>{{ $sym }} {{ number_format($igvMonto, 2) }}</span>
                        </div>
                        @endif

                        @if($percepcionMonto > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">
                                Percepción IGV
                                @if(($orden_compra->orden_compra_percepcion_porcentaje ?? 0) > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">
                                        {{ number_format($orden_compra->orden_compra_percepcion_porcentaje, 2) }}%
                                    </span>
                                @endif
                            </span>
                            <span>{{ $sym }} {{ number_format($percepcionMonto, 2) }}</span>
                        </div>
                        @endif

                        @if($flete > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Flete</span>
                            <span>{{ $sym }} {{ number_format($flete, 2) }}</span>
                        </div>
                        @endif

                        @if($gastos > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Gastos operativos</span>
                            <span>{{ $sym }} {{ number_format($gastos, 2) }}</span>
                        </div>
                        @endif

                        <div class="d-flex justify-content-between pt-3">
                            <span class="fw-bold">TOTAL</span>
                            <span class="fw-bold fs-5 text-primary">{{ $sym }} {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
    @endcan
</div>

@endsection
