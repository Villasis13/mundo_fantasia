<div class="container-fluid py-3" style="max-width:1080px;">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="fa-solid fa-circle-xmark"></i><span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Top bar --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('Gestionventas.guias_remision') }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i></a>
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-truck-fast me-2 text-primary"></i>Nueva Guía de Remisión</h5>
        </div>
        @if($idVenta)
            <span class="badge bg-success p-2"><i class="fa-solid fa-link me-1"></i>Factura vinculada: {{ $facturaVinculada }}
                <button type="button" class="btn-close btn-close-white ms-2" wire:click="desvincularFactura"></button>
            </span>
        @else
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFacturaGuia"
                    wire:click="cargarFacturasIniciales">
                <i class="fa-solid fa-link me-1"></i>Cargar Factura/Boleta
            </button>
        @endif
    </div>

    {{-- ════════ UN SOLO CARD, BLOQUES SIN TÍTULOS ════════ --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body">

            {{-- Bloque: datos de la guía --}}
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Serie</label>
                    <input type="text" class="form-control form-control-sm fw-bold text-primary" value="{{ $this->serie }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">N.º de guía</label>
                    <input type="text" class="form-control form-control-sm" value="{{ str_pad($nextNumero, 8, '0', STR_PAD_LEFT) }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Fecha de emisión <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('guiaEmision') is-invalid @enderror" wire:model="guiaEmision">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Fecha de traslado <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('guiaTraslado') is-invalid @enderror" wire:model="guiaTraslado">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Motivo <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model="guiaMotivo">
                        <option value="01">01 – Venta</option>
                        <option value="02">02 – Compra</option>
                        <option value="03">03 – Venta con entrega a terceros</option>
                        <option value="04">04 – Traslado entre establecimientos</option>
                        <option value="05">05 – Consignación</option>
                        <option value="06">06 – Devolución</option>
                        <option value="13">13 – Otros</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Observación</label>
                    <input type="text" class="form-control form-control-sm" wire:model="guiaObservacion">
                </div>
            </div>

            <hr class="my-4">

            {{-- Bloque: salida y llegada --}}
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Dirección de salida <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('dirPartida') is-invalid @enderror" wire:model="dirPartida">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Ubigeo salida <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('ubigeoPartida') is-invalid @enderror" wire:model="ubigeoPartida">
                        <option value="">— Seleccionar ubigeo —</option>
                        @foreach($ubigeos as $u)
                            <option value="{{ $u->ubigeo_cod }}">{{ $u->ubigeo_departamento }} / {{ $u->ubigeo_provincia }} / {{ $u->ubigeo_distrito }} ({{ $u->ubigeo_cod }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Dirección de llegada <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('dirLlegada') is-invalid @enderror" wire:model="dirLlegada">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Ubigeo llegada <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('ubigeoLlegada') is-invalid @enderror" wire:model="ubigeoLlegada">
                        <option value="">— Seleccionar ubigeo —</option>
                        @foreach($ubigeos as $u)
                            <option value="{{ $u->ubigeo_cod }}">{{ $u->ubigeo_departamento }} / {{ $u->ubigeo_provincia }} / {{ $u->ubigeo_distrito }} ({{ $u->ubigeo_cod }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4">

            {{-- Bloque: cliente / destinatario --}}
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalClienteGuia">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar cliente
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('cliNombre') is-invalid @enderror" wire:model="cliNombre">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">N.º documento</label>
                    <input type="text" class="form-control form-control-sm" maxlength="15" wire:model.live.debounce.600ms="cliNumDoc">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Dirección fiscal</label>
                    <input type="text" class="form-control form-control-sm" wire:model="cliDireccion">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Distrito</label>
                    <input type="text" class="form-control form-control-sm" wire:model="cliDistrito">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Provincia</label>
                    <input type="text" class="form-control form-control-sm" wire:model="cliProvincia">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Departamento</label>
                    <input type="text" class="form-control form-control-sm" wire:model="cliDepartamento">
                </div>
            </div>

            <hr class="my-4">

            {{-- Bloque: carga --}}
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Peso bruto (Kg) <span class="text-danger">*</span></label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm" wire:model="pesoBruto">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cantidad de bultos</label>
                    <input type="number" min="0" class="form-control form-control-sm" wire:model="nroBultos">
                </div>
                @if($idVenta)
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Documentos Relacionados</label>
                    <input type="text" class="form-control form-control-sm fw-semibold text-success" value="{{ $facturaVinculada }}" disabled>
                </div>
                @endif
            </div>

            <hr class="my-4">

            {{-- Bloque: transporte --}}
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Empresa transportista</label>
                    <input type="text" class="form-control form-control-sm" wire:model="transNombre">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Marca</label>
                    <input type="text" class="form-control form-control-sm" wire:model="vehMarca">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Placa(s) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm text-uppercase @error('vehPlaca') is-invalid @enderror" maxlength="20" wire:model="vehPlaca" placeholder="ABC-123">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Licencia de conducir</label>
                    <input type="text" class="form-control form-control-sm" maxlength="12" wire:model="condLicencia">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">DNI chofer</label>
                    <input type="text" class="form-control form-control-sm" maxlength="15" wire:model="condNumDoc">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nombre del chofer</label>
                    <input type="text" class="form-control form-control-sm" wire:model="condNombre">
                </div>
            </div>

            <hr class="my-4">

            {{-- Bloque: bienes a trasladar (mismo card) --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Bienes a trasladar</span>
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="agregarItemManual"><i class="fa-solid fa-plus me-1"></i>Agregar ítem</button>
            </div>
            <div class="position-relative mb-3">
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="buscarProducto" placeholder="Buscar producto por nombre o código...">
                @if(count($resultadosProductos))
                <div class="list-group position-absolute w-100 shadow border rounded" style="z-index:1050;max-height:260px;overflow:auto;background:#fff;">
                    @foreach($resultadosProductos as $p)
                    <button type="button" class="list-group-item list-group-item-action py-1" style="background:#fff;"
                            wire:click="agregarProducto({{ $p['id_pro'] }}, @js($p['pro_codigo']), @js($p['pro_nombre']))">
                        <div class="fw-semibold small">{{ $p['pro_nombre'] }}</div>
                        <small class="text-muted">{{ $p['pro_codigo'] }}</small>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            @error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:100px;">Código</th>
                            <th>Producto</th>
                            <th style="width:80px;">UND</th>
                            <th style="width:90px;">Cantidad</th>
                            <th style="width:110px;">P. Venta</th>
                            <th style="width:110px;" class="text-end">Total</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $it)
                        <tr wire:key="guia-item-{{ $i }}">
                            <td><input type="text" class="form-control form-control-sm" wire:model="items.{{ $i }}.codigo"></td>
                            <td><input type="text" class="form-control form-control-sm @error('items.'.$i.'.descripcion') is-invalid @enderror" wire:model="items.{{ $i }}.descripcion"></td>
                            <td>
                                <select class="form-select form-select-sm" wire:model="items.{{ $i }}.um">
                                    @foreach(['NIU','KGM','TNE','LTR','MTR','BOL','CJA','PAR','ZZ'] as $um)
                                        <option value="{{ $um }}">{{ $um }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" min="0" step="1" class="form-control form-control-sm @error('items.'.$i.'.cantidad') is-invalid @enderror" wire:model.live="items.{{ $i }}.cantidad"></td>
                            <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" wire:model.live="items.{{ $i }}.precio"></td>
                            <td class="text-end fw-semibold text-primary">{{ number_format((float)($it['cantidad'] ?? 0) * (float)($it['precio'] ?? 0), 2) }}</td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" wire:click="quitarItem({{ $i }})"><i class="fa-solid fa-trash"></i></button></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No hay bienes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('Gestionventas.guias_remision') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="button" class="btn btn-primary fw-semibold" wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
                    <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                    <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="guardar"></i>Guardar Guía
                </button>
            </div>

        </div>
    </div>

    {{-- ── Modal: Vincular factura ── --}}
    <div class="modal fade" id="modalFacturaGuia" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title fw-bold">Cargar Factura a la Guía de Remisión</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Filtrar</label>
                        <input type="text" class="form-control form-control-sm"
                               wire:model.live.debounce.400ms="factFiltro"
                               placeholder="Serie - Correlativo (ej. B001-00)">
                    </div>
                    @if($factMensaje)<div class="alert alert-warning py-1 small">{{ $factMensaje }}</div>@endif
                    @if(count($factResultados))
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>Tipo</th><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach($factResultados as $f)
                            <tr>
                                <td class="small">{{ $f['tipo'] }}</td>
                                <td class="small fw-semibold text-primary">{{ $f['comprobante'] }}</td>
                                <td class="small">{{ $f['fecha'] }}</td>
                                <td class="small">{{ $f['cliente_nombre'] }}<div class="text-muted">{{ $f['cliente_numero'] }}</div></td>
                                <td class="small text-end fw-semibold">S/ {{ $f['total'] }}</td>
                                <td><button class="btn btn-sm btn-primary" wire:click="vincularFactura('{{ $f['id_venta'] }}')">Cargar</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal: Buscar cliente ── --}}
    <div class="modal fade" id="modalClienteGuia" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title fw-bold">Buscar Cliente</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="text" class="form-control form-control-sm mb-2" wire:model.live.debounce.400ms="buscarCliente" placeholder="Nombre o N° documento...">
                    <div style="max-height:300px;overflow:auto;">
                        @foreach($resultadosClientes as $c)
                        <div class="border rounded p-2 mb-1" style="cursor:pointer;" wire:click="seleccionarCliente('{{ $c['id_clientes'] }}')">
                            <div class="fw-semibold small">{{ $c['cliente_razonsocial'] ?: $c['cliente_nombre'] }}</div>
                            <small class="text-muted">{{ $c['cliente_numero'] }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('abrirEnlaces', (e) => { const d = Array.isArray(e) ? e[0] : e; if (d && d.url) window.open(d.url, '_blank'); });
            Livewire.on('cerrarModalFacturaGuia', () => { const m = bootstrap.Modal.getInstance(document.getElementById('modalFacturaGuia')); if (m) m.hide(); });
            Livewire.on('cerrarModalClienteGuia', () => { const m = bootstrap.Modal.getInstance(document.getElementById('modalClienteGuia')); if (m) m.hide(); });
            // Tras registrar: abrir PDF en pestaña nueva y redirigir al listado
            Livewire.on('guiaGuardada', (e) => {
                const d = Array.isArray(e) ? e[0] : e;
                if (d && d.pdf) window.open(d.pdf, '_blank');
                if (d && d.lista) setTimeout(() => { window.location.href = d.lista; }, 400);
            });
        });
    </script>
</div>
