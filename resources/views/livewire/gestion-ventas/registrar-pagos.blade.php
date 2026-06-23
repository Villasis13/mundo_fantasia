<div>
    {{-- ── Modal Registro de Pagos ──────────────────────────────── --}}
    <div class="modal fade" id="modalRegistrarPagos" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Registros de Pagos de las cuotas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">

                        <div class="col-12 mb-3">
                            <h6 class="fw-bold text-success">
                                <i class="fa-solid fa-money-bill-wave"></i> Registro de Pago
                            </h6>
                            <hr class="mt-2">
                        </div>

                        <div class="col-12">
                            <form wire:submit="guardarPago" method="post">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="row text-center small fw-semibold">
                                            <div class="col-lg-4 col-md-4 col-sm-12 mb-2">
                                                <span class="text-muted">Total de la Cuota</span>
                                                <div class="fs-5 fw-bold text-dark">
                                                    S/ {{ number_format($montoTotalCuota, 2) }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-12 mb-2">
                                                <span class="text-muted">Saldo Pagado</span>
                                                <div class="fs-5 fw-bold text-success">
                                                    S/ {{ number_format($montoPagadoCuota, 2) }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-12 mb-2">
                                                <span class="text-muted">Saldo Restante</span>
                                                <div class="fs-5 fw-bold text-danger">
                                                    S/ {{ number_format($montoRestante, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                        <label class="form-label fw-semibold">Tipo de Pago</label>
                                        <select class="form-select @error('id_tipo_pago') is-invalid @enderror" wire:model="id_tipo_pago">
                                            <option value="">Seleccionar</option>
                                            @foreach($tipoPagos as $tip)
                                                <option value="{{ $tip->id_tipo_pago }}">{{ $tip->tipo_pago_nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('id_tipo_pago') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-lg-2 col-md-6 col-sm-12 mb-3">
                                        <label class="form-label fw-semibold">Monto</label>
                                        <input type="text" class="form-control @error('monto') is-invalid @enderror" wire:model="monto">
                                        @error('monto') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-lg-2 col-md-6 col-sm-12 mb-3">
                                        <label class="form-label fw-semibold">Fecha de Pago</label>
                                        <input type="date" class="form-control @error('fecha') is-invalid @enderror" wire:model="fecha">
                                        @error('fecha') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                                        <label class="form-label fw-semibold">Voucher</label>
                                        <input class="form-control @error('voucher') is-invalid @enderror" type="file" wire:model="voucher">
                                        @error('voucher') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>

                                    @can('registro_pagos.crear')
                                    <div class="col-lg-1 col-md-12 col-sm-12 text-end">
                                        <button class="btn btn-success mt-4" wire:loading.attr="disabled" wire:target="guardarPago">
                                            <span wire:loading wire:target="guardarPago">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>
                                            <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="guardarPago"></i>
                                        </button>
                                    </div>
                                    @endcan

                                    @if (session('success'))
                                    <div class="col-12">
                                        <div class="alert alert-success alert-dismissible mt-2">
                                            {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                    @endif
                                    @if (session('error'))
                                    <div class="col-12">
                                        <div class="alert alert-danger alert-dismissible mt-2">
                                            {{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </form>
                        </div>

                        <div class="col-12 mt-2 mb-3">
                            <h6 class="fw-bold text-primary">
                                <i class="fa-solid fa-list"></i> Historial de Pagos
                            </h6>
                            <hr class="mt-2">
                        </div>

                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="encabezado_tabla_color">
                                            <th>#</th>
                                            <th>Registrado Por</th>
                                            <th>Fecha Pago</th>
                                            <th>Fecha Registro</th>
                                            <th>Tipo de Pago</th>
                                            <th>Monto</th>
                                            <th>Voucher</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pagosRealizadosCuota as $i => $pa)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="fw-semibold">{{ $pa->nombre_users }}</td>
                                            <td>{{ date('d/m/Y', strtotime($pa->pagos_cuota_fecha)) }}</td>
                                            <td>{{ date('d/m/Y H:i', strtotime($pa->created_at)) }}</td>
                                            <td>{{ $pa->tipo_pago_nombre }}</td>
                                            <td class="fw-bold text-success">S/ {{ $pa->pagos_cuota_monto }}</td>
                                            <td>
                                                @if($pa->pagos_cuota_voucher && file_exists($pa->pagos_cuota_voucher))
                                                    <a class="btn btn-sm btn-outline-primary"
                                                       href="{{ asset($pa->pagos_cuota_voucher) }}"
                                                       target="_blank">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Sin voucher</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @can('registro_pagos.eliminar')
                                                <button class="btn btn-sm btn-danger"
                                                        wire:click="eliminarRegistroPago({{ $pa->id_pagos_cuota }})"
                                                        title="Eliminar pago">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                @endcan
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                No hay pagos registrados.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tarjeta principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-2">
                <h5 class="mb-0 fw-bold">Gestión de Pagos por Cobrar</h5>
                <small class="text-muted">
                    Visualiza y filtra el historial de cuotas, pagos realizados y saldos pendientes.
                </small>
            </div>

            {{-- ── Filtros empresa / sucursal ───────────────────── --}}
            @if($esSuperAdmin)
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pt-2 border-top">
                    <span class="text-muted small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar por
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-building text-muted small"></i>
                        <select wire:model.live="empresaSeleccionada"
                                class="form-select form-select-sm" style="min-width:190px">
                            <option value="0">Todas las empresas</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($empresaSeleccionada > 0 && $sucursalesDisponibles->isNotEmpty())
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-code-branch text-muted small"></i>
                        <select wire:model.live="sucursalSeleccionada"
                                class="form-select form-select-sm" style="min-width:190px">
                            <option value="0">Todas las sucursales</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            @elseif($esAdmin && $sucursalesDisponibles->isNotEmpty())
                <div class="d-flex align-items-center gap-2 mb-3 pt-2 border-top">
                    <i class="fa-solid fa-code-branch text-muted small"></i>
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="max-width:280px">
                        <option value="0">Todas las sucursales</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- ── Filtros de búsqueda ──────────────────────────── --}}
            <div class="row align-items-end g-2">
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <label class="form-label mb-1" for="idCliente">Cliente:</label>
                    @if($esSuperAdmin && !$idEmpresaActiva)
                        <select class="form-select form-select-sm" disabled>
                            <option>— Selecciona primero una empresa —</option>
                        </select>
                    @else
                        <select id="idCliente" wire:model="idCliente"
                                class="form-select form-select-sm @error('idCliente') is-invalid @enderror">
                            <option value="">Todos</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id_clientes }}">{{ $cli->cliente_razonsocial }}</option>
                            @endforeach
                        </select>
                        @error('idCliente') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    @endif
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label mb-1" for="desde">Desde:</label>
                    <input type="date" id="desde" wire:model="desde"
                           class="form-control form-control-sm @error('desde') is-invalid @enderror">
                    @error('desde') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label mb-1" for="hasta">Hasta:</label>
                    <input type="date" id="hasta" wire:model="hasta" min="{{ $desde }}"
                           class="form-control form-control-sm @error('hasta') is-invalid @enderror">
                    @error('hasta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label mb-1" for="estadoPagado">Estado:</label>
                    <select id="estadoPagado" wire:model="estadoPagado"
                            class="form-select form-select-sm @error('estadoPagado') is-invalid @enderror">
                        <option value="2">TODOS</option>
                        <option value="0">PENDIENTES</option>
                        <option value="1">PAGADOS</option>
                    </select>
                    @error('estadoPagado') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <button class="btn btn-primary btn-sm w-100" wire:click="listarRegistrosPagos()">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th>#</th>
                            <th>Serie y Correlativo</th>
                            <th>Cliente</th>
                            <th>Registrado Por</th>
                            <th>N° de Cuota</th>
                            <th>Fecha de Pago</th>
                            <th class="text-end">Monto a Pagar</th>
                            <th class="text-end">Monto Pagado</th>
                            <th class="text-end">Saldo Restante</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $i => $list)
                        @php
                            $montoTotal    = (float)$list->venta_cuota_importe;
                            $montoPagado   = (float)$list->monto_pagado;
                            $saldoRestante = $montoTotal - $montoPagado;
                            $nroCuota      = str_pad((string)$list->venta_cuota_numero, 3, '0', STR_PAD_LEFT);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold text-dark small">
                                    {{ $list->venta_tipo === '01' ? 'Factura' : 'Boleta' }}
                                </div>
                                <small class="text-muted">{{ $list->venta_serie }}-{{ $list->venta_correlativo }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">{{ $list->cliente_razonsocial }}</div>
                                <small class="text-muted">
                                    {{ $list->id_tipo_documento == 4 ? 'RUC' : 'DNI' }}: {{ $list->cliente_numero }}
                                </small>
                            </td>
                            <td class="small">{{ $list->nombre_users }}</td>
                            <td class="fw-semibold text-dark">{{ $nroCuota }}</td>
                            <td class="small">{{ date('d/m/Y', strtotime($list->venta_cuota_fecha)) }}</td>
                            <td class="text-end fw-semibold text-dark">
                                {{ $list->simbolo }}{{ number_format($montoTotal, 2) }}
                            </td>
                            <td class="text-end fw-semibold text-success">
                                {{ $list->simbolo }}{{ number_format($montoPagado, 2) }}
                            </td>
                            <td class="text-end fw-semibold {{ $saldoRestante <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $list->simbolo }}{{ number_format($saldoRestante, 2) }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $list->venta_cuota_pago == 1 ? 'success' : 'danger' }}">
                                    {{ $list->venta_cuota_pago == 1 ? 'Pagado' : 'Pendiente' }}
                                </span>
                            </td>
                            <td>
                                <a target="_blank" title="Ver detalle"
                                   class="btn btn-sm btn-info m-1"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-warning m-1"
                                        wire:click="listarPagosRealizadosCuota({{ $list->id_ventas_cuotas }})"
                                        data-bs-toggle="modal" data-bs-target="#modalRegistrarPagos"
                                        title="Registrar pago">
                                    <i class="fa-solid fa-money-bill"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar</strong> para ver las cuotas.
                                @else
                                    No se encontraron cuotas con los filtros aplicados.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($registros instanceof \Illuminate\Pagination\LengthAwarePaginator && $registros->total() > 0)
                {{ $registros->links(data: ['scrollTo' => false]) }}
            @endif
        </div>
    </div>

    <div wire:loading wire:target="listarRegistrosPagos">
        <x-loader />
    </div>
</div>
