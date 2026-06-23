<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Sucursal                           --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSucursal" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Sucursal' : 'Nueva Sucursal' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos de la sucursal seleccionada.' : 'Completa los campos para registrar una nueva sucursal.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        {{-- Nombre --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="sucursalNombre"
                                   class="form-control @error('sucursalNombre') is-invalid @enderror"
                                   placeholder="Ej: Sede Central, Tienda Norte...">
                            @error('sucursalNombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tipo --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select wire:model="sucursalTipo"
                                    class="form-select @error('sucursalTipo') is-invalid @enderror">
                                <option value="1">Tienda</option>
                                <option value="2">Sucursal</option>
                                <option value="3">Almacén</option>
                            </select>
                            @error('sucursalTipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Dirección
                            </label>
                            <textarea wire:model="sucursalDireccion"
                                      class="form-control @error('sucursalDireccion') is-invalid @enderror"
                                      rows="2"
                                      placeholder="Ej: Av. Principal 123, Lima..."></textarea>
                            @error('sucursalDireccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                            class="btn btn-primary fw-semibold px-5"
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
    <div class="modal fade" id="modalEstadoSucursal" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">

                <div style="height:5px;" class="{{ $nuevoEstado === 0 ? 'bg-danger' : 'bg-success' }}"></div>

                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1;"></button>

                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3
                         {{ $nuevoEstado === 0 ? 'bg-danger' : 'bg-success' }}"
                         style="width:76px;height:76px;">
                        <i class="fa-solid fa-{{ $nuevoEstado === 0 ? 'ban' : 'circle-check' }} fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-size:1rem;">
                        {{ $nuevoEstado === 0 ? '¿Deshabilitar esta sucursal?' : '¿Habilitar esta sucursal?' }}
                    </h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">
                        {{ $nuevoEstado === 0 ? 'La sucursal dejará de estar disponible.' : 'La sucursal volverá a estar disponible.' }}
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
                    <a href="{{ url('configuracion/empresas') }}"
                       class="text-decoration-none text-muted d-inline-flex align-items-center gap-1 mb-2"
                       style="font-size:.78rem;">
                        <i class="fa-solid fa-arrow-left" style="font-size:.65rem;"></i>
                        Volver a Empresas
                    </a>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-code-branch me-2 text-primary"></i>Sucursales
                    </h5>
                    <small class="text-muted">Gestiona las sucursales de <strong>{{ $nombreEmpresa }}</strong>.</small>
                </div>
                @can('sucursal_opcion.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Sucursal
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
                           placeholder="Buscar sucursal...">
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
                                <span wire:click="ordenar('id_sucursal')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='id_sucursal' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='id_sucursal' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:160px;" class="text-start">
                                <span wire:click="ordenar('sucursal_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='sucursal_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='sucursal_nombre' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:90px;" class="text-center">Tipo</th>
                            <th class="text-start" style="min-width:200px;">Dirección</th>
                            <th style="width:90px;" class="text-center">Estado</th>
                            <th style="width:80px;" class="text-center">Cajas</th>
                            <th style="width:110px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sucursales as $index => $suc)
                            <tr>
                                <td class="ps-3 text-center text-muted small fw-semibold">
                                    {{ $sucursales->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $suc->sucursal_nombre }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $tipoLabels = [1=>'Tienda',2=>'Sucursal',3=>'Almacén'];
                                        $tipoColors = [1=>'success',2=>'primary',3=>'warning'];
                                        $t = $suc->sucursal_tipo ?? 2;
                                    @endphp
                                    <span class="badge bg-{{ $tipoColors[$t] ?? 'secondary' }}">
                                        {{ $tipoLabels[$t] ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ $suc->sucursal_direccion ?? '—' }}
                                </td>
                                <td class="text-center">
                                    @if($suc->sucursal_estado == 1)
                                        <span class="badge bg-success">
                                            <i class="fa-solid fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ url('configuracion/cajas/' . $suc->id_sucursal) }}"
                                       class="badge text-decoration-none bg-primary px-3 py-2"
                                       title="Ver cajas de esta sucursal">
                                        <i class="fa-solid fa-cash-register me-1 small"></i>
                                        {{ $suc->contar_cajas }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @can('sucursal_opcion.actualizar')
                                        <button class="btn btn-sm btn-warning"
                                                wire:click="abrirModalEditar({{ $suc->id_sucursal }})"
                                                title="Editar sucursal">
                                            <i class="fa-solid fa-pencil text-white"></i>
                                        </button>
                                        @endcan
                                        @can('sucursal_opcion.cambiar_estado')
                                        @if($suc->sucursal_estado == 1)
                                            <button class="btn btn-sm btn-danger"
                                                    wire:click="confirmarCambiarEstado({{ $suc->id_sucursal }}, 0)"
                                                    title="Deshabilitar sucursal">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="confirmarCambiarEstado({{ $suc->id_sucursal }}, 1)"
                                                    title="Habilitar sucursal">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </button>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-code-branch fa-2x mb-2 d-block opacity-25"></i>
                                    @if($buscar)
                                        No se encontraron sucursales que coincidan con <strong>"{{ $buscar }}"</strong>.
                                    @else
                                        No hay sucursales registradas para esta empresa.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($sucursales->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $sucursales->firstItem() }}–{{ $sucursales->lastItem() }}
                        de {{ $sucursales->total() }} registros
                    </small>
                    {{ $sucursales->links(data: ['scrollTo' => false]) }}
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSucursal')).show();
    });

    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSucursal'));
        if (modal) modal.hide();
    });

    $wire.on('abrirModalEstado', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstadoSucursal')).show();
    });

    $wire.on('cerrarModalEstado', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstadoSucursal'));
        if (modal) modal.hide();
    });
</script>
@endscript
