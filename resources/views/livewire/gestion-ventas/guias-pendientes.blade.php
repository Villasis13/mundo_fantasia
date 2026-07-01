<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-truck-fast me-2 text-primary"></i>Guías de Remisión Remitente</h5>
        </div>
        <a href="{{ route('Gestionventas.generar_guia') }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus me-1"></i>Generar guía</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Emisión desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Emisión hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente de enviar</option>
                        <option value="enviado">Enviado</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">N.º de guía</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroNumero" placeholder="Serie o número">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">N° Guía</th>
                            <th>Tipo</th>
                            <th>Emisión</th>
                            <th>Traslado</th>
                            <th>Destinatario</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guias as $g)
                        <tr>
                            <td class="ps-3 small fw-semibold text-primary">{{ $g->guia_numero ?: ($g->guia_serie.'-'.str_pad($g->guia_correlativo,8,'0',STR_PAD_LEFT)) }}</td>
                            <td class="small">{{ $g->guia_tipo === '31' ? 'Transportista' : 'Remitente' }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($g->guia_fecha_emision)->format('d/m/Y') }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($g->guia_fecha_traslado)->format('d/m/Y') }}</td>
                            <td class="small">
                                {{ $g->guia_dest_nombre }}
                                <div class="text-muted" style="font-size:.72rem">{{ $g->guia_dest_numero_doc }}</div>
                            </td>
                            <td class="text-center">
                                @if($g->guia_estado == 0)
                                    <span class="badge bg-danger">Anulado</span>
                                @elseif($g->guia_estado_sunat == 1)
                                    <span class="badge bg-success">Enviado</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente de enviar</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <a class="btn btn-sm btn-outline-secondary" title="PDF" target="_blank"
                                   href="{{ route('Gestionventas.imprimir_guia_pdf', ['id_guia' => $g->id_guia]) }}">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No hay guías registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($guias->hasPages())
        <div class="card-footer py-2">{{ $guias->links() }}</div>
        @endif
    </div>
</div>
