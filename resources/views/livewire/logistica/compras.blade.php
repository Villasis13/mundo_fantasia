<div>
<style>
    .form-control[readonly], .form-select[readonly] {
        background-color: #eef0f2 !important;
        color: #495057;
        cursor: default;
    }
</style>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-xmark flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Modal Enviar (pendiente → en_transito) --}}
    <div class="modal fade" id="modalEnviar" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="fa-solid fa-truck me-2 text-warning"></i>Confirmar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Desea marcar esta compra como <strong>en tránsito</strong>?</p>
                    <p class="text-muted small">El stock se actualizará cuando se recepcione la mercadería.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-semibold" wire:click="enviarOrden">
                        <i class="fa-solid fa-truck me-1"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Recepcionar (en_transito → recibido + stock) --}}
    <div class="modal fade" id="modalRecibir" wire:ignore.self tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-box-open me-2 text-success"></i>Recepcionar Compra
                    </h5>
                    <button type="button" class="btn-close"
                            wire:loading.attr="disabled" wire:target="recibirOrden"
                            data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Ingresa la cantidad <strong>exacta que llegó</strong>. Si es menor a lo pedido se registra como merma; si es mayor se acredita el exceso al almacén.
                    </div>

                    @error('cantidades')
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $message }}
                    </div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="encabezado_tabla_color">
                                    <th class="ps-3">#</th>
                                    <th>Producto</th>
                                    <th class="text-center" style="width:120px;">Pedido</th>
                                    <th class="text-center" style="width:180px;">Recibido</th>
                                    <th class="text-center" style="width:110px;">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detallesRecibir as $i => $det)
                                @php
                                    $pedido   = (float) $det['cantidad_pedida'];
                                    $recibido = (float) ($cantidadesRecibidas[$det['id_detalle_compra']] ?? $pedido);
                                    $diff     = $recibido - $pedido;
                                @endphp
                                <tr class="{{ $diff < 0 ? 'table-danger' : ($diff > 0 ? 'table-warning' : '') }}">
                                    <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold d-block">{{ $det['pro_nombre'] }}</span>
                                        <small class="text-muted">{{ $det['pro_codigo'] }}</small>
                                    </td>
                                    <td class="text-center fw-semibold text-muted">
                                        {{ number_format($pedido, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                               wire:model.live="cantidadesRecibidas.{{ $det['id_detalle_compra'] }}"
                                               class="form-control form-control-sm text-center"
                                               min="0" step="0.01"
                                               style="width:120px; margin:auto;">
                                    </td>
                                    <td class="text-center fw-semibold small">
                                        @if($diff == 0)
                                            <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                        @elseif($diff < 0)
                                            <span class="text-danger">{{ number_format($diff, 2) }}</span>
                                        @else
                                            <span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> +{{ number_format($diff, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">Rojo</span> Faltante / merma &nbsp;
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-2">Amarillo</span> Exceso recibido &nbsp;
                            <span class="badge bg-light text-dark border me-2">Sin color</span> Cantidad exacta
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            wire:loading.attr="disabled" wire:target="recibirOrden"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="recibirOrden"
                            wire:loading.attr="disabled"
                            wire:target="recibirOrden">
                        <span wire:loading.remove wire:target="recibirOrden">
                            <i class="fa-solid fa-box-open me-1"></i> Confirmar Recepción
                        </span>
                        <span wire:loading wire:target="recibirOrden">
                            <span class="spinner-border spinner-border-sm me-1"></span> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Nuevo Proveedor --}}
    <div class="modal fade" id="modalNuevoProveedor" wire:ignore.self tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold mb-0" style="font-size:16px;">
                        <i class="fa-solid fa-truck me-2 text-primary"></i>Nuevo Proveedor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Nombre / Razón Social <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('npNombre') is-invalid @enderror"
                                   wire:model="npNombre" placeholder="Ej. DISTRIBUIDORA SAC">
                            @error('npNombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-5">
                            <label class="form-label fw-semibold small text-secondary mb-1">Tipo Documento</label>
                            <select class="form-select" wire:model="npTipoDoc">
                                <option value="4">RUC</option>
                                <option value="2">DNI</option>
                                <option value="5">Pasaporte</option>
                                <option value="1">Otro</option>
                            </select>
                        </div>

                        <div class="col-7">
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                N° Documento <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('npNumDoc') is-invalid @enderror"
                                       wire:model="npNumDoc" placeholder="Ej. 20512345678" maxlength="20">
                                <button type="button" class="btn btn-outline-secondary px-2"
                                        wire:click="npBuscarDoc"
                                        wire:loading.attr="disabled" wire:target="npBuscarDoc"
                                        title="Consultar RUC/DNI">
                                    <span wire:loading wire:target="npBuscarDoc">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                    <span wire:loading.remove wire:target="npBuscarDoc">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                </button>
                            </div>
                            @error('npNumDoc') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @if($npDocMensaje)
                                <div class="mt-1 small {{ $npDocMensajeTipo === 'success' ? 'text-success' : 'text-danger' }}">
                                    <i class="fa-solid {{ $npDocMensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' }} me-1"></i>{{ $npDocMensaje }}
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary mb-1">Dirección</label>
                            <input type="text" class="form-control" wire:model="npDireccion"
                                   placeholder="Dirección (opcional)">
                        </div>

                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Teléfono</label>
                            <input type="text" class="form-control" wire:model="npTelefono"
                                   placeholder="(opcional)">
                        </div>

                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary mb-1">Correo</label>
                            <input type="email" class="form-control" wire:model="npCorreo"
                                   placeholder="(opcional)">
                        </div>
                    </div>

                </div>
                <div class="modal-footer px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold px-4"
                            wire:click="guardarNuevoProveedor"
                            wire:loading.attr="disabled" wire:target="guardarNuevoProveedor">
                        <span wire:loading wire:target="guardarNuevoProveedor">
                            <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                        </span>
                        <span wire:loading.remove wire:target="guardarNuevoProveedor">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Guardar
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
                        <i class="fa-solid fa-cart-flatbed me-2 text-primary"></i>
                        Historial de Compras
                    </h5>
                    <small class="text-muted">Compras registradas.</small>
                </div>
                <div class="d-flex gap-2">
                    @can('historial_compras.exportar')
                    <button class="btn btn-sm btn-outline-danger fw-semibold"
                            wire:click="exportarPdf"
                            wire:loading.attr="disabled" wire:target="exportarPdf">
                        <span wire:loading.remove wire:target="exportarPdf">
                            <i class="fa-solid fa-file-pdf me-1"></i> PDF
                        </span>
                        <span wire:loading wire:target="exportarPdf">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                    </button>
                    <button class="btn btn-sm btn-outline-success fw-semibold"
                            wire:click="exportarExcel"
                            wire:loading.attr="disabled" wire:target="exportarExcel">
                        <span wire:loading.remove wire:target="exportarExcel">
                            <img src="{{ asset('iconos_svg/microsoft-excel.svg') }}" style="width:16px;height:16px;vertical-align:middle;" class="me-1"> Excel
                        </span>
                        <span wire:loading wire:target="exportarExcel">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                    </button>
                    @endcan
                    @can('registro_compras.crear')
                    <button class="btn btn-primary fw-semibold" wire:click="nuevaOrden">
                        <i class="fa-solid fa-plus me-1"></i> Nueva Compra
                    </button>
                    @endcan
                </div>
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3">

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Mostrar</label>
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>


                @if($proveedoresHistorial->isNotEmpty())
                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Proveedor</label>
                    <select wire:model.live="filtroProveedor"
                            class="form-select form-select-sm" style="min-width:170px;">
                        <option value="0">Todos</option>
                        @foreach($proveedoresHistorial as $prov)
                            <option value="{{ $prov->id_proveedores }}">{{ $prov->proveedores_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Estado</label>
                    <select wire:model.live="filtroEstado" class="form-select form-select-sm" style="min-width:140px;">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_transito">En Tránsito</option>
                        <option value="recibido">Recibido</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </div>

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Condición</label>
                    <select wire:model.live="filtroCondicion" class="form-select form-select-sm" style="min-width:130px;">
                        <option value="">Todas</option>
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>

                <div class="col-auto">
                    <label class="form-label fw-semibold small text-secondary mb-1">Recepción</label>
                    <select wire:model.live="filtroDiferencia" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="">Todos</option>
                        <option value="con_diferencia">Con diferencias</option>
                        <option value="sin_nota">Sin nota registrada</option>
                    </select>
                </div>

            </div>
        </div>

        @if($ordenes->contains(fn($o) => ($o->items_con_diferencia ?? 0) > 0))
        <div class="px-3 pt-2 pb-1 d-flex gap-3 flex-wrap">
            <small>
                <span class="d-inline-block me-1" style="width:14px;height:14px;background:#fff8e1;border:1px solid #ffe082;border-radius:3px;vertical-align:middle;"></span>
                <span class="text-muted">Recepcionada con cantidades diferentes a lo pedido</span>
            </small>
            <small>
                <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>
                <span class="text-muted">Sin nota de crédito/débito registrada</span>
            </small>
        </div>
        @endif
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:50px;">#</th>
                            <th>N° Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Detalles</th>
                            <th class="text-center">Condición</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $index => $orden)
                        @php
                            $estadoInfo = match($orden->orden_compra_estado) {
                                'pendiente'   => ['bg-secondary',       'Pendiente'],
                                'en_transito' => ['bg-warning text-white','En Tránsito'],
                                'recibido'    => ['bg-success',          'Recibido'],
                                'anulado'     => ['bg-danger',           'Anulado'],
                                default       => ['bg-light text-dark',  $orden->orden_compra_estado],
                            };
                            $conDiferencia = ($orden->items_con_diferencia ?? 0) > 0;
                            $sinNota       = $conDiferencia && ($orden->notas_nc + $orden->notas_db) == 0;
                        @endphp
                        <tr style="{{ $conDiferencia ? 'background-color:#fff8e1;' : '' }}">
                            <td class="ps-3 text-muted small fw-semibold">
                                {{ $ordenes->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="fw-semibold small">{{ $orden->orden_compra_numero }}</span>
                                @if($sinNota)
                                <i class="fa-solid fa-triangle-exclamation text-danger ms-1"
                                   title="Diferencias de cantidad sin nota de crédito/débito registrada"
                                   style="font-size:.75rem;"></i>
                                @endif
                                @if($orden->notas_nc > 0)
                                <span class="badge bg-info bg-opacity-90 ms-1" style="font-size:.65rem;"
                                      title="{{ $orden->notas_nc }} Nota(s) de Crédito">
                                    NC {{ $orden->notas_nc > 1 ? '×'.$orden->notas_nc : '' }}
                                </span>
                                @endif
                                @if($orden->notas_db > 0)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;"
                                      title="{{ $orden->notas_db }} Nota(s) de Débito">
                                    DB {{ $orden->notas_db > 1 ? '×'.$orden->notas_db : '' }}
                                </span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $orden->proveedores_nombre }}</span>
                            </td>
                            <td>
                                <small>{{ \Carbon\Carbon::parse($orden->orden_compra_fecha)->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                @if($orden->orden_compra_tipo_doc || $orden->orden_compra_numero_doc)
                                    <small class="text-muted d-block lh-1">
                                        {{ $orden->orden_compra_tipo_doc }}
                                        {{ $orden->orden_compra_numero_doc }}
                                    </small>
                                @endif
                                <span class="fw-semibold small text-primary">
                                    S/ {{ number_format($orden->orden_compra_total ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if(($orden->condicion_pago ?? 'contado') === 'contado')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal">
                                        <i class="fa-solid fa-money-bill-wave me-1" style="font-size:.65rem;"></i>Contado
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-normal">
                                        <i class="fa-solid fa-calendar-days me-1" style="font-size:.65rem;"></i>Crédito
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $estadoInfo[0] }}">{{ $estadoInfo[1] }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('logistica.ordenCompraDetalle') }}?ordenCompra={{ $orden->id_orden_compra }}"
                                   class="btn btn-sm btn-info me-1" title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @can('historial_compras.exportar')
                                <a href="{{ route('logistica.ingreso_compra_pdf') }}?ordenCompra={{ $orden->id_orden_compra }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-danger me-1"
                                   title="Ver PDF del ingreso">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                @endcan
                                @can('historial_compras.cambiar_estado')
                                @if($orden->orden_compra_estado === 'pendiente')
                                <button class="btn btn-sm btn-warning me-1"
                                        wire:click="confirmarEnviar({{ $orden->id_orden_compra }})"
                                        title="Marcar en tránsito">
                                    <i class="fa-solid fa-truck"></i>
                                </button>
                                @endif
                                @if($orden->orden_compra_estado === 'en_transito')
                                <button class="btn btn-sm btn-success me-1"
                                        wire:click="confirmarRecibir({{ $orden->id_orden_compra }})"
                                        title="Recepcionar compra">
                                    <i class="fa-solid fa-box-open"></i>
                                </button>
                                @endif
                                @if(!in_array($orden->orden_compra_estado, ['anulado','recibido']))
                                <button class="btn btn-sm btn-danger"
                                        wire:click="confirmarAnular({{ $orden->id_orden_compra }})"
                                        title="Anular compra">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                                @endif
                                @endcan
                                @if($orden->orden_compra_estado === 'recibido' && $orden->notas_nc == 0)
                                @can('notas_compra.crear')
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary ms-1"
                                        title="Nota de Crédito / Débito"
                                        wire:click="abrirModalNcDb({{ $orden->id_orden_compra }}, 'NC')"
                                        wire:loading.attr="disabled"
                                        wire:target="abrirModalNcDb({{ $orden->id_orden_compra }}, 'NC')">
                                    <span wire:loading.remove wire:target="abrirModalNcDb({{ $orden->id_orden_compra }}, 'NC')">
                                        <i class="fa-solid fa-file-invoice"></i> NC/DB
                                    </span>
                                    <span wire:loading wire:target="abrirModalNcDb({{ $orden->id_orden_compra }}, 'NC')">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-cart-flatbed fa-2x mb-2 d-block opacity-25"></i>
                                    No hay compras en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ordenes->count())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $ordenes->firstItem() }}–{{ $ordenes->lastItem() }}
                    de {{ $ordenes->total() }} compras
                </small>
                {{ $ordenes->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         VISTA: NUEVA ORDEN
    ═══════════════════════════════════════════════════════════ --}}
    @if($vista === 'nueva')
    @php $sym = $this->moneda === 'USD' ? '$' : ($this->moneda === 'EUR' ? '€' : 'S/'); @endphp

    {{-- ── Barra superior ── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Registro de Ingresos - Compras
            </h5>
        </div>
        <button class="btn btn-primary fw-semibold px-4"
                wire:click="guardarOrden" wire:loading.attr="disabled" wire:target="guardarOrden">
            <span wire:loading.remove wire:target="guardarOrden">
                <i class="fa-solid fa-floppy-disk me-1"></i> Registrar Compra
            </span>
            <span wire:loading wire:target="guardarOrden">
                <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
            </span>
        </button>
    </div>

    {{-- ══ BLOQUE 1 — Datos del Comprobante (ancho completo) ══ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body pb-2">
            <h6 class="fw-bold text-muted small text-uppercase mb-3">
                <i class="fa-solid fa-file-lines me-1"></i> Datos del Comprobante
            </h6>
            {{-- FILA 1: Orden N° · Emisión · Almacenamiento · Comprobante · Tipo · Estado --}}
            <div class="row g-2 mb-2">
                <div class="col-auto" style="min-width:100px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">Orden N°</label>
                    <input type="text" class="form-control form-control-sm fw-semibold"
                           value="{{ $proximoNumero }}" readonly data-compra-nav>
                </div>

                <div class="col-auto" style="min-width:140px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">Emisión</label>
                    <input type="date" class="form-control form-control-sm" wire:model="fechaEmision" data-compra-nav>
                </div>

                <div class="col-auto" style="min-width:155px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">Almacenamiento</label>
                    <input type="date" class="form-control form-control-sm" wire:model="fechaAlmacenamiento" data-compra-nav>
                </div>

                <div class="col">
                    <label class="form-label fw-semibold small text-secondary mb-1">Comprobante</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" style="max-width:115px;" wire:model="tipoDoc" data-compra-nav>
                            <option value="">— Tipo —</option>
                            <option value="BOLETA">Boleta</option>
                            <option value="FACTURA">Factura</option>
                        </select>
                        <input type="text" class="form-control" style="max-width:80px;"
                               wire:model="docSerie" placeholder="Serie" maxlength="10" data-compra-nav>
                        <input type="text" class="form-control"
                               wire:model="docCorrelativo" placeholder="Correlativo" maxlength="20" data-compra-nav>
                    </div>
                </div>

                <div class="col-auto" style="min-width:120px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">Tipo</label>
                    <select class="form-select form-select-sm" wire:model.live="condicionPago" data-compra-nav>
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <div class="col-auto" style="min-width:140px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">Estado</label>
                    <select class="form-select form-select-sm" wire:model="estadoOrden" data-compra-nav>
                        <option value="en_transito">Tránsito</option>
                        <option value="recibido">Recepcionado</option>
                    </select>
                </div>
            </div>

            {{-- FILA 2: Proveedor · Razón Social · RUC · Observaciones --}}
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary mb-1">
                        Proveedor <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <select class="form-select @error('idProveedor') is-invalid @enderror"
                                wire:model.live="idProveedor" data-compra-nav>
                            <option value="0">— Seleccionar —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id_proveedores }}">{{ $prov->proveedores_nombre }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary px-2"
                                data-bs-toggle="modal" data-bs-target="#modalNuevoProveedor"
                                title="Agregar proveedor">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    @error('idProveedor') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary mb-1">Razón Social</label>
                    <input type="text" class="form-control form-control-sm text-muted"
                           value="{{ $proveedorRazonSocial ?: '—' }}" readonly data-compra-nav>
                </div>

                <div class="col-md-1" style="min-width:130px;">
                    <label class="form-label fw-semibold small text-secondary mb-1">RUC</label>
                    <input type="text" class="form-control form-control-sm text-muted"
                           value="{{ $proveedorRuc ?: '—' }}" readonly data-compra-nav>
                </div>

                <div class="col">
                    <label class="form-label fw-semibold small text-secondary mb-1">Observaciones</label>
                    <textarea class="form-control form-control-sm" wire:model="observacion"
                              rows="1" placeholder="Notas adicionales..." style="resize:none;" data-compra-nav></textarea>
                </div>
            </div>

            {{-- FILA 3: Transportista 1 --}}
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary mb-1">Transportista</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-secondary mb-1">RUC Transportista</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary mb-1">N° Fact.</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-secondary mb-1">Fecha</label>
                    <input type="date" class="form-control form-control-sm" readonly data-compra-nav>
                </div>
            </div>

            {{-- FILA 4: Transportista 2 --}}
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-secondary mb-1">Transportista</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-secondary mb-1">RUC Transportista</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary mb-1">N° Fact.</label>
                    <input type="text" class="form-control form-control-sm" readonly placeholder="—" data-compra-nav>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-secondary mb-1">Fecha</label>
                    <input type="date" class="form-control form-control-sm" readonly data-compra-nav>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ BLOQUE 2 — Productos (ancho completo) ══ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold text-muted small text-uppercase mb-3">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Productos
            </h6>

            {{-- Buscador --}}
            <div class="position-relative mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" class="form-control"
                           wire:model.live.debounce.300ms="buscarProducto"
                           wire:focus="cargarSugerencias"
                           wire:blur.debounce.150ms="cerrarSugerencias"
                           placeholder="Haz clic para ver productos o escribe para filtrar...">
                </div>
                @if(!empty($resultadosBusqueda))
                <div class="position-absolute w-100 shadow-lg border rounded-2 bg-white"
                     style="z-index:999;top:100%;max-height:260px;overflow-y:auto;">
                    @foreach($resultadosBusqueda as $res)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                         style="cursor:pointer;" wire:click="agregarProducto({{ $res->id_pro }})" wire:key="res-{{ $res->id_pro }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $res->pro_nombre }}</span>
                            <small class="text-muted">{{ $res->pro_codigo }}</small>
                        </div>
                        <div class="text-end">
                            <small class="text-success fw-semibold d-block">{{ $sym }} {{ number_format($res->ps_precio_uni ?? 0, 2) }}</small>
                            @if($res->id_medida == 59)
                                <small class="text-primary">Servicio</small>
                            @else
                                <small class="text-muted">Stock: {{ $res->ps_stock }}</small>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @if($buscarProducto !== '' && empty($resultadosBusqueda))
                <div class="position-absolute w-100 shadow border rounded-2 bg-white px-3 py-2" style="z-index:999;top:100%;">
                    <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>No se encontraron productos.</small>
                </div>
                @endif
            </div>

            @error('items')
            <div class="alert alert-warning py-2 small mb-3">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $message }}
            </div>
            @enderror

            @if(!empty($items))
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.82rem;min-width:880px;">
                    <thead>
                        <tr class="table-light">
                            <th style="width:32px;">#</th>
                            <th style="width:100px;">Código</th>
                            <th>Producto</th>
                            <th style="width:110px;">Presentación</th>
                            <th class="text-center" style="width:88px;">Cantidad</th>
                            <th class="text-center" style="width:110px;">Cant × Unid<br><small class="fw-normal text-muted" style="font-size:.72rem;">(Almacén)</small></th>
                            <th style="width:130px;">Costo Unit. S/</th>
                            <th class="text-end" style="width:85px;">Total S/</th>
                            <th class="text-end" style="width:72px;">Flete S/</th>
                            <th class="text-end" style="width:72px;">IGV S/ {{ $this->igvPorcentaje > 0 ? $this->igvPorcentaje.'%' : '' }}</th>
                            <th style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        @php
                            $igvItem   = round(($item['total'] ?? 0) * (float) $this->igvPorcentaje / 100, 2);
                            $fleteItem = $subtotal > 0
                                ? round(($item['total'] / $subtotal) * (float) $this->flete, 2)
                                : 0;
                        @endphp
                        <tr wire:key="item-{{ $i }}">
                            <td class="text-muted small ps-1">{{ $i + 1 }}</td>
                            <td class="text-muted small">{{ $item['codigo'] }}</td>
                            <td>
                                <span class="fw-semibold d-block lh-sm">{{ $item['nombre'] }}</span>
                            </td>
                            <td>
                                @if(!empty($item['presentacion']))
                                    <span class="badge bg-primary text-white px-2 py-1" style="font-size:.78rem;">
                                        {{ $item['presentacion'] }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <input type="text" inputmode="decimal"
                                       class="form-control form-control-sm text-center @error("items.{$i}.cantidad") is-invalid @enderror"
                                       wire:model.live="items.{{ $i }}.cantidad">
                                @error("items.{$i}.cantidad") <div class="invalid-feedback" style="font-size:.7rem;">{{ $message }}</div> @enderror
                            </td>
                            <td class="text-center">
                                @if(!empty($item['cantidad_x_unidad']))
                                    <span class="fw-semibold small">{{ $item['cantidad_x_unidad'] }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text px-1">{{ $sym }}</span>
                                    <input type="text" inputmode="decimal"
                                           class="form-control @error("items.{$i}.precio_compra") is-invalid @enderror"
                                           wire:model.live="items.{{ $i }}.precio_compra">
                                </div>
                                @error("items.{$i}.precio_compra") <div class="invalid-feedback" style="font-size:.7rem;">{{ $message }}</div> @enderror
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($item['total'], 2) }}</td>
                            <td class="text-end text-muted small">0.00</td>
                            <td class="text-end text-muted small">0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                        wire:click="quitarItem({{ $i }})" title="Quitar">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted py-4 border border-dashed rounded-2">
                <i class="fa-solid fa-boxes-stacked fa-2x opacity-25 d-block mb-2"></i>
                <small>Busca y agrega productos a la compra.</small>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ BLOQUE 3 — Totales ══ --}}
    <div class="row mb-3">
        <div class="col-md-6 ms-auto">
    <div class="card border-0 shadow-sm">
        <div class="card-body">

                {{-- Columna de totales --}}
                <div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chkRevertirIgv"
                               @checked($revertirDesagregarIgv)
                               wire:click="toggleRevertirIgv">
                        <label class="form-check-label small fw-semibold" for="chkRevertirIgv">
                            Revertir Desagregar IGV de Pro
                        </label>
                    </div>
                    <div class="rounded-2 border p-3" style="background:#f8f9fb;">
                        @php $subtotalConIgv = round($subtotalNeto + $igvMonto, 2); @endphp

                        {{-- Subtotal S/ --}}
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-muted small">Subtotal S/</span>
                            <input type="text" class="form-control form-control-sm text-end"
                                   value="{{ number_format($subtotal, 2) }}" readonly style="max-width:130px;">
                        </div>

                        {{-- Dscto S/ --}}
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-muted small">Dscto S/</span>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm text-end"
                                   wire:model.live="descuentoImporte" placeholder="0.00" style="max-width:130px;">
                        </div>

                        {{-- SubTot S/ (subtotal − descuento) --}}
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom pb-2 mb-1">
                            <span class="text-muted small fw-semibold">SubTot S/</span>
                            <input type="text" class="form-control form-control-sm text-end fw-semibold"
                                   value="{{ number_format($subtotalNeto, 2) }}" readonly style="max-width:130px;">
                        </div>

                        {{-- IGV % + monto S/ --}}
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-muted small">IGV %</span>
                            <div class="d-flex gap-1 align-items-center">
                                <div class="input-group input-group-sm" style="width:82px;">
                                    <input type="text" inputmode="decimal" class="form-control text-center"
                                           wire:model.live="igvPorcentaje" placeholder="0">
                                    <span class="input-group-text bg-white px-1">%</span>
                                </div>
                                <input type="text" class="form-control form-control-sm text-end"
                                       value="{{ number_format($igvMonto, 2) }}" readonly style="width:100px;">
                            </div>
                        </div>

                        {{-- SubTot S/ (con IGV) --}}
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom pb-2 mb-1">
                            <span class="text-muted small fw-semibold">SubTot S/</span>
                            <input type="text" class="form-control form-control-sm text-end fw-semibold"
                                   value="{{ number_format($subtotalConIgv, 2) }}" readonly style="max-width:130px;">
                        </div>

                        {{-- Percep IGV % + monto S/ --}}
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-muted small">Percep IGV %</span>
                            <div class="d-flex gap-1 align-items-center">
                                <div class="input-group input-group-sm" style="width:82px;">
                                    <input type="text" inputmode="decimal" class="form-control text-center"
                                           wire:model.live="percepcionPorcentaje" placeholder="0">
                                    <span class="input-group-text bg-white px-1">%</span>
                                </div>
                                <input type="text" class="form-control form-control-sm text-end"
                                       value="{{ number_format($percepcionMonto, 2) }}" readonly style="width:100px;">
                            </div>
                        </div>

                        {{-- Flete S/ --}}
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-muted small">Flete S/</span>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm text-end"
                                   wire:model.live="flete" placeholder="0.00" style="max-width:130px;">
                        </div>

                        {{-- Total S/ --}}
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-1">
                            <span class="fw-bold">
                                Total S/
                                <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary-subtle fw-semibold ms-1" style="font-size:.7rem;">{{ $this->moneda }}</span>
                            </span>
                            <input type="text" class="form-control form-control-sm text-end fw-bold text-primary"
                                   value="{{ number_format($totalOrden, 2) }}" readonly
                                   style="max-width:130px;font-size:1.1rem;">
                        </div>

                    </div>
                </div>

        </div>
    </div>
        </div>
    </div>

    @endif

    {{-- Modal confirmación anulación --}}
    <div class="modal fade" id="modalAnularOrden" tabindex="-1"
         aria-hidden="true" wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden position-relative">
                <div style="height:5px;" class="bg-danger"></div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                        data-bs-dismiss="modal" style="z-index:1;"></button>
                <div class="modal-body px-4 pt-4 pb-3">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger mb-3"
                             style="width:76px;height:76px;">
                            <i class="fa-solid fa-ban fa-2x text-white"></i>
                        </div>
                        <h6 class="fw-bold mb-1">¿Anular esta compra?</h6>
                        <p class="text-muted mb-0" style="font-size:.85rem;">
                            Si la orden fue recepcionada, se revertirá el stock en el almacén correspondiente.
                        </p>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            Motivo de anulación <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('motivoAnulacion') is-invalid @enderror"
                                  wire:model="motivoAnulacion"
                                  rows="3"
                                  placeholder="Describe el motivo de la anulación..."></textarea>
                        @error('motivoAnulacion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold px-4"
                            wire:click="anularOrden"
                            wire:loading.attr="disabled"
                            wire:target="anularOrden">
                        <span wire:loading.remove wire:target="anularOrden">
                            <i class="fa-solid fa-ban me-1"></i> Sí, anular
                        </span>
                        <span wire:loading wire:target="anularOrden">
                            <span class="spinner-border spinner-border-sm me-1"></span> Anulando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal NC / DB ════════════════════════════════════════ --}}
    <div class="modal fade" id="modalNcDb" tabindex="-1" wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold mb-0">
                        @if($ncDbTipo === 'NC')
                            <i class="fa-solid fa-file-circle-minus text-info me-2"></i>Nueva Nota de Crédito
                        @else
                            <i class="fa-solid fa-file-circle-plus text-warning me-2"></i>Nueva Nota de Débito
                        @endif
                    </h5>
                    <button type="button" class="btn-close" wire:click="$dispatch('cerrarModalNcDb')"></button>
                </div>
                <div class="modal-body">

                    {{-- Tipo selector --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Tipo</label>
                            <select wire:model.live="ncDbTipo" class="form-select form-select-sm">
                                <option value="NC">Nota de Crédito (NC)</option>
                                <option value="DB">Nota de Débito (DB)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">N° Doc. Proveedor</label>
                            <input type="text" wire:model="ncDbNumeroDoc"
                                   class="form-control form-control-sm" placeholder="Ej: F001-00001234">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div>
                                <div class="form-check form-switch">
                                    <input type="checkbox" wire:model.live="ncDbAfectaStock"
                                           class="form-check-input" id="ncDbStockCheck">
                                    <label class="form-check-label small fw-semibold" for="ncDbStockCheck">
                                        Afecta stock
                                    </label>
                                </div>
                                @if($ncDbAfectaStock)
                                <select wire:model="ncDbIdAlmacen" class="form-select form-select-sm mt-1">
                                    <option value="0">— Almacén —</option>
                                    @foreach($almacenes as $alm)
                                    <option value="{{ $alm->id_almacen }}">
                                        {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Motivo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            Motivo <span class="text-danger">*</span>
                        </label>
                        <textarea wire:model="ncDbMotivo"
                                  class="form-control form-control-sm @error('ncDbMotivo') is-invalid @enderror"
                                  rows="2" placeholder="Describe el motivo de la nota..."></textarea>
                        @error('ncDbMotivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tabla de ítems --}}
                    @error('ncDbItems') <div class="alert alert-warning py-2 small mb-2">{{ $message }}</div> @enderror
                    <div class="table-responsive">
                        <table class="table table-sm align-middle border mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:130px;" class="text-end">Precio Unit.</th>
                                    <th style="width:110px;">Cantidad</th>
                                    <th style="width:110px;" class="text-end">Total</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ncDbItems as $idx => $item)
                                <tr wire:key="ncdb-{{ $idx }}">
                                    <td class="small">{{ $item['nombre'] }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">S/</span>
                                            <input type="number"
                                                   wire:model.live="ncDbItems.{{ $idx }}.precio"
                                                   wire:change="calcularTotalNcDb"
                                                   class="form-control text-end" min="0" step="0.01">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number"
                                               wire:model.live="ncDbItems.{{ $idx }}.cantidad"
                                               wire:change="calcularTotalNcDb"
                                               class="form-control form-control-sm text-center" min="0.01" step="0.01">
                                    </td>
                                    <td class="text-end fw-semibold small">
                                        S/ {{ number_format($item['total'], 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                                wire:click="quitarItemNcDb({{ $idx }})">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @if(empty($ncDbItems))
                                <tr>
                                    <td colspan="5" class="text-center text-muted small py-3">Sin ítems.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if($ncDbTipo === 'NC')
                    <div class="alert alert-info py-2 mt-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        La NC <strong>reducirá</strong> el saldo en Cuentas por Pagar de esta compra al aprobarla.
                    </div>
                    @else
                    <div class="alert alert-warning py-2 mt-3 small mb-0">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        La DB <strong>aumentará</strong> el saldo en Cuentas por Pagar de esta compra al aprobarla.
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-top d-flex justify-content-between align-items-center">
                    <span class="fw-bold">
                        Total: <span class="text-primary fs-6">S/ {{ number_format($ncDbTotal, 2) }}</span>
                    </span>
                    @error('ncDbTotal') <small class="text-danger">{{ $message }}</small> @enderror
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm"
                                wire:click="$dispatch('cerrarModalNcDb')">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm fw-semibold"
                                wire:click="guardarNcDb"
                                wire:loading.attr="disabled" wire:target="guardarNcDb">
                            <span wire:loading.remove wire:target="guardarNcDb">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Nota
                            </span>
                            <span wire:loading wire:target="guardarNcDb">
                                <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal SUNAT — facturas disponibles ═══════════════════ --}}
    <div class="modal fade" id="modalSunat" tabindex="-1" aria-hidden="true"
         wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                        Facturas disponibles en SUNAT
                    </h5>
                    <button type="button" class="btn-close"
                            wire:click="cerrarModalSunat"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0">
                    @if(count($sunatFacturas) > 0)
                    @php
                        $numeroBuscado = trim($numeroDoc);
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="encabezado_tabla_color">
                                <tr>
                                    <th class="ps-3">Serie - N°</th>
                                    <th>Tipo</th>
                                    <th>Emisor (RUC / Razón Social)</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sunatFacturas as $idx => $f)
                                @php
                                    $docNum  = ($f['serie'] ?? '') . '-' . str_pad($f['numero'] ?? '', 8, '0', STR_PAD_LEFT);
                                    $esBuscado = $numeroBuscado && strcasecmp($docNum, $numeroBuscado) === 0;
                                @endphp
                                <tr class="{{ $esBuscado ? 'table-success' : '' }}">
                                    <td class="ps-3 fw-semibold">
                                        {{ $docNum }}
                                        @if($esBuscado)
                                            <span class="badge bg-success ms-1" style="font-size:.65rem;">
                                                <i class="fa-solid fa-star me-1"></i>Buscado
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $tc = $f['tipoComprobante'] ?? ''; @endphp
                                        <span class="badge {{ $tc === '01' ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ $tc === '01' ? 'Factura' : ($tc === '03' ? 'Boleta' : $tc) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold lh-1">{{ $f['razonSocial'] ?? '—' }}</div>
                                        <small class="text-muted">{{ $f['numRuc'] ?? '' }}</small>
                                    </td>
                                    <td class="text-muted">
                                        {{ isset($f['fechaEmision']) ? \Carbon\Carbon::parse($f['fechaEmision'])->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-end fw-bold">
                                        S/ {{ number_format((float)($f['mtoTotal'] ?? 0), 2) }}
                                    </td>
                                    <td class="text-center">
                                        @php $estado = strtoupper($f['estado'] ?? ''); @endphp
                                        <span class="badge {{ $estado === 'ACEPTADO' ? 'bg-success' : ($estado === 'ANULADO' ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ ucfirst(strtolower($estado ?: 'N/D')) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm {{ $esBuscado ? 'btn-success' : 'btn-outline-primary' }} px-3"
                                                wire:click="aplicarFacturaSunat({{ $idx }})"
                                                data-bs-dismiss="modal">
                                            <i class="fa-solid fa-arrow-down me-1"></i> Aplicar
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-box-open fa-2x d-block mb-2 opacity-25"></i>
                        No se encontraron comprobantes recientes.
                    </div>
                    @endif
                </div>

                <div class="modal-footer border-top pt-2 pb-3 justify-content-end">
                    <button type="button" class="btn btn-light px-4"
                            data-bs-dismiss="modal"
                            wire:click="cerrarModalSunat">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══ Modal Selector de Presentación (Compra) ══════════════ --}}
    <div class="modal fade" id="modalPresentacionCompra" tabindex="-1"
         aria-hidden="true" wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div style="height:4px;" class="bg-primary"></div>
                <div class="modal-header border-0 pb-1 pt-3 px-4">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>Seleccionar Presentación
                    </h6>
                    <button type="button" class="btn-close"
                            wire:click="$dispatch('cerrarModalPresentacionCompra')"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    @if(!empty($productoPendienteData))
                    <p class="text-muted small mb-3">
                        <strong>{{ $productoPendienteData['nombre'] ?? '' }}</strong>
                        — elige la presentación con la que ingresa esta compra:
                    </p>
                    @endif
                    <div class="d-flex flex-column gap-2">
                        @foreach($presentacionesPendientes as $pres)
                        <button type="button"
                                class="btn btn-outline-primary text-start px-3 py-2 d-flex justify-content-between align-items-center"
                                wire:click="seleccionarPresentacionCompra({{ $pres['id_pres'] }})"
                                wire:key="pres-compra-{{ $pres['id_pres'] }}">
                            <span class="fw-semibold">{{ $pres['pres_nombre'] }}</span>
                            @if($pres['pres_factor'] > 0)
                            <span class="badge bg-primary text-white" style="font-size:.75rem;">
                                × {{ $pres['pres_factor'] }} uds.
                            </span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="nuevaOrden, volverHistorial, guardarOrden, agregarProducto, anularOrden, recibirOrden, condicionPago, seleccionarPresentacionCompra, toggleRevertirIgv">
        <x-loader />
    </div>

    @script
    <script>
        $wire.on('abrirModalAnular', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAnularOrden')).show();
        });
        $wire.on('cerrarModalAnular', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalAnularOrden'));
            if (m) m.hide();
        });
        $wire.on('abrirModalEnviar', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEnviar')).show();
        });
        $wire.on('cerrarModalEnviar', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalEnviar'));
            if (m) m.hide();
        });
        $wire.on('abrirModalRecibir', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRecibir')).show();
        });
        $wire.on('cerrarModalRecibir', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalRecibir'));
            if (m) m.hide();
        });
        $wire.on('abrirModalSunat', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSunat')).show();
        });
        $wire.on('cerrarModalSunat', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalSunat'));
            if (m) m.hide();
        });
        $wire.on('abrirModalNcDb', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNcDb')).show();
        });
        $wire.on('cerrarModalNcDb', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalNcDb'));
            if (m) m.hide();
        });
        $wire.on('abrirModalPresentacionCompra', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPresentacionCompra')).show();
        });
        $wire.on('cerrarModalPresentacionCompra', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalPresentacionCompra'));
            if (m) m.hide();
        });
        document.getElementById('modalAnularOrden')
            .addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });

    $wire.on('cerrarModalNuevoProveedor', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevoProveedor')).hide();
    });

    document.addEventListener('keydown', function(e) {
        const el = e.target;
        if (!el.hasAttribute('data-compra-nav')) return;
        let dir = 0;
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') dir = 1;
        else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') dir = -1;
        else return;
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            if (el.tagName === 'INPUT' && (el.type === 'text' || el.type === '')) {
                if (e.key === 'ArrowLeft' && el.selectionStart !== 0) return;
                if (e.key === 'ArrowRight' && el.selectionStart !== el.value.length) return;
            }
            if (el.tagName === 'TEXTAREA') {
                if (e.key === 'ArrowLeft' && el.selectionStart !== 0) return;
                if (e.key === 'ArrowRight' && el.selectionStart !== el.value.length) return;
            }
        }
        e.preventDefault();
        const all = Array.from(document.querySelectorAll('[data-compra-nav]'));
        const idx = all.indexOf(el);
        const next = all[idx + dir];
        if (next) { next.focus(); if (next.select) next.select(); }
    }, true);
    </script>
    @endscript

</div>
