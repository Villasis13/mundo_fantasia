<div>

    {{-- Encabezado --}}
    <div class="mb-5">
        <h4 class="fw-bold mb-1" style="color:#111827;font-size:1.45rem">Recuperar contraseña</h4>
        <p class="text-muted mb-0" style="font-size:.9rem">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
    </div>

    {{-- Confirmación enviada --}}
    @if($enviado)
        <div class="alert border-0 rounded-3 py-4 px-4 mb-4"
             style="background:#f0fdf4;border-left:4px solid #16a34a !important;border-left-width:4px !important">
            <div class="d-flex align-items-start gap-2">
                <i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i>
                <div>
                    <div class="fw-semibold mb-1" style="color:#166534;font-size:.92rem">Correo enviado</div>
                    <div class="small" style="color:#15803d">
                        Revisa tu bandeja de entrada. Si la cuenta existe, recibirás el enlace en breve.
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver al inicio de sesión
            </a>
        </div>
    @else

        {{-- Formulario --}}
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
                    autofocus
                    wire:keydown.enter="enviar"
                />
                @error('email')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button
            class="btn btn-primary w-100 fw-semibold"
            style="padding:.75rem;border-radius:10px;font-size:.95rem"
            wire:click="enviar"
            wire:loading.attr="disabled"
            wire:target="enviar"
            type="button"
        >
            <span wire:loading.remove wire:target="enviar">
                <i class="fa-solid fa-paper-plane me-2"></i>Enviar enlace de recuperación
            </span>
            <span wire:loading wire:target="enviar">
                <span class="spinner-border spinner-border-sm me-2"></span>Enviando...
            </span>
        </button>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver al inicio de sesión
            </a>
        </div>

    @endif

</div>
