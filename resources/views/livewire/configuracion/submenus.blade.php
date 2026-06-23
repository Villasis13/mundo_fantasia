<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Submenú                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSubmenu" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Submenú' : 'Nuevo Submenú' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos del submenú seleccionado.' : 'Completa los campos para registrar un nuevo submenú.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        {{-- Nombre --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="subMenuNombre"
                                   class="form-control @error('subMenuNombre') is-invalid @enderror"
                                   placeholder="Ej: Reportes, Configuración...">
                            @error('subMenuNombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Función --}}
                        <div class="col-8">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Función <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="subMenuFuncion"
                                   class="form-control @error('subMenuFuncion') is-invalid @enderror"
                                   placeholder="Ej: reportes, configuracion...">
                            @error('subMenuFuncion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fa-solid fa-circle-info me-1 text-info"></i>
                                Nombre único — se usa como clave de permiso.
                            </div>
                        </div>

                        {{-- Orden --}}
                        <div class="col-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Orden <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   wire:model="subMenuOrden"
                                   class="form-control @error('subMenuOrden') is-invalid @enderror"
                                   placeholder="1"
                                   min="1"
                                   max="999">
                            @error('subMenuOrden')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Visibilidad --}}
                        <div class="col-12 pt-1">
                            <div class="form-check form-switch d-flex align-items-center gap-3 ps-0">
                                <input class="form-check-input ms-0 flex-shrink-0"
                                       type="checkbox"
                                       role="switch"
                                       id="switchMostrarSub"
                                       wire:model="subMenuMostrar"
                                       style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                <label class="form-check-label fw-semibold small text-secondary mb-0"
                                       for="switchMostrarSub"
                                       style="cursor:pointer;">
                                    {{ $subMenuMostrar ? 'Visible en navegación' : 'Oculto de navegación' }}
                                </label>
                                <span class="badge {{ $subMenuMostrar ? 'bg-success' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-{{ $subMenuMostrar ? 'eye' : 'eye-slash' }} me-1"></i>
                                    {{ $subMenuMostrar ? 'Visible' : 'Oculto' }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-between">
                    <button type="button"
                            class="btn btn-light px-4"
                            data-bs-dismiss="modal"
                            wire:click="limpiarFormulario">
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
    <div class="modal fade" id="modalEstadoSubmenu" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
                        {{ $nuevoEstado === 0 ? '¿Deshabilitar este submenú?' : '¿Habilitar este submenú?' }}
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
    {{--  CARD PRINCIPAL                                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <a href="{{ url('configuracion/menus') }}"
                       class="text-decoration-none text-muted d-inline-flex align-items-center gap-1 mb-2"
                       style="font-size:.78rem;">
                        <i class="fa-solid fa-arrow-left" style="font-size:.65rem;"></i>
                        Volver a Menús
                    </a>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-list me-2 text-primary"></i>Submenús
                    </h5>
                    <small class="text-muted">Gestiona los submenús del menú <strong>{{ $nombreMenu }}</strong>.</small>
                </div>
                @can('gestion_submenus.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Submenú
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
                           placeholder="Buscar submenú...">
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

            {{-- Alertas --}}
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
                            <th class="ps-3" style="width:50px;">
                                <span wire:click="ordenar('id_submenu')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='id_submenu' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='id_submenu' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:130px;">
                                <span wire:click="ordenar('submenu_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='submenu_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='submenu_nombre' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:130px;">
                                <span wire:click="ordenar('submenu_funcion')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Función
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='submenu_funcion' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='submenu_funcion' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:65px;" class="text-center">
                                <span wire:click="ordenar('submenu_orden')" role="button" class="d-inline-flex align-items-center justify-content-center gap-1">
                                    Orden
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='submenu_orden' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='submenu_orden' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:80px;" class="text-center">Visible</th>
                            <th style="width:110px;" class="text-center">Estado</th>
                            <th style="width:80px;" class="text-center">Opciones</th>
                            <th style="width:110px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submenus as $index => $sub)
                            <tr>
                                <td class="ps-3 text-center text-muted small fw-semibold">
                                    {{ $submenus->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $sub->submenu_nombre }}</span>
                                </td>
                                <td>
                                    <code class="text-white small bg-primary bg-opacity-75 px-2 py-1 rounded">
                                        {{ $sub->submenu_funcion }}
                                    </code>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">{{ $sub->submenu_orden }}</span>
                                </td>
                                <td class="text-center">
                                    @if($sub->submenu_mostrar)
                                        <span class="badge bg-info">
                                            <i class="fa-solid fa-eye small"></i>
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fa-solid fa-eye-slash small"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($sub->submenu_estado == 1)
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
                                    <a href="{{ url('configuracion/opciones/' . $sub->id_submenu) }}"
                                       class="badge text-decoration-none bg-primary px-2 py-2"
                                       title="Ver opciones">
                                        <i class="fa-solid fa-layer-group me-1 small"></i>
                                        {{ $sub->contar }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @can('gestion_submenus.actualizar')
                                        <button class="btn btn-sm btn-warning"
                                                wire:click="abrirModalEditar({{ $sub->id_submenu }})"
                                                title="Editar">
                                            <i class="fa-solid fa-pencil text-white"></i>
                                        </button>
                                        @endcan
                                        @can('gestion_submenus.cambiar_estado')
                                        @if($sub->submenu_estado == 1)
                                            <button class="btn btn-sm btn-danger"
                                                    wire:click="confirmarCambiarEstado({{ $sub->id_submenu }}, 0)"
                                                    title="Deshabilitar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="confirmarCambiarEstado({{ $sub->id_submenu }}, 1)"
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
                                    <i class="fa-solid fa-list fa-2x mb-2 d-block opacity-25"></i>
                                    @if($buscar)
                                        No se encontraron submenús que coincidan con <strong>"{{ $buscar }}"</strong>.
                                    @else
                                        No hay submenús registrados para este menú.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($submenus->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $submenus->firstItem() }}–{{ $submenus->lastItem() }}
                        de {{ $submenus->total() }} registros
                    </small>
                    {{ $submenus->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, ordenar, abrirModalNuevo, abrirModalEditar, guardar, cambiarEstado">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSubmenu')).show();
    });

    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSubmenu'));
        if (modal) modal.hide();
    });

    $wire.on('abrirModalEstado', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstadoSubmenu')).show();
    });

    $wire.on('cerrarModalEstado', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstadoSubmenu'));
        if (modal) modal.hide();
    });
</script>
@endscript
