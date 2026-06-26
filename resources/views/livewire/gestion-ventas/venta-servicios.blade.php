<div>

    {{-- ══════ CAJA SIN APERTURA ══════ --}}
    @if (!$validarCaja)
        <div class="alert alert-warning d-flex align-items-center gap-2 mt-2">
            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
            <span>Antes de continuar, debe <a href="{{ route('admin') }}" class="alert-link fw-bold">abrir la caja</a> para realizar ventas.</span>
        </div>
    @endif

    @if ($validarCaja && !$idSucursal)
        <div class="alert alert-warning mt-2">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            La caja activa no tiene sucursal asignada. Contacte al administrador.
        </div>
    @endif

    @if ($validarCaja && $idSucursal)

        {{-- ══════ FLASH MESSAGES ══════ --}}
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ══════ ENCABEZADO ══════ --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rv-header-icon" style="background:#E6F1FB;color:#185FA5;">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <div class="rv-header-title me-2">Venta de Servicios</div>
                    <span class="rv-header-sub">
                        <i class="fa-solid fa-store" style="font-size:10px;"></i>
                        {{ $nombreSucursal }}
                    </span>
                    @if($nombreCaja)
                        <span class="rv-header-sep">·</span>
                        <span class="rv-header-caja">
                            <i class="fa-solid fa-cash-register" style="font-size:10px;"></i>
                            {{ $nombreCaja }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3">

            {{-- ══ COLUMNA IZQUIERDA ══ --}}
            <div class="col-12 col-lg-8 d-flex flex-column gap-3">

                {{-- ── CLIENTE ── --}}
                <div class="rv-card">
                    <div class="rv-ch">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rv-ic" style="background:#E6F1FB;">
                                <i class="fa-solid fa-user" style="color:#185FA5;font-size:12px;"></i>
                            </div>
                            <span class="rv-lbl">Cliente</span>
                        </div>
                        <button type="button" class="rv-pill-btn rv-pill-blue" wire:click="abrirModalClientes">
                            <i class="fa-solid fa-address-book" style="font-size:11px;"></i>
                            Lista de clientes
                        </button>
                    </div>
                    <div class="rv-cb">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-sm-4 col-md-3">
                                <label class="rv-fl">Tipo de documento</label>
                                <select class="rv-select" wire:model.live="idTipoDocumento">
                                    <option value="2">DNI</option>
                                    <option value="4">RUC</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-4 col-md-4">
                                <label class="rv-fl">N° documento</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="rv-input flex-grow-1"
                                           wire:model.defer="numDocumento"
                                           placeholder="{{ $idTipoDocumento == '2' ? '8 dígitos' : '11 dígitos' }}"
                                           maxlength="{{ $idTipoDocumento == '2' ? '8' : '11' }}">
                                    <button class="rv-sq-btn flex-shrink-0" type="button"
                                            wire:click="consultarDocumento"
                                            wire:loading.attr="disabled" wire:target="consultarDocumento">
                                        <span wire:loading wire:target="consultarDocumento">
                                            <i class="fa-solid fa-spinner fa-spin" style="font-size:11px;color:#185FA5;"></i>
                                        </span>
                                        <span wire:loading.remove wire:target="consultarDocumento">
                                            <i class="fa-solid fa-magnifying-glass" style="font-size:11px;color:#185FA5;"></i>
                                        </span>
                                    </button>
                                </div>
                                @if($mensajeConsulta)
                                    <div class="rv-msg rv-msg-{{ $tipoMensajeConsulta }}">
                                        <span class="rv-dot"></span>{{ $mensajeConsulta }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-12 col-sm-4 col-md-5">
                                <label class="rv-fl">{{ $idTipoDocumento == '4' ? 'Razón social' : 'Nombre' }}</label>
                                <input type="text" class="rv-input" wire:model.defer="nombreCliente" placeholder="Nombre del cliente">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <label class="rv-fl">Teléfono</label>
                                <input type="text" class="rv-input" wire:model.defer="telefonoCliente" placeholder="Opcional">
                            </div>
                            <div class="col-12 col-sm-8">
                                <label class="rv-fl">Dirección</label>
                                <input type="text" class="rv-input" wire:model.defer="direccionCliente" placeholder="Opcional">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── COMPROBANTE ── --}}
                <div class="rv-card">
                    <div class="rv-ch">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rv-ic" style="background:#EAF3DE;">
                                <i class="fa-solid fa-receipt" style="color:#3B6D11;font-size:12px;"></i>
                            </div>
                            <span class="rv-lbl">Comprobante</span>
                        </div>
                    </div>
                    <div class="rv-cb">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-sm-5 col-md-5">
                                <label class="rv-fl">Tipo de comprobante</label>
                                <div class="rv-btn-group">
                                    <input type="radio" class="rv-rh" id="vs-tc-03" name="vs_tipo_comprobante" value="03" wire:model.live="tipoComprobante">
                                    <label for="vs-tc-03" class="rv-btn {{ $tipoComprobante === '03' ? 'rv-bt-blue' : '' }}">
                                        <i class="fa-solid fa-receipt" style="font-size:10px;"></i> Boleta
                                    </label>
                                    <input type="radio" class="rv-rh" id="vs-tc-01" name="vs_tipo_comprobante" value="01" wire:model.live="tipoComprobante">
                                    <label for="vs-tc-01" class="rv-btn {{ $tipoComprobante === '01' ? 'rv-bt-blue' : '' }}">
                                        <i class="fa-solid fa-file-invoice" style="font-size:10px;"></i> Factura
                                    </label>
                                    <input type="radio" class="rv-rh" id="vs-tc-20" name="vs_tipo_comprobante" value="20" wire:model.live="tipoComprobante">
                                    <label for="vs-tc-20" class="rv-btn {{ $tipoComprobante === '20' ? 'rv-bt-blue' : '' }}">
                                        <i class="fa-solid fa-file-lines" style="font-size:10px;"></i> N.Venta
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-3 col-md-3">
                                <label class="rv-fl">Serie</label>
                                @if(!empty($series))
                                    <select class="rv-select" wire:model.live="idSerie">
                                        @foreach($series as $s)
                                            <option value="{{ $s->id_serie }}">{{ $s->serie }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="rv-input rv-warn">Sin series</div>
                                @endif
                            </div>
                            <div class="col-6 col-sm-2 col-md-2">
                                <label class="rv-fl">Correlativo</label>
                                <div class="rv-input rv-corr">{{ $correlativo }}</div>
                            </div>
                            <div class="col-6 col-sm-2 col-md-2">
                                <label class="rv-fl">IGV</label>
                                <select class="rv-select" wire:model.live="porcentajeIgv">
                                    <option value="18.0">18%</option>
                                    <option value="10.5">10.5%</option>
                                    <option value="0">Sin IGV</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── PAGO ── --}}
                <div class="rv-card">
                    <div class="rv-ch">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rv-ic" style="background:#FAEEDA;">
                                <i class="fa-solid fa-money-bill-wave" style="color:#854F0B;font-size:12px;"></i>
                            </div>
                            <span class="rv-lbl">Pago</span>
                        </div>
                    </div>
                    <div class="rv-cb">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-sm-4">
                                <label class="rv-fl">Forma de pago</label>
                                <div class="rv-btn-group">
                                    <input type="radio" class="rv-rh" id="vs-fp-1" name="vs_formas_pago" value="1" wire:model.live="idFormasPago">
                                    <label for="vs-fp-1" class="rv-btn {{ $idFormasPago == 1 ? 'rv-bt-green' : '' }}">
                                        <i class="fa-solid fa-money-bill" style="font-size:10px;"></i> Contado
                                    </label>
                                    <input type="radio" class="rv-rh" id="vs-fp-2" name="vs_formas_pago" value="2" wire:model.live="idFormasPago">
                                    <label for="vs-fp-2" class="rv-btn {{ $idFormasPago == 2 ? 'rv-bt-amber' : '' }}">
                                        <i class="fa-solid fa-calendar-days" style="font-size:10px;"></i> Crédito
                                    </label>
                                </div>
                            </div>

                            @if($idFormasPago == 1)
                                <div class="col-12 col-sm-4">
                                    <label class="rv-fl">Tipo de pago</label>
                                    <select class="rv-select" wire:model.live="idTipoPago">
                                        <option value="">Seleccionar...</option>
                                        @foreach($tiposPago as $tp)
                                            <option value="{{ $tp->id_tipo_pago }}">{{ $tp->tipo_pago_nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <label class="rv-fl">Pagó con</label>
                                    <div class="d-flex">
                                        <span class="rv-prefix">S/</span>
                                        <input type="text" inputmode="decimal"
                                               class="rv-input rv-money flex-grow-1"
                                               placeholder="0.00"
                                               wire:model="pagoCliente"
                                               wire:change="actualizarPagoCliente">
                                    </div>
                                </div>
                            @endif

                            @if($idFormasPago == 2)
                                <div class="col-12 col-sm-8">
                                    <label class="rv-fl d-none d-sm-block">&nbsp;</label>
                                    <button type="button" class="rv-cuotas-btn w-100"
                                            data-bs-toggle="modal" data-bs-target="#vsModalCuotas">
                                        <i class="fa-solid fa-calendar-days me-1" style="font-size:11px;"></i>
                                        Gestionar cuotas
                                        @if(count($cuotas) > 0)
                                            <span class="rv-badge rv-b-blue ms-2">{{ count($cuotas) }}</span>
                                        @endif
                                    </button>
                                    @if(count($cuotas) > 0)
                                        <div class="rv-cuotas-mini mt-2">
                                            <div class="rv-cm-row"><span>Cuotas:</span><strong>{{ count($cuotas) }}</strong></div>
                                            <div class="rv-cm-row"><span>Total:</span><strong>S/ {{ number_format($this->sumaCuotas, 2) }}</strong></div>
                                            <div class="rv-cm-row {{ $this->saldoCuotas == 0 ? 'rv-ok' : 'rv-err' }}">
                                                <span>{{ $this->saldoCuotas == 0 ? 'Cuadrado ✓' : 'Diferencia:' }}</span>
                                                @if($this->saldoCuotas != 0)
                                                    <strong>S/ {{ number_format(abs($this->saldoCuotas), 2) }}</strong>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── DETALLE MANUAL ── --}}
                <div class="rv-card">
                    <div class="rv-ch flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rv-ic" style="background:#EAF3DE;">
                                <i class="fa-solid fa-pen-to-square" style="color:#3B6D11;font-size:12px;"></i>
                            </div>
                            <span class="rv-lbl">Detalle (escritura manual)</span>
                            <span class="rv-badge rv-b-blue ms-1">
                                {{ count($lineas) }} línea{{ count($lineas) !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <button type="button" class="rv-pill-btn rv-pill-green" wire:click="agregarLinea">
                            <i class="fa-solid fa-plus" style="font-size:11px;"></i> Agregar línea
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="rv-tbl">
                            <thead>
                            <tr>
                                <th class="rv-th" style="min-width:220px;">Descripción</th>
                                <th class="rv-th text-center" style="min-width:140px;">Afectación</th>
                                <th class="rv-th text-center" style="min-width:80px;">Cantidad</th>
                                <th class="rv-th text-end" style="min-width:100px;">Precio unit.</th>
                                <th class="rv-th text-end" style="min-width:90px;">Total</th>
                                <th class="rv-th text-center" style="width:40px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($lineas as $idx => $linea)
                                @php
                                    $precio   = (float)($linea['precio'] ?? 0);
                                    $cantidad = (float)($linea['cantidad'] ?? 0);
                                    $tipoAfec = (int)($linea['id_tipo_afectacion'] ?? 1);
                                    $tasa     = $porcentajeIgv / 100;
                                    $sub      = round($precio * $cantidad, 2);
                                    $total    = $tipoAfec === 1 ? round($sub + $sub * $tasa, 2) : $sub;
                                @endphp
                                <tr class="rv-tr" wire:key="vs-linea-{{ $idx }}">
                                    <td class="rv-td">
                                        <input type="text" class="rv-input"
                                               placeholder="Ej. Servicio de mantenimiento..."
                                               wire:model.live.debounce.400ms="lineas.{{ $idx }}.descripcion">
                                    </td>
                                    <td class="rv-td text-center">
                                        <select class="rv-select" wire:model.live="lineas.{{ $idx }}.id_tipo_afectacion">
                                            <option value="1">Gravado</option>
                                            <option value="2">Exonerado</option>
                                            <option value="3">Inafecto</option>
                                        </select>
                                    </td>
                                    <td class="rv-td text-center">
                                        <input type="number" class="rv-ti" style="text-align:center;width:70px;"
                                               min="0" step="0.01"
                                               wire:model.live.debounce.400ms="lineas.{{ $idx }}.cantidad">
                                    </td>
                                    <td class="rv-td text-end">
                                        <input type="number" class="rv-ti" style="text-align:right;"
                                               min="0" step="0.01" placeholder="0.00"
                                               wire:model.live.debounce.400ms="lineas.{{ $idx }}.precio">
                                    </td>
                                    <td class="rv-td text-end">
                                        <span style="font-size:13px;font-weight:700;color:#212529;">{{ number_format($total, 2) }}</span>
                                    </td>
                                    <td class="rv-td text-center">
                                        <button class="rv-del" type="button" wire:click="quitarLinea({{ $idx }})" title="Quitar">
                                            <i class="fa-solid fa-xmark" style="font-size:12px;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>{{-- /col izquierda --}}

            {{-- ══ COLUMNA DERECHA: Resumen ══ --}}
            <div class="col-12 col-lg-4">
                <div class="rv-card rv-sticky-resumen">
                    <div class="rv-ch">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rv-ic" style="background:#f0f0ee;">
                                <i class="fa-solid fa-calculator" style="color:#5F5E5A;font-size:12px;"></i>
                            </div>
                            <span class="rv-lbl">Resumen</span>
                        </div>
                    </div>
                    <div class="rv-cb">
                        <div class="rv-tr-row"><span>Op. gravada</span><span>S/ {{ number_format($this->totales['gravada'], 2) }}</span></div>
                        <div class="rv-tr-row"><span>IGV {{ $porcentajeIgv }}%</span><span>S/ {{ number_format($this->totales['igv'], 2) }}</span></div>
                        <div class="rv-tr-row"><span>Exonerada</span><span>S/ {{ number_format($this->totales['exonerada'], 2) }}</span></div>
                        <div class="rv-tr-row"><span>Inafectada</span><span>S/ {{ number_format($this->totales['inafecta'], 2) }}</span></div>
                        @if($idFormasPago == 1)
                            <div class="rv-tr-row" style="border-top:1px solid #e9ecef;margin-top:4px;padding-top:8px;">
                                <span>Pago con</span><span>S/ {{ number_format((float)$this->pagoCliente, 2) }}</span>
                            </div>
                            <div class="rv-tr-row">
                                <span>Vuelto</span>
                                <span style="color:{{ $this->vuelto > 0 ? '#3B6D11' : '#6c757d' }};font-weight:700;">
                                    S/ {{ number_format($this->vuelto, 2) }}
                                </span>
                            </div>
                        @endif
                        <div class="rv-total-final">
                            <span>Total</span>
                            <span>S/ {{ number_format($this->totales['total'], 2) }}</span>
                        </div>
                        <button class="rv-cobrar-btn" type="button"
                                wire:click="guardar"
                                wire:loading.attr="disabled"
                                wire:target="guardar"
                                @if(empty($series)) disabled @endif>
                            <span wire:loading wire:target="guardar">
                                <i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...
                            </span>
                            <span wire:loading.remove wire:target="guardar">
                                <i class="fa-solid fa-money-bill-wave me-2"></i>COBRAR
                            </span>
                        </button>
                        @if(empty($series))
                            <p class="text-center mt-2 mb-0" style="font-size:11px;color:#dc3545;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Configure series para esta caja.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

        </div>{{-- /row --}}

    @endif

    {{-- ══════ MODAL CLIENTES ══════ --}}
    @if($mostrarModalClientes)
        <div class="rv-overlay" wire:click.self="cerrarModalClientes">
            <div class="rv-modal">
                <div class="rv-mh">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rv-ic" style="background:#E6F1FB;">
                            <i class="fa-solid fa-address-book" style="color:#185FA5;font-size:12px;"></i>
                        </div>
                        <span style="font-size:14px;font-weight:700;color:#212529;">Lista de Clientes</span>
                    </div>
                    <button type="button" class="rv-icon-btn" wire:click="cerrarModalClientes">
                        <i class="fa-solid fa-xmark" style="font-size:13px;color:#6c757d;"></i>
                    </button>
                </div>
                <div class="rv-ms">
                    <i class="fa-solid fa-magnifying-glass rv-si"></i>
                    <input type="text" class="rv-si-input"
                           wire:model.live.debounce.300ms="buscarCliente"
                           placeholder="Buscar por nombre, razón social o N° documento..."
                           autocomplete="off">
                </div>
                <div class="rv-mb">
                    @if($clientes && $clientes->count() > 0)
                        @foreach($clientes as $cli)
                            @php
                                $nombre = $cli->id_tipo_documento == 4
                                    ? ($cli->cliente_razonsocial ?? $cli->cliente_nombre ?? '')
                                    : ($cli->cliente_nombre ?? '');
                            @endphp
                            <div class="rv-cli-item"
                                 wire:click="seleccionarCliente({{ $cli->id_clientes }})"
                                 wire:key="vs-cli-{{ $cli->id_clientes }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rv-av {{ $cli->id_tipo_documento == 4 ? 'rv-av-blue' : 'rv-av-green' }}">
                                        {{ strtoupper(substr($nombre, 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="text-truncate" style="font-size:13px;font-weight:700;color:#212529;">{{ $nombre }}</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                            <span class="rv-badge {{ $cli->id_tipo_documento == 4 ? 'rv-b-blue' : 'rv-b-green' }}">
                                                {{ $cli->id_tipo_documento == 4 ? 'RUC' : 'DNI' }}
                                            </span>
                                            <span style="font-size:12px;color:#6c757d;">{{ $cli->cliente_numero }}</span>
                                        </div>
                                    </div>
                                    <small class="text-muted flex-shrink-0 d-none d-sm-block">
                                        Seleccionar <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @elseif($clientes && $clientes->count() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-user-slash fa-2x d-block mb-2 opacity-25"></i>
                            <small>No se encontraron clientes.</small>
                        </div>
                    @endif
                </div>
                @if($clientes && $clientes->hasPages())
                    <div class="rv-mf">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            @if($clientes->onFirstPage())
                                <span class="rv-pg rv-pg-dis"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
                            @else
                                <button class="rv-pg" wire:click="previousPage('clientesPage')"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></button>
                            @endif
                            <span class="rv-pg rv-pg-act">{{ $clientes->currentPage() }}</span>
                            @if($clientes->hasMorePages())
                                <button class="rv-pg" wire:click="nextPage('clientesPage')"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></button>
                            @else
                                <span class="rv-pg rv-pg-dis"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $clientes->firstItem() }}–{{ $clientes->lastItem() }} de {{ $clientes->total() }}</small>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════ MODAL CUOTAS ══════ --}}
    <div class="modal fade" id="vsModalCuotas" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius:12px;border:1px solid #e9ecef;">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;background:#fafafa;border-radius:12px 12px 0 0;">
                    <h5 class="modal-title" style="font-size:14px;font-weight:700;">
                        <i class="fa-solid fa-calendar-days me-2" style="color:#185FA5;"></i>Gestión de Cuotas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-sm-5">
                            <label class="form-label mb-1 small fw-bold text-muted">Monto total a financiar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="text" class="form-control fw-bold" style="color:#185FA5;" value="{{ number_format($this->totales['total'], 2) }}" readonly>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label mb-1 small fw-bold text-muted">N° de cuotas</label>
                            <input type="number" class="form-control form-control-sm" min="1" max="24" wire:model.live="numeroCuotas">
                        </div>
                        <div class="col-sm-3 d-grid">
                            <button type="button" class="btn btn-primary btn-sm" wire:click="generarCuotas">Generar</button>
                        </div>
                    </div>
                    @if(count($cuotas))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                <tr><th style="width:50px;">#</th><th>Monto (S/)</th><th>Fecha de pago</th><th style="width:50px;"></th></tr>
                                </thead>
                                <tbody>
                                @foreach($cuotas as $i => $cuota)
                                    <tr wire:key="vs-cuota-{{ $i }}">
                                        <td class="align-middle fw-bold text-muted">{{ $i + 1 }}</td>
                                        <td><input type="number" class="form-control form-control-sm" step="0.01" min="0" wire:model.live="cuotas.{{ $i }}.monto"></td>
                                        <td><input type="date" class="form-control form-control-sm" wire:model.live="cuotas.{{ $i }}.fecha_pago"></td>
                                        <td><button type="button" class="btn btn-outline-danger btn-sm" wire:click="eliminarCuota({{ $i }})"><i class="fa-solid fa-trash fa-xs"></i></button></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-calendar-xmark fa-2x d-block mb-2 opacity-25"></i>
                            <small>Ingrese el número de cuotas y presione <strong>Generar</strong>.</small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer d-flex justify-content-between" style="border-top:1px solid #f0f0f0;">
                    <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>La suma de cuotas debe igualar el total.</small>
                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>

@script
    <script>
        Livewire.on('ventaGuardada', ({ ventaId }) => {
            window.location.href = ruta_global + 'Gestionventas/venta_detalle/?venta_id=' + ventaId;
        });
    </script>
@endscript

<style>
    .rv-header-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
    .rv-header-title { font-size:15px; font-weight:700; color:#1a1a1a; }
    .rv-header-sub   { font-size:12px; color:#6c757d; display:flex; align-items:center; gap:4px; }
    .rv-header-sep   { font-size:12px; color:#adb5bd; }
    .rv-header-caja  { font-size:12px; font-weight:700; color:#185FA5; background:#E6F1FB; padding:2px 8px; border-radius:10px; display:flex; align-items:center; gap:4px; }
    .rv-icon-btn { background:transparent; border:none; cursor:pointer; padding:0; display:flex; align-items:center; }
    .rv-sticky-resumen { position:sticky; top:16px; }
    .rv-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; }
    .rv-ch   { padding:10px 16px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; gap:8px; background:#fafafa; }
    .rv-ic   { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rv-lbl  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6c757d; }
    .rv-cb   { padding:14px 16px; }
    .rv-fl { font-size:11px; color:#6c757d; margin-bottom:4px; display:block; font-weight:600; }
    .rv-input, .rv-select { width:100%; background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; padding:7px 10px; font-size:13px; color:#212529; outline:none; transition:border-color .15s, box-shadow .15s; font-family:inherit; }
    .rv-input:focus, .rv-select:focus { border-color:#378ADD; box-shadow:0 0 0 3px rgba(55,138,221,.12); }
    .rv-corr   { font-weight:700; text-align:center; color:#185FA5; letter-spacing:.04em; background:#f0f4f8; cursor:default; }
    .rv-warn   { color:#854F0B; background:#FAEEDA; border-color:#FAC775; }
    .rv-prefix { background:#f0f4f8; border:1px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px; padding:7px 10px; font-size:13px; color:#6c757d; font-weight:700; flex-shrink:0; }
    .rv-money  { border-radius:0 8px 8px 0 !important; border-left:none !important; }
    .rv-sq-btn { width:34px; height:35px; background:#E6F1FB; border:1px solid #B5D4F4; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; transition:background .15s; }
    .rv-sq-btn:hover { background:#B5D4F4; }
    .rv-msg { display:flex; align-items:center; gap:5px; margin-top:5px; font-size:11px; }
    .rv-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .rv-msg-success { color:#3B6D11; } .rv-msg-success .rv-dot { background:#639922; }
    .rv-msg-error   { color:#791F1F; } .rv-msg-error   .rv-dot { background:#A32D2D; }
    .rv-msg-warning { color:#854F0B; } .rv-msg-warning .rv-dot { background:#BA7517; }
    .rv-btn-group { display:flex; gap:4px; }
    .rv-rh        { display:none; }
    .rv-btn { flex:1; padding:7px 6px; border-radius:8px; border:1px solid #dee2e6; background:#fff; font-size:11px; font-weight:700; cursor:pointer; color:#6c757d; text-align:center; transition:all .15s; user-select:none; white-space:nowrap; }
    .rv-btn:hover { background:#f8f9fa; }
    .rv-bt-blue  { background:#E6F1FB !important; border-color:#378ADD !important; color:#0C447C !important; }
    .rv-bt-green { background:#EAF3DE !important; border-color:#639922 !important; color:#3B6D11 !important; }
    .rv-bt-amber { background:#FAEEDA !important; border-color:#BA7517 !important; color:#633806 !important; }
    .rv-pill-btn  { display:flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; cursor:pointer; transition:all .15s; white-space:nowrap; border:1px solid; }
    .rv-pill-blue { background:#E6F1FB; border-color:#B5D4F4; color:#0C447C; }
    .rv-pill-blue:hover { background:#B5D4F4; }
    .rv-pill-green{ background:#EAF3DE; border-color:#639922; color:#3B6D11; }
    .rv-cuotas-btn { width:100%; padding:8px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; font-size:12px; font-weight:700; color:#212529; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
    .rv-cuotas-btn:hover { background:#e9ecef; }
    .rv-cuotas-mini { padding:10px 12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; font-size:12px; }
    .rv-cm-row { display:flex; justify-content:space-between; padding:2px 0; color:#6c757d; }
    .rv-ok  { color:#3B6D11; font-weight:700; }
    .rv-err { color:#A32D2D; font-weight:700; }
    .rv-tr-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0f0f0; }
    .rv-tr-row:last-of-type { border-bottom:none; }
    .rv-tr-row span:first-child { font-size:12px; color:#6c757d; }
    .rv-tr-row span:last-child  { font-size:12px; font-weight:700; color:#212529; }
    .rv-total-final { display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:2px solid #dee2e6; }
    .rv-total-final span:first-child { font-size:15px; font-weight:700; color:#A32D2D; }
    .rv-total-final span:last-child  { font-size:20px; font-weight:700; color:#A32D2D; }
    .rv-cobrar-btn { width:100%; padding:11px; background:#198754; border:none; border-radius:8px; color:#fff; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; margin-top:12px; transition:background .15s; }
    .rv-cobrar-btn:hover    { background:#146c43; }
    .rv-cobrar-btn:disabled { background:#adb5bd; cursor:not-allowed; }
    .rv-badge  { display:inline-flex; align-items:center; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
    .rv-b-blue { background:#E6F1FB; color:#0C447C; }
    .rv-b-green{ background:#EAF3DE; color:#3B6D11; }
    .rv-b-amber{ background:#FAEEDA; color:#633806; }
    .rv-b-gray { background:#f0f0ee; color:#444; }
    .rv-tbl { width:100%; border-collapse:collapse; font-size:13px; }
    .rv-th  { padding:9px 14px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; background:#fafafa; border-bottom:1px solid #e9ecef; white-space:nowrap; }
    .rv-tr  { border-bottom:1px solid #f0f0f0; }
    .rv-tr:last-child { border-bottom:none; }
    .rv-td  { padding:10px 14px; vertical-align:middle; }
    .rv-ti  { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:5px 8px; font-size:12px; color:#212529; width:90px; outline:none; font-family:inherit; transition:border-color .15s; }
    .rv-ti:focus { border-color:#378ADD; }
    .rv-del { width:26px; height:26px; border-radius:6px; background:transparent; border:1px solid #dee2e6; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; color:#A32D2D; transition:background .12s, border-color .12s; }
    .rv-del:hover { background:#FCEBEB; border-color:#F7C1C1; }
    .rv-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:2000; display:flex; align-items:center; justify-content:center; padding:16px; }
    .rv-modal   { background:#fff; border-radius:12px; width:100%; max-width:620px; max-height:85vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.2); }
    .rv-mh      { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f0f0f0; background:#fafafa; flex-shrink:0; }
    .rv-ms      { display:flex; align-items:center; gap:10px; padding:10px 20px; border-bottom:1px solid #f0f0f0; background:#f8f9fa; flex-shrink:0; }
    .rv-si       { color:#adb5bd; font-size:13px; flex-shrink:0; }
    .rv-si-input { flex:1; border:none; background:transparent; outline:none; font-size:13px; color:#212529; font-family:inherit; min-width:0; }
    .rv-mb      { flex:1; overflow-y:auto; padding:12px 16px; }
    .rv-mf      { padding:12px 20px; border-top:1px solid #f0f0f0; background:#fafafa; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    .rv-cli-item { padding:10px 12px; border:1px solid #e9ecef; border-radius:8px; margin-bottom:6px; cursor:pointer; transition:border-color .12s, background .12s; }
    .rv-cli-item:hover { border-color:#378ADD; background:#fafcff; }
    .rv-av       { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; flex-shrink:0; }
    .rv-av-blue  { background:#B5D4F4; color:#0C447C; }
    .rv-av-green { background:#9FE1CB; color:#085041; }
    .rv-pg       { min-width:30px; height:30px; border-radius:6px; border:1px solid #dee2e6; background:#fff; font-size:12px; font-weight:700; color:#212529; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:all .12s; padding:0 6px; }
    .rv-pg:hover { background:#f8f9fa; border-color:#adb5bd; }
    .rv-pg-act   { background:#185FA5 !important; border-color:#185FA5 !important; color:#fff !important; }
    .rv-pg-dis   { opacity:.4; cursor:not-allowed; background:#f8f9fa; }
</style>
