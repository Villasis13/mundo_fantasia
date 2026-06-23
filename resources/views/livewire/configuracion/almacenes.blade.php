<div>

    {{-- ══ MODAL — Eliminar ══════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarAlmacen" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Eliminar Almacén
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-warehouse fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-0">¿Estás seguro de que deseas eliminar este almacén?</p>
                    <small class="text-muted">Esta acción no se puede deshacer.</small>
                </div>
                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold"
                            wire:click="eliminar"
                            wire:loading.attr="disabled" wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-trash me-1"></i> Sí, eliminar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Eliminando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ MODAL — Crear / Editar ════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAlmacen" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-warehouse me-2" style="color:#0b1892;"></i>
                        {{ $modoEdicion ? 'Editar Almacén' : 'Nuevo Almacén' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormulario"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small text-uppercase">
                            Nombre del Almacén <span class="text-danger">*</span>
                        </label>
                        <input type="text" wire:model="almacenNombre"
                               class="form-control @error('almacenNombre') is-invalid @enderror"
                               placeholder="Ej: Almacén Principal, Almacén 2...">
                        @error('almacenNombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal" wire:click="limpiarFormulario">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="guardar"
                            wire:loading.attr="disabled" wire:target="guardar">
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

    {{-- ══ CONTENIDO PRINCIPAL ════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">

            {{-- Breadcrumb --}}
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                @if($idEmpresa)
                    <a href="{{ route('configuracion.tiendas', $idEmpresa) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Tiendas
                    </a>
                @else
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver
                    </a>
                @endif
                @if($nombreEmpresa)
                    <span class="badge text-white" style="background:#0b1892;">
                        <i class="fa-solid fa-building me-1"></i> {{ $nombreEmpresa }}
                    </span>
                @endif
                @if($nombreTienda)
                    <span class="badge bg-success">
                        <i class="fa-solid fa-store me-1"></i> {{ $nombreTienda }}
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-warehouse me-2 text-warning"></i>Almacenes
                        @if($nombreTienda)
                            <span class="text-muted fw-normal fs-6">— {{ $nombreTienda }}</span>
                        @endif
                    </h5>
                    <small class="text-muted">Gestión de almacenes de la tienda.</small>
                </div>
                @can('gestionar_opcion_tienda.crear')
                <button class="btn btn-warning fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Almacén
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
                <div class="input-group input-group-sm" style="max-width:340px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar por nombre...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm" type="button"
                                wire:click="$set('buscar','')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
                    <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr class="encabezado_tabla_color text-center">
                        <th style="cursor:pointer;" wire:click="ordenar('id_tienda')">#
                            @if($ordenColumna === 'id_tienda')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                        <th style="cursor:pointer; text-align:left;" wire:click="ordenar('tienda_nombre')">Nombre
                            @if($ordenColumna === 'tienda_nombre')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($almacenes as $almacen)
                        <tr>
                            <td class="text-center text-muted">{{ $almacen->id_tienda }}</td>
                            <td class="fw-semibold">
                                <i class="fa-solid fa-warehouse text-warning me-2"></i>
                                {{ $almacen->tienda_nombre }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $almacen->tienda_estado == 1 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $almacen->tienda_estado == 1 ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('gestionar_opcion_tienda.actualizar')
                                    <button class="btn btn-sm btn-primary"
                                            wire:click="abrirModalEditar({{ $almacen->id_tienda }})"
                                            title="Editar almacén">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endcan
                                    @can('gestionar_opcion_tienda.cambiar_estado')
                                    <button class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminar({{ $almacen->id_tienda }})"
                                            title="Eliminar almacén">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="fa-solid fa-warehouse fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron almacenes registrados para esta tienda.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($almacenes->count())
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-3 pb-3">
                    <small class="text-muted">
                        Mostrando {{ $almacenes->firstItem() }} - {{ $almacenes->lastItem() }}
                        de {{ $almacenes->total() }} registros
                    </small>
                    {{ $almacenes->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="abrirModalEditar, abrirModalNuevo, guardar, eliminar">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        new bootstrap.Modal(document.getElementById('modalAlmacen')).show();
    });
    $wire.on('cerrarModal', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalAlmacen'));
        if (m) m.hide();
    });
    document.getElementById('modalAlmacen').addEventListener('hidden.bs.modal', () => {
        $wire.limpiarFormulario();
    });
    $wire.on('abrirModalEliminar', () => {
        new bootstrap.Modal(document.getElementById('modalEliminarAlmacen')).show();
    });
    $wire.on('cerrarModalEliminar', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalEliminarAlmacen'));
        if (m) m.hide();
    });
</script>
@endscript
