<div>
    {{-- ══════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Caja                          --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalCaja" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-cash-register me-2"></i>
                        {{ $modoEdicion ? 'Editar Caja' : 'Nueva Caja' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- Nombre de la caja --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Nombre de la Caja <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="cajaNombre"
                                   class="form-control @error('cajaNombre') is-invalid @enderror"
                                   placeholder="Ej: Caja 1, Caja Principal...">
                            @error('cajaNombre')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Nombre de la ticketera --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Nombre de la Ticketera <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="cajaImpresora"
                                   class="form-control @error('cajaImpresora') is-invalid @enderror"
                                   placeholder="Ej: Ticketera, Epson TM-T20...">
                            @error('cajaImpresora')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Aviso series automáticas --}}
                        @if(!$modoEdicion)
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">
                                    <i class="fa-solid fa-circle-info me-2"></i>
                                    Al crear la caja se generarán automáticamente las series para
                                    <strong>Factura</strong>, <strong>Boleta</strong>, <strong>Nota de Crédito</strong> y <strong>Nota de Débito</strong>.
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                <div class="modal-footer justify-content-between border-top px-4">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                            wire:click="limpiarFormulario">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-success text-white fw-semibold px-4"
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            wire:target="guardar">
                        <span wire:loading.remove wire:target="guardar">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                        </span>
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Guardando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{--  MODAL — Deshabilitar Caja                            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarCaja" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                        Deshabilitar Caja
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-cash-register fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-0">¿Estás seguro de que deseas deshabilitar esta caja?</p>
                    <small class="text-muted">Puedes volver a habilitarla cuando lo necesites.</small>
                </div>

                <div class="modal-footer justify-content-between px-4">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-danger fw-semibold"
                            wire:click="eliminar"
                            wire:loading.attr="disabled"
                            wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-ban me-1"></i> Sí, deshabilitar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Deshabilitando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{--  MODAL — Gestión de Series                            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSeries" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-list-ol me-2"></i>
                        Series — {{ $nombreCajaSeries }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">

                    <div class="alert alert-warning py-2 small mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Modifica con cuidado. El cambio de serie afecta la numeración de futuros comprobantes.
                    </div>

                    {{-- Tabla de series editables --}}
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                            <tr class="encabezado_tabla_color text-center">
                                <th>Tipo Comprobante</th>
                                <th>Serie</th>
                                <th>Correlativo Actual</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($series as $i => $s)
                                <tr>
                                    <td class="text-center fw-semibold">
                                            <span class="badge bg-secondary fs-6">
                                                {{ $this->tipoCompLabel($s['tipocomp']) }}
                                            </span>
                                    </td>
                                    <td>
                                        <input type="text"
                                               wire:model="series.{{ $i }}.serie"
                                               class="form-control text-uppercase @error('series.'.$i.'.serie') is-invalid @enderror"
                                               placeholder="Ej: F001"
                                               maxlength="10"
                                               style="text-transform:uppercase;">
                                        @error('series.'.$i.'.serie')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number"
                                               wire:model="series.{{ $i }}.correlativo"
                                               class="form-control @error('series.'.$i.'.correlativo') is-invalid @enderror"
                                               min="0"
                                               placeholder="0">
                                        @error('series.'.$i.'.correlativo')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer justify-content-between border-top px-4">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-success text-white fw-semibold px-4"
                            wire:click="guardarSeries"
                            wire:loading.attr="disabled"
                            wire:target="guardarSeries">
                        <span wire:loading.remove wire:target="guardarSeries">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Series
                        </span>
                        <span wire:loading wire:target="guardarSeries">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Guardando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{--  CARD PRINCIPAL                                        --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    @if($idSucursal > 0 || $idTienda > 0)
                        <a href="javascript:history.back()"
                           class="text-decoration-none text-muted d-inline-flex align-items-center gap-1 mb-2"
                           style="font-size:.78rem;">
                            <i class="fa-solid fa-arrow-left" style="font-size:.65rem;"></i>
                            Volver a {{ $idTienda > 0 ? 'Sedes' : 'Sucursales' }}
                        </a>
                    @endif
                    <h5 class="mb-1 fw-bold d-flex align-items-center gap-2 flex-wrap">
                        <i class="fa-solid fa-cash-register me-2 text-primary"></i>Cajas
                        @if($idSucursal > 0 || $idTienda > 0)
                            @php
                                $tipo = $idTienda > 0 ? $tipoTienda : $tipoSucursal;
                                $tipoInfo = match($tipo) {
                                    1 => ['label' => 'Tienda',   'class' => 'bg-success'],
                                    2 => ['label' => 'Sucursal', 'class' => 'bg-primary'],
                                    3 => ['label' => 'Almacén',  'class' => 'bg-warning text-dark'],
                                    default => null,
                                };
                            @endphp
                            @if($tipoInfo)
                                <span class="badge {{ $tipoInfo['class'] }} fw-normal fs-7">
                                    {{ $tipoInfo['label'] }}
                                </span>
                            @endif
                        @endif
                    </h5>
                    <small class="text-muted">
                        @if($idTienda > 0)
                            Gestiona las cajas de <strong>{{ $nombreTienda }}</strong>.
                        @elseif($idSucursal > 0)
                            Gestiona las cajas de la sucursal <strong>{{ $nombreSucursal }}</strong>.
                        @else
                            Gestiona las cajas y sus series de comprobantes.
                        @endif
                    </small>
                    @if($tipoSucursal === 3 || $tipoTienda === 3)
                        <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small d-flex align-items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                            Los almacenes no pueden tener cajas registradas.
                        </div>
                    @endif
                </div>
                @can('gestion_de_cajas.crear')
                @if($tipoSucursal !== 3 && $tipoTienda !== 3)
                <button class="btn btn-primary fw-semibold"
                        wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Caja
                </button>
                @endif
                @endcan
            </div>

            {{-- Buscador + selector de registros por página --}}
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">

                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width: auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="text-muted small mb-0 text-nowrap">registros</label>
                </div>

                <div class="input-group" style="max-width: 360px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar por nombre o ticketera...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary"
                                type="button"
                                wire:click="$set('buscar', '')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>

            </div>
        </div>

        <div class="card-body p-0">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0" role="alert">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0" role="alert">
                    <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Tabla --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr class="encabezado_tabla_color text-center">
                        <th class="ps-3">#</th>

                        <th style="cursor:pointer;" wire:click="ordenar('caja_numero_nombre')">
                            Nombre
                            @if($ordenColumna === 'caja_numero_nombre')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>

                        <th style="cursor:pointer;" wire:click="ordenar('caja_numero_impresora')">
                            Ticketera
                            @if($ordenColumna === 'caja_numero_impresora')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>

                        <th>Series</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($cajas->count())
                        @foreach($cajas as $index => $caja)
                            @php
                                $seriesCaja = DB::table('serie')
                                    ->where('id_caja_numero', $caja->id_caja_numero)
                                    ->whereIn('tipocomp', ['01', '03', '07', '08'])
                                    ->orderBy('tipocomp')
                                    ->get();
                            @endphp
                            <tr @if($caja->caja_numero_estado == 0) style="background:rgba(220,53,69,0.18);" @endif>
                                <td class="ps-3 text-center text-muted">
                                    {{ $cajas->firstItem() + $index }}
                                </td>
                                <td class="fw-semibold">
                                    {{ $caja->caja_numero_nombre }}
                                </td>
                                <td class="text-muted">
                                    {{ $caja->caja_numero_impresora }}
                                </td>
                                <td class="text-center">
                                    @foreach($seriesCaja as $s)
                                        <span class="badge bg-light text-dark border me-1">
                                            {{ $s->serie }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $caja->caja_numero_estado == 1 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $caja->caja_numero_estado == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @can('gestion_de_cajas.actualizar')
                                    <button class="btn btn-sm btn-warning m-1"
                                            wire:click="abrirModalSeries({{ $caja->id_caja_numero }})"
                                            title="Ver y editar series">
                                        <i class="fa-solid fa-list-ol text-white"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary m-1"
                                            wire:click="abrirModalEditar({{ $caja->id_caja_numero }})"
                                            title="Editar caja">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endcan
                                    @can('gestion_de_cajas.cambiar_estado')
                                    @if($caja->caja_numero_estado == 1)
                                        <button class="btn btn-sm btn-danger m-1"
                                                wire:click="confirmarEliminar({{ $caja->id_caja_numero }})"
                                                title="Deshabilitar caja">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-success m-1"
                                                wire:click="habilitarCaja({{ $caja->id_caja_numero }})"
                                                title="Habilitar caja">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-cash-register fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron cajas registradas.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            {{-- Paginación + info --}}
            @if($cajas->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $cajas->firstItem() }} - {{ $cajas->lastItem() }}
                        de {{ $cajas->total() }} registros
                    </small>
                    {{ $cajas->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    {{-- Loader --}}
    <div wire:loading wire:target="buscar, porPagina, ordenar, habilitarCaja, eliminar">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        new bootstrap.Modal(document.getElementById('modalCaja')).show();
    });

    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCaja'));
        if (modal) modal.hide();
    });

    $wire.on('abrirModalEliminar', () => {
        new bootstrap.Modal(document.getElementById('modalEliminarCaja')).show();
    });

    $wire.on('cerrarModalEliminar', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarCaja'));
        if (modal) modal.hide();
    });

    $wire.on('abrirModalSeries', () => {
        new bootstrap.Modal(document.getElementById('modalSeries')).show();
    });

    $wire.on('cerrarModalSeries', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSeries'));
        if (modal) modal.hide();
    });
</script>
@endscript
