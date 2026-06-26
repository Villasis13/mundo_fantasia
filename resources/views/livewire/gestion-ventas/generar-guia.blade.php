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
            <div>
                <h5 class="fw-bold mb-0">Nueva Guía de Remisión</h5>
                <small class="text-muted">Complete los campos requeridos para generar la guía.</small>
            </div>
        </div>
        @if($idVenta)
            <span class="badge bg-success p-2"><i class="fa-solid fa-link me-1"></i>Factura vinculada
                <button type="button" class="btn-close btn-close-white ms-2" wire:click="desvincularFactura"></button>
            </span>
        @else
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFacturaGuia">
                <i class="fa-solid fa-link me-1"></i>Vincular factura
            </button>
        @endif
    </div>

    {{-- ── 1. Información de la guía ── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold"><span class="badge bg-primary me-2">1</span>Información de la Guía</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Fecha de emisión <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('guiaEmision') is-invalid @enderror" wire:model="guiaEmision">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tipo de guía <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model.live="guiaTipo">
                        <option value="09">Guía Remitente</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Serie</label>
                    <input type="text" class="form-control form-control-sm fw-bold text-primary" value="{{ $this->serie }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Fecha de traslado <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('guiaTraslado') is-invalid @enderror" wire:model="guiaTraslado">
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Motivo de traslado <span class="text-danger">*</span></label>
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
                <div class="col-12">
                    <label class="form-label small fw-semibold">Observación <small class="text-muted">(opcional)</small></label>
                    <textarea class="form-control form-control-sm" rows="2" wire:model="guiaObservacion"></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. Cliente / Destinatario ── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold"><span class="badge bg-primary me-2">2</span>Datos del Cliente / Destinatario</div>
        <div class="card-body">
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalClienteGuia">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar cliente registrado
            </button>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tipo de documento</label>
                    <select class="form-select form-select-sm" wire:model="cliTipoDoc">
                        <option value="">— Seleccionar —</option>
                        @foreach($tipoDocs as $td)
                            <option value="{{ $td->id_tipo_documento }}">{{ $td->tipo_documento_identidad }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">N.º Documento</label>
                    <input type="text" class="form-control form-control-sm" maxlength="15" wire:model.live.debounce.600ms="cliNumDoc" placeholder="RUC / DNI">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nombre / Razón Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('cliNombre') is-invalid @enderror" wire:model="cliNombre">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Dirección <small class="text-muted">(opcional)</small></label>
                    <input type="text" class="form-control form-control-sm" wire:model="cliDireccion">
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3. Transporte ── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold"><span class="badge bg-primary me-2">3</span>Datos del Transporte</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">RUC Transportista</label>
                    <input type="text" class="form-control form-control-sm" maxlength="11" wire:model="transRuc">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Razón Social Transportista</label>
                    <input type="text" class="form-control form-control-sm" wire:model="transNombre">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tipo de Transporte <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model="tipoTrans">
                        <option value="02">02 – Privado</option>
                        <option value="01">01 – Público</option>
                    </select>
                </div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Placa del Vehículo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm text-uppercase @error('vehPlaca') is-invalid @enderror" maxlength="8" wire:model="vehPlaca" placeholder="ABC-123">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Marca del Vehículo</label>
                    <input type="text" class="form-control form-control-sm" wire:model="vehMarca">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Placa de Carreta</label>
                    <input type="text" class="form-control form-control-sm" maxlength="8" wire:model="vehCarreta">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Certificado MTC</label>
                    <input type="text" class="form-control form-control-sm" wire:model="certMtc">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Peso Bruto <span class="text-danger">*</span></label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm" wire:model="pesoBruto">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Unidad de Medida</label>
                    <select class="form-select form-select-sm" wire:model="unidadMedida">
                        <option value="KGM">KGM – Kilogramo</option>
                        <option value="TNE">TNE – Tonelada</option>
                        <option value="GRM">GRM – Gramo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">N.º de Bultos</label>
                    <input type="number" min="0" class="form-control form-control-sm" wire:model="nroBultos">
                </div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tipo Doc. Conductor</label>
                    <select class="form-select form-select-sm" wire:model="condTipoDoc">
                        <option value="1">DNI</option>
                        <option value="4">C.E.</option>
                        <option value="7">Pasaporte</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">N.º Documento</label>
                    <input type="text" class="form-control form-control-sm" maxlength="15" wire:model="condNumDoc">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Nombres</label>
                    <input type="text" class="form-control form-control-sm" wire:model="condNombre">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Apellidos</label>
                    <input type="text" class="form-control form-control-sm" wire:model="condApellidos">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Licencia</label>
                    <input type="text" class="form-control form-control-sm" maxlength="12" wire:model="condLicencia">
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4. Puntos ── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold"><span class="badge bg-primary me-2">4</span>Punto de Partida y Llegada</div>
        <div class="card-body">
            @php $colP = str_starts_with($partidaKey, 'empresa_') ? 'col-md-3' : 'col-md-4'; @endphp
            <div class="row g-3">
                <div class="{{ $colP }}">
                    <label class="form-label small fw-semibold">Punto de Partida <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model.live="partidaKey">
                        <option value="">— Seleccione punto de partida —</option>
                        <optgroup label="Almacenes">
                            @foreach($almacenes as $alm)
                            <option value="almacen_{{ $alm->id_almacen }}">
                                {{ $alm->almacen_nombre }} — {{ $alm->empresa_nombrecomercial }}{{ $alm->almacen_direccion ? ' / '.$alm->almacen_direccion : '' }}
                            </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Empresas / Sedes">
                            @foreach($empresasPartida as $emp)
                            <option value="empresa_{{ $emp->id_empresa }}">{{ $emp->empresa_nombrecomercial }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                @if(str_starts_with($partidaKey, 'empresa_'))
                <div class="{{ $colP }}">
                    <label class="form-label small fw-semibold">Sede de Partida <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" wire:model.live="idTiendaPartida" {{ $sedesPartida->isEmpty() ? 'disabled' : '' }}>
                        <option value="0">— Seleccione sede —</option>
                        @foreach($sedesPartida as $sede)
                        <option value="{{ $sede->id_tienda }}">{{ $sede->tienda_nombre }}{{ $sede->tienda_direccion ? ' / '.$sede->tienda_direccion : '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="{{ $colP }}">
                    <label class="form-label small fw-semibold">Dirección de Partida <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('dirPartida') is-invalid @enderror"
                           wire:model="dirPartida" placeholder="Se completa al elegir; o escríbela si el almacén no tiene dirección">
                    @error('dirPartida')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="{{ $colP }}">
                    <label class="form-label small fw-semibold">Ubigeo de Partida <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('ubigeoPartida') is-invalid @enderror" wire:model="ubigeoPartida">
                        <option value="">— Seleccionar ubigeo —</option>
                        @foreach($ubigeos as $u)
                            <option value="{{ $u->ubigeo_cod }}">{{ $u->ubigeo_departamento }} / {{ $u->ubigeo_provincia }} / {{ $u->ubigeo_distrito }} ({{ $u->ubigeo_cod }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Dirección de Llegada <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm @error('dirLlegada') is-invalid @enderror" wire:model="dirLlegada">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Ubigeo de Llegada <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('ubigeoLlegada') is-invalid @enderror" wire:model="ubigeoLlegada">
                        <option value="">— Seleccionar ubigeo —</option>
                        @foreach($ubigeos as $u)
                            <option value="{{ $u->ubigeo_cod }}">{{ $u->ubigeo_departamento }} / {{ $u->ubigeo_provincia }} / {{ $u->ubigeo_distrito }} ({{ $u->ubigeo_cod }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 5. Bienes ── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
            <span><span class="badge bg-primary me-2">5</span>Bienes a Trasladar</span>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="agregarItemManual"><i class="fa-solid fa-plus me-1"></i>Agregar ítem</button>
        </div>
        <div class="card-body">
            <div class="position-relative mb-3">
                <label class="form-label small fw-semibold">Buscar producto</label>
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.400ms="buscarProducto" placeholder="Nombre o código del producto...">
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
                            <th style="width:90px;">Código</th>
                            <th>Producto</th>
                            <th style="width:80px;">U.M.</th>
                            <th style="width:80px;">Cantidad</th>
                            <th style="width:95px;">Peso Unit.</th>
                            <th style="width:95px;">Peso Total</th>
                            <th>Observación</th>
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
                            <td><input type="number" min="0" step="0.001" class="form-control form-control-sm" wire:model.live="items.{{ $i }}.peso"></td>
                            <td class="text-end fw-semibold text-primary">{{ number_format((float)($it['cantidad'] ?? 0) * (float)($it['peso'] ?? 0), 3) }}</td>
                            <td><input type="text" class="form-control form-control-sm" wire:model="items.{{ $i }}.observacion"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" wire:click="quitarItem({{ $i }})"><i class="fa-solid fa-trash"></i></button></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No hay bienes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-2 small">Peso total estimado: <strong class="text-primary">{{ number_format($this->pesoTotal, 3) }} KG</strong></div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('Gestionventas.guias_remision') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="button" class="btn btn-primary fw-semibold" wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
            <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
            <i class="fa-solid fa-floppy-disk me-1" wire:loading.remove wire:target="guardar"></i>Guardar Guía
        </button>
    </div>

    {{-- ── Modal: Vincular factura ── --}}
    <div class="modal fade" id="modalFacturaGuia" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title fw-bold">Vincular Factura</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col"><label class="form-label small">Serie</label><input type="text" class="form-control form-control-sm" wire:model="factSerie" placeholder="F001"></div>
                        <div class="col"><label class="form-label small">Correlativo</label><input type="text" class="form-control form-control-sm" wire:model="factCorrelativo" placeholder="00000001"></div>
                        <div class="col-auto"><button class="btn btn-sm btn-primary" wire:click="buscarFactura"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button></div>
                    </div>
                    @if($factMensaje)<div class="alert alert-warning py-1 small">{{ $factMensaje }}</div>@endif
                    @if(count($factResultados))
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>Factura</th><th>Cliente</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach($factResultados as $f)
                            <tr>
                                <td class="small fw-semibold text-primary">{{ $f['serie'] }}-{{ $f['correlativo'] }}</td>
                                <td class="small">{{ $f['cliente_nombre'] }}<div class="text-muted">{{ $f['cliente_numero'] }}</div></td>
                                <td class="small">S/ {{ $f['total'] }}</td>
                                <td><button class="btn btn-sm btn-primary" wire:click="vincularFactura('{{ $f['id_venta'] }}')">Vincular</button></td>
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
            Livewire.on('abrirEnlaces', (e) => {
                const d = Array.isArray(e) ? e[0] : e;
                if (d && d.url) window.open(d.url, '_blank');
            });
            Livewire.on('cerrarModalFacturaGuia', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalFacturaGuia')); if (m) m.hide();
            });
            Livewire.on('cerrarModalClienteGuia', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('modalClienteGuia')); if (m) m.hide();
            });
        });
    </script>
</div>
