<div>
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Reporte de Ventas por Cliente</h5>
                <small class="text-muted">
                    Consulta el resumen, detalle de ventas, productos comprados y deuda por cliente.
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

                {{-- ── Sede ───────────────────────────────────────── --}}
                @if(count($sucursalesDisponibles) > 0 && ($empresaSeleccionada > 0 || $esAdmin))
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Sede:</label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select">
                            <option value="0">Todas las sedes</option>
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

                {{-- ── Cliente buscador estilo ubigeo ─────────────── --}}
                @if($idEmpresaActiva)
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <label class="form-label">Cliente:</label>
                        @if($clienteSeleccionadoBuscador)
                            <div class="d-flex align-items-center gap-2 p-2 rounded border bg-light">
                                <i class="fa-solid fa-user text-primary"></i>
                                <span class="small fw-semibold flex-grow-1">{{ $clienteSeleccionadoBuscador['label'] }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="limpiarCliente">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        @else
                            <div class="position-relative">
                                <input type="text"
                                       wire:model.live.debounce.300ms="buscarCliente"
                                       wire:focus="$set('mostrarListaCliente', true)"
                                       class="form-control"
                                       placeholder="Haz clic o escribe para buscar...">
                                @if($mostrarListaCliente)
                                    <div class="position-absolute w-100 border rounded shadow-sm bg-white"
                                         style="z-index:1055; max-height:210px; overflow-y:auto; top:100%;">
                                        @forelse($clientesBuscador as $cli)
                                            @php
                                                $nombre = $cli->id_tipo_documento == 4
                                                    ? $cli->cliente_razonsocial
                                                    : $cli->cliente_nombre;
                                            @endphp
                                            <div class="px-3 py-2 small"
                                                 style="cursor:pointer;"
                                                 wire:click="seleccionarCliente({{ $cli->id_clientes }}, '{{ addslashes($nombre) }} - {{ $cli->cliente_numero }}')"
                                                 onmouseover="this.style.background='#eef1ff'"
                                                 onmouseout="this.style.background='white'">
                                                <i class="fa-solid fa-user text-muted me-1"></i>
                                                <strong>{{ $nombre }}</strong>
                                                <span class="text-muted"> &mdash; {{ $cli->cliente_numero }}</span>
                                            </div>
                                        @empty
                                            <div class="px-3 py-2 small text-muted">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                Sin resultados
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

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
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Ticket Promedio</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->ticket_promedio, 2) }}</h4>
                                <small class="opacity-75">Por comprobante</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#3a3a5c,#6c6c9a);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Clientes</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">{{ $resumenClientes->count() }}</h4>
                                <small class="opacity-75">Con compras en el período</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#b30000,#e53935);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Deuda Total Pendiente</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($totalesResumen->deuda_total, 2) }}</h4>
                                <small class="opacity-75">Cuotas sin pagar</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Tabla resumen por cliente ────────────────────── --}}
                <h6 class="fw-bold mb-3" style="color:#0b1892;">
                    <i class="fa-solid fa-users me-2"></i> Resumen por Cliente
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th class="text-start">Cliente</th>
                            <th>N° Documento</th>
                            <th>Total Ventas</th>
                            <th>Facturas</th>
                            <th>Boletas</th>
                            <th>N. Venta</th>
                            <th>Comp.</th>
                            <th>Ticket Prom.</th>
                            <th>Deuda</th>
                            <th>Detalle</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resumenClientes as $i => $c)
                            @php
                                $nombreCli      = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;
                                $esSeleccionado = $clienteSeleccionado == $c->id_clientes;
                            @endphp
                            <tr class="{{ $esSeleccionado ? 'table-primary' : '' }}">
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold small">{{ $nombreCli }}</div>
                                    <small class="text-muted">{{ $c->id_tipo_documento == 4 ? 'RUC' : 'DNI' }}</small>
                                </td>
                                <td class="text-center small fw-semibold">{{ $c->cliente_numero }}</td>
                                <td class="text-end fw-bold" style="color:#0b1892;">S/ {{ number_format($c->total_ventas, 2) }}</td>
                                <td class="text-end small" style="color:#5a9900;">S/ {{ number_format($c->total_facturas, 2) }}</td>
                                <td class="text-end small" style="color:#b3009e;">S/ {{ number_format($c->total_boletas, 2) }}</td>
                                <td class="text-end small text-secondary">S/ {{ number_format($c->total_nv, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $c->cantidad }}</span>
                                </td>
                                <td class="text-end small">S/ {{ number_format($c->ticket_promedio, 2) }}</td>
                                <td class="text-end">
                                    @if($c->deuda_pendiente > 0)
                                        <span class="badge bg-danger">S/ {{ number_format($c->deuda_pendiente, 2) }}</span>
                                    @else
                                        <span class="badge bg-success">Sin deuda</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($esSeleccionado)
                                        <button class="btn btn-sm btn-secondary" wire:click="cerrarDetalle">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-primary"
                                                wire:click="verDetalle({{ $c->id_clientes }}, '{{ addslashes($nombreCli) }}')"
                                                title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-users-slash fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron clientes con ventas en el período.
                                </td>
                            </tr>
                        @endforelse

                        @if($resumenClientes->count())
                            <tr style="background:#eef1ff;">
                                <td colspan="3" class="fw-bold" style="color:#0b1892;">TOTAL GENERAL</td>
                                <td class="text-end fw-bold" style="color:#0b1892;">S/ {{ number_format($totalesResumen->total_ventas, 2) }}</td>
                                <td colspan="4" class="text-center fw-bold text-muted">{{ $totalesResumen->cantidad }} comp.</td>
                                <td class="text-end fw-bold">S/ {{ number_format($totalesResumen->ticket_promedio, 2) }}</td>
                                <td class="text-end fw-bold text-danger">S/ {{ number_format($totalesResumen->deuda_total, 2) }}</td>
                                <td></td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                {{-- ── Detalle del cliente seleccionado ───────────── --}}
                @if($clienteSeleccionado)
                    <div class="card border-0 shadow-sm mt-2">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h6 class="fw-bold mb-0" style="color:#0b1892;">
                                    <i class="fa-solid fa-user me-2"></i>
                                    {{ $nombreClienteDetalle }}
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

                            {{-- Tabs --}}
                            <ul class="nav nav-tabs mt-3 border-0">
                                <li class="nav-item">
                                    <button wire:click="cambiarTab('ventas')"
                                            class="nav-link {{ $tabDetalle === 'ventas' ? 'active fw-semibold' : '' }}">
                                        <i class="fa-solid fa-receipt me-1"></i> Ventas
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button wire:click="cambiarTab('productos')"
                                            class="nav-link {{ $tabDetalle === 'productos' ? 'active fw-semibold' : '' }}">
                                        <i class="fa-solid fa-boxes-stacked me-1"></i> Productos
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button wire:click="cambiarTab('deuda')"
                                            class="nav-link {{ $tabDetalle === 'deuda' ? 'active fw-semibold' : '' }}">
                                        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Deuda
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-0">

                            {{-- Tab: Ventas --}}
                            @if($tabDetalle === 'ventas')
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
                                            <th>Vendedor</th>
                                            <th>F. Pago</th>
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
                                        @forelse($detalleVentas as $index => $v)
                                            <tr>
                                                <td class="text-center text-muted">{{ $detalleVentas->firstItem() + $index }}</td>
                                                <td class="small">
                                                    <span class="d-block">{{ date('d/m/Y', strtotime($v->venta_fecha)) }}</span>
                                                    <span class="text-muted">{{ date('H:i', strtotime($v->venta_fecha)) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary small">
                                                        {{ match($v->venta_tipo) { '01'=>'FAC','03'=>'BOL','20'=>'NV', default=>$v->venta_tipo } }}
                                                    </span>
                                                </td>
                                                <td class="text-center small fw-semibold">{{ $v->venta_serie }}-{{ $v->venta_correlativo }}</td>
                                                <td class="small">{{ $v->nombre_users }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $v->id_formas_pago == 1 ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $v->id_formas_pago == 1 ? 'Contado' : 'Crédito' }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold" style="color:#0b1892;">
                                                    {{ $v->simbolo }}{{ number_format($v->venta_total, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted py-4">Sin ventas en el período.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($detalleVentas->count())
                                    <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <small class="text-muted">Mostrando {{ $detalleVentas->firstItem() }} - {{ $detalleVentas->lastItem() }} de {{ $detalleVentas->total() }}</small>
                                        {{ $detalleVentas->links(data: ['scrollTo' => false]) }}
                                    </div>
                                @endif
                            @endif

                            {{-- Tab: Productos --}}
                            @if($tabDetalle === 'productos')
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr class="encabezado_tabla_color text-center">
                                            <th>#</th>
                                            <th class="text-start">Producto</th>
                                            <th>Código</th>
                                            <th>Categoría</th>
                                            <th>Cantidad</th>
                                            <th>Precio Prom.</th>
                                            <th>Total</th>
                                            <th>En # Comp.</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($detalleProductos as $index => $p)
                                            <tr>
                                                <td class="text-center text-muted">{{ $detalleProductos->firstItem() + $index }}</td>
                                                <td class="fw-semibold small">{{ $p->pro_nombre }}</td>
                                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->pro_codigo }}</span></td>
                                                <td class="text-center small text-muted">{{ $p->ca_nombre }}</td>
                                                <td class="text-center fw-bold">{{ number_format($p->total_cantidad, 2) }}</td>
                                                <td class="text-end small">S/ {{ number_format($p->precio_promedio, 2) }}</td>
                                                <td class="text-end fw-bold" style="color:#0b1892;">S/ {{ number_format($p->total_importe, 2) }}</td>
                                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $p->en_comprobantes }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-4">Sin productos en el período.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($detalleProductos->count())
                                    <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <small class="text-muted">Mostrando {{ $detalleProductos->firstItem() }} - {{ $detalleProductos->lastItem() }} de {{ $detalleProductos->total() }}</small>
                                        {{ $detalleProductos->links(data: ['scrollTo' => false]) }}
                                    </div>
                                @endif
                            @endif

                            {{-- Tab: Deuda --}}
                            @if($tabDetalle === 'deuda')
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr class="encabezado_tabla_color text-center">
                                            <th>#</th>
                                            <th>Comprobante</th>
                                            <th>N° Cuota</th>
                                            <th>Vencimiento</th>
                                            <th>Importe</th>
                                            <th>Pagado</th>
                                            <th>Saldo</th>
                                            <th>Estado</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($deudaCliente as $index => $cu)
                                            @php
                                                $hoy2     = \Carbon\Carbon::today();
                                                $venc     = \Carbon\Carbon::parse($cu->venta_cuota_fecha);
                                                $saldo2   = max(0, $cu->venta_cuota_importe - $cu->total_pagado);
                                                $diasDiff = $hoy2->diffInDays($venc, false);

                                                if ($cu->venta_cuota_pago == 1) {
                                                    $badge  = '<span class="badge bg-success">Pagada</span>';
                                                    $rowCls = '';
                                                } elseif ($diasDiff < 0) {
                                                    $badge  = '<span class="badge bg-danger">Vencida</span>';
                                                    $rowCls = 'table-danger';
                                                } elseif ($diasDiff <= 7) {
                                                    $badge  = '<span class="badge bg-warning text-dark">Por vencer</span>';
                                                    $rowCls = 'table-warning';
                                                } else {
                                                    $badge  = '<span class="badge bg-secondary">Pendiente</span>';
                                                    $rowCls = '';
                                                }
                                            @endphp
                                            <tr class="{{ $rowCls }}">
                                                <td class="text-center text-muted">{{ $deudaCliente->firstItem() + $index }}</td>
                                                <td class="text-center small">
                                                    <span class="badge bg-secondary">{{ match($cu->venta_tipo) { '01'=>'FAC','03'=>'BOL', default=>$cu->venta_tipo } }}</span>
                                                    {{ $cu->venta_serie }}-{{ $cu->venta_correlativo }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border fw-bold">{{ str_pad($cu->venta_cuota_numero, 3, '0', STR_PAD_LEFT) }}</span>
                                                </td>
                                                <td class="text-center small">{{ $venc->format('d/m/Y') }}</td>
                                                <td class="text-end fw-semibold">S/ {{ number_format($cu->venta_cuota_importe, 2) }}</td>
                                                <td class="text-end text-success">S/ {{ number_format($cu->total_pagado, 2) }}</td>
                                                <td class="text-end fw-bold {{ $saldo2 > 0 ? 'text-danger' : 'text-success' }}">S/ {{ number_format($saldo2, 2) }}</td>
                                                <td class="text-center">{!! $badge !!}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                                    Este cliente no tiene deuda pendiente.
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($deudaCliente->count())
                                    <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <small class="text-muted">Mostrando {{ $deudaCliente->firstItem() }} - {{ $deudaCliente->lastItem() }} de {{ $deudaCliente->total() }}</small>
                                        {{ $deudaCliente->links(data: ['scrollTo' => false]) }}
                                    </div>
                                @endif
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

    <div wire:loading wire:target="listarRegistros,verDetalle,cerrarDetalle,cambiarTab">
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
