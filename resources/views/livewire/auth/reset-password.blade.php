<div>

    {{-- Encabezado --}}
    <div class="mb-5">
        <h4 class="fw-bold mb-1" style="color:#111827;font-size:1.45rem">Nueva contraseña</h4>
        <p class="text-muted mb-0" style="font-size:.9rem">Establece una nueva contraseña segura para tu cuenta.</p>
    </div>

    {{-- Error de token --}}
    @error('token')
        <div class="alert alert-danger d-flex align-items-start gap-2 py-3 mb-4 border-0 rounded-3"
             style="background:#fff1f2;border-left:4px solid #dc2626 !important;border-left-width:4px !important">
            <i class="fa-solid fa-circle-exclamation mt-1 flex-shrink-0 text-danger"></i>
            <div class="small fw-medium" style="color:#991b1b">{{ $message }}</div>
        </div>
    @enderror

    {{-- Correo --}}
    <div class="mb-4">
        <label for="email" class="form-label fw-semibold"
               style="font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Correo electrónico
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-envelope text-muted" style="font-size:.8rem"></i>
            </span>
            <input
                type="email"
                wire:model="email"
                id="email"
                class="form-control border-start-0 ps-1 @error('email') is-invalid @enderror"
                placeholder="tucorreo@ejemplo.com"
                autocomplete="email"
            />
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Nueva contraseña --}}
    <div class="mb-4">
        <label for="password" class="form-label fw-semibold"
               style="font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Nueva contraseña
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-lock text-muted" style="font-size:.8rem"></i>
            </span>
            <input
                type="password"
                wire:model="password"
                id="password"
                class="form-control border-start-0 border-end-0 ps-1 @error('password') is-invalid @enderror"
                placeholder="Mínimo 8 caracteres"
                autocomplete="new-password"
                id="password"
            />
            <button type="button" class="input-group-text bg-light" id="btnTogglePass" title="Mostrar/ocultar">
                <i class="fa-solid fa-eye text-muted" id="icon-password" style="font-size:.8rem"></i>
            </button>
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Confirmar contraseña --}}
    <div class="mb-5">
        <label for="passwordConfirm" class="form-label fw-semibold"
               style="font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">
            Confirmar contraseña
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fa-solid fa-lock text-muted" style="font-size:.8rem"></i>
            </span>
            <input
                type="password"
                wire:model="passwordConfirm"
                id="passwordConfirm"
                class="form-control border-start-0 ps-1 @error('passwordConfirm') is-invalid @enderror"
                placeholder="Repite la contraseña"
                autocomplete="new-password"
            />
            @error('passwordConfirm')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button
        class="btn btn-primary w-100 fw-semibold"
        style="padding:.75rem;border-radius:10px;font-size:.95rem"
        wire:click="restablecer"
        wire:loading.attr="disabled"
        wire:target="restablecer"
        type="button"
    >
        <span wire:loading.remove wire:target="restablecer">
            <i class="fa-solid fa-key me-2"></i>Restablecer contraseña
        </span>
        <span wire:loading wire:target="restablecer">
            <span class="spinner-border spinner-border-sm me-2"></span>Procesando...
        </span>
    </button>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
            <i class="fa-solid fa-arrow-left me-1"></i>Volver al inicio de sesión
        </a>
    </div>

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
</script>
@endscript
