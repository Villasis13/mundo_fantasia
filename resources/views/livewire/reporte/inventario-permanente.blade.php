<div>
    @php
        $exportParams = [
            'modo'        => $modoRango, 'desde' => $filtroDesde, 'hasta' => $filtroHasta,
            'mes'         => $filtroMes, 'anio' => $filtroAnio,
            'familia'     => $filtroFamilia, 'categoria' => $filtroCategoria,
            'tipo'        => $tipoReporte, 'valorizado' => $valorizado,
            'existencias' => $filtroExistencias, 'empresa' => $empresaSeleccionada,
            'ordenar'     => $ordenar, '_' => now()->format('YmdHis'),
        ];
    @endphp

    <style>
        .ip-card{background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(16,24,40,.04);}
        .ip-sec-title{display:flex;align-items:center;gap:10px;font-weight:700;font-size:1.02rem;color:#1f2937;margin-bottom:18px;}
        .ip-sec-title i{color:#4b5563;}
        .ip-label{font-size:.72rem;font-weight:700;letter-spacing:.04em;color:#6b7280;text-transform:uppercase;margin-bottom:8px;display:block;}
        .ip-select{border:1px solid #e5e7eb;border-radius:9px;padding:.55rem .8rem;font-size:.9rem;background:#fff;width:100%;}
        .ip-select:focus{outline:none;border-color:#8b93f8;box-shadow:0 0 0 3px rgba(99,102,241,.12);}
        .ip-toggle{display:flex;border:1px solid #e5e7eb;border-radius:9px;overflow:hidden;}
        .ip-toggle button{flex:1;border:0;background:#fff;padding:.55rem 0;font-size:.9rem;color:#4b5563;cursor:pointer;}
        .ip-toggle button.active{background:#eef1f6;font-weight:600;color:#111827;}
        .ip-radio{display:flex;align-items:center;gap:9px;padding:6px 0;cursor:pointer;font-size:.92rem;color:#374151;}
        .ip-radio input{width:17px;height:17px;accent-color:#111827;cursor:pointer;}
        .ip-col-div{border-left:1px solid #eef0f3;}
        @media (max-width:767px){.ip-col-div{border-left:0;}}
    </style>

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ══ PARÁMETROS DEL REPORTE ══ --}}
    <div class="ip-card">
        <div class="ip-sec-title"><i class="fa-solid fa-sliders"></i> Parámetros del reporte</div>
        <div class="row g-4 align-items-start">
            <div class="col-12 col-md-5">
                <label class="ip-label">Almacén</label>
                <select class="ip-select" wire:model.live="empresaSeleccionada">
                    <option value="0">Todos</option>
                    @foreach($empresas as $e)
                        <option value="{{ $e->id_empresa }}">{{ $e->empresa_razon_social ?: $e->empresa_nombrecomercial }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="ip-label">Consultar por</label>
                <div class="ip-toggle">
                    <button type="button" class="{{ $modoRango === 'periodo' ? 'active' : '' }}" wire:click="$set('modoRango','periodo')">Periodo</button>
                    <button type="button" class="{{ $modoRango === 'fecha' ? 'active' : '' }}" wire:click="$set('modoRango','fecha')">Fechas</button>
                </div>
            </div>
            <div class="col-12 col-md-4">
                @if($modoRango === 'periodo')
                    <label class="ip-label">Periodo</label>
                    <div class="d-flex gap-2">
                        <select class="ip-select" wire:model.live="filtroMes">
                            @foreach(['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                                @if($i > 0)<option value="{{ $i }}">{{ $m }}</option>@endif
                            @endforeach
                        </select>
                        <select class="ip-select" wire:model.live="filtroAnio" style="max-width:110px;">
                            @for($y = (int) now()->format('Y'); $y >= (int) now()->format('Y') - 6; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @else
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="ip-label">Desde</label>
                            <input type="date" class="ip-select" wire:model.live="filtroDesde">
                        </div>
                        <div class="col-6">
                            <label class="ip-label">Hasta</label>
                            <input type="date" class="ip-select" wire:model.live="filtroHasta">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══ CONFIGURACIÓN DEL REPORTE ══ --}}
    <div class="ip-card">
        <div class="ip-sec-title"><i class="fa-solid fa-gear"></i> Configuración del reporte</div>
        <div class="row g-4">
            {{-- Tipo de reporte --}}
            <div class="col-12 col-md-3">
                <label class="ip-label">Tipo de reporte</label>
                <label class="ip-radio"><input type="radio" wire:model.live="tipoReporte" value="detallado_diario"> Detallado diario</label>
                <label class="ip-radio"><input type="radio" wire:model.live="tipoReporte" value="acumulado_dia"> Acumulado por día</label>
            </div>
            {{-- Presentación --}}
            <div class="col-12 col-md-3 ip-col-div">
                <label class="ip-label">Presentación</label>
                <label class="ip-radio"><input type="radio" wire:model.live="valorizado" value="valorizado"> Valorizado</label>
                <label class="ip-radio"><input type="radio" wire:model.live="valorizado" value="unidades"> Unidades físicas</label>
            </div>
            {{-- Mostrar productos --}}
            <div class="col-12 col-md-3 ip-col-div">
                <label class="ip-label">Mostrar productos</label>
                <label class="ip-radio"><input type="radio" wire:model.live="filtroExistencias" value="todos"> Todos</label>
                <label class="ip-radio"><input type="radio" wire:model.live="filtroExistencias" value="con_saldo"> Solo con saldo</label>
                <label class="ip-radio"><input type="radio" wire:model.live="filtroExistencias" value="con_saldo_positivo"> Solo con saldo positivo</label>
                <label class="ip-radio"><input type="radio" wire:model.live="filtroExistencias" value="con_movimientos"> Solo con movimientos</label>
                <label class="ip-radio"><input type="radio" wire:model.live="filtroExistencias" value="sin_movimientos"> Solo sin movimientos</label>
            </div>
            {{-- Ordenar por --}}
            <div class="col-12 col-md-3 ip-col-div">
                <label class="ip-label">Ordenar por</label>
                <label class="ip-radio"><input type="radio" wire:model.live="ordenar" value="producto"> Producto</label>
                <label class="ip-radio"><input type="radio" wire:model.live="ordenar" value="codigo"> Código</label>
            </div>
        </div>
    </div>

    {{-- ══ PRODUCTOS A INCLUIR ══ --}}
    <div class="ip-card">
        <div class="ip-sec-title"><i class="fa-solid fa-box-open"></i> Productos a incluir</div>
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <label class="ip-label">Familia</label>
                <select class="ip-select" wire:model.live="filtroFamilia">
                    <option value="0">Todos</option>
                    @foreach($familias as $fa)
                        <option value="{{ $fa->id_fa }}">{{ $fa->fa_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="ip-label">Categoría</label>
                <select class="ip-select" wire:model.live="filtroCategoria">
                    <option value="0">Todos</option>
                    @foreach($categorias as $ca)
                        <option value="{{ $ca->id_ca }}">{{ $ca->ca_nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- ══ BOTONES ══ --}}
    @can('inventario_permanente.exportar')
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('reporte.inventario_permanente_excel', $exportParams) }}" target="_blank"
           class="btn btn-success d-inline-flex align-items-center gap-2 px-3">
            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" alt="Excel" style="width:18px;height:18px;filter:brightness(0) invert(1);"> Exportar Excel
        </a>
        <a href="{{ route('reporte.inventario_permanente_pdf', $exportParams) }}" target="_blank"
           class="btn btn-danger d-inline-flex align-items-center gap-2 px-3">
            <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </a>
    </div>
    @endcan

</div>
