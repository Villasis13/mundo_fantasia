<div>

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
                        Stock por Sede
                    </h5>
                    <small class="text-muted">Stock por sede / tienda por producto.</small>
                </div>
                @can('stock_establecimiento.exportar')
                <button wire:click="exportarExcel" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading.remove wire:target="exportarExcel">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Excel
                    </span>
                    <span wire:loading wire:target="exportarExcel">
                        <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                    </span>
                </button>
                @endcan
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

                <div class="col-auto">
                    <select wire:model.live="filtroFamilia" class="form-select form-select-sm" style="min-width:150px;">
                        <option value="0">Todas las familias</option>
                        @foreach($familias as $fam)
                            <option value="{{ $fam->id_fa }}">{{ $fam->fa_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                @if($categorias->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroCategoria" class="form-select form-select-sm" style="min-width:150px;">
                        <option value="0">Todas las categorías</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id_ca }}">{{ $cat->ca_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <select wire:model.live="filtroEstado" class="form-select form-select-sm" style="min-width:130px;">
                        <option value="todos">Todos</option>
                        <option value="sin_stock">Sin stock</option>
                    </select>
                </div>

                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="buscar"
                               placeholder="Nombre, código..." style="min-width:180px;">
                    </div>
                </div>

            </div>
        </div>

        {{-- Leyenda --}}
        <div class="d-flex gap-3 px-3 py-2 border-bottom bg-light small flex-wrap">
            <span class="text-muted">
                <span class="text-success fw-bold me-1">●</span>Con stock
                &nbsp;<span class="text-warning fw-bold me-1">●</span>Bajo mínimo
                &nbsp;<span class="text-danger fw-bold me-1">●</span>Sin stock
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 small" id="tablaStock">
                    <thead>
                        {{-- Fila 1: grupos --}}
                        <tr>
                            <th colspan="2" class="text-center" style="background:#fff;border-bottom:0;"></th>
                            @if($tiendasConfig->isNotEmpty())
                            <th colspan="{{ $tiendasConfig->count() }}" class="text-center fw-bold text-white"
                                style="background:#5a6268;">
                                <i class="fa-solid fa-store me-1"></i>Sedes / Tiendas
                            </th>
                            @endif
                        </tr>
                        {{-- Fila 2: columnas individuales --}}
                        <tr class="encabezado_tabla_color">
                            <th style="cursor:pointer;min-width:90px;" wire:click="ordenar('p.pro_codigo')">
                                Código
                                <i class="fa-solid fa-sort{{ $ordenColumna==='p.pro_codigo' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='p.pro_codigo' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            <th style="cursor:pointer;min-width:180px;" wire:click="ordenar('p.pro_nombre')">
                                Producto
                                <i class="fa-solid fa-sort{{ $ordenColumna==='p.pro_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                   {{ $ordenColumna!=='p.pro_nombre' ? ' opacity-25' : '' }} ms-1 small"></i>
                            </th>
                            @foreach($tiendasConfig as $tienda)
                            <th class="text-center" style="min-width:90px;" title="{{ $tienda->empresa_nombrecomercial }} — {{ $tienda->tienda_nombre }}">
                                {{ Str::limit($tienda->tienda_nombre, 14) }}
                                <div class="text-white-50 fw-normal" style="font-size:.65rem;">
                                    {{ Str::words($tienda->empresa_nombrecomercial, 2, '…') }}
                                </div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $prod)
                        @php
                            $sedeMap   = collect($stockSedes[$prod->id_pro] ?? [])->keyBy('id_tienda');
                            $totalSede = $sedeMap->sum('ps_stock');
                            $sinStock  = $totalSede <= 0;
                        @endphp
                        <tr class="{{ $sinStock ? 'table-danger' : '' }}">
                            <td class="text-muted">{{ $prod->pro_codigo }}</td>
                            <td>
                                <span class="fw-semibold">{{ $prod->pro_nombre }}</span>
                                @if($prod->pro_codigo_interno)
                                    <br><small class="text-muted" style="font-size:.72rem;">{{ $prod->pro_codigo_interno }}</small>
                                @endif
                            </td>
                            @foreach($tiendasConfig as $tienda)
                            @php
                                $reg = $sedeMap[$tienda->id_tienda] ?? null;
                                $qty = $reg ? (float) $reg->ps_stock : 0;
                                $bajMin = $reg && $qty > 0 && ($reg->ps_stock_minimo ?? 0) > 0 && $qty <= $reg->ps_stock_minimo;
                            @endphp
                            <td class="text-center fw-bold {{ $qty > 0 ? ($bajMin ? 'text-warning' : 'text-success') : 'text-danger' }}">
                                {{ number_format($qty, 0) }}
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 2 + $tiendasConfig->count() }}"
                                class="text-center text-muted py-5">
                                <i class="fa-solid fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                No se encontraron registros con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($productos->hasPages())
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }}
                    de {{ $productos->total() }} productos
                </small>
                {{ $productos->links(data: ['scrollTo' => false]) }}
            </div>
        </div>
        @endif
    </div>

    <div wire:loading wire:target="filtroFamilia, filtroCategoria, filtroEstado, buscar, porPagina, ordenar, exportarExcel">
        <x-loader />
    </div>

</div>
