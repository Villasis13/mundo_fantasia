@if(count($filas) === 0)
    <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-inbox fa-2x mb-2"></i>
        <p class="mb-0">No hay registros.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:0.81rem;">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Serie - Número</th>
                    <th>Cliente / RUC-DNI</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Estado SUNAT</th>
                    <th class="text-center">Tipo envío</th>
                    <th>Respuesta SUNAT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $i => $v)
                    @php
                        if ($v->anulado_sunat) {
                            $rowClass  = 'table-warning';
                            $badgeHtml = '<span class="badge bg-warning text-dark">Anulado</span>';
                        } elseif ($v->venta_estado_sunat) {
                            $rowClass  = '';
                            $badgeHtml = '<span class="badge bg-success">Declarado</span>';
                        } else {
                            $rowClass  = 'table-danger bg-opacity-10';
                            $badgeHtml = '<span class="badge bg-danger">Pendiente</span>';
                        }
                        $tipoBadge = match($v->venta_tipo) {
                            '01' => '<span class="badge bg-primary">Factura</span>',
                            '03' => '<span class="badge bg-info text-dark">Boleta</span>',
                            '07' => '<span class="badge bg-warning text-dark">N.Crédito</span>',
                            '08' => '<span class="badge bg-secondary">N.Débito</span>',
                            default => '<span class="badge bg-light text-dark">'.$v->venta_tipo.'</span>',
                        };
                        $tipoEnvio = match((int)($v->venta_tipo_envio ?? 0)) {
                            1 => 'Directo',
                            2 => 'Res. diario',
                            default => '—',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y') }}</td>
                        <td>{!! $tipoBadge !!}</td>
                        <td class="fw-semibold">{{ $v->venta_serie }}-{{ str_pad($v->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width:160px;">{{ $v->cliente_nombre }}</div>
                            <small class="text-muted">{{ $v->cliente_numero }}</small>
                        </td>
                        <td class="text-end fw-semibold">{{ $v->simbolo }} {{ number_format($v->venta_total, 2) }}</td>
                        <td class="text-center">{!! $badgeHtml !!}</td>
                        <td class="text-center">
                            <small class="text-muted">{{ $tipoEnvio }}</small>
                            @if($v->venta_fecha_envio)
                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($v->venta_fecha_envio)->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($v->venta_respuesta_sunat)
                                <small class="{{ $v->venta_estado_sunat && !$v->anulado_sunat ? 'text-success' : 'text-warning' }}"
                                       title="{{ $v->venta_respuesta_sunat }}">
                                    {{ \Illuminate\Support\Str::limit($v->venta_respuesta_sunat, 55) }}
                                </small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
