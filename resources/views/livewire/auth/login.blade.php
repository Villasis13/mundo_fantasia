<div>

    {{-- Encabezado del formulario --}}
    <div class="mb-5">
        <h4 class="fw-bold mb-1" style="color:#111827;font-size:1.45rem">Iniciar sesión</h4>
        <p class="text-muted mb-0" style="font-size:.9rem">Ingresa tus credenciales para acceder al sistema</p>
    </div>

    {{-- ── Error de autenticación ───────────────────────────────── --}}
    @if($errorLogin)
        <div class="alert alert-danger d-flex align-items-start gap-2 py-3 mb-4 border-0 rounded-3" role="alert"
             style="background:#fff1f2;border-left:4px solid #dc2626 !important;border-left-width:4px !important">
            <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0 text-danger"></i>
            <div class="small fw-medium" style="color:#991b1b">{{ $errorLogin }}</div>
        </div>
    @endif

    {{-- ── Formulario ───────────────────────────────────────────── --}}

    {{-- Usuario --}}
    <div class="mb-4" style="position:relative"
         x-data="{}"
         x-on:click.away="$wire.set('mostrarSugerencias', false)">
        <label for="username" class="form-label fw-semibold" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Nombre de usuario
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-user text-muted" style="font-size:.8rem"></i>
            </span>
            <input
                type="text"
                wire:model.live="username"
                id="username"
                class="form-control border-start-0 ps-1 @error('username') is-invalid @enderror"
                placeholder="Tu nombre de usuario"
                autocomplete="off"
                autofocus
                wire:keydown.enter="iniciarSesion"
                wire:keydown.escape="$wire.set('mostrarSugerencias', false)"
            />
            @error('username')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        {{-- Dropdown de sugerencias --}}
        @if($mostrarSugerencias && count($sugerenciasUsuarios))
        <div style="position:absolute;top:100%;left:0;right:0;z-index:9999;background:#fff;border:1px solid #d1d5db;border-top:none;border-radius:0 0 10px 10px;box-shadow:0 6px 16px rgba(0,0,0,.12);overflow:hidden">
            @foreach($sugerenciasUsuarios as $u)
            <div
                wire:click="seleccionarUsuario('{{ $u }}')"
                style="padding:.55rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.55rem;font-size:.88rem;color:#374151;border-bottom:1px solid #f3f4f6;transition:background .12s"
                onmouseover="this.style.background='#eff6ff'"
                onmouseout="this.style.background=''"
            >
                <i class="fa-solid fa-user-circle" style="font-size:.85rem;color:#6b7280"></i>
                <span>{{ $u }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Contraseña --}}
    <div class="mb-4">
        <label class="form-label fw-semibold" for="password"
               style="font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Contraseña
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-lock text-muted" style="font-size:.8rem"></i>
            </span>
            <input
                type="password"
                id="password"
                wire:model="password"
                class="form-control border-start-0 border-end-0 ps-1 @error('password') is-invalid @enderror"
                placeholder="••••••••"
                autocomplete="current-password"
                wire:keydown.enter="iniciarSesion"
            />
            <button type="button" class="input-group-text bg-light" id="btnTogglePass" title="Mostrar/ocultar contraseña">
                <i class="fa-solid fa-eye text-muted" id="icon-password" style="font-size:.8rem"></i>
            </button>
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Recordar sesión + enlace de recuperación --}}
    <div class="mb-5 d-flex align-items-center justify-content-between">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" wire:model="remember" id="remember" />
            <label class="form-check-label small text-muted" for="remember">
                Mantener sesión iniciada
            </label>
        </div>
        <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color:#1a56db">
            ¿Olvidaste tu contraseña?
        </a>
    </div>

    {{-- Botón de ingreso --}}
    <button
        class="btn btn-primary w-100 fw-semibold"
        style="padding:.75rem;border-radius:10px;font-size:.95rem"
        wire:click="iniciarSesion"
        wire:loading.attr="disabled"
        wire:target="iniciarSesion"
        type="button"
    >
        <span wire:loading.remove wire:target="iniciarSesion">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Ingresar al sistema
        </span>
        <span wire:loading wire:target="iniciarSesion">
            <span class="spinner-border spinner-border-sm me-2"></span>Verificando credenciales...
        </span>
    </button>

    {{-- Pie del formulario --}}
    <p class="text-center text-muted mt-4 mb-0" style="font-size:.8rem">
        ¿Problemas para acceder? Contacta al administrador del sistema.
    </p>

</div>

@script
<script>
    document.getElementById('btnTogglePass').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('icon-password');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye',       !isHidden);
        icon.classList.toggle('fa-eye-slash',  isHidden);
    });

    $wire.on('focusPassword', () => {
        document.getElementById('password').focus();
    });
</script>
@endscript
