<div>

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

    {{-- ══════════════════════════════════════════════════
         MODAL — Confirmar Acción
    ═══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalConfirmar" wire:ignore.self tabindex="-1"
         x-data="{ procesando: false }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-circle-question me-2 text-warning"></i>
                        Confirmar Acción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($accionConfirm === 'anular')
                        <p>¿Desea <strong>anular</strong> esta transferencia?</p>
                        <p class="text-muted small mb-3">Si está en tránsito, el stock será restaurado en el origen.</p>
                        <div>
                            <label class="form-label fw-semibold small text-secondary mb-1">
                                Motivo de anulación <span class="text-danger">*</span>
                            </label>
                            <textarea wire:model="motivoAnulacion" rows="3"
                                      class="form-control @error('motivoAnulacion') is-invalid @enderror"
                                      placeholder="Describe el motivo de la anulación..."></textarea>
                            @error('motivoAnulacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    {{--<button type="button" class="btn btn-secondary"
                            :disabled="procesando"
                            data-bs-dismiss="modal">
                        <span x-show="!procesando">Cancelar</span>
                        <span x-show="procesando" style="display:none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Espera...
                        </span>
                    </button>--}}
                    <button type="button"
                            class="btn {{ $accionConfirm === 'anular' ? 'btn-danger' : 'btn-primary' }}"
                            wire:click="ejecutarAccion"
                            x-on:click="procesando = true"
                            :disabled="procesando">
                        <span x-show="!procesando">Confirmar</span>
                        <span x-show="procesando" style="display:none">
                            <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         MODAL — Ver Detalle
    ═══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalle" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-truck-moving me-2 text-primary"></i>
                        Detalle de Transferencia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($detalleTransferencia)
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">N° Transferencia</small>
                            <strong>{{ $detalleTransferencia->transferencia_numero }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Fecha</small>
                            <strong>{{ \Carbon\Carbon::parse($detalleTransferencia->transferencia_fecha)->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Origen</small>
                            <span class="badge bg-primary" style="white-space:normal;word-break:break-word;text-align:left;line-height:1.6;padding:4px 7px;">
                                <i class="fa-solid fa-warehouse me-1"></i>{{ $detalleTransferencia->origen_nombre }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Destino</small>
                            <span class="badge bg-info text-white" style="white-space:normal;word-break:break-word;text-align:left;line-height:1.6;padding:4px 7px;">{{ $detalleTransferencia->destino_nombre }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Creado por</small>
                            <strong>{{ $detalleTransferencia->nombre_users }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Estado</small>
                            @php
                                $estadoBadge = match($detalleTransferencia->transferencia_estado) {
                                    'pendiente'   => ['bg-secondary',       'Pendiente'],
                                    'en_transito' => ['bg-warning text-white','En Tránsito'],
                                    'recibido'    => ['bg-success',          'Recibido'],
                                    'anulado'     => ['bg-danger',           'Anulado'],
                                    default       => ['bg-secondary text-white',  '—'],
                                };
                            @endphp
                            <span class="badge {{ $estadoBadge[0] }}">{{ $estadoBadge[1] }}</span>
                        </div>
                        @if($detalleTransferencia->transferencia_motivo)
                        <div class="col-12">
                            <small class="text-muted d-block">Motivo</small>
                            <span>{{ $detalleTransferencia->transferencia_motivo }}</span>
                        </div>
                        @endif
                        @if($detalleTransferencia->transferencia_motivo_anulacion)
                        <div class="col-12">
                            <small class="text-muted d-block text-danger">Motivo de anulación</small>
                            <span class="text-danger">{{ $detalleTransferencia->transferencia_motivo_anulacion }}</span>
                        </div>
                        @endif
                    </div>
                    @php $fueRecibida = $detalleTransferencia->transferencia_estado === 'recibido'; @endphp
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th class="text-end">Enviado</th>
                                @if($fueRecibida)
                                <th class="text-end">Recibido</th>
                                <th class="text-center">Diferencia</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalleItems as $item)
                            @php
                                $enviado  = (float) $item->detalle_cantidad;
                                $recibido = (float) ($item->detalle_cantidad_recibida ?? 0);
                                $diff     = $recibido - $enviado;
                            @endphp
                            <tr class="{{ $fueRecibida ? ($diff < 0 ? 'table-danger' : ($diff > 0 ? 'table-info' : '')) : '' }}">
                                <td>{{ $item->pro_nombre }}</td>
                                <td class="text-muted small">{{ $item->pro_codigo }}</td>
                                <td class="text-end fw-semibold">{{ number_format($enviado, 2) }}</td>
                                @if($fueRecibida)
                                <td class="text-end fw-semibold">{{ number_format($recibido, 2) }}</td>
                                <td class="text-center small fw-semibold">
                                    @if($diff == 0)
                                        <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                    @elseif($diff < 0)
                                        <span class="text-danger">{{ number_format($diff, 2) }}</span>
                                    @else
                                        <span class="text-primary">+{{ number_format($diff, 2) }}</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($fueRecibida)
                    <div class="mt-2">
                        <small class="text-muted">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">Rojo</span> Merma / faltante &nbsp;
                            <span class="badge bg-info-subtle text-info border border-info-subtle me-2">Azul</span> Exceso recibido &nbsp;
                            <span class="badge bg-light text-dark border me-2">Sin color</span> Cantidad exacta
                        </small>
                    </div>
                    @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         MODAL — Recepcionar Transferencia
    ═══════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalRecepcion" wire:ignore.self tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-box-open me-2 text-success"></i>
                        Recepcionar Transferencia
                        @if($ordenRecibir)
                            <span class="fw-normal text-muted small ms-2">— {{ $ordenRecibir->transferencia_numero }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close"
                            wire:loading.attr="disabled" wire:target="confirmarRecepcion"
                            wire:click="volverDesdeRecepcion"></button>
                </div>
                <div class="modal-body">
                    @if($ordenRecibir)
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Origen</small>
                            <span class="badge bg-primary" style="white-space:normal;word-break:break-word;text-align:left;line-height:1.6;padding:4px 7px;">
                                <i class="fa-solid fa-warehouse me-1"></i>{{ $ordenRecibir->origen_nombre }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Destino</small>
                            <span class="badge bg-info text-white" style="white-space:normal;word-break:break-word;text-align:left;line-height:1.6;padding:4px 7px;">{{ $ordenRecibir->destino_nombre }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Fecha</small>
                            <span class="fw-semibold small">
                                {{ \Carbon\Carbon::parse($ordenRecibir->transferencia_fecha)->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Ajusta la cantidad recibida si algún producto llegó incompleto o dañado. Las diferencias quedan registradas como merma.
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
                                    <th class="text-center" style="width:110px;">Enviado</th>
                                    <th class="text-center" style="width:180px;">Recibido</th>
                                    <th class="text-center" style="width:110px;">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detallesRecibir as $i => $det)
                                @php
                                    $enviado  = (float) $det->detalle_cantidad;
                                    $recibido = (float) ($cantidadesRecibidas[$det->id_transferencia_detalle] ?? $enviado);
                                    $diff     = $recibido - $enviado;
                                @endphp
                                <tr class="{{ $diff < 0 ? 'table-danger' : ($diff > 0 ? 'table-info' : '') }}">
                                    <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold d-block">{{ $det->pro_nombre }}</span>
                                        <small class="text-muted">{{ $det->pro_codigo }}</small>
                                    </td>
                                    <td class="text-center fw-semibold text-muted">{{ number_format($enviado, 2) }}</td>
                                    <td class="text-center">
                                        <input type="number"
                                               wire:model.live="cantidadesRecibidas.{{ $det->id_transferencia_detalle }}"
                                               class="form-control form-control-sm text-center"
                                               min="0" max="{{ $enviado }}" step="0.01"
                                               style="width:120px; margin:auto;">
                                    </td>
                                    <td class="text-center fw-semibold small">
                                        @if($diff == 0)
                                            <span class="text-success"><i class="fa-solid fa-check"></i></span>
                                        @elseif($diff < 0)
                                            <span class="text-danger">{{ number_format($diff, 2) }}</span>
                                        @else
                                            <span class="text-primary">+{{ number_format($diff, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">Rojo</span> Merma / faltante &nbsp;
                            <span class="badge bg-info-subtle text-info border border-info-subtle me-2">Azul</span> Exceso recibido &nbsp;
                            <span class="badge bg-light text-dark border me-2">Sin color</span> Cantidad exacta
                        </small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            wire:loading.attr="disabled"
                            wire:target="confirmarRecepcion"
                            wire:click="volverDesdeRecepcion">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-success fw-semibold"
                            wire:click="confirmarRecepcion"
                            wire:loading.attr="disabled"
                            wire:target="confirmarRecepcion">
                        <span wire:loading.remove wire:target="confirmarRecepcion">
                            <i class="fa-solid fa-check me-1"></i> Confirmar Recepción
                        </span>
                        <span wire:loading wire:target="confirmarRecepcion">
                            <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         VISTA: NUEVA TRANSFERENCIA
    ═══════════════════════════════════════════════════════ --}}
    @if($vista === 'nueva')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-truck-moving me-2 text-primary"></i>
                    Nueva Transferencia de Stock
                </h5>
                <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver
                </button>
            </div>
        </div>
        <div class="card-body">

            {{-- Origen / Destino --}}
            <div class="row g-3 mb-4">

                {{-- ─ ORIGEN ──────────────────────────────────── --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1 text-primary"></i>
                        Origen <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="origenKey"
                            class="form-select @error('origenKey') is-invalid @enderror">
                        <option value="">— Seleccione origen —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}"
                                    {{ $destinoKey === 'almacen_'.$alm->id_almacen ? 'disabled' : '' }}>
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresas as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('origenKey') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if(str_starts_with($origenKey, 'empresa_'))
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Sede origen <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="idTiendaOrigen"
                            class="form-select @error('idTiendaOrigen') is-invalid @enderror"
                            {{ $sedesOrigen->isEmpty() ? 'disabled' : '' }}>
                        <option value="0">— Seleccione sede —</option>
                        @foreach($sedesOrigen as $sede)
                        <option value="{{ $sede->id_tienda }}"
                                {{ ($destinoKey === $origenKey && $idTiendaDestino == $sede->id_tienda) ? 'disabled' : '' }}>
                            {{ $sede->tienda_nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('idTiendaOrigen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @endif

                {{-- ─ DESTINO ──────────────────────────────────── --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1 text-success"></i>
                        Destino <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="destinoKey"
                            class="form-select @error('destinoKey') is-invalid @enderror">
                        <option value="">— Seleccione destino —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}"
                                    {{ $origenKey === 'almacen_'.$alm->id_almacen ? 'disabled' : '' }}>
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresas as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('destinoKey') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if(str_starts_with($destinoKey, 'empresa_'))
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Sede destino <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="idTiendaDestino"
                            class="form-select @error('idTiendaDestino') is-invalid @enderror"
                            {{ $sedesDestino->isEmpty() ? 'disabled' : '' }}>
                        <option value="0">— Seleccione sede —</option>
                        @foreach($sedesDestino as $sede)
                        <option value="{{ $sede->id_tienda }}"
                                {{ ($origenKey === $destinoKey && $idTiendaOrigen == $sede->id_tienda) ? 'disabled' : '' }}>
                            {{ $sede->tienda_nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('idTiendaDestino') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @endif

                {{-- Motivo --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Motivo</label>
                    <textarea wire:model="motivo" class="form-control" rows="2"
                              placeholder="Motivo de la transferencia..."></textarea>
                </div>
            </div>

            {{-- Buscador de productos --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Productos
                    </h6>

                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            @php
                                $origenOk  = str_starts_with($origenKey,  'almacen_') || ($idTiendaOrigen  > 0);
                                $destinoOk = str_starts_with($destinoKey, 'almacen_') || ($idTiendaDestino > 0);
                            @endphp
                            <input type="text" class="form-control"
                                   wire:model.live.debounce.300ms="buscarProducto"
                                   placeholder="{{ ($origenOk && $destinoOk) ? 'Escribe nombre o código para buscar...' : 'Primero configura el origen y destino' }}"
                                   {{ (!$origenOk || !$destinoOk) ? 'disabled' : '' }}
                                   autocomplete="off">
                        </div>

                        @if(!empty($resultados))
                        <div class="position-absolute w-100 shadow-lg border rounded-2 bg-white"
                             style="z-index:999; top:100%; max-height:280px; overflow-y:auto;">
                            @foreach($resultados as $prod)
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                                 style="cursor:pointer;"
                                 wire:click="agregarProducto({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', '{{ $prod->pro_codigo }}', {{ $prod->stock_origen }})"
                                 wire:key="res-{{ $prod->id_pro }}">
                                <div>
                                    <span class="fw-semibold d-block">{{ $prod->pro_nombre }}</span>
                                    <small class="text-muted">{{ $prod->pro_codigo }}</small>
                                    @if($prod->fa_nombre || $prod->ca_nombre)
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-tag fa-xs me-1 opacity-50"></i>
                                        {{ implode(' › ', array_filter([$prod->fa_nombre, $prod->ca_nombre])) }}
                                    </small>
                                    @endif
                                </div>
                                <div class="text-end ms-3 flex-shrink-0">
                                    <small class="text-success fw-semibold d-block">
                                        Stock: {{ number_format($prod->stock_origen, 2) }}
                                    </small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($origenOk && $destinoOk && $buscarProducto !== '' && empty($resultados))
                        <div class="position-absolute w-100 shadow border rounded-2 bg-white px-3 py-2"
                             style="z-index:999; top:100%;">
                            <small class="text-muted">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                No se encontraron productos con stock en el Almacén Principal.
                            </small>
                        </div>
                        @endif
                    </div>

                    @error('items')
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $message }}
                        </div>
                    @enderror

                    {{-- Tabla de ítems --}}
                    @if(count($items) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="table-light">
                                    <th>Producto</th>
                                    <th style="width:130px;" class="text-end">Stock origen</th>
                                    <th style="width:150px;">Cantidad</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $idx => $item)
                                <tr wire:key="item-{{ $idx }}">
                                    <td>
                                        <span class="fw-semibold d-block small">{{ $item['nombre'] }}</span>
                                        <small class="text-muted">{{ $item['codigo'] }}</small>
                                    </td>
                                    <td class="text-end text-muted">{{ number_format($item['stock_max'], 2) }}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" max="{{ $item['stock_max'] }}"
                                               wire:model.live="items.{{ $idx }}.cantidad"
                                               class="form-control form-control-sm text-end @error('items.'.$idx.'.cantidad') is-invalid @enderror">
                                        @error('items.'.$idx.'.cantidad')
                                            <div class="invalid-feedback small">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="quitarItem({{ $idx }})" title="Quitar">
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
                        <small>Busca y agrega productos a transferir.</small>
                    </div>
                    @endif

                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-1">
                <button type="button" class="btn btn-secondary" wire:click="volverHistorial">Cancelar</button>
                <button type="button" class="btn btn-primary fw-semibold"
                        wire:click="guardar" wire:loading.attr="disabled"
                        {{ (!$origenOk || !$destinoOk || empty($items)) ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="guardar">
                        <i class="fa-solid fa-paper-plane me-1"></i> Crear Transferencia
                    </span>
                    <span wire:loading wire:target="guardar">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;" role="status"></span> Guardando...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         VISTA: HISTORIAL
    ═══════════════════════════════════════════════════════ --}}
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-truck-moving me-2 text-primary"></i>
                        Transferencias de Stock
                    </h5>
                    <small class="text-muted">Traslados desde el Almacén Principal hacia las sedes.</small>
                </div>
                @can('transferencias_stock.crear')
                <button class="btn btn-primary fw-semibold" wire:click="nuevaTransferencia"
                        wire:loading.attr="disabled" wire:target="nuevaTransferencia">
                    <span wire:loading.remove wire:target="nuevaTransferencia">
                        <i class="fa-solid fa-plus me-1"></i> Nueva Transferencia
                    </span>
                    <span wire:loading wire:target="nuevaTransferencia">
                        <span class="spinner-border" style="width:1.4rem;height:1.4rem;vertical-align:middle;margin-right:.35rem;" role="status"></span>Cargando...
                    </span>
                </button>
                @endcan
            </div>

            <div class="row g-2 align-items-end mt-3">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                {{-- Filtro Origen --}}
                <div class="col-auto">
                    <select wire:model.live="filtroOrigenKey" class="form-select form-select-sm" style="min-width:200px;">
                        <option value="">— Origen: todos —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}">
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresas as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                @if(str_starts_with($filtroOrigenKey, 'empresa_') && $sedesOrigenFiltro->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroIdTiendaOrigen" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todas las sedes</option>
                        @foreach($sedesOrigenFiltro as $sede)
                        <option value="{{ $sede->id_tienda }}">{{ $sede->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Filtro Destino --}}
                <div class="col-auto">
                    <select wire:model.live="filtroDestinoKey" class="form-select form-select-sm" style="min-width:200px;">
                        <option value="">— Destino: todos —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}">
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresas as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">
                                {{ $emp->empresa_nombrecomercial }}
                            </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                @if(str_starts_with($filtroDestinoKey, 'empresa_') && $sedesDestinoFiltro->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroIdTiendaDestino" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todas las sedes</option>
                        @foreach($sedesDestinoFiltro as $sede)
                        <option value="{{ $sede->id_tienda }}">{{ $sede->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-auto">
                    <select wire:model.live="filtroEstado" class="form-select form-select-sm" style="min-width:140px;">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_transito">En Tránsito</option>
                        <option value="recibido">Recibido</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </div>

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

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:42px;">#</th>
                            <th style="width:120px;">N° Transferencia</th>
                            <th>Origen → Destino</th>
                            <th style="width:85px;">Fecha</th>
                            <th style="width:120px;">Usuario</th>
                            <th class="text-center" style="width:100px;">Estado</th>
                            <th class="text-center" style="width:150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferencias as $index => $trf)
                        @php
                            $estadoInfo = match($trf->transferencia_estado) {
                                'pendiente'   => ['bg-light text-secondary border', 'Pendiente'],
                                'en_transito' => ['bg-light text-secondary border', 'En Tránsito'],
                                'recibido'    => ['bg-light text-secondary border', 'Recibido'],
                                'anulado'     => ['bg-light text-secondary border', 'Anulado'],
                                default       => ['bg-light text-secondary border', '—'],
                            };
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted fw-semibold">{{ $transferencias->firstItem() + $index }}</td>
                            <td class="fw-semibold small">{{ $trf->transferencia_numero }}</td>
                            <td>
                                <div class="mb-1">
                                    <span class="badge bg-primary me-1" style="font-size:0.58rem;padding:2px 5px;vertical-align:middle;">ORI</span>
                                    <span class="small fw-semibold">{{ $trf->origen_nombre }}</span>
                                </div>
                                <div>
                                    <span class="badge bg-info me-1" style="font-size:0.58rem;padding:2px 5px;vertical-align:middle;">DST</span>
                                    <span class="small text-muted">{{ $trf->destino_nombre }}</span>
                                </div>
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($trf->transferencia_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-muted small">{{ $trf->nombre_users }}</td>
                            <td class="text-center">
                                <span class="badge {{ $estadoInfo[0] }}">{{ $estadoInfo[1] }}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info me-1"
                                        wire:click="verDetalle({{ $trf->id_transferencia }})"
                                        wire:loading.attr="disabled"
                                        wire:target="verDetalle({{ $trf->id_transferencia }})"
                                        title="Ver detalle">
                                    <span wire:loading.remove wire:target="verDetalle({{ $trf->id_transferencia }})">
                                        <i class="fa-solid fa-eye"></i>
                                    </span>
                                    <span wire:loading wire:target="verDetalle({{ $trf->id_transferencia }})">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                                <a href="{{ route('logistica.transferencia_pdf', ['id' => $trf->id_transferencia]) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-danger me-1"
                                   title="Descargar PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                @if($trf->transferencia_estado === 'en_transito')
                                    @can('transferencias_stock.actualizar')
                                    <button class="btn btn-sm btn-success me-1" title="Recepcionar en sede"
                                            wire:click="abrirRecepcion({{ $trf->id_transferencia }})"
                                            wire:loading.attr="disabled"
                                            wire:target="abrirRecepcion({{ $trf->id_transferencia }})">
                                        <span wire:loading.remove wire:target="abrirRecepcion({{ $trf->id_transferencia }})">
                                            <i class="fa-solid fa-box-open"></i>
                                        </span>
                                        <span wire:loading wire:target="abrirRecepcion({{ $trf->id_transferencia }})">
                                            <span class="spinner-border spinner-border-sm"></span>
                                        </span>
                                    </button>
                                    @endcan
                                @endif
                                @if($trf->transferencia_estado === 'en_transito')
                                    @can('transferencias_stock.cambiar_estado')
                                    <button class="btn btn-sm btn-secondary" title="Anular"
                                            wire:click="confirmarAccion({{ $trf->id_transferencia }}, 'anular')"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmarAccion({{ $trf->id_transferencia }}, 'anular')">
                                        <span wire:loading.remove wire:target="confirmarAccion({{ $trf->id_transferencia }}, 'anular')">
                                            <i class="fa-solid fa-ban"></i>
                                        </span>
                                        <span wire:loading wire:target="confirmarAccion({{ $trf->id_transferencia }}, 'anular')">
                                            <span class="spinner-border spinner-border-sm"></span>
                                        </span>
                                    </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fa-solid fa-truck-moving fa-2x mb-2 d-block opacity-25"></i>
                                No hay transferencias en el período seleccionado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($transferencias->hasPages())
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $transferencias->firstItem() }}–{{ $transferencias->lastItem() }}
                    de {{ $transferencias->total() }} registros
                </small>
                {{ $transferencias->links() }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <div wire:loading wire:target="origenKey, idTiendaOrigen, destinoKey, idTiendaDestino">
        <x-loader />
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('abrirPdf', ({ url }) => window.open(url, '_blank'));

            Livewire.on('abrirModalConfirmar', () => {
                new bootstrap.Modal(document.getElementById('modalConfirmar')).show();
            });
            Livewire.on('cerrarModalConfirmar', () => {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmar'))?.hide();
            });
            Livewire.on('abrirModalDetalle', () => {
                new bootstrap.Modal(document.getElementById('modalDetalle')).show();
            });
            Livewire.on('abrirModalRecepcion', () => {
                new bootstrap.Modal(document.getElementById('modalRecepcion')).show();
            });
            Livewire.on('cerrarModalRecepcion', () => {
                bootstrap.Modal.getInstance(document.getElementById('modalRecepcion'))?.hide();
            });

            // Resetear estado Alpine al cerrar cada modal (hidden.bs.modal se
            // dispara cuando Bootstrap termina la animación de cierre)
            document.getElementById('modalConfirmar').addEventListener('hidden.bs.modal', function () {
                Alpine.$data(this).procesando = false;
            });
        });
    </script>
</div>
