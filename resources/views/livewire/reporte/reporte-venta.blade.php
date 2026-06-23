<div>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header mb-3">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Reporte de Ventas</h5>
                <small class="text-muted">
                    Consulta y filtra las ventas y cobros registrados dentro de un rango de fechas.
                </small>
            </div>

            <div class="row align-items-end g-2">

                {{-- ── Empresa (SuperAdmin y Admin) ──────────────── --}}
                @if($esSuperAdmin || $esAdmin)
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">
                            <i class="fa-solid fa-building text-muted me-1"></i>Empresa:
                        </label>
                        <select wire:model.live="empresaSeleccionada" class="form-select ">
                            <option value="0">Seleccionar Empresa</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- ── Sucursal (SuperAdmin con empresa elegida, o Admin) ── --}}
                @if(count($sucursalesDisponibles) > 0 && ($empresaSeleccionada > 0 || $esAdmin))
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">
                            <i class="fa-solid fa-code-branch text-muted me-1"></i>Sucursal:
                        </label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select ">
                            <option value="0">Todas las sucursales</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- ── Tipo de reporte ────────────────────────────── --}}
                <div class="col-lg-2 col-md-3 col-sm-12">
                    <label class="form-label mb-1">
                        <i class="fa-solid fa-chart-bar text-muted me-1"></i>Tipo de reporte:
                    </label>
                    <select wire:model.live="tipoReporte" class="form-select">
                        <option value="ventas">Ventas</option>
                        <option value="ventas_detallado">Ventas Detallado</option>
                        <option value="resumen_ventas">Resumen Ventas</option>
                        <option value="para_estudio">Para Estudio</option>
                    </select>
                </div>

                {{-- ── Desde ──────────────────────────────────────── --}}
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label" for="desde">Desde:</label>
                    <input type="date"
                           class="form-control @error('desde') is-invalid @enderror"
                           id="desde"
                           wire:model="desde">
                    @error('desde') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                {{-- ── Hasta ──────────────────────────────────────── --}}
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label" for="hasta">Hasta:</label>
                    <input type="date"
                           class="form-control @error('hasta') is-invalid @enderror"
                           id="hasta"
                           min="{{ $desde }}"
                           wire:model="hasta">
                    @error('hasta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                {{-- ── Buscar + Exportar ───────────────────────────── --}}
                <div class="col-lg-auto col-md-auto col-sm-12 d-flex gap-1 align-items-end mt-4 mt-lg-0">
                    @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                        <button class="btn btn-success" disabled title="Selecciona primero una empresa">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    @else
                        <button class="btn btn-success"
                                wire:click="listarRegistrosPagos()"
                                wire:loading.attr="disabled"
                                wire:target="listarRegistrosPagos"
                                title="Buscar datos">
                            <span wire:loading wire:target="listarRegistrosPagos">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                            <i class="fa-solid fa-search" wire:loading.remove wire:target="listarRegistrosPagos"></i>
                        </button>
                    @endif

                    @can('reporte_ventas.exportar')
                    <button type="button"
                            class="btn btn-outline-success"
                            wire:click="imprimirExcel()"
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
                    <button type="button"
                            class="btn btn-outline-danger"
                            wire:click="imprimirPdf()"
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

            {{-- Aviso SuperAdmin sin empresa elegida --}}
            @if(($esSuperAdmin || $esAdmin) && !$idEmpresaActiva)
                <div class="alert alert-warning py-2 mt-3 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Selecciona primero una empresa para visualizar el reporte.
                </div>
            @endif

        </div>

        <div class="card-body">

            @if($buscar)
                @php
                    $hayDatos = count($listaVentas) > 0
                             || count($listaNotasVentas) > 0
                             || count($listaNotaCredito) > 0
                             || count($listaNotaDebito) > 0
                             || count($listaPagosCuotas) > 0;
                @endphp

                @if($hayDatos)
                    {{-- ── Resumen compacto ────────────────────────────── --}}
                    <div class="mb-4 px-1" style="font-size:0.8rem; line-height:1.8;">
                        <span class="text-muted">Boletas y Facturas:</span>
                        <span class="fw-semibold text-primary">S/ {{ number_format($ventasBrutas, 2) }}</span>
                        <span class="text-muted mx-1">|</span>
                        <span class="text-muted">Pagos de Cuotas:</span>
                        <span class="fw-semibold text-info">S/ {{ number_format($pagosCuotas, 2) }}</span>
                        <span class="text-muted mx-1">|</span>
                        <span class="text-muted">Notas de Venta:</span>
                        <span class="fw-semibold text-secondary">S/ {{ number_format($notasVentas, 2) }}</span>
                        <span class="text-muted mx-1">|</span>
                        <span class="text-muted">N. Crédito:</span>
                        <span class="fw-semibold text-warning">S/ {{ number_format($notasCredito, 2) }}</span>
                        <span class="text-muted mx-1">|</span>
                        <span class="text-muted">N. Débito:</span>
                        <span class="fw-semibold text-success">S/ {{ number_format($notasDebito, 2) }}</span>
                        <span class="text-muted mx-1">|</span>
                        <span class="text-muted fw-semibold">Ingreso Total:</span>
                        <span class="fw-bold text-dark">S/ {{ number_format($ventasBrutas - $notasCredito + $notasDebito + $pagosCuotas + $notasVentas, 2) }}</span>
                    </div>

                    {{-- ── Gráfico ─────────────────────────────────────── --}}
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div id="grafico"></div>
                        </div>
                    </div>

                    {{-- ── Tabla: Comprobantes de Venta ─────────────────── --}}
                    @if(count($listaVentas) > 0)
                    <div class="row mb-5">
                        <div class="col-12 mb-3">
                            <h5 class="fw-bold text-uppercase">
                                <i class="fa-solid fa-receipt"></i> Comprobantes de Venta Emitidos
                            </h5>
                            <hr class="mt-2">
                        </div>
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Serie</th>
                                        <th>Número</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listaVentas as $indexVent => $list)
                                        <tr>
                                            <td>{{ $indexVent + 1 }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strtotime($list->venta_fecha)) }}</td>
                                            <td>
                                                <a href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}" target="_blank">
                                                    {{ $list->venta_tipo == '01' ? 'FACTURA' : 'BOLETA' }}
                                                </a>
                                            </td>
                                            <td>{{ $list->venta_serie }}</td>
                                            <td>{{ $list->venta_correlativo }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $list->cliente_razonsocial }}</div>
                                                <small class="text-muted">
                                                    {{ $list->id_tipo_documento == 4 ? 'RUC' : 'DNI' }}: {{ $list->cliente_numero }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->simbolo }}{{ number_format($list->venta_total, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Tabla: Notas de Ventas ───────────────────────── --}}
                    @if(count($listaNotasVentas) > 0)
                    <div class="row mb-5">
                        <div class="col-12 mb-3">
                            <h5 class="fw-bold text-uppercase">
                                <i class="fa-solid fa-list-check"></i> Notas de Ventas
                            </h5>
                            <hr class="mt-2">
                        </div>
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Serie y Número</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listaNotasVentas as $indexVent => $list)
                                        <tr>
                                            <td>{{ $indexVent + 1 }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strtotime($list->venta_fecha)) }}</td>
                                            <td>
                                                <a href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}" target="_blank">
                                                    {{ $list->venta_serie }}-{{ $list->venta_correlativo }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $list->cliente_razonsocial }}</div>
                                                <small class="text-muted">
                                                    {{ $list->id_tipo_documento == 4 ? 'RUC' : 'DNI' }}: {{ $list->cliente_numero }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->simbolo }}{{ number_format($list->venta_total, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Tabla: Notas de Crédito ──────────────────────── --}}
                    @if(count($listaNotaCredito) > 0)
                    <div class="row mb-5">
                        <div class="col-12 mb-3">
                            <h5 class="fw-bold text-uppercase">
                                <i class="fa-solid fa-file-invoice"></i> Notas de Crédito
                            </h5>
                            <hr class="mt-2">
                        </div>
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Serie y Correlativo</th>
                                        <th>Doc Ref</th>
                                        <th>Motivo</th>
                                        <th>Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listaNotaCredito as $indexVentC => $list)
                                        @php
                                            $motivo = \App\Models\Tipo_ncredito::listar_tipo_notaC_x_codigo($list->venta_codigo_motivo_nota);
                                        @endphp
                                        <tr>
                                            <td>{{ $indexVentC + 1 }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strtotime($list->venta_fecha)) }}</td>
                                            <td>
                                                <a href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}" target="_blank">
                                                    {{ $list->venta_serie }}-{{ $list->venta_correlativo }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->serie_modificar }}-{{ $list->correlativo_modificar }}
                                                </div>
                                            </td>
                                            <td>{{ $motivo ? $motivo->tipo_nota_descripcion : '' }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->simbolo }}{{ number_format($list->venta_total, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Tabla: Notas de Débito ───────────────────────── --}}
                    @if(count($listaNotaDebito) > 0)
                    <div class="row mb-5">
                        <div class="col-12 mb-3">
                            <h5 class="fw-bold text-uppercase">
                                <i class="fa-solid fa-arrow-up"></i> Notas de Débito
                            </h5>
                            <hr class="mt-2">
                        </div>
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Serie y Correlativo</th>
                                        <th>Doc Ref</th>
                                        <th>Motivo</th>
                                        <th>Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listaNotaDebito as $indexVentD => $list)
                                        @php
                                            $motivo = \App\Models\Tipo_ndebito::listar_tipo_notaD_x_codigo($list->venta_codigo_motivo_nota);
                                        @endphp
                                        <tr>
                                            <td>{{ $indexVentD + 1 }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strtotime($list->venta_fecha)) }}</td>
                                            <td>
                                                <a href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}" target="_blank">
                                                    {{ $list->venta_serie }}-{{ $list->venta_correlativo }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->serie_modificar }}-{{ $list->correlativo_modificar }}
                                                </div>
                                            </td>
                                            <td>{{ $motivo ? $motivo->tipo_nota_descripcion : '' }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->simbolo }}{{ number_format($list->venta_total, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Tabla: Pagos de Cuotas ───────────────────────── --}}
                    @if(count($listaPagosCuotas) > 0)
                    <div class="row mb-5">
                        <div class="col-12 mb-3">
                            <h5 class="fw-bold text-uppercase">
                                <i class="fa-solid fa-money-bill-wave"></i> Pagos de Cuotas
                            </h5>
                            <hr class="mt-2">
                        </div>
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr class="encabezado_tabla_color">
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Tipo de Pago</th>
                                        <th>Comprobante Vinculado</th>
                                        <th>N° Cuota</th>
                                        <th>Monto</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listaPagosCuotas as $pagoCuotaIndex => $list)
                                        @php
                                            $nroCuota = str_pad((string) $list->venta_cuota_numero, 3, '0', STR_PAD_LEFT);
                                        @endphp
                                        <tr>
                                            <td>{{ $pagoCuotaIndex + 1 }}</td>
                                            <td>{{ date('d/m/Y', strtotime($list->pagos_cuota_fecha)) }}</td>
                                            <td>{{ $list->tipo_pago_nombre }}</td>
                                            <td>{{ $list->venta_serie }}-{{ $list->venta_correlativo }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $nroCuota }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $list->simbolo }}{{ number_format($list->pagos_cuota_monto, 2) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
                        <p class="mb-0">No se encontraron registros para el período seleccionado.</p>
                    </div>
                @endif
            @endif

        </div>
    </div>

    <div wire:loading wire:target="listarRegistrosPagos">
        <x-loader />
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endassets

@script
<script>
    $wire.on('abrirEnlaces', (event) => {
        window.open(event.url, '_blank');
    });

    let chart = null;
    $wire.on('grafico', (event) => {
        setTimeout(() => {
            const element = document.querySelector("#grafico");
            if (!element) return;

            if (!chart) {
                chart = new ApexCharts(element, {
                    series: [{ name: "Ingresos", data: event.totales }],
                    chart: { height: 350, type: 'line', zoom: { enabled: false } },
                    title: { text: 'Ingresos generados por día', align: 'left' },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'straight' },
                    grid: { row: { colors: ['#f3f3f3', 'transparent'], opacity: 0.5 } },
                    xaxis: { categories: event.labels }
                });
                chart.render();
            } else {
                chart.updateOptions({ xaxis: { categories: event.labels } });
                chart.updateSeries([{ name: "Ingresos", data: event.totales }]);
            }
        }, 100);
    });
</script>
@endscript
