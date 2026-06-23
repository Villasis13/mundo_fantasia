<div>

    {{-- ── Alertas ──────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Modal Nuevo Movimiento ───────────────────────────────── --}}
    <div class="modal fade" id="modalMovimiento" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-money-bill-transfer me-2 text-primary"></i>
                        Registrar Movimiento de Caja
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        wire:click="limpiarFormulario"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="guardar">
                        <div class="row g-3">

                            {{-- Tipo --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror"
                                    wire:model.live="tipo">
                                    <option value="1">Ingreso</option>
                                    <option value="2">Egreso</option>
                                </select>
                                @error('tipo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Medio de pago --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Medio de Pago <span class="text-danger">*</span></label>
                                <select class="form-select @error('idTipoPago') is-invalid @enderror"
                                    wire:model.live="idTipoPago">
                                    <option value="">Seleccionar</option>
                                    @foreach($tiposPago as $tp)
                                        <option value="{{ $tp->id_tipo_pago }}">{{ $tp->tipo_pago_nombre }}</option>
                                    @endforeach
                                </select>
                                @error('idTipoPago') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Monto --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Monto (S/) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01"
                                    class="form-control @error('monto') is-invalid @enderror"
                                    wire:model="monto" placeholder="0.00">
                                @error('monto') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Concepto --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Concepto <span class="text-danger">*</span></label>
                                <input type="text" maxlength="300"
                                    class="form-control @error('concepto') is-invalid @enderror"
                                    wire:model="concepto" placeholder="Ej: Pago de servicio, depósito, préstamo...">
                                @error('concepto') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- N° operación (solo si no es efectivo) --}}
                            @if($idTipoPago && $idTipoPago != 1)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Operación <span class="text-danger">*</span></label>
                                <input type="text" maxlength="100"
                                    class="form-control @error('numeroOperacion') is-invalid @enderror"
                                    wire:model="numeroOperacion" placeholder="Número de operación / referencia">
                                @error('numeroOperacion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            @endif

                            {{-- Observación --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observación</label>
                                <textarea class="form-control" rows="2" maxlength="500"
                                    wire:model="observacion" placeholder="Observación adicional (opcional)"></textarea>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal" wire:click="limpiarFormulario">
                                Cancelar
                            </button>
                            @can('movimientos_caja.crear')
                            <button type="submit" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="guardar">
                                <span wire:loading wire:target="guardar">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                </span>
                                <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="guardar"></i>
                                Guardar
                            </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal Eliminar ───────────────────────────────────────── --}}
    <div class="modal fade" id="modalEliminar" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar Movimiento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar este movimiento? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="eliminar"
                        wire:loading.attr="disabled" wire:target="eliminar">
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Cabecera ─────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-money-bill-transfer me-2 text-primary"></i>
                Movimientos de Caja
            </h5>
            <small class="text-muted">Registra ingresos y egresos manuales del turno</small>
        </div>
        @if($cajaAbierta)
        @can('movimientos_caja.crear')
        <button class="btn btn-primary"
            wire:click="limpiarFormulario"
            data-bs-toggle="modal" data-bs-target="#modalMovimiento">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Movimiento
        </button>
        @endcan
        @endif
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                @if($esSuperAdmin)
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($esSuperAdmin || $esAdmin)
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Sucursal</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada">
                        <option value="0">Seleccionar sucursal</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Caja</label>
                    <select class="form-select form-select-sm" wire:model.live="idCajaNumeroSeleccionada">
                        <option value="0">Seleccionar caja</option>
                        @foreach($cajas as $c)
                            <option value="{{ $c->id_caja_numero }}">{{ $c->caja_numero_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Fecha</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFecha">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Mostrar</label>
                    <select class="form-select form-select-sm" wire:model.live="porPagina">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Estado de caja ───────────────────────────────────────── --}}
    @if($idCajaNumeroSeleccionada || (!$esSuperAdmin && !$esAdmin))
        @if($cajaAbierta)
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="fa-solid fa-lock-open"></i>
                <span>
                    Caja <strong>{{ $cajaAbierta->caja_numero_nombre ?? '' }}</strong> abierta
                    desde <strong>{{ \Carbon\Carbon::parse($cajaAbierta->caja_fecha_apertura)->format('H:i') }}</strong>
                    — Apertura: <strong>S/ {{ number_format($cajaAbierta->caja_apertura, 2) }}</strong>
                </span>
            </div>
        @else
            <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3">
                <i class="fa-solid fa-lock"></i>
                <span>No hay un turno abierto para esta caja en la fecha seleccionada. No se pueden registrar movimientos.</span>
            </div>
        @endif
    @endif

    {{-- ── Tarjetas resumen ─────────────────────────────────────── --}}
    @if($resumen)
    @php $neto = $resumen->total_ingresos - $resumen->total_egresos; @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-start border-success border-3 shadow-sm">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-plus me-1"></i> Total Ingresos</div>
                    <div class="fs-5 fw-bold text-success">S/ {{ number_format($resumen->total_ingresos, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-danger border-3 shadow-sm">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-minus me-1"></i> Total Egresos</div>
                    <div class="fs-5 fw-bold text-danger">S/ {{ number_format($resumen->total_egresos, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-3 {{ $neto >= 0 ? 'border-primary' : 'border-danger' }} shadow-sm">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1"><i class="fa-solid fa-equals me-1"></i> Neto del Día</div>
                    <div class="fs-5 fw-bold {{ $neto >= 0 ? 'text-primary' : 'text-danger' }}">
                        S/ {{ number_format($neto, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Tabla ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="cursor:pointer" wire:click="ordenar('id_caja_movimiento')">#
                                @if($ordenColumna === 'id_caja_movimiento')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th>Tipo</th>
                            <th style="cursor:pointer" wire:click="ordenar('concepto')">Concepto
                                @if($ordenColumna === 'concepto')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th>Medio de Pago</th>
                            <th>N° Operación</th>
                            <th style="cursor:pointer" wire:click="ordenar('monto')">Monto
                                @if($ordenColumna === 'monto')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th>Registrado por</th>
                            <th style="cursor:pointer" wire:click="ordenar('created_at')">Hora
                                @if($ordenColumna === 'created_at')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $mov->id_caja_movimiento }}</td>
                            <td>
                                @if($mov->tipo == 1)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fa-solid fa-arrow-down me-1"></i>Ingreso
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="fa-solid fa-arrow-up me-1"></i>Egreso
                                    </span>
                                @endif
                            </td>
                            <td>{{ $mov->concepto }}</td>
                            <td class="small">{{ $mov->tipo_pago_nombre ?? '—' }}</td>
                            <td class="small text-muted">{{ $mov->numero_operacion ?? '—' }}</td>
                            <td class="fw-semibold {{ $mov->tipo == 1 ? 'text-success' : 'text-danger' }}">
                                S/ {{ number_format($mov->monto, 2) }}
                            </td>
                            <td class="small">{{ $mov->nombre_users }}</td>
                            <td class="small text-muted">
                                {{ \Carbon\Carbon::parse($mov->created_at)->format('H:i') }}
                            </td>
                            <td class="text-end pe-3">
                                @can('movimientos_caja.eliminar')
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="confirmarEliminar({{ $mov->id_caja_movimiento }})"
                                    title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                No hay movimientos registrados para este turno.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movimientos instanceof \Illuminate\Pagination\LengthAwarePaginator && $movimientos->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $movimientos->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── Scripts ──────────────────────────────────────────────── --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('cerrarModal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalMovimiento'));
                if (modal) modal.hide();
            });
            Livewire.on('abrirModalEliminar', () => {
                new bootstrap.Modal(document.getElementById('modalEliminar')).show();
            });
            Livewire.on('cerrarModalEliminar', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminar'));
                if (modal) modal.hide();
            });
        });
    </script>

</div>
