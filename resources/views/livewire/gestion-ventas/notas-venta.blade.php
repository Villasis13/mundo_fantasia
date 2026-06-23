<div>
    {{-- ── Modal Anulación ──────────────────────────────────────── --}}
    <div class="modal fade" id="modalAnularNotaVenta" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Anulación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="fs-5 mb-1">
                        ¿Está seguro que desea <strong>anular esta Nota de Venta</strong>?
                    </p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>

                @if (session('errorAnular'))
                <div class="alert alert-danger mt-3 mb-3 mx-3">
                    {{ session('errorAnular') }}
                </div>
                @endif

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        No, cancelar
                    </button>
                    @can('historial_notas_venta.cambiar_estado')
                    <button type="button" class="btn btn-danger" wire:click="anularNotaVenta()"
                            wire:loading.attr="disabled" wire:target="anularNotaVenta">
                        <span wire:loading wire:target="anularNotaVenta">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        Sí, anular
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tarjeta principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-2">
                <h5 class="mb-0 fw-bold">Notas de venta</h5>
                <small class="text-muted">
                    Visualiza y filtra el historial de las notas de venta registradas en el sistema.
                </small>
            </div>

            {{-- ── Filtros empresa / sucursal ───────────────────── --}}
            @if($esSuperAdmin)
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pt-2 border-top">
                    <span class="text-muted small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar por
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-building text-muted small"></i>
                        <select wire:model.live="empresaSeleccionada"
                                class="form-select form-select-sm" style="min-width:190px">
                            <option value="0">Todas las empresas</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($empresaSeleccionada > 0 && $sucursalesDisponibles->isNotEmpty())
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-code-branch text-muted small"></i>
                        <select wire:model.live="sucursalSeleccionada"
                                class="form-select form-select-sm" style="min-width:190px">
                            <option value="0">Todas las sucursales</option>
                            @foreach($sucursalesDisponibles as $suc)
                                <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            @elseif($esAdmin && $sucursalesDisponibles->isNotEmpty())
                <div class="d-flex align-items-center gap-2 mb-3 pt-2 border-top">
                    <i class="fa-solid fa-code-branch text-muted small"></i>
                    <select wire:model.live="sucursalSeleccionada"
                            class="form-select form-select-sm" style="max-width:280px">
                        <option value="0">Todas las sucursales</option>
                        @foreach($sucursalesDisponibles as $suc)
                            <option value="{{ $suc->id_sucursal }}">{{ $suc->sucursal_nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- ── Filtros de búsqueda ──────────────────────────── --}}
            <div class="row align-items-end g-2">
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <label class="form-label mb-1" for="idCliente">Cliente:</label>
                    @if($esSuperAdmin && !$idEmpresaActiva)
                        <select class="form-select form-select-sm" disabled>
                            <option>— Selecciona primero una empresa —</option>
                        </select>
                    @else
                        <select id="idCliente" wire:model="idCliente"
                                class="form-select form-select-sm @error('idCliente') is-invalid @enderror">
                            <option value="">Todos</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id_clientes }}">{{ $cli->cliente_razonsocial }}</option>
                            @endforeach
                        </select>
                        @error('idCliente') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    @endif
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label mb-1" for="desde">Desde:</label>
                    <input type="date" id="desde" wire:model="desde"
                           class="form-control form-control-sm @error('desde') is-invalid @enderror">
                    @error('desde') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-lg-2 col-md-2 col-sm-12">
                    <label class="form-label mb-1" for="hasta">Hasta:</label>
                    <input type="date" id="hasta" wire:model="hasta" min="{{ $desde }}"
                           class="form-control form-control-sm @error('hasta') is-invalid @enderror">
                    @error('hasta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 d-flex justify-content-between align-items-end">
                    <button class="btn btn-primary btn-sm" wire:click="listarRegistros()">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                    </button>
                    @can('historial_notas_venta.exportar')
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-success btn-sm" wire:click="imprimirExcel()">
                            <i class="fa-solid fa-file-excel me-1"></i>Excel
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="imprimirPdf()">
                            <i class="fa-solid fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success alert-dismissible mb-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger alert-dismissible mb-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="encabezado_tabla_color text-center">
                            <th>#</th>
                            <th>Fecha de Emisión</th>
                            <th>Serie y Correlativo</th>
                            <th>Cliente</th>
                            <th>Registrado Por</th>
                            <th class="text-end">Total</th>
                            <th>PDF</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $i => $list)
                        @php
                            $anulado = $list->anulado_sunat == 1;
                            $cliente = $list->id_tipo_documento == 4
                                ? $list->cliente_razonsocial
                                : $list->cliente_nombre;
                        @endphp
                        <tr style="{{ $anulado ? 'background:#efa6ad' : '' }}">
                            <td class="text-center">{{ $registros->firstItem() + $i }}</td>
                            <td>
                                <span class="d-block small">{{ date('d/m/Y', strtotime($list->venta_fecha)) }}</span>
                                <span class="d-block text-muted" style="font-size:.78rem">{{ date('H:i:s', strtotime($list->venta_fecha)) }}</span>
                            </td>
                            <td class="fw-semibold">{{ $list->venta_serie }}-{{ $list->venta_correlativo }}</td>
                            <td>
                                <div class="small">{{ $list->cliente_numero }}</div>
                                <div class="fw-semibold small">{{ $cliente }}</div>
                            </td>
                            <td class="small">{{ $list->nombre_users }}</td>
                            <td class="text-end fw-semibold">
                                {{ $list->simbolo }}{{ number_format($list->venta_total, 2) }}
                            </td>
                            <td class="text-center">
                                <a target="_blank"
                                   href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $list->id_venta]) }}"
                                   title="Imprimir PDF">
                                    <i class="fa-regular fa-file-pdf text-danger fa-lg"></i>
                                </a>
                            </td>
                            <td class="text-center">
                                <a target="_blank" title="Ver detalle"
                                   class="btn btn-sm btn-info"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $list->id_venta]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @can('historial_notas_venta.cambiar_estado')
                                @if(!$anulado)
                                <button title="Anular"
                                        class="btn btn-sm btn-danger ms-1"
                                        wire:click="ponerIdAnularNotaVenta({{ $list->id_venta }})"
                                        data-bs-toggle="modal" data-bs-target="#modalAnularNotaVenta">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar</strong> para ver las notas de venta.
                                @else
                                    No se encontraron registros con los filtros aplicados.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($registros instanceof \Illuminate\Pagination\LengthAwarePaginator && $registros->total() > 0)
                {{ $registros->links(data: ['scrollTo' => false]) }}
            @endif
        </div>
    </div>

    <div wire:loading wire:target="listarRegistros">
        <x-loader />
    </div>
</div>
@script
<script>
    $wire.on('hidemodal', () => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAnularNotaVenta'));
        if (modal) modal.hide();
    });
    $wire.on('abrirEnlaces', (event) => {
        window.open(event.url, '_blank');
    });
</script>
@endscript
