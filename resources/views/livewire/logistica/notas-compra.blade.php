<div>

{{-- ══ MODAL CONFIRMAR ACCIÓN ══════════════════════════════════════════════ --}}
<div class="modal fade" id="modalAccionNota" wire:ignore.self tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold">
                    @if($accionTipo === 'aprobar')
                        <i class="fa-solid fa-circle-check text-success me-2"></i>Aprobar Nota
                    @else
                        <i class="fa-solid fa-ban text-danger me-2"></i>Anular Nota
                    @endif
                </h6>
                <button type="button" class="btn-close" wire:click="$dispatch('cerrarModalAccion')"></button>
            </div>
            <div class="modal-body pt-2">
                @if($accionTipo === 'aprobar')
                    <p class="mb-0 text-muted small">¿Deseas <strong>aprobar</strong> esta nota? Se aplicará el impacto en cuentas por pagar y stock (si corresponde).</p>
                @else
                    <p class="mb-2 text-muted small">¿Deseas <strong>anular</strong> esta nota? Si estaba aprobada, se revertirá su impacto.</p>
                    <label class="form-label fw-semibold small text-secondary mb-1">Motivo de anulación <span class="text-danger">*</span></label>
                    <textarea wire:model="motivoAccion" class="form-control form-control-sm @error('motivoAccion') is-invalid @enderror"
                        rows="2" placeholder="Describe el motivo..."></textarea>
                    @error('motivoAccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @endif
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button class="btn btn-sm btn-secondary" wire:click="$dispatch('cerrarModalAccion')">Cancelar</button>
                <button class="btn btn-sm {{ $accionTipo === 'aprobar' ? 'btn-success' : 'btn-danger' }}"
                        wire:click="ejecutarAccion" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="ejecutarAccion">
                        {{ $accionTipo === 'aprobar' ? 'Aprobar' : 'Anular' }}
                    </span>
                    <span wire:loading wire:target="ejecutarAccion">
                        <span class="spinner-border spinner-border-sm me-1"></span>Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ VISTA HISTORIAL ══════════════════════════════════════════════════════ --}}
