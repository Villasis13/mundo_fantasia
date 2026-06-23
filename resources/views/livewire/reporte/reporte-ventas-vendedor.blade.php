<div>
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Reporte de Ventas por Vendedor</h5>
                <small class="text-muted">
                    Consulta el resumen y detalle de ventas agrupadas por vendedor.
                </small>
            </div>

            <div class="row align-items-end g-2">

                {{-- ── Empresa (SuperAdmin y Admin) ──────────────── --}}
                @if($esSuperAdmin || $esAdmin)
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Empresa:</label>
                        <select wire:model.live="empresaSeleccionada" class="form-select">
                            <option value="0">Seleccionar Empresa</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- ── Sucursal ───────────────────────────────────── --}}
                @if(count($sucursalesDisponibles) > 0 && ($empresaSeleccionada > 0 || $esAdmin))
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Sucursal:</label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select">
                            <option value="0">Todas las sucursales</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- ── Desde ──────────────────────────────────────── --}}
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Desde:</label>
                    <input type="date" class="form-control @error('desde') is-invalid @enderror"
                           wire:model="desde">
                    @error('desde') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                {{-- ── Hasta ──────────────────────────────────────── --}}
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Hasta:</label>
                    <input type="date" class="form-control @error('hasta') is-invalid @enderror"
                           wire:model="hasta" min="{{ $desde }}">
                    @error('hasta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                {{-- ── Vendedor (solo rol Ventas) ──────────────────── --}}
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Vendedor:</label>
                    <select wire:model="idVendedor" class="form-select"
                            @if(!$idEmpresaActiva) disabled @endif>
                        <option value="">Todos</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->id_users }}">{{ $v->nombre_users }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ── Buscar + Exportar ───────────────────────────── --}}
                <div class="col-lg-auto col-md-auto col-sm-12 d-flex gap-1 align-items-end">
                    @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                        <button class="btn btn-primary" disabled title="Selecciona primero una empresa">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    @else
                        <button class="btn btn-primary"
                                wire:click="listarRegistros"
                                wire:loading.attr="disabled"
                                wire:target="listarRegistros"
                                title="Buscar">
                            <span wire:loading wire:target="listarRegistros">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                            <i class="fa-solid fa-search" wire:loading.remove wire:target="listarRegistros"></i>
                        </button>
                    @endif

                    @can('reporte_ventas.exportar')
                    <button class="btn btn-outline-success"
                            wire:click="imprimirExcel"
                            wire:loading.attr="disabled"
                            wire:target="imprimirExcel"
                            title="Exportar Excel"
                            @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva) disabled @endif>
                        <span wire:loading.remove wire:target="imprimirExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;">
                        </span>
                        <span wire:loading wire:target="imprimirExcel">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </button>
                    <button class="btn btn-outline-danger"
                            wire:click="imprimirPdf"
                            wire:loading.attr="disabled"
                            wire:target="imprimirPdf"
                            title="Exportar PDF"
                            @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva) disabled @endif>
                        <span wire:loading.remove wire:target="imprimirPdf">
                            <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:18px;height:18px;vertical-align:middle;">
                        </span>
                        <span wire:loading wire:target="imprimirPdf">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </button>
                    @endcan
                </div>

            </div>

            {{-- Aviso SuperAdmin sin empresa --}}
            @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                <div class="alert alert-warning py-2 mt-3 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Selecciona primero una empresa para visualizar el reporte.
                </div>
            @endif

        </div>

        <div class="card-body">

            @if(session('errorGeneral'))
                <div class="alert alert-danger alert-dismissible mb-3">
                    {{ session('errorGeneral') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($buscar)

                {{-- ── Tarjetas resumen general ────────────────────── --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#0b1892,#2257f1);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Total Vendido</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->total_ventas, 2) }}</h4>
                                <small class="opacity-75">{{ $totalesResumen->cantidad }} comprobante(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#5a9900,#aadd00);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Facturas</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->total_facturas, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#b3009e,#e600cc);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Boletas</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->total_boletas, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#3a3a5c,#6c6c9a);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Notas de Venta</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->total_nv, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Tabla resumen por vendedor ───────────────────── --}}
                <h6 class="fw-bold mb-3" style="color:#0b1892;">
                    <i class="fa-solid fa-users me-2"></i> Resumen por Vendedor
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th class="text-start">Vendedor</th>
                            <th>Total Ventas</th>
                            <th>Facturas</th>
                            <th>Boletas</th>
                            <th>Notas de Venta</th>
                            <th>Comprobantes</th>
                            <th>% del Total</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resumenVendedores as $i => $v)
                            @php
                                $porcentaje     = $totalesResumen->total_ventas > 0
                                    ? round(($v->total_ventas / $totalesResumen->total_ventas) * 100, 1)
                                    : 0;
                                $esSeleccionado = $vendedorSeleccionado == $v->id_users;
                            @endphp
                            <tr class="{{ $esSeleccionado ? 'table-primary' : '' }}">
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td><div class="fw-semibold">{{ $v->nombre_users }}</div></td>
                                <td class="text-end fw-bold" style="color:#0b1892;">
                                    S/ {{ number_format($v->total_ventas, 2) }}
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold small" style="color:#5a9900;">S/ {{ number_format($v->total_facturas, 2) }}</div>
                                    <small class="text-muted">{{ $v->cant_facturas }} comp.</small>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold small" style="color:#b3009e;">S/ {{ number_format($v->total_boletas, 2) }}</div>
                                    <small class="text-muted">{{ $v->cant_boletas }} comp.</small>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold small text-secondary">S/ {{ number_format($v->total_nv, 2) }}</div>
                                    <small class="text-muted">{{ $v->cant_nv }} comp.</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fw-bold">{{ $v->cantidad }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;">
                                            <div class="progress-bar" style="width:{{ $porcentaje }}%;background:#0b1892;"></div>
                                        </div>
                                        <small class="text-muted fw-semibold" style="min-width:38px;">{{ $porcentaje }}%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($esSeleccionado)
                                        <button class="btn btn-sm btn-secondary" wire:click="cerrarDetalle">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-primary"
                                                wire:click="verDetalle({{ $v->id_users }}, '{{ addslashes($v->nombre_users) }}')"
                                                title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron ventas en el período seleccionado.
                                </td>
                            </tr>
                        @endforelse

                        @if($resumenVendedores->count())
                            <tr style="background:#eef1ff;">
                                <td colspan="2" class="fw-bold" style="color:#0b1892;">TOTAL GENERAL</td>
                                <td class="text-end fw-bold" style="color:#0b1892;">S/ {{ number_format($totalesResumen->total_ventas, 2) }}</td>
                                <td class="text-end fw-bold" style="color:#5a9900;">S/ {{ number_format($totalesResumen->total_facturas, 2) }}</td>
                                <td class="text-end fw-bold" style="color:#b3009e;">S/ {{ number_format($totalesResumen->total_boletas, 2) }}</td>
                                <td class="text-end fw-bold text-secondary">S/ {{ number_format($totalesResumen->total_nv, 2) }}</td>
                                <td class="text-center fw-bold">{{ $totalesResumen->cantidad }}</td>
                                <td class="text-center fw-bold">100%</td>
                                <td></td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                {{-- ── Detalle del vendedor seleccionado ───────────── --}}
                @if($vendedorSeleccionado && $detalleVentas->count() >= 0)
                    <div class="card border-0 shadow-sm mt-2">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h6 class="fw-bold mb-0" style="color:#0b1892;">
                                    <i class="fa-solid fa-list me-2"></i>
                                    Detalle de ventas — {{ $nombreVendedorDetalle }}
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="text-muted small mb-0">Mostrar</label>
                                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                    <label class="text-muted small mb-0">registros</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                    <tr class="encabezado_tabla_color text-center">
                                        <th>#</th>
                                        <th style="cursor:pointer;" wire:click="ordenar('venta_fecha')">
                                            Fecha
                                            @if($ordenColumna === 'venta_fecha')
                                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                            @endif
                                        </th>
                                        <th>Tipo</th>
                                        <th>Comprobante</th>
                                        <th style="cursor:pointer;" wire:click="ordenar('cliente_nombre')">
                                            Cliente
                                            @if($ordenColumna === 'cliente_nombre')
                                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                            @endif
                                        </th>
                                        <th>Forma de Pago</th>
                                        <th style="cursor:pointer;" wire:click="ordenar('venta_total')">
                                            Total
                                            @if($ordenColumna === 'venta_total')
                                                <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                            @endif
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($detalleVentas as $index => $venta)
                                        @php
                                            $cliente  = $venta->id_tipo_documento == 4
                                                ? $venta->cliente_razonsocial
                                                : $venta->cliente_nombre;
                                            $tipoComp = match($venta->venta_tipo) {
                                                '01' => 'FACTURA',
                                                '03' => 'BOLETA',
                                                '20' => 'NOTA VENTA',
                                                default => $venta->venta_tipo,
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted">{{ $detalleVentas->firstItem() + $index }}</td>
                                            <td class="small">
                                                <span class="d-block">{{ date('d/m/Y', strtotime($venta->venta_fecha)) }}</span>
                                                <span class="text-muted">{{ date('H:i', strtotime($venta->venta_fecha)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary small">{{ $tipoComp }}</span>
                                            </td>
                                            <td class="text-center small fw-semibold">
                                                {{ $venta->venta_serie }}-{{ $venta->venta_correlativo }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold small">{{ Str::limit($cliente, 25) }}</div>
                                                <small class="text-muted">{{ $venta->cliente_numero }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $venta->id_formas_pago == 1 ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    {{ $venta->id_formas_pago == 1 ? 'Contado' : 'Crédito' }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold" style="color:#0b1892;">
                                                {{ $venta->simbolo }}{{ number_format($venta->venta_total, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No se encontraron ventas para este vendedor en el período.
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($detalleVentas->count())
                                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <small class="text-muted">
                                        Mostrando {{ $detalleVentas->firstItem() }} - {{ $detalleVentas->lastItem() }}
                                        de {{ $detalleVentas->total() }} registros
                                    </small>
                                    {{ $detalleVentas->links(data: ['scrollTo' => false]) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            @else
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-25"></i>
                    @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                        Selecciona una empresa para comenzar.
                    @else
                        Selecciona un rango de fechas y presiona <strong>Buscar</strong>.
                    @endif
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="listarRegistros,verDetalle,cerrarDetalle">
        <x-loader />
    </div>
</div>

@script
<script>
    $wire.on('abrirEnlaces', (event) => {
        window.open(event.url, '_blank');
    });
</script>
@endscript
