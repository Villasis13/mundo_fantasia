<div>

    {{-- Error de autenticación --}}
    @if($errorLogin)
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 mb-3 border-0 rounded-3"
             style="background:#fff1f2;border-left:4px solid #dc2626 !important;">
            <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0 text-danger" style="font-size:.8rem"></i>
            <div class="small fw-medium" style="color:#991b1b">{{ $errorLogin }}</div>
        </div>
    @endif

    {{-- Usuario --}}
    <div class="mb-3" style="position:relative"
         x-data="{}"
         x-on:click.away="$wire.set('mostrarSugerencias', false)">
        <label for="username" class="form-label fw-semibold mb-1"
               style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Usuario
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-user text-muted" style="font-size:.78rem"></i>
            </span>
            <input type="text"
                   wire:model.live="username"
                   id="username"
                   class="form-control border-start-0 ps-1 @error('username') is-invalid @enderror"
                   placeholder="Nombre de usuario"
                   autocomplete="off"
                   autofocus
                   wire:keydown.enter="iniciarSesion"
                   wire:keydown.escape="$wire.set('mostrarSugerencias', false)" />
            @error('username')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        @if($mostrarSugerencias && count($sugerenciasUsuarios))
        <div style="position:absolute;top:100%;left:0;right:0;z-index:9999;background:#fff;border:1px solid #d1d5db;border-top:none;border-radius:0 0 8px 8px;box-shadow:0 6px 16px rgba(0,0,0,.12);overflow:hidden">
            @foreach($sugerenciasUsuarios as $u)
            <div wire:click="seleccionarUsuario('{{ $u }}')"
                 style="padding:.45rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:#374151;border-bottom:1px solid #f3f4f6"
                 onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                <i class="fa-solid fa-user-circle" style="font-size:.8rem;color:#6b7280"></i>
                <span>{{ $u }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Contraseña --}}
    <div class="mb-4">
        <label class="form-label fw-semibold mb-1" for="password"
               style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Contraseña
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-lock text-muted" style="font-size:.78rem"></i>
            </span>
            <input type="password"
                   id="password"
                   wire:model="password"
                   class="form-control border-start-0 border-end-0 ps-1 @error('password') is-invalid @enderror"
                   placeholder="••••••••"
                   autocomplete="current-password"
                   wire:keydown.enter="iniciarSesion" />
            <button type="button" class="input-group-text bg-light" id="btnTogglePass">
                <i class="fa-solid fa-eye text-muted" id="icon-password" style="font-size:.78rem"></i>
            </button>
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Botón --}}
    <button class="btn btn-primary w-100 fw-semibold"
            style="padding:.65rem;border-radius:8px;"
            wire:click="iniciarSesion"
            wire:loading.attr="disabled"
            wire:target="iniciarSesion"
            type="button">
        <span wire:loading.remove wire:target="iniciarSesion">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Ingresar
        </span>
        <span wire:loading wire:target="iniciarSesion">
            <span class="spinner-border spinner-border-sm me-2"></span>Verificando...
        </span>
    </button>

</div>

@script
<script>
    document.getElementById('btnTogglePass').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('icon-password');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
    });

    $wire.on('focusPassword', () => {
        document.getElementById('password').focus();
    });
</script>
@endscript
