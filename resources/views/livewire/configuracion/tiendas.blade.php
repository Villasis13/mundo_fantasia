<div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Deshabilitar Sede                                --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarTienda" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Deshabilitar Sede
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-store fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-0">¿Estás seguro de que deseas deshabilitar esta sede?</p>
                    <small class="text-muted">Puedes volver a habilitarla cuando lo necesites.</small>
                </div>
                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold"
                            wire:click="eliminar"
                            wire:loading.attr="disabled" wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-ban me-1"></i> Sí, deshabilitar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Deshabilitando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Sede                              --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalTienda" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-store me-2" style="color:#0b1892;"></i>
                        {{ $modoEdicion ? 'Editar Sede' : 'Nueva Sede' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- Nombre --}}
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Nombre de la Sede <span class="text-danger">*</span>
                            </label>
                            <input type="text" wire:model="tiendaNombre"
                                   class="form-control @error('tiendaNombre') is-invalid @enderror"
                                   placeholder="Ej: Sede Centro, Sede Norte...">
                            @error('tiendaNombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Código --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Código <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text" wire:model="tiendaCodigo"
                                   class="form-control @error('tiendaCodigo') is-invalid @enderror"
                                   placeholder="Ej: S001">
                            @error('tiendaCodigo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Dirección <span class="text-danger">*</span>
                            </label>
                            <input type="text" wire:model="tiendaDireccion"
                                   class="form-control @error('tiendaDireccion') is-invalid @enderror"
                                   placeholder="Ej: Av. Principal 123, Piso 2">
                            @error('tiendaDireccion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Teléfono --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Teléfono <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text" wire:model="tiendaTelefono"
                                   class="form-control @error('tiendaTelefono') is-invalid @enderror"
                                   placeholder="Ej: 065-123456">
                            @error('tiendaTelefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Responsable --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">Responsable</label>
                            <select wire:model="tiendaResponsable"
                                    class="form-select @error('tiendaResponsable') is-invalid @enderror">
                                <option value="">— Sin responsable —</option>
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id_users }}">{{ $u->nombre_users }}</option>
                                @endforeach
                            </select>
                            @error('tiendaResponsable') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

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

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  CONTENIDO PRINCIPAL                                       --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">

            {{-- Breadcrumb --}}
            <div class="d-flex align-items-center gap-2 mb-3">
                @if($idGrupo)
                    <a href="{{ route('configuracion.empresas.grupo', $idGrupo) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Empresas
                    </a>
                @else
                    <a href="{{ route('configuracion.empresas') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Empresas
                    </a>
                @endif
                @if($nombreEmpresa)
                    <span class="badge text-white" style="background:#0b1892;">
                        <i class="fa-solid fa-building me-1"></i> {{ $nombreEmpresa }}
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    @if($logoEmpresa)
                        <img src="{{ asset($logoEmpresa) }}" alt="Logo empresa"
                             style="height:48px;width:auto;object-fit:contain;border-radius:6px;">
                    @endif
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fa-solid fa-store me-2 text-success"></i>Sedes
                            @if($nombreEmpresa)
                                <span class="text-muted fw-normal fs-6">— {{ $nombreEmpresa }}</span>
                            @endif
                        </h5>
                        <small class="text-muted">Gestión de sedes de la empresa.</small>
                    </div>
                </div>
                @can('opcion_gestion_tiendas.crear')
                <button class="btn btn-success fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Sede
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
                        <option value="100">100</option>
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
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="$set('buscar','')">
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
                        <th style="cursor:pointer;" wire:click="ordenar('t.id_tienda')">
                            #
                            @if($ordenColumna === 't.id_tienda') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th style="cursor:pointer; text-align:left;" wire:click="ordenar('t.tienda_nombre')">
                            Nombre
                            @if($ordenColumna === 't.tienda_nombre') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th class="text-start">Código</th>
                        <th class="text-start">Dirección</th>
                        <th class="text-start">Teléfono</th>
                        <th class="text-start">Responsable</th>
                        <th>Cajas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tiendas as $tienda)
                        <tr @if($tienda->tienda_estado == 0) style="background:rgba(220,53,69,0.18);" @endif>
                            <td class="text-center text-muted">{{ $tienda->id_tienda }}</td>
                            <td class="fw-semibold">{{ $tienda->tienda_nombre }}</td>
                            <td class="small text-muted">{{ $tienda->tienda_codigo ?? '—' }}</td>
                            <td class="small">{{ $tienda->tienda_direccion ?? '—' }}</td>
                            <td class="small text-muted">{{ $tienda->tienda_telefono ?? '—' }}</td>
                            <td class="small">
                                @if($tienda->responsable_nombre)
                                    <i class="fa-solid fa-user-tie text-muted me-1"></i>
                                    {{ $tienda->responsable_nombre }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('configuracion.cajas.tienda', $tienda->id_tienda) }}"
                                   class="badge {{ $tienda->count_cajas > 0 ? 'bg-primary' : 'bg-secondary' }} text-decoration-none"
                                   title="{{ $tienda->count_cajas > 0 ? 'Ver cajas' : 'Agregar cajas' }}">
                                    <i class="fa-solid fa-cash-register me-1"></i>{{ $tienda->count_cajas }}
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $tienda->tienda_estado == 1 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $tienda->tienda_estado == 1 ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('opcion_gestion_tiendas.actualizar')
                                    <button class="btn btn-sm btn-primary"
                                            wire:click="abrirModalEditar({{ $tienda->id_tienda }})"
                                            title="Editar sede">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endcan
                                    @can('opcion_gestion_tiendas.cambiar_estado')
                                    @if($tienda->tienda_estado == 1)
                                        <button class="btn btn-sm btn-danger"
                                                wire:click="confirmarEliminar({{ $tienda->id_tienda }})"
                                                title="Deshabilitar sede">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-success"
                                                wire:click="habilitarTienda({{ $tienda->id_tienda }})"
                                                title="Habilitar sede">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fa-solid fa-store fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron sedes registradas para esta empresa.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($tiendas->count())
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-3 pb-3">
                    <small class="text-muted">
                        Mostrando {{ $tiendas->firstItem() }} - {{ $tiendas->lastItem() }}
                        de {{ $tiendas->total() }} registros
                    </small>
                    {{ $tiendas->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    {{-- Loader --}}
    <div wire:loading wire:target="abrirModalEditar, abrirModalNuevo, guardar, eliminar, habilitarTienda">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        new bootstrap.Modal(document.getElementById('modalTienda')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalTienda'));
        if (modal) modal.hide();
    });

    document.getElementById('modalTienda').addEventListener('hidden.bs.modal', () => {
        $wire.limpiarFormulario();
    });

    $wire.on('abrirModalEliminar', () => {
        new bootstrap.Modal(document.getElementById('modalEliminarTienda')).show();
    });
    $wire.on('cerrarModalEliminar', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarTienda'));
        if (modal) modal.hide();
    });
</script>
@endscript
