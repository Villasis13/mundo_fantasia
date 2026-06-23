<div>
    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-book-open me-2 text-primary"></i>Kardex
            </h5>
            <small class="text-muted">Movimientos de entradas, salidas y saldo por producto</small>
        </div>
        @if($buscando && (
            $tipoKardex === 'resumido'  ? count($lineasResumido) > 0 :
            ($idProducto               ? ($saldoInicial || count($lineas) > 0) : count($kardexPorProducto) > 0)
        ))
            @can('kardex_valorizado.exportar')
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-danger fw-semibold"
                        wire:click="exportarPdf"
                        wire:loading.attr="disabled" wire:target="exportarPdf">
                    <span wire:loading.remove wire:target="exportarPdf">
                        <img src="{{ asset('iconos_svg/pdf.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> PDF
                    </span>
                    <span wire:loading wire:target="exportarPdf">
                        <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                    </span>
                </button>
                <button class="btn btn-sm btn-outline-success fw-semibold"
                        wire:click="exportarExcel"
                        wire:loading.attr="disabled" wire:target="exportarExcel">
                    <span wire:loading.remove wire:target="exportarExcel">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Excel
                    </span>
                    <span wire:loading wire:target="exportarExcel">
                        <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                    </span>
                </button>
            </div>
            @endcan
        @endif
    </div>

    {{-- ── Flash ──────────────────────────────────────────────── --}}
    @if(session('errorGeneral'))
        <div class="alert alert-danger alert-dismissible py-2 mb-3">
            {{ session('errorGeneral') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->has('idProducto'))
        <div class="alert alert-warning py-2 mb-3">{{ $errors->first('idProducto') }}</div>
    @endif
    @if($errors->has('ubicacion'))
        <div class="alert alert-warning py-2 mb-3">{{ $errors->first('ubicacion') }}</div>
    @endif

    {{-- ── Filtros ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">

                {{-- Almacén / Empresa (select combinado igual que Origen en Transferencias) --}}
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label form-label-sm mb-1">Almacén / Empresa <span class="text-danger">*</span></label>
                    <select wire:model.live="ubicacionKey" class="form-select form-select-sm @error('ubicacionKey') is-invalid @enderror">
                        <option value="">— Seleccione —</option>
                        @if(count($almacenesDisponibles) > 0)
                            <optgroup label="Almacenes">
                                @foreach($almacenesDisponibles as $alm)
                                    <option value="almacen_{{ $alm->id_almacen }}">{{ $alm->almacen_nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(count($empresas) > 0)
                            <optgroup label="Empresas / Sedes">
                                @foreach($empresas as $emp)
                                    <option value="empresa_{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('ubicacionKey')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Sede (aparece solo cuando se selecciona una empresa) --}}
                @if(str_starts_with($ubicacionKey, 'empresa_'))
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm mb-1">Sede</label>
                        <select wire:model.live="sucursalSeleccionada" class="form-select form-select-sm"
                                {{ count($sucursalesDisponibles) === 0 ? 'disabled' : '' }}>
                            <option value="0">— Seleccione sede —</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Tipo kardex --}}
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select wire:model.live="tipoKardex" class="form-select form-select-sm">
                        <option value="fisico">Físico</option>
                        <option value="valorizado">Valorizado</option>
                        <option value="resumido">Resumido</option>
                    </select>
                </div>

                {{-- Familia / Marca (siempre visible, obligatorio) --}}
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label form-label-sm mb-1">
                        Familia / Marca <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="familiaSeleccionada"
                            class="form-select form-select-sm @error('familiaSeleccionada') is-invalid @enderror">
                        <option value="0">— Seleccione familia —</option>
                        @foreach($familias as $fam)
                            <option value="{{ $fam->id_fa }}">{{ $fam->fa_nombre }}</option>
                        @endforeach
                    </select>
                    @error('familiaSeleccionada')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Búsqueda producto (opcional) --}}
                <div class="col-12 col-sm-3 col-lg-3">
                    <label class="form-label form-label-sm mb-1">Producto</label>
                    <div class="position-relative">
                        <div class="input-group input-group-sm">
                            <input type="text"
                                   wire:model.live.debounce.300ms="busquedaProducto"
                                   wire:keyup="buscarProducto"
                                   wire:keydown.escape="limpiarProducto"
                                   class="form-control form-control-sm @error('idProducto') is-invalid @enderror"
                                   placeholder="Nombre, código, familia, categoría..."
                                   autocomplete="off">
                            @if($idProducto)
                                <button type="button" wire:click="limpiarProducto"
                                        class="btn btn-outline-secondary btn-sm" title="Limpiar">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            @endif
                        </div>
                        @if(count($sugerenciasProducto) > 0)
                            <div class="position-absolute w-100 bg-white border rounded shadow-sm mt-1"
                                 style="z-index:1050;max-height:220px;overflow-y:auto;">
                                @foreach($sugerenciasProducto as $sug)
                                    <div class="px-3 py-2 cursor-pointer hover-bg-light"
                                         style="cursor:pointer;"
                                         wire:click="seleccionarProducto({{ $sug->id_pro }}, '{{ addslashes($sug->pro_nombre) }}', '{{ $sug->pro_codigo }}')"
                                         onmouseover="this.style.background='#f0f4ff'"
                                         onmouseout="this.style.background=''">
                                        <span class="fw-semibold text-dark" style="font-size:0.8rem;">{{ $sug->pro_nombre }}</span>
                                        <span class="text-muted ms-2" style="font-size:0.75rem;">[{{ $sug->pro_codigo }}]</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($idProducto)
                        <small class="text-success"><i class="fa-solid fa-check me-1"></i>{{ $productoNombre }}</small>
                    @endif
                </div>

                {{-- Desde --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" wire:model="desde"
                           class="form-control form-control-sm @error('desde') is-invalid @enderror">
                    @error('desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Hasta --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" wire:model="hasta"
                           class="form-control form-control-sm @error('hasta') is-invalid @enderror">
                    @error('hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Botón buscar --}}
                <div class="col-12 col-lg-auto">
                    <button wire:click="buscar" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-100">
                        <span wire:loading wire:target="buscar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="buscar" class="fa-solid fa-magnifying-glass me-1"></i>
                        Generar Kardex
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Resultado ───────────────────────────────────────────── --}}
    @if($buscando)

    {{-- ══ FORMATO RESUMIDO ══════════════════════════════════════════════════ --}}
    @if($tipoKardex === 'resumido')

        @if(count($lineasResumido) === 0)
            <div class="alert alert-info py-2 mb-0">
                <i class="fa-solid fa-circle-info me-2"></i>No se encontraron datos para el período seleccionado.
            </div>
        @else

            {{-- Cabecera SUNAT resumido --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="text-center fw-bold mb-2 text-uppercase" style="font-size:0.82rem;letter-spacing:0.04em;">
                        Registro de Inventario Permanente Valorizado (Resumen)
                    </div>
                    <div class="row g-1 text-start" style="font-size:0.78rem;">
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">PERIODO:</span>
                            <span class="ms-1">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">NOMBRE Y/O RAZÓN SOCIAL:</span>
                            <span class="ms-1">{{ $headerInfo['empresa_nombre'] ?? '—' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">RUC:</span>
                            <span class="ms-1">{{ $headerInfo['empresa_ruc'] ?? '—' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">ESTABLECIMIENTO:</span>
                            <span class="ms-1">{{ $headerInfo['sede_nombre'] ?? '—' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">TIPO (TABLA 5):</span>
                            <span class="ms-1">01 MERCADERIA</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted fw-semibold" style="font-size:0.7rem;">MÉTODO:</span>
                            <span class="ms-1">PROMEDIO PONDERADO MÓVIL</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla resumido --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:0.73rem;">
                            <thead>
                                <tr style="background:#1e3a5f;color:white;" class="text-center">
                                    <th class="text-white" style="width:80px;">COD. EXISTENCIA</th>
                                    <th class="text-white">PRODUCTO</th>
                                    <th class="text-white" style="width:80px;">SALDO INICIAL</th>
                                    <th class="text-white" style="width:70px;" style="background:#1a6b35;">INGRESOS CANT.</th>
                                    <th class="text-white" style="width:70px;" style="background:#7b1e1e;">EGRESOS CANT.</th>
                                    <th class="text-white" style="width:80px;">SALDO FINAL</th>
                                    <th class="text-white" style="width:80px;">C.U.</th>
                                    <th class="text-white" style="width:90px;">C.T.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalGeneral = 0; @endphp
                                @foreach($lineasResumido as $grupo)
                                <tr style="background:#dce8ff;font-weight:700;font-size:0.75rem;">
                                    <td colspan="8" class="ps-2">
                                        <i class="fa-solid fa-tag me-1 text-primary" style="font-size:0.65rem;"></i>
                                        MARCA: {{ $grupo['familia'] }}
                                    </td>
                                </tr>
                                @foreach($grupo['productos'] as $i => $prod)
                                <tr class="{{ $i % 2 === 0 ? '' : 'table-light' }}">
                                    <td class="text-center" style="font-size:0.7rem;">{{ $prod['codigo'] }}</td>
                                    <td>{{ $prod['nombre'] }}</td>
                                    <td class="text-end">{{ number_format($prod['saldo_ini_cant'], 2) }}</td>
                                    <td class="text-end" style="color:#1a6b35;">{{ number_format($prod['ingresos_cant'], 2) }}</td>
                                    <td class="text-end" style="color:#c0392b;">{{ number_format($prod['egresos_cant'], 2) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($prod['saldo_final_cant'], 2) }}</td>
                                    <td class="text-end">{{ number_format($prod['c_u'], 4) }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($prod['c_t'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:0.75rem;">
                                    <td colspan="7" class="text-end pe-3">TOTALES:</td>
                                    <td class="text-end">S/ {{ number_format($grupo['total_ct'], 2) }}</td>
                                </tr>
                                @php $totalGeneral += $grupo['total_ct']; @endphp
                                @endforeach
                                <tr style="background:#0d1b2a;color:#fff;font-weight:700;font-size:0.78rem;">
                                    <td colspan="7" class="text-end pe-3">TOTAL GENERAL:</td>
                                    <td class="text-end">S/ {{ number_format($totalGeneral, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endif

    {{-- ══ MULTI-PRODUCTO (fisico/valorizado sin producto, por familia) ═════ --}}
    @elseif(!$idProducto)

        {{-- Cabecera SUNAT (siempre visible) --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="text-center fw-bold mb-2 text-uppercase" style="font-size:0.82rem;letter-spacing:0.04em;">
                    {{ $tipoKardex === 'fisico'
                        ? 'Registro del Inventario Permanente en Unidades Físicas'
                        : 'Registro del Inventario Permanente Valorizado' }}
                </div>
                <div class="row g-1 text-start" style="font-size:0.78rem;">
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">PERIODO:</span>
                        <span class="ms-1">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">NOMBRE Y/O RAZÓN SOCIAL:</span>
                        <span class="ms-1">{{ $headerInfo['empresa_nombre'] ?? '—' }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">RUC:</span>
                        <span class="ms-1">{{ $headerInfo['empresa_ruc'] ?? '—' }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">ESTABLECIMIENTO:</span>
                        <span class="ms-1">{{ $headerInfo['sede_nombre'] ?? '—' }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">TIPO (TABLA 5):</span>
                        <span class="ms-1">01 MERCADERIA</span>
                    </div>
                    @if($tipoKardex !== 'fisico')
                    <div class="col-12">
                        <span class="text-muted fw-semibold" style="font-size:0.7rem;">MÉTODO:</span>
                        <span class="ms-1">PROMEDIO PONDERADO MÓVIL</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Banda Familia / Marca --}}
        @php $familiaNombre = collect($familias)->firstWhere('id_fa', $familiaSeleccionada)?->fa_nombre ?? '—'; @endphp
        <div class="d-flex align-items-center gap-2 px-3 py-2 mb-3 rounded"
             style="background:#dce8ff;font-size:0.82rem;font-weight:700;letter-spacing:0.03em;">
            <i class="fa-solid fa-tag text-primary" style="font-size:0.75rem;"></i>
            FAMILIA / MARCA: {{ $familiaNombre }}
        </div>

        @if(empty($kardexPorProducto))
            <div class="alert alert-info py-2 mb-0">
                <i class="fa-solid fa-circle-info me-2"></i>No se encontraron movimientos para el período seleccionado.
            </div>
        @else
            @foreach($kardexPorProducto as $kp)
            @php
                $kpSI  = $kp['saldoInicial'];
                $kpLn  = $kp['lineas'];
                $kpTot = $kp['totales'];
            @endphp
            <div class="mb-4">
                <div class="px-3 py-2 d-flex align-items-center gap-2 rounded-top"
                     style="background:#1e3a5f;color:#fff;font-size:0.8rem;">
                    <i class="fa-solid fa-box fa-sm"></i>
                    <span class="fw-bold">{{ $kp['codigo'] }}</span>
                    <span>{{ $kp['nombre'] }}</span>
                </div>
                <div class="card border-0 shadow-sm rounded-0 rounded-bottom">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                        @if($tipoKardex === 'fisico')
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.73rem;">
                                <thead>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th colspan="5" class="text-white py-1">DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO, DOCUMENTO INTERNO O SIMILAR</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:80px;">TIPO OPERACIÓN</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:65px;">ENTRADAS</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:65px;">SALIDAS</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:75px;">SALDO FINAL</th>
                                    </tr>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th class="text-white" style="width:72px;">FECHA</th>
                                        <th class="text-white" style="width:80px;">T/DOC</th>
                                        <th class="text-white" style="width:50px;">SERIE</th>
                                        <th class="text-white" style="width:70px;">NÚMERO</th>
                                        <th class="text-white">CLIENTE Y/O PROVEEDOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($kpSI)
                                    <tr style="background:#f0f4ff;font-weight:600;">
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="4" class="text-center text-muted" style="font-size:0.72rem;">SALDO INICIAL AL {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-end text-muted">—</td>
                                        <td class="text-end text-muted">—</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($kpSI['cantidad'], 2) }}</td>
                                    </tr>
                                    @endif
                                    @forelse($kpLn as $i => $ln)
                                    <tr class="{{ $i % 2 === 0 ? '' : 'table-light' }}">
                                        <td class="text-center">{{ \Carbon\Carbon::parse($ln['fecha'])->format('d/m/Y') }}</td>
                                        <td class="text-center" style="font-size:0.7rem;">{{ $ln['tdoc'] ?? '00' }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-center">{{ $ln['id_referencia'] ?? $ln['id_movimiento'] }}</td>
                                        <td class="text-truncate" style="max-width:180px;" title="{{ $ln['motivo'] ?? '' }}">{{ $ln['motivo'] ?? '—' }}</td>
                                        <td class="text-center" style="font-size:0.7rem;">{{ $ln['tipo_op'] ?? '99' }}</td>
                                        <td class="text-end" style="color:#1a6b35;">{{ $ln['entrada_cant'] !== null ? number_format($ln['entrada_cant'], 2) : '—' }}</td>
                                        <td class="text-end" style="color:#c0392b;">{{ $ln['salida_cant'] !== null ? number_format($ln['salida_cant'], 2) : '—' }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($ln['saldo_cant'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="9" class="text-center text-muted py-2">Sin movimientos en el período.</td></tr>
                                    @endforelse
                                    @if($kpTot && count($kpLn) > 0)
                                    <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:0.78rem;">
                                        <td colspan="6" class="text-end pe-3">TOTALES DEL PERÍODO</td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($kpTot['entrada_cant'], 2) }}</td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($kpTot['salida_cant'], 2) }}</td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($kpTot['saldo_cant'], 2) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        @else
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.72rem;">
                                <thead>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th colspan="4" class="text-white py-1">DOCUMENTO</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:75px;">TIPO OPERACIÓN</th>
                                        <th colspan="3" class="text-white" style="background:#1a6b35;">ENTRADAS</th>
                                        <th colspan="3" class="text-white" style="background:#7b1e1e;">SALIDAS</th>
                                        <th colspan="3" class="text-white" style="background:#1a3a6b;">SALDO FINAL</th>
                                    </tr>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th class="text-white" style="width:68px;">FECHA</th>
                                        <th class="text-white" style="width:72px;">T/DOC</th>
                                        <th class="text-white" style="width:48px;">SERIE</th>
                                        <th class="text-white" style="width:65px;">NÚMERO</th>
                                        <th class="text-white" style="background:#1a6b35;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#1a6b35;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#1a6b35;width:68px;">COSTO TOTAL</th>
                                        <th class="text-white" style="background:#7b1e1e;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#7b1e1e;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#7b1e1e;width:68px;">COSTO TOTAL</th>
                                        <th class="text-white" style="background:#1a3a6b;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#1a3a6b;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#1a3a6b;width:68px;">COSTO TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($kpSI)
                                    @php $siCu = $kpSI['cantidad'] != 0 ? $kpSI['valor'] / $kpSI['cantidad'] : 0; @endphp
                                    <tr style="background:#f0f4ff;font-weight:600;">
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted" style="font-size:0.68rem;">SALDO INICIAL AL {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted">—</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($kpSI['cantidad'], 2) }}</td>
                                        <td class="text-end text-primary">{{ number_format($siCu, 4) }}</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($kpSI['valor'], 2) }}</td>
                                    </tr>
                                    @endif
                                    @forelse($kpLn as $i => $ln)
                                    @php $sCu = $ln['saldo_cant'] != 0 ? $ln['saldo_valor'] / $ln['saldo_cant'] : 0; @endphp
                                    <tr class="{{ $i % 2 === 0 ? '' : 'table-light' }}">
                                        <td class="text-center">{{ \Carbon\Carbon::parse($ln['fecha'])->format('d/m/Y') }}</td>
                                        <td class="text-center" style="font-size:0.68rem;">{{ $ln['tdoc'] ?? '00' }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-center">{{ $ln['id_referencia'] ?? $ln['id_movimiento'] }}</td>
                                        <td class="text-center" style="font-size:0.68rem;">{{ $ln['tipo_op'] ?? '99' }}</td>
                                        <td class="text-end" style="color:#1a6b35;">{{ $ln['entrada_cant'] !== null ? number_format($ln['entrada_cant'], 2) : '—' }}</td>
                                        <td class="text-end" style="color:#1a6b35;">{{ $ln['entrada_cu'] !== null ? number_format($ln['entrada_cu'], 4) : '—' }}</td>
                                        <td class="text-end fw-semibold" style="color:#1a6b35;">{{ $ln['entrada_total'] !== null ? number_format($ln['entrada_total'], 2) : '—' }}</td>
                                        <td class="text-end" style="color:#c0392b;">{{ $ln['salida_cant'] !== null ? number_format($ln['salida_cant'], 2) : '—' }}</td>
                                        <td class="text-end" style="color:#c0392b;">{{ $ln['salida_cu'] !== null ? number_format($ln['salida_cu'], 4) : '—' }}</td>
                                        <td class="text-end fw-semibold" style="color:#c0392b;">{{ $ln['salida_total'] !== null ? number_format($ln['salida_total'], 2) : '—' }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($ln['saldo_cant'], 2) }}</td>
                                        <td class="text-end text-primary">{{ number_format($sCu, 4) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($ln['saldo_valor'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="14" class="text-center text-muted py-2">Sin movimientos en el período.</td></tr>
                                    @endforelse
                                    @if($kpTot && count($kpLn) > 0)
                                    <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:0.72rem;">
                                        <td colspan="5" class="text-end pe-3">TOTALES DEL PERÍODO</td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($kpTot['entrada_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($kpTot['entrada_valor'], 2) }}</td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($kpTot['salida_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($kpTot['salida_valor'], 2) }}</td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($kpTot['saldo_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($kpTot['saldo_valor'], 2) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif

    @else

        @if(count($lineas) === 0 && !$saldoInicial)
            <div class="alert alert-info py-2 mb-0">
                <i class="fa-solid fa-circle-info me-2"></i>No se encontraron movimientos para el período seleccionado.
            </div>
        @else

            {{-- ══ FORMATO FÍSICO (SUNAT) ════════════════════════════════ --}}
            @if($tipoKardex === 'fisico')

                {{-- Cabecera SUNAT --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="text-center fw-bold mb-2 text-uppercase" style="font-size:0.82rem;letter-spacing:0.04em;">
                            Registro del Inventario Permanente en Unidades Físicas
                        </div>
                        <div class="row g-1 text-start" style="font-size:0.78rem;">
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">PERIODO:</span>
                                <span class="ms-1">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">NOMBRE Y/O RAZÓN SOCIAL:</span>
                                <span class="ms-1">{{ $headerInfo['empresa_nombre'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">RUC:</span>
                                <span class="ms-1">{{ $headerInfo['empresa_ruc'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">ESTABLECIMIENTO:</span>
                                <span class="ms-1">{{ $headerInfo['sede_nombre'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">TIPO (TABLA 5):</span>
                                <span class="ms-1">01 MERCADERIA</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">COD. UND DE MED (TABLA 6):</span>
                                <span class="ms-1">07 UND</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabla físico SUNAT --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.73rem;">
                                <thead>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th colspan="5" class="text-white py-1">DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO, DOCUMENTO INTERNO O SIMILAR</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:80px;">TIPO OPERACIÓN <span style="font-size:0.6rem;opacity:0.8;">(TAB 12)</span></th>
                                        <th rowspan="2" class="align-middle text-white" style="width:65px;">ENTRADAS</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:65px;">SALIDAS</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:75px;">SALDO FINAL</th>
                                    </tr>
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th class="text-white" style="width:72px;">FECHA</th>
                                        <th class="text-white" style="width:80px;">T/DOC <span style="font-size:0.6rem;opacity:0.8;">(TAB 10)</span></th>
                                        <th class="text-white" style="width:50px;">SERIE</th>
                                        <th class="text-white" style="width:70px;">NÚMERO</th>
                                        <th class="text-white">CLIENTE Y/O PROVEEDOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Saldo inicial --}}
                                    @if($saldoInicial)
                                    <tr style="background:#f0f4ff;font-weight:600;">
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="4" class="text-center text-muted" style="font-size:0.72rem;">
                                            SALDO INICIAL AL {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-end text-muted">—</td>
                                        <td class="text-end text-muted">—</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($saldoInicial['cantidad'], 2) }}</td>
                                    </tr>
                                    @endif

                                    @forelse($lineas as $i => $ln)
                                    <tr class="{{ $i % 2 === 0 ? '' : 'table-light' }}">
                                        <td class="text-center">{{ \Carbon\Carbon::parse($ln['fecha'])->format('d/m/Y') }}</td>
                                        <td class="text-center" style="font-size:0.7rem;">{{ $ln['tdoc'] ?? '00' }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-center">{{ $ln['id_referencia'] ?? $ln['id_movimiento'] }}</td>
                                        <td class="text-truncate" style="max-width:180px;" title="{{ $ln['motivo'] ?? '' }}">
                                            {{ $ln['motivo'] ?? '—' }}
                                        </td>
                                        <td class="text-center" style="font-size:0.7rem;">{{ $ln['tipo_op'] ?? '99' }}</td>
                                        <td class="text-end" style="color:#1a6b35;">
                                            {{ $ln['entrada_cant'] !== null ? number_format($ln['entrada_cant'], 2) : '—' }}
                                        </td>
                                        <td class="text-end" style="color:#c0392b;">
                                            {{ $ln['salida_cant'] !== null ? number_format($ln['salida_cant'], 2) : '—' }}
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ number_format($ln['saldo_cant'], 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3">
                                            No hay movimientos en el período seleccionado.
                                        </td>
                                    </tr>
                                    @endforelse

                                    @if($totales && count($lineas) > 0)
                                    <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:0.78rem;">
                                        <td colspan="6" class="text-end pe-3">TOTALES DEL PERÍODO</td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($totales['entrada_cant'], 2) }}</td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($totales['salida_cant'], 2) }}</td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($totales['saldo_cant'], 2) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            {{-- ══ FORMATO VALORIZADO ═══════════════════════════════════ --}}
            @else

                {{-- Cabecera SUNAT valorizado --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="text-center fw-bold mb-2 text-uppercase" style="font-size:0.82rem;letter-spacing:0.04em;">
                            Registro del Inventario Permanente Valorizado
                        </div>
                        <div class="row g-1 text-start" style="font-size:0.78rem;">
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">PERIODO:</span>
                                <span class="ms-1">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">NOMBRE Y/O RAZÓN SOCIAL:</span>
                                <span class="ms-1">{{ $headerInfo['empresa_nombre'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">RUC:</span>
                                <span class="ms-1">{{ $headerInfo['empresa_ruc'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">ESTABLECIMIENTO:</span>
                                <span class="ms-1">{{ $headerInfo['sede_nombre'] ?? '—' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">TIPO (TABLA 5):</span>
                                <span class="ms-1">01 MERCADERIA</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">COD. UND DE MED (TABLA 6):</span>
                                <span class="ms-1">07 UND</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fw-semibold" style="font-size:0.7rem;">MÉTODO:</span>
                                <span class="ms-1">PROMEDIO PONDERADO MÓVIL</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabla kardex valorizado SUNAT --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.72rem;">
                                <thead>
                                    {{-- Fila 1 --}}
                                    <tr style="background:#1e3a5f;color:white;" class="text-center">
                                        <th colspan="4" class="text-white py-1">DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO, DOCUMENTO INTERNO O SIMILAR</th>
                                        <th rowspan="2" class="align-middle text-white" style="width:75px;">TIPO OPERACIÓN <span style="font-size:0.6rem;opacity:0.8;">(TAB 12)</span></th>
                                        <th colspan="3" class="text-white" style="background:#1a6b35;">ENTRADAS</th>
                                        <th colspan="3" class="text-white" style="background:#7b1e1e;">SALIDAS</th>
                                        <th colspan="3" class="text-white" style="background:#1a3a6b;">SALDO FINAL</th>
                                    </tr>
                                    {{-- Fila 2 --}}
                                    <tr style="background:#1e3a5f;color:white;" class="text-center" style="font-size:0.68rem;">
                                        <th class="text-white" style="width:68px;">FECHA</th>
                                        <th class="text-white" style="width:72px;">T/DOC <span style="font-size:0.6rem;opacity:0.8;">(TAB 10)</span></th>
                                        <th class="text-white" style="width:48px;">SERIE</th>
                                        <th class="text-white" style="width:65px;">NÚMERO</th>
                                        <th class="text-white" style="background:#1a6b35;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#1a6b35;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#1a6b35;width:68px;">COSTO TOTAL</th>
                                        <th class="text-white" style="background:#7b1e1e;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#7b1e1e;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#7b1e1e;width:68px;">COSTO TOTAL</th>
                                        <th class="text-white" style="background:#1a3a6b;width:58px;">CANTIDAD</th>
                                        <th class="text-white" style="background:#1a3a6b;width:68px;">COSTO UNIT.</th>
                                        <th class="text-white" style="background:#1a3a6b;width:68px;">COSTO TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Saldo inicial --}}
                                    @if($saldoInicial)
                                    @php
                                        $siCu = ($saldoInicial['cantidad'] != 0)
                                            ? $saldoInicial['valor'] / $saldoInicial['cantidad']
                                            : 0;
                                    @endphp
                                    <tr style="background:#f0f4ff;font-weight:600;">
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted" style="font-size:0.68rem;">
                                            SALDO INICIAL AL {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted">—</td>
                                        <td colspan="3" class="text-center text-muted">—</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($saldoInicial['cantidad'], 2) }}</td>
                                        <td class="text-end text-primary">{{ number_format($siCu, 4) }}</td>
                                        <td class="text-end text-primary fw-bold">{{ number_format($saldoInicial['valor'], 2) }}</td>
                                    </tr>
                                    @endif

                                    @forelse($lineas as $i => $ln)
                                    @php
                                        $saldoCu = ($ln['saldo_cant'] != 0)
                                            ? $ln['saldo_valor'] / $ln['saldo_cant']
                                            : 0;
                                    @endphp
                                    <tr class="{{ $i % 2 === 0 ? '' : 'table-light' }}">
                                        <td class="text-center">{{ \Carbon\Carbon::parse($ln['fecha'])->format('d/m/Y') }}</td>
                                        <td class="text-center" style="font-size:0.68rem;">{{ $ln['tdoc'] ?? '00' }}</td>
                                        <td class="text-center text-muted">—</td>
                                        <td class="text-center">{{ $ln['id_referencia'] ?? $ln['id_movimiento'] }}</td>
                                        <td class="text-center" style="font-size:0.68rem;">{{ $ln['tipo_op'] ?? '99' }}</td>
                                        {{-- Entradas --}}
                                        <td class="text-end" style="color:#1a6b35;">
                                            {{ $ln['entrada_cant'] !== null ? number_format($ln['entrada_cant'], 2) : '—' }}
                                        </td>
                                        <td class="text-end" style="color:#1a6b35;">
                                            {{ $ln['entrada_cu'] !== null ? number_format($ln['entrada_cu'], 4) : '—' }}
                                        </td>
                                        <td class="text-end fw-semibold" style="color:#1a6b35;">
                                            {{ $ln['entrada_total'] !== null ? number_format($ln['entrada_total'], 2) : '—' }}
                                        </td>
                                        {{-- Salidas --}}
                                        <td class="text-end" style="color:#c0392b;">
                                            {{ $ln['salida_cant'] !== null ? number_format($ln['salida_cant'], 2) : '—' }}
                                        </td>
                                        <td class="text-end" style="color:#c0392b;">
                                            {{ $ln['salida_cu'] !== null ? number_format($ln['salida_cu'], 4) : '—' }}
                                        </td>
                                        <td class="text-end fw-semibold" style="color:#c0392b;">
                                            {{ $ln['salida_total'] !== null ? number_format($ln['salida_total'], 2) : '—' }}
                                        </td>
                                        {{-- Saldo final --}}
                                        <td class="text-end fw-bold text-primary">{{ number_format($ln['saldo_cant'], 2) }}</td>
                                        <td class="text-end text-primary">{{ number_format($saldoCu, 4) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($ln['saldo_valor'], 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-muted py-3">
                                            No hay movimientos en el período seleccionado.
                                        </td>
                                    </tr>
                                    @endforelse

                                    @if($totales && count($lineas) > 0)
                                    <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:0.72rem;">
                                        <td colspan="5" class="text-end pe-3">TOTALES DEL PERÍODO</td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($totales['entrada_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#7effa7;">{{ number_format($totales['entrada_valor'], 2) }}</td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($totales['salida_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#ffaaaa;">{{ number_format($totales['salida_valor'], 2) }}</td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($totales['saldo_cant'], 2) }}</td>
                                        <td></td>
                                        <td class="text-end" style="color:#aad4ff;">{{ number_format($totales['saldo_valor'], 2) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            @endif {{-- end tipoKardex fisico/valorizado --}}

        @endif {{-- end count($lineas) --}}
    @endif {{-- end tipoKardex !== resumido --}}
    @endif {{-- end $buscando --}}

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (event) => { window.open(event.url, '_blank'); });
        });
    </script>

</div>
