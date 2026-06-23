<div>
    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-scale-balanced me-2 text-primary"></i>Conciliación Ventas vs SUNAT
            </h5>
            <small class="text-muted">Comprobantes emitidos vs declarados en el período</small>
        </div>
        @if($buscando && count($detalle) > 0)
            <div class="d-flex gap-2">
                <button wire:click="exportarPdf" class="btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-file-pdf me-1"></i>PDF
                </button>
                <button wire:click="exportarExcel" class="btn btn-sm btn-outline-success">
                    <i class="fa-solid fa-file-excel me-1"></i>Excel
                </button>
            </div>
        @endif
    </div>

    {{-- ── Flash ──────────────────────────────────────────────── --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible py-2 mb-3">
            {{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filtros ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                @if($esSuperAdmin)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm mb-1">Empresa</label>
                        <select wire:model.live="empresaSeleccionada" class="form-select form-select-sm">
                            <option value="0">— Todas las empresas —</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(count($sucursalesDisponibles) > 0)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm mb-1">Sucursal</label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm">
                            <option value="0">— Todas —</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select wire:model="tipoVenta" class="form-select form-select-sm">
                        <option value="">— Todos —</option>
                        <option value="01">Factura</option>
                        <option value="03">Boleta</option>
                        <option value="07">Nota Crédito</option>
                        <option value="08">Nota Débito</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" wire:model="desde"
                           class="form-control form-control-sm @error('desde') is-invalid @enderror">
                    @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" wire:model="hasta"
                           class="form-control form-control-sm @error('hasta') is-invalid @enderror">
                    @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-6 col-lg-1">
                    <button wire:click="buscar" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar
                    </button>
                </div>

            </div>
        </div>
    </div>

    @if(!$buscando)
        <div class="text-center py-5 text-muted border rounded">
            <i class="fa-solid fa-scale-balanced fa-2x mb-2"></i>
            <p class="mb-0">Selecciona el período y presiona <strong>Buscar</strong> para ver la conciliación.</p>
        </div>
    @else

    {{-- ── Tarjetas resumen ────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3" style="border-top:3px solid #6c757d !important;">
                <div class="small text-muted mb-1">Total emitidos</div>
                <div class="fs-3 fw-bold text-dark">{{ $resumenTotales['emitidas'] ?? 0 }}</div>
                <div class="small text-muted">S/ {{ number_format($resumenTotales['montoEmitido'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3" style="border-top:3px solid #198754 !important;">
                <div class="small text-muted mb-1">Declarados a SUNAT</div>
                <div class="fs-3 fw-bold text-success">{{ $resumenTotales['declaradas'] ?? 0 }}</div>
                <div class="small text-muted">S/ {{ number_format($resumenTotales['montoDeclarado'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3" style="border-top:3px solid #dc3545 !important;">
                <div class="small text-muted mb-1">Pendientes de declarar</div>
                <div class="fs-3 fw-bold text-danger">{{ $resumenTotales['pendientes'] ?? 0 }}</div>
                <div class="small text-muted">S/ {{ number_format($resumenTotales['montoPendiente'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3" style="border-top:3px solid #fd7e14 !important;">
                <div class="small text-muted mb-1">Anulados en SUNAT</div>
                <div class="fs-3 fw-bold text-warning">{{ $resumenTotales['anuladas'] ?? 0 }}</div>
                @php
                    $pct = ($resumenTotales['emitidas'] ?? 0) > 0
                        ? round(($resumenTotales['declaradas'] / $resumenTotales['emitidas']) * 100, 1)
                        : 0;
                @endphp
                <div class="small text-muted">{{ $pct }}% declarado</div>
            </div>
        </div>
    </div>

    {{-- ── Barra de progreso de declaración ──────────────────── --}}
    @if(($resumenTotales['emitidas'] ?? 0) > 0)
        <div class="card border-0 shadow-sm mb-3 px-3 py-2">
            <div class="d-flex justify-content-between small mb-1">
                <span class="fw-semibold">Avance de declaración</span>
                <span class="{{ $pct == 100 ? 'text-success fw-bold' : 'text-danger' }}">{{ $pct }}%</span>
            </div>
            <div class="progress" style="height:10px;">
                <div class="progress-bar {{ $pct == 100 ? 'bg-success' : ($pct >= 80 ? 'bg-warning' : 'bg-danger') }}"
                     style="width:{{ $pct }}%"></div>
            </div>
        </div>
    @endif

    {{-- ── Tabs ────────────────────────────────────────────────── --}}
    <ul class="nav nav-tabs mb-0" style="border-bottom:none;">
        <li class="nav-item">
            <button class="nav-link @if($vistaActiva === 'resumen') active fw-semibold @endif"
                    wire:click="setVista('resumen')">
                <i class="fa-solid fa-table me-1"></i>Por tipo
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link @if($vistaActiva === 'detalle') active fw-semibold @endif"
                    wire:click="setVista('detalle')">
                <i class="fa-solid fa-list me-1"></i>Detalle completo
                <span class="badge bg-secondary ms-1">{{ count($detalle) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link @if($vistaActiva === 'pendientes') active fw-semibold @endif"
                    wire:click="setVista('pendientes')">
                <i class="fa-solid fa-clock me-1"></i>Solo pendientes
                @if(($resumenTotales['pendientes'] ?? 0) > 0)
                    <span class="badge bg-danger ms-1">{{ $resumenTotales['pendientes'] }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="card border-0 shadow-sm" style="border-radius:0 0.375rem 0.375rem 0.375rem;">
        <div class="card-body p-0">

            {{-- ── Tab: Por tipo ────────────────────────────── --}}
            @if($vistaActiva === 'resumen')
                @if(count($resumenPorTipo) === 0)
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No hay comprobantes en el período seleccionado.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3">Tipo comprobante</th>
                                    <th class="text-center">Emitidos</th>
                                    <th class="text-center">Declarados</th>
                                    <th class="text-center">Pendientes</th>
                                    <th class="text-center">Anulados</th>
                                    <th class="text-end">Monto emitido</th>
                                    <th class="text-end">Monto declarado</th>
                                    <th class="text-end">Monto pendiente</th>
                                    <th class="text-center">% declarado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resumenPorTipo as $fila)
                                    @php
                                        $pctTipo = $fila->emitidas > 0
                                            ? round(($fila->declaradas / $fila->emitidas) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            @php
                                                $badge = match($fila->tipo) {
                                                    '01' => 'bg-primary',
                                                    '03' => 'bg-info text-dark',
                                                    '07' => 'bg-warning text-dark',
                                                    '08' => 'bg-secondary',
                                                    default => 'bg-light text-dark',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }} me-1">{{ $fila->tipo }}</span>
                                            {{ $fila->label }}
                                        </td>
                                        <td class="text-center">{{ $fila->emitidas }}</td>
                                        <td class="text-center text-success fw-semibold">{{ $fila->declaradas }}</td>
                                        <td class="text-center {{ $fila->pendientes > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            {{ $fila->pendientes }}
                                        </td>
                                        <td class="text-center text-warning">{{ $fila->anuladas }}</td>
                                        <td class="text-end">S/ {{ number_format($fila->monto_emitido, 2) }}</td>
                                        <td class="text-end text-success">S/ {{ number_format($fila->monto_declarado, 2) }}</td>
                                        <td class="text-end {{ $fila->monto_pendiente > 0 ? 'text-danger' : 'text-muted' }}">
                                            S/ {{ number_format($fila->monto_pendiente, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height:8px; min-width:60px;">
                                                <div class="progress-bar {{ $pctTipo == 100 ? 'bg-success' : ($pctTipo >= 80 ? 'bg-warning' : 'bg-danger') }}"
                                                     style="width:{{ $pctTipo }}%"></div>
                                            </div>
                                            <small class="{{ $pctTipo == 100 ? 'text-success' : 'text-danger' }}">{{ $pctTipo }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background:#1e3a5f; color:#fff; font-size:0.83rem;">
                                    <td class="ps-3 fw-bold">TOTAL</td>
                                    <td class="text-center fw-bold">{{ $resumenTotales['emitidas'] ?? 0 }}</td>
                                    <td class="text-center fw-bold">{{ $resumenTotales['declaradas'] ?? 0 }}</td>
                                    <td class="text-center fw-bold">{{ $resumenTotales['pendientes'] ?? 0 }}</td>
                                    <td class="text-center fw-bold">{{ $resumenTotales['anuladas'] ?? 0 }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($resumenTotales['montoEmitido'] ?? 0, 2) }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($resumenTotales['montoDeclarado'] ?? 0, 2) }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($resumenTotales['montoPendiente'] ?? 0, 2) }}</td>
                                    <td class="text-center fw-bold">{{ $pct }}%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            @endif

            {{-- ── Tab: Detalle completo ────────────────────── --}}
            @if($vistaActiva === 'detalle')
                @include('livewire.facturacion.conciliacion-sunat-tabla', ['filas' => $detalle])
            @endif

            {{-- ── Tab: Solo pendientes ────────────────────── --}}
            @if($vistaActiva === 'pendientes')
                @php $soloP = collect($detalle)->where('venta_estado_sunat', 0); @endphp
                @if($soloP->isEmpty())
                    <div class="text-center py-5">
                        <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                        <p class="mb-0 fw-semibold text-success">¡Todo declarado! No hay comprobantes pendientes.</p>
                    </div>
                @else
                    @include('livewire.facturacion.conciliacion-sunat-tabla', ['filas' => $soloP])
                @endif
            @endif

        </div>
    </div>

    @endif {{-- fin buscando --}}

</div>

@script
<script>
    $wire.on('abrirEnlace', (event) => { window.open(event.url, '_blank'); });
</script>
@endscript
