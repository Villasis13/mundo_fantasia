<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa fa-calendar-check text-primary me-2"></i> Corte Mensual — Compras vs Ventas
                    </h5>
                    <small class="text-muted">Resumen comparativo mensual de compras y ventas por año.</small>
                </div>
                @if($buscado && $totales)
                <div class="d-flex gap-2">
                    @can('reporte_corte_mensual.exportar')
                    <button wire:click="imprimirPdf" class="btn btn-sm btn-outline-danger">
                        <i class="fa fa-file-pdf me-1"></i> PDF
                    </button>
                    <button wire:click="imprimirExcel" class="btn btn-sm btn-outline-success">
                        <i class="fa fa-file-excel me-1"></i> Excel
                    </button>
                    @endcan
                </div>
                @endif
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3">
                @if($esSuperAdmin || $esAdmin)
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1">Empresa</label>
                    <select wire:model.live="empresaSeleccionada" class="form-select form-select-sm" style="min-width:180px;">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(count($sucursalesDisponibles) > 0)
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1">Sucursal</label>
                    <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todos los establecimientos</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1">Año</label>
                    <select wire:model.live="filtroAnio" class="form-select form-select-sm">
                        @foreach(range(now()->year, now()->year - 4) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button wire:click="generar" class="btn btn-sm btn-primary">
                        <i class="fa fa-chart-bar me-1"></i> Generar
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">

            @if(!$buscado)
            <div class="text-center text-muted py-5">
                <i class="fa fa-calendar fa-3x mb-3 d-block opacity-25"></i>
                Seleccione el año y presione <strong>Generar</strong>.
            </div>

            @elseif($totales)

            {{-- Tarjetas resumen --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-start border-primary border-3 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="small text-muted">Total Compras</div>
                            <div class="fw-bold fs-5 text-primary">S/ {{ number_format($totales->gran_total_compras, 2) }}</div>
                            <div class="small text-muted">{{ number_format($totales->num_ordenes) }} órdenes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-success border-3 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="small text-muted">Total Ventas</div>
                            <div class="fw-bold fs-5 text-success">S/ {{ number_format($totales->total_ventas, 2) }}</div>
                            <div class="small text-muted">{{ number_format($totales->num_ventas) }} ventas</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-3 border-0 shadow-sm {{ $totales->diferencia >= 0 ? 'border-success' : 'border-danger' }}">
                        <div class="card-body py-3">
                            <div class="small text-muted">Diferencia Neta</div>
                            <div class="fw-bold fs-5 {{ $totales->diferencia >= 0 ? 'text-success' : 'text-danger' }}">
                                S/ {{ number_format($totales->diferencia, 2) }}
                            </div>
                            <div class="small text-muted">Ventas − Compras</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-warning border-3 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="small text-muted">Total Flete</div>
                            <div class="fw-bold fs-5 text-warning">S/ {{ number_format($totales->total_flete, 2) }}</div>
                            <div class="small text-muted">en compras del año</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla mensual --}}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th>Mes</th>
                            <th class="text-end">N° Órdenes</th>
                            <th class="text-end">Mercadería</th>
                            <th class="text-end">Flete</th>
                            <th class="text-end">Otros</th>
                            <th class="text-end">Total Compras</th>
                            <th class="text-end">N° Ventas</th>
                            <th class="text-end">Total Ventas</th>
                            <th class="text-end">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($meses as $m)
                        @php $sinData = $m->num_ordenes == 0 && $m->num_ventas == 0; @endphp
                        <tr class="{{ $sinData ? 'text-muted' : '' }}">
                            <td class="fw-semibold">{{ $m->mes_nombre }}</td>
                            <td class="text-end">{{ $m->num_ordenes ?: '—' }}</td>
                            <td class="text-end">{{ $m->total_mercaderia > 0 ? 'S/ '.number_format($m->total_mercaderia,2) : '—' }}</td>
                            <td class="text-end">{{ $m->total_flete > 0 ? 'S/ '.number_format($m->total_flete,2) : '—' }}</td>
                            <td class="text-end">{{ $m->total_gastos > 0 ? 'S/ '.number_format($m->total_gastos,2) : '—' }}</td>
                            <td class="text-end fw-semibold {{ $m->gran_total_compras > 0 ? 'text-primary' : '' }}">
                                {{ $m->gran_total_compras > 0 ? 'S/ '.number_format($m->gran_total_compras,2) : '—' }}
                            </td>
                            <td class="text-end">{{ $m->num_ventas ?: '—' }}</td>
                            <td class="text-end fw-semibold {{ $m->total_ventas > 0 ? 'text-success' : '' }}">
                                {{ $m->total_ventas > 0 ? 'S/ '.number_format($m->total_ventas,2) : '—' }}
                            </td>
                            <td class="text-end fw-bold {{ $m->diferencia > 0 ? 'text-success' : ($m->diferencia < 0 ? 'text-danger' : 'text-muted') }}">
                                @if($m->num_ordenes > 0 || $m->num_ventas > 0)
                                    S/ {{ number_format($m->diferencia, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold" style="background:#f0f4f8;">
                            <td>TOTAL</td>
                            <td class="text-end">{{ number_format($totales->num_ordenes) }}</td>
                            <td class="text-end">S/ {{ number_format($totales->total_mercaderia, 2) }}</td>
                            <td class="text-end">S/ {{ number_format($totales->total_flete, 2) }}</td>
                            <td class="text-end">S/ {{ number_format($totales->total_gastos, 2) }}</td>
                            <td class="text-end text-primary">S/ {{ number_format($totales->gran_total_compras, 2) }}</td>
                            <td class="text-end">{{ number_format($totales->num_ventas) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($totales->total_ventas, 2) }}</td>
                            <td class="text-end {{ $totales->diferencia >= 0 ? 'text-success' : 'text-danger' }}">
                                S/ {{ number_format($totales->diferencia, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div wire:loading.flex wire:target="generar" style="position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(2px);">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <img src="{{ asset('isologo.ico') }}" style="width:70px;height:70px;object-fit:contain;animation:spin-rep 1s linear infinite;filter:drop-shadow(0 6px 18px rgba(0,0,0,.35));" alt="">
            <div style="color:#fff;font-weight:600;font-size:14px;">Cargando...</div>
        </div>
    </div>
    <style>@keyframes spin-rep{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>

    <script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('abrirEnlaces', e => window.open(e.url, '_blank'));
    });
    </script>
</div>
