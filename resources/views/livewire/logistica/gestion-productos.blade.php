<div>

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Crear / Editar Producto
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalProducto" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-{{ $modoEdicion ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="limpiarFormulario"></button>
                </div>

                {{-- Tabs nav --}}
                {{-- <ul class="nav nav-tabs px-4 pt-2 border-0 bg-white" role="tablist">
                    <li class="nav-item">
                        <button type="button"
                                class="nav-link {{ $tabActiva === 'base' ? 'active fw-semibold' : 'text-muted' }}"
                                wire:click="$set('tabActiva','base')">
                            <i class="fa-solid fa-cube me-1 small"></i> Datos base
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button"
                                class="nav-link {{ $tabActiva === 'sucursales' ? 'active fw-semibold' : 'text-muted' }}"
                                wire:click="$set('tabActiva','sucursales')">
                            <i class="fa-solid fa-store me-1 small"></i> Sucursales
                            @if(count($configuracion) > 0)
                                <span class="badge bg-success ms-1" style="font-size:.65rem;">{{ count($configuracion) }}</span>
                            @endif
                        </button>
                    </li>
                </ul> --}}

                <div class="modal-body px-4 pt-3 pb-2">

                    {{-- ── TAB DATOS BASE ────────────────────────────── --}}
                    <div>
                        <div class="row g-3">

                            {{-- @if($esSuperAdmin)
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Empresa <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('empresaIdModal') is-invalid @enderror"
                                        wire:model.live="empresaIdModal">
                                    <option value="0">— Seleccionar empresa —</option>
                                    @foreach($empresas as $emp)
                                        <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                    @endforeach
                                </select>
                                @error('empresaIdModal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif --}}

                            <div class="col-md-8">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       wire:model="proNombre"
                                       class="form-control @error('proNombre') is-invalid @enderror"
                                       placeholder="Ej. Alimento para perro adulto">
                                @error('proNombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Código <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       wire:model="proCodigo"
                                       class="form-control @error('proCodigo') is-invalid @enderror"
                                       placeholder="PRD-001">
                                @error('proCodigo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">Marca</label>
                                <input type="text"
                                       wire:model="proMarca"
                                       class="form-control @error('proMarca') is-invalid @enderror"
                                       placeholder="Ej. Nike, Sony, 3M..."
                                       list="listaMarcas">
                                <datalist id="listaMarcas">
                                    @foreach($marcas as $m)
                                        <option value="{{ $m }}">
                                    @endforeach
                                </datalist>
                                @error('proMarca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Familia <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" wire:model.live="idFamilia">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($familias as $fam)
                                        <option value="{{ $fam->id_fa }}">{{ $fam->fa_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Categoría <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('idCa') is-invalid @enderror"
                                        wire:model="idCa"
                                        {{ !$idFamilia ? 'disabled' : '' }}>
                                    <option value="">
                                        {{ $idFamilia ? '— Seleccionar —' : '← Primero elige una Familia' }}
                                    </option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id_ca }}">{{ $cat->ca_nombre }}</option>
                                    @endforeach
                                </select>
                                @error('idCa')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Unidad de Medida <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('idMedida') is-invalid @enderror"
                                        wire:model="idMedida">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($medidas->whereIn('id_medida', [58, 59]) as $med)
                                        <option value="{{ $med->id_medida }}">{{ $med->medida_nombre }}</option>
                                    @endforeach
                                </select>
                                @error('idMedida')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Tipo de Afectación <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('idTipoAfectacion') is-invalid @enderror"
                                        wire:model="idTipoAfectacion">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($tiposAfectacion as $ta)
                                        <option value="{{ $ta->id_tipo_afectacion }}">{{ $ta->descripcion }}</option>
                                    @endforeach
                                </select>
                                @error('idTipoAfectacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-end pb-1">
                                <div class="form-check form-switch d-flex align-items-center gap-3 ps-0 w-100">
                                    <input class="form-check-input ms-0 flex-shrink-0"
                                           type="checkbox" role="switch"
                                           id="chkImpuestoBolsa"
                                           wire:model="impuestoBolsa"
                                           style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                    <label class="form-check-label fw-semibold small text-secondary mb-0"
                                           for="chkImpuestoBolsa" style="cursor:pointer;">
                                        Impuesto a bolsa
                                    </label>
                                    <span class="badge {{ $impuestoBolsa ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                        {{ $impuestoBolsa ? 'Sí' : 'No' }}
                                    </span>
                                </div>
                            </div>

                            {{-- ── Código Interno ──────────────────── --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-secondary mb-1">
                                    Código Interno
                                </label>
                                <input type="text"
                                       wire:model="proCodigoInterno"
                                       class="form-control bg-light"
                                       readonly>
                            </div>

                            {{-- ── Separador costos ─────────────────── --}}
                            <div class="col-12">
                                <hr class="my-0">
                                <small class="text-muted fw-semibold text-uppercase" style="font-size:.7rem;">
                                    <i class="fa-solid fa-calculator me-1"></i>Costos y Precio
                                </small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-secondary mb-1">Costo Base</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model.live="proCostoBase"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-secondary mb-1">Flete</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model.live="proFlete"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-secondary mb-1">Margen Ganancia</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">%</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model.live="proMargenGanancia"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">Costo Total</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">S/</span>
                                    <p class="form-control form-control-sm bg-light mb-0 fw-bold text-primary"
                                       style="line-height:1.8;">
                                        {{ number_format($proCostoTotal, 2) }}
                                    </p>
                                </div>
                                <small class="text-muted" style="font-size:.7rem;">(Base + Flete) × (1 + Margen%)</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">Precio de Venta</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model="proPrecioVenta"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">Precio Público</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model="proPrecioPublico"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-secondary mb-1">Precio Mayorista</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" inputmode="decimal"
                                           wire:model="proPrecioMayorista"
                                           class="form-control"
                                           placeholder="0.00">
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ══ TAB SUCURSALES (comentado) ══
                    <div style="{{ $tabActiva !== 'sucursales' ? 'display:none' : '' }}; max-height:55vh; overflow-y:auto;">
                        ...
                    </div>
                    ══════════════════════════════════ --}}

                </div>{{-- /modal-body --}}

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-between">
                    <button type="button" class="btn btn-light px-4"
                            data-bs-dismiss="modal" wire:click="limpiarFormulario">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary fw-semibold px-5"
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            wire:target="guardar">
                        <span wire:loading.remove wire:target="guardar">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                        </span>
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            {{ $modoEdicion ? 'Actualizando...' : 'Guardando...' }}
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Importar Excel (solo superadmin)
    ═══════════════════════════════════════════════════════════════ --}}
    @if($esSuperAdmin)
    <div class="modal fade" id="modalImportarExcel" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:22px;height:22px;" class="me-2">
                        Importar Productos desde Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            wire:click="cerrarModalImport"></button>
                </div>

                <div class="modal-body px-4 pt-3 pb-2">

                    @if(!$importProcesado)
                        <p class="text-muted small mb-3">
                            El archivo debe ser <strong>.xlsx</strong> con una sola hoja y las siguientes columnas desde <strong>A1</strong>:
                        </p>
                        <div class="mb-3 bg-light rounded p-3" style="font-size:.78rem; overflow-x:auto;">
                            <code class="text-dark" style="white-space:nowrap;">
                                A:CODIGO &nbsp;|&nbsp; B:PRODUCTO &nbsp;|&nbsp; C:MARCA &nbsp;|&nbsp; D:UNIDAD_MEDIDA &nbsp;|&nbsp;
                                E:STOCK_INICIAL &nbsp;|&nbsp; F:STOCK &nbsp;|&nbsp; G:PRECIO_PUBLICO &nbsp;|&nbsp;
                                H:PRECIO_MAYOR &nbsp;|&nbsp; I:PRECIO_ESPECIAL &nbsp;|&nbsp; J:COSTO &nbsp;|&nbsp; K:VALOR_INVENTARIO
                            </code>
                        </div>

                        {{-- Selector de almacén / tienda --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Destino del stock <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('destinoImportKey') is-invalid @enderror"
                                    wire:model="destinoImportKey">
                                <option value="">— Selecciona el destino —</option>
                                @if(count($almacenesImport) > 0)
                                <optgroup label="Almacenes">
                                    @foreach($almacenesImport as $a)
                                        <option value="almacen_{{ $a->id_almacen }}">
                                            {{ $a->empresa_nombrecomercial }} — {{ $a->almacen_nombre }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @if(count($tiendasImport) > 0)
                                <optgroup label="Tiendas / Sedes">
                                    @foreach($tiendasImport as $t)
                                        <option value="tienda_{{ $t->id_tienda }}">
                                            {{ $t->empresa_nombrecomercial }} — {{ $t->tienda_nombre }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                            @error('destinoImportKey')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Archivo Excel <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('archivoImport') is-invalid @enderror"
                                   wire:model="archivoImport"
                                   accept=".xlsx,.xls">
                            @error('archivoImport')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div wire:loading wire:target="archivoImport"
                                 class="text-muted small mt-1">
                                <span class="spinner-border spinner-border-sm me-1"></span>Cargando archivo...
                            </div>
                        </div>
                    @else
                        {{-- Resultados --}}
                        <div class="text-center mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success mb-3"
                                 style="width:64px;height:64px;">
                                <i class="fa-solid fa-circle-check fa-2x text-white"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Importación completada</h6>
                        </div>
                        <div class="row g-2 text-center mb-2">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-bold fs-5 text-success">{{ $importResultado['creados'] ?? 0 }}</div>
                                    <small class="text-muted">Creados</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-bold fs-5 text-primary">{{ $importResultado['actualizados'] ?? 0 }}</div>
                                    <small class="text-muted">Actualizados</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="fw-bold fs-5 text-secondary">{{ $importResultado['omitidos'] ?? 0 }}</div>
                                    <small class="text-muted">Omitidos</small>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 justify-content-between">
                    <button type="button" class="btn btn-light px-4"
                            data-bs-dismiss="modal" wire:click="cerrarModalImport">
                        <i class="fa-solid fa-xmark me-1"></i> {{ $importProcesado ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    @if(!$importProcesado)
                    <button type="button" class="btn btn-success fw-semibold px-5"
                            wire:click="importarExcel"
                            wire:loading.attr="disabled"
                            wire:target="importarExcel">
                        <span wire:loading.remove wire:target="importarExcel">
                            <i class="fa-solid fa-upload me-1"></i> Importar
                        </span>
                        <span wire:loading wire:target="importarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span> Procesando...
                        </span>
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Detalle de Producto
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleProducto" wire:ignore.self tabindex="-1"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-circle-info me-2 text-primary"></i>
                        Detalle del Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    @if($detalleProd)
                    {{-- Encabezado: foto + nombre + códigos --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($detalleProd->pro_foto && file_exists(public_path($detalleProd->pro_foto)))
                            <img src="{{ asset($detalleProd->pro_foto) }}" alt=""
                                 style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid #dee2e6;">
                        @else
                            <img src="{{ asset('sin-fotografia.png') }}" alt=""
                                 style="width:80px;height:80px;object-fit:cover;border-radius:10px;opacity:.3;">
                        @endif
                        <div>
                            <h6 class="fw-bold mb-1 fs-6">{{ $detalleProd->pro_nombre }}</h6>
                            <div class="d-flex gap-2 flex-wrap mb-1">
                                <span class="badge bg-primary" style="font-size:.72rem;">
                                    <i class="fa-solid fa-barcode me-1 opacity-50"></i>{{ $detalleProd->pro_codigo }}
                                </span>
                                @if($detalleProd->pro_codigo_interno)
                                <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                                    {{ $detalleProd->pro_codigo_interno }}
                                </span>
                                @endif
                                @if($detalleProd->pro_marca)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size:.72rem;">
                                    <i class="fa-solid fa-tag me-1"></i>{{ $detalleProd->pro_marca }}
                                </span>
                                @endif
                                @if($detalleProd->impuesto_bolsa)
                                <span class="badge bg-warning text-dark" style="font-size:.72rem;">Bolsa</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                {{ $detalleProd->fa_nombre ?? '' }}
                                @if($detalleProd->fa_nombre && $detalleProd->ca_nombre) · @endif
                                {{ $detalleProd->ca_nombre ?? '' }}
                                @if($detalleProd->medida_nombre)
                                    &nbsp;·&nbsp;<span class="text-secondary">{{ $detalleProd->medida_nombre }}</span>
                                @endif
                            </small>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    @if($detalleProd->pro_descripcion)
                    <div class="mb-3 p-3 bg-light rounded-2">
                        <p class="text-muted small mb-1 fw-semibold text-uppercase" style="font-size:.7rem;">
                            <i class="fa-solid fa-align-left me-1"></i>Descripción
                        </p>
                        <p class="mb-0 small">{{ $detalleProd->pro_descripcion }}</p>
                    </div>
                    @endif

                    {{-- Costos y precios --}}
                    <p class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="fa-solid fa-calculator me-1"></i>Costos y Precio
                    </p>
                    <div class="row g-2 mb-3">
                        @php
                            $items = [
                                ['Costo Base',      'S/ ' . number_format($detalleProd->pro_costo_base ?? 0, 2),      'text-dark'],
                                ['Flete',           'S/ ' . number_format($detalleProd->pro_flete ?? 0, 2),           'text-dark'],
                                ['Margen',          'S/ ' . number_format($detalleProd->pro_margen_ganancia ?? 0, 2), 'text-dark'],
                                ['Costo Total',     'S/ ' . number_format($detalleProd->pro_costo_total ?? 0, 2),     'text-primary fw-bold'],
                                ['Precio Total',    'S/ ' . number_format($detalleProd->pro_costo_total ?? 0, 2),    'text-success fw-bold'],
                            ];
                        @endphp
                        @foreach($items as [$lbl, $val, $cls])
                        <div class="col-4 col-md">
                            <div class="border rounded-2 p-2 text-center h-100">
                                <div class="text-muted" style="font-size:.68rem;">{{ $lbl }}</div>
                                <div class="{{ $cls }}" style="font-size:.9rem;">{{ $val }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Precios por sede --}}
                    @if($detalleStockTiendas->isNotEmpty())
                    <p class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="fa-solid fa-store me-1"></i>Precios por Sede
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:.83rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Sede</th>
                                    <th class="text-end">Precio 1</th>
                                    <th class="text-end">Precio 2</th>
                                    <th class="text-end">Precio 3</th>
                                    <th class="text-center">Afectación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleStockTiendas as $st)
                                <tr>
                                    <td class="fw-semibold">{{ $st->tienda_nombre }}</td>
                                    <td class="text-end">S/ {{ number_format($st->ps_precio_uni, 2) }}</td>
                                    <td class="text-end text-muted">
                                        @if($st->ps_precio_uni_2 > 0) S/ {{ number_format($st->ps_precio_uni_2, 2) }} @else — @endif
                                    </td>
                                    <td class="text-end text-muted">
                                        @if($st->ps_precio_uni_3 > 0) S/ {{ number_format($st->ps_precio_uni_3, 2) }} @else — @endif
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $st->tipo_afectacion ?? '—' }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @endif
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Confirmar eliminar
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalEliminarProducto" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px;" class="bg-danger"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1;"></button>
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3"
                         style="width:76px;height:76px;">
                        <i class="fa-solid fa-trash fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Eliminar este producto?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem;">
                        El producto será desactivado del sistema.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold px-4"
                            wire:click="eliminar"
                            wire:loading.attr="disabled"
                            wire:target="eliminar">
                        <span wire:loading.remove wire:target="eliminar">
                            <i class="fa-solid fa-trash me-1"></i> Sí, eliminar
                        </span>
                        <span wire:loading wire:target="eliminar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Eliminando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         CARD PRINCIPAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                        Gestión de Productos
                    </h5>
                    <small class="text-muted">Administra el catálogo de productos por empresa y sucursal.</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @can('gestion_productos.exportar')
                    <button class="btn btn-sm btn-outline-success fw-semibold"
                            wire:click="exportarExcel"
                            wire:loading.attr="disabled" wire:target="exportarExcel">
                        <span wire:loading.remove wire:target="exportarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}"
                                 style="width:18px;height:18px;vertical-align:middle;" class="me-1"> Exportar
                        </span>
                        <span wire:loading wire:target="exportarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generando...
                        </span>
                    </button>
                    @endcan
                    @can('gestion_productos.crear')
                    <button class="btn btn-primary fw-semibold" wire:click="abrirModalNuevo">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Producto
                    </button>
                    @endcan
                </div>
            </div>

            {{-- Fila 1: Filtros + Buscador --}}
            <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
                <label class="text-muted small mb-0 text-nowrap">Mostrar</label>
                <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label class="text-muted small mb-0 text-nowrap">registros</label>

                <div style="width:1px;height:20px;background:#dee2e6;flex-shrink:0;"></div>

                <select wire:model.live="filtroFamilia"
                        class="form-select form-select-sm" style="width:auto;min-width:150px;">
                    <option value="0">Todas las familias</option>
                    @foreach($familias as $fam)
                        <option value="{{ $fam->id_fa }}">{{ $fam->fa_nombre }}</option>
                    @endforeach
                </select>

                @if($filtroFamilia > 0 && $categoriasFilro->isNotEmpty())
                <select wire:model.live="filtroCategoria"
                        class="form-select form-select-sm" style="width:auto;min-width:160px;">
                    <option value="0">Todas las categorías</option>
                    @foreach($categoriasFilro as $cat)
                        <option value="{{ $cat->id_ca }}">{{ $cat->ca_nombre }}</option>
                    @endforeach
                </select>
                @endif

                <select wire:model.live="filtroStock"
                        class="form-select form-select-sm" style="width:auto;min-width:140px;">
                    <option value="">Todos los stocks</option>
                    <option value="con">Con stock</option>
                    <option value="sin">Sin stock</option>
                </select>

                <div class="input-group input-group-sm ms-auto" style="max-width:280px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="buscar"
                           class="form-control border-start-0 bg-light"
                           placeholder="Nombre, código, marca...">
                    @if($buscar)
                        <button class="btn btn-outline-secondary btn-sm" type="button"
                                wire:click="$set('buscar','')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Fila 2: Acciones contextuales (visible solo con producto seleccionado) --}}
            <div class="d-flex align-items-center gap-2 mt-2 py-2 px-3 rounded-2 flex-wrap
                        {{ $productoSeleccionado ? '' : 'opacity-50' }}"
                 style="{{ $productoSeleccionado ? 'background:#eef6ff;border:1px solid #c8deff;' : 'background:#f8f9fa;border:1px dashed #dee2e6;' }}">
                <i class="fa-solid fa-{{ $productoSeleccionado ? 'circle-dot text-primary' : 'circle-dot text-muted' }}" style="font-size:.75rem;"></i>
                <span class="small fw-semibold {{ $productoSeleccionado ? 'text-primary' : 'text-muted' }}">
                    {{ $productoSeleccionado ? 'Producto seleccionado — acciones disponibles:' : 'Selecciona un producto para habilitar acciones' }}
                </span>

                <div class="ms-auto d-flex gap-2 flex-wrap">
                    <button type="button"
                            class="btn btn-sm fw-semibold {{ $productoSeleccionado ? 'btn-success' : 'btn-outline-secondary' }}"
                            wire:click="abrirModalSeries"
                            {{ $productoSeleccionado ? '' : 'disabled' }}
                            wire:loading.attr="disabled" wire:target="abrirModalSeries">
                        <span wire:loading.remove wire:target="abrirModalSeries">
                            <i class="fa-solid fa-barcode me-1"></i>Registrar Serie
                        </span>
                        <span wire:loading wire:target="abrirModalSeries">
                            <span class="spinner-border spinner-border-sm me-1"></span>Cargando...
                        </span>
                    </button>

                    <button type="button"
                            class="btn btn-sm fw-semibold {{ $productoSeleccionado ? '' : 'btn-outline-secondary' }}"
                            style="{{ $productoSeleccionado ? 'background:#0C447C;color:#fff;border:none;' : '' }}"
                            wire:click="verAdquisicionesRecientes"
                            {{ $productoSeleccionado ? '' : 'disabled' }}
                            wire:loading.attr="disabled" wire:target="verAdquisicionesRecientes">
                        <span wire:loading.remove wire:target="verAdquisicionesRecientes">
                            <i class="fa-solid fa-truck-ramp-box me-1"></i>Adquisiciones Recientes
                        </span>
                        <span wire:loading wire:target="verAdquisicionesRecientes">
                            <span class="spinner-border spinner-border-sm me-1"></span>Cargando...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
                    <i class="fa-solid fa-circle-info flex-shrink-0"></i>
                    <span>{{ session('info') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
                    <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3 text-center" style="width:60px;">
                                <span wire:click="ordenar('p.id_pro')" role="button"
                                      class="d-inline-flex align-items-center gap-1">
                                    #
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='p.id_pro' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='p.id_pro' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th style="width:58px;">Foto</th>
                            <th>
                                <span wire:click="ordenar('p.pro_nombre')" role="button"
                                      class="d-inline-flex align-items-center gap-1">
                                    Nombre / Código
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='p.pro_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='p.pro_nombre' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th>
                                <span wire:click="ordenar('c.ca_nombre')" role="button"
                                      class="d-inline-flex align-items-center gap-1">
                                    Categoría
                                    <i class="fa-solid fa-sort{{ $ordenColumna==='c.ca_nombre' ? ($ordenDireccion==='asc'?'-up':'-down') : '' }}
                                       {{ $ordenColumna!=='c.ca_nombre' ? ' opacity-25' : '' }} small"></i>
                                </span>
                            </th>
                            <th>Medida</th>
                            {{-- @if($esSuperAdmin)
                            <th>Empresa</th>
                            @endif --}}
                            <th class="text-center" style="width:100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $index => $prod)
                        <tr style="{{ $productoSeleccionado === $prod->id_pro ? 'background:#EBF3FF;' : '' }}">
                            <td class="ps-2 text-center" style="width:60px;">
                                <label style="cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
                                    <input type="radio"
                                           wire:model.live="productoSeleccionado"
                                           value="{{ $prod->id_pro }}"
                                           style="accent-color:#0d6efd;width:15px;height:15px;cursor:pointer;">
                                    <span class="text-muted small fw-semibold">{{ $productos->firstItem() + $index }}</span>
                                </label>
                            </td>
                            <td>
                                @if($prod->pro_foto && file_exists(public_path($prod->pro_foto)))
                                    <img src="{{ asset($prod->pro_foto) }}" alt=""
                                         style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
                                @else
                                    <img src="{{ asset('sin-fotografia.png') }}" alt=""
                                         style="width:44px;height:44px;object-fit:cover;border-radius:6px;opacity:.3;">
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $prod->pro_nombre }}</span>
                                @if($prod->pro_marca)
                                    <span class="badge bg-light text-dark border ms-1" style="font-size:.65rem;">{{ $prod->pro_marca }}</span>
                                @endif
                                <br>
                                <small class="text-primary fw-semibold">{{ $prod->pro_codigo }}</small>
                                @if($prod->pro_codigo_interno)
                                    <span class="text-muted"> · </span>
                                    <small class="text-muted">{{ $prod->pro_codigo_interno }}</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted d-block" style="font-size:.75rem;">{{ $prod->fa_nombre }}</small>
                                {{ $prod->ca_nombre }}
                            </td>
                            <td><small>{{ $prod->medida_nombre }}</small></td>
                            {{-- @if($esSuperAdmin)
                            <td><small>{{ $prod->empresa_nombrecomercial ?? '—' }}</small></td>
                            @endif --}}
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button class="btn btn-sm btn-outline-primary"
                                            wire:click="verDetalle({{ $prod->id_pro }})"
                                            title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('gestion_productos.actualizar')
                                    <button class="btn btn-sm btn-warning"
                                            wire:click="abrirModalEditar({{ $prod->id_pro }})"
                                            title="Editar">
                                        <i class="fa-solid fa-pencil text-white"></i>
                                    </button>
                                    @endcan
                                    @can('gestion_productos.cambiar_estado')
                                    <button class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminar({{ $prod->id_pro }})"
                                            title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                @if($buscar)
                                    <i class="fa-solid fa-boxes-stacked fa-2x mb-2 d-block opacity-25"></i>
                                    No se encontraron productos con <strong>"{{ $buscar }}"</strong>.
                                @else
                                    <i class="fa-solid fa-boxes-stacked fa-2x mb-2 d-block opacity-25"></i>
                                    No hay productos registrados todavía.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($productos->count())
                <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }}
                        de {{ $productos->total() }} registros
                    </small>
                    {{ $productos->links(data: ['scrollTo' => false]) }}
                </div>
            @endif

        </div>
    </div>

    <div wire:loading wire:target="buscar, porPagina, filtroEmpresa, filtroFamilia, filtroCategoria, filtroStock, ordenar, abrirModalNuevo, abrirModalEditar, guardar, eliminar, importarExcel, exportarExcel, verDetalle">
        <x-loader />
    </div>

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Registrar / Ver Series
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalSeries" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom py-2 px-4" style="background:#f0f8f2;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="color:#198754;">
                            <i class="fa-solid fa-barcode me-2"></i>{{ $productoSeleccionadoNombre ?: 'Series del Producto' }}
                        </h5>
                        <div class="text-muted small mt-1">
                            <span class="fw-semibold text-success">{{ collect($seriesProducto)->where('estado', 1)->count() }} disponible(s)</span>
                            <span class="text-muted"> · {{ count($seriesProducto) }} en total</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">

                    {{-- ── Formulario nueva serie ── --}}
                    <div class="card border-0 mb-3" style="background:#f8fff9;border:1px solid #d1e7dd !important;">
                        <div class="card-body py-2 px-3">
                            <p class="fw-semibold small mb-2" style="color:#198754;">
                                <i class="fa-solid fa-plus-circle me-1"></i>Registrar nueva serie
                            </p>
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-5">
                                    <label class="form-label small text-muted mb-1">Número de serie <span class="text-danger">*</span></label>
                                    <input type="text"
                                           wire:model="nuevaSerie"
                                           wire:keydown.enter="registrarSerie"
                                           class="form-control form-control-sm @if($errorSerie) is-invalid @endif"
                                           placeholder="Ej: SN-001, IMEI, etc."
                                           maxlength="100">
                                    @if($errorSerie)
                                        <div class="invalid-feedback">{{ $errorSerie }}</div>
                                    @endif
                                </div>
                                <div class="col-sm-5">
                                    <label class="form-label small text-muted mb-1">Observación (opcional)</label>
                                    <input type="text"
                                           wire:model="nuevaSerieObservacion"
                                           wire:keydown.enter="registrarSerie"
                                           class="form-control form-control-sm"
                                           placeholder="Color, lote, etc."
                                           maxlength="255">
                                </div>
                                <div class="col-sm-2">
                                    <button type="button"
                                            class="btn btn-success btn-sm w-100 fw-semibold"
                                            wire:click="registrarSerie"
                                            wire:loading.attr="disabled" wire:target="registrarSerie">
                                        <span wire:loading.remove wire:target="registrarSerie">
                                            <i class="fa-solid fa-plus"></i> Agregar
                                        </span>
                                        <span wire:loading wire:target="registrarSerie">
                                            <span class="spinner-border spinner-border-sm"></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            @if($successSerie)
                            <div class="alert alert-success py-1 px-2 mt-2 mb-0" style="font-size:.8rem;">
                                <i class="fa-solid fa-circle-check me-1"></i>{{ $successSerie }}
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- ── Listado de series ── --}}
                    @if(empty($seriesProducto))
                    <div class="text-muted text-center py-4" style="font-size:.85rem;">
                        <i class="fa-solid fa-barcode fa-2x d-block mb-2 opacity-20"></i>
                        No hay series registradas para este producto todavía.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                            <thead>
                                <tr class="encabezado_tabla_color">
                                    <th class="ps-3">N° Serie</th>
                                    <th class="text-center">Estado</th>
                                    <th>Observación</th>
                                    <th>Venta vinculada</th>
                                    <th>Registrado por</th>
                                    <th>Fecha registro</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($seriesProducto as $s)
                                @php
                                    $estadoBadge = match((int)$s['estado']) {
                                        1 => ['label' => 'Disponible', 'class' => 'bg-success'],
                                        2 => ['label' => 'Vendido',    'class' => 'bg-primary'],
                                        0 => ['label' => 'Baja',       'class' => 'bg-secondary'],
                                        default => ['label' => '-', 'class' => 'bg-secondary'],
                                    };
                                    $editando = $editandoSerieId === $s['id_producto_serie'];
                                @endphp
                                <tr style="{{ $editando ? 'background:#fffbeb;' : '' }}">

                                    {{-- Número de serie --}}
                                    <td class="ps-3">
                                        @if($editando)
                                            <input type="text"
                                                   wire:model="editandoSerieNumero"
                                                   wire:keydown.enter="guardarEdicionSerie"
                                                   wire:keydown.escape="cancelarEdicionSerie"
                                                   class="form-control form-control-sm @if($errorEdicionSerie) is-invalid @endif"
                                                   style="min-width:130px;"
                                                   maxlength="100">
                                            @if($errorEdicionSerie)
                                                <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $errorEdicionSerie }}</div>
                                            @endif
                                        @else
                                            <span class="fw-semibold">{{ $s['numero_serie'] }}</span>
                                        @endif
                                    </td>

                                    {{-- Estado --}}
                                    <td class="text-center">
                                        <span class="badge {{ $estadoBadge['class'] }}" style="font-size:.72rem;">
                                            {{ $estadoBadge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Observación --}}
                                    <td>
                                        @if($editando)
                                            <input type="text"
                                                   wire:model="editandoSerieObservacion"
                                                   wire:keydown.enter="guardarEdicionSerie"
                                                   wire:keydown.escape="cancelarEdicionSerie"
                                                   class="form-control form-control-sm"
                                                   style="min-width:150px;"
                                                   placeholder="Opcional"
                                                   maxlength="255">
                                        @else
                                            <span class="text-muted">{{ $s['observacion'] ?? '—' }}</span>
                                        @endif
                                    </td>

                                    {{-- Venta vinculada --}}
                                    <td>
                                        @if($s['venta_serie'])
                                            <span class="text-primary fw-semibold" style="font-size:.78rem;">
                                                {{ $s['venta_serie'] }}-{{ $s['venta_correlativo'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-muted" style="font-size:.75rem;">{{ $s['nombre_users'] ?? '—' }}</td>
                                    <td class="text-muted text-nowrap" style="font-size:.75rem;">
                                        {{ $s['created_at'] ? \Carbon\Carbon::parse($s['created_at'])->format('d/m/Y H:i') : '—' }}
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="text-center">
                                        @if($editando)
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success btn-sm"
                                                        title="Guardar"
                                                        wire:click="guardarEdicionSerie"
                                                        wire:loading.attr="disabled" wire:target="guardarEdicionSerie">
                                                    <i class="fa-solid fa-check" style="font-size:.75rem;"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        title="Cancelar"
                                                        wire:click="cancelarEdicionSerie">
                                                    <i class="fa-solid fa-xmark" style="font-size:.75rem;"></i>
                                                </button>
                                            </div>
                                        @elseif((int)$s['estado'] === 1)
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button"
                                                        class="btn btn-warning btn-sm"
                                                        title="Editar"
                                                        wire:click="iniciarEdicionSerie({{ $s['id_producto_serie'] }})">
                                                    <i class="fa-solid fa-pencil" style="font-size:.75rem;"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm"
                                                        title="Dar de baja"
                                                        wire:click="darDeBajaSerie({{ $s['id_producto_serie'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="darDeBajaSerie({{ $s['id_producto_serie'] }})"
                                                        onclick="return confirm('¿Dar de baja la serie {{ $s['numero_serie'] }}?')">
                                                    <i class="fa-solid fa-ban" style="font-size:.75rem;"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>

                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         MODAL — Adquisiciones Recientes
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAdquisiciones" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom py-2 px-4" style="background:#f0f4f8;">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="color:#0C447C;">
                            <i class="fa-solid fa-truck-ramp-box me-2"></i>Adquisiciones Recientes
                        </h5>
                        @if($productoSeleccionadoNombre)
                        <div class="text-muted small mt-1">
                            <i class="fa-solid fa-box me-1"></i>{{ $productoSeleccionadoNombre }}
                        </div>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">

                    {{-- ── Órdenes de Compra ── --}}
                    <h6 class="fw-bold mb-2" style="color:#1a1a1a;font-size:.85rem;">
                        <i class="fa-solid fa-file-invoice me-1 text-primary"></i>
                        Órdenes de Compra / Facturas
                        <span class="badge bg-primary ms-1" style="font-size:.7rem;">{{ count($adquisicionesCompras) }}</span>
                    </h6>

                    @if(empty($adquisicionesCompras))
                    <div class="text-muted text-center py-3" style="font-size:.85rem;">
                        <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                        No hay órdenes de compra registradas para este producto.
                    </div>
                    @else
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.8rem;">
                            <thead>
                                <tr class="encabezado_tabla_color text-center">
                                    <th class="ps-3 text-start">N° Compra</th>
                                    <th class="text-start">Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Tipo Doc.</th>
                                    <th>N° Documento</th>
                                    <th class="text-end">Cant.</th>
                                    <th class="text-end">P. Compra</th>
                                    <th class="text-end">Subtotal</th>
                                    <th>Transportista</th>
                                    <th>Guía Remitente</th>
                                    <th>Condición</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adquisicionesCompras as $oc)
                                @php
                                    $estadoClase = match($oc['orden_compra_estado'] ?? '') {
                                        'recibido'   => 'bg-success',
                                        'en_transito'=> 'bg-warning text-dark',
                                        'anulado'    => 'bg-danger',
                                        default      => 'bg-secondary',
                                    };
                                    $estadoLabel = match($oc['orden_compra_estado'] ?? '') {
                                        'recibido'    => 'Recibido',
                                        'en_transito' => 'En tránsito',
                                        'anulado'     => 'Anulado',
                                        'pendiente'   => 'Pendiente',
                                        default       => ucfirst($oc['orden_compra_estado'] ?? '-'),
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-semibold text-primary">{{ $oc['orden_compra_numero'] ?? '-' }}</td>
                                    <td>{{ $oc['proveedores_nombre'] ?? '-' }}</td>
                                    <td class="text-center text-nowrap">{{ $oc['orden_compra_fecha'] ? \Carbon\Carbon::parse($oc['orden_compra_fecha'])->format('d/m/Y') : '-' }}</td>
                                    <td class="text-center">{{ $oc['orden_compra_tipo_doc'] ?? '-' }}</td>
                                    <td class="text-center">{{ $oc['orden_compra_numero_doc'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($oc['detalle_compra_cantidad'], 2) }}</td>
                                    <td class="text-end">S/ {{ number_format($oc['detalle_compra_precio_compra'], 2) }}</td>
                                    <td class="text-end fw-semibold">S/ {{ number_format($oc['detalle_compra_total_pedido'], 2) }}</td>
                                    <td>
                                        @if($oc['orden_compra_guia_transportista'])
                                            <span class="badge bg-light text-dark border" style="font-size:.75rem;">
                                                <i class="fa-solid fa-truck me-1 text-muted"></i>{{ $oc['orden_compra_guia_transportista'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($oc['orden_compra_guia_remitente'])
                                            <span class="badge bg-light text-dark border" style="font-size:.75rem;">{{ $oc['orden_compra_guia_remitente'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $oc['condicion_pago'] === 'contado' ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size:.7rem;">
                                            {{ ucfirst($oc['condicion_pago'] ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $estadoClase }}" style="font-size:.7rem;">{{ $estadoLabel }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- ── Notas de Crédito / Débito ── --}}
                    <h6 class="fw-bold mb-2" style="color:#1a1a1a;font-size:.85rem;">
                        <i class="fa-solid fa-file-circle-minus me-1 text-warning"></i>
                        Notas de Crédito / Débito vinculadas
                        <span class="badge bg-secondary ms-1" style="font-size:.7rem;">{{ count($adquisicionesNotas) }}</span>
                    </h6>

                    @if(empty($adquisicionesNotas))
                    <div class="text-muted text-center py-3" style="font-size:.85rem;">
                        <i class="fa-solid fa-circle-check fa-lg d-block mb-1 text-success opacity-50"></i>
                        Sin notas de crédito ni débito asociadas.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.8rem;">
                            <thead>
                                <tr class="encabezado_tabla_color text-center">
                                    <th class="ps-3 text-start">N° Nota</th>
                                    <th>Tipo</th>
                                    <th class="text-start">Proveedor</th>
                                    <th>Fecha</th>
                                    <th class="text-start">N° Doc.</th>
                                    <th class="text-start">Motivo</th>
                                    <th class="text-end">Total</th>
                                    <th>Estado</th>
                                    <th class="text-start">Orden vinculada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adquisicionesNotas as $nota)
                                @php
                                    $ocVinculada = collect($adquisicionesCompras)->firstWhere('id_orden_compra', $nota['id_orden_compra']);
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $nota['nota_numero'] ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $nota['tipo_nota'] === 'NC' ? 'bg-warning text-dark' : 'bg-danger' }}" style="font-size:.72rem;">
                                            {{ $nota['tipo_nota'] === 'NC' ? 'Nota Crédito' : 'Nota Débito' }}
                                        </span>
                                    </td>
                                    <td>{{ $nota['proveedores_nombre'] ?? '-' }}</td>
                                    <td class="text-center text-nowrap">{{ $nota['nota_fecha'] ? \Carbon\Carbon::parse($nota['nota_fecha'])->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $nota['nota_numero_doc'] ?? '-' }}</td>
                                    <td style="max-width:200px;" class="text-truncate">{{ $nota['nota_motivo'] ?? '-' }}</td>
                                    <td class="text-end fw-semibold">S/ {{ number_format($nota['nota_total'], 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $nota['nota_estado'] === 'aprobado' ? 'bg-success' : 'bg-secondary' }}" style="font-size:.7rem;">
                                            {{ ucfirst($nota['nota_estado']) }}
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:.75rem;">
                                        {{ $ocVinculada ? ($ocVinculada['orden_compra_numero'] ?? '-') : '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- ── Ventas ── --}}
                    <hr class="my-3">
                    <h6 class="fw-bold mb-2" style="color:#1a1a1a;font-size:.85rem;">
                        <i class="fa-solid fa-cash-register me-1 text-success"></i>
                        Ventas registradas
                        <span class="badge bg-success ms-1" style="font-size:.7rem;">{{ count($adquisicionesVentas) }}</span>
                    </h6>

                    @if(empty($adquisicionesVentas))
                    <div class="text-muted text-center py-3" style="font-size:.85rem;">
                        <i class="fa-solid fa-inbox fa-lg d-block mb-1 opacity-25"></i>
                        No hay ventas registradas para este producto.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.8rem;">
                            <thead>
                                <tr class="encabezado_tabla_color text-center">
                                    <th class="ps-3 text-start">Comprobante</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th class="text-start">Cliente</th>
                                    <th>Doc. Cliente</th>
                                    <th class="text-end">Cant.</th>
                                    <th class="text-end">P. Unitario</th>
                                    <th class="text-end">Importe</th>
                                    <th class="text-start">Vendedor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adquisicionesVentas as $vt)
                                @php
                                    $tipoLabel = match($vt['venta_tipo'] ?? '') {
                                        '01' => 'Factura',
                                        '03' => 'Boleta',
                                        '07' => 'N. Crédito',
                                        '08' => 'N. Débito',
                                        default => $vt['venta_tipo'] ?? '-',
                                    };
                                    $tipoBadge = match($vt['venta_tipo'] ?? '') {
                                        '01' => 'bg-primary',
                                        '03' => 'bg-success',
                                        '07' => 'bg-warning text-dark',
                                        '08' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-semibold text-primary text-nowrap">
                                        {{ $vt['venta_serie'] }}-{{ $vt['venta_correlativo'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $tipoBadge }}" style="font-size:.7rem;">{{ $tipoLabel }}</span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        {{ $vt['venta_fecha'] ? \Carbon\Carbon::parse($vt['venta_fecha'])->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>{{ $vt['cliente_nombre'] }}</td>
                                    <td class="text-center">{{ $vt['cliente_doc'] ?: '-' }}</td>
                                    <td class="text-end">{{ number_format($vt['venta_detalle_cantidad'], 2) }}</td>
                                    <td class="text-end">S/ {{ number_format($vt['venta_detalle_precio_unitario'], 2) }}</td>
                                    <td class="text-end fw-semibold">S/ {{ number_format($vt['venta_detalle_importe_total'], 2) }}</td>
                                    <td class="text-muted" style="font-size:.75rem;">{{ $vt['nombre_users'] ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>

                <div class="modal-footer border-top py-2 d-flex justify-content-between align-items-center">
                    <a href="{{ route('logistica.adquisiciones_recientes_excel') }}?id_pro={{ $productoSeleccionado ?? '' }}"
                       class="btn btn-sm btn-outline-success fw-semibold"
                       target="_blank">
                        <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:16px;height:16px;vertical-align:middle;" class="me-1">
                        Descargar Excel
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

</div>

@script
<script>
    $wire.on('abrirModal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto')).show();
    });

    $wire.on('cerrarModal', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalProducto'));
        if (m) m.hide();
    });

    $wire.on('abrirModalEliminar', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEliminarProducto')).show();
    });

    $wire.on('cerrarModalEliminar', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalEliminarProducto'));
        if (m) m.hide();
    });

    $wire.on('abrirModalDetalle', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleProducto')).show();
    });

    $wire.on('abrirModalImport', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalImportarExcel')).show();
    });

    $wire.on('abrirModalAdquisiciones', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAdquisiciones')).show();
    });

    $wire.on('abrirModalSeries', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSeries')).show();
    });

    $wire.on('cerrarModalImport', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalImportarExcel'));
        if (m) m.hide();
    });
</script>
@endscript
