<div>
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Productos Más y Menos Vendidos</h5>
                <small class="text-muted">
                    Ranking de productos por cantidad vendida y monto facturado, con comparativa mensual.
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

                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Desde:</label>
                    <input type="date" class="form-control @error('desde') is-invalid @enderror"
                           wire:model="desde">
                    @error('desde') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Hasta:</label>
                    <input type="date" class="form-control @error('hasta') is-invalid @enderror"
                           wire:model="hasta" min="{{ $desde }}">
                    @error('hasta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                @if($idEmpresaActiva)
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Categor&iacute;a:</label>
                        <select wire:model.live="idCategoria" class="form-select">
                            <option value="">Todas</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id_ca }}">{{ $cat->ca_nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Mostrar Top:</label>
                        <select wire:model.live="topN" class="form-select">
                            <option value="5">Top 5</option>
                            <option value="10">Top 10</option>
                            <option value="15">Top 15</option>
                            <option value="20">Top 20</option>
                            <option value="50">Top 50</option>
                        </select>
                    </div>
                @endif

                <div class="col-lg-12 col-md-12 d-flex gap-2 align-items-end flex-wrap justify-content-end mt-4">
                    @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                        <button class="btn btn-primary" disabled
                                data-bs-tooltip="tooltip" data-bs-placement="top"
                                data-bs-title="Selecciona primero una empresa">
                            <i class="fa-solid fa-search me-1"></i> Buscar
                        </button>
                    @else
                        <button class="btn btn-primary"
                                wire:click="listarRegistros"
                                wire:loading.attr="disabled"
                                wire:target="listarRegistros">
                            <span wire:loading wire:target="listarRegistros">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                            </span>
                            <i class="fa-solid fa-search me-1" wire:loading.remove wire:target="listarRegistros"></i>
                            Buscar
                        </button>
                    @endif
                    @if($buscar)
                        @can('reporte_productos.exportar')
                        <button class="btn btn-outline-success" wire:click="imprimirExcel"
                                wire:loading.attr="disabled" wire:target="imprimirExcel">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </button>
                        <button class="btn btn-outline-danger" wire:click="imprimirPdf"
                                wire:loading.attr="disabled" wire:target="imprimirPdf">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </button>
                        @endcan
                    @endif
                </div>

            </div>

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

                {{-- Tarjetas resumen --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#0b1892,#2257f1);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Productos Vendidos</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">{{ number_format($resumenGeneral->total_productos) }}</h4>
                                <small class="opacity-75">Productos distintos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#5a9900,#aadd00);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Unidades Vendidas</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">{{ number_format($resumenGeneral->total_unidades, 0) }}</h4>
                                <small class="opacity-75">Total de unidades</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#b3009e,#e600cc);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Monto Total</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($resumenGeneral->total_monto, 2) }}</h4>
                                <small class="opacity-75">En comprobantes vigentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#3a3a5c,#6c6c9a);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Comprobantes</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">{{ number_format($resumenGeneral->total_comprobantes) }}</h4>
                                <small class="opacity-75">Con productos vendidos</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabs de vista --}}
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <button wire:click="cambiarVista('cantidad')" class="nav-link {{ $vistaActiva === 'cantidad' ? 'active fw-semibold' : '' }}">
                            <i class="fa-solid fa-sort-amount-down me-1"></i> Por Cantidad
                        </button>
                    </li>
                    <li class="nav-item">
                        <button wire:click="cambiarVista('monto')" class="nav-link {{ $vistaActiva === 'monto' ? 'active fw-semibold' : '' }}">
                            <i class="fa-solid fa-dollar-sign me-1"></i> Por Monto
                        </button>
                    </li>
                    <li class="nav-item">
                        <button wire:click="cambiarVista('comparativo')" class="nav-link {{ $vistaActiva === 'comparativo' ? 'active fw-semibold' : '' }}">
                            <i class="fa-solid fa-chart-bar me-1"></i> Comparativo Mensual
                        </button>
                    </li>
                    <li class="nav-item">
                        <button wire:click="cambiarVista('sin_rotacion')" class="nav-link {{ $vistaActiva === 'sin_rotacion' ? 'active fw-semibold' : '' }}">
                            <i class="fa-solid fa-box-open me-1"></i> Sin Rotaci&oacute;n
                            @if($sinRotacion->count() > 0)
                                <span class="badge bg-danger ms-1">{{ $sinRotacion->count() }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item">
                        <button wire:click="cambiarVista('tabla')" class="nav-link {{ $vistaActiva === 'tabla' ? 'active fw-semibold' : '' }}">
                            <i class="fa-solid fa-table me-1"></i> Tabla Completa
                        </button>
                    </li>
                </ul>

                @if($vistaActiva === 'cantidad')
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-1" style="color:#0b1892;"><i class="fa-solid fa-trophy text-warning me-2"></i>Top {{ $topN }} &mdash; M&aacute;s vendidos por cantidad</h6>
                                    <small class="text-muted d-block mb-3">Unidades vendidas en el per&iacute;odo</small>
                                    <div wire:ignore><div id="grafico-top-cantidad"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-0">
                                    <div class="p-3 border-bottom"><h6 class="fw-bold mb-0" style="color:#0b1892;"><i class="fa-solid fa-ranking-star text-success me-2"></i>Ranking Top {{ $topN }}</h6></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead><tr class="encabezado_tabla_color text-center"><th>#</th><th class="text-start">Producto</th><th>Unidades</th><th>Total</th></tr></thead>
                                            <tbody>
                                            @foreach($rankingCantidad as $i => $p)
                                                <tr>
                                                    <td class="text-center">@if($i==0)🥇@elseif($i==1)🥈@elseif($i==2)🥉@else<span class="text-muted fw-semibold">{{$i+1}}</span>@endif</td>
                                                    <td><div class="fw-semibold small">{{ Str::limit($p->pro_nombre,22) }}</div><small class="text-muted">{{ $p->ca_nombre }}</small></td>
                                                    <td class="text-center fw-bold" style="color:#0b1892;">{{ number_format($p->total_cantidad,0) }}</td>
                                                    <td class="text-end small">S/ {{ number_format($p->total_monto,2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3" style="color:#b30000;"><i class="fa-solid fa-arrow-trend-down me-2"></i>Bottom {{ $topN }} &mdash; Menos vendidos por cantidad</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead><tr class="encabezado_tabla_color text-center"><th>#</th><th class="text-start">Producto</th><th>C&oacute;digo</th><th>Categor&iacute;a</th><th>Unidades</th><th>Monto</th><th>Comp.</th><th>Stock</th></tr></thead>
                                            <tbody>
                                            @foreach($bottomCantidad as $i => $p)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $i+1 }}</td>
                                                    <td><div class="fw-semibold small">{{ $p->pro_nombre }}</div></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->pro_codigo }}</span></td>
                                                    <td class="small text-muted">{{ $p->ca_nombre }}</td>
                                                    <td class="text-center fw-semibold text-danger">{{ number_format($p->total_cantidad,0) }}</td>
                                                    <td class="text-end small">S/ {{ number_format($p->total_monto,2) }}</td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->en_comprobantes }}</span></td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $p->stock_actual <= 0 ? 'bg-danger' : ($p->stock_actual <= 10 ? 'bg-warning text-dark' : 'bg-success') }}">
                                                            {{ $p->stock_actual }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($vistaActiva === 'monto')
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-1" style="color:#0b1892;"><i class="fa-solid fa-trophy text-warning me-2"></i>Top {{ $topN }} &mdash; M&aacute;s vendidos por monto</h6>
                                    <small class="text-muted d-block mb-3">Monto total facturado en el per&iacute;odo</small>
                                    <div wire:ignore><div id="grafico-top-monto"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-0">
                                    <div class="p-3 border-bottom"><h6 class="fw-bold mb-0" style="color:#0b1892;"><i class="fa-solid fa-ranking-star text-success me-2"></i>Ranking Top {{ $topN }}</h6></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead><tr class="encabezado_tabla_color text-center"><th>#</th><th class="text-start">Producto</th><th>Monto</th><th>Unid.</th></tr></thead>
                                            <tbody>
                                            @foreach($rankingMonto as $i => $p)
                                                <tr>
                                                    <td class="text-center">@if($i==0)🥇@elseif($i==1)🥈@elseif($i==2)🥉@else<span class="text-muted fw-semibold">{{$i+1}}</span>@endif</td>
                                                    <td><div class="fw-semibold small">{{ Str::limit($p->pro_nombre,22) }}</div><small class="text-muted">{{ $p->ca_nombre }}</small></td>
                                                    <td class="text-end fw-bold" style="color:#0b1892;">S/ {{ number_format($p->total_monto,2) }}</td>
                                                    <td class="text-center small text-muted">{{ number_format($p->total_cantidad,0) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3" style="color:#b30000;"><i class="fa-solid fa-arrow-trend-down me-2"></i>Bottom {{ $topN }} &mdash; Menos vendidos por monto</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead><tr class="encabezado_tabla_color text-center"><th>#</th><th class="text-start">Producto</th><th>C&oacute;digo</th><th>Categor&iacute;a</th><th>Monto</th><th>Unidades</th><th>Precio Prom.</th><th>Stock</th></tr></thead>
                                            <tbody>
                                            @foreach($bottomMonto as $i => $p)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $i+1 }}</td>
                                                    <td><div class="fw-semibold small">{{ $p->pro_nombre }}</div></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->pro_codigo }}</span></td>
                                                    <td class="small text-muted">{{ $p->ca_nombre }}</td>
                                                    <td class="text-end fw-semibold text-danger">S/ {{ number_format($p->total_monto,2) }}</td>
                                                    <td class="text-center">{{ number_format($p->total_cantidad,0) }}</td>
                                                    <td class="text-end small">S/ {{ number_format($p->precio_promedio,2) }}</td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $p->stock_actual <= 0 ? 'bg-danger' : ($p->stock_actual <= 10 ? 'bg-warning text-dark' : 'bg-success') }}">
                                                            {{ $p->stock_actual }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($vistaActiva === 'comparativo')
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-1" style="color:#0b1892;"><i class="fa-solid fa-chart-bar me-2"></i> Unidades por Mes</h6>
                                    <small class="text-muted d-block mb-3">Total de unidades vendidas por mes</small>
                                    <div wire:ignore><div id="grafico-comp-cantidad"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-1" style="color:#0b1892;"><i class="fa-solid fa-chart-line me-2"></i> Monto por Mes</h6>
                                    <small class="text-muted d-block mb-3">Total facturado por mes</small>
                                    <div wire:ignore><div id="grafico-comp-monto"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3" style="color:#0b1892;"><i class="fa-solid fa-table me-2"></i> Detalle por Mes</h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead><tr class="encabezado_tabla_color text-center"><th>Mes</th><th>Unidades Vendidas</th><th>Monto Total</th><th>Productos Distintos</th><th>Promedio x Mes</th></tr></thead>
                                            <tbody>
                                            @php $totalUC = $comparativaMeses->sum('total_cantidad'); $totalMC = $comparativaMeses->sum('total_monto'); @endphp
                                            @foreach($comparativaMeses as $mes)
                                                <tr>
                                                    <td class="fw-semibold text-center">{{ $mes->mes_label }}</td>
                                                    <td class="text-center fw-bold" style="color:#0b1892;">{{ number_format($mes->total_cantidad,0) }}</td>
                                                    <td class="text-end fw-bold" style="color:#5a9900;">S/ {{ number_format($mes->total_monto,2) }}</td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $mes->productos_distintos }}</span></td>
                                                    <td class="text-end small text-muted">S/ {{ $mes->productos_distintos > 0 ? number_format($mes->total_monto/$mes->productos_distintos,2) : '0.00' }}</td>
                                                </tr>
                                            @endforeach
                                            @if($comparativaMeses->count())
                                                <tr style="background:#eef1ff;">
                                                    <td class="fw-bold" style="color:#0b1892;">TOTAL</td>
                                                    <td class="text-center fw-bold" style="color:#0b1892;">{{ number_format($totalUC,0) }}</td>
                                                    <td class="text-end fw-bold" style="color:#5a9900;">S/ {{ number_format($totalMC,2) }}</td>
                                                    <td></td><td></td>
                                                </tr>
                                            @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($vistaActiva === 'sin_rotacion')
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-0" style="color:#b30000;">
                                        <i class="fa-solid fa-box-open me-2"></i>Productos sin rotaci&oacute;n en el per&iacute;odo
                                    </h6>
                                    <small class="text-muted">Productos activos que no registraron ninguna venta entre {{ $desde }} y {{ $hasta }}</small>
                                </div>
                                <span class="badge bg-danger fs-6 px-3 py-2">
                                    {{ $sinRotacion->count() }} {{ $sinRotacion->count() === 1 ? 'producto' : 'productos' }}
                                </span>
                            </div>
                            @if($sinRotacion->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead>
                                            <tr class="encabezado_tabla_color text-center">
                                                <th>#</th>
                                                <th class="text-start">Producto</th>
                                                <th>C&oacute;digo</th>
                                                <th>Categor&iacute;a</th>
                                                <th>Stock actual</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($sinRotacion as $i => $p)
                                            <tr>
                                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                                <td><div class="fw-semibold small">{{ $p->pro_nombre }}</div></td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border">{{ $p->pro_codigo ?: '—' }}</span>
                                                </td>
                                                <td class="small text-muted">{{ $p->ca_nombre }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $p->stock_actual > 0 ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                                        {{ number_format($p->stock_actual, 0) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($sinRotacion->count() >= 100)
                                    <p class="text-muted small text-center mt-2 mb-0">
                                        <i class="fa-solid fa-circle-info me-1"></i>Se muestran los primeros 100 productos ordenados por stock mayor.
                                    </p>
                                @endif
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="fa-solid fa-circle-check fa-2x mb-2 d-block" style="color:#5a9900; opacity:.6;"></i>
                                    Todos los productos activos tuvieron al menos una venta en este per&iacute;odo.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($vistaActiva === 'tabla')
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <label class="text-muted small mb-0">Mostrar</label>
                        <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                            <option value="15">15</option><option value="25">25</option>
                            <option value="50">50</option><option value="100">100</option>
                        </select>
                        <label class="text-muted small mb-0">registros</label>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr class="encabezado_tabla_color text-center">
                                <th>#</th>
                                <th style="cursor:pointer;" wire:click="ordenar('pro_nombre')" class="text-start">
                                    Producto @if($ordenColumna==='pro_nombre')<i class="fa-solid fa-sort-{{ $ordenDireccion==='asc'?'up':'down' }} ms-1"></i>@else<i class="fa-solid fa-sort ms-1 opacity-25"></i>@endif
                                </th>
                                <th>C&oacute;digo</th><th>Categor&iacute;a</th>
                                <th style="cursor:pointer;" wire:click="ordenar('total_cantidad')">
                                    Unidades @if($ordenColumna==='total_cantidad')<i class="fa-solid fa-sort-{{ $ordenDireccion==='asc'?'up':'down' }} ms-1"></i>@else<i class="fa-solid fa-sort ms-1 opacity-25"></i>@endif
                                </th>
                                <th style="cursor:pointer;" wire:click="ordenar('total_monto')">
                                    Monto @if($ordenColumna==='total_monto')<i class="fa-solid fa-sort-{{ $ordenDireccion==='asc'?'up':'down' }} ms-1"></i>@else<i class="fa-solid fa-sort ms-1 opacity-25"></i>@endif
                                </th>
                                <th style="cursor:pointer;" wire:click="ordenar('precio_promedio')">
                                    Precio Prom. @if($ordenColumna==='precio_promedio')<i class="fa-solid fa-sort-{{ $ordenDireccion==='asc'?'up':'down' }} ms-1"></i>@else<i class="fa-solid fa-sort ms-1 opacity-25"></i>@endif
                                </th>
                                <th style="cursor:pointer;" wire:click="ordenar('en_comprobantes')">
                                    Comp. @if($ordenColumna==='en_comprobantes')<i class="fa-solid fa-sort-{{ $ordenDireccion==='asc'?'up':'down' }} ms-1"></i>@else<i class="fa-solid fa-sort ms-1 opacity-25"></i>@endif
                                </th>
                                <th>Stock</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tablaCompleta as $index => $p)
                                <tr>
                                    <td class="text-center text-muted">{{ $tablaCompleta->firstItem() + $index }}</td>
                                    <td><div class="fw-semibold small">{{ $p->pro_nombre }}</div></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->pro_codigo }}</span></td>
                                    <td class="small text-muted">{{ $p->ca_nombre }}</td>
                                    <td class="text-center fw-bold" style="color:#0b1892;">{{ number_format($p->total_cantidad,0) }}</td>
                                    <td class="text-end fw-bold" style="color:#5a9900;">S/ {{ number_format($p->total_monto,2) }}</td>
                                    <td class="text-end small">S/ {{ number_format($p->precio_promedio,2) }}</td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->en_comprobantes }}</span></td>
                                    <td class="text-center">
                                        <span class="badge {{ $p->stock_actual <= 0 ? 'bg-danger' : ($p->stock_actual <= 10 ? 'bg-warning text-dark' : 'bg-success') }}">
                                            {{ $p->stock_actual }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                        No se encontraron productos con ventas en el per&iacute;odo.
                                    </td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($tablaCompleta->count())
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                            <small class="text-muted">Mostrando {{ $tablaCompleta->firstItem() }} - {{ $tablaCompleta->lastItem() }} de {{ $tablaCompleta->total() }} productos</small>
                            {{ $tablaCompleta->links(data: ['scrollTo' => false]) }}
                        </div>
                    @endif
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

    <div wire:loading wire:target="listarRegistros,cambiarVista">
        <x-loader />
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endassets

@script
<script>
    const ASSU = { azul:'#0b1892', azulElec:'#2257f1', lima:'#aadd00', magenta:'#e600cc', gris:'#6c6c9a' };
    let chartTopCantidad=null, chartTopMonto=null, chartCompCantidad=null, chartCompMonto=null;
    function destruir(c) { if(c){try{c.destroy();}catch(e){}} return null; }

    $wire.on('abrirEnlaces', (event) => { window.open(event.url, '_blank'); });

    $wire.on('actualizarGraficosProductos', (event) => {
        setTimeout(() => {
            const data = Array.isArray(event) ? event[0] : event;
            renderTopCantidad(data.rankingCantidad);
            renderTopMonto(data.rankingMonto);
            renderCompCantidad(data.comparativa);
            renderCompMonto(data.comparativa);
        }, 150);
    });

    function renderTopCantidad(data) {
        const el = document.querySelector('#grafico-top-cantidad'); if(!el) return;
        chartTopCantidad = destruir(chartTopCantidad);
        chartTopCantidad = new ApexCharts(el, {
            series:[{name:'Unidades',data:data.totales}],
            chart:{type:'bar',height:300,toolbar:{show:false},fontFamily:'inherit'},
            colors:[ASSU.azul], plotOptions:{bar:{borderRadius:4,horizontal:true,barHeight:'60%'}},
            dataLabels:{enabled:true,formatter:(v)=>Math.round(v)+' u.',style:{fontSize:'10px'}},
            xaxis:{categories:data.labels,labels:{style:{fontSize:'11px'}}},
            tooltip:{y:{formatter:(v)=>Math.round(v)+' unidades'}},
            grid:{borderColor:'#f0f0f0',strokeDashArray:4},
        });
        chartTopCantidad.render();
    }

    function renderTopMonto(data) {
        const el = document.querySelector('#grafico-top-monto'); if(!el) return;
        chartTopMonto = destruir(chartTopMonto);
        chartTopMonto = new ApexCharts(el, {
            series:[{name:'Monto',data:data.totales}],
            chart:{type:'bar',height:300,toolbar:{show:false},fontFamily:'inherit'},
            colors:[ASSU.magenta], plotOptions:{bar:{borderRadius:4,horizontal:true,barHeight:'60%'}},
            dataLabels:{enabled:false},
            xaxis:{categories:data.labels,labels:{formatter:(v)=>'S/ '+parseFloat(v).toLocaleString('es-PE',{minimumFractionDigits:0}),style:{fontSize:'11px'}}},
            tooltip:{y:{formatter:(v)=>'S/ '+parseFloat(v).toLocaleString('es-PE',{minimumFractionDigits:2})}},
            grid:{borderColor:'#f0f0f0',strokeDashArray:4},
        });
        chartTopMonto.render();
    }

    function renderCompCantidad(data) {
        const el = document.querySelector('#grafico-comp-cantidad'); if(!el) return;
        chartCompCantidad = destruir(chartCompCantidad);
        chartCompCantidad = new ApexCharts(el, {
            series:[{name:'Unidades',data:data.cantidad}],
            chart:{type:'bar',height:260,toolbar:{show:false},fontFamily:'inherit'},
            colors:[ASSU.azulElec], plotOptions:{bar:{borderRadius:4,columnWidth:'55%'}},
            dataLabels:{enabled:false},
            xaxis:{categories:data.labels,labels:{style:{fontSize:'11px'}}},
            tooltip:{y:{formatter:(v)=>Math.round(v)+' unidades'}},
            grid:{borderColor:'#f0f0f0',strokeDashArray:4},
        });
        chartCompCantidad.render();
    }

    function renderCompMonto(data) {
        const el = document.querySelector('#grafico-comp-monto'); if(!el) return;
        chartCompMonto = destruir(chartCompMonto);
        chartCompMonto = new ApexCharts(el, {
            series:[{name:'Monto',data:data.monto}],
            chart:{type:'area',height:260,toolbar:{show:false},fontFamily:'inherit'},
            colors:[ASSU.lima],
            fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.5,opacityTo:0.05,stops:[0,100]}},
            stroke:{curve:'smooth',width:3}, dataLabels:{enabled:false},
            xaxis:{categories:data.labels,labels:{style:{fontSize:'11px'}}},
            tooltip:{y:{formatter:(v)=>'S/ '+parseFloat(v).toLocaleString('es-PE',{minimumFractionDigits:2})}},
            grid:{borderColor:'#f0f0f0',strokeDashArray:4},
        });
        chartCompMonto.render();
    }
</script>
@endscript
