<div>

    {{-- ══ COVER BANNER ══════════════════════════════════════════════════════ --}}
    <div class="pf-cover position-relative mb-0">
        <div class="pf-cover-inner"></div>
    </div>

    {{-- ══ LAYOUT PRINCIPAL ══════════════════════════════════════════════════ --}}
    <div class="row g-4 pf-layout">

        {{-- ── TARJETA IZQUIERDA: Avatar + info del usuario ────────────────── --}}
        <div class="col-xl-3 col-lg-4">
            <div class="pf-card pf-profile-card text-center">

                {{-- Avatar con botón upload --}}
                <div class="pf-avatar-wrap">
                    <div class="pf-avatar-ring">
                        @if($fotoUsuario)
                            <img src="{{ $fotoUsuario->temporaryUrl() }}" alt="Preview" class="pf-avatar-img">
                        @else
                            <img src="{{ asset($fotoActual) }}" alt="Foto de perfil" class="pf-avatar-img"
                                 onerror="this.src='{{ asset('sin-fotografia.png') }}'">
                        @endif
                    </div>
                    <label for="fotoInput" class="pf-camera-btn" title="Cambiar foto">
                        <i class="fa-solid fa-camera text-white"></i>
                    </label>
                    <input type="file" id="fotoInput" wire:model="fotoUsuario" accept="image/*" class="d-none">
                </div>

                @error('fotoUsuario')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                {{-- Nombre completo --}}
                <div class="pf-user-name mt-3">
                    {{ $persona->persona_nombre ?? '' }}
                    {{ $persona->persona_apellido_paterno ?? '' }}
                </div>
                <div class="pf-user-username text-muted mb-3">{{ $user->username }}</div>

                <hr class="my-3">

                {{-- Rol (solo lectura) --}}
                <div class="pf-info-row">
                    <span class="pf-info-icon"><i class="fa-solid fa-shield-halved text-primary"></i></span>
                    <span class="pf-info-label">Rol</span>
                    <span class="pf-badge-rol">{{ $rolNombre }}</span>
                </div>

                {{-- Sucursales (solo lectura) --}}
                @if($sucursales->count())
                <div class="pf-info-row align-items-start mt-2">
                    <span class="pf-info-icon mt-1"><i class="fa-solid fa-store text-success"></i></span>
                    <span class="pf-info-label mt-1">Sucursal{{ $sucursales->count() > 1 ? 'es' : '' }}</span>
                    <div class="d-flex flex-column gap-1 align-items-end flex-grow-1">
                        @foreach($sucursales as $suc)
                            <span class="pf-badge-suc">{{ $suc }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Email (solo lectura) --}}
                <div class="pf-info-row mt-2">
                    <span class="pf-info-icon"><i class="fa-solid fa-envelope text-info"></i></span>
                    <span class="pf-info-label">Correo</span>
                    <span class="pf-info-val text-truncate" style="max-width:140px" title="{{ $user->email }}">
                        {{ $user->email }}
                    </span>
                </div>

            </div>
        </div>

        {{-- ── PANEL DERECHO: Formulario ────────────────────────────────────── --}}
        <div class="col-xl-9 col-lg-8">
            <div class="pf-card">

                {{-- Tabs ──────────────────────────────────────────────────── --}}
                <div class="pf-tabs">
                    <button type="button"
                            class="pf-tab {{ $tab === 'datos' ? 'pf-tab-active' : '' }}"
                            wire:click="$set('tab','datos')">
                        <i class="fa-solid fa-user me-2"></i>Datos personales
                    </button>
                    <button type="button"
                            class="pf-tab {{ $tab === 'password' ? 'pf-tab-active' : '' }}"
                            wire:click="$set('tab','password')">
                        <i class="fa-solid fa-lock me-2"></i>Contraseña
                    </button>
                </div>

                <div class="pf-form-body">

                    {{-- ── TAB: DATOS PERSONALES ──────────────────────────── --}}
                    @if($tab === 'datos')
                    <form wire:submit.prevent="actualizarDatos">

                        {{-- Foto preview cargando --}}
                        <div wire:loading wire:target="fotoUsuario" class="pf-upload-loading mb-3">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Procesando imagen...
                        </div>

                        <div class="row g-3">

                            {{-- Nombre --}}
                            <div class="col-md-4">
                                <label class="pf-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" wire:model.lazy="nombre"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       placeholder="Ingresa tu nombre">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Apellido paterno --}}
                            <div class="col-md-4">
                                <label class="pf-label">Apellido paterno <span class="text-danger">*</span></label>
                                <input type="text" wire:model.lazy="apellidoPaterno"
                                       class="form-control @error('apellidoPaterno') is-invalid @enderror"
                                       placeholder="Apellido paterno">
                                @error('apellidoPaterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Apellido materno --}}
                            <div class="col-md-4">
                                <label class="pf-label">Apellido materno</label>
                                <input type="text" wire:model.lazy="apellidoMaterno"
                                       class="form-control @error('apellidoMaterno') is-invalid @enderror"
                                       placeholder="Apellido materno">
                                @error('apellidoMaterno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- DNI --}}
                            <div class="col-md-3">
                                <label class="pf-label">DNI</label>
                                <input type="text" wire:model.lazy="dni" maxlength="20"
                                       class="form-control @error('dni') is-invalid @enderror"
                                       placeholder="Nro. documento">
                                @error('dni') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-md-5">
                                <label class="pf-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" wire:model.lazy="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="correo@empresa.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Username --}}
                            <div class="col-md-4">
                                <label class="pf-label">Nombre de usuario <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">@</span>
                                    <input type="text" wire:model.lazy="username"
                                           class="form-control @error('username') is-invalid @enderror"
                                           placeholder="usuario123">
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Foto de perfil --}}
                            <div class="col-12">
                                <label class="pf-label">Foto de perfil</label>
                                <div class="pf-upload-area"
                                     onclick="document.getElementById('fotoInput').click()">
                                    @if($fotoUsuario)
                                        <i class="fa-solid fa-circle-check text-success me-2"></i>
                                        <span class="text-success fw-semibold">
                                            {{ $fotoUsuario->getClientOriginalName() }}
                                        </span>
                                        <span class="text-muted ms-2 small">
                                            ({{ number_format($fotoUsuario->getSize() / 1024, 0) }} KB)
                                        </span>
                                    @else
                                        <i class="fa-solid fa-cloud-arrow-up me-2 text-muted"></i>
                                        <span class="text-muted">Clic para cambiar foto &nbsp;·&nbsp; PNG, JPG, WEBP — máx. 2 MB</span>
                                    @endif
                                </div>
                            </div>

                        </div>

                        {{-- Acciones --}}
                        <div class="pf-form-footer mt-4">
                            <div class="text-muted small">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Los campos con <span class="text-danger">*</span> son obligatorios.
                                El rol y las sucursales solo pueden ser modificados por un administrador.
                            </div>
                            <button type="submit" class="btn btn-primary px-4"
                                    wire:loading.attr="disabled" wire:target="actualizarDatos">
                                <span wire:loading.remove wire:target="actualizarDatos">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios
                                </span>
                                <span wire:loading wire:target="actualizarDatos">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Guardando...
                                </span>
                            </button>
                        </div>

                    </form>
                    @endif

                    {{-- ── TAB: CONTRASEÑA ─────────────────────────────────── --}}
                    @if($tab === 'password')
                    <form wire:submit.prevent="cambiarContrasena">

                        <div class="pf-security-banner mb-4">
                            <i class="fa-solid fa-shield-check me-2 text-primary"></i>
                            <span>Usa una contraseña segura de al menos <strong>6 caracteres</strong> con combinación de letras y números.</span>
                        </div>

                        <div class="row g-3" style="max-width:560px">

                            {{-- Contraseña actual --}}
                            <div class="col-12">
                                <label class="pf-label">Contraseña actual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                                    <input type="password" wire:model.lazy="passwordActual"
                                           class="form-control @error('passwordActual') is-invalid @enderror"
                                           placeholder="••••••••">
                                    @error('passwordActual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Nueva contraseña --}}
                            <div class="col-12">
                                <label class="pf-label">Nueva contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                                    <input type="password" wire:model.lazy="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Mínimo 6 caracteres">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Confirmar contraseña --}}
                            <div class="col-12">
                                <label class="pf-label">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                                    <input type="password" wire:model.lazy="passwordConfirm"
                                           class="form-control @error('passwordConfirm') is-invalid @enderror"
                                           placeholder="Repite la contraseña">
                                    @error('passwordConfirm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                        </div>

                        <div class="pf-form-footer mt-4">
                            <div></div>
                            <button type="submit" class="btn btn-warning px-4 fw-semibold"
                                    wire:loading.attr="disabled" wire:target="cambiarContrasena">
                                <span wire:loading.remove wire:target="cambiarContrasena">
                                    <i class="fa-solid fa-rotate me-2"></i>Actualizar contraseña
                                </span>
                                <span wire:loading wire:target="cambiarContrasena">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Actualizando...
                                </span>
                            </button>
                        </div>

                    </form>
                    @endif

                </div>{{-- /pf-form-body --}}
            </div>{{-- /pf-card --}}
        </div>{{-- /col derecho --}}

    </div>{{-- /row --}}

</div>

<style>
    /* ── Cover banner ─────────────────────────────────────────────── */
    .pf-cover {
        height: 180px;
        border-radius: 18px 18px 0 0;
        overflow: hidden;
        margin-bottom: 0;
    }
    .pf-cover-inner {
        width: 100%; height: 100%;
        background: linear-gradient(120deg, #1a1aad 0%, #2d2de8 45%, #1a7fd4 75%, #38c2f5 100%);
        position: relative;
    }
    .pf-cover-inner::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            radial-gradient(ellipse at 90% 40%, rgba(180,232,0,.22) 0%, transparent 50%),
            radial-gradient(ellipse at 10% 90%, rgba(232,0,180,.18) 0%, transparent 45%);
    }
    .pf-cover-inner::after {
        content: '';
        position: absolute;
        right: -30px; bottom: -50px;
        width: 260px; height: 260px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }

    /* ── Layout: eliminar gap del row ─────────────────────────────── */
    .pf-layout {
        margin-top: 0 !important;
        padding: 0 !important;
        --bs-gutter-x: 0 !important;
        --bs-gutter-y: 0 !important;
    }

    /* ── Card base ────────────────────────────────────────────────── */
    .pf-card {
        background: #fff;
        border: 1px solid #e2e6f0;
        border-radius: 0;
        box-shadow: none;
        overflow: visible;
    }

    /* ── Tarjeta izquierda ────────────────────────────────────────── */
    .pf-profile-card {
        padding: 0 22px 28px;
        border-radius: 0 0 0 18px;
        border-right: none;
        text-align: center;
        box-shadow: 0 4px 24px rgba(26,26,173,.06);
        overflow: visible;
        position: relative;
    }

    /* ── Tarjeta derecha ──────────────────────────────────────────── */
    .col-xl-9 > .pf-card,
    .col-lg-8 > .pf-card {
        border-radius: 0 0 18px 0;
        box-shadow: 0 4px 24px rgba(26,26,173,.06);
        overflow: hidden;
    }

    /* ── CLAVE: el avatar sube para cruzar el borde cover/card ───── */
    .pf-avatar-wrap {
        position: relative;
        display: inline-block;
        margin-top: -50px;
        z-index: 10;
    }
    .pf-avatar-ring {
        width: 96px; height: 96px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 20px rgba(26,26,173,.2);
        overflow: hidden;
        margin: 0 auto;
        background: #e2e6f0;
    }
    .pf-avatar-img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .pf-camera-btn {
        position: absolute;
        bottom: 2px; right: -2px;
        width: 28px; height: 28px;
        border-radius: 50%;
        background: #1a1aad;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px;
        cursor: pointer;
        border: 3px solid #fff;
        transition: background .2s;
        z-index: 11;
    }
    .pf-camera-btn:hover { background: #1414a0; }

    /* ── Nombre / username ────────────────────────────────────────── */
    .pf-user-name {
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
        line-height: 1.3;
        margin-top: 12px !important;
    }
    .pf-user-username {
        font-size: .78rem;
        color: #9ca3af;
        margin-bottom: 16px !important;
        margin-top: 2px !important;
    }

    /* ── Fila de info ─────────────────────────────────────────────── */
    .pf-info-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .82rem;
        width: 100%;
        padding: 5px 0;
        text-align: left;
    }
    .pf-info-icon { width: 20px; flex-shrink: 0; text-align: center; }
    .pf-info-label { color: #9ca3af; font-size: .75rem; white-space: nowrap; flex-shrink: 0; }
    .pf-info-val {
        color: #374151; font-weight: 600; margin-left: auto;
        font-size: .78rem; max-width: 130px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .pf-badge-rol {
        margin-left: auto;
        background: #eef2ff; color: #1a1aad;
        border: 1.5px solid #c7d2fe; border-radius: 20px;
        padding: 3px 12px; font-size: .72rem; font-weight: 700; white-space: nowrap;
    }
    .pf-badge-suc {
        background: #f0fdf4; color: #15803d;
        border: 1.5px solid #bbf7d0; border-radius: 20px;
        padding: 3px 10px; font-size: .72rem; font-weight: 700; text-align: right;
    }

    /* ── Tabs ─────────────────────────────────────────────────────── */
    .pf-tabs {
        display: flex;
        border-bottom: 1px solid #e2e6f0;
        padding: 0 28px;
        background: #fafbfd;
    }
    .pf-tab {
        padding: 15px 18px;
        border: none; background: none;
        color: #9ca3af; font-size: .855rem; font-weight: 600;
        border-bottom: 2px solid transparent;
        cursor: pointer; margin-bottom: -1px;
        transition: color .15s, border-color .15s;
        display: flex; align-items: center; gap: 7px;
    }
    .pf-tab:hover { color: #1a1aad; }
    .pf-tab-active { color: #1a1aad; border-bottom-color: #1a1aad; }

    /* ── Cuerpo del formulario ────────────────────────────────────── */
    .pf-form-body { padding: 28px 28px 6px; }

    /* ── Labels ───────────────────────────────────────────────────── */
    .pf-label {
        display: block; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em;
        color: #9ca3af; margin-bottom: 6px;
    }

    /* ── Inputs ───────────────────────────────────────────────────── */
    .pf-form-body .form-control {
        height: 40px; border-radius: 9px;
        border: 1.5px solid #e2e6f0;
        font-size: .855rem; color: #111827; padding: 0 13px;
        transition: border-color .15s, box-shadow .15s;
    }
    .pf-form-body .form-control:focus {
        border-color: #1a1aad;
        box-shadow: 0 0 0 3px rgba(26,26,173,.09);
    }
    .pf-form-body .input-group-text {
        border-radius: 9px 0 0 9px;
        border: 1.5px solid #e2e6f0; border-right: none;
        background: #f8f9fc; color: #9ca3af;
        font-weight: 700; font-size: .82rem;
    }
    .pf-form-body .input-group .form-control {
        border-radius: 0 9px 9px 0; border-left: none;
    }
    .pf-form-body .input-group:focus-within .input-group-text,
    .pf-form-body .input-group:focus-within .form-control { border-color: #1a1aad; }
    .pf-form-body .input-group:focus-within .form-control { box-shadow: none; }

    /* ── Row gap ──────────────────────────────────────────────────── */
    .pf-form-body .row { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }

    /* ── Upload ───────────────────────────────────────────────────── */
    .pf-upload-area {
        border: 2px dashed #e2e6f0; border-radius: 10px;
        padding: 14px 18px; cursor: pointer;
        transition: border-color .2s, background .2s, color .2s;
        font-size: .855rem; color: #9ca3af; background: #fafbfd;
        display: flex; align-items: center; gap: 8px;
    }
    .pf-upload-area:hover { border-color: #1a1aad; background: #eef2ff; color: #1a1aad; }
    .pf-upload-loading {
        background: #eef2ff; border: 1px solid #c7d2fe;
        border-radius: 8px; padding: 8px 14px;
        font-size: .85rem; color: #1a1aad;
        display: flex; align-items: center;
    }

    /* ── Banner seguridad ─────────────────────────────────────────── */
    .pf-security-banner {
        background: #eef2ff; border: 1px solid #c7d2fe;
        border-radius: 10px; padding: 12px 16px;
        font-size: .855rem; color: #1a1aad;
        display: flex; align-items: center;
    }

    /* ── Footer ───────────────────────────────────────────────────── */
    .pf-form-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding: 18px 28px 20px;
        border-top: 1px solid #f0f2f8;
        gap: 12px; flex-wrap: wrap; margin-top: 16px;
    }

    /* ── Botones ──────────────────────────────────────────────────── */
    .pf-form-footer .btn-primary {
        background: #1a1aad; border-color: #1a1aad;
        font-weight: 700; font-size: .875rem;
        padding: 10px 24px; border-radius: 9px;
        transition: background .15s, transform .1s;
    }
    .pf-form-footer .btn-primary:hover {
        background: #1414a0; border-color: #1414a0; transform: translateY(-1px);
    }
    .pf-form-footer .btn-warning {
        font-weight: 700; font-size: .875rem;
        padding: 10px 24px; border-radius: 9px;
        transition: transform .1s;
    }
    .pf-form-footer .btn-warning:hover { transform: translateY(-1px); }

    /* ── Responsive ───────────────────────────────────────────────── */
    @media (max-width: 991px) {
        .pf-profile-card {
            border-radius: 0;
            border-right: 1px solid #e2e6f0;
            border-bottom: none;
        }
        .col-xl-9 > .pf-card,
        .col-lg-8 > .pf-card { border-radius: 0 0 18px 18px; }
    }
</style>

@script
    <script>
        $wire.on('notificar', ({ mensaje, tipo }) => {
            if (typeof respuesta === 'function') respuesta(mensaje, tipo);
        });
    </script>
@endscript
