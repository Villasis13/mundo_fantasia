<div>

<style>
    .suc-label:has(input:checked) {
        border-color: #0d6efd !important;
        background: rgba(13,110,253,.07) !important;
    }
    .suc-label:has(input:checked) .suc-label-text {
        color: #0d6efd;
        font-weight: 600;
    }
    .suc-label { transition: border-color .15s, background .15s; }
</style>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Usuario                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalUsuario" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'user-pen' : 'user-plus' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body px-4 pt-2 pb-3">
                    <small class="text-muted">
                        {{ $modoEdicion ? 'Modifica los datos del usuario seleccionado.' : 'Completa los campos para registrar un nuevo usuario.' }}
                    </small>

                    <div class="row mt-3 g-3">

                        {{-- Foto + preview --}}
                        <div class="col-12 d-flex align-items-center gap-3">
                            @if($fotoUsuario)
                                <img src="{{ $fotoUsuario->temporaryUrl() }}"
                                     class="rounded-circle border"
                                     style="width:72px;height:72px;object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:72px;height:72px;">
                                    <i class="fa-solid fa-user fa-xl text-muted opacity-50"></i>
                                </div>
                            @endif
                            <div>
                                <label class="form-label fw-semibold small text-secondary mb-1">Foto de perfil</label>
                                <input type="file"
                                       wire:model="fotoUsuario"
                                       class="form-control form-control-sm @error('fotoUsuario') is-invalid @enderror"
                                       accept="image/*">
                                @error('fotoUsuario')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">JPG, PNG, WebP. Máx. 2 MB.</div>
                            </div>
                        </div>

                        {{-- DNI con consulta automática --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                N° DNI <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       wire:model.live.debounce.600ms="dni"
                                       class="form-control @error('dni') is-invalid @enderror"
                                       placeholder="Ej: 12345678"
                                       maxlength="8">
                                <span class="input-group-text bg-light">
                                    @if($buscandoDni)
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    @else
                                        <i class="fa-solid fa-id-card text-muted small"></i>
                                    @endif
                                </span>
                            </div>
                            @error('dni')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @if($dniMensaje && !$errors->has('dni'))
                                <div class="small mt-1 {{ $dniMensajeTipo === 'success' ? 'text-success' : 'text-danger' }}">
                                    <i class="fa-solid fa-{{ $dniMensajeTipo === 'success' ? 'circle-check' : 'circle-xmark' }} me-1"></i>
                                    {{ $dniMensaje }}
                                </div>
                            @endif
                        </div>

                        {{-- Nombre --}}
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   placeholder="Ej: Juan">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Apellido Paterno --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Apellido Paterno <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="apellidoPaterno"
                                   class="form-control @error('apellidoPaterno') is-invalid @enderror"
                                   placeholder="Ej: García">
                            @error('apellidoPaterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Apellido Materno --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Apellido Materno</label>
                            <input type="text"
                                   wire:model="apellidoMaterno"
                                   class="form-control @error('apellidoMaterno') is-invalid @enderror"
                                   placeholder="Ej: López">
                            @error('apellidoMaterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Correo electrónico <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   wire:model="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="usuario@ejemplo.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre de usuario <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   wire:model="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   placeholder="Ej: jgarcia">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Rol --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Rol <span class="text-danger">*</span>
                            </label>
                            <select wire:model="rolId"
                                    class="form-select @error('rolId') is-invalid @enderror">
                                <option value="">Seleccionar rol...</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                                @endforeach
                            </select>
                            @error('rolId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Contraseñas --}}
                        <div class="col-12">
                            <hr class="my-1">
                            <small class="text-muted fw-semibold">
                                <i class="fa-solid fa-lock me-1"></i>
                                Contraseña {{ $modoEdicion ? '(dejar en blanco para no cambiar)' : '' }}
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Contraseña @if(!$modoEdicion)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="password"
                                   wire:model="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mínimo 6 caracteres">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Repetir contraseña @if(!$modoEdicion)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="password"
                                   wire:model="passwordConfirm"
                                   class="form-control @error('passwordConfirm') is-invalid @enderror"
                                   placeholder="Repite la contraseña">
                            @error('passwordConfirm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- ── Sección Empresa / Tiendas ────────────────────────── --}}
                        <div class="col-12">
                            <hr class="my-2">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="rounded-2 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:32px;height:32px;">
                                    <i class="fa-solid fa-store text-white" style="font-size:.8rem;"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold small">Acceso a tiendas <span class="text-danger">*</span></p>
                                    <p class="mb-0 text-muted" style="font-size:.72rem;">
                                        Asigna las tiendas que podrá gestionar este usuario.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Selector de empresa (solo superadmin) --}}
                        @if($esSuperAdmin)
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                <i class="fa-solid fa-building me-1"></i>Empresa <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="empresaIdModal" class="form-select @error('empresaIdModal') is-invalid @enderror">
                                <option value="0">— Selecciona una empresa —</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                            @error('empresaIdModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(!$errors->has('empresaIdModal'))
                                <div class="form-text">Elige la empresa para ver y asignar sus tiendas.</div>
                            @endif
                        </div>
                        @endif

                        {{-- Checkboxes de tiendas --}}
                        <div class="col-12">
                            @if($esSuperAdmin && !$empresaIdModal)
                                <div class="border rounded-3 p-4 text-center text-muted bg-light"
                                     style="border-style:dashed !important;">
                                    <i class="fa-solid fa-building fa-lg mb-2 d-block opacity-25"></i>
                                    <small>Primero selecciona una empresa para ver sus tiendas.</small>
                                </div>
                            @elseif($tiendasPorEmpresa->isEmpty())
                                <div class="text-muted small fst-italic">
                                    <i class="fa-solid fa-circle-info me-1"></i>No hay tiendas disponibles.
                                </div>
                            @else
                                <div class="border rounded-3 overflow-hidden @error('tiendasSeleccionadas') border-danger @enderror">
                                    @foreach($tiendasPorEmpresa as $empresaId => $tiendasGrupo)
                                        @if($esAdmin)
                                        <div class="bg-light border-bottom px-3 py-2">
                                            <span class="fw-semibold small text-secondary">
                                                <i class="fa-solid fa-building me-1"></i>
                                                {{ $tiendasGrupo->first()->empresa_nombrecomercial }}
                                            </span>
                                        </div>
                                        @endif
                                        <div class="p-3 d-flex flex-wrap gap-2">
                                            @foreach($tiendasGrupo as $tienda)
                                                <label for="tienda_{{ $tienda->id_tienda }}"
                                                       class="suc-label d-flex align-items-center gap-2 border rounded-2 px-3 py-2 bg-white"
                                                       style="cursor:pointer;min-width:130px;">
                                                    <input class="form-check-input m-0 flex-shrink-0" type="checkbox"
                                                           wire:model="tiendasSeleccionadas"
                                                           value="{{ $tienda->id_tienda }}"
                                                           id="tienda_{{ $tienda->id_tienda }}">
                                                    <span class="suc-label-text small d-flex flex-column">
                                                        <span>
                                                            <i class="fa-solid fa-store me-1 text-muted" style="font-size:.7rem;"></i>
                                                            {{ $tienda->tienda_nombre }}
                                                        </span>
                                                        @php
                                                            $tipoTienda = match((int)($tienda->tienda_tipo ?? 1)) {
                                                                1 => ['label' => 'Tienda',   'class' => 'bg-success'],
                                                                2 => ['label' => 'Sucursal', 'class' => 'bg-primary'],
                                                                default => ['label' => 'Tienda', 'class' => 'bg-success'],
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $tipoTienda['class'] }}" style="font-size:.6rem;">{{ $tipoTienda['label'] }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                @error('tiendasSeleccionadas')
                                    <div class="text-danger small mt-1">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            @endif
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
    <div class="modal fade" id="modalEstadoUsuario" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
                        {{ $nuevoEstado === 0 ? '¿Deshabilitar este usuario?' : '¿Habilitar este usuario?' }}
                    </h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">
                        @if($nuevoEstado === 0)
                            El usuario no podrá iniciar sesión en el sistema.
                        @else
                            El usuario recuperará acceso al sistema.
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
    {{--  CARD PRINCIPAL                                            --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">

            {{-- Título y botón nuevo --}}
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-users me-2 text-primary"></i>Usuarios
                    </h5>
                    <small class="text-muted">Gestión de usuarios del sistema.</small>
                </div>
                @can('gestion_usuarios.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-user-plus me-1"></i> Nuevo Usuario
                </button>
                @endcan
            </div>

            {{-- ── Barra de controles: mostrar + filtros + búsqueda ── --}}
            <div class="d-flex align-items-center flex-wrap gap-2 mt-4">

                {{-- Mostrar X registros --}}
                <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <label class="text-muted small mb-0 text-nowrap me-1">registros</label>

                <div class="vr opacity-25 d-none d-sm-block" style="height:24px;"></div>

                {{-- Empresa (superadmin) --}}
                @if($esSuperAdmin)
                    <select wire:model.live="filtroEmpresa"
                            class="form-select form-select-sm"
                            style="min-width:160px;max-width:200px;">
                        <option value="0">Todas las empresas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Sucursal --}}
                {{-- @if($sucursalesParaFiltro->isNotEmpty())
                    <select wire:model.live="filtroSucursal"
                            class="form-select form-select-sm"
                            style="min-width:150px;max-width:190px;">
                        <option value="0">Todas las sedes</option>
                        @foreach($sucursalesParaFiltro as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                @endif --}}

                {{-- Rol --}}
                <select wire:model.live="filtroRol"
                        class="form-select form-select-sm"
                        style="min-width:130px;max-width:160px;">
                    <option value="0">Todos los roles</option>
                    @foreach($rolesFiltro as $rol)
                        <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                    @endforeach
                </select>

                {{-- Estado --}}
                <select wire:model.live="filtroEstado"
                        class="form-select form-select-sm"
                        style="width:130px;">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>

                {{-- Búsqueda (empuja al extremo derecho) --}}
                <div class="input-group input-group-sm ms-auto" style="max-width:260px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar usuario...">
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
                                <span wire:click="ordenar('u.id_users')" role="button" class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='u.id_users' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='u.id_users' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:60px;">Foto</th>
                            <th style="min-width:120px;" class="text-start">
                                <span wire:click="ordenar('u.username')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Usuario
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='u.username' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='u.username' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:150px;" class="text-start">
                                <span wire:click="ordenar('p.persona_nombre')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Nombre
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='p.persona_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='p.persona_nombre' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:150px;" class="text-start">
                                <span wire:click="ordenar('u.email')" role="button" class="d-inline-flex align-items-center gap-1">
                                    Email
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='u.email' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }} {{ $ordenColumna!=='u.email' ? 'opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="min-width:100px;" class="text-start">Rol</th>
                            <th style="width:110px;">Estado</th>
                            <th style="width:90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $index => $u)
                            <tr>
                                <td class="ps-3 text-center text-muted small fw-semibold">
                                    {{ $usuarios->firstItem() + $index }}
                                </td>
                                <td class="text-center">
                                    @php $foto = $u->user_fotografia; @endphp
                                    @if($foto && $foto !== 'sin-fotografia.png')
                                        <img src="{{ asset($foto) }}"
                                             class="rounded-circle border"
                                             style="width:36px;height:36px;object-fit:cover;"
                                             alt="{{ $u->username }}">
                                    @else
                                        <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                                             style="width:36px;height:36px;">
                                            <i class="fa-solid fa-user text-muted" style="font-size:.8rem;"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $u->username }}</span>
                                </td>
                                <td>
                                    <span class="small">
                                        {{ $u->persona_nombre }}
                                        {{ $u->persona_apellido_paterno }}
                                        {{ $u->persona_apellido_materno }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $u->email }}</small>
                                </td>
                                <td>
                                    @if($u->rol_nombre)
                                        <span class="badge bg-primary bg-opacity-75 fw-normal">{{ $u->rol_nombre }}</span>
                                    @else
                                        <span class="text-muted small">Sin rol</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($u->users_estado == 1)
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
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @can('gestion_usuarios.actualizar')
                                        <button class="btn btn-sm btn-warning"
                                                wire:click="abrirModalEditar({{ $u->id_users }})"
                                                title="Editar">
                                            <i class="fa-solid fa-pencil text-white"></i>
                                        </button>
                                        @endcan
                                        @can('gestion_usuarios.cambiar_estado')
                                        @if($u->users_estado == 1)
                                            <button class="btn btn-sm btn-danger"
                                                    wire:click="confirmarCambiarEstado({{ $u->id_users }}, 0)"
                                                    title="Deshabilitar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="confirmarCambiarEstado({{ $u->id_users }}, 1)"
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
                                    <i class="fa-solid fa-users fa-2x mb-2 d-block opacity-25"></i>
                                    @if($buscar)
                                        No se encontraron usuarios que coincidan con <strong>"{{ $buscar }}"</strong>.
                                    @else
                                        No hay usuarios registrados.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($usuarios->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }}
                        de {{ $usuarios->total() }} registros
                    </small>
                    {{ $usuarios->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, ordenar, abrirModalNuevo, abrirModalEditar, guardar, cambiarEstado, filtroEmpresa, filtroSucursal, filtroRol, filtroEstado">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalUsuario'));
        if (modal) modal.hide();
    });
    $wire.on('abrirModalEstado', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstadoUsuario')).show();
    });
    $wire.on('cerrarModalEstado', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEstadoUsuario'));
        if (modal) modal.hide();
    });
</script>
@endscript
