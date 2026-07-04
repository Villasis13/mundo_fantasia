<div class="container-fluid py-3">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i><span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-code-merge me-2 text-primary"></i>Unir Códigos de Producto</h5>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2 py-2 small">
        <i class="fa-solid fa-circle-info mt-1"></i>
        <div>Cuando la misma mercadería está duplicada con distinto código, indica el <b>Código Receptor</b> (el que se mantiene) y el <b>Código a Eliminar</b> (el duplicado). Al procesar, el stock e historial del eliminado se trasladan al receptor y el duplicado se elimina. <b>Esta acción no se puede deshacer.</b></div>
    </div>

    <div class="row g-3">
        {{-- ══════ PANEL RECEPTOR ══════ --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white fw-bold py-2">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Código Receptor (se mantiene)
                </div>
                <div class="card-body">
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text">Código</span>
                        <input type="text" class="form-control" wire:model="codigoReceptor"
                               wire:keydown.enter="buscarReceptor" placeholder="Ingrese el código y busque">
                        <button class="btn btn-success" wire:click="buscarReceptor">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    @if(count($receptorMatches) > 1 && !$receptorId)
                        <div class="mb-3">
                            <div class="small text-muted mb-1">Se encontraron varios productos con ese código. Selecciona uno:</div>
                            @foreach($receptorMatches as $m)
                                <button type="button" class="btn btn-sm btn-outline-success w-100 text-start mb-1"
                                        wire:click="seleccionarReceptor({{ $m['id_pro'] }})">
                                    <b>#{{ $m['id_pro'] }}</b> — {{ $m['nombre'] }} <span class="text-muted">(stock {{ number_format($m['stock'],2) }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($receptor)
                        @include('livewire.logistica.partials.unir-codigo-detalle', ['d' => $receptor, 'color' => 'success'])
                    @elseif(!count($receptorMatches))
                        <div class="text-center text-muted py-4"><i class="fa-solid fa-box fa-2x d-block mb-2 opacity-50"></i>Busca un código para ver el producto.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════ PANEL ELIMINAR ══════ --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-danger text-white fw-bold py-2">
                    <i class="fa-solid fa-trash me-2"></i>Código a Eliminar (duplicado)
                </div>
                <div class="card-body">
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text">Código</span>
                        <input type="text" class="form-control" wire:model="codigoEliminar"
                               wire:keydown.enter="buscarEliminar" placeholder="Ingrese el código y busque">
                        <button class="btn btn-danger" wire:click="buscarEliminar">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    @if(count($eliminarMatches) > 1 && !$eliminarId)
                        <div class="mb-3">
                            <div class="small text-muted mb-1">Se encontraron varios productos con ese código. Selecciona uno:</div>
                            @foreach($eliminarMatches as $m)
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 text-start mb-1"
                                        wire:click="seleccionarEliminar({{ $m['id_pro'] }})">
                                    <b>#{{ $m['id_pro'] }}</b> — {{ $m['nombre'] }} <span class="text-muted">(stock {{ number_format($m['stock'],2) }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($eliminar)
                        @include('livewire.logistica.partials.unir-codigo-detalle', ['d' => $eliminar, 'color' => 'danger'])
                    @elseif(!count($eliminarMatches))
                        <div class="text-center text-muted py-4"><i class="fa-solid fa-box fa-2x d-block mb-2 opacity-50"></i>Busca un código para ver el producto.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════ PROCESAR ══════ --}}
    <div class="text-center mt-3">
        <button class="btn btn-primary btn-lg fw-semibold px-5"
                data-bs-toggle="modal" data-bs-target="#modalConfirmarUnion"
                @disabled(!$receptorId || !$eliminarId || $receptorId === $eliminarId)>
            <i class="fa-solid fa-code-merge me-2"></i>Procesar Unión
        </button>
        @if($receptorId && $eliminarId && $receptorId === $eliminarId)
            <div class="text-danger small mt-2">El receptor y el eliminado no pueden ser el mismo producto.</div>
        @endif
    </div>

    {{-- ══════ MODAL CONFIRMACIÓN ══════ --}}
    <div class="modal fade" id="modalConfirmarUnion" wire:ignore.self tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Confirmar Unión de Códigos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Deseas unir los códigos? Se trasladará el stock e historial del duplicado al receptor y <b>se eliminará el producto duplicado</b>.</p>
                    @if($receptor && $eliminar)
                    <div class="d-flex align-items-center justify-content-center gap-3 my-3">
                        <span class="badge bg-danger p-2">Eliminar: {{ $eliminar['codigo'] }}<br><small>#{{ $eliminar['id_pro'] }}</small></span>
                        <i class="fa-solid fa-arrow-right text-muted"></i>
                        <span class="badge bg-success p-2">Receptor: {{ $receptor['codigo'] }}<br><small>#{{ $receptor['id_pro'] }}</small></span>
                    </div>
                    @endif
                    <div class="alert alert-warning small mb-0"><i class="fa-solid fa-circle-exclamation me-1"></i>Esta acción <b>no se puede deshacer</b>.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-semibold"
                            wire:click="procesar" wire:loading.attr="disabled" wire:target="procesar">
                        <span wire:loading.remove wire:target="procesar"><i class="fa-solid fa-code-merge me-1"></i>Sí, unir</span>
                        <span wire:loading wire:target="procesar"><span class="spinner-border spinner-border-sm me-1"></span> Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        // Cerrar el modal cuando el proceso termina (éxito o error)
        $wire.on('unionProcesada', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarUnion'));
            if (m) m.hide();
        });
    </script>
    @endscript
</div>
