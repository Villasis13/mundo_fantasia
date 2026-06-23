<div>
    {{-- ── Alertas ─────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Modal confirmación comunicación de baja ────────────── --}}
    <div class="modal fade" id="modalConfirmacionVentasSunat" wire:ignore.self tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                        Confirmar Comunicación de Baja
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-6 mb-0">{{ $mensajeConfirmacion ?: '¿Confirma la acción seleccionada?' }}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-danger fw-semibold"
                            wire:click="ejecutarConfirmacion"
                            wire:loading.attr="disabled"
                            wire:target="ejecutarConfirmacion">
                        <span wire:loading.remove wire:target="ejecutarConfirmacion">
                            <i class="fa-solid fa-ban me-1"></i> Sí, anular
                        </span>
                        <span wire:loading wire:target="ejecutarConfirmacion">
                            <span class="spinner-border spinner-border-sm me-1"></span> Enviando a SUNAT...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Selector empresa / sucursal ──────────────────────────── --}}
    @if($esSuperAdmin || $esAdmin)
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar por
                    </span>
                    @if($esSuperAdmin || $esAdmin)
                        <div class="d-flex align-items-center gap-1">
                            <i class="fa-solid fa-building text-muted small"></i>
                            <select wire:model.live="empresaSeleccionada"
                                    class="form-select form-select-sm" style="min-width:190px">
                                <option value="0">Seleccionar Empresa</option>
                                @foreach($empresas as $emp)
                                    <option value="{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(count($sucursalesDisponibles) > 0 && ($empresaSeleccionada > 0 || $esAdmin))
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
            </div>
        </div>
    @endif

    {{-- ── Card principal ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="mb-3">
                <h5 class="mb-0 fw-bold">Comprobantes emitidos</h5>
                <small class="text-muted">Consulta y filtra los comprobantes enviados a SUNAT.</small>
            </div>

            @if($esSuperAdmin && !$idEmpresaActiva)
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Selecciona primero una empresa para visualizar los comprobantes.
                </div>
            @else
                <div class="row align-items-end g-2">
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">Tipo de Venta</label>
                        <select wire:model="tipoVenta" class="form-select form-select-sm">
                            <option value="0">TODOS</option>
                            <option value="03">BOLETA</option>
                            <option value="01">FACTURA</option>
                            <option value="07">NOTA DE CRÉDITO</option>
                            <option value="08">NOTA DE DÉBITO</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">Desde:</label>
                        <input type="date" wire:model="fechaInicio" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <label class="form-label mb-1">Hasta:</label>
                        <input type="date" wire:model="fechaFinal" class="form-control form-control-sm">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <button class="btn btn-success btn-sm"
                                wire:click="listar()"
                                wire:loading.attr="disabled"
                                wire:target="listar">
                            <span wire:loading wire:target="listar">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                            </span>
                            <i class="fa fa-search me-1" wire:loading.remove wire:target="listar"></i>
                            Buscar Datos
                        </button>
                    </div>
                    @if($buscar && count($ventas) > 0)
                        @can('historial_ventas_sunat.exportar')
                        <div class="col-lg-4 col-md-12 col-sm-12 text-end">
                            <button type="button"
                                    class="btn btn-outline-success btn-sm shadow-sm"
                                    wire:click="exportarExcel"
                                    wire:loading.attr="disabled"
                                    wire:target="exportarExcel">
                                        <span wire:loading wire:target="exportarExcel">
                                            <span class="spinner-border spinner-border-sm me-1"></span>
                                        </span>
                                        <i class="fas fa-file-excel me-1" wire:loading.remove wire:target="exportarExcel"></i>
                                        Excel
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm shadow-sm"
                                    wire:click="exportarPdf"
                                    wire:loading.attr="disabled"
                                    wire:target="exportarPdf">
                                        <span wire:loading wire:target="exportarPdf">
                                            <span class="spinner-border spinner-border-sm me-1"></span>
                                        </span>
                                        <i class="fas fa-file-pdf me-1" wire:loading.remove wire:target="exportarPdf"></i>
                                        PDF
                            </button>
                        </div>
                        @endcan
                    @endif
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="dataTable_ventas_sunat">
                    <thead>
                    <tr class="encabezado_tabla_color">
                        <th>#</th>
                        <th>Fecha de Emisión</th>
                        <th>Tipo de Envío</th>
                        <th>Comprobante</th>
                        <th>Cliente</th>
                        <th>Forma de Pago</th>
                        <th>Total</th>
                        <th>PDF</th>
                        <th>XML</th>
                        <th>CDR</th>
                        <th>Estado Sunat</th>
                        <th>Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($ventas as $index => $al)
                        @php
                            $stylee = $al->anulado_sunat == 1
                                ? 'color:black;text-align:center;background:#efa6ad;'
                                : 'color:black;text-align:center;';

                            $tipo_comprobante = match($al->venta_tipo) {
                                '03' => 'BOLETA',
                                '01' => 'FACTURA',
                                '07' => 'NOTA DE CRÉDITO',
                                '08' => 'NOTA DE DÉBITO',
                                default => '--'
                            };

                            $mensaje = '';
                            $estilo_mensaje = '';
                            if ($al->venta_estado_sunat == 1) {
                                $mensaje = $al->venta_respuesta_sunat != ''
                                    ? $al->venta_respuesta_sunat
                                    : 'Aceptado por Resumen Diario';
                                $estilo_mensaje = 'color:green;font-size:14px;';
                            }

                            $cliente = $al->id_tipo_documento == 4
                                ? $al->cliente_razonsocial
                                : $al->cliente_nombre;
                        @endphp
                        <tr style="{{ $stylee }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($al->venta_fecha)->format('d-m-Y H:i:s') }}</td>

                            @if($al->venta_tipo_envio == 1)
                                <td>DIRECTO</td>
                            @else
                                <td>
                                    <a href="{{ route('facturacion.detalle_resumen', $al->resumen->id_envio_resumen) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top"
                                       data-bs-title="Ver Resumen Diario" target="_blank">
                                        RESUMEN DIARIO
                                    </a>
                                </td>
                            @endif

                            <td>
                                {{ $tipo_comprobante }}<br>
                                {{ $al->venta_serie . '-' . $al->venta_correlativo }}
                            </td>

                            <td>
                                {{ $al->cliente_numero }}<br>
                                {{ $cliente }}
                            </td>

                            <td>{{ $al->id_formas_pago == 1 ? 'CONTADO' : 'CREDITO' }}</td>

                            <td>{{ $al->simbolo }} {{ $al->venta_total }}</td>

                            <td>
                                <a target="_blank"
                                   href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $al->id_venta]) }}"
                                   data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Imprimir PDF">
                                    <i class="fa-regular fa-file-pdf text-danger fa-lg"></i>
                                </a>
                            </td>

                            @if($al->venta_tipo_envio == 1)
                                <td>
                                    <a target="_blank" href="{{ asset($al->venta_rutaXML) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar XML">
                                        <i class="fa fa-file-text text-primary"></i>
                                    </a>
                                    <a download="{{ $al->venta_rutaXML }}" href="{{ asset($al->venta_rutaXML) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Descargar XML">
                                        <i class="fa fa-download text-primary"></i>
                                    </a>
                                </td>
                                <td>
                                    <a target="_blank" href="{{ asset($al->venta_rutaCDR) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Visualizar CDR">
                                        <i class="fa fa-file text-success"></i>
                                    </a>
                                    <a download="{{ $al->venta_rutaCDR }}" href="{{ asset($al->venta_rutaCDR) }}"
                                       data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Descargar CDR">
                                        <i class="fa fa-download text-success"></i>
                                    </a>
                                </td>
                            @else
                                <td>--</td>
                                <td>--</td>
                            @endif

                            <td style="{{ $estilo_mensaje }}">{{ $mensaje }}</td>

                            <td style="text-align:left">
                                <a target="_blank"
                                   class="btn btn-sm btn-primary m-1"
                                   data-bs-tooltip="tooltip" data-bs-placement="top" data-bs-title="Ver Detalle"
                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $al->id_venta]) }}">
                                    <i class="fa fa-eye text-white"></i>
                                </a>

                                @if($al->anulado_sunat == 0)
                                    @php
                                        $date2 = new DateTime(date('Y-m-d H:i:s'));
                                        $date1 = new DateTime($al->venta_fecha);
                                        $dias  = $date2->diff($date1)->days;
                                    @endphp
                                    @if($dias < 7)
                                        @if($al->venta_tipo != '03')
                                            @if($al->tipo_documento_modificar != '')
                                                @if($al->tipo_documento_modificar == '01')
                                                    @can('historial_ventas_sunat.cambiar_estado')
                                                        <button class="btn btn-sm btn-danger m-1"
                                                                wire:click="confirmarComunicacionBaja({{ $al->id_venta }})"
                                                                data-bs-tooltip="tooltip" data-bs-placement="top"
                                                                data-bs-title="Anulación por Comunicación de Baja">
                                                            <i class="fa fa-ban text-white"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            @else
                                                @php
                                                    $mostrarN = true;
                                                    $mostrarP = true;
                                                    $mostrarD = true;
                                                    $motivoBloqueo = null;

                                                    $buscaNotaElectronica = \Illuminate\Support\Facades\DB::table('ventas')
                                                        ->where([
                                                            ['anulado_sunat', 0],
                                                            ['venta_cancelar', 1],
                                                            ['tipo_documento_modificar', $al->venta_tipo],
                                                            ['serie_modificar', $al->venta_serie],
                                                            ['correlativo_modificar', $al->venta_correlativo],
                                                        ])
                                                        ->whereIn('venta_tipo', ['07', '08'])
                                                        ->first();

                                                    if ($buscaNotaElectronica) {
                                                        $mostrarN = false;
                                                        $motivoBloqueo = $buscaNotaElectronica->venta_tipo === '07'
                                                            ? 'Tiene una Nota de Crédito vinculada'
                                                            : 'Tiene una Nota de Débito vinculada';
                                                    }

                                                    if ($al->id_formas_pago == 2) {
                                                        $cuotas = \Illuminate\Support\Facades\DB::table('ventas_cuotas')
                                                            ->where([['id_venta', $al->id_venta], ['venta_cuota_estado', 1]])
                                                            ->get();
                                                        $pagos = \Illuminate\Support\Facades\DB::table('pagos_cuotas')
                                                            ->whereNull('deleted_at')
                                                            ->whereIn('id_ventas_cuotas', $cuotas->pluck('id_ventas_cuotas'))
                                                            ->exists();
                                                        if ($pagos) {
                                                            $mostrarP = false;
                                                            $motivoBloqueo = $motivoBloqueo ?? 'Tiene cuotas pagadas registradas';
                                                        }
                                                    }

                                                    $fechaEmision = \Carbon\Carbon::parse($al->venta_fecha);
                                                    $diasHabiles  = 0;
                                                    $fecha        = $fechaEmision->copy()->addDay();
                                                    while ($diasHabiles < 7) {
                                                        if ($fecha->isWeekday()) $diasHabiles++;
                                                        $fecha->addDay();
                                                    }
                                                    $plazoVencido = now()->greaterThan($fecha);
                                                    if ($plazoVencido) {
                                                        $mostrarD = false;
                                                        $motivoBloqueo = $motivoBloqueo ?? 'El plazo de 7 días hábiles para la baja ha vencido';
                                                    }

                                                    $puedeAnular = $mostrarN && $mostrarP && $mostrarD;
                                                @endphp
                                                @if($puedeAnular)
                                                    @can('historial_ventas_sunat.cambiar_estado')
                                                        <button class="btn btn-sm btn-danger m-1"
                                                                wire:click="confirmarComunicacionBaja({{ $al->id_venta }})"
                                                                data-bs-tooltip="tooltip" data-bs-placement="top"
                                                                data-bs-title="Anulación por Comunicación de Baja">
                                                            <i class="fa fa-ban text-white"></i>
                                                        </button>
                                                    @endcan
                                                @else
                                                    <button class="btn btn-sm btn-secondary m-1"
                                                            data-bs-tooltip="tooltip" data-bs-placement="top"
                                                            data-bs-title="{{ $motivoBloqueo }}">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        @endif
                                    @endif
                                @endif

                                @if($al->anulado_sunat == 0 && in_array($al->venta_tipo, ['01', '03']))
                                    @can('generar_nota.crear')
                                        <a class="btn btn-sm btn-success m-1"
                                           data-bs-tooltip="tooltip" data-bs-placement="top"
                                           data-bs-title="Generar Nota de Crédito o Débito"
                                           href="{{ route('facturacion.generar_nota', $al->id_venta) }}"
                                           target="_blank">
                                            <i class="fa fa-clipboard text-white"></i>
                                        </a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                @if(!$buscar)
                                    Utilice los filtros y haga clic en <strong>Buscar Datos</strong> para ver los comprobantes.
                                @else
                                    No se encontraron comprobantes con los filtros aplicados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="listar">
        <x-loader />
    </div>
</div>

@script
    <script>
        // ── Modal de confirmación ─────────────────────────────────
        $wire.on('abrirModalVentasSunat', () => {
            const el    = document.getElementById('modalConfirmacionVentasSunat');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();
        });

        $wire.on('cerrarModalVentasSunat', () => {
            const el    = document.getElementById('modalConfirmacionVentasSunat');
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        });

        document.getElementById('modalConfirmacionVentasSunat')
            .addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });

        // ── Exportables ───────────────────────────────────────────
        $wire.on('abrirNuevaPestana', (event) => {
            window.open(event.url, '_blank');
        });
    </script>
@endscript
