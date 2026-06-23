<div>
    {{-- ── Alertas ─────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Modal confirmación ───────────────────────────────── --}}
    <div class="modal fade" id="modalConfirmacionResumen" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                        Confirmar envío
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-6 mb-0">
                        {{ $mensajeConfirmacion ?: '¿Está seguro que desea continuar?' }}
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-success fw-semibold"
                            wire:click="ejecutarConfirmacion"
                            wire:loading.attr="disabled"
                            wire:target="ejecutarConfirmacion">
                        <span wire:loading.remove wire:target="ejecutarConfirmacion">
                            <i class="fa-solid fa-upload me-1"></i> Sí, enviar
                        </span>
                        <span wire:loading wire:target="ejecutarConfirmacion">
                            <span class="spinner-border spinner-border-sm me-1"></span> Enviando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Selector empresa / sucursal ─────────────────────── --}}
    @if($esSuperAdmin || $esAdmin)
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold">
                    <i class="fa-solid fa-filter me-1"></i>Filtrar por
                </span>
                @if($esSuperAdmin || $esAdmin)
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-building text-muted small"></i>
                    <select wire:model.live="empresaSeleccionada"
                            class="form-select form-select-sm" style="min-width:190px">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(count($sucursalesDisponibles) > 0 && ($empresaSeleccionada > 0 || $esAdmin))
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-code-branch text-muted small"></i>
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="min-width:190px">
                        <option value="0">Todas las sucursales</option>
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

    {{-- ── Card principal ───────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Historial de resumen diario</h5>
                <small class="text-muted">Consulta y filtra las boletas registradas.</small>
            </div>

            @if($esSuperAdmin && !$idEmpresaActiva)
            <div class="alert alert-warning py-2 mb-0">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                Selecciona primero una empresa para visualizar el resumen diario.
            </div>
            @else
            <div class="row align-items-end g-2">
                <div class="col-lg-3 col-md-4 col-sm-12">
                    <label class="form-label mb-1" for="tab2_fecha">Fecha:</label>
                    <input type="date" id="tab2_fecha" wire:model="fechaHoy"
                           class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm" wire:click="listar()"
                            wire:loading.attr="disabled" wire:target="listar">
                        <span wire:loading wire:target="listar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-magnifying-glass me-1" wire:loading.remove wire:target="listar"></i>
                        Buscar
                    </button>
                </div>
            </div>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th>Fecha de Emisión</th>
                            <th>Comprobante</th>
                            <th>Serie y Correlativo</th>
                            <th>Cliente</th>
                            <th>Forma de Pago</th>
                            <th>Tipo de Pago</th>
                            <th>Total</th>
                            <th>PDF</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($ventas as $index => $al)
                        @php
                            $anulado = (int) $al->anulado_sunat === 1;
                            $stylee  = $anulado ? 'text-align:center;text-decoration:line-through' : 'text-align:center';
                            $tipo_comprobante = match($al->venta_tipo) {
                                '03' => 'BOLETA',
                                '01' => 'FACTURA',
                                '07' => 'NOTA DE CRÉDITO',
                                '08' => 'NOTA DE DÉBITO',
                                default => '--',
                            };
                            $cliente = (int) $al->id_tipo_documento === 4
                                ? ($al->cliente_razonsocial ?? '')
                                : ($al->cliente_nombre ?? '');
                            $mensaje = $al->venta_respuesta_sunat ?? 'Sin Enviar a Sunat';
                        @endphp
                        <tr style="{{ $stylee }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->venta_fecha)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $tipo_comprobante }}</td>
                            <td class="fw-semibold">{{ $al->venta_serie }}-{{ $al->venta_correlativo }}</td>
                            <td>
                                <div class="small">{{ $al->cliente_numero }}</div>
                                <div class="fw-semibold small">{{ $cliente }}</div>
                            </td>
                            <td>{{ (int) $al->id_formas_pago === 1 ? 'CONTADO' : 'CREDITO' }}</td>
                            <td>
                                @foreach($al->tipo_pago as $d)
                                    <div>✅ {{ $d->tipo_pago_nombre }}</div>
                                @endforeach
                            </td>
                            <td>{{ $al->simbolo }} {{ $al->venta_total }}</td>
                            <td class="text-center">
                                <a target="_blank"
                                   href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $al->id_venta]) }}"
                                   title="Imprimir PDF">
                                    <i class="fa-regular fa-file-pdf text-danger fa-lg"></i>
                                </a>
                            </td>
                            <td style="color:red;font-size:14px">{{ $mensaje }}</td>
                            <td class="text-center">
                                <a target="_blank" title="Ver detalle"
                                   class="btn btn-sm btn-info"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $al->id_venta]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice el filtro de fecha y haga clic en <strong>Buscar</strong>.
                                @else
                                    No se encontraron boletas para la fecha indicada.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($buscar && count($ventas) > 0)
        <div class="card-footer text-end">
            @can('resumen_diario.crear')
            <button class="btn btn-success"
                    wire:click="confirmarEnvioResumen()">
                <i class="fa-solid fa-upload me-1"></i> Enviar Resumen Diario
            </button>
            @endcan
        </div>
        @endif
    </div>

    <div wire:loading wire:target="listar,enviarResumenSunat">
        <x-loader />
    </div>
</div>

@script
<script>
    $wire.on('abrirModalConfirmacionResumen', () => {
        const modalEl = document.getElementById('modalConfirmacionResumen');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });

    $wire.on('cerrarModalConfirmacionResumen', () => {
        const modalEl = document.getElementById('modalConfirmacionResumen');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    });

    document.getElementById('modalConfirmacionResumen')
        .addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
</script>
@endscript
