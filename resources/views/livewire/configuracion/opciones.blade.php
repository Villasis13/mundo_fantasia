<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Opción                             --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalOpcion" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Opción' : 'Nueva Opción' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos de la opción seleccionada.' : 'Completa los campos para registrar una nueva opción.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="opcionNombre"
                                   class="form-control @error('opcionNombre') is-invalid @enderror"
                                   placeholder="Ej: Reportes, Configuración...">
                            @error('opcionNombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-8">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Función <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="opcionFuncion"
                                   class="form-control @error('opcionFuncion') is-invalid @enderror"
                                   placeholder="Ej: ver-reportes, configuracion...">
                            @error('opcionFuncion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fa-solid fa-circle-info me-1 text-info"></i>
                                Nombre único — se usa como clave de permiso.
                            </div>
                        </div>

                        <div class="col-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Orden <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   wire:model="opcionOrden"
                                   class="form-control @error('opcionOrden') is-invalid @enderror"
                                   placeholder="1" min="1" max="999">
                            @error('opcionOrden')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 pt-1">
                            <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                                <input class="form-check-input ms-0 flex-shrink-0"
                                       type="checkbox"
                                       role="switch"
                                       id="switchMostrarOp"
                                       wire:model="opcionMostrar"
                                       style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                <label class="form-check-label fw-semibold small text-secondary mb-0"
                                       for="switchMostrarOp"
                                       style="cursor:pointer;">
                                    {{ $opcionMostrar ? 'Visible en navegación' : 'Oculto de navegación' }}
                                </label>
                                <span class="badge {{ $opcionMostrar ? 'bg-success' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-{{ $opcionMostrar ? 'eye' : 'eye-slash' }} me-1"></i>
                                    {{ $opcionMostrar ? 'Visible' : 'Oculto' }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-between">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" wire:click="limpiarFormulario">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-primary fw-semibold px-4"
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            wire:target="guardar">
                        <span wire:loading.remove wire:target="guardar">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                        </span>
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            {{ $modoEdicion ? 'Actualizando...' : 'Guardando...' }}
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Confirmar cambio de estado                        --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEstadoOpcion" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">

                {{-- Franja de color superior --}}
                <div style="height:5px;" class="{{ $nuevoEstado === 0 ? 'bg-danger' : 'bg-success' }}"></div>

                {{-- Botón cerrar --}}
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1;"></button>

                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3
                         {{ $nuevoEstado === 0 ? 'bg-danger' : 'bg-success' }}"
                         style="width:76px;height:76px;">
                        <i class="fa-solid fa-{{ $nuevoEstado === 0 ? 'ban' : 'circle-check' }} fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-size:1rem;">
                        {{ $nuevoEstado === 0 ? '¿Deshabilitar esta opción?' : '¿Habilitar esta opción?' }}
                    </h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">
                        {{ $nuevoEstado === 0 ? 'Dejará de aparecer en el sistema.' : 'Volverá a estar disponible.' }}
                    </p>
                </div>

                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-sm {{ $nuevoEstado === 0 ? 'btn-danger' : 'btn-success' }} fw-semibold px-4"
                            wire:click="cambiarEstado"
                            wire:loading.attr="disabled"
                            wire:target="cambiarEstado">
                        <span wire:loading.remove wire:target="cambiarEstado">
                            <i class="fa-solid fa-{{ $nuevoEstado === 0 ? 'ban' : 'circle-check' }} me-1"></i>
                            {{ $nuevoEstado === 0 ? 'Sí, deshabilitar' : 'Sí, habilitar' }}
                        </span>
                        <span wire:loading wire:target="cambiarEstado">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Procesando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Permisos de acción                                --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalPermisosOpcion" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-key me-2 text-primary"></i>
                        Permisos — <span class="text-muted fw-normal">{{ $nombreOpcionPermisos }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">Agrega los permisos de acción para esta opción.</small>

                    @if(session('successPermiso'))
                        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mt-3 mb-0" role="alert">
                            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                            <span>{{ session('successPermiso') }}</span>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('errorPermiso'))
                        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mt-3 mb-0" role="alert">
                            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
                            <span>{{ session('errorPermiso') }}</span>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row mt-3 g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nuevo permiso <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       wire:model="nuevoPermisoNombre"
                                       class="form-control @error('nuevoPermisoNombre') is-invalid @enderror"
                                       placeholder="Ej: gestion_de_menu.listar"
                                       wire:keydown.enter="agregarPermiso">
                                <button class="btn btn-primary fw-semibold"
                                        type="button"
                                        wire:click="agregarPermiso"
                                        wire:loading.attr="disabled"
                                        wire:target="agregarPermiso">
                                    <span wire:loading.remove wire:target="agregarPermiso">
                                        <i class="fa-solid fa-plus me-1"></i> Agregar
                                    </span>
                                    <span wire:loading wire:target="agregarPermiso">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                @error('nuevoPermisoNombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mt-3" style="max-height:260px;overflow-y:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="sticky-top bg-white">
                                <tr class="encabezado_tabla_color">
                                    <th class="ps-3" style="width:50px;">#</th>
                                    <th>Permiso</th>
                                    <th style="width:60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permisosAccion as $i => $pa)
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="small text-secondary">{{ $pa['name'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @unless($pa['esPredefinido'])
                                                <button class="btn btn-sm btn-danger"
                                                        wire:click="eliminarPermiso({{ $pa['id'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="eliminarPermiso({{ $pa['id'] }})"
                                                        title="Eliminar permiso">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-key fa-2x d-block mb-2 opacity-25"></i>
                                            Aún no hay permisos de acción registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-end">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  CARD PRINCIPAL                                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <a href="{{ url('configuracion/submenu/' . $idMenu) }}"
                       class="text-decoration-none text-muted d-inline-flex align-items-center gap-1 mb-2"
                       style="font-size:.78rem;">
                        <i class="fa-solid fa-arrow-left" style="font-size:.65rem;"></i>
                        Volver a Submenús
                    </a>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>Opciones
                    </h5>
                    <small class="text-muted">Gestiona las opciones del submenú <strong>{{ $nombreSubmenu }}</strong>.</small>
                </div>
                @can('gestion_opciones.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Opción
                </button>
                @endcan
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <label class="text-muted small mb-0 text-nowrap">registros</label>
                </div>

                <div class="input-group input-group-sm" style="max-width:300px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar opción...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm"
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

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th class="ps-3" style="width:50px;">
                                <span wire:click="ordenar('id_opciones')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='id_opciones' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='id_opciones' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:130px;" class="text-start">
                                <span wire:click="ordenar('opciones_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='opciones_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='opciones_nombre' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:130px;" class="text-start">
                                <span wire:click="ordenar('opciones_funcion')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Función
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='opciones_funcion' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='opciones_funcion' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:65px;">
                                <span wire:click="ordenar('opciones_orden')" role="button" class="d-inline-flex align-items-center justify-content-center gap-1">
                                    Orden
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='opciones_orden' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='opciones_orden' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:80px;">Visible</th>
                            <th style="width:110px;">Estado</th>
                            <th style="width:70px;">Permisos</th>
                            <th style="width:100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opciones as $index => $op)
                            <tr>
                                <td class="ps-3 text-center text-muted small fw-semibold">
                                    {{ $opciones->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $op->opciones_nombre }}</span>
                                </td>
                                <td>
                                    <code class="text-white small bg-primary bg-opacity-75 px-2 py-1 rounded">
                                        {{ $op->opciones_funcion }}
                                    </code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">{{ $op->opciones_orden }}</span>
                                </td>
                                <td class="text-center">
                                    @if($op->opciones_mostrar)
                                        <span class="badge bg-info"><i class="fa-solid fa-eye small"></i></span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fa-solid fa-eye-slash small"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($op->opciones_estado == 1)
                                        <span class="badge bg-success">
                                            <i class="fa-solid fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                                            Habilitado
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                                            Deshabilitado
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @can('gestion_opciones.crear')
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="abrirModalPermisos({{ $op->id_opciones }})"
                                            title="Gestionar permisos">
                                        <i class="fa-solid fa-key text-white"></i>
                                    </button>
                                    @endcan
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @can('gestion_opciones.actualizar')
                                        <button class="btn btn-sm btn-warning"
                                                wire:click="abrirModalEditar({{ $op->id_opciones }})"
                                                title="Editar">
                                            <i class="fa-solid fa-pencil text-white"></i>
                                        </button>
                                        @endcan
                                        @can('gestion_opciones.cambiar_estado')
                                        @if($op->opciones_estado == 1)
                                            <button class="btn btn-sm btn-danger"
                                                    wire:click="confirmarCambiarEstado({{ $op->id_opciones }}, 0)"
                                                    title="Deshabilitar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="confirmarCambiarEstado({{ $op->id_opciones }}, 1)"
                                                    title="Habilitar">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </button>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>
                                    @if($buscar)
                                        No se encontraron opciones que coincidan con <strong>"{{ $buscar }}"</strong>.
                                    @else
                                        No hay opciones registradas para este submenú.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($opciones->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $opciones->firstItem() }}–{{ $opciones->lastItem() }}
                        de {{ $opciones->total() }} registros
                    </small>
                    {{ $opciones->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, ordenar, abrirModalNuevo, abrirModalEditar, guardar, cambiarEstado, abrirModalPermisos, agregarPermiso">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOpcion')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalOpcion'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalEstado', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstadoOpcion')).show();
    });
    $wire.on('cerrarModalEstado', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstadoOpcion'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalPermisos', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPermisosOpcion')).show();
    });
    $wire.on('cerrarModalPermisos', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalPermisosOpcion'));
        if (modal) modal.hide();
    });
</script>
@endscript