@if($vista === 'historial')
<div class="container-fluid py-3">

    {{-- Alertas --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="fa-solid fa-circle-check me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
        <i class="fa-solid fa-circle-exclamation me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
            <div>
                <h5 class="mb-0 fw-bold">
                    <i class="fa-solid fa-file-invoice text-primary me-2"></i>Notas de Crédito / Débito — Compras
                </h5>
                <small class="text-muted">Gestiona las NC y DB emitidas por proveedores</small>
            </div>
            @can('notas_compra.crear')
            <button class="btn btn-primary btn-sm fw-semibold" wire:click="nuevaNota">
                <i class="fa-solid fa-plus me-1"></i>Nueva Nota
            </button>
            @endcan
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom py-2">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <select wire:model.live="filtroTipo" class="form-select form-select-sm" style="min-width:140px;">
                        <option value="">— Tipo: todos —</option>
                        <option value="NC">Nota de Crédito</option>
                        <option value="DB">Nota de Débito</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select wire:model.live="filtroEstado" class="form-select form-select-sm" style="min-width:140px;">
                        <option value="">— Estado: todos —</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </div>
                @if(auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('admin'))
                <div class="col-auto">
                    <select wire:model.live="filtroProveedor" class="form-select form-select-sm" style="min-width:180px;">
                        <option value="0">— Proveedor: todos —</option>
                        @foreach($proveedores as $p)
                        <option value="{{ $p->id_proveedores }}">{{ $p->proveedores_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <input type="date" wire:model.live="filtroDesde" class="form-control form-control-sm" title="Desde">
                </div>
                <div class="col-auto">
                    <input type="date" wire:model.live="filtroHasta" class="form-control form-control-sm" title="Hasta">
                </div>
                <div class="col-auto ms-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm">
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3">#</th>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>OC Ref.</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Afecta Stock</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notas as $nota)
                        <tr wire:key="nota-{{ $nota->id_nota_compra }}">
                            <td class="ps-3 text-muted">{{ $nota->id_nota_compra }}</td>
                            <td class="fw-semibold">{{ $nota->nota_numero }}</td>
                            <td>
                                @if($nota->tipo_nota === 'NC')
                                <span class="badge bg-info text-white">Nota Crédito</span>
                                @else
                                <span class="badge bg-warning text-dark">Nota Débito</span>
                                @endif
                            </td>
                            <td>{{ $nota->proveedores_nombre }}</td>
                            <td class="text-muted small">
                                @if($nota->id_orden_compra)
                                    {{ DB::table('orden_compra')->where('id_orden_compra', $nota->id_orden_compra)->value('orden_compra_numero') ?? '—' }}
                                @else —
                                @endif
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($nota->nota_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-end fw-semibold">S/ {{ number_format($nota->nota_total, 2) }}</td>
                            <td class="text-center">
                                @if($nota->nota_afecta_stock)
                                <span class="badge bg-success bg-opacity-75"><i class="fa-solid fa-check"></i></span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($nota->nota_estado === 'pendiente')
                                <span class="badge bg-secondary">Pendiente</span>
                                @elseif($nota->nota_estado === 'aprobado')
                                <span class="badge bg-success">Aprobado</span>
                                @else
                                <span class="badge bg-danger">Anulado</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    @if($nota->nota_estado === 'pendiente')
                                    @can('notas_compra.aprobar')
                                    <button class="btn btn-sm btn-outline-success" title="Aprobar"
                                            wire:click="abrirAccion({{ $nota->id_nota_compra }}, 'aprobar')">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </button>
                                    @endcan
                                    @can('notas_compra.eliminar')
                                    <button class="btn btn-sm btn-outline-danger" title="Anular"
                                            wire:click="abrirAccion({{ $nota->id_nota_compra }}, 'anular')">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                    @endcan
                                    @elseif($nota->nota_estado === 'aprobado')
                                    <span class="text-muted small">—</span>
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>No hay notas registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($notas->hasPages())
        <div class="card-footer bg-white border-top py-2">
            {{ $notas->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ══ VISTA NUEVA NOTA ════════════════════════════════════════════════════ --}}
@elseif($vista === 'nueva')
<div class="container-fluid py-3">
    <div class="row g-3">

        {{-- Columna formulario --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" wire:click="volverHistorial">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-file-invoice text-primary me-1"></i>
                        Nueva Nota de {{ $tipo === 'NC' ? 'Crédito' : 'Débito' }}
                    </h5>
                </div>
                <div class="card-body">

                    @if(session('error'))
                    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
                    @endif

                    {{-- Tipo de nota --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Tipo de Nota <span class="text-danger">*</span></label>
                            <select wire:model.live="tipo" class="form-select form-select-sm @error('tipo') is-invalid @enderror">
                                <option value="NC">Nota de Crédito (NC)</option>
                                <option value="DB">Nota de Débito (DB)</option>
                            </select>
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Empresa</label>
                            <select wire:model.live="idEmpresa" class="form-select form-select-sm">
                                <option value="0">— Seleccionar empresa —</option>
                                @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Proveedor <span class="text-danger">*</span></label>
                            <select wire:model.live="idProveedor" class="form-select form-select-sm @error('idProveedor') is-invalid @enderror">
                                <option value="0">— Seleccionar proveedor —</option>
                                @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedores }}">{{ $p->proveedores_nombre }}</option>
                                @endforeach
                            </select>
                            @error('idProveedor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Documento y referencia --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">N° Doc. Proveedor</label>
                            <input type="text" wire:model="numeroDoc" class="form-control form-control-sm" placeholder="Ej: F001-00001234">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">Fecha de Nota <span class="text-danger">*</span></label>
                            <input type="date" wire:model="fechaNota" class="form-control form-control-sm @error('fechaNota') is-invalid @enderror">
                            @error('fechaNota') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary mb-1">OC de Referencia</label>
                            <select wire:model="idOrdenRef" class="form-select form-select-sm">
                                <option value="">— Sin referencia —</option>
                                @foreach($ordenesRef as $oc)
                                <option value="{{ $oc->id_orden_compra }}">
                                    {{ $oc->orden_compra_numero }} — S/ {{ number_format($oc->orden_compra_total, 2) }}
                                </option>
                                @endforeach
                            </select>
                            @if($idProveedor && $ordenesRef->isEmpty())
                            <small class="text-muted">Sin órdenes de referencia para este proveedor.</small>
                            @endif
                        </div>
                    </div>

                    {{-- Motivo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">Motivo <span class="text-danger">*</span></label>
                        <textarea wire:model="motivo" class="form-control form-control-sm @error('motivo') is-invalid @enderror"
                            rows="2" placeholder="Describe el motivo de la nota..."></textarea>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Afecta stock --}}
                    <div class="row g-3 mb-3 align-items-center">
                        <div class="col-auto">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model.live="afectaStock" class="form-check-input" id="afectaStockCheck">
                                <label class="form-check-label fw-semibold small" for="afectaStockCheck">
                                    {{ $tipo === 'NC' ? 'Devuelve productos al proveedor (afecta stock)' : 'Recibe productos adicionales (afecta stock)' }}
                                </label>
                            </div>
                        </div>
                        @if($afectaStock)
                        <div class="col-md-4">
                            <select wire:model="idAlmacen" class="form-select form-select-sm">
                                <option value="0">— Seleccionar almacén —</option>
                                @foreach($almacenes as $alm)
                                <option value="{{ $alm->id_almacen }}">{{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    {{-- Búsqueda de productos --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fa-solid fa-box-open me-1"></i>Agregar Ítems
                        </label>
                        <div class="position-relative">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control"
                                       wire:model.live.debounce.300ms="buscarProducto"
                                       placeholder="Buscar producto por nombre o código..."
                                       autocomplete="off">
                            </div>
                            @error('buscarProducto') <small class="text-danger">{{ $message }}</small> @enderror

                            @if(!empty($resultados))
                            <div class="position-absolute w-100 shadow border rounded-2 bg-white" style="z-index:999; top:100%; max-height:200px; overflow-y:auto;">
                                @foreach($resultados as $prod)
                                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                                     style="cursor:pointer;"
                                     wire:click="agregarProducto({{ $prod->id_pro }}, '{{ addslashes($prod->pro_nombre) }}', {{ $prod->precio }}, {{ $prod->stock }})"
                                     wire:key="res-{{ $prod->id_pro }}">
                                    <div>
                                        <span class="fw-semibold d-block small">{{ $prod->pro_nombre }}</span>
                                        <small class="text-muted">{{ $prod->pro_codigo }}</small>
                                    </div>
                                    <div class="text-end ms-3">
                                        <small class="text-primary fw-semibold d-block">S/ {{ number_format($prod->precio, 2) }}</small>
                                        <small class="text-muted">Stock: {{ number_format($prod->stock, 2) }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tabla de ítems --}}
                    @error('items') <div class="alert alert-warning py-2 small mb-2">{{ $message }}</div> @enderror
                    @if(!empty($items))
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:120px;" class="text-end">Precio Unit.</th>
                                    <th style="width:110px;">Cantidad</th>
                                    <th style="width:110px;" class="text-end">Total</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $idx => $item)
                                <tr wire:key="item-{{ $idx }}">
                                    <td class="small">{{ $item['nombre'] }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">S/</span>
                                            <input type="number" wire:model.live="items.{{ $idx }}.precio"
                                                   wire:change="calcularTotal"
                                                   class="form-control text-end" min="0" step="0.01">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" wire:model.live="items.{{ $idx }}.cantidad"
                                               wire:change="calcularTotal"
                                               class="form-control form-control-sm text-center" min="0.01" step="0.01">
                                    </td>
                                    <td class="text-end fw-semibold small">S/ {{ number_format($item['total'], 2) }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                                wire:click="quitarItem({{ $idx }})">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Observación --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">Observación</label>
                        <textarea wire:model="observacion" class="form-control form-control-sm" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- Columna resumen --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-calculator me-1 text-primary"></i>Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Tipo</span>
                        @if($tipo === 'NC')
                        <span class="badge bg-info">Nota de Crédito</span>
                        @else
                        <span class="badge bg-warning text-dark">Nota de Débito</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Ítems</span>
                        <span class="fw-semibold">{{ count($items) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-5 text-primary">S/ {{ number_format($total, 2) }}</span>
                    </div>
                    @error('total') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror

                    @if($tipo === 'NC')
                    <div class="alert alert-info py-2 mt-3 small">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        La NC <strong>reducirá</strong> el saldo pendiente en cuentas por pagar
                        {{ $afectaStock ? 'y <strong>reducirá el stock</strong> del almacén.' : '(sin impacto en stock).' }}
                    </div>
                    @else
                    <div class="alert alert-warning py-2 mt-3 small">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        La DB <strong>aumentará</strong> el saldo pendiente en cuentas por pagar
                        {{ $afectaStock ? 'y <strong>agregará stock</strong> al almacén.' : '(sin impacto en stock).' }}
                    </div>
                    @endif

                    <div class="mt-3 d-grid gap-2">
                        <button class="btn btn-primary fw-semibold"
                                wire:click="guardar"
                                wire:loading.attr="disabled"
                                {{ (empty($items) || !$idProveedor) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="guardar">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Nota
                            </span>
                            <span wire:loading wire:target="guardar">
                                <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                            </span>
                        </button>
                        <button class="btn btn-outline-secondary" wire:click="volverHistorial">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endif

{{-- ── Loader global ─────────────────────────────────────────────────────── --}}
<div wire:loading wire:target="guardar, ejecutarAccion, abrirAccion, nuevaNota, volverHistorial">
    <x-loader />
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('abrirModalAccion', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAccionNota')).show();
        });
        Livewire.on('cerrarModalAccion', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('modalAccionNota'));
            if (m) m.hide();
        });
    });
</script>
</div>
