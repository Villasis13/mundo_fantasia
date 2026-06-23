<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Familia                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalFamilia" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Familia' : 'Nueva Familia' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos de la familia.' : 'Completa los campos para registrar una nueva familia.' }}
                    </small>
                    <div class="row mt-3 g-3">

                        {{-- @if($esSuperAdmin)
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                <i class="fa-solid fa-building me-1"></i>Empresa <span class="text-danger">*</span>
                            </label>
                            <select wire:model="empresaIdModal"
                                    class="form-select @error('empresaIdModal') is-invalid @enderror">
                                <option value="0">— Selecciona una empresa —</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                            @error('empresaIdModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif --}}

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="faNombre"
                                   class="form-control @error('faNombre') is-invalid @enderror"
                                   placeholder="Ej: Alimentos, Medicamentos, Accesorios">
                            @error('faNombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    {{--  MODAL — Confirmar Eliminar                                --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarFamilia" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">

                <div style="height:5px;" class="bg-danger"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1;"></button>

                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3"
                         style="width:76px;height:76px;">
                        <i class="fa-solid fa-trash fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-size:1rem;">¿Eliminar esta familia?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Esta acción no se puede deshacer.</p>
                </div>

                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button"
                            class="btn btn-danger btn-sm fw-semibold px-4"
                            wire:click="eliminar"
                            wire:loading.attr="disabled"
                            wire:target="eliminar">
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

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  CARD PRINCIPAL                                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>Familias
                    </h5>
                    <small class="text-muted">Agrupa tus productos en familias para organizarlos mejor.</small>
                </div>
                @can('gestion_familias.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Familia
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
                           placeholder="Buscar familia...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="$set('buscar', '')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- @if($esSuperAdmin)
            <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-2 border-top">
                <span class="text-muted small text-nowrap fw-semibold">
                    <i class="fa-solid fa-filter me-1"></i>Filtros
                </span>
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-building text-muted small"></i>
                    <select wire:model.live="filtroEmpresa"
                            class="form-select form-select-sm"
                            style="min-width:190px;">
                        <option value="0">Todas las empresas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif --}}

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
                                <span wire:click="ordenar('f.id_fa')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='f.id_fa' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='f.id_fa' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th class="text-start" style="min-width:160px;">
                                <span wire:click="ordenar('f.fa_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='f.fa_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='f.fa_nombre' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            {{-- @if($esSuperAdmin)
                            <th class="text-start" style="min-width:160px;">
                                <span wire:click="ordenar('e.empresa_nombrecomercial')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Empresa
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='e.empresa_nombrecomercial' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='e.empresa_nombrecomercial' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            @endif --}}
                            <th style="width:110px;">Categorías</th>
                            <th style="width:100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($familias as $index => $familia)
                        <tr>
                            <td class="ps-3 text-center text-muted small fw-semibold">
                                {{ $familias->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $familia->fa_nombre }}</span>
                            </td>
                            {{-- @if($esSuperAdmin)
                            <td>
                                @if($familia->empresa_nombrecomercial)
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                        <i class="fa-solid fa-building me-1"></i>{{ $familia->empresa_nombrecomercial }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            @endif --}}
                            <td class="text-center">
                                <a href="{{ route('Gestion.categorias', $familia->id_fa) }}"
                                   class="badge bg-info text-decoration-none"
                                   title="Ver categorías">
                                    <i class="fa-solid fa-tags me-1"></i>{{ $familia->contar_categorias }}
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    @can('gestion_familias.actualizar')
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="abrirModalEditar({{ $familia->id_fa }})"
                                            title="Editar">
                                        <i class="fa-solid fa-pencil text-white"></i>
                                    </button>
                                    @endcan
                                    @can('gestion_familias.cambiar_estado')
                                    <button class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminar({{ $familia->id_fa }})"
                                            title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                @if($buscar)
                                    <i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron familias que coincidan con <strong>"{{ $buscar }}"</strong>.
                                @else
                                    <i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>
                                    No hay familias registradas.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($familias->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $familias->firstItem() }}–{{ $familias->lastItem() }}
                    de {{ $familias->total() }} registros
                </small>
                {{ $familias->links(data: ['scrollTo' => false]) }}
            </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, ordenar, abrirModalNuevo, abrirModalEditar, guardar, eliminar, filtroEmpresa">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFamilia')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalFamilia'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalEliminar', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminarFamilia')).show();
    });
    $wire.on('cerrarModalEliminar', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarFamilia'));
        if (modal) modal.hide();
    });
</script>
@endscript
