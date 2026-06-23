<div>
    {{-- ── Alertas ─────────────────────────────────────────────── --}}
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

    {{-- ── Modal confirmación consulta ticket ─────────────────── --}}
    <div class="modal fade" id="modalConfirmacionResumenDiario" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-circle-question text-info me-2"></i>
                        Consultar Resumen Diario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-6 mb-0">{{ $mensajeConfirmacion ?: '¿Confirma la acción seleccionada?' }}</p>
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
                            <i class="fa-solid fa-cloud-arrow-down me-1"></i> Sí, consultar
                        </span>
                        <span wire:loading wire:target="ejecutarConfirmacion">
                            <span class="spinner-border spinner-border-sm me-1"></span> Consultando SUNAT...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ── Selector empresa (solo SuperAdmin, sin sucursal) ─────── --}}
    @if($esSuperAdmin || $esAdmin)
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar por
                    </span>
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
                </div>
            </div>
        </div>
    @endif

    {{-- ── Card principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Resúmenes diarios enviados</h5>
                <small class="text-muted">Consulta y revisa los resúmenes diarios enviados a SUNAT.</small>
            </div>

            @if($esSuperAdmin && !$idEmpresaActiva)
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Selecciona primero una empresa para visualizar los resúmenes diarios.
                </div>
            @else
                <div class="row align-items-end g-2">
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">Desde:</label>
                        <input type="date" wire:model="fechaInicio" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">Hasta:</label>
                        <input type="date" wire:model="fechaFinal" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <button class="btn btn-success btn-sm"
                                wire:click="listar()"
                                wire:loading.attr="disabled"
                                wire:target="listar">
                            <span wire:loading wire:target="listar">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                            </span>
                            <i class="fa fa-search me-1" wire:loading.remove wire:target="listar"></i>
                            Buscar Datos
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="dataTable_resumen_diario">
                    <thead>
                    <tr class="encabezado_tabla_color">
                        <th>#</th>
                        <th>Fecha de Emisión</th>
                        <th>Fecha de Comprobantes</th>
                        <th>Serie y Correlativo</th>
                        <th>XML</th>
                        <th>Estado XML</th>
                        <th>CDR</th>
                        <th>Estado Sunat</th>
                        <th>Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($resumenes as $index => $al)
                        @php
                            $mensaje = $al->envio_resumen_estadosunat ?? 'Sin Enviar a Sunat';
                            $estilo_mensaje = $al->envio_resumen_estadosunat === null
                                ? 'color:red;font-size:14px;'
                                : 'color:green;font-size:14px;';

                            $mensaje_consulta = $al->envio_resumen_estadosunat_consulta ?? '';
                            $estilo_mensaje_consulta = $al->envio_resumen_estadosunat_consulta === null
                                ? ''
                                : 'color:green;font-size:14px;';
                        @endphp
                        <tr style="text-align:center;">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->envio_sunat_datetime)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->envio_resumen_fecha)->format('d-m-Y') }}</td>
                            <td>{{ $al->envio_resumen_serie . '-' . $al->envio_resumen_correlativo }}</td>

                            @if(file_exists($al->envio_resumen_nombreXML))
                                <td>
                                    <a target="_blank"
                                       href="{{ asset($al->envio_resumen_nombreXML) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar XML">
                                        <i class="fa fa-file-text text-primary"></i>
                                    </a>
                                </td>
                            @else
                                <td>--</td>
                            @endif

                            <td style="{{ $estilo_mensaje }}">{{ $mensaje }}</td>

                            @if(file_exists($al->envio_resumen_nombreCDR))
                                <td>
                                    <a target="_blank"
                                       href="{{ asset($al->envio_resumen_nombreCDR) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar CDR">
                                        <i class="fa fa-file text-success"></i>
                                    </a>
                                </td>
                            @else
                                <td>--</td>
                            @endif

                            <td style="{{ $estilo_mensaje_consulta }}">{{ $mensaje_consulta }}</td>

                            <td>
                                @can('historial_resumenes_diarios.crear')
                                <button class="btn btn-sm btn-success m-1"
                                        wire:click="confirmarConsultaTicket({{ $al->id_envio_resumen }})"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmarConsultaTicket({{ $al->id_envio_resumen }})"
                                        data-bs-tooltip="tooltip" data-bs-placement="top"
                                        data-bs-title="Consultar Resumen Diario">
                                    <i class="fa fa-cloud-download text-white"></i>
                                </button>
                                @endcan
                                <a target="_blank"
                                   class="btn btn-sm btn-primary m-1"
                                   data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Ver Detalle"
                                   href="{{ route('facturacion.detalle_resumen', $al->id_envio_resumen) }}">
                                    <i class="fa fa-eye text-white"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar Datos</strong> para ver los resúmenes.
                                @else
                                    No se encontraron resúmenes con los filtros aplicados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="listar">
        <x-loader />
    </div>
</div>
@script
    <script>
        $wire.on('abrirModalResumenDiario', () => {
            const el    = document.getElementById('modalConfirmacionResumenDiario');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();
        });

        $wire.on('cerrarModalResumenDiario', () => {
            const el    = document.getElementById('modalConfirmacionResumenDiario');
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        });

        document.getElementById('modalConfirmacionResumenDiario')
            .addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });
    </script>
@endscript
