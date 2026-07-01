<div class="container-fluid py-3">

    {{-- Título fuera del card --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-paper-plane me-2 text-primary"></i>Envío y Recepción de Guías de Remisión Electrónicas SUNAT
        </h5>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Período inicio</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="periodoInicio">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Período fin</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="periodoFin">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Serie - Correlativo</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="filtroSerieCorr" placeholder="Ej. T001-00000001">
                </div>
            </div>
        </div>
    </div>

    {{-- Listado --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small">{{ $guias->total() }} guía(s)</span>
            @if(count($seleccionadas))
                <span class="badge bg-primary">{{ count($seleccionadas) }} seleccionada(s) para enviar</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Serie - Correlativo</th>
                            <th>Cliente</th>
                            <th class="text-center">XML</th>
                            <th class="text-center">Envío</th>
                            <th class="text-center">Respuesta SUNAT</th>
                            <th>Observación de respuesta</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guias as $g)
                            @php
                                $numero = $g->guia_numero ?: ($g->guia_serie.'-'.str_pad($g->guia_correlativo,8,'0',STR_PAD_LEFT));
                                $enviado = (int)$g->guia_estado_sunat === 1;
                                $tieneCdr = !empty($g->guia_ruta_cdr);
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($g->guia_fecha_emision)->format('d/m/Y') }}</td>
                                <td>{{ $g->created_at ? \Carbon\Carbon::parse($g->created_at)->format('H:i:s') : '—' }}</td>
                                <td class="fw-semibold text-primary">{{ $numero }}</td>
                                <td>
                                    <div>{{ \Illuminate\Support\Str::limit($g->guia_dest_nombre, 28) }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $g->guia_dest_numero_doc }}</div>
                                </td>
                                <td class="text-center">
                                    @if(!empty($g->guia_ruta_xml))
                                        <a href="{{ asset($g->guia_ruta_xml) }}" target="_blank" class="badge bg-info text-dark text-decoration-none"><i class="fa-solid fa-file-code me-1"></i>XML</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($enviado)
                                        <span class="badge bg-success">Enviado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($tieneCdr)
                                        <span class="badge bg-success">Aceptado</span>
                                    @elseif($enviado)
                                        <span class="badge bg-secondary">En proceso</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $g->guia_respuesta_sunat ?: '—' }}</small></td>
                                <td class="text-center">
                                    @if(!$enviado)
                                        <input type="checkbox" class="form-check-input" value="{{ $g->id_guia }}" wire:model="seleccionadas">
                                    @else
                                        <i class="fa-solid fa-check text-success"></i>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4"><i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>No se encontraron guías en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($guias->hasPages())<div class="card-footer py-2">{{ $guias->links() }}</div>@endif
    </div>

    <p class="text-muted small mt-2 mb-0">
        <i class="fa-solid fa-circle-info me-1"></i>Marca las guías y usa la acción de envío. <em>(El envío a SUNAT lo implementa el módulo de facturación electrónica.)</em>
    </p>
</div>
