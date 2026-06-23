<div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Nueva Nota de Entrada / Salida
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalMovimiento" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-file-lines me-2"></i>
                        Registrar Nota de
                        {{ $tipo == 1 ? 'Entrada' : 'Salida' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Tipo de nota --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Nota</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" value="1"
                                       wire:model.live="tipo" id="tipoEntrada">
                                <label class="form-check-label" for="tipoEntrada">
                                    <i class="fa-solid fa-arrow-up text-success me-1"></i> Nota de Entrada
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" value="2"
                                       wire:model.live="tipo" id="tipoSalida">
                                <label class="form-check-label" for="tipoSalida">
                                    <i class="fa-solid fa-arrow-down text-danger me-1"></i> Nota de Salida
                                </label>
                            </div>
                        </div>
                        @error('tipo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Concepto --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Concepto <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('concepto') is-invalid @enderror"
                                wire:model="concepto">
                            <option value="">— Seleccionar concepto —</option>
                            @if($tipo == 1)
                                <option value="Ajuste de inventario">Ajuste de inventario</option>
                                <option value="Devolución interna">Devolución interna</option>
                                <option value="Sobrante de conteo">Sobrante de conteo</option>
                                <option value="Donación recibida">Donación recibida</option>
                                <option value="Préstamo recibido">Préstamo recibido</option>
                                <option value="Otro">Otro</option>
                            @else
                                <option value="Ajuste de inventario">Ajuste de inventario</option>
                                <option value="Merma / Deterioro">Merma / Deterioro</option>
                                <option value="Devolución a proveedor">Devolución a proveedor</option>
                                <option value="Donación entregada">Donación entregada</option>
                                <option value="Uso interno">Uso interno</option>
                                <option value="Préstamo entregado">Préstamo entregado</option>
                                <option value="Faltante de conteo">Faltante de conteo</option>
                                <option value="Otro">Otro</option>
                            @endif
                        </select>
                        @error('concepto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Observación (opcional para ambos tipos) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Observación
                            <span class="text-muted fw-normal small">(opcional)</span>
                        </label>
                        <textarea class="form-control @error('motivo') is-invalid @enderror"
                                  rows="2" wire:model="motivo"
                                  placeholder="Detalle adicional..."></textarea>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Buscador de productos --}}
                    <div class="mb-2 position-relative">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>
                            Buscar Producto
                            @if($tipo == 2)
                                <small class="text-muted fw-normal">(con stock disponible)</small>
                            @endif
                        </label>
                        <input type="text" class="form-control"
                               wire:model.live.debounce.350ms="buscarProducto"
                               wire:focus="cargarSugerenciasIniciales"
                               placeholder="Escriba nombre o código...">

                        @if(count($productosDisponibles) > 0)
                        <div class="list-group shadow-lg position-absolute w-100 bg-white border"
                             style="z-index:1060;max-height:240px;overflow-y:auto;top:calc(100% + 2px)">
                            @foreach($productosDisponibles as $prod)
                            <button type="button"
                                    class="list-group-item list-group-item-action bg-white py-2"
                                    wire:click="agregarProducto({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', {{ $prod->ps_stock }})">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold small">{{ $prod->pro_nombre }}</span>
                                    <span class="badge {{ $prod->ps_stock > 0 ? 'bg-success' : 'bg-danger' }} ms-2">
                                        {{ number_format($prod->ps_stock, 2) }}
                                    </span>
                                </div>
                                @if($prod->pro_codigo)
                                    <small class="text-muted">Cód: {{ $prod->pro_codigo }}</small>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @elseif(strlen($buscarProducto) >= 2)
                        <small class="text-muted d-block mt-1">
                            <i class="fa-solid fa-circle-info me-1"></i>Sin resultados.
                        </small>
                        @endif
                    </div>

                    @error('productosSeleccionados')
                    <div class="alert alert-warning py-2 small mb-2">{{ $message }}</div>
                    @enderror

                    {{-- Tabla de productos seleccionados --}}
                    @if(count($productosSeleccionados) > 0)
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    @if($tipo == 2)
                                    <th class="text-center" style="width:110px">Stock disp.</th>
                                    @endif
                                    <th style="width:130px">Cantidad</th>
                                    <th style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productosSeleccionados as $i => $item)
                                <tr>
                                    <td class="align-middle">{{ $item['pro_nombre'] }}</td>
                                    @if($tipo == 2)
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $item['ps_stock'] > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ number_format($item['ps_stock'], 2) }}
                                        </span>
                                    </td>
                                    @endif
                                    <td>
                                        <input type="number"
                                               class="form-control form-control-sm @error('productosSeleccionados.'.$i.'.cantidad') is-invalid @enderror"
                                               wire:model="productosSeleccionados.{{ $i }}.cantidad"
                                               min="0.01" step="0.01"
                                               @if($tipo == 2) max="{{ $item['ps_stock'] }}" @endif>
                                        @error('productosSeleccionados.'.$i.'.cantidad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-danger btn-sm"
                                                wire:click="quitarProducto({{ $i }})">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary"
                            wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="guardar"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL — Detalle
    ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDetalleMovimiento" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                @if($detalleMovimiento)
                <div style="height:5px"
                     class="{{ $detalleMovimiento->movimientos_productos_tipo == 1 ? 'bg-success' : 'bg-danger' }}"></div>
                @else
                <div style="height:5px" class="bg-secondary"></div>
                @endif
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">
                        @if($detalleMovimiento)
                            @if($detalleMovimiento->movimientos_productos_tipo == 1)
                                <i class="fa-solid fa-arrow-up text-success me-2"></i>Nota de Entrada
                            @else
                                <i class="fa-solid fa-arrow-down text-danger me-2"></i>Nota de Salida
                            @endif
                        @endif
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    @if($detalleMovimiento)
                    {{-- Info cabecera --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.7rem;">FECHA</div>
                                <div class="fw-semibold small">
                                    {{ \Carbon\Carbon::parse($detalleMovimiento->movimientos_productos_fecha)->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.7rem;">USUARIO</div>
                                <div class="fw-semibold small text-truncate">{{ $detalleMovimiento->nombre_users }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-light rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.7rem;">UBICACIÓN</div>
                                <div class="fw-semibold small">
                                    @if($detalleMovimiento->tienda_nombre)
                                        <i class="fa-solid fa-store me-1 text-primary"></i>{{ $detalleMovimiento->tienda_nombre }}
                                    @else
                                        <i class="fa-solid fa-warehouse me-1 text-primary"></i>Almacén Principal
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($detalleMovimiento->concepto)
                        <div class="col-12">
                            <div class="bg-light rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.7rem;">CONCEPTO</div>
                                <div class="fw-semibold small">{{ $detalleMovimiento->concepto }}</div>
                            </div>
                        </div>
                        @endif
                        @if($detalleMovimiento->movimientos_productos_motivo)
                        <div class="col-12">
                            <div class="bg-light rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.7rem;">OBSERVACIÓN</div>
                                <div class="small">{{ $detalleMovimiento->movimientos_productos_motivo }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                    {{-- Tabla productos --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                            <thead class="encabezado_tabla_color">
                                <tr>
                                    <th class="text-white">Producto</th>
                                    <th class="text-white text-center" style="width:90px">Cantidad</th>
                                    <th class="text-white text-end" style="width:100px">Costo Unit.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detalleItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->pro_nombre }}</div>
                                        @if($item->pro_codigo)
                                        <small class="text-muted">{{ $item->pro_codigo }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ number_format($item->movimientos_productos_detalle_cantidad, 2) }}
                                    </td>
                                    <td class="text-end text-muted">
                                        @if($item->costo_unitario > 0)
                                            S/ {{ number_format($item->costo_unitario, 4) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Sin detalle.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         HISTORIAL
    ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-file-lines me-2 text-primary"></i>
                        Notas de Entrada / Salida
                        @if($ubicacionActual)
                            <span class="badge fw-normal ms-1" style="background:#eef1ff;color:#0b1892;font-size:.72rem;">
                                <i class="fa-solid fa-{{ $modoAlmacen ? 'warehouse' : 'store' }} me-1"></i>{{ $ubicacionActual }}
                            </span>
                        @endif
                    </h5>
                    <small class="text-muted">Ingresos y salidas de productos sin compra ni venta.</small>
                </div>
                @if(!$modoAlmacen && $sucursalSeleccionada)
                @can('movimientos_productos.crear')
                <button class="btn btn-success fw-semibold" wire:click="nuevo">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Nota
                </button>
                @endcan
                @endif
            </div>

            {{-- Filtros --}}
            <div class="row g-2 align-items-end mt-3 flex-wrap">

                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                {{-- Empresa (superadmin) --}}
                @if($esSuperAdmin)
                <div class="col-auto">
                    <select wire:model.live="empresaSeleccionada"
                            class="form-select form-select-sm" style="min-width:200px;">
                        <option value="0">
                            <i class="fa-solid fa-warehouse"></i> — Almacén Principal —
                        </option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial ?? $emp->empresa_razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Sede --}}
                @if(($esSuperAdmin || $esAdmin) && !$modoAlmacen && count((array)$sucursalesDisponibles) > 0)
                <div class="col-auto">
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">— Todas las sedes —</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_tienda }}">{{ $suc->tienda_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Fechas + Buscar --}}
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Desde</span>
                        <input type="date" class="form-control" wire:model="desde">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">Hasta</span>
                        <input type="date" class="form-control" wire:model="hasta">
                    </div>
                </div>
                <div class="col-auto">
                    <button wire:click="buscar" wire:loading.attr="disabled" wire:target="buscar"
                            class="btn btn-primary btn-sm fw-semibold px-3">
                        <span wire:loading wire:target="buscar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <i wire:loading.remove wire:target="buscar" class="fa-solid fa-magnifying-glass me-1"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3" style="width:44px">#</th>
                            <th style="width:90px">Fecha</th>
                            <th style="width:130px">Tipo</th>
                            <th>Ubicación</th>
                            <th>Usuario</th>
                            <th>Concepto / Observación</th>
                            <th class="text-center" style="width:70px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $idx => $mov)
                        @php
                            $esEntrada = $mov->movimientos_productos_tipo == 1;
                            $rowBg = $esEntrada ? 'rgba(25,135,84,.04)' : 'rgba(220,53,69,.04)';
                        @endphp
                        <tr style="background:{{ $rowBg }}">
                            <td class="ps-3 text-muted fw-semibold">
                                {{ $historial->firstItem() + $idx }}
                            </td>
                            <td class="text-muted">
                                {{ \Carbon\Carbon::parse($mov->movimientos_productos_fecha)->format('d/m/Y') }}
                            </td>
                            <td>
                                @if($esEntrada)
                                    <span class="badge bg-success text-white">
                                        <i class="fa-solid fa-arrow-up me-1" style="font-size:.65rem;"></i>Nota de Entrada
                                    </span>
                                @else
                                    <span class="badge bg-danger text-white">
                                        <i class="fa-solid fa-arrow-down me-1" style="font-size:.65rem;"></i>Nota de Salida
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($mov->tienda_nombre)
                                    <span class="badge bg-secondary text-white fw-normal">
                                        <i class="fa-solid fa-store me-1"></i>{{ $mov->tienda_nombre }}
                                    </span>
                                @else
                                    <span class="badge text-white fw-normal" style="background:#0b1892;">
                                        <i class="fa-solid fa-warehouse me-1"></i>Almacén Principal
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $mov->nombre_users }}</td>
                            <td>
                                @if($mov->concepto)
                                    <span class="badge fw-normal"
                                          style="background:{{ $esEntrada ? '#d1fae5' : '#fee2e2' }};color:{{ $esEntrada ? '#065f46' : '#991b1b' }}">
                                        {{ $mov->concepto }}
                                    </span>
                                @endif
                                @if($mov->movimientos_productos_motivo)
                                    <div class="text-muted text-truncate mt-1" style="max-width:200px;font-size:.78rem;"
                                         title="{{ $mov->movimientos_productos_motivo }}">
                                        {{ $mov->movimientos_productos_motivo }}
                                    </div>
                                @elseif(!$mov->concepto)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('movimientos_productos.listar')
                                <button class="btn btn-sm btn-outline-primary"
                                        wire:click="verDetalle({{ $mov->id_movimientos_productos }})"
                                        title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                @if(!$mostrarResultados)
                                    <i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>
                                    Seleccione los filtros y haga clic en <strong>Buscar</strong>.
                                @else
                                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                                    No se encontraron notas en el rango seleccionado.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($historial->hasPages())
            <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <small class="text-muted">
                    Mostrando {{ $historial->firstItem() }}–{{ $historial->lastItem() }}
                    de {{ $historial->total() }} registros
                </small>
                {{ $historial->links(data: ['scrollTo' => false]) }}
            </div>
            @endif
        </div>
    </div>

    <div wire:loading wire:target="verDetalle, guardar, nuevo, buscar">
        <x-loader />
    </div>

</div>

@script
<script>
    $wire.on('abrirModalMovimiento', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMovimiento')).show();
    });
    $wire.on('cerrarModalMovimiento', () => {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalMovimiento'));
        if (m) m.hide();
    });
    $wire.on('abrirModalDetalle', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleMovimiento')).show();
    });

    ['modalMovimiento', 'modalDetalleMovimiento'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });
        }
    });
</script>
@endscript
