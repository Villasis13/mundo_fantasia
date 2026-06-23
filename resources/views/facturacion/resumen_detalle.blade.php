@extends('layouts.plantilla')
@section('content')
    <!-- Modal -->

    <div class="tab-content">
        @can($opciones[0]->opciones_funcion . '.opcion')
        {{-- tab 1--}}
        <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}" class="tab-pane fade show active " role="tabpanel" aria-labelledby="opciones_{{$opciones[0]->id_opciones}}" >
            <div class="card">
                <div class="card-header text-center">
                    <h5 class="mb-0 fw-bold">Detalle del resumen diario</h5>
                    <small class="text-success">
                        <b>Serie y correlativo {{ $resumen->envio_resumen_serie . '-' . $resumen->envio_resumen_correlativo }}</b>
                    </small>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 text-center">
                            <p>Fecha de comprobantes</p>
                            <p>{{ \Carbon\Carbon::parse($resumen->envio_resumen_fecha)->format('d-m-Y') }}</p>
                        </div>

                        <div class="col-lg-4 text-center">
                            <p>Fecha de emisión</p>
                            <p>{{ \Carbon\Carbon::parse($resumen->envio_sunat_datetime)->format('d-m-Y') }}</p>
                        </div>

                        <div class="col-lg-4 text-center">
                            <p>N.º Ticket: {{ $resumen->envio_resumen_ticket }}</p>
                        </div>

                        <div class="col-lg-12 col-sm-12 col-md-12 mt-3">
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable1">
                                    <thead>
                                        <tr class="encabezado_tabla_color">
                                            <th>#</th>
                                            <th>Fecha de emisión</th>
                                            <th>Comprobante</th>
                                            <th>Serie y correlativo</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Condición del comprobante</th>
                                            <th>PDF</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($detalle as $index => $al)
                                        @php
                                            $tipo_comprobante = match($al->venta_tipo) {
                                                '03' => 'BOLETA',
                                                '01' => 'FACTURA',
                                                '07' => 'NOTA DE CRÉDITO',
                                                '08' => 'NOTA DE DÉBITO',
                                                default => '--'
                                            };

                                            if ($al->venta_condicion_resumen == 1) {
                                                $mensaje = 'REGISTRADO';
                                                $estilo_mensaje = 'color: green; font-size: 14px;';
                                            } elseif ($al->venta_condicion_resumen == 2) {
                                                $mensaje = 'MODIFICADO';
                                                $estilo_mensaje = 'color: blue; font-size: 14px;';
                                            } else {
                                                $mensaje = 'ANULADO';
                                                $estilo_mensaje = 'color: red; font-size: 14px;';
                                            }

                                            $cliente = $al->ven->id_tipo_documento == 4
                                                ? $al->ven->cliente_razonsocial
                                                : $al->ven->cliente_nombre;
                                        @endphp

                                        <tr style="text-align: center;">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($al->venta_fecha)->format('d-m-Y H:i:s') }}</td>
                                            <td>{{ $tipo_comprobante }}</td>
                                            <td>{{ $al->venta_serie . '-' . $al->venta_correlativo }}</td>
                                            <td>
                                                {{ $al->ven->cliente_numero }}<br>
                                                {{ $cliente }}
                                            </td>
                                            <td>{{ $al->ven->simbolo . ' ' . $al->venta_total }}</td>
                                            <td style="{{ $estilo_mensaje }}">{{ $mensaje }}</td>
                                            <td>
                                                <center>
                                                    <a target="_blank"
                                                       href="{{ route('Gestionventas.imprimir_ticket_pdf', ['venta_id' => $al->id_venta]) }}"
                                                       style="color: red"
                                                       data-bs-tooltip="tooltip"
                                                       data-bs-placement="top"
                                                       data-bs-title="Imprimir PDF">
                                                        <i class="fa fa-file-pdf"></i>
                                                    </a>
                                                </center>
                                            </td>
                                            <td>
                                                <a target="_blank"
                                                   class="btn btn-sm btn-primary btne"
                                                   href="{{ route('Gestionventas.venta_detalle', ['venta_id' => $al->id_venta]) }}"
                                                   data-bs-tooltip="tooltip"
                                                   data-bs-placement="top"
                                                   data-bs-title="Ver detalle">
                                                    <i class="fa fa-eye ver_detalle"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
@endsection

