<div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Eliminar Plan                                    --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarPlan" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Eliminar Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-layer-group fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-0">¿Estás seguro de que deseas eliminar este plan?</p>
                    <small class="text-muted">No se podrá eliminar si tiene empresas activas vinculadas.</small>
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

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Plan                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalPlan" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-layer-group me-2" style="color:#0b1892;"></i>
                        {{ $modoEdicion ? 'Editar Plan' : 'Nuevo Plan' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- Nombre --}}
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Nombre del Plan <span class="text-danger">*</span>
                            </label>
                            <input type="text" wire:model="planNombre"
                                   class="form-control @error('planNombre') is-invalid @enderror"
                                   placeholder="Ej: Plan Básico, Plan Empresarial...">
                            @error('planNombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Estado --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-muted small text-uppercase">Estado</label>
                            <select wire:model="planEstado" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>

                        {{-- Descripción --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">Descripción</label>
                            <input type="text" wire:model="planDescripcion"
                                   class="form-control @error('planDescripcion') is-invalid @enderror"
                                   placeholder="Descripción breve del plan">
                            @error('planDescripcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Precio --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Precio (S/) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" wire:model="planPrecio"
                                       class="form-control @error('planPrecio') is-invalid @enderror"
                                       placeholder="0.00" min="0" step="0.01">
                                @error('planPrecio') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Duración --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Duración (días) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" wire:model="planDuracionDias"
                                       class="form-control @error('planDuracionDias') is-invalid @enderror"
                                       placeholder="Ej: 30, 90, 365" min="1">
                                <span class="input-group-text">días</span>
                                @error('planDuracionDias') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            @if($planDuracionDias > 0)
                                <small class="text-muted">
                                    Equivale a ~{{ round($planDuracionDias / 30, 1) }} mes(es)
                                </small>
                            @endif
                        </div>

                        {{-- Resumen visual del plan --}}
                        @if($planNombre || $planPrecio || $planDuracionDias)
                            <div class="col-12">
                                <div class="rounded p-3 border" style="background:#eef1ff;">
                                    <small class="text-muted fw-semibold d-block mb-1 text-uppercase" style="font-size:10px;">
                                        Vista previa del plan
                                    </small>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div>
                                            <span class="fw-bold" style="color:#0b1892; font-size:16px;">
                                                {{ $planNombre ?: '—' }}
                                            </span>
                                        </div>
                                        @if($planPrecio)
                                            <div>
                                                <span class="badge bg-success fs-6">S/ {{ number_format($planPrecio, 2) }}</span>
                                            </div>
                                        @endif
                                        @if($planDuracionDias)
                                            <div>
                                                <span class="badge" style="background:#0b1892;">
                                                    <i class="fa-solid fa-calendar-days me-1"></i>
                                                    {{ $planDuracionDias }} días
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($planDescripcion)
                                        <small class="text-muted mt-1 d-block">{{ $planDescripcion }}</small>
                                    @endif
                                </div>
                            </div>
                        @endif

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
    {{--  CONTENIDO PRINCIPAL                                      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>Planes
                    </h5>
                    <small class="text-muted">Gestión de planes disponibles para las empresas.</small>
                </div>
                @can('gestion_planes.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Plan
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
                    <input type="text" wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar por nombre o descripción...">
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
                        <th style="cursor:pointer;" wire:click="ordenar('id_plan')">
                            #
                            @if($ordenColumna === 'id_plan') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th style="cursor:pointer;" wire:click="ordenar('plan_nombre')" class="text-start">
                            Nombre
                            @if($ordenColumna === 'plan_nombre') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th class="text-start">Descripción</th>
                        <th style="cursor:pointer;" wire:click="ordenar('plan_precio')">
                            Precio
                            @if($ordenColumna === 'plan_precio') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th style="cursor:pointer;" wire:click="ordenar('plan_duracion_dias')">
                            Duración
                            @if($ordenColumna === 'plan_duracion_dias') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th>Empresas Vinculadas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($planes as $plan)
                        @php
                            $empresasActivas = DB::table('empresa_planes')
                                ->where('id_plan', $plan->id_plan)
                                ->where('estado', 1)
                                ->count();
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $plan->id_plan }}</td>
                            <td>
                                <div class="fw-semibold">{{ $plan->plan_nombre }}</div>
                            </td>
                            <td class="small text-muted">{{ $plan->plan_descripcion ?? '—' }}</td>
                            <td class="text-center fw-bold" style="color:#5a9900;">
                                S/ {{ number_format($plan->plan_precio, 2) }}
                            </td>
                            <td class="text-center">
                                    <span class="badge" style="background:#0b1892;">
                                        <i class="fa-solid fa-calendar-days me-1"></i>
                                        {{ $plan->plan_duracion_dias }} días
                                    </span>
                                <small class="text-muted d-block">~{{ round($plan->plan_duracion_dias / 30, 1) }} mes(es)</small>
                            </td>
                            <td class="text-center">
                                @if($empresasActivas > 0)
                                    <span class="badge bg-primary">{{ $empresasActivas }} activa(s)</span>
                                @else
                                    <span class="badge bg-light text-dark border">Sin empresas</span>
                                @endif
                            </td>
                            <td class="text-center">
                                    <span class="badge {{ $plan->plan_estado == 1 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $plan->plan_estado == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('gestion_planes.actualizar')
                                    <button class="btn btn-sm btn-primary"
                                            wire:click="abrirModalEditar({{ $plan->id_plan }})"
                                            title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endcan
                                    @can('gestion_planes.cambiar_estado')
                                    <button class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminar({{ $plan->id_plan }})"
                                            title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron planes registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($planes->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $planes->firstItem() }}–{{ $planes->lastItem() }}
                        de {{ $planes->total() }} registros
                    </small>
                    {{ $planes->links(data: ['scrollTo' => false]) }}
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
            new bootstrap.Modal(document.getElementById('modalPlan')).show();
        });
        $wire.on('cerrarModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalPlan'));
            if (modal) modal.hide();
        });
        $wire.on('abrirModalEliminar', () => {
            new bootstrap.Modal(document.getElementById('modalEliminarPlan')).show();
        });
        $wire.on('cerrarModalEliminar', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarPlan'));
            if (modal) modal.hide();
        });
    </script>
@endscript
