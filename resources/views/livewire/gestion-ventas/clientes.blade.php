<div>
    {{-- ══════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Cliente                       --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalCliente" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        {{ $modoEdicion ? 'Editar Cliente' : 'Nuevo Cliente' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">

                    {{-- Mensaje de consulta documento --}}
                    @if($mensajeConsulta)
                        <div class="alert alert-{{ $tipoMensajeConsulta === 'success' ? 'success' : ($tipoMensajeConsulta === 'warning' ? 'warning' : 'danger') }} py-2 mb-3" role="alert">
                            <i class="fa-solid fa-{{ $tipoMensajeConsulta === 'success' ? 'circle-check' : ($tipoMensajeConsulta === 'warning' ? 'triangle-exclamation' : 'circle-xmark') }} me-2"></i>
                            {{ $mensajeConsulta }}
                        </div>
                    @endif

                    <div class="row g-3">

                        {{-- Tipo de Documento --}}
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Tipo de Documento <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="idTipoDocumento"
                                    class="form-select @error('idTipoDocumento') is-invalid @enderror">
                                <option value="">— Seleccionar —</option>
                                @foreach($tiposDocumento as $td)
                                    <option value="{{ $td->id_tipo_documento }}">
                                        {{ $td->tipo_documento_identidad_abr }} — {{ $td->tipo_documento_identidad }}
                                    </option>
                                @endforeach
                            </select>
                            @error('idTipoDocumento')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Número de Documento + Botón Consultar --}}
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Número de Documento <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       wire:model="clienteNumero"
                                       class="form-control @error('clienteNumero') is-invalid @enderror"
                                       placeholder="Ingrese el número..."
                                       maxlength="{{ $idTipoDocumento == 4 ? 11 : ($idTipoDocumento == 2 ? 8 : 20) }}">
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        wire:click="consultarDocumento"
                                        wire:loading.attr="disabled"
                                        wire:target="consultarDocumento"
                                        title="Consultar documento">
                                    <span wire:loading.remove wire:target="consultarDocumento">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <span wire:loading wire:target="consultarDocumento">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                @error('clienteNumero')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Nombre --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Nombre / Razón Social <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="clienteNombre"
                                   class="form-control @error('clienteNombre') is-invalid @enderror"
                                   placeholder="Nombre completo o razón social...">
                            @error('clienteNombre')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Teléfono --}}
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Teléfono
                                <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text"
                                   wire:model="clienteTelefono"
                                   class="form-control @error('clienteTelefono') is-invalid @enderror"
                                   placeholder="Número de teléfono...">
                            @error('clienteTelefono')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Dirección
                                @if($idTipoDocumento == 4)
                                    <span class="text-danger">*</span>
                                @else
                                    <span class="text-muted fw-normal">(opcional)</span>
                                @endif
                            </label>
                            <input type="text"
                                   wire:model="clienteDireccion"
                                   class="form-control @error('clienteDireccion') is-invalid @enderror"
                                   placeholder="Dirección del cliente...">
                            @error('clienteDireccion')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

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
    {{--  MODAL — Confirmar Eliminación                        --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarCliente" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <p class="fs-5 mb-1">
                        ¿Está seguro que desea <strong>eliminar este cliente</strong>?
                    </p>
                    <p class="text-muted small">
                        El cliente no aparecerá más en el sistema. Esta acción no se puede deshacer.
                    </p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-danger fw-semibold"
                            wire:click="eliminar"
                            wire:loading.attr="disabled"
                            wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-trash me-1"></i> Sí, eliminar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Eliminando...
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
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Clientes</h5>
                    <small class="text-muted">Gestiona el listado de clientes registrados en el sistema.</small>
                    <div class="mt-1">
                        <span class="badge text-dark fw-normal" style="background:#eef8f0;font-size:.72rem;">
                            <i class="fa-solid fa-circle-info me-1 text-success"></i>
                            Un cliente registrado en cualquier sede puede usarse para ventas en todas las sedes del sistema.
                        </span>
                    </div>
                </div>
                @can('gestion_de_clientes.crear')
                <button class="btn btn-success text-white fw-semibold"
                        wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Cliente
                </button>
                @endcan
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-center mt-3">

                {{-- Registros por página --}}
                <div class="col-auto d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width: auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="text-muted small mb-0 text-nowrap">registros</label>
                </div>

                {{-- Filtro empresa (solo superadmin) --}}
                @if(isset($empresas) && $empresas->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroEmpresa"
                            class="form-select form-select-sm" style="min-width:180px;">
                        <option value="0">— Todas las empresas —</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial ?? $emp->empresa_razon_social }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Buscador --}}
                <div class="col-auto ms-auto">
                    <div class="input-group" style="min-width:280px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text"
                               wire:model.live.debounce.400ms="buscar"
                               class="form-control border-start-0 bg-light"
                               placeholder="Buscar nombre, documento, teléfono...">
                        @if($buscar)
                            <button class="btn btn-outline-secondary" type="button"
                                    wire:click="$set('buscar', '')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Exportar Excel --}}
                <div class="col-auto">
                    <button type="button"
                            class="btn btn-outline-success fw-semibold"
                            wire:click="exportarExcel"
                            wire:loading.attr="disabled"
                            wire:target="exportarExcel"
                            title="Exportar clientes filtrados a Excel">
                        <span wire:loading.remove wire:target="exportarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1">Exportar Excel
                        </span>
                        <span wire:loading wire:target="exportarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Generando...
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div class="card-body">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible mt-3 mb-2" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mt-3 mb-2" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Tabla --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr class="encabezado_tabla_color text-center">
                        <th class="ps-3">#</th>
                        <th>Tipo Doc.</th>
                        <th style="cursor:pointer;" wire:click="ordenar('cliente_numero')">
                            N° Documento
                            @if($ordenColumna === 'cliente_numero')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                        <th style="cursor:pointer;" wire:click="ordenar('cliente_nombre')">
                            Nombre / Razón Social
                            @if($ordenColumna === 'cliente_nombre')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                        <th style="cursor:pointer;" wire:click="ordenar('cliente_telefono')">
                            Teléfono
                            @if($ordenColumna === 'cliente_telefono')
                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($clientes->count())
                        @foreach($clientes as $index => $cli)
                            <tr>
                                <td class="ps-3 text-center text-muted">
                                    {{ $clientes->firstItem() + $index }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $cli->tipo_documento_identidad_abr }}
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">
                                    {{ $cli->cliente_numero }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        {{ $cli->id_tipo_documento == 4 ? $cli->cliente_razonsocial : $cli->cliente_nombre }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    {{ $cli->cliente_telefono ?? '—' }}
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $cli->cliente_direccion ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @can('gestion_de_clientes.actualizar')
                                        <button class="btn btn-sm btn-primary"
                                                wire:click="abrirModalEditar({{ $cli->id_clientes }})"
                                                title="Editar cliente">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        @endcan
                                        @can('gestion_de_clientes.cambiar_estado')
                                        <button class="btn btn-sm btn-danger"
                                                wire:click="confirmarEliminar({{ $cli->id_clientes }})"
                                                title="Eliminar cliente">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-users-slash fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron clientes.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            {{-- Paginación + info de registros --}}
            @if($clientes->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $clientes->firstItem() }} - {{ $clientes->lastItem() }}
                        de {{ $clientes->total() }} registros
                    </small>
                    {{ $clientes->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    {{-- Loader --}}
    <div wire:loading wire:target="buscar, porPagina, ordenar, filtroEmpresa">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalCliente'));
        modal.show();
    });

    $wire.on('cerrarModal', () => {
        const modalEl = document.getElementById('modalCliente');
        const modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    });

    $wire.on('abrirModalEliminar', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalEliminarCliente'));
        modal.show();
    });

    $wire.on('cerrarModalEliminar', () => {
        const modalEl = document.getElementById('modalEliminarCliente');
        const modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    });
</script>
@endscript
