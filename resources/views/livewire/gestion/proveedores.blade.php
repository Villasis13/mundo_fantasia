<div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Proveedor                          --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalProveedor" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'truck' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos del proveedor.' : 'Completa los campos para registrar un nuevo proveedor.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        {{-- Tipo de documento --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Tipo de documento <span class="text-danger">*</span>
                            </label>
                            <select wire:model="idTipoDocumento"
                                    class="form-select @error('idTipoDocumento') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                @foreach($tiposDocumento as $td)
                                    <option value="{{ $td->id_tipo_documento }}">{{ $td->tipo_documento_identidad_abr }}</option>
                                @endforeach
                            </select>
                            @error('idTipoDocumento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Número de documento con lookup --}}
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                N° Documento <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       wire:model.live.debounce.500ms="numeroDocumento"
                                       class="form-control @error('numeroDocumento') is-invalid @enderror"
                                       placeholder="DNI (8 dígitos) o RUC (11 dígitos)"
                                       maxlength="11">
                                <span class="input-group-text bg-light">
                                    @if($buscandoDocumento)
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    @else
                                        <i class="fa-solid fa-id-card text-muted small"></i>
                                    @endif
                                </span>
                            </div>
                            @error('numeroDocumento')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @if($documentoMensaje && !$errors->has('numeroDocumento'))
                                <div class="small mt-1 {{ $documentoMensajeTipo === 'success' ? 'text-success' : 'text-danger' }}">
                                    <i class="fa-solid fa-{{ $documentoMensajeTipo === 'success' ? 'circle-check' : 'circle-xmark' }} me-1"></i>
                                    {{ $documentoMensaje }}
                                </div>
                            @endif
                        </div>

                        {{-- Nombre / Razón social --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre / Razón Social <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="proveedorNombre"
                                   class="form-control @error('proveedorNombre') is-invalid @enderror"
                                   placeholder="Nombre o razón social del proveedor">
                            @error('proveedorNombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">Dirección</label>
                            <input type="text"
                                   wire:model="proveedorDireccion"
                                   class="form-control @error('proveedorDireccion') is-invalid @enderror"
                                   placeholder="Dirección del proveedor">
                            @error('proveedorDireccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold"><i class="fa-solid fa-address-card me-1"></i>Contacto (opcional)</small></div>

                        {{-- Nombre contacto --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Nombre de contacto</label>
                            <input type="text"
                                   wire:model="proveedorContacto"
                                   class="form-control @error('proveedorContacto') is-invalid @enderror"
                                   placeholder="Ej: Juan Pérez">
                            @error('proveedorContacto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Cargo --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Cargo</label>
                            <input type="text"
                                   wire:model="proveedorCargo"
                                   class="form-control @error('proveedorCargo') is-invalid @enderror"
                                   placeholder="Ej: Gerente Comercial">
                            @error('proveedorCargo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Teléfono --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Teléfono</label>
                            <input type="text"
                                   wire:model="proveedorTelefono"
                                   class="form-control @error('proveedorTelefono') is-invalid @enderror"
                                   placeholder="Ej: 987654321"
                                   maxlength="20">
                            @error('proveedorTelefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Correo --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Correo electrónico</label>
                            <input type="email"
                                   wire:model="proveedorCorreo"
                                   class="form-control @error('proveedorCorreo') is-invalid @enderror"
                                   placeholder="proveedor@ejemplo.com">
                            @error('proveedorCorreo') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    <div class="modal fade" id="modalEliminarProveedor" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
                    <h6 class="fw-bold mb-1" style="font-size:1rem;">¿Eliminar este proveedor?</h6>
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
                        <i class="fa-solid fa-truck me-2 text-primary"></i>Proveedores
                    </h5>
                    <small class="text-muted">Gestión de proveedores registrados.</small>
                </div>
                @can('gestion_proveedores.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Proveedor
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
                <div class="input-group input-group-sm" style="max-width:320px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar por nombre o documento...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="$set('buscar', '')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>

            @if($esSuperAdmin)
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
            @endif

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
                                <span wire:click="ordenar('p.id_proveedores')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='p.id_proveedores' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='p.id_proveedores' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th class="text-start" style="min-width:130px;">Documento</th>
                            <th class="text-start" style="min-width:180px;">
                                <span wire:click="ordenar('p.proveedores_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre / Razón Social
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='p.proveedores_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='p.proveedores_nombre' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            {{--@if($esSuperAdmin)
                            <th class="text-start" style="min-width:140px;">Empresa</th>
                            @endif--}}
                            <th class="text-start" style="min-width:110px;">Teléfono</th>
                            <th class="text-start" style="min-width:160px;">Correo</th>
                            <th style="width:100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedores as $index => $proveedor)
                        <tr>
                            <td class="ps-3 text-center text-muted small fw-semibold">
                                {{ $proveedores->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    {{ $proveedor->tipo_documento_identidad_abr }}
                                </span>
                                <span class="small ms-1">{{ $proveedor->proveedores_numero_documento }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $proveedor->proveedores_nombre }}</span>
                                @if($proveedor->proveedores_direccion)
                                    <br><small class="text-muted">{{ Str::limit($proveedor->proveedores_direccion, 50) }}</small>
                                @endif
                            </td>
                            {{--@if($esSuperAdmin)
                            <td>
                                @if($proveedor->empresa_nombrecomercial)
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                        <i class="fa-solid fa-building me-1"></i>{{ $proveedor->empresa_nombrecomercial }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            @endif--}}
                            <td>
                                <small class="text-muted">{{ $proveedor->proveedores_telefono ?: '—' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $proveedor->proveedores_correo ?: '—' }}</small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    @can('gestion_proveedores.actualizar')
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="abrirModalEditar({{ $proveedor->id_proveedores }})"
                                            title="Editar">
                                        <i class="fa-solid fa-pencil text-white"></i>
                                    </button>
                                    @endcan
                                    @can('gestion_proveedores.cambiar_estado')
                                    <button class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminar({{ $proveedor->id_proveedores }})"
                                            title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $esSuperAdmin ? 7 : 6 }}" class="text-center text-muted py-5">
                                @if($buscar)
                                    <i class="fa-solid fa-truck fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron proveedores que coincidan con <strong>"{{ $buscar }}"</strong>.
                                @else
                                    <i class="fa-solid fa-truck fa-2x mb-2 d-block opacity-25"></i>
                                    No hay proveedores registrados.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($proveedores->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $proveedores->firstItem() }}–{{ $proveedores->lastItem() }}
                    de {{ $proveedores->total() }} registros
                </small>
                {{ $proveedores->links(data: ['scrollTo' => false]) }}
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProveedor')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalProveedor'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalEliminar', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminarProveedor')).show();
    });
    $wire.on('cerrarModalEliminar', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarProveedor'));
        if (modal) modal.hide();
    });
</script>
@endscript
