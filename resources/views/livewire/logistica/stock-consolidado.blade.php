<div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-warehouse me-2 text-primary"></i>
                        Stock Consolidado del Grupo
                    </h5>
                    <small class="text-muted">Existencias de productos en todas las empresas y sucursales.</small>
                </div>
                {{--@can('stock_consolidado.exportar')
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Imprimir
                </button>
                @endcan--}}
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3">

                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                @if($esSuperAdmin)
                <div class="col-auto">
                    <select wire:model.live="empresaSeleccionada"
                            class="form-select form-select-sm" style="min-width:180px;">
                        <option value="0">Todas las empresas</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($sucursalesDisponibles->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todas las sucursales</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <select wire:model.live="filtroFamilia"
                            class="form-select form-select-sm" style="min-width:150px;">
                        <option value="0">Todas las familias</option>
                        @foreach($familias as $fam)
                            <option value="{{ $fam->id_fa }}">{{ $fam->fa_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <select wire:model.live="filtroEstado"
                            class="form-select form-select-sm" style="min-width:150px;">
                        <option value="todos">Todos los estados</option>
                        <option value="sin_stock">Sin stock</option>
                        <option value="bajo_minimo">Bajo mínimo</option>
                    </select>
                </div>

                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control"
                               wire:model.live.debounce.300ms="buscar"
                               placeholder="Nombre, código..." style="min-width:180px;">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            {{-- Leyenda --}}
            <div class="d-flex gap-3 px-3 py-2 border-bottom bg-light small">
                <span><span class="badge bg-danger me-1">●</span> Sin stock</span>
                <span><span class="badge bg-warning text-dark me-1">●</span> Bajo mínimo</span>
                <span><span class="badge bg-success me-1">●</span> Normal</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="tablaStockConsolidado">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3">#</th>
                            <th wire:click="ordenar('p.pro_nombre')" style="cursor:pointer;" class="text-nowrap">
                                Producto
                                <i class="fa-solid fa-sort{{ $ordenColumna==='p.pro_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='p.pro_nombre' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            <th wire:click="ordenar('p.pro_codigo')" style="cursor:pointer;" class="text-nowrap">
                                Código
                                <i class="fa-solid fa-sort{{ $ordenColumna==='p.pro_codigo' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='p.pro_codigo' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            @if($esSuperAdmin)
                            <th>Empresa</th>
                            @endif
                            <th wire:click="ordenar('s.sucursal_nombre')" style="cursor:pointer;" class="text-nowrap">
                                Sucursal
                                <i class="fa-solid fa-sort{{ $ordenColumna==='s.sucursal_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='s.sucursal_nombre' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            <th>Familia</th>
                            <th class="text-end text-nowrap" wire:click="ordenar('ps.ps_stock')" style="cursor:pointer;">
                                Stock
                                <i class="fa-solid fa-sort{{ $ordenColumna==='ps.ps_stock' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='ps.ps_stock' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            <th class="text-end text-nowrap" wire:click="ordenar('ps.ps_stock_minimo')" style="cursor:pointer;">
                                Mínimo
                                <i class="fa-solid fa-sort{{ $ordenColumna==='ps.ps_stock_minimo' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='ps.ps_stock_minimo' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            <th class="text-end">Precio Vta.</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockPaginado as $index => $row)
                            @php
                                $sinStock = $row->ps_stock <= 0;
                                $bajoMin  = !$sinStock && $row->ps_stock_minimo > 0
                                            && $row->ps_stock <= $row->ps_stock_minimo;
                                $rowClass = $sinStock ? 'table-danger' : ($bajoMin ? 'table-warning' : '');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="ps-3 text-muted fw-semibold">{{ $stockPaginado->firstItem() + $index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $row->pro_nombre }}</span>
                                    @if($row->pro_codigo_interno)
                                        <br><small class="text-muted">{{ $row->pro_codigo_interno }}</small>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $row->pro_codigo }}</td>
                                @if($esSuperAdmin)
                                <td>
                                    <span class="badge bg-primary bg-opacity-75 fw-normal">
                                        {{ $row->empresa_nombrecomercial }}
                                    </span>
                                </td>
                                @endif
                                <td>
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                        {{ $row->sucursal_nombre }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $row->fa_nombre ?? '—' }}</td>
                                <td class="text-end fw-bold {{ $sinStock ? 'text-danger' : ($bajoMin ? 'text-warning' : 'text-success') }}">
                                    {{ number_format($row->ps_stock, 2) }}
                                </td>
                                <td class="text-end text-muted">{{ number_format($row->ps_stock_minimo, 2) }}</td>
                                <td class="text-end">S/ {{ number_format($row->ps_precio_uni, 2) }}</td>
                                <td class="text-center">
                                    @if($sinStock)
                                        <span class="badge bg-danger">Sin stock</span>
                                    @elseif($bajoMin)
                                        <span class="badge bg-warning text-dark">Bajo mínimo</span>
                                    @else
                                        <span class="badge bg-success">Normal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $esSuperAdmin ? 10 : 9 }}" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron registros con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($stockPaginado->hasPages())
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $stockPaginado->firstItem() }}–{{ $stockPaginado->lastItem() }}
                        de {{ $stockPaginado->total() }} registros
                    </small>
                    {{ $stockPaginado->links(data: ['scrollTo' => false]) }}
                </div>
            </div>
        @endif
    </div>

    <style>
        @media print {
            .card-header .row, .pagination, nav, button { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

</div>
