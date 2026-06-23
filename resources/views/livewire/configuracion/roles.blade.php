<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Rol                                --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalRol" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Rol' : 'Nuevo Rol' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos del rol seleccionado.' : 'Completa los campos para registrar un nuevo rol.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="rolNombre"
                                   class="form-control @error('rolNombre') is-invalid @enderror"
                                   placeholder="Ej: Administrador, Vendedor...">
                            @error('rolNombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">Descripción</label>
                            <input type="text"
                                   wire:model="rolDescripcion"
                                   class="form-control @error('rolDescripcion') is-invalid @enderror"
                                   placeholder="Breve descripción del rol...">
                            @error('rolDescripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
    <div class="modal fade" id="modalEstadoRol" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
                        {{ $nuevoEstado === 0 ? '¿Deshabilitar este rol?' : '¿Habilitar este rol?' }}
                    </h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">
                        @if($nuevoEstado === 0)
                            Los usuarios con este rol perderán sus accesos al sistema.
                        @else
                            El rol volverá a estar disponible para los usuarios.
                        @endif
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
    {{--  MODAL — Asignar permisos al rol                           --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalPermisosRol" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-shield-halved me-2 text-primary"></i>Permisos del Rol
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        Gestiona los permisos del rol <strong>{{ $nombreRolPermisos }}</strong>.
                    </small>

                    @if(count($permisosArbol) > 0)
                        <div class="mt-3">
                            @foreach($permisosArbol as $mi => $menu)

                                {{-- ── Bloque de menú ──────────────────────────────── --}}
                                <div class="border rounded mb-2 overflow-hidden">

                                    {{-- Cabecera del menú --}}
                                    <div class="d-flex align-items-center px-3 py-2" style="background:#f5f6fa;">
                                        <label class="d-flex align-items-center gap-2 fw-semibold flex-grow-1 mb-0"
                                               style="cursor:pointer;font-size:.88rem;">
                                            <input type="checkbox"
                                                   class="form-check-input mt-0 flex-shrink-0"
                                                   wire:model="permisosSeleccionados"
                                                   value="{{ $menu['id'] }}">
                                            {{ $menu['name'] }}
                                        </label>
                                        <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#menuBlk{{ $mi }}"
                                                aria-expanded="false">
                                            <i class="fa-solid fa-chevron-down" style="font-size:.7rem;"></i>
                                        </button>
                                    </div>

                                    {{-- Contenido colapsable --}}
                                    <div id="menuBlk{{ $mi }}" class="collapse">
                                        <div class="px-3 pt-2 pb-3">

                                            @foreach($menu['sub'] as $si => $sub)
                                                @if($si > 0)<hr class="my-2 opacity-10">@endif

                                                {{-- Nivel 2: Submenú --}}
                                                <label class="d-flex align-items-center gap-2 fw-semibold text-secondary mb-2"
                                                       style="cursor:pointer;font-size:.83rem;">
                                                    <input type="checkbox"
                                                           class="form-check-input mt-0 flex-shrink-0"
                                                           wire:model="permisosSeleccionados"
                                                           value="{{ $sub['id'] }}">
                                                    {{ $sub['name'] }}
                                                </label>

                                                @foreach($sub['opciones'] as $op)
                                                    <div class="ms-3 mb-2">
                                                        {{-- Nivel 3: Opción --}}
                                                        <label class="d-flex align-items-center gap-2 mb-1"
                                                               style="cursor:pointer;font-size:.82rem;">
                                                            <input type="checkbox"
                                                                   class="form-check-input mt-0 flex-shrink-0"
                                                                   wire:model="permisosSeleccionados"
                                                                   value="{{ $op['id'] }}">
                                                            <span class="text-dark">{{ $op['name'] }}</span>
                                                        </label>

                                                        {{-- Nivel 4: Acciones como chips inline --}}
                                                        @if(count($op['acciones']) > 0)
                                                            <div class="d-flex flex-wrap gap-2 ms-4">
                                                                @foreach($op['acciones'] as $ac)
                                                                    <label class="d-flex align-items-center gap-1 border rounded px-2 py-1"
                                                                           style="cursor:pointer;font-size:.75rem;background:#fff;">
                                                                        <input type="checkbox"
                                                                               class="form-check-input mt-0 flex-shrink-0"
                                                                               style="width:.8rem;height:.8rem;"
                                                                               wire:model="permisosSeleccionados"
                                                                               value="{{ $ac['id'] }}">
                                                                        <span class="text-muted">{{ $ac['name'] }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach

                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-shield-halved fa-2x d-block mb-2 opacity-25"></i>
                            No hay permisos configurados en el sistema.
                        </div>
                    @endif

                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-between">
                    <small class="text-muted">
                        <i class="fa-solid fa-circle-check me-1 text-primary"></i>
                        {{ count($permisosSeleccionados) }} permiso(s) seleccionado(s)
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                        <button type="button"
                                class="btn btn-primary fw-semibold px-4"
                                wire:click="guardarPermisos"
                                wire:loading.attr="disabled"
                                wire:target="guardarPermisos">
                            <span wire:loading.remove wire:target="guardarPermisos">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                            </span>
                            <span wire:loading wire:target="guardarPermisos">
                                <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
                            </span>
                        </button>
                    </div>
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
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-user-shield me-2 text-primary"></i>Roles
                    </h5>
                    <small class="text-muted">Gestiona los roles y sus permisos de acceso.</small>
                </div>
                @can('gestion_roles.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Rol
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
                           placeholder="Buscar rol...">
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
                                <span wire:click="ordenar('id')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='id' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='id' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:130px;" class="text-start">
                                <span wire:click="ordenar('name')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='name' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='name' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:150px;" class="text-start">Descripción</th>
                            <th style="width:100px;">Permisos</th>
                            <th style="width:110px;">Estado</th>
                            <th style="width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $index => $rol)
                            <tr>
                                <td class="ps-3 text-center text-muted small fw-semibold">
                                    {{ $roles->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $rol->name }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $rol->rol_descripcion ?? '—' }}</small>
                                </td>
                                <td class="text-center">
                                    @can('gestion_roles.crear')
                                    <button class="btn btn-sm btn-outline-primary"
                                            wire:click="abrirModalPermisos({{ $rol->id }})"
                                            title="Gestionar permisos">
                                        <i class="fa-solid fa-shield-halved me-1"></i>
                                        {{ $rol->total_permisos }}
                                    </button>
                                    @endcan
                                </td>
                                <td class="text-center">
                                    @if($rol->rol_estado == 1)
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
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @can('gestion_roles.actualizar')
                                        <button class="btn btn-sm btn-warning"
                                                wire:click="abrirModalEditar({{ $rol->id }})"
                                                title="Editar">
                                            <i class="fa-solid fa-pencil text-white"></i>
                                        </button>
                                        @endcan
                                        @can('gestion_roles.cambiar_estado')
                                        @if($rol->rol_estado == 1)
                                            <button class="btn btn-sm btn-danger"
                                                    wire:click="confirmarCambiarEstado({{ $rol->id }}, 0)"
                                                    title="Deshabilitar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="confirmarCambiarEstado({{ $rol->id }}, 1)"
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
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-user-shield fa-2x mb-2 d-block opacity-25"></i>
                                    @if($buscar)
                                        No se encontraron roles que coincidan con <strong>"{{ $buscar }}"</strong>.
                                    @else
                                        No hay roles registrados.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($roles->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $roles->firstItem() }}–{{ $roles->lastItem() }}
                        de {{ $roles->total() }} registros
                    </small>
                    {{ $roles->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, ordenar, abrirModalNuevo, abrirModalEditar, guardar, cambiarEstado, abrirModalPermisos, guardarPermisos">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRol')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalRol'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalEstado', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstadoRol')).show();
    });
    $wire.on('cerrarModalEstado', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstadoRol'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalPermisos', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPermisosRol')).show();
    });
    $wire.on('cerrarModalPermisos', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalPermisosRol'));
        if (modal) modal.hide();
    });
</script>
@endscript
