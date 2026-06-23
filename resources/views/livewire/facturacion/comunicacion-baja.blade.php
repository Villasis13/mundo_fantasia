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

    {{-- ── Selector empresa / sucursal ──────────────────────────── --}}
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

    {{-- ── Card principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Comunicaciones de baja</h5>
                <small class="text-muted">Consulta y revisa los comprobantes anulados enviados a SUNAT.</small>
            </div>

            @if($esSuperAdmin && !$idEmpresaActiva)
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Selecciona primero una empresa para visualizar las comunicaciones de baja.
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
                <table class="table table-hover align-middle" id="dataTable_comunicacion_baja">
                    <thead>
                    <tr class="encabezado_tabla_color">
                        <th>#</th>
                        <th>Fecha de Emisión</th>
                        <th>Fecha de Comprobantes</th>
                        <th>Serie y Correlativo</th>
                        <th>Forma de Pago</th>
                        <th>XML</th>
                        <th>CDR</th>
                        <th>Estado Sunat</th>
                        <th>Datos del Comprobante Anulado</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($ventas as $index => $al)
                        @php
                            $mensaje_consulta = $al->venta_anulado_estado_sunat ?? '';
                            $estilo_mensaje_consulta = $al->venta_anulado_estado_sunat
                                ? 'color:green;font-size:14px;'
                                : '';
                        @endphp
                        <tr style="text-align:center;">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->venta_anulado_datetime)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->venta_anulado_fecha)->format('d-m-Y') }}</td>
                            <td>{{ $al->venta_anulado_serie . '-' . $al->venta_anulado_correlativo }}</td>
                            <td>{{ $al->id_formas_pago == 1 ? 'CONTADO' : 'CREDITO' }}</td>

                            @if(file_exists($al->venta_anulado_rutaXML))
                                <td>
                                    <a target="_blank"
                                       href="{{ asset($al->venta_anulado_rutaXML) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar XML">
                                        <i class="fa fa-file-text text-primary"></i>
                                    </a>
                                </td>
                            @else
                                <td>--</td>
                            @endif

                            @if(file_exists($al->venta_anulado_rutaCDR))
                                <td>
                                    <a target="_blank"
                                       href="{{ asset($al->venta_anulado_rutaCDR) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar CDR">
                                        <i class="fa fa-file text-success"></i>
                                    </a>
                                </td>
                            @else
                                <td>--</td>
                            @endif

                            <td style="{{ $estilo_mensaje_consulta }}">{{ $mensaje_consulta }}</td>

                            <td>
                                <a target="_blank"
                                   data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Ver detalle"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $al->id_venta]) }}">
                                    {{ $al->venta_serie . '-' . $al->venta_correlativo }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar Datos</strong> para ver las comunicaciones.
                                @else
                                    No se encontraron comunicaciones de baja con los filtros aplicados.
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
