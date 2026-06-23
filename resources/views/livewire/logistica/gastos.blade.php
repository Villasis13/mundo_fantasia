<div>

    {{-- Alertas flash --}}
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

    {{-- Modal: Anular --}}
    <div class="modal fade" id="modalAnularGasto" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-ban me-2 text-danger"></i>Anular Registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted small mb-3">Esta acción marcará el registro como anulado. Indique el motivo.</p>
                    <label class="form-label fw-semibold">Motivo de anulación <span class="text-danger">*</span></label>
                    <textarea wire:model.live="motivoAnulacion"
                              class="form-control @error('motivoAnulacion') is-invalid @enderror"
                              rows="4" placeholder="Describa el motivo..."></textarea>
                    @error('motivoAnulacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-semibold"
                            wire:click="anular" wire:loading.attr="disabled" wire:target="anular">
                        <span wire:loading.remove wire:target="anular"><i class="fa-solid fa-ban me-1"></i> Anular</span>
                        <span wire:loading wire:target="anular"><span class="spinner-border spinner-border-sm me-1"></span> Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ HISTORIAL ══════════════════ --}}
    @if($vista === 'historial')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-receipt me-2" style="color:#0b1892;"></i>
                        Ingresos / Egresos
                    </h5>
                    <small class="text-muted">Registro de movimientos de caja</small>
                </div>
                <div class="d-flex gap-2">
                    @can('gastos.exportar')
                    <button class="btn btn-sm btn-outline-success fw-semibold"
                            wire:click="exportarExcel"
                            wire:loading.attr="disabled" wire:target="exportarExcel">
                        <span wire:loading.remove wire:target="exportarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}"
                                 style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar
                        </span>
                        <span wire:loading wire:target="exportarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endcan
                    @can('gastos.crear')
                    <button class="btn btn-success fw-semibold" wire:click="nuevaGasto">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Registro
                    </button>
                    @endcan
                </div>
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="buscar"
                           placeholder="Buscar detalle o tipo..."
                           style="min-width:200px;">
                </div>
                <div class="col-auto">
                    <select wire:model.live="filtroTipoGasto" class="form-select form-select-sm" style="min-width:190px;">
                        <option value="">Todos los tipos</option>
                        @foreach($tiposGasto as $tipo)
                            <option value="{{ $tipo->id_tipo_gasto }}">{{ $tipo->tipo_gasto_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Desde</span>
                        <input type="date" class="form-control" wire:model.live="filtroFechaDesde">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Hasta</span>
                        <input type="date" class="form-control" wire:model.live="filtroFechaHasta">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:50px;">#</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Clasificación</th>
                            <th>Detalle</th>
                            <th class="text-end">Monto (S/)</th>
                            <th>Tienda / Caja</th>
                            <th>Registrado por</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gastos as $index => $gasto)
                        <tr @if($gasto->gasto_estado == 0) style="background-color:#fff0f0;" @endif>
                            <td class="ps-3 text-muted small">{{ $gastos->firstItem() + $index }}</td>
                            <td>
                                @if(($gasto->gasto_tipo ?? 1) == 2)
                                    <span class="badge bg-success">Ingreso</span>
                                @else
                                    <span class="badge bg-danger">Egreso</span>
                                @endif
                            </td>
                            <td class="small">{{ \Carbon\Carbon::parse($gasto->gasto_fecha)->format('d/m/Y') }}</td>
                            <td class="small fw-semibold">{{ $gasto->tipo_gasto_nombre }}</td>
                            <td class="small">
                                {{ mb_strlen($gasto->gasto_detalle) > 70
                                    ? mb_substr($gasto->gasto_detalle, 0, 70) . '…'
                                    : $gasto->gasto_detalle }}
                            </td>
                            <td class="text-end small fw-semibold
                                @if(($gasto->gasto_tipo ?? 1) == 2) text-success @else text-danger @endif">
                                @if(($gasto->gasto_tipo ?? 1) == 2)+ @else- @endif
                                S/ {{ number_format($gasto->gasto_monto, 2) }}
                            </td>
                            <td class="small">
                                {{ $gasto->tienda_nombre ?? '—' }}
                                @if($gasto->caja_numero_nombre)
                                    <br><span class="text-muted" style="font-size:11px;">
                                        <i class="fa-solid fa-cash-register me-1"></i>{{ $gasto->caja_numero_nombre }}
                                    </span>
                                @endif
                            </td>
                            <td class="small">{{ $gasto->nombre_users }}</td>
                            <td class="text-center">
                                @if($gasto->gasto_estado == 1)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @can('gastos.actualizar')
                                    <button class="btn btn-warning btn-sm"
                                            wire:click="editar({{ $gasto->id_gasto }})" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endcan
                                    @can('gastos.cambiar_estado')
                                    @if($gasto->gasto_estado == 1)
                                    <button class="btn btn-outline-danger btn-sm"
                                            wire:click="confirmarAnular({{ $gasto->id_gasto }})" title="Anular">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No se encontraron registros.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($gastos->hasPages())
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2 px-3">
            <small class="text-muted">
                Mostrando {{ $gastos->firstItem() }}–{{ $gastos->lastItem() }} de {{ $gastos->total() }} registros
            </small>
            {{ $gastos->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════ NUEVO / EDITAR ══════════════════ --}}
    @if($vista === 'nuevo')
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-receipt me-2" style="color:#0b1892;"></i>
            {{ $idEditar ? 'Editar Registro' : 'Nuevo Registro' }}
        </h5>
        <button class="btn btn-outline-secondary btn-sm" wire:click="volverHistorial">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al historial
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-semibold text-secondary">
                <i class="fa-solid fa-file-invoice-dollar me-2"></i>Datos del movimiento
            </h6>
        </div>
        <div class="card-body">

            {{-- Tipo de movimiento --}}
            <div class="mb-4">
                <label class="form-label fw-semibold d-block mb-2">
                    Tipo de movimiento <span class="text-danger">*</span>
                </label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="tipoMov" id="tipoIngreso"
                           wire:model.live="tipoMovimiento" value="2" autocomplete="off">
                    <label class="btn btn-outline-success fw-semibold px-4" for="tipoIngreso">
                        <i class="fa-solid fa-arrow-trend-up me-2"></i>Ingreso
                    </label>

                    <input type="radio" class="btn-check" name="tipoMov" id="tipoEgreso"
                           wire:model.live="tipoMovimiento" value="1" autocomplete="off">
                    <label class="btn btn-outline-danger fw-semibold px-4" for="tipoEgreso">
                        <i class="fa-solid fa-arrow-trend-down me-2"></i>Egreso
                    </label>
                </div>
                @error('tipoMovimiento')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Caja --}}
            <div class="row g-3 mb-4 pb-3 border-bottom">
                <div class="col-12">
                    <h6 class="fw-semibold text-secondary mb-0">
                        <i class="fa-solid fa-cash-register me-2"></i>Origen del movimiento
                    </h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Caja <span class="text-danger">*</span></label>
                    <select wire:model.live="idCajaSeleccionada"
                            class="form-select @error('idCajaSeleccionada') is-invalid @enderror">
                        <option value="0">— Seleccione caja —</option>
                        @foreach($cajasDisponibles as $caja)
                            <option value="{{ $caja->id_caja_numero }}">
                                {{ $caja->caja_numero_nombre }}
                                @if($caja->tienda_nombre) — {{ $caja->tienda_nombre }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('idCajaSeleccionada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Info saldo de caja (solo cuando tipo = Gasto y hay caja seleccionada) --}}
            @if($tipoMovimiento == 1 && $idCajaSeleccionada > 0)
                @if(!$infoCaja['abierta'])
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                    <div>
                        <strong>Caja no aperturada.</strong>
                        La caja <strong>{{ $infoCaja['nombre'] ?? 'seleccionada' }}</strong> no tiene apertura activa hoy.
                        Debe aperturarla antes de registrar un gasto.
                    </div>
                </div>
                @else
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:#f0f7ff;border:1px solid #b8d9f5;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:#0b1892;color:#fff;font-size:20px;">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div>
                        <div class="small text-muted mb-1">
                            <i class="fa-solid fa-store me-1"></i>{{ $infoCaja['nombre'] ?? '—' }}
                        </div>
                        <div class="fw-bold" style="font-size:1.15rem;color:#0b1892;">
                            Saldo disponible: <span style="color:#198754;">S/ {{ number_format($infoCaja['saldo'] ?? 0, 2) }}</span>
                        </div>
                        <div class="small text-muted">
                            Apertura: S/ {{ number_format($infoCaja['apertura'] ?? 0, 2) }}
                        </div>
                    </div>
                </div>
                @endif
            @endif

            {{-- Datos del movimiento --}}
            <div class="row g-3">

                {{-- Tipo / Clasificación --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Clasificación <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="idTipoGasto"
                            class="form-select @error('idTipoGasto') is-invalid @enderror">
                        <option value="0">-- Seleccione --</option>
                        @foreach($tiposGasto as $tipo)
                            <option value="{{ $tipo->id_tipo_gasto }}">{{ $tipo->tipo_gasto_nombre }}</option>
                        @endforeach
                    </select>
                    @error('idTipoGasto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fecha --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" wire:model="gastoFecha"
                           class="form-control @error('gastoFecha') is-invalid @enderror">
                    @error('gastoFecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Detalle --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Detalle <span class="text-danger">*</span></label>
                    <textarea wire:model="gastoDetalle"
                              class="form-control @error('gastoDetalle') is-invalid @enderror"
                              rows="3"
                              placeholder="Describa el concepto del {{ $tipoMovimiento == 2 ? 'ingreso' : 'egreso' }}..."></textarea>
                    @error('gastoDetalle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Monto --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monto (S/) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text @if($tipoMovimiento == 2) bg-success text-white @else bg-danger text-white @endif fw-bold">
                            @if($tipoMovimiento == 2)+@else-@endif S/
                        </span>
                        <input type="number" wire:model.live="gastoMonto"
                               step="0.01" min="0.01"
                               class="form-control @error('gastoMonto') is-invalid @enderror"
                               placeholder="0.00">
                        @error('gastoMonto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Observación --}}
                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        Observación <small class="text-muted">(opcional)</small>
                    </label>
                    <textarea wire:model="gastoObservacion" class="form-control" rows="2"
                              placeholder="Observaciones adicionales..."></textarea>
                </div>

            </div>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-end gap-2 py-3">
            <button type="button" class="btn btn-secondary" wire:click="volverHistorial">
                <i class="fa-solid fa-xmark me-1"></i> Cancelar
            </button>
            @if($idEditar)
            <button type="button" class="btn btn-primary fw-semibold"
                    wire:click="actualizar" wire:loading.attr="disabled" wire:target="actualizar">
                <span wire:loading.remove wire:target="actualizar">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar
                </span>
                <span wire:loading wire:target="actualizar">
                    <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
                </span>
            </button>
            @else
            <button type="button"
                    class="btn fw-semibold {{ $tipoMovimiento == 2 ? 'btn-success' : 'btn-danger' }}"
                    wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
                <span wire:loading.remove wire:target="guardar">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Registrar {{ $tipoMovimiento == 2 ? 'Ingreso' : 'Egreso' }}
                </span>
                <span wire:loading wire:target="guardar">
                    <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
                </span>
            </button>
            @endif
        </div>
    </div>
    @endif

    <div wire:loading wire:target="nuevaGasto, volverHistorial, guardar, actualizar, editar, tipoMovimiento, idCajaSeleccionada, exportarExcel">
        <x-loader />
    </div>

    @script
    <script>
        $wire.on('abrirModalAnularGasto', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAnularGasto')).show();
        });
        $wire.on('cerrarModalAnularGasto', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalAnularGasto'));
            if (m) m.hide();
        });
        document.getElementById('modalAnularGasto')
            .addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });
    </script>
    @endscript

</div>
