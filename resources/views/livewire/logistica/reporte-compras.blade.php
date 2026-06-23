<div>
    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-chart-bar me-2 text-primary"></i>Reporte de Compras
            </h5>
            <small class="text-muted">Órdenes de compra recibidas por proveedor y período</small>
        </div>
        @if($buscando && $totales)
            <div class="d-flex gap-2">
                <button wire:click="exportarPdf" class="btn btn-sm btn-outline-danger" title="Exportar PDF">
                    <i class="fa-solid fa-file-pdf me-1"></i>PDF
                </button>
                <button wire:click="exportarExcel" class="btn btn-sm btn-outline-success" title="Exportar Excel">
                    <i class="fa-solid fa-file-excel me-1"></i>Excel
                </button>
            </div>
        @endif
    </div>

    {{-- ── Flash ──────────────────────────────────────────────── --}}
    @if(session('errorGeneral'))
        <div class="alert alert-danger alert-dismissible py-2 mb-3">
            {{ session('errorGeneral') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filtros ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                {{-- Empresa (solo superadmin: tiene empresas cargadas) --}}
                @if(count($empresas) > 0)
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

                {{-- Sucursal (cuando hay sucursales disponibles) --}}
                @if(count($sucursalesDisponibles) > 0)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm mb-1">Sucursal</label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm">
                            <option value="0">— Todas las sucursales —</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Proveedor --}}
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label form-label-sm mb-1">Proveedor</label>
                    <select wire:model="idProveedor" class="form-select form-select-sm">
                        <option value="">— Todos —</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id_proveedores }}">{{ $prov->proveedores_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Desde --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" wire:model="desde" class="form-control form-control-sm @error('desde') is-invalid @enderror">
                    @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Hasta --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" wire:model="hasta" class="form-control form-control-sm @error('hasta') is-invalid @enderror">
                    @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Agrupación --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Agrupación período</label>
                    <select wire:model="agrupacion" class="form-select form-select-sm">
                        <option value="mensual">Mensual</option>
                        <option value="diario">Diario</option>
                    </select>
                </div>

                {{-- Botón buscar --}}
                <div class="col-6 col-lg-1">
                    <button wire:click="buscar" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tarjetas de resumen ─────────────────────────────────── --}}
    @if($buscando && $totales)
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-2">
                    <div class="fs-4 fw-bold text-primary">{{ number_format($totales->num_ordenes ?? 0) }}</div>
                    <div class="small text-muted">Órdenes recibidas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-2">
                    <div class="fs-4 fw-bold text-info">{{ number_format($totales->num_proveedores ?? 0) }}</div>
                    <div class="small text-muted">Proveedores</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-2">
                    <div class="fs-4 fw-bold text-warning">S/ {{ number_format($totales->total_costo_base ?? 0, 2) }}</div>
                    <div class="small text-muted">Costo base total</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-2">
                    <div class="fs-4 fw-bold text-success">S/ {{ number_format($totales->total_costo ?? 0, 2) }}</div>
                    <div class="small text-muted">Costo total (+ flete)</div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Tabs ────────────────────────────────────────────────── --}}
    @if($buscando)
        <ul class="nav nav-tabs mb-0" style="border-bottom: none;">
            <li class="nav-item">
                <button class="nav-link @if($vistaActiva === 'por_proveedor') active fw-semibold @endif"
                        wire:click="setVista('por_proveedor')">
                    <i class="fa-solid fa-truck me-1"></i>Por Proveedor
                    @if($vistaActiva === 'por_proveedor')
                        <span class="badge bg-primary ms-1">{{ count($resultados) }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link @if($vistaActiva === 'por_periodo') active fw-semibold @endif"
                        wire:click="setVista('por_periodo')">
                    <i class="fa-solid fa-calendar me-1"></i>Por Período
                    @if($vistaActiva === 'por_periodo')
                        <span class="badge bg-primary ms-1">{{ count($porPeriodo) }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="card border-0 shadow-sm" style="border-radius: 0 0.375rem 0.375rem 0.375rem;">
            <div class="card-body p-0">

                {{-- ── Tab: Por Proveedor ──────────────────────── --}}
                @if($vistaActiva === 'por_proveedor')
                    @if(count($resultados) === 0)
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fa-2x mb-2"></i>
                            <p class="mb-0">No hay compras recibidas en el período seleccionado.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.82rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Proveedor</th>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th class="text-center">Cant. recibida</th>
                                        <th class="text-end">Costo base</th>
                                        <th class="text-end">Flete</th>
                                        <th class="text-end">Costo total</th>
                                        <th class="text-end">P.V. referencial</th>
                                        <th class="text-center">OCs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $proveedorActual = null;
                                        $idx = 0;
                                        $subBase = 0; $subFlete = 0; $subTotal = 0;
                                    @endphp
                                    @foreach($resultados as $row)
                                        @if($proveedorActual !== null && $proveedorActual !== $row->id_proveedores)
                                            <tr class="table-light fw-semibold" style="font-size:0.78rem; color:#1e3a5f;">
                                                <td colspan="5" class="ps-3 text-end pe-2">Subtotal</td>
                                                <td class="text-end">S/ {{ number_format($subBase, 2) }}</td>
                                                <td class="text-end">S/ {{ number_format($subFlete, 2) }}</td>
                                                <td class="text-end">S/ {{ number_format($subTotal, 2) }}</td>
                                                <td colspan="2"></td>
                                            </tr>
                                            @php $subBase = 0; $subFlete = 0; $subTotal = 0; @endphp
                                        @endif

                                        @if($proveedorActual !== $row->id_proveedores)
                                            <tr style="background:#e8eef5;">
                                                <td colspan="10" class="ps-3 py-1 fw-bold" style="font-size:0.8rem; color:#1e3a5f;">
                                                    <i class="fa-solid fa-truck me-1"></i>{{ $row->proveedores_nombre }}
                                                </td>
                                            </tr>
                                            @php $proveedorActual = $row->id_proveedores; @endphp
                                        @endif

                                        @php
                                            $idx++;
                                            $subBase  += $row->total_costo_base;
                                            $subFlete += $row->total_flete;
                                            $subTotal += $row->total_costo;
                                        @endphp
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $idx }}</td>
                                            <td></td>
                                            <td class="fw-semibold">{{ $row->pro_nombre }}</td>
                                            <td><span class="badge bg-secondary">{{ $row->pro_codigo }}</span></td>
                                            <td class="text-center">{{ number_format($row->total_cantidad, 2) }}</td>
                                            <td class="text-end">S/ {{ number_format($row->total_costo_base, 2) }}</td>
                                            <td class="text-end text-muted">S/ {{ number_format($row->total_flete, 2) }}</td>
                                            <td class="text-end fw-semibold">S/ {{ number_format($row->total_costo, 2) }}</td>
                                            <td class="text-end text-success">
                                                @if($row->precio_venta_ref > 0)
                                                    S/ {{ number_format($row->precio_venta_ref, 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $row->num_ordenes }}</span>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Último subtotal --}}
                                    @if(count($resultados) > 0)
                                        <tr class="table-light fw-semibold" style="font-size:0.78rem; color:#1e3a5f;">
                                            <td colspan="5" class="ps-3 text-end pe-2">Subtotal</td>
                                            <td class="text-end">S/ {{ number_format($subBase, 2) }}</td>
                                            <td class="text-end">S/ {{ number_format($subFlete, 2) }}</td>
                                            <td class="text-end">S/ {{ number_format($subTotal, 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1e3a5f; color:#fff; font-size:0.83rem;">
                                        <td colspan="5" class="ps-3 text-end fw-bold pe-2">TOTAL GENERAL</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_costo_base ?? 0, 2) }}</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_flete ?? 0, 2) }}</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_costo ?? 0, 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                @endif

                {{-- ── Tab: Por Período ─────────────────────────── --}}
                @if($vistaActiva === 'por_periodo')
                    @if(count($porPeriodo) === 0)
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calendar-xmark fa-2x mb-2"></i>
                            <p class="mb-0">No hay datos para el período seleccionado.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.82rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>{{ $agrupacion === 'diario' ? 'Fecha' : 'Mes' }}</th>
                                        <th class="text-center">Órdenes</th>
                                        <th class="text-center">Proveedores</th>
                                        <th class="text-center">Cant. ítems</th>
                                        <th class="text-end">Costo base</th>
                                        <th class="text-end">Flete</th>
                                        <th class="text-end">Costo total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($porPeriodo as $i => $p)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                            <td class="fw-semibold">{{ $p->periodo_label }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $p->num_ordenes }}</span>
                                            </td>
                                            <td class="text-center">{{ $p->num_proveedores }}</td>
                                            <td class="text-center">{{ number_format($p->total_cantidad, 2) }}</td>
                                            <td class="text-end">S/ {{ number_format($p->total_costo_base, 2) }}</td>
                                            <td class="text-end text-muted">S/ {{ number_format($p->total_flete, 2) }}</td>
                                            <td class="text-end fw-semibold">S/ {{ number_format($p->total_costo, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1e3a5f; color:#fff; font-size:0.83rem;">
                                        <td colspan="5" class="ps-3 fw-bold">TOTAL</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_costo_base ?? 0, 2) }}</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_flete ?? 0, 2) }}</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($totales->total_costo ?? 0, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                @endif

            </div>
        </div>
    @elseif(!$buscando)
        <div class="text-center py-5 text-muted border rounded">
            <i class="fa-solid fa-magnifying-glass fa-2x mb-2"></i>
            <p class="mb-0">Selecciona los filtros y presiona <strong>Buscar</strong> para ver el reporte.</p>
        </div>
    @endif

</div>

@script
<script>
    $wire.on('abrirEnlaces', (event) => { window.open(event.url, '_blank'); });
</script>
@endscript
