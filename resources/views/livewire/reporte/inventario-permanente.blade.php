<div>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-warehouse me-2 text-primary"></i>Inventario Permanente
            </h5>
            <small class="text-muted">Genera el reporte de inventario permanente en Excel o PDF.</small>
        </div>

        <div class="card-body">
            <div class="row g-3">

                {{-- Fecha / Periodo --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Fecha / Periodo</label>
                    <select class="form-select form-select-sm" wire:model.live="modoRango">
                        <option value="fecha">Fecha</option>
                        <option value="periodo">Periodo</option>
                    </select>
                </div>

                @if($modoRango === 'fecha')
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Fecha desde</label>
                        <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Fecha hasta</label>
                        <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                    </div>
                    <div class="col-12 col-md-3"></div>
                @else
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Mes</label>
                        <select class="form-select form-select-sm" wire:model.live="filtroMes">
                            @foreach(['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                                @if($i > 0)<option value="{{ $i }}">{{ $m }}</option>@endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Año</label>
                        <select class="form-select form-select-sm" wire:model.live="filtroAnio">
                            @for($y = (int) now()->format('Y'); $y >= (int) now()->format('Y') - 6; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-12 col-md-3"></div>
                @endif

                {{-- Familia --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Familia</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroFamilia">
                        <option value="0">Todos</option>
                        @foreach($familias as $fa)
                            <option value="{{ $fa->id_fa }}">{{ $fa->fa_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Categoría --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Categoría</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCategoria">
                        <option value="0">Todos</option>
                        @foreach($categorias as $ca)
                            <option value="{{ $ca->id_ca }}">{{ $ca->ca_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de reporte (estático por el momento) --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tipo de reporte</label>
                    <select class="form-select form-select-sm" wire:model.live="tipoReporte">
                        <option value="detallado_diario">Detallado diario</option>
                        <option value="acumulado_dia">Acumulado por día</option>
                    </select>
                </div>

                {{-- Valorizado / Unidades físicas --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Modo</label>
                    <select class="form-select form-select-sm" wire:model.live="valorizado">
                        <option value="valorizado">Valorizado</option>
                        <option value="unidades">Unidades físicas</option>
                    </select>
                </div>

                {{-- Filtro de existencias --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filtro de existencias</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroExistencias">
                        <option value="todos">Todos</option>
                        <option value="con_saldo_positivo">Solo C/Saldo (+)</option>
                        <option value="sin_movimientos">Solo S/Movimiento</option>
                        <option value="con_saldo_negativo">Solo C/Saldos (-)</option>
                        <option value="con_movimientos">Solo C/Movimientos</option>
                    </select>
                </div>

                {{-- Botones (enlaces directos, sin ciclo Livewire) --}}
                <div class="col-12 d-flex gap-2 justify-content-end pt-2 border-top mt-2">
                    @can('inventario_permanente.exportar')
                    @php
                        $exportParams = [
                            'modo'        => $modoRango,
                            'desde'       => $filtroDesde,
                            'hasta'       => $filtroHasta,
                            'mes'         => $filtroMes,
                            'anio'        => $filtroAnio,
                            'familia'     => $filtroFamilia,
                            'categoria'   => $filtroCategoria,
                            'tipo'        => $tipoReporte,
                            'valorizado'  => $valorizado,
                            'existencias' => $filtroExistencias,
                            '_'           => now()->format('YmdHis'),
                        ];
                    @endphp
                    <a href="{{ route('reporte.inventario_permanente_excel', $exportParams) }}" target="_blank"
                       class="btn btn-outline-success btn-sm">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar Excel
                    </a>
                    <a href="{{ route('reporte.inventario_permanente_pdf', $exportParams) }}" target="_blank"
                       class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
        });
    </script>
</div>
