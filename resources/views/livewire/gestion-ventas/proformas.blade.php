<div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Confirmar Aprobación
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAprobarProforma" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px" class="bg-success"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1"></button>
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success mb-3"
                         style="width:76px;height:76px">
                        <i class="fa-solid fa-circle-check fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Aprobar esta proforma?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem">
                        La proforma cambiará a estado <strong>Aprobada</strong>.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm fw-semibold px-4"
                            wire:click="aprobar" wire:loading.attr="disabled" wire:target="aprobar">
                        <span wire:loading.remove wire:target="aprobar">
                            <i class="fa-solid fa-circle-check me-1"></i> Sí, aprobar
                        </span>
                        <span wire:loading wire:target="aprobar">
                            <span class="spinner-border spinner-border-sm me-1"></span> Aprobando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Confirmar Anulación
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalAnularProforma" wire:ignore.self tabindex="-1"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px" class="bg-danger"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1"></button>
                <div class="modal-body text-center px-4 pt-4 pb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3"
                         style="width:76px;height:76px">
                        <i class="fa-solid fa-ban fa-2x text-white"></i>
                    </div>
                    <h6 class="fw-bold mb-1">¿Anular esta proforma?</h6>
                    <p class="text-muted mb-0" style="font-size:.85rem">
                        La proforma quedará como <strong>Anulada</strong> y no podrá reactivarse.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold px-4"
                            wire:click="anular" wire:loading.attr="disabled" wire:target="anular">
                        <span wire:loading.remove wire:target="anular">
                            <i class="fa-solid fa-ban me-1"></i> Sí, anular
                        </span>
                        <span wire:loading wire:target="anular">
                            <span class="spinner-border spinner-border-sm me-1"></span> Anulando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         VISTA: HISTORIAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'historial')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                        Proformas
                        @if($sucursalNombre)
                            <span class="badge fw-normal ms-1" style="background:#eef1ff;color:#0b1892;font-size:.72rem;">
                                <i class="fa-solid fa-store me-1"></i>{{ $sucursalNombre }}
                            </span>
                        @endif
                    </h5>
                    <small class="text-muted">Historial de proformas registradas.</small>
                </div>
                @can('gestion_proformas.crear')
                <button class="btn btn-success fw-semibold" wire:click="nuevaProforma">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Proforma
                </button>
                @endcan
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3 flex-wrap">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div class="col-auto">
                    <select wire:model.live="empresaSeleccionada"
                            class="form-select form-select-sm" style="min-width:170px">
                        <option value="0">Seleccionar Empresa</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial ?? $emp->empresa_razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                @if(count((array)$sucursalesDisponibles) > 0)
                <div class="col-auto">
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="min-width:160px">
                        <option value="0">— Sucursal —</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Desde</span>
                        <input type="date" class="form-control" wire:model.live="filtroDesde">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Hasta</span>
                        <input type="date" class="form-control" wire:model.live="filtroHasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Alertas --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mx-3 mt-3 mb-0">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
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

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:50px">#</th>
                            <th>N° Proforma</th>
                            <th>Cliente</th>
                            <th>Empresa / Sede</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                            <th class="text-center" style="width:100px">Estado</th>
                            <th class="text-center" style="width:110px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($esSuperAdmin && !$empresaSeleccionada)
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-building fa-2x d-block mb-2 opacity-25"></i>
                                Seleccione una empresa para visualizar las proformas.
                            </td>
                        </tr>
                        @else
                        @forelse($proformas as $idx => $pro)
                        <tr>
                            <td class="ps-3 text-muted small fw-semibold">{{ $proformas->firstItem() + $idx }}</td>
                            <td class="fw-semibold small">{{ $pro->profo_serie }}-{{ str_pad($pro->profo_correlativo,6,'0',STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-semibold">{{ $pro->cliente_nombre }}</div>
                                <small class="text-muted">{{ $pro->cliente_numero }}</small>
                            </td>
                            <td>
                                @if($pro->empresa_nombre)
                                    <div class="small fw-semibold" style="color:#0b1892;">
                                        <i class="fa-solid fa-building me-1" style="font-size:.65rem;"></i>{{ $pro->empresa_nombre }}
                                    </div>
                                @endif
                                @if($pro->tienda_nombre)
                                    <span class="badge bg-secondary bg-opacity-75 fw-normal">
                                        <i class="fa-solid fa-store me-1" style="font-size:.6rem;"></i>{{ $pro->tienda_nombre }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($pro->profo_fecha_emision)->format('d/m/Y') }}</small></td>
                            <td class="text-end fw-semibold">
                                S/ {{ number_format($pro->total ?? 0, 2) }}
                            </td>
                            <td class="text-center">
                                @if($pro->profo_acti_estado == 0)
                                    <span class="badge bg-warning text-white">Pendiente</span>
                                @elseif($pro->profo_acti_estado == 1)
                                    <span class="badge bg-success">Aprobada</span>
                                @elseif($pro->profo_acti_estado == 2)
                                    <span class="badge bg-primary">Despachada</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('gestion_proformas.exportar')
                                <a href="{{ route('Gestionventas.imprimir_proforma') }}?data={{ $pro->id_profo }}"
                                   class="btn btn-sm btn-danger m-1" title="Imprimir PDF" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                @endcan
                                @can('gestion_proformas.aprobar')
                                @if($pro->profo_acti_estado == 0)
                                <button class="btn btn-sm btn-success m-1"
                                        wire:click="confirmarAprobar({{ $pro->id_profo }})"
                                        title="Aprobar proforma">
                                    <i class="fa-solid fa-circle-check"></i>
                                </button>
                                @endif
                                @endcan
                                @can('gestion_proformas.cambiar_estado')
                                @if($pro->profo_acti_estado == 0)
                                <button class="btn btn-sm btn-danger m-1"
                                        wire:click="confirmarAnular({{ $pro->id_profo }})"
                                        title="Anular proforma">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-file-invoice fa-2x d-block mb-2 opacity-25"></i>
                                No se encontraron proformas en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            @if($proformas->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $proformas->firstItem() }}–{{ $proformas->lastItem() }}
                    de {{ $proformas->total() }} proformas
                </small>
                {{ $proformas->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         VISTA: NUEVA PROFORMA
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'nueva')

    {{-- Alertas --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
        <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
        <span>{{ session('error') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light border" wire:click="volverHistorial">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </button>
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-file-invoice me-2 text-primary"></i> Nueva Proforma
            </h5>
        </div>
        {{-- Badge empresa / sede --}}
        <div class="d-flex align-items-center gap-2">
            @if($empresaNombre && !$esSuperAdmin)
            <span class="badge fw-normal py-2 px-3" style="background:#f0f4ff;color:#0b1892;font-size:.78rem;">
                <i class="fa-solid fa-building me-1"></i>{{ $empresaNombre }}
            </span>
            @endif
            @if($sucursalNombre)
            <span class="badge fw-normal py-2 px-3" style="background:#eef1ff;color:#0b1892;font-size:.78rem;">
                <i class="fa-solid fa-store me-1"></i>{{ $sucursalNombre }}
            </span>
            @endif
        </div>
    </div>

    {{-- Selección empresa / sucursal — solo superadmin --}}
    @if($esSuperAdmin)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small mb-1">Empresa</label>
                    <select class="form-select form-select-sm" wire:model.live="empresaSeleccionada">
                        <option value="0">— Seleccione empresa —</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial ?? $emp->empresa_razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small mb-1">Sede <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model.live="sucursalSeleccionada"
                            {{ !$empresaSeleccionada ? 'disabled' : '' }}>
                        <option value="0">— Seleccione sede —</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!$sucursalSeleccionada)
                <div class="col-auto">
                    <small class="text-warning fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Seleccione una sede para buscar productos.
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @elseif(!$sucursalSeleccionada)
    <div class="alert alert-warning py-2 small mb-3">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        No tiene una sede asignada. Contacte al administrador.
    </div>
    @endif

    <div class="row g-3">

        {{-- Información del cliente --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-user me-2 text-primary"></i>Información del Cliente
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Tipo Documento <span class="text-danger">*</span></label>
                            <select class="form-select @error('idTipoDocumento') is-invalid @enderror"
                                    wire:model.live="idTipoDocumento">
                                @foreach($tiposDocumento as $td)
                                    <option value="{{ $td->id_tipo_documento }}">{{ $td->tipo_documento_identidad_abr }}</option>
                                @endforeach
                            </select>
                            @error('idTipoDocumento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">N° Documento <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text"
                                       class="form-control @error('numDocumento') is-invalid @enderror"
                                       wire:model.live.debounce.600ms="numDocumento"
                                       placeholder="DNI (8 dígitos) o RUC (11 dígitos)"
                                       autocomplete="off">
                                <div wire:loading wire:target="numDocumento"
                                     class="position-absolute top-50 end-0 translate-middle-y pe-3">
                                    <span class="spinner-border spinner-border-sm text-primary"></span>
                                </div>
                            </div>
                            @error('numDocumento') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        @if($mensajeConsulta)
                        <div class="col-12">
                            <div class="alert py-2 small mb-0
                                @if($tipoMensajeConsulta === 'success') alert-success
                                @elseif($tipoMensajeConsulta === 'warning') alert-warning
                                @else alert-danger
                                @endif">
                                <i class="fa-solid fa-circle-info me-1"></i>{{ $mensajeConsulta }}
                            </div>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Razón Social / Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('razonSocial') is-invalid @enderror"
                                   wire:model="razonSocial" placeholder="Nombre o razón social">
                            @error('razonSocial') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Teléfono</label>
                            <input type="text" class="form-control" wire:model="telefono" placeholder="Teléfono">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Dirección</label>
                            <textarea class="form-control" rows="2" wire:model="direccion" placeholder="Dirección"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Información adicional --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-circle-info me-2 text-primary"></i>Información Adicional
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Forma de Pago <span class="text-danger">*</span></label>
                            <select class="form-select @error('formaPago') is-invalid @enderror"
                                    wire:model="formaPago">
                                <option value="1">CONTADO</option>
                                <option value="2">CRÉDITO</option>
                            </select>
                            @error('formaPago') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Lugar de Entrega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('lugarEntrega') is-invalid @enderror"
                                   wire:model="lugarEntrega">
                            @error('lugarEntrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Observaciones</label>
                            <textarea class="form-control" rows="3" wire:model="observaciones"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de productos --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Lista de Productos
                    </h6>
                </div>
                <div class="card-body p-4">

                    {{-- Buscador --}}
                    <style>
                        tr.resultado-proforma:focus {
                            background-color: #0b1892 !important;
                            color: #fff;
                            outline: none;
                        }
                        tr.resultado-proforma:focus .badge {
                            filter: brightness(1.3);
                        }
                    </style>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar Producto
                            @if($sucursalSeleccionada)
                            <span class="text-muted fw-normal ms-2" style="font-size:.72rem;">
                                ↓ navegar · Enter seleccionar · Esc volver al buscador
                            </span>
                            @endif
                        </label>

                        @if($sucursalSeleccionada)
                        <input type="text"
                               id="input-buscar-proforma"
                               class="form-control"
                               wire:model.live.debounce.300ms="buscarProducto"
                               placeholder="Escriba nombre o código del producto..."
                               autocomplete="off">

                        @if(count($resultadosBusqueda) > 0)
                        <div class="table-responsive mt-2 border rounded shadow-sm" id="tabla-resultados-proforma">
                            <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-end">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resultadosBusqueda as $prod)
                                    <tr class="resultado-proforma"
                                        tabindex="0"
                                        style="cursor:pointer;outline:none;"
                                        wire:click="agregarProducto({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', {{ $prod->precio_venta ?? 0 }}, '{{ addslashes($prod->pro_codigo ?? '') }}')">
                                        <td class="fw-semibold">{{ $prod->pro_nombre }}</td>
                                        <td>
                                            @if($prod->pro_codigo)
                                                <span class="badge bg-light text-secondary border fw-normal" style="font-size:.7rem;">{{ $prod->pro_codigo }}</span>
                                            @endif
                                            @if($prod->pro_codigo_interno)
                                                <span class="badge bg-light text-secondary border fw-normal ms-1" style="font-size:.7rem;">{{ $prod->pro_codigo_interno }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.7rem;">{{ $prod->medida ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary fw-normal">{{ number_format($prod->ps_stock ?? 0, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary fw-normal">S/ {{ number_format($prod->precio_venta ?? 0, 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @elseif(strlen($buscarProducto) >= 2)
                        <div class="mt-2 text-muted small">
                            <i class="fa-solid fa-circle-info me-1"></i>Sin resultados para "{{ $buscarProducto }}".
                        </div>
                        @endif

                        @else
                        <div class="form-control bg-light text-muted d-flex align-items-center gap-2" style="cursor:not-allowed">
                            <i class="fa-solid fa-lock opacity-50"></i>
                            <span class="small">
                                {{ $esSuperAdmin
                                    ? 'Seleccione una sede primero para buscar productos.'
                                    : 'No tiene una sede asignada. Contacte al administrador.' }}
                            </span>
                        </div>
                        @endif
                    </div>

                    @error('items')
                    <div class="alert alert-warning py-2 small mb-2">{{ $message }}</div>
                    @enderror

                    {{-- Tabla de items --}}
                    @if(count($items) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:140px">Precio Unit. (S/)</th>
                                    <th style="width:110px">Cantidad</th>
                                    <th class="text-end" style="width:110px">Subtotal</th>
                                    <th style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item['nombre'] }}</div>
                                        @if(!empty($item['codigo']))
                                        <div class="text-muted" style="font-size:.72rem;">{{ $item['codigo'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                               class="form-control form-control-sm"
                                               wire:model.live="items.{{ $i }}.precio">
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1"
                                               class="form-control form-control-sm @error('items.'.$i.'.cantidad') is-invalid @enderror"
                                               wire:model.live="items.{{ $i }}.cantidad">
                                        @error('items.'.$i.'.cantidad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-end fw-semibold">
                                        S/ {{ number_format(($item['precio'] ?? 0) * ($item['cantidad'] ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm"
                                                wire:click="quitarItem({{ $i }})">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-primary">
                                        S/ {{ number_format(collect($items)->sum(fn($i) => ($i['precio'] ?? 0) * ($i['cantidad'] ?? 0)), 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                </div>
                <div class="card-footer bg-white border-top text-end">
                    <button type="button" class="btn btn-primary"
                            wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="guardar"></i>
                        Guardar Proforma
                    </button>
                </div>
            </div>
        </div>

    </div>
    @endif

    <div wire:loading wire:target="guardar, aprobar, nuevaProforma, volverHistorial">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModalAprobar', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAprobarProforma')).show();
    });
    $wire.on('cerrarModalAprobar', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalAprobarProforma'));
        if (m) m.hide();
    });

    document.getElementById('modalAprobarProforma')
        .addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });

    $wire.on('abrirModalAnular', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAnularProforma')).show();
    });
    $wire.on('cerrarModalAnular', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalAnularProforma'));
        if (m) m.hide();
    });

    document.getElementById('modalAnularProforma')
        .addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });

    // ── Autofocus al abrir Nueva Proforma ────────────────────
    const enfocarBuscadorProforma = () => {
        $nextTick(() => {
            const input = document.getElementById('input-buscar-proforma');
            if (input) input.focus();
        });
    };

    $wire.on('vistaNueva',      enfocarBuscadorProforma);
    $wire.on('enfocarBuscador', enfocarBuscadorProforma);

    // ── Navegación por teclado en tabla de productos ──────────
    document.addEventListener('keydown', (e) => {
        const inputProd = document.getElementById('input-buscar-proforma');
        if (!inputProd) return;

        const wrapper = document.getElementById('tabla-resultados-proforma');
        const rows    = wrapper ? Array.from(wrapper.querySelectorAll('tr.resultado-proforma')) : [];

        if (document.activeElement === inputProd) {
            if (e.key === 'ArrowDown' && rows.length) {
                e.preventDefault();
                rows[0].focus();
            }
            return;
        }

        const idx = rows.indexOf(document.activeElement);
        if (idx === -1) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (idx < rows.length - 1) rows[idx + 1].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx > 0) rows[idx - 1].focus();
            else         inputProd.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            rows[idx].click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            inputProd.focus();
        }
    }, true);
</script>
@endscript
