<div>
    {{-- ── Alertas ─────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Modal confirmación ───────────────────────────────────── --}}
    <div class="modal fade" id="modalConfirmacionPendientes" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                        Confirmar acción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-6 mb-0">
                        {{ $mensajeConfirmacion ?: '¿Confirma la acción seleccionada?' }}
                    </p>
                </div>
                @php
                    $claseConfirmar = match($accionConfirmacion) {
                        'enviar_sunat', 'envio_masivo' => 'btn-success',
                        'estado_enviado', 'estado_anulado' => 'btn-warning',
                        default => 'btn-primary',
                    };
                    $iconoConfirmar = match($accionConfirmacion) {
                        'enviar_sunat' => 'check',
                        'envio_masivo' => 'upload',
                        'estado_enviado', 'estado_anulado' => 'rotate',
                        default => 'check',
                    };
                    $textoConfirmar = match($accionConfirmacion) {
                        'enviar_sunat' => 'Sí, enviar',
                        'envio_masivo' => 'Sí, enviar',
                        'estado_enviado' => 'Sí, marcar aceptado',
                        'estado_anulado' => 'Sí, marcar anulado',
                        default => 'Confirmar',
                    };
                @endphp
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn {{ $claseConfirmar }} fw-semibold"
                            wire:click="ejecutarConfirmacion"
                            wire:loading.attr="disabled"
                            wire:target="ejecutarConfirmacion">
                        <span wire:loading.remove wire:target="ejecutarConfirmacion">
                            <i class="fa-solid fa-{{ $iconoConfirmar }} me-1"></i> {{ $textoConfirmar }}
                        </span>
                        <span wire:loading wire:target="ejecutarConfirmacion">
                            <span class="spinner-border spinner-border-sm me-1"></span> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Card principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Historial de pendientes de declarar</h5>
                <small class="text-muted">Consulta y filtra las ventas registradas.</small>
            </div>

            <div class="row align-items-end g-2">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-lg-3 col-md-4 col-sm-12">
                    <label class="form-label mb-1">
                        <i class="fa-solid fa-building me-1 text-muted"></i>Empresa
                    </label>
                    <select wire:model.live="empresaSeleccionada"
                            class="form-select form-select-sm">
                        <option value="0">Todas las empresas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label mb-1">Tipo de Venta</label>
                    <select wire:model="tipoVenta" class="form-select form-select-sm">
                        <option value="">TODOS</option>
                        <option value="03">BOLETA</option>
                        <option value="01">FACTURA</option>
                        <option value="07">NOTA DE CRÉDITO</option>
                        <option value="08">NOTA DE DÉBITO</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label mb-1" for="tab1_desde">Desde:</label>
                    <input type="date" id="tab1_desde" wire:model="fechaInicio"
                           class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label mb-1" for="tab1_hasta">Hasta:</label>
                    <input type="date" id="tab1_hasta" wire:model="fechaFinal"
                           class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <button class="btn btn-primary btn-sm" wire:click="listar()"
                            wire:loading.attr="disabled" wire:target="listar">
                        <span wire:loading wire:target="listar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-magnifying-glass me-1" wire:loading.remove wire:target="listar"></i>
                        Buscar
                    </button>
                </div>
            </div>

        </div>

        <div class="card-body">
            @if($buscar)
            <div class="mb-3">
                <label>
                    COMPROBANTES SIN ENVIAR:
                    <span class="text-danger fw-bold">{{ $ventasCant }}</span><br>
                    <small class="text-muted">* ENVIAR MÁXIMO 3 DÍAS DESPUÉS LA FECHA DE EMISIÓN</small>
                </label>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th>Fecha de Emisión</th>
                            <th>Comprobante</th>
                            <th>Serie y Correlativo</th>
                            <th>Cliente</th>
                            <th>Forma de Pago</th>
                            <th>Tipo de Pago</th>
                            <th>Total</th>
                            <th>PDF</th>
                            <th>Estado Sunat</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($ventas as $index => $al)
                        @php
                            $anulado = (int) $al->anulado_sunat === 1;
                            $stylee  = $anulado ? 'text-align:center;text-decoration:line-through' : 'text-align:center';
                            $tipo_comprobante = match($al->venta_tipo) {
                                '03' => 'BOLETA',
                                '01' => 'FACTURA',
                                '07' => 'NOTA DE CRÉDITO',
                                '08' => 'NOTA DE DÉBITO',
                                default => '--',
                            };
                            $cliente   = (int) $al->id_tipo_documento === 4
                                ? ($al->cliente_razonsocial ?? '')
                                : ($al->cliente_nombre ?? '');
                            $mensaje   = $al->venta_respuesta_sunat ?? 'Sin Enviar a Sunat';
                            $respuesta = (string) ($al->venta_respuesta_sunat ?? '');
                            $error1033 = str_contains($respuesta, '1033');
                            $error1032 = str_contains($respuesta, '1032');
                        @endphp
                        <tr id="venta_{{ $al->id_venta }}" style="{{ $stylee }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($al->venta_fecha)->format('d-m-Y') }}<br>
                                <span class="text-muted small">{{ \Carbon\Carbon::parse($al->venta_fecha)->format('H:i:s') }}</span>
                            </td>
                            <td>{{ $tipo_comprobante }}</td>
                            <td class="fw-semibold">{{ $al->venta_serie }}-{{ $al->venta_correlativo }}</td>
                            <td>
                                <div class="small">{{ $al->cliente_numero }}</div>
                                <div class="fw-semibold small">{{ $cliente }}</div>
                            </td>
                            <td>{{ (int) $al->id_formas_pago === 1 ? 'CONTADO' : 'CREDITO' }}</td>
                            <td>
                                @if(!empty($al->tipo_pago) && count($al->tipo_pago) > 0)
                                    @foreach($al->tipo_pago as $d)
                                        <div>✅ {{ $d->tipo_pago_nombre }}</div>
                                    @endforeach
                                @else
                                    --
                                @endif
                            </td>
                            <td>{{ $al->simbolo }} {{ $al->venta_total }}</td>
                            <td class="text-center">
                                <a target="_blank"
                                   href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $al->id_venta]) }}"
                                   title="Imprimir PDF">
                                    <i class="fa-regular fa-file-pdf text-danger fa-lg"></i>
                                </a>
                            </td>
                            <td style="color:red;font-size:14px">{{ $mensaje }}</td>
                            <td>
                                <a target="_blank" title="Ver detalle"
                                   class="btn btn-sm btn-info"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $al->id_venta]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                @if(
                                    (string) $al->anulado_sunat === '0'
                                    && in_array((string) $al->venta_tipo_envio, ['0', '1'])
                                    && (string) $al->venta_tipo !== '03'
                                )
                                    @can('pendientes_declarar.crear')
                                    <button title="Enviar a Sunat"
                                            class="btn btn-sm btn-success m-1"
                                            wire:click="confirmarEnvioSunat({{ $al->id_venta }})">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    @endcan
                                @endif

                                @if($anulado)
                                    <span class="text-danger small d-block mt-1">
                                        ANULADO — ir a resumen diario para enviar a SUNAT
                                    </span>
                                @endif

                                @can('pendientes_declarar.crear')
                                @if($error1033)
                                    <button title="Cambiar Estado"
                                            class="btn btn-sm btn-warning ms-1"
                                            wire:click="confirmarCambiarEstadoEnviado({{ $al->id_venta }})">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                @elseif($error1032)
                                    <button title="Cambiar Estado"
                                            class="btn btn-sm btn-warning ms-1"
                                            wire:click="confirmarCambiarEstadoAnulado({{ $al->id_venta }})">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar</strong> para ver los comprobantes.
                                @else
                                    No se encontraron comprobantes con los filtros aplicados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($buscar && $tipoVenta === '01' && count($ventas) > 0)
            <div class="text-center mt-3">
                @can('pendientes_declarar.crear')
                <button class="btn btn-success"
                        wire:click="confirmarEnvioMasivo()">
                    <i class="fa-solid fa-upload me-1"></i> Enviar ventas a Sunat
                </button>
                @endcan
            </div>
            @endif
        </div>
    </div>

    <div wire:loading wire:target="listar,enviarSunat,cambiarEstadoEnviado,cambiarEstadoAnulado,envioMasivo">
        <x-loader />
    </div>
</div>

@script
<script>
    $wire.on('abrirModalConfirmacionPendientes', () => {
        const modalEl = document.getElementById('modalConfirmacionPendientes');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });

    $wire.on('cerrarModalConfirmacionPendientes', () => {
        const modalEl = document.getElementById('modalConfirmacionPendientes');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    });

    document.getElementById('modalConfirmacionPendientes')
        .addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
</script>
@endscript
