@extends('layouts.plantilla')
@section('title','Pendientes de Declarar')
@section('content')
    <div class="tab-content">
        @can($opciones[0]->opciones_funcion . '.opcion')
            <div id="vista_para_opciones_{{ $opciones[0]->id_opciones }}"
                 class="tab-pane fade show active" role="tabpanel"
                 aria-labelledby="opciones_{{ $opciones[0]->id_opciones }}">
                @livewire('facturacion.pendientes-declarar')
            </div>
        @endcan

        @if(isset($opciones[1]))
            @can($opciones[1]->opciones_funcion . '.opcion')
                <div id="vista_para_opciones_{{ $opciones[1]->id_opciones }}"
                     class="tab-pane fade" role="tabpanel"
                     aria-labelledby="opciones_{{ $opciones[1]->id_opciones }}">
                    @livewire('facturacion.resumen-diario')
                </div>
            @endcan
        @endif
    </div>
@endsection
