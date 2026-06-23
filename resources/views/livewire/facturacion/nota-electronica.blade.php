<div>

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
    @if (session()->has('info'))
        <div class="alert alert-info alert-dismissible fade show mb-3">
            <i class="fa-solid fa-circle-info me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ══════ ENCABEZADO ══════ --}}
    <div class="ne-header">
        <div class="ne-header-left">
            <div class="ne-header-icon">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <div class="ne-header-title">Generar nota electrónica</div>
                <div class="ne-header-sub">
                    Comprobante afectado:
                    <span class="ne-badge ne-badge-blue ms-1">
                        {{ $ventaTipo === '01' ? 'FACTURA' : 'BOLETA' }}
                        &nbsp;{{ $ventaSerie }}-{{ str_pad($ventaCorrelativo, 8, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════ FILA 1: 3 columnas ══════ --}}
    <div class="ne-row3">

        {{-- ── CLIENTE ── --}}
        <div class="ne-card">
            <div class="ne-ch">
                <div class="ne-ch-left">
                    <div class="ne-ic" style="background:#E6F1FB;">
                        <i class="fa-solid fa-user" style="color:#185FA5;font-size:12px;"></i>
                    </div>
                    <span class="ne-lbl">Cliente</span>
                </div>
            </div>
            <div class="ne-cb">

                {{-- Tipo de documento: fijado automáticamente según comprobante afectado, no editable --}}
                <div class="ne-fg">
                    <label class="ne-fl">Tipo de documento</label>
                    <div class="ne-input ne-input-readonly" style="display:flex;align-items:center;gap:8px;">
                        <span class="ne-badge {{ $ventaTipo === '01' ? 'ne-badge-blue' : 'ne-badge-green' }}">
                            {{ $ventaTipo === '01' ? 'RUC' : 'DNI' }}
                        </span>
                        <span style="font-size:11px;color:#6c757d;">
                            {{ $ventaTipo === '01' ? 'Obligatorio para facturas' : 'Obligatorio para boletas' }}
                        </span>
                    </div>
                </div>

                <div class="ne-fg">
                    <label class="ne-fl">N° documento</label>
                    <div class="ne-input-group">
                        <input type="text"
                               class="ne-input"
                               wire:model.defer="numDocumento"
                               placeholder="{{ $idTipoDocumento == '2' ? '8 dígitos' : '11 dígitos' }}"
                               maxlength="{{ $idTipoDocumento == '2' ? '8' : '11' }}">
                        <button class="ne-search-btn"
                                wire:click="consultarDocumento"
                                wire:loading.attr="disabled"
                                wire:target="consultarDocumento"
                                type="button">
                            <span wire:loading wire:target="consultarDocumento">
                                <i class="fa-solid fa-spinner fa-spin" style="font-size:11px;color:#185FA5;"></i>
                            </span>
                            <span wire:loading.remove wire:target="consultarDocumento">
                                <i class="fa-solid fa-magnifying-glass" style="font-size:11px;color:#185FA5;"></i>
                            </span>
                        </button>
                    </div>
                    @if($mensajeConsulta)
                        <div class="ne-msg-doc ne-msg-{{ $tipoMensajeConsulta }}">
                            <span class="ne-msg-dot"></span>
                            {{ $mensajeConsulta }}
                        </div>
                    @endif
                </div>

                <div class="ne-fg">
                    <label class="ne-fl">{{ $idTipoDocumento == '4' ? 'Razón social' : 'Nombre' }}</label>
                    <input type="text" class="ne-input" wire:model.defer="nombreCliente" placeholder="Nombre del cliente">
                </div>

                <div class="ne-fg" style="margin-bottom:0;">
                    <label class="ne-fl">
                        Dirección
                        @if($idTipoDocumento == '4')
                            <span style="color:#A32D2D;">*</span>
                        @endif
                    </label>
                    <textarea class="ne-input" rows="2"
                              wire:model.defer="direccionCliente"
                              placeholder="{{ $idTipoDocumento == '4' ? 'Obligatorio para RUC' : 'Opcional' }}"
                              style="resize:none;"></textarea>
                </div>

            </div>
        </div>

        {{-- ── CONFIGURACIÓN DE LA NOTA ── --}}
        <div class="ne-card">
            <div class="ne-ch">
                <div class="ne-ch-left">
                    <div class="ne-ic" style="background:#FAEEDA;">
                        <i class="fa-solid fa-sliders" style="color:#854F0B;font-size:12px;"></i>
                    </div>
                    <span class="ne-lbl">Tipo de nota</span>
                </div>
            </div>
            <div class="ne-cb">

                <div class="ne-fg">
                    <label class="ne-fl">Seleccionar tipo</label>
                    <div class="ne-btn-group">
                        <input type="radio" class="ne-radio-hidden" id="tn-07"
                               name="tipo_nota" value="07" wire:model.live="tipoNota">
                        <label for="tn-07" class="ne-btn-tipo {{ $tipoNota === '07' ? 'ne-btn-credito' : '' }}">
                            <i class="fa-solid fa-arrow-down me-1" style="font-size:11px;"></i>Crédito
                        </label>

                        <input type="radio" class="ne-radio-hidden" id="tn-08"
                               name="tipo_nota" value="08" wire:model.live="tipoNota">
                        <label for="tn-08" class="ne-btn-tipo {{ $tipoNota === '08' ? 'ne-btn-debito' : '' }}">
                            <i class="fa-solid fa-arrow-up me-1" style="font-size:11px;"></i>Débito
                        </label>
                    </div>
                </div>

                @if($tipoNota)
                    <div class="ne-fg">
                        <label class="ne-fl">Motivo</label>
                        <select class="ne-select" wire:model.live="motivoNota">
                            @foreach($motivosNota as $m)
                                <option value="{{ $m->codigo }}">{{ $m->tipo_nota_descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ne-g2">
                        <div class="ne-fg" style="margin-bottom:0;">
                            <label class="ne-fl">Serie</label>
                            @if(!empty($series))
                                <div class="ne-input ne-input-readonly">{{ $series[0]['serie'] ?? '—' }}</div>
                            @else
                                <div class="ne-input ne-input-warn">Sin series disponibles</div>
                            @endif
                        </div>
                        <div class="ne-fg" style="margin-bottom:0;">
                            <label class="ne-fl">Correlativo</label>
                            <div class="ne-input ne-input-readonly ne-input-correlativo">
                                {{ str_pad($correlativo, 8, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>
                    </div>

                    {{-- IGV: se hereda de la venta afectada, solo lectura --}}
                    <div class="ne-fg" style="margin-bottom:0;margin-top:11px;">
                        <label class="ne-fl">Porcentaje IGV</label>
                        <div class="ne-input ne-input-readonly" style="display:flex;align-items:center;justify-content:space-between;">
                            <span>Heredado de la venta afectada</span>
                            <span class="ne-badge ne-badge-blue">{{ $porcentajeIgv }}%</span>
                        </div>
                    </div>
                @else
                    <div class="ne-empty-tipo">
                        <i class="fa-solid fa-hand-pointer ne-empty-icon"></i>
                        <span>Seleccione el tipo de nota para continuar</span>
                    </div>
                @endif

            </div>
        </div>

        {{-- ── COMPROBANTE AFECTADO + TOTALES + BOTÓN ── --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            <div class="ne-card">
                <div class="ne-ch">
                    <div class="ne-ch-left">
                        <div class="ne-ic" style="background:#FCEBEB;">
                            <i class="fa-solid fa-file-circle-xmark" style="color:#A32D2D;font-size:12px;"></i>
                        </div>
                        <span class="ne-lbl">Comprobante afectado</span>
                    </div>
                </div>
                <div class="ne-cb">
                    <div class="ne-afectado">
                        <div class="ne-afectado-circle {{ $ventaTipo === '01' ? 'ne-circle-f' : 'ne-circle-b' }}">
                            {{ $ventaTipo === '01' ? 'F' : 'B' }}
                        </div>
                        <div>
                            <div class="ne-afectado-tipo">
                                {{ $ventaTipo === '01' ? 'Factura electrónica' : 'Boleta de venta' }}
                            </div>
                            <div class="ne-afectado-num">
                                {{ $ventaSerie }}-{{ str_pad($ventaCorrelativo, 8, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ne-card" style="flex:1;">
                <div class="ne-ch">
                    <div class="ne-ch-left">
                        <div class="ne-ic" style="background:#f0f0ee;">
                            <i class="fa-solid fa-calculator" style="color:#5F5E5A;font-size:12px;"></i>
                        </div>
                        <span class="ne-lbl">Resumen de montos</span>
                    </div>
                </div>
                <div class="ne-cb">
                    <div class="ne-tot-row"><span>Op. gravada</span><span>S/ {{ number_format($this->totales['gravada'], 2) }}</span></div>
                    <div class="ne-tot-row"><span>IGV {{ $porcentajeIgv }}%</span><span>S/ {{ number_format($this->totales['igv'], 2) }}</span></div>
                    <div class="ne-tot-row"><span>Exonerada</span><span>S/ {{ number_format($this->totales['exonerada'], 2) }}</span></div>
                    <div class="ne-tot-row"><span>Inafectada</span><span>S/ {{ number_format($this->totales['inafecta'], 2) }}</span></div>
                    <div class="ne-tot-row"><span>Gratuitas</span><span>S/ {{ number_format($this->totales['gratuita'], 2) }}</span></div>
                    <div class="ne-tot-row"><span>ICBPER</span><span>S/ {{ number_format($this->totales['impuesto'], 2) }}</span></div>
                    <div class="ne-total-final">
                        <span>Total</span>
                        <span>S/ {{ number_format($this->totales['total'], 2) }}</span>
                    </div>
                    @can('generar_nota.crear')
                    <button class="ne-save-btn"
                            type="button"
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Guardar nota electrónica
                        </span>
                    </button>
                    @endcan
                </div>
            </div>

        </div>

    </div>{{-- /fila 1 --}}

    {{-- ══════ FILA 2: Tabla de productos ══════ --}}
    <div class="ne-card">

        <div class="ne-ch" style="flex-wrap:wrap;gap:8px;">
            <div class="ne-ch-left">
                <div class="ne-ic" style="background:#EAF3DE;">
                    <i class="fa-solid fa-box" style="color:#3B6D11;font-size:12px;"></i>
                </div>
                <span class="ne-lbl">Productos de la nota</span>
                <span class="ne-badge ne-badge-blue" style="margin-left:8px;">
                    {{ count($items) }} ítem{{ count($items) !== 1 ? 's' : '' }}
                </span>
            </div>

            @if($tipoNota && $motivoNota)
                @php $permisos = $this->permisos; @endphp
                <div class="ne-permisos-bar">
                    <span class="ne-permisos-label">Permisos — motivo {{ $motivoNota }}:</span>
                    <span class="ne-pchip {{ $permisos['editarPrecio'] ? 'ne-pon' : 'ne-poff' }}">
                        <i class="fa-solid fa-tag" style="font-size:9px;"></i>
                        Precio {{ $permisos['editarPrecio'] ? 'editable' : 'bloqueado' }}
                    </span>
                    <span class="ne-pchip {{ $permisos['editarCantidad'] ? 'ne-pon' : 'ne-poff' }}">
                        <i class="fa-solid fa-hashtag" style="font-size:9px;"></i>
                        Cantidad {{ $permisos['editarCantidad'] ? 'editable' : 'bloqueada' }}
                    </span>
                    <span class="ne-pchip {{ $permisos['eliminarItem'] ? 'ne-pon' : 'ne-poff' }}">
                        <i class="fa-solid fa-trash" style="font-size:9px;"></i>
                        Eliminar {{ $permisos['eliminarItem'] ? 'permitido' : 'bloqueado' }}
                    </span>
                    <span class="ne-pchip {{ $permisos['agregarProducto'] ? 'ne-pon' : 'ne-poff' }}">
                        <i class="fa-solid fa-plus" style="font-size:9px;"></i>
                        Agregar {{ $permisos['agregarProducto'] ? 'permitido' : 'bloqueado' }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Búsqueda inline --}}
        @php $permisosTabla = $this->permisos ?? ['editarPrecio'=>true,'editarCantidad'=>true,'eliminarItem'=>true,'agregarProducto'=>true]; @endphp
        <div style="position:relative;">
            @if($permisosTabla['agregarProducto'])
                <div class="ne-search-bar">
                    <i class="fa-solid fa-magnifying-glass ne-search-icon"></i>
                    <input type="text"
                           id="ne-input-buscar"
                           class="ne-search-input"
                           wire:model.live.debounce.300ms="buscarProducto"
                           wire:focus="abrirBusqueda"
                           placeholder="Buscar producto adicional por nombre o código..."
                           autocomplete="off">
                </div>

                @if(!empty($resultadosProductos))
                    <div class="ne-dropdown" id="ne-search-dropdown">
                        @foreach($resultadosProductos as $p)
                            <div class="ne-dropdown-item"
                                 wire:click="agregarProducto({{ $p->id_pro }})"
                                 wire:key="res-{{ $p->id_pro }}">
                                <span class="ne-drop-nombre">{{ $p->pro_nombre }}</span>
                                <span class="ne-drop-precio">S/ {{ number_format($p->pro_precio_uni, 2) }}</span>
                                <br>
                                <small class="ne-drop-codigo">{{ $p->pro_codigo ?? '' }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="ne-search-bar ne-search-blocked">
                    <i class="fa-solid fa-lock" style="color:#adb5bd;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:13px;color:#adb5bd;">
                        No es posible agregar productos — el motivo seleccionado bloquea esta acción
                    </span>
                </div>
            @endif
        </div>

        {{-- Tabla --}}
        @php $permisos = $permisosTabla; @endphp
        <div style="overflow-x:auto;">
            <table class="ne-table">
                <thead>
                <tr>
                    <th class="ne-th" style="width:40%;">Descripción</th>
                    <th class="ne-th" style="width:14%;text-align:right;">Precio unit.</th>
                    <th class="ne-th" style="width:10%;text-align:center;">Cantidad</th>
                    <th class="ne-th" style="width:12%;text-align:right;">Subtotal</th>
                    <th class="ne-th" style="width:12%;text-align:right;">Total</th>
                    <th class="ne-th" style="width:5%;text-align:center;"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $idx => $item)
                    @php
                        $esBolsa   = (int)($item['impuesto_bolsa'] ?? 0) === 1;
                        $tipoAfec  = (int)($item['id_tipo_afectacion'] ?? 0);
                        $precio    = (float)($item['precio_venta'] ?? 0);
                        $cantidad  = (float)($item['cantidad'] ?? 0);
                        $tasa      = $porcentajeIgv / 100;
                        $sub       = $esBolsa ? 0 : round($precio * $cantidad, 2);
                        $totalItem = ($tipoAfec === 1 && !$esBolsa) ? round($sub + $sub * $tasa, 2) : $sub;
                    @endphp
                    <tr class="ne-tr" wire:key="item-{{ $idx }}">

                        <td class="ne-td">
                            <div class="ne-prod-nombre">{{ $item['nombre_producto'] }}</div>
                            <div style="margin-top:3px;">
                                @if($esBolsa)
                                    <span class="ne-badge ne-badge-amber">ICBPER</span>
                                @elseif($tipoAfec === 1)
                                    <span class="ne-badge ne-badge-blue">Gravado</span>
                                @elseif($tipoAfec === 2)
                                    <span class="ne-badge ne-badge-green">Exonerado</span>
                                @else
                                    <span class="ne-badge ne-badge-gray">Inafecto</span>
                                @endif
                            </div>
                        </td>

                        <td class="ne-td" style="text-align:right;">
                            @if($esBolsa)
                                <span class="ne-val-muted">S/ 0.50 c/u</span>
                            @elseif($permisos['editarPrecio'])
                                <input type="number"
                                       class="ne-input-table"
                                       style="text-align:right;"
                                       value="{{ number_format($precio, 2, '.', '') }}"
                                       step="0.01" min="0"
                                       wire:change="actualizarPrecio({{ $idx }}, $event.target.value)">
                            @else
                                <span class="ne-val-bold">{{ number_format($precio, 2) }}</span>
                            @endif
                        </td>

                        <td class="ne-td" style="text-align:center;">
                            @if($permisos['editarCantidad'])
                                <input type="number"
                                       class="ne-input-table"
                                       style="text-align:center;width:70px;"
                                       value="{{ $cantidad }}"
                                       min="0.01" step="1"
                                       wire:change="actualizarCantidad({{ $idx }}, $event.target.value)">
                            @else
                                <span class="ne-val-bold">{{ $cantidad }}</span>
                            @endif
                        </td>

                        <td class="ne-td" style="text-align:right;">
                            <span class="ne-val-muted">{{ number_format($sub, 2) }}</span>
                        </td>

                        <td class="ne-td" style="text-align:right;">
                            <span class="ne-val-bold">{{ number_format($totalItem, 2) }}</span>
                        </td>

                        <td class="ne-td" style="text-align:center;">
                            @if($permisos['eliminarItem'])
                                <button class="ne-del-btn" type="button"
                                        wire:click="quitarItem({{ $idx }})"
                                        title="Quitar ítem">
                                    <i class="fa-solid fa-xmark" style="font-size:12px;"></i>
                                </button>
                            @else
                                <span style="color:#aaa;font-size:11px;" title="Bloqueado por motivo">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                            @endif
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ne-empty-table">
                            <i class="fa-solid fa-box-open" style="display:block;font-size:28px;margin-bottom:8px;opacity:.2;"></i>
                            No hay productos cargados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>{{-- /fila 2 --}}

</div>

@script
<script>
    document.addEventListener('click', function (e) {
        const input    = document.getElementById('ne-input-buscar');
        const dropdown = document.getElementById('ne-search-dropdown');
        if (dropdown && input) {
            if (!dropdown.contains(e.target) && !input.contains(e.target)) {
                @this.limpiarBusqueda();
            }
        }
    });

    Livewire.on('notaGuardada', ({ notaId }) => {
        window.location.href = ruta_global + 'facturacion/pendiente_declarar';
    });
</script>
@endscript

<style>
    /* ── Layout ───────────────────────────────────────── */
    .ne-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ne-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .ne-header-icon {
        width: 36px;
        height: 36px;
        background: #E6F1FB;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #185FA5;
        font-size: 16px;
        flex-shrink: 0;
    }
    .ne-header-title {
        font-size: 15px;
        font-weight: 600;
        color: #1a1a1a;
    }
    .ne-header-sub {
        font-size: 12px;
        color: #6c757d;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }

    .ne-row3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }
    @media (max-width: 992px) {
        .ne-row3 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .ne-row3 { grid-template-columns: 1fr; }
    }

    /* ── Card ─────────────────────────────────────────── */
    .ne-card {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
    }
    .ne-ch {
        padding: 10px 16px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: #fafafa;
    }
    .ne-ch-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ne-ic {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ne-lbl {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #6c757d;
    }
    .ne-cb {
        padding: 14px 16px;
    }

    /* ── Form ─────────────────────────────────────────── */
    .ne-fg { margin-bottom: 11px; }
    .ne-fg:last-child { margin-bottom: 0; }
    .ne-fl {
        font-size: 11px;
        color: #6c757d;
        margin-bottom: 4px;
        display: block;
        font-weight: 600;
    }
    .ne-input,
    .ne-select {
        width: 100%;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 13px;
        color: #212529;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        font-family: inherit;
    }
    .ne-input:focus,
    .ne-select:focus {
        border-color: #378ADD;
        box-shadow: 0 0 0 3px rgba(55,138,221,.12);
    }
    .ne-input-readonly {
        font-weight: 600;
        cursor: default;
        user-select: none;
        background: #f0f4f8;
    }
    .ne-input-correlativo {
        text-align: center;
        color: #185FA5;
        letter-spacing: .04em;
    }
    .ne-input-warn {
        color: #854F0B;
        background: #FAEEDA;
        border-color: #FAC775;
    }
    .ne-g2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .ne-input-group {
        display: flex;
        gap: 6px;
    }
    .ne-input-group .ne-input { flex: 1; }
    .ne-search-btn {
        width: 34px;
        height: 35px;
        background: #E6F1FB;
        border: 1px solid #B5D4F4;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s;
    }
    .ne-search-btn:hover { background: #B5D4F4; }

    /* Mensajes doc */
    .ne-msg-doc {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
        font-size: 11px;
    }
    .ne-msg-doc .ne-msg-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .ne-msg-success { color: #3B6D11; }
    .ne-msg-success .ne-msg-dot { background: #639922; }
    .ne-msg-error   { color: #791F1F; }
    .ne-msg-error   .ne-msg-dot { background: #A32D2D; }
    .ne-msg-warning { color: #854F0B; }
    .ne-msg-warning .ne-msg-dot { background: #BA7517; }

    /* Botones tipo nota */
    .ne-btn-group { display: flex; gap: 4px; }
    .ne-radio-hidden { display: none; }
    .ne-btn-tipo {
        flex: 1;
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        color: #6c757d;
        text-align: center;
        transition: all .15s;
        user-select: none;
    }
    .ne-btn-tipo:hover { background: #f8f9fa; }
    .ne-btn-credito {
        background: #EAF3DE !important;
        border-color: #639922 !important;
        color: #3B6D11 !important;
    }
    .ne-btn-debito {
        background: #FCEBEB !important;
        border-color: #A32D2D !important;
        color: #791F1F !important;
    }

    /* Empty tipo nota */
    .ne-empty-tipo {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 20px 0;
        color: #adb5bd;
        font-size: 12px;
        text-align: center;
    }
    .ne-empty-icon { font-size: 22px; opacity: .4; }

    /* Comprobante afectado */
    .ne-afectado {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }
    .ne-afectado-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
    }
    .ne-circle-f { background: #B5D4F4; color: #0C447C; }
    .ne-circle-b { background: #9FE1CB; color: #085041; }
    .ne-afectado-tipo { font-size: 13px; font-weight: 600; color: #212529; }
    .ne-afectado-num  { font-size: 12px; color: #6c757d; }

    /* Totales */
    .ne-tot-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .ne-tot-row:last-of-type { border-bottom: none; }
    .ne-tot-row span:first-child { font-size: 12px; color: #6c757d; }
    .ne-tot-row span:last-child  { font-size: 12px; font-weight: 600; color: #212529; }
    .ne-total-final {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid #dee2e6;
    }
    .ne-total-final span:first-child { font-size: 15px; font-weight: 700; color: #A32D2D; }
    .ne-total-final span:last-child  { font-size: 20px; font-weight: 700; color: #A32D2D; }

    /* Botón guardar */
    .ne-save-btn {
        width: 100%;
        padding: 11px;
        background: #185FA5;
        border: none;
        border-radius: 8px;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 12px;
        transition: background .15s;
    }
    .ne-save-btn:hover    { background: #0C447C; }
    .ne-save-btn:disabled { background: #adb5bd; cursor: not-allowed; }

    /* Badges */
    .ne-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .ne-badge-blue  { background: #E6F1FB; color: #0C447C; }
    .ne-badge-green { background: #EAF3DE; color: #3B6D11; }
    .ne-badge-amber { background: #FAEEDA; color: #633806; }
    .ne-badge-gray  { background: #f0f0ee; color: #444441; }
    .ne-badge-red   { background: #FCEBEB; color: #791F1F; }

    /* Permisos */
    .ne-permisos-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .ne-permisos-label {
        font-size: 11px;
        color: #6c757d;
        font-weight: 600;
    }
    .ne-pchip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .ne-pon  { background: #EAF3DE; color: #3B6D11; }
    .ne-poff { background: #f0f0ee; color: #444441; }

    /* Búsqueda */
    .ne-search-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        background: #fafafa;
        border-bottom: 1px solid #f0f0f0;
    }
    .ne-search-icon { color: #adb5bd; font-size: 13px; flex-shrink: 0; }
    .ne-search-input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        color: #212529;
        font-family: inherit;
    }
    .ne-search-input::placeholder { color: #adb5bd; }

    /* Dropdown */
    .ne-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1055;
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 260px;
        overflow-y: auto;
        box-shadow: 0 4px 16px rgba(0,0,0,.08);
    }
    .ne-dropdown-item {
        padding: 9px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background .1s;
    }
    .ne-dropdown-item:hover    { background: #f8f9fa; }
    .ne-dropdown-item:last-child { border-bottom: none; }
    .ne-drop-nombre { font-size: 13px; font-weight: 600; color: #212529; }
    .ne-drop-precio { float: right; font-size: 13px; font-weight: 600; color: #185FA5; }
    .ne-drop-codigo { font-size: 11px; color: #6c757d; }

    /* Tabla */
    .ne-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .ne-th {
        padding: 9px 14px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6c757d;
        background: #fafafa;
        border-bottom: 1px solid #e9ecef;
        white-space: nowrap;
    }
    .ne-tr { border-bottom: 1px solid #f0f0f0; }
    .ne-tr:last-child { border-bottom: none; }
    .ne-tr:hover { background: #fafcff; }
    .ne-td { padding: 10px 14px; vertical-align: middle; }
    .ne-prod-nombre { font-size: 13px; font-weight: 600; color: #212529; }
    .ne-input-table {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 5px 8px;
        font-size: 12px;
        color: #212529;
        width: 90px;
        outline: none;
        font-family: inherit;
        transition: border-color .15s;
    }
    .ne-input-table:focus { border-color: #378ADD; }
    .ne-val-bold  { font-size: 13px; font-weight: 600; color: #212529; }
    .ne-val-muted { font-size: 13px; color: #6c757d; }
    .ne-del-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        background: transparent;
        border: 1px solid #dee2e6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #A32D2D;
        transition: background .12s, border-color .12s;
    }
    .ne-del-btn:hover { background: #FCEBEB; border-color: #F7C1C1; }
    .ne-empty-table {
        text-align: center;
        color: #adb5bd;
        padding: 40px 20px;
        font-size: 13px;
    }
    .ne-search-blocked {
        background: #f8f9fa;
        cursor: not-allowed;
        opacity: .75;
    }
    .ms-1 { margin-left: 4px; }
</style>
