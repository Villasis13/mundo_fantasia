<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Notas de Crédito o Débito
        </h5>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroDesde">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model="filtroHasta">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <input type="text" class="form-control form-control-sm" wire:model="filtroCliente" placeholder="Nombre o documento">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tipo de Nota</label>
                    <select class="form-select form-select-sm" wire:model="filtroTipo">
                        <option value="">Todas</option>
                        <option value="07">Nota de Crédito</option>
                        <option value="08">Nota de Débito</option>
                    </select>
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
            <span class="fw-semibold small">{{ $ventas->total() }} nota(s)</span>
            <select class="form-select form-select-sm w-auto" wire:model.live="porPagina">
                <option value="20">20</option><option value="50">50</option><option value="100">100</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha de Emisión</th>
                            <th>Tipo de Nota</th>
                            <th>Serie y Correlativo</th>
                            <th>Comprobante Afectado</th>
                            <th>Cliente</th>
                            <th>Motivo</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $vta)
                            @php
                                $tipoLbl = ['07'=>'Nota de Crédito','08'=>'Nota de Débito'][$vta->venta_tipo] ?? $vta->venta_tipo;
                                $tipoBadge = $vta->venta_tipo == '07' ? 'bg-danger' : 'bg-primary';
                                $clienteNom = $vta->id_tipo_documento == 4 ? ($vta->cliente_razonsocial ?: $vta->cliente_nombre) : ($vta->cliente_nombre ?: $vta->cliente_razonsocial);
                                $afectado = trim(($vta->serie_modificar ?? '') . (($vta->serie_modificar && $vta->correlativo_modificar) ? '-' : '') . ($vta->correlativo_modificar ?? ''));
                                $motivo = $vta->venta_tipo == '07'
                                    ? ($motivosNC[$vta->venta_codigo_motivo_nota] ?? $vta->venta_codigo_motivo_nota)
                                    : ($motivosND[$vta->venta_codigo_motivo_nota] ?? $vta->venta_codigo_motivo_nota);
                            @endphp
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($vta->venta_fecha)->format('d/m/Y H:i') }}</td>
                                <td><span class="badge {{ $tipoBadge }}">{{ $tipoLbl }}</span></td>
                                <td class="fw-semibold text-primary">{{ $vta->venta_serie }}-{{ str_pad($vta->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</td>
                                <td class="fw-semibold">{{ $afectado ?: '—' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($clienteNom, 28) }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $vta->cliente_numero }}</div>
                                </td>
                                <td style="max-width:200px;font-size:.78rem;">{{ $vta->venta_codigo_motivo_nota ? $vta->venta_codigo_motivo_nota.' - '.$motivo : '—' }}</td>
                                <td class="text-end fw-bold text-primary">S/ {{ number_format($vta->venta_total, 2) }}</td>
                                <td class="text-center">
                                    @if($vta->venta_estado_sunat == 1)
                                        <span class="badge bg-success">Aceptado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('Gestionventas.imprimir_ticket_escpos_pdf', ['venta_id' => $vta->id_venta]) }}"
                                       target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="fa fa-filter fa-2x d-block mb-2 opacity-50"></i>
                                {{ $buscado ? 'No se encontraron notas con los filtros seleccionados.' : 'Aplique un filtro y presione Buscar.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ventas->hasPages())<div class="card-footer py-2">{{ $ventas->links() }}</div>@endif
    </div>
</div>
