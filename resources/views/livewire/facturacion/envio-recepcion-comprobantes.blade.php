<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-paper-plane me-2 text-primary"></i>Envío y Recepción de Comprobantes de Pago Electrónicos (CPE) SUNAT
        </h5>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroDesde">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroHasta">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <input type="text" class="form-control form-control-sm" wire:model="filtroCliente" placeholder="Nombre o documento">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" wire:click="buscar"
                            wire:loading.attr="disabled" wire:target="buscar">
                        <span wire:loading.remove wire:target="buscar"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</span>
                        <span wire:loading wire:target="buscar"><span class="spinner-border spinner-border-sm me-1"></span>Buscando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small">{{ $ventas->total() }} comprobante(s)</span>
            <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                <option value="20">20</option><option value="50">50</option><option value="100">100</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Hora</th>
                            <th>Serie y Correlativo</th>
                            <th class="text-end">Total</th>
                            <th>Cliente</th>
                            <th class="text-center">XML</th>
                            <th class="text-center">Envío</th>
                            <th class="text-center">Respuesta SUNAT</th>
                            <th>Observación de Respuesta</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $vta)
                            @php
                                $clienteNom = $vta->id_tipo_documento == 4 ? ($vta->cliente_razonsocial ?: $vta->cliente_nombre) : ($vta->cliente_nombre ?: $vta->cliente_razonsocial);
                                $fechaObj = \Carbon\Carbon::parse($vta->venta_fecha);
                            @endphp
                            <tr>
                                <td class="ps-3">{{ $fechaObj->format('d/m/Y') }}</td>
                                <td>{{ $fechaObj->format('H:i:s') }}</td>
                                <td class="fw-semibold text-primary">{{ $vta->venta_serie }}-{{ str_pad($vta->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</td>
                                <td class="text-end fw-bold text-primary">S/ {{ number_format($vta->venta_total, 2) }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($clienteNom, 28) }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $vta->cliente_numero }}</div>
                                </td>
                                <td class="text-center">
                                    @if(!empty($vta->venta_rutaXML))
                                        <a href="{{ asset($vta->venta_rutaXML) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Descargar XML">
                                            <i class="fa-solid fa-file-code"></i>
                                        </a>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($vta->venta_fecha_envio))
                                        <span class="small">{{ \Carbon\Carbon::parse($vta->venta_fecha_envio)->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($vta->venta_estado_sunat == 1)
                                        <span class="badge bg-success">Aceptado</span>
                                    @endif
                                </td>
                                <td style="max-width:220px;font-size:.75rem;">{{ $vta->venta_respuesta_sunat }}</td>
                                <td class="text-center pe-3"></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">
                                <i class="fa fa-filter fa-2x d-block mb-2 opacity-50"></i>
                                {{ $buscado ? 'No se encontraron comprobantes con los filtros seleccionados.' : 'Aplique un filtro y presione Buscar.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ventas->hasPages())<div class="card-footer py-2">{{ $ventas->links() }}</div>@endif
    </div>
</div>
