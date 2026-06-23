<div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa fa-file-invoice-dollar me-2 text-primary"></i>Reporte CxP</h5>
            <small class="text-muted">Cuentas por pagar por proveedor y vencimiento</small>
        </div>
    </div>

    {{-- Filtros --}}
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
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Proveedor</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="filtroProveedor" placeholder="Nombre o RUC">
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
                    <label class="form-label fw-semibold small">Vence desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vence hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill" wire:click="generar">
                        <i class="fa fa-search me-1"></i>Generar
                    </button>
                    @can('reporte_cxp.exportar')
                    @if($buscado && $reporte)
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
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if($buscado && $reporte)
    @php $t = $reporte['totales']; $hoy = now()->toDateString(); @endphp

    {{-- Totales --}}
    <div class="mb-2 px-1" style="font-size:0.8rem; line-height:1.8;">
        <span class="text-muted">Total:</span>
        <span class="fw-semibold">S/ {{ number_format($t['total'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Pagado:</span>
        <span class="fw-semibold text-success">S/ {{ number_format($t['pagado'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Saldo:</span>
        <span class="fw-semibold text-warning">S/ {{ number_format($t['saldo'], 2) }}</span>
        <span class="text-muted mx-1">|</span>
        <span class="text-muted">Vencido:</span>
        <span class="fw-semibold text-danger">S/ {{ number_format($t['vencido'], 2) }}</span>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Proveedor</th>
                            <th>Documento</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Pagado</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Pagos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reporte['cuentas'] as $c)
                        @php
                            $vencida  = $c->cp_fecha_vencimiento < $hoy && $c->cp_estado != 3;
                            $pagos    = $reporte['pagosPorCuenta']->get($c->id_cuenta_pagar, collect());
                            $collapseId = 'pagos-cxp-' . $c->id_cuenta_pagar;
                        @endphp
                        <tr class="{{ $vencida ? 'table-danger' : '' }}">
                            <td class="ps-3">
                                <div class="fw-semibold small">{{ $c->proveedores_nombre }}
                                    @if($c->es_vinculada ?? false)
                                        <span class="badge bg-primary ms-1" style="font-size:0.65rem;">Vinculada</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $c->proveedores_numero_documento }}</div>
                            </td>
                            <td class="small">
                                <span class="badge bg-secondary">{{ $c->cp_tipo_doc }}</span>
                                {{ $c->cp_numero_doc }}
                            </td>
                            <td class="small">{{ \Carbon\Carbon::parse($c->cp_fecha_emision)->format('d/m/Y') }}</td>
                            <td class="small {{ $vencida ? 'text-danger fw-semibold' : '' }}">
                                {{ \Carbon\Carbon::parse($c->cp_fecha_vencimiento)->format('d/m/Y') }}
                                @if($vencida)
                                    <br><small class="text-danger">{{ \Carbon\Carbon::parse($c->cp_fecha_vencimiento)->diffInDays(now()) }}d vencido</small>
                                @endif
                            </td>
                            <td class="text-end small">S/ {{ number_format($c->cp_monto_total, 2) }}</td>
                            <td class="text-end small">S/ {{ number_format($c->cp_monto_pagado, 2) }}</td>
                            <td class="text-end small fw-semibold {{ $vencida ? 'text-danger' : '' }}">
                                S/ {{ number_format($c->cp_saldo, 2) }}
                            </td>
                            <td class="text-center">
                                @if($c->cp_estado == 1)
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @elseif($c->cp_estado == 2)
                                    <span class="badge bg-info text-dark">Parcial</span>
                                @else
                                    <span class="badge bg-success">Pagada</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                @if($pagos->count())
                                <button class="btn btn-sm btn-outline-info py-0 px-2"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        title="Ver {{ $pagos->count() }} pago(s)">
                                    <i class="fa fa-clock-rotate-left me-1"></i>{{ $pagos->count() }}
                                </button>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @if($pagos->count())
                        <tr class="collapse" id="{{ $collapseId }}">
                            <td colspan="9" class="p-0">
                                <div class="bg-light border-start border-4 border-info px-3 py-2">
                                    <table class="table table-sm mb-0" style="font-size:0.8rem;">
                                        <thead>
                                            <tr class="text-muted">
                                                <th style="width:30px;">#</th>
                                                <th>Fecha</th>
                                                <th>Medio de Pago</th>
                                                <th class="text-end">Monto</th>
                                                <th>N° Operación</th>
                                                <th>Voucher</th>
                                                <th>Registrado por</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pagos as $i => $pago)
                                            <tr>
                                                <td class="text-muted">{{ $i + 1 }}</td>
                                                <td>{{ \Carbon\Carbon::parse($pago->pcp_fecha)->format('d/m/Y') }}</td>
                                                <td>{{ $pago->tipo_pago_nombre }}</td>
                                                <td class="text-end fw-semibold text-success">S/ {{ number_format($pago->pcp_monto, 2) }}</td>
                                                <td>{{ $pago->pcp_numero_operacion ?: '—' }}</td>
                                                <td>{{ $pago->pcp_voucher ?: '—' }}</td>
                                                <td>{{ $pago->nombre_users }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No hay cuentas por pagar con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @elseif($buscado)
    <div class="text-center text-muted py-5">
        <i class="fa fa-inbox fa-3x mb-3 d-block opacity-50"></i>
        No se encontraron datos con los filtros seleccionados.
    </div>
    @endif

    {{-- Loader grande --}}
    <div wire:loading.flex style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <img src="{{ asset('isologo.ico') }}" style="width:70px;height:70px;object-fit:contain;animation:spin-rep 1s linear infinite;filter:drop-shadow(0 6px 18px rgba(0,0,0,.35));" alt="">
            <div style="color:#fff;font-weight:600;font-size:14px;">Cargando...</div>
        </div>
    </div>
    <style>@keyframes spin-rep{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (event) => { window.open(event.url, '_blank'); });
        });
    </script>

</div>
