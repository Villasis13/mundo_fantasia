<div>
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Control de Pagos de Cuotas</h5>
                <small class="text-muted">
                    Consulta y filtra el estado de cuotas: vencidas, por vencer y pagadas.
                </small>
            </div>

            <div class="row align-items-end g-2">

                {{-- ── Empresa (solo SuperAdmin) ─────────────────── --}}
                @if($esSuperAdmin)
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label">Empresa:</label>
                        <select wire:model.live="empresaSeleccionada" class="form-select">
                            <option value="0">Todas las empresas</option>
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
                                <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
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

                {{-- Cliente buscador estilo ubigeo --}}
                @if($idEmpresaActiva)
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <label class="form-label">Cliente:</label>
                        @if($clienteSeleccionado)
                            <div class="d-flex align-items-center gap-2 p-2 rounded border bg-light">
                                <i class="fa-solid fa-user text-primary"></i>
                                <span class="small fw-semibold flex-grow-1">{{ $clienteSeleccionado['label'] }}</span>
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
                                        @forelse($clientes as $cli)
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


                {{-- ── Estado ─────────────────────────────────────── --}}
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label">Estado:</label>
                    <select wire:model="estado" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="pagadas">Pagadas</option>
                        <option value="pendientes">Pendientes</option>
                        <option value="vencidas">Vencidas</option>
                        <option value="por_vencer">Por vencer (7 días)</option>
                    </select>
                </div>

                {{-- ── Botones ─────────────────────────────────────── --}}
                <div class="col-lg-12 col-md-12 col-sm-12 d-flex gap-2 justify-content-end align-items-end flex-wrap mt-4">
                    @if($esSuperAdmin && !$idEmpresaActiva)
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
                        @can('reporte_caja.exportar')
                        <button class="btn btn-outline-success" wire:click="imprimirExcel"
                                wire:loading.attr="disabled" wire:target="imprimirExcel">
                            <span wire:loading wire:target="imprimirExcel">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                            <i class="fas fa-file-excel me-1" wire:loading.remove wire:target="imprimirExcel"></i>
                            Excel
                        </button>
                        <button class="btn btn-outline-danger" wire:click="imprimirPdf"
                                wire:loading.attr="disabled" wire:target="imprimirPdf">
                            <span wire:loading wire:target="imprimirPdf">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                            <i class="fas fa-file-pdf me-1" wire:loading.remove wire:target="imprimirPdf"></i>
                            PDF
                        </button>
                        @endcan
                    @endif
                </div>

            </div>

            {{-- Aviso SuperAdmin sin empresa --}}
            @if($esSuperAdmin && !$idEmpresaActiva)
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

            @if($buscar && $resumen)

                {{-- ── Tarjetas de resumen ─────────────────────────── --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#0b1892,#2257f1);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Total Pendiente</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($resumen->total_pendiente, 2) }}</h4>
                                <small class="opacity-75">{{ $resumen->cant_pendientes }} cuota(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#b30000,#e53935);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Total Vencido</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($resumen->total_vencido, 2) }}</h4>
                                <small class="opacity-75">{{ $resumen->cant_vencidas }} cuota(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#5a9900,#aadd00);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Total Cobrado</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">S/ {{ number_format($resumen->total_pagado, 2) }}</h4>
                                <small class="opacity-75">{{ $resumen->cant_pagadas }} cuota(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#3a3a5c,#6c6c9a);">
                            <div class="card-body text-white">
                                <small class="opacity-75 text-uppercase fw-semibold d-block" style="font-size:10px;">Saldo por Cobrar</small>
                                <h4 class="fw-bold mb-0 mt-1 text-white">
                                    S/ {{ number_format(max(0, $resumen->total_pendiente - $resumen->total_pagado), 2) }}
                                </h4>
                                <small class="opacity-75">Neto pendiente</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Control de paginación ────────────────────────── --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted small mb-0">Mostrar</label>
                        <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label class="text-muted small mb-0">registros</label>
                    </div>
                </div>

                {{-- ── Tabla ────────────────────────────────────────── --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th style="cursor:pointer;" wire:click="ordenar('cliente_nombre')">
                                Cliente
                                @if($ordenColumna === 'cliente_nombre')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                @endif
                            </th>
                            <th>Comprobante</th>
                            <th style="cursor:pointer;" wire:click="ordenar('venta_cuota_numero')">
                                N° Cuota
                                @if($ordenColumna === 'venta_cuota_numero')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                @endif
                            </th>
                            <th style="cursor:pointer;" wire:click="ordenar('venta_cuota_importe')">
                                Importe
                                @if($ordenColumna === 'venta_cuota_importe')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                @endif
                            </th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th style="cursor:pointer;" wire:click="ordenar('venta_cuota_fecha')">
                                Vencimiento
                                @if($ordenColumna === 'venta_cuota_fecha')
                                    <i class="fa-solid fa-sort-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @else
                                    <i class="fa-solid fa-sort ms-1 opacity-25"></i>
                                @endif
                            </th>
                            <th>Último Pago</th>
                            <th>Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($cuotas->count())
                            @foreach($cuotas as $index => $c)
                                @php
                                    $hoy         = \Carbon\Carbon::today();
                                    $vencimiento = \Carbon\Carbon::parse($c->venta_cuota_fecha);
                                    $diasDiff    = $hoy->diffInDays($vencimiento, false);
                                    $saldo       = max(0, $c->venta_cuota_importe - $c->total_pagado);
                                    $cliente     = $c->id_tipo_documento == 4 ? $c->cliente_razonsocial : $c->cliente_nombre;

                                    if ($c->venta_cuota_pago == 1) {
                                        $estadoBadge = '<span class="badge bg-success">Pagada</span>';
                                        $rowClass    = '';
                                    } elseif ($diasDiff < 0) {
                                        $estadoBadge = '<span class="badge bg-danger">Vencida hace ' . abs((int) $diasDiff) . 'd</span>';
                                        $rowClass    = 'table-danger';
                                    } elseif ($diasDiff <= 7) {
                                        $estadoBadge = '<span class="badge bg-warning text-dark">Vence en ' . (int) $diasDiff . 'd</span>';
                                        $rowClass    = 'table-warning';
                                    } else {
                                        $estadoBadge = '<span class="badge bg-secondary">Pendiente</span>';
                                        $rowClass    = '';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="text-center text-muted">{{ $cuotas->firstItem() + $index }}</td>
                                    <td>
                                        <div class="fw-semibold small">{{ Str::limit($cliente, 25) }}</div>
                                        <small class="text-muted">{{ $c->cliente_numero }}</small>
                                    </td>
                                    <td class="text-center small">
                                        <span class="badge bg-secondary">
                                            {{ match($c->venta_tipo) { '01' => 'FAC', '03' => 'BOL', default => $c->venta_tipo } }}
                                        </span>
                                        {{ $c->venta_serie }}-{{ $c->venta_correlativo }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border fw-bold">
                                            {{ str_pad($c->venta_cuota_numero, 3, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">S/ {{ number_format($c->venta_cuota_importe, 2) }}</td>
                                    <td class="text-end text-success fw-semibold">S/ {{ number_format($c->total_pagado, 2) }}</td>
                                    <td class="text-end fw-bold {{ $saldo > 0 ? 'text-danger' : 'text-success' }}">
                                        S/ {{ number_format($saldo, 2) }}
                                    </td>
                                    <td class="text-center small">{{ $vencimiento->format('d/m/Y') }}</td>
                                    <td class="text-center small">
                                        @if($c->ultimo_pago)
                                            {{ \Carbon\Carbon::parse($c->ultimo_pago)->format('d/m/Y') }}
                                            @if($c->tipo_pago_nombre)
                                                <small class="text-muted d-block">{{ $c->tipo_pago_nombre }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{!! $estadoBadge !!}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron cuotas con los filtros aplicados.
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                @if($cuotas->count())
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                        <small class="text-muted">
                            Mostrando {{ $cuotas->firstItem() }} - {{ $cuotas->lastItem() }}
                            de {{ $cuotas->total() }} registros
                        </small>
                        {{ $cuotas->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif

            @elseif(!$buscar)
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-25"></i>
                    @if($esSuperAdmin && !$idEmpresaActiva)
                        Selecciona una empresa para comenzar.
                    @else
                        Selecciona un rango de fechas y presiona <strong>Buscar</strong>.
                    @endif
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="listarRegistros">
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
