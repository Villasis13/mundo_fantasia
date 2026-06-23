<div>

    {{-- ── Alertas ──────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Modal: Registrar pago ─────────────────────────────────── --}}
    <div class="modal fade" id="modalPagoCxP" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa fa-dollar-sign me-2 text-success"></i>Registrar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        wire:click="cerrarModalPago"></button>
                </div>
                <div class="modal-body">
                    @if($cuentaActual)
                    <div class="alert alert-info py-2 small mb-3">
                        <strong>{{ $cuentaActual->cp_tipo_doc }} {{ $cuentaActual->cp_numero_doc }}</strong><br>
                        Saldo pendiente: <strong>S/ {{ number_format($cuentaActual->cp_saldo, 2) }}</strong>
                    </div>
                    @endif
                    @if($cuotaVinculadaInfo)
                    <div class="alert alert-success py-2 small mb-3 d-flex align-items-center gap-2">
                        <i class="fa fa-link fa-lg flex-shrink-0"></i>
                        <div>
                            Vinculado a CxC <strong>{{ $cuotaVinculadaInfo->venta_serie }}-{{ str_pad($cuotaVinculadaInfo->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</strong>
                            (Cuota {{ $cuotaVinculadaInfo->venta_cuota_numero }}).
                            El pago se sincronizará automáticamente en Cuentas por Cobrar.
                        </div>
                    </div>
                    @endif
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Monto (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm @error('pcpMonto') is-invalid @enderror"
                                   wire:model="pcpMonto">
                            @error('pcpMonto') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm @error('pcpFecha') is-invalid @enderror"
                                   wire:model="pcpFecha">
                            @error('pcpFecha') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label form-label-sm fw-semibold">Medio de pago <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('pcpIdTipoPago') is-invalid @enderror"
                                    wire:model.live="pcpIdTipoPago">
                                <option value="0">— Seleccione —</option>
                                @foreach($tiposPago as $tp)
                                    <option value="{{ $tp->id_tipo_pago }}">{{ $tp->tipo_pago_nombre }}</option>
                                @endforeach
                            </select>
                            @error('pcpIdTipoPago') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        @if($pcpIdTipoPago && $pcpIdTipoPago != 1)
                        <div class="col-md-12">
                            <label class="form-label form-label-sm fw-semibold">N° operación <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('pcpNumeroOperacion') is-invalid @enderror"
                                   wire:model="pcpNumeroOperacion" placeholder="Número de transacción">
                            @error('pcpNumeroOperacion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div class="col-md-12">
                            <label class="form-label form-label-sm fw-semibold">Voucher / referencia</label>
                            <input type="text" class="form-control form-control-sm"
                                   wire:model="pcpVoucher" placeholder="Opcional">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label form-label-sm fw-semibold">Observación</label>
                            <input type="text" class="form-control form-control-sm"
                                   wire:model="pcpObservacion" placeholder="Opcional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="cerrarModalPago">Cancelar</button>
                    <button type="button" class="btn btn-success" wire:click="registrarPago"
                        wire:loading.attr="disabled" wire:target="registrarPago">
                        <span wire:loading wire:target="registrarPago">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa fa-check me-1" wire:loading.remove wire:target="registrarPago"></i>
                        Confirmar pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal: Historial de pagos ─────────────────────────────── --}}
    <div class="modal fade" id="modalHistorialPagosCxP" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa fa-clock-rotate-left me-2 text-info"></i>Historial de Pagos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        wire:click="cerrarModalHistorial"></button>
                </div>
                <div class="modal-body">
                    @if($cuentaHistorial)
                    <div class="alert alert-light border py-2 small mb-3">
                        <strong>{{ $cuentaHistorial->proveedores_nombre }}</strong> —
                        <span class="badge bg-secondary">{{ $cuentaHistorial->cp_tipo_doc }}</span>
                        {{ $cuentaHistorial->cp_numero_doc }}<br>
                        Total: <strong>S/ {{ number_format($cuentaHistorial->cp_monto_total, 2) }}</strong>
                        &nbsp;|&nbsp; Pagado: <strong class="text-success">S/ {{ number_format($cuentaHistorial->cp_monto_pagado, 2) }}</strong>
                        &nbsp;|&nbsp; Saldo: <strong class="text-warning">S/ {{ number_format($cuentaHistorial->cp_saldo, 2) }}</strong>
                    </div>
                    @endif
                    @if(count($historialPagos))
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Medio de Pago</th>
                                    <th class="text-end">Monto</th>
                                    <th>N° Operación</th>
                                    <th>Voucher</th>
                                    <th>Registrado por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historialPagos as $i => $pago)
                                <tr>
                                    <td class="text-muted small">{{ $i + 1 }}</td>
                                    <td class="small">{{ \Carbon\Carbon::parse($pago->pcp_fecha)->format('d/m/Y') }}</td>
                                    <td class="small">{{ $pago->tipo_pago_nombre }}</td>
                                    <td class="text-end small fw-semibold text-success">S/ {{ number_format($pago->pcp_monto, 2) }}</td>
                                    <td class="small">{{ $pago->pcp_numero_operacion ?: '—' }}</td>
                                    <td class="small">{{ $pago->pcp_voucher ?: '—' }}</td>
                                    <td class="small">{{ $pago->nombre_users }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                        No se han registrado pagos para este comprobante.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"
                        wire:click="cerrarModalHistorial">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal: Confirmar anulación ───────────────────────────── --}}
    <div class="modal fade" id="modalAnularCxP" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold text-danger">
                        <i class="fa fa-ban me-2"></i>Anular cuenta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Confirma que desea anular esta cuenta por pagar? Esta acción no se puede revertir.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="anular"
                        wire:loading.attr="disabled" wire:target="anular">
                        <i class="fa fa-ban me-1"></i>Anular
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Cabecera ──────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">
                <i class="fa fa-file-invoice-dollar me-2 text-primary"></i>
                Cuentas por Pagar
            </h5>
            <small class="text-muted">Gestión de obligaciones con proveedores</small>
        </div>
        <div class="d-flex gap-2">
            @can('cuentas_pagar.exportar')
            <button class="btn btn-sm btn-outline-danger fw-semibold"
                    wire:click="exportarPdf"
                    wire:loading.attr="disabled" wire:target="exportarPdf">
                <span wire:loading.remove wire:target="exportarPdf">
                    <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> PDF
                </span>
                <span wire:loading wire:target="exportarPdf">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                </span>
            </button>
            <button class="btn btn-sm btn-outline-success fw-semibold"
                    wire:click="exportarExcel"
                    wire:loading.attr="disabled" wire:target="exportarExcel">
                <span wire:loading.remove wire:target="exportarExcel">
                    <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Excel
                </span>
                <span wire:loading wire:target="exportarExcel">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                </span>
            </button>
            @endcan
        </div>
    </div>

    {{-- ── Filtros ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                @if($esSuperAdmin)
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">Todas las empresas</option>
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
                @endif

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Proveedor</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="filtroProveedor"
                           placeholder="Nombre o RUC">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tipo proveedor</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroVinculada">
                        <option value="">— Todos —</option>
                        <option value="1">Solo vinculadas</option>
                        <option value="0">Excluir vinculadas</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">— Todos —</option>
                        <option value="1">Pendiente</option>
                        <option value="2">Pago parcial</option>
                        <option value="3">Pagada</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vence desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vence hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Mostrar</label>
                    <select class="form-select form-select-sm" wire:model.live="porPagina">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Indicadores compactos ──────────────────────────────────── --}}
    <div class="mb-2 px-1" style="font-size:0.8rem; line-height:1.8;">
        <span class="text-muted">Pendiente:</span>
        <span class="fw-semibold">S/ {{ number_format($indicadores['total_pendiente'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Vencido:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($indicadores['vencido'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Vence en 7d:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($indicadores['por_vencer_7d'], 2) }}</span>
    </div>

    {{-- ── Tabla ──────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="cursor:pointer" wire:click="ordenar('proveedores_nombre')">
                                Proveedor
                                @if($ordenColumna === 'proveedores_nombre')
                                    <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th>Documento</th>
                            <th style="cursor:pointer" wire:click="ordenar('cp_fecha_emision')">
                                Emisión
                                @if($ordenColumna === 'cp_fecha_emision')
                                    <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th style="cursor:pointer" wire:click="ordenar('cp_fecha_vencimiento')">
                                Vencimiento
                                @if($ordenColumna === 'cp_fecha_vencimiento')
                                    <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-end" style="cursor:pointer" wire:click="ordenar('cp_monto_total')">
                                Total
                                @if($ordenColumna === 'cp_monto_total')
                                    <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-end">Pagado</th>
                            <th class="text-end" style="cursor:pointer" wire:click="ordenar('cp_saldo')">
                                Saldo
                                @if($ordenColumna === 'cp_saldo')
                                    <i class="fa fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cuentas as $cuenta)
                        @php
                            $hoy     = now()->toDateString();
                            $vencida = $cuenta->cp_fecha_vencimiento < $hoy
                                       && $cuenta->cp_estado != 3
                                       && $cuenta->cp_estado != 0;
                        @endphp
                        <tr class="{{ $vencida ? 'table-danger' : '' }}">
                            <td class="ps-3">
                                <div class="fw-semibold small">{{ $cuenta->proveedores_nombre }}
                                    @if($cuenta->es_vinculada)
                                        <span class="badge bg-primary ms-1" title="Empresa vinculada del grupo" style="font-size:0.65rem;">Vinculada</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $cuenta->proveedores_numero_documento }}</div>
                            </td>
                            <td class="small">
                                <span class="badge bg-secondary">{{ $cuenta->cp_tipo_doc }}</span>
                                {{ $cuenta->cp_numero_doc }}
                            </td>
                            <td class="small">{{ \Carbon\Carbon::parse($cuenta->cp_fecha_emision)->format('d/m/Y') }}</td>
                            <td class="small {{ $vencida ? 'text-danger fw-semibold' : '' }}">
                                {{ \Carbon\Carbon::parse($cuenta->cp_fecha_vencimiento)->format('d/m/Y') }}
                                @if($vencida)
                                    <br><small class="text-danger">{{ \Carbon\Carbon::parse($cuenta->cp_fecha_vencimiento)->diffInDays(now()) }}d vencido</small>
                                @endif
                            </td>
                            <td class="text-end small">S/ {{ number_format($cuenta->cp_monto_total, 2) }}</td>
                            <td class="text-end small">S/ {{ number_format($cuenta->cp_monto_pagado, 2) }}</td>
                            <td class="text-end small fw-semibold {{ $vencida ? 'text-danger' : '' }}">
                                S/ {{ number_format($cuenta->cp_saldo, 2) }}
                            </td>
                            <td class="text-center">
                                @if($cuenta->cp_estado == 0)
                                    <span class="badge bg-secondary">Anulada</span>
                                @elseif($cuenta->cp_estado == 1)
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @elseif($cuenta->cp_estado == 2)
                                    <span class="badge bg-info text-dark">Parcial</span>
                                @else
                                    <span class="badge bg-success">Pagada</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if($cuenta->cp_monto_pagado > 0)
                                    <button class="btn btn-sm btn-outline-info" title="Ver historial de pagos"
                                            wire:click="abrirModalHistorial({{ $cuenta->id_cuenta_pagar }})">
                                        <i class="fa fa-clock-rotate-left"></i>
                                    </button>
                                    @endif
                                    @can('cuentas_pagar.actualizar')
                                        @if($cuenta->es_vinculada && $cuenta->cp_estado != 0)
                                            @if(!$cuenta->id_vinculado_cxc)
                                            <button class="btn btn-sm btn-outline-primary" title="Vincular a CxC"
                                                    wire:click="abrirModalVincular({{ $cuenta->id_cuenta_pagar }})">
                                                <i class="fa fa-link"></i>
                                            </button>
                                            @else
                                            <button class="btn btn-sm btn-primary"
                                                    title="Sincronizado con CxC {{ $cuenta->cxc_serie }}-{{ str_pad($cuenta->cxc_correlativo ?? '', 8, '0', STR_PAD_LEFT) }} — Clic para desvincular"
                                                    wire:click="desvincular({{ $cuenta->id_cuenta_pagar }})"
                                                    wire:confirm="¿Desconectar la sincronización automática con CxC para esta cuenta?">
                                                <i class="fa fa-link"></i>
                                            </button>
                                            @endif
                                        @endif
                                        @if(in_array($cuenta->cp_estado, [1, 2]))
                                        <button class="btn btn-sm btn-outline-success" title="Registrar pago"
                                                wire:click="abrirModalPago({{ $cuenta->id_cuenta_pagar }})">
                                            <i class="fa fa-dollar-sign"></i>
                                        </button>
                                        @endif
                                    @endcan
                                    @can('cuentas_pagar.cambiar_estado')
                                        @if($cuenta->cp_estado != 0)
                                        <button class="btn btn-sm btn-outline-danger" title="Anular"
                                                wire:click="confirmarAnular({{ $cuenta->id_cuenta_pagar }})">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No hay cuentas por pagar registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cuentas->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $cuentas->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── Modal: Vincular a CxC ───────────────────────────────── --}}
    <div class="modal fade" id="modalVincularCxC" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa fa-link me-2 text-primary"></i>Vincular a Cuenta por Cobrar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        wire:click="cerrarModalVincular"></button>
                </div>
                <div class="modal-body">
                    @if($cuentaVincular)
                    <div class="alert alert-light border py-2 small mb-3">
                        <strong>{{ $cuentaVincular->proveedores_nombre }}</strong> &mdash;
                        <span class="badge bg-secondary">{{ $cuentaVincular->cp_tipo_doc }}</span>
                        {{ $cuentaVincular->cp_numero_doc }}
                        &nbsp;|&nbsp; Monto: <strong>S/ {{ number_format($cuentaVincular->cp_monto_total, 2) }}</strong>
                    </div>
                    @endif
                    <p class="small text-muted mb-2">
                        Seleccione la cuota de venta correspondiente en la empresa proveedora. Una vez vinculadas, cada pago registrado aquí se sincronizará automáticamente en Cuentas por Cobrar.
                    </p>
                    @if(count($cuotasDisponibles) === 0)
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                        No se encontraron cuotas pendientes para vincular en la empresa vinculada.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Comprobante</th>
                                    <th>Cuota</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Importe</th>
                                    <th class="text-end">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cuotasDisponibles as $cuota)
                                <tr class="{{ $idCuotaSeleccionada == $cuota->id_ventas_cuotas ? 'table-primary' : '' }}"
                                    style="cursor:pointer"
                                    wire:click="$set('idCuotaSeleccionada', {{ $cuota->id_ventas_cuotas }})">
                                    <td>
                                        <input type="radio" wire:model="idCuotaSeleccionada"
                                               value="{{ $cuota->id_ventas_cuotas }}"
                                               @checked($idCuotaSeleccionada == $cuota->id_ventas_cuotas)>
                                    </td>
                                    <td class="small">{{ $cuota->venta_serie }}-{{ str_pad($cuota->venta_correlativo, 8, '0', STR_PAD_LEFT) }}</td>
                                    <td class="small">{{ $cuota->venta_cuota_numero }}</td>
                                    <td class="small">{{ \Carbon\Carbon::parse($cuota->venta_cuota_fecha)->format('d/m/Y') }}</td>
                                    <td class="text-end small">S/ {{ number_format($cuota->venta_cuota_importe, 2) }}</td>
                                    <td class="text-end small fw-semibold">S/ {{ number_format($cuota->saldo, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="cerrarModalVincular">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="vincularCuota"
                        wire:loading.attr="disabled" wire:target="vincularCuota"
                        @disabled(!$idCuotaSeleccionada)>
                        <i class="fa fa-link me-1"></i> Vincular
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Loader grande --}}
    <div wire:loading.flex style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <img src="{{ asset('isologo.ico') }}" style="width:70px;height:70px;object-fit:contain;animation:spin-rep 1s linear infinite;filter:drop-shadow(0 6px 18px rgba(0,0,0,.35));" alt="">
            <div style="color:#fff;font-weight:600;font-size:14px;">Cargando...</div>
        </div>
    </div>
    <style>@keyframes spin-rep{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>

    {{-- ── Scripts ────────────────────────────────────────────────── --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (event) => {
                window.open(event.url, '_blank');
            });
            Livewire.on('abrirModalPago', () => {
                new bootstrap.Modal(document.getElementById('modalPagoCxP')).show();
            });
            Livewire.on('cerrarModalPago', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalPagoCxP'));
                if (m) m.hide();
            });
            Livewire.on('abrirModalAnular', () => {
                new bootstrap.Modal(document.getElementById('modalAnularCxP')).show();
            });
            Livewire.on('cerrarModalAnular', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalAnularCxP'));
                if (m) m.hide();
            });
            Livewire.on('abrirModalHistorial', () => {
                new bootstrap.Modal(document.getElementById('modalHistorialPagosCxP')).show();
            });
            Livewire.on('cerrarModalHistorial', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalHistorialPagosCxP'));
                if (m) m.hide();
            });
            Livewire.on('abrirModalVincular', () => {
                new bootstrap.Modal(document.getElementById('modalVincularCxC')).show();
            });
            Livewire.on('cerrarModalVincular', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalVincularCxC'));
                if (m) m.hide();
            });
        });
    </script>

</div>
