<div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>
                        Mercadería en Tránsito
                    </h5>
                    <small class="text-muted">Órdenes de compra y transferencias pendientes de recepción.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                @can('mercaderia_transito.exportar')
                <button wire:click="imprimirPdf" class="btn btn-sm btn-outline-danger">
                    <i class="fa fa-file-pdf me-1"></i> PDF
                </button>
                <button wire:click="imprimirExcel" class="btn btn-sm btn-outline-success">
                    <i class="fa fa-file-excel me-1"></i> Excel
                </button>
                @endcan
            </div>

            <div class="row g-2 align-items-end mt-3">
                <div class="col-auto">
                    <select wire:model.live="porPagina" class="form-select form-select-sm" style="width:auto;">
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                </div>

                @if($esSuperAdmin)
                <div class="col-auto">
                    <select wire:model.live="filtroEmpresa" class="form-select form-select-sm" style="min-width:170px;">
                        <option value="0">— Seleccione empresa —</option>
                        @foreach($empresas as $emp)
                            <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($sucursalesDisponibles->isNotEmpty())
                <div class="col-auto">
                    <select wire:model.live="filtroSucursal" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="0">Todos los establecimientos</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-tabs mt-3 border-0">
                <li class="nav-item">
                    <button class="nav-link {{ $tab === 'compras' ? 'active fw-semibold' : 'text-muted' }}"
                            wire:click="$set('tab','compras')">
                        <i class="fa-solid fa-cart-flatbed me-1"></i>
                        Órdenes de Compra
                        @if($comprasTransito->total() > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $comprasTransito->total() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $tab === 'transferencias' ? 'active fw-semibold' : 'text-muted' }}"
                            wire:click="$set('tab','transferencias')">
                        <i class="fa-solid fa-truck-moving me-1"></i>
                        Transferencias
                        @if($transferenciasTransito->total() > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $transferenciasTransito->total() }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">

            {{-- Tab: Órdenes de Compra --}}
            @if($tab === 'compras')
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3">#</th>
                            <th>N° Orden</th>
                            <th>Proveedor</th>
                            <th>Sucursal Destino</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comprasTransito as $index => $oc)
                        <tr>
                            <td class="ps-3 text-muted fw-semibold">{{ $comprasTransito->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $oc->orden_compra_numero }}</td>
                            <td>{{ $oc->proveedores_nombre }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-75">{{ $oc->sucursal_nombre ?? '—' }}</span>
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($oc->orden_compra_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-end fw-semibold">S/ {{ number_format($oc->orden_compra_total ?? 0, 2) }}</td>
                            <td class="text-center">
                                @if($oc->orden_compra_estado === 'pendiente')
                                    <span class="badge bg-secondary">Pendiente</span>
                                @else
                                    <span class="badge bg-warning text-dark">En Tránsito</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('logistica.ordenCompraDetalle') }}?ordenCompra={{ $oc->id_orden_compra }}"
                                   class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                @if($esSuperAdmin && $filtroEmpresa == 0)
                                    <i class="fa-solid fa-building fa-2x mb-2 d-block opacity-25"></i>
                                    Seleccione una empresa.
                                @else
                                    <i class="fa-solid fa-check-circle fa-2x mb-2 d-block opacity-25 text-success"></i>
                                    No hay órdenes de compra pendientes de recepción.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($comprasTransito->hasPages())
            <div class="px-3 py-2 border-top">{{ $comprasTransito->links() }}</div>
            @endif

            {{-- Tab: Transferencias --}}
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="encabezado_tabla_color">
                            <th class="ps-3">#</th>
                            <th>N° Transferencia</th>
                            <th>Origen → Destino</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferenciasTransito as $index => $trf)
                        <tr>
                            <td class="ps-3 text-muted fw-semibold">{{ $transferenciasTransito->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $trf->transferencia_numero }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-75">{{ $trf->origen_nombre }}</span>
                                <i class="fa-solid fa-arrow-right mx-1 text-muted small"></i>
                                <span class="badge bg-info text-white">{{ $trf->destino_nombre }}</span>
                            </td>
                            <td><small>{{ \Carbon\Carbon::parse($trf->transferencia_fecha)->format('d/m/Y') }}</small></td>
                            <td class="text-muted small">{{ $trf->nombre_users }}</td>
                            <td class="text-center">
                                @if($trf->transferencia_estado === 'pendiente')
                                    <span class="badge bg-secondary">Pendiente</span>
                                @else
                                    <span class="badge bg-warning text-white">En Tránsito</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                @if($esSuperAdmin && $filtroEmpresa == 0)
                                    <i class="fa-solid fa-building fa-2x mb-2 d-block opacity-25"></i>
                                    Seleccione una empresa.
                                @else
                                    <i class="fa-solid fa-check-circle fa-2x mb-2 d-block opacity-25 text-success"></i>
                                    No hay transferencias en tránsito.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transferenciasTransito->hasPages())
            <div class="px-3 py-2 border-top">{{ $transferenciasTransito->links() }}</div>
            @endif
            @endif
        </div>
    </div>

    <script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('abrirEnlaces', e => window.open(e.url, '_blank'));
    });
    </script>
</div>
