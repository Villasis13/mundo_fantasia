<div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Eliminar Empresa                                 --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarEmpresa" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Deshabilitar Empresa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-building-circle-xmark fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-0">¿Estás seguro de que deseas deshabilitar esta empresa?</p>
                    <small class="text-muted">Se desactivarán también sus sucursales, tiendas y cajas.</small>
                </div>
                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold"
                            wire:click="eliminar"
                            wire:loading.attr="disabled" wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-ban me-1"></i> Sí, deshabilitar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Deshabilitando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Asignar / Renovar Plan                          --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAsignarPlan" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-layer-group me-2" style="color:#0b1892;"></i>
                        Asignar / Renovar Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormularioPlan"></button>
                </div>

                <div class="modal-body p-4">

                    {{-- Empresa seleccionada --}}
                    <div class="rounded p-3 mb-4" style="background:#eef1ff;">
                        <small class="text-muted fw-semibold d-block text-uppercase" style="font-size:10px;">Empresa</small>
                        <span class="fw-bold" style="color:#0b1892;">{{ $nombreEmpresaPlan }}</span>
                    </div>

                    {{-- Plan activo actual --}}
                    @if($idEmpresaPlan)
                        @php
                            $planActivo = DB::table('empresa_planes as ep')
                                ->select('ep.*', 'p.plan_nombre', 'p.plan_precio', 'p.plan_duracion_dias')
                                ->join('planes as p', 'p.id_plan', '=', 'ep.id_plan')
                                ->where('ep.id_empresa', $idEmpresaPlan)
                                ->where('ep.estado', 1)
                                ->first();
                        @endphp

                        @if($planActivo)
                            @php
                                $hoy            = \Carbon\Carbon::today();
                                $fechaFinActual = \Carbon\Carbon::parse($planActivo->fecha_fin);
                                $diasRestantes  = $hoy->diffInDays($fechaFinActual, false);
                                $vencido        = $diasRestantes < 0;
                            @endphp
                            <div class="alert {{ $vencido ? 'alert-danger' : 'alert-success' }} py-2 mb-4">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <strong>Plan actual:</strong> {{ $planActivo->plan_nombre }}
                                        <span class="badge ms-2 {{ $vencido ? 'bg-danger' : 'bg-success' }}">
                                            {{ $vencido ? 'Vencido' : 'Activo' }}
                                        </span>
                                    </div>
                                    <div class="small">
                                        <i class="fa-solid fa-calendar-days me-1"></i>
                                        {{ \Carbon\Carbon::parse($planActivo->fecha_inicio)->format('d/m/Y') }}
                                        →
                                        {{ $fechaFinActual->format('d/m/Y') }}
                                        @if(!$vencido)
                                            <span class="badge bg-light text-dark border ms-1">
                                                {{ (int)$diasRestantes }} días restantes
                                            </span>
                                        @else
                                            <span class="badge bg-danger ms-1">
                                                Venció hace {{ abs((int)$diasRestantes) }} días
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning py-2 mb-4">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>
                                Esta empresa no tiene un plan activo asignado.
                            </div>
                        @endif
                    @endif

                    <div class="row g-3">

                        {{-- Seleccionar plan --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Seleccionar Plan <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="idPlanSeleccionado"
                                    class="form-select @error('idPlanSeleccionado') is-invalid @enderror">
                                <option value="">— Seleccionar plan —</option>
                                @foreach($planes as $plan)
                                    <option value="{{ $plan->id_plan }}">
                                        {{ $plan->plan_nombre }}
                                        — S/ {{ number_format($plan->plan_precio, 2) }}
                                        — {{ $plan->plan_duracion_dias }} días
                                    </option>
                                @endforeach
                            </select>
                            @error('idPlanSeleccionado') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Resumen del plan seleccionado --}}
                        @if($idPlanSeleccionado)
                            @php
                                $planElegido = DB::table('planes')->where('id_plan', $idPlanSeleccionado)->first();
                            @endphp
                            @if($planElegido)
                                <div class="col-12">
                                    <div class="rounded p-3 border" style="background:#f8f9ff;">
                                        <div class="row g-2 text-center">
                                            <div class="col-4">
                                                <small class="text-muted d-block" style="font-size:10px;">PRECIO</small>
                                                <span class="fw-bold text-success">S/ {{ number_format($planElegido->plan_precio, 2) }}</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block" style="font-size:10px;">DURACIÓN</small>
                                                <span class="fw-bold" style="color:#0b1892;">{{ $planElegido->plan_duracion_dias }} días</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block" style="font-size:10px;">VENCE EL</small>
                                                @if($fechaInicioPlan)
                                                    <span class="fw-bold small text-muted">
                                                        {{ \Carbon\Carbon::parse($fechaInicioPlan)->addDays($planElegido->plan_duracion_dias)->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($planElegido->plan_descripcion)
                                            <small class="text-muted d-block mt-2 text-center">{{ $planElegido->plan_descripcion }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- Fecha de inicio --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Fecha de Inicio <span class="text-danger">*</span>
                            </label>
                            <input type="date" wire:model.live="fechaInicioPlan"
                                   class="form-control @error('fechaInicioPlan') is-invalid @enderror">
                            @error('fechaInicioPlan') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        {{-- Monto pagado --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Monto Pagado (S/) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" wire:model="montoPagadoPlan"
                                       class="form-control @error('montoPagadoPlan') is-invalid @enderror"
                                       placeholder="0.00" min="0" step="0.01">
                                @error('montoPagadoPlan') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Observación --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted small text-uppercase">
                                Observación <small class="text-muted fw-normal">(opcional)</small>
                            </label>
                            <input type="text" wire:model="observacionPlan"
                                   class="form-control @error('observacionPlan') is-invalid @enderror"
                                   placeholder="Ej: Pago por transferencia, renovación anual...">
                            @error('observacionPlan') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    {{-- Historial de planes --}}
                    @if($idEmpresaPlan)
                        @php
                            $historialPlanes = DB::table('empresa_planes as ep')
                                ->select('ep.*', 'p.plan_nombre', 'p.plan_precio', 'u.nombre_users')
                                ->join('planes as p', 'p.id_plan', '=', 'ep.id_plan')
                                ->leftJoin('users as u', 'u.id_users', '=', 'ep.id_users')
                                ->where('ep.id_empresa', $idEmpresaPlan)
                                ->orderByDesc('ep.created_at')
                                ->limit(5)
                                ->get();
                        @endphp

                        @if($historialPlanes->count())
                            <div class="mt-4">
                                <h6 class="fw-semibold mb-2" style="color:#0b1892;">
                                    <i class="fa-solid fa-clock-rotate-left me-2"></i>
                                    Historial (últimos 5)
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead>
                                        <tr class="encabezado_tabla_color text-center">
                                            <th>Plan</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Monto</th>
                                            <th>Asignó</th>
                                            <th>Estado</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($historialPlanes as $h)
                                            <tr>
                                                <td class="fw-semibold small">{{ $h->plan_nombre }}</td>
                                                <td class="text-center small">{{ \Carbon\Carbon::parse($h->fecha_inicio)->format('d/m/Y') }}</td>
                                                <td class="text-center small">{{ \Carbon\Carbon::parse($h->fecha_fin)->format('d/m/Y') }}</td>
                                                <td class="text-end small fw-semibold text-success">S/ {{ number_format($h->monto_pagado, 2) }}</td>
                                                <td class="text-center small text-muted">{{ $h->nombre_users ?? '—' }}</td>
                                                <td class="text-center">
                                                        <span class="badge {{ $h->estado == 1 ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $h->estado == 1 ? 'Activo' : 'Vencido' }}
                                                        </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                </div>

                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal" wire:click="limpiarFormularioPlan">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn fw-semibold text-white"
                            style="background:#0b1892;"
                            wire:click="asignarPlan"
                            wire:loading.attr="disabled" wire:target="asignarPlan">
                        <span wire:loading.remove wire:target="asignarPlan">
                            <i class="fa-solid fa-link me-1"></i> Asignar Plan
                        </span>
                        <span wire:loading wire:target="asignarPlan">
                            <span class="spinner-border spinner-border-sm me-1"></span> Asignando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  MODAL — Crear / Editar Empresa                          --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEmpresa" wire:ignore.self tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-building me-2" style="color:#0b1892;"></i>
                        {{ $modoEdicion ? 'Editar Empresa' : 'Nueva Empresa' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormulario"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- ── COLUMNA IZQUIERDA ──────────────────────── --}}
                        <div class="col-lg-6">

                            {{-- RUC + Consulta --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    RUC <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" wire:model="empresaRuc"
                                           class="form-control @error('empresaRuc') is-invalid @enderror"
                                           placeholder="11 dígitos" maxlength="11">
                                    <button class="btn btn-outline-primary fw-semibold"
                                            wire:click="consultarRuc"
                                            wire:loading.attr="disabled" wire:target="consultarRuc"
                                            type="button">
                                        <span wire:loading.remove wire:target="consultarRuc">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
                                        </span>
                                        <span wire:loading wire:target="consultarRuc">
                                            <span class="spinner-border spinner-border-sm me-1"></span>
                                        </span>
                                    </button>
                                    @error('empresaRuc') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                @if($mensajeConsultaRuc)
                                    <small class="mt-1 d-block {{ $tipoMensajeRuc === 'success' ? 'text-success' : ($tipoMensajeRuc === 'warning' ? 'text-warning' : 'text-danger') }}">
                                        <i class="fa-solid fa-circle-info me-1"></i> {{ $mensajeConsultaRuc }}
                                    </small>
                                @endif
                            </div>

                            {{-- Razón Social --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Razón Social <span class="text-danger">*</span>
                                </label>
                                <input type="text" wire:model="empresaRazonSocial"
                                       class="form-control @error('empresaRazonSocial') is-invalid @enderror"
                                       placeholder="Razón social de la empresa">
                                @error('empresaRazonSocial') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Nombre Comercial --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Nombre Comercial <span class="text-danger">*</span>
                                </label>
                                <input type="text" wire:model="empresaNombreComercial"
                                       class="form-control @error('empresaNombreComercial') is-invalid @enderror"
                                       placeholder="Nombre comercial">
                                @error('empresaNombreComercial') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Descripción --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Descripción</label>
                                <input type="text" wire:model="empresaDescripcion"
                                       class="form-control @error('empresaDescripcion') is-invalid @enderror"
                                       placeholder="Descripción breve">
                                @error('empresaDescripcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Domicilio Fiscal --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Domicilio Fiscal <span class="text-danger">*</span>
                                </label>
                                <input type="text" wire:model="empresaDomicilioFiscal"
                                       class="form-control @error('empresaDomicilioFiscal') is-invalid @enderror"
                                       placeholder="Dirección fiscal">
                                @error('empresaDomicilioFiscal') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- País --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    País <span class="text-danger">*</span>
                                </label>
                                <input type="text" wire:model="empresaPais"
                                       class="form-control @error('empresaPais') is-invalid @enderror">
                                @error('empresaPais') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Ubigeo buscador --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Ubigeo</label>
                                @if($ubigeoSeleccionado)
                                    <div class="d-flex align-items-center gap-2 p-2 rounded border bg-light">
                                        <i class="fa-solid fa-location-dot text-primary"></i>
                                        <span class="small fw-semibold flex-grow-1">{{ $ubigeoSeleccionado['label'] }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="limpiarUbigeo">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="position-relative">
                                        <input type="text"
                                               wire:model.live.debounce.300ms="buscarUbigeo"
                                               wire:focus="$set('mostrarListaUbigeo', true)"
                                               class="form-control"
                                               placeholder="Escribe departamento, provincia o distrito...">
                                        @if($mostrarListaUbigeo && strlen($buscarUbigeo) >= 2)
                                            <div class="position-absolute w-100 border rounded shadow-sm bg-white"
                                                 style="z-index:1055; max-height:200px; overflow-y:auto; top:100%;">
                                                @forelse($ubigeosFiltrados as $ub)
                                                    <div class="px-3 py-2 small"
                                                         style="cursor:pointer;"
                                                         wire:click="seleccionarUbigeo({{ $ub->id_ubigeo }}, '{{ addslashes($ub->ubigeo_departamento . ' / ' . $ub->ubigeo_provincia . ' / ' . $ub->ubigeo_distrito) }}')"
                                                         onmouseover="this.style.background='#eef1ff'"
                                                         onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-location-dot text-muted me-1"></i>
                                                        <strong>{{ $ub->ubigeo_departamento }}</strong>
                                                        / {{ $ub->ubigeo_provincia }}
                                                        / {{ $ub->ubigeo_distrito }}
                                                    </div>
                                                @empty
                                                    <div class="px-3 py-2 small text-muted">
                                                        <i class="fa-solid fa-circle-info me-1"></i>
                                                        Sin resultados para "{{ $buscarUbigeo }}"
                                                    </div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                    <small class="text-muted">Escribe al menos 2 caracteres para buscar.</small>
                                @endif
                                @error('idUbigeo') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        {{-- ── COLUMNA DERECHA ─────────────────────────── --}}
                        <div class="col-lg-6">

                            {{-- Teléfonos --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold text-muted small text-uppercase">Teléfono 1</label>
                                    <input type="text" wire:model="empresaTelefono1"
                                           class="form-control @error('empresaTelefono1') is-invalid @enderror"
                                           placeholder="Ej: 01 234 5678">
                                    @error('empresaTelefono1') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold text-muted small text-uppercase">Teléfono 2</label>
                                    <input type="text" wire:model="empresaTelefono2"
                                           class="form-control @error('empresaTelefono2') is-invalid @enderror"
                                           placeholder="Ej: 987 654 321">
                                    @error('empresaTelefono2') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Correo --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Correo</label>
                                <input type="email" wire:model="empresaCorreo"
                                       class="form-control @error('empresaCorreo') is-invalid @enderror"
                                       placeholder="correo@empresa.com">
                                @error('empresaCorreo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Usuario SOL --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Usuario SOL <span class="text-danger">*</span>
                                </label>
                                <input type="text" wire:model="empresaUsuarioSol"
                                       class="form-control @error('empresaUsuarioSol') is-invalid @enderror"
                                       placeholder="Usuario SUNAT SOL">
                                @error('empresaUsuarioSol') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Clave SOL --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Clave SOL <span class="text-danger">*</span>
                                </label>
                                <input type="password" wire:model="empresaClaveSol"
                                       class="form-control @error('empresaClaveSol') is-invalid @enderror"
                                       placeholder="Clave SUNAT SOL">
                                @error('empresaClaveSol') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                            {{-- Logo de la empresa --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">
                                    Logo de la Empresa
                                    @if(!$modoEdicion) <span class="text-danger">*</span> @endif
                                </label>
                                @if($modoEdicion && $empresaFotoActual)
                                    <div class="mb-2">
                                        <img src="{{ asset($empresaFotoActual) }}" alt="Logo actual"
                                             class="rounded border" style="height:60px; object-fit:contain;">
                                        <small class="text-muted d-block mt-1">Logo actual. Sube uno nuevo para reemplazarlo.</small>
                                    </div>
                                @endif
                                @if($empresaFoto)
                                    <div class="mb-2">
                                        <img src="{{ $empresaFoto->temporaryUrl() }}" alt="Vista previa"
                                             class="rounded border" style="height:60px; object-fit:contain;">
                                        <small class="text-success d-block mt-1">
                                            <i class="fa-solid fa-circle-check me-1"></i> Imagen seleccionada
                                        </small>
                                    </div>
                                @endif
                                <input type="file" wire:model="empresaFoto"
                                       class="form-control @error('empresaFoto') is-invalid @enderror"
                                       accept="image/*">
                                @error('empresaFoto') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                <small class="text-muted">Máximo 2MB. Se guardará como logo y logo de ticket.</small>
                            </div>

                            {{-- Certificado Digital --}}
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-2">
                                    <i class="fa-solid fa-shield-halved me-1 text-primary"></i>
                                    Certificado Digital
                                </label>

                                @if($certPfxActual)
                                <div class="alert alert-success py-2 small mb-2">
                                    <i class="fa-solid fa-file-shield me-1"></i>
                                    Certificado actual: <strong>{{ basename($certPfxActual) }}</strong>
                                    <span class="text-muted ms-1">— Sube uno nuevo para reemplazarlo.</span>
                                </div>
                                @endif

                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <label class="form-label small text-secondary mb-1">
                                            Archivo .pfx / .p12
                                            <span class="text-muted fw-normal">(opcional)</span>
                                        </label>
                                        <input type="file"
                                               wire:model="archivoCert"
                                               class="form-control form-control-sm"
                                               accept=".pfx,.p12">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small text-secondary mb-1">
                                            Contraseña
                                            <span class="text-muted fw-normal">(opcional)</span>
                                        </label>
                                        <input type="password"
                                               wire:model="certPassword"
                                               class="form-control form-control-sm"
                                               placeholder="Contraseña del certificado"
                                               autocomplete="new-password">
                                    </div>
                                </div>

                                @if($certContenido)
                                <div class="alert alert-success py-2 small mt-2 mb-0">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    Archivo listo para guardar.
                                </div>
                                @endif
                            </div>

                            {{-- Credenciales SIRE --}}
                            <div class="mb-2">
                                <label class="form-label fw-semibold text-muted small text-uppercase mb-2">
                                    <i class="fa-solid fa-plug me-1 text-success"></i>
                                    Credenciales SIRE
                                    <small class="text-muted fw-normal">(api.sunat.gob.pe)</small>
                                </label>
                                <div class="alert alert-info py-2 small mb-2">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Obtén el <strong>Client ID</strong> y <strong>Client Secret</strong> en
                                    <a href="https://api.sunat.gob.pe" target="_blank" class="alert-link">api.sunat.gob.pe</a>
                                    → Credenciales de API → SIRE Compras.
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-secondary mb-1">
                                            Client ID
                                            <span class="text-muted fw-normal">(opcional)</span>
                                        </label>
                                        <input type="text"
                                               wire:model="empresaSireClientId"
                                               class="form-control form-control-sm font-monospace"
                                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-secondary mb-1">
                                            Client Secret
                                            <span class="text-muted fw-normal">(opcional)</span>
                                        </label>
                                        <input type="password"
                                               wire:model="empresaSireClientSecret"
                                               class="form-control form-control-sm"
                                               placeholder="Client Secret"
                                               autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between px-4">
                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal" wire:click="limpiarFormulario">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="guardar"
                            wire:loading.attr="disabled" wire:target="guardar">
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

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{--  CONTENIDO PRINCIPAL                                      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa-solid fa-building me-2 text-primary"></i>Empresas
                        @if($nombreGrupo)
                            <span class="text-muted fw-normal fs-6">— {{ $nombreGrupo }}</span>
                        @endif
                    </h5>
                    <small class="text-muted">
                        {{ $idGrupo ? 'Empresas del grupo seleccionado.' : 'Gestión de empresas del sistema.' }}
                    </small>
                </div>
                {{-- @can('opcion_gestion_empresas.crear')
                <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Empresa
                </button>
                @endcan --}}
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="text-muted small mb-0 text-nowrap">registros</label>
                </div>
                <div class="input-group input-group-sm" style="max-width:340px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Buscar por razón social, RUC...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="$set('buscar','')">
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
                        <th style="cursor:pointer;" wire:click="ordenar('id_empresa')">
                            #
                            @if($ordenColumna === 'id_empresa') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th>Logo</th>
                        <th style="cursor:pointer;" wire:click="ordenar('empresa_ruc')" class="text-start">
                            RUC
                            @if($ordenColumna === 'empresa_ruc') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th style="cursor:pointer;" wire:click="ordenar('empresa_razon_social')" class="text-start">
                            Razón Social
                            @if($ordenColumna === 'empresa_razon_social') <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else <i class="fa-solid fa-sort ms-1 opacity-25"></i> @endif
                        </th>
                        <th class="text-start">Nombre Comercial</th>
                        <th class="text-start">Ubigeo</th>
                        <th>Sedes</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($empresas as $empresa)
                        <tr @if($empresa->empresa_estado == '0') style="background:rgba(220,53,69,0.18);" @endif>
                            <td class="text-center text-muted">{{ $empresa->id_empresa }}</td>
                            <td class="text-center">
                                @if($empresa->empresa_foto)
                                    <img src="{{ asset($empresa->empresa_foto) }}" alt="Logo"
                                         class="rounded" style="height:56px; width:56px; object-fit:contain; background:#fff; padding:4px; border:1px solid #dee2e6; box-shadow:0 1px 4px rgba(0,0,0,.1);">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded"
                                          style="height:56px; width:56px; background:#f5f5f9; border:1px solid #dee2e6;">
                                        <i class="fa-solid fa-image text-muted fa-lg"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="fw-semibold small">{{ $empresa->empresa_ruc }}</td>
                            <td>
                                <div class="fw-semibold small">{{ $empresa->empresa_razon_social }}</div>
                                @if($empresa->empresa_correo)
                                    <small class="text-muted">{{ $empresa->empresa_correo }}</small>
                                @endif
                            </td>
                            <td class="small">{{ $empresa->empresa_nombrecomercial }}</td>
                            <td class="small text-muted">
                                @if($empresa->ubigeo_departamento)
                                    <i class="fa-solid fa-location-dot me-1 text-primary"></i>
                                    {{ $empresa->ubigeo_departamento }} / {{ $empresa->ubigeo_distrito }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('configuracion.tiendas', $empresa->id_empresa) }}"
                                   class="d-flex flex-column gap-1 align-items-center text-decoration-none"
                                   title="Ver sedes de {{ $empresa->empresa_razon_social }}">
                                    @if($empresa->count_tiendas > 0)
                                        <span class="badge bg-success">
                                            <i class="fa-solid fa-store me-1"></i>{{ $empresa->count_tiendas }} Sede(s)
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fa-solid fa-plus me-1"></i>Agregar
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td class="text-center small">
                                @if($empresa->empresa_telefono1)
                                    <div>{{ $empresa->empresa_telefono1 }}</div>
                                @endif
                                @if($empresa->empresa_telefono2)
                                    <div class="text-muted">{{ $empresa->empresa_telefono2 }}</div>
                                @endif
                                @if(!$empresa->empresa_telefono1 && !$empresa->empresa_telefono2)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="badge {{ $empresa->empresa_estado == '1' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $empresa->empresa_estado == '1' ? 'Activo' : 'Inactivo' }}
                                </span>
                                @php
                                    $sinCert  = empty($empresa->empresa_cert_pfx);
                                    $venc     = $empresa->empresa_cert_vencimiento;
                                    $diasVenc = $venc ? \Carbon\Carbon::parse($venc)->diffInDays(now(), false) : null;
                                    $passRaw  = $empresa->empresa_cert_password ?? '';
                                    $passLen  = mb_strlen($passRaw);
                                    $passParcial = $passLen > 0
                                        ? mb_substr($passRaw, 0, min(3, $passLen)) . str_repeat('*', max(0, $passLen - 3))
                                        : '';
                                @endphp
                                @if($sinCert)
                                <div class="mt-1">
                                    <span class="badge bg-warning text-dark" style="font-size:.65rem;"
                                          title="Falta subir el certificado digital">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Sin certificado
                                    </span>
                                </div>
                                @else
                                    @if($diasVenc !== null && $diasVenc > 0)
                                    <div class="mt-1">
                                        <span class="badge bg-danger" style="font-size:.65rem;"
                                              title="Certificado vencido el {{ \Carbon\Carbon::parse($venc)->format('d/m/Y') }}">
                                            <i class="fa-solid fa-circle-xmark me-1"></i>Cert. vencido
                                        </span>
                                    </div>
                                    @elseif($diasVenc !== null && $diasVenc > -31)
                                    <div class="mt-1">
                                        <span class="badge text-white" style="font-size:.65rem;background:#fd7e14;"
                                              title="Vence el {{ \Carbon\Carbon::parse($venc)->format('d/m/Y') }}">
                                            <i class="fa-solid fa-clock me-1"></i>Vence pronto
                                        </span>
                                    </div>
                                    @else
                                    <div class="mt-1">
                                        <span class="badge bg-primary" style="font-size:.65rem;">
                                            <i class="fa-solid fa-file-shield me-1"></i>Cert. cargado
                                        </span>
                                    </div>
                                    @endif
                                    @if($passParcial)
                                    <div class="mt-1">
                                        <small class="font-monospace text-muted" style="font-size:.7rem;"
                                               title="Contraseña del certificado (parcial)">
                                            <i class="fa-solid fa-key me-1 opacity-50"></i>{{ $passParcial }}
                                        </small>
                                    </div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('opcion_gestion_empresas.actualizar')
                                    <button class="btn btn-sm btn-primary"
                                            wire:click="abrirModalEditar({{ $empresa->id_empresa }})"
                                            title="Editar empresa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @if(!empty($empresa->empresa_cert_pfx))
                                    <button class="btn btn-sm btn-secondary"
                                            wire:click="descargarCertificado({{ $empresa->id_empresa }})"
                                            title="Descargar certificado .pfx">
                                        <i class="fa-solid fa-file-arrow-down"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="eliminarCertificado({{ $empresa->id_empresa }})"
                                            wire:confirm="¿Eliminar el certificado de esta empresa? Esta acción no se puede deshacer."
                                            title="Eliminar certificado">
                                        <i class="fa-solid fa-file-circle-xmark"></i>
                                    </button>
                                    @endif
                                    @endcan
                                    @can('opcion_gestion_empresas.cambiar_estado')
                                    @if($empresa->empresa_estado == '1')
                                        <button class="btn btn-sm btn-danger"
                                                wire:click="confirmarEliminar({{ $empresa->id_empresa }})"
                                                title="Deshabilitar empresa">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-success"
                                                wire:click="habilitarEmpresa({{ $empresa->id_empresa }})"
                                                title="Habilitar empresa">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa-solid fa-building-circle-xmark fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($empresas->count())
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                    <small class="text-muted">
                        Mostrando {{ $empresas->firstItem() }} - {{ $empresas->lastItem() }}
                        de {{ $empresas->total() }} registros
                    </small>
                    {{ $empresas->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    {{-- Loader --}}
    <div wire:loading wire:target="abrirModalEditar, abrirModalNuevo, abrirModalPlan, guardar, eliminar, habilitarEmpresa, asignarPlan">
        <x-loader />
    </div>

</div>

@script
<script>
    // ── Modal Empresa ────────────────────────────────────────────
    $wire.on('abrirModal', () => {
        new bootstrap.Modal(document.getElementById('modalEmpresa')).show();
    });
    $wire.on('cerrarModal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEmpresa'));
        if (modal) modal.hide();
    });

    // ── Modal Eliminar ───────────────────────────────────────────
    $wire.on('abrirModalEliminar', () => {
        new bootstrap.Modal(document.getElementById('modalEliminarEmpresa')).show();
    });
    $wire.on('cerrarModalEliminar', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarEmpresa'));
        if (modal) modal.hide();
    });

    // ── Modal Asignar Plan ───────────────────────────────────────
    $wire.on('abrirModalPlan', () => {
        new bootstrap.Modal(document.getElementById('modalAsignarPlan')).show();
    });
    $wire.on('cerrarModalPlan', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsignarPlan'));
        if (modal) modal.hide();
    });
</script>
@endscript
