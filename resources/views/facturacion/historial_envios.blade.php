@extends('layouts.plantilla')
@section('title','Historial de Envíos')
@section('content')
    <div class="tab-content">
        @can($opciones[0]->opciones_funcion . '.opcion')
            <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}" class="tab-pane fade show active  " role="tabpanel" aria-labelledby="opciones_{{$opciones[0]->id_opciones}}" >
                @livewire('facturacion.ventas-sunat')
            </div>
        @endcan
        @can($opciones[1]->opciones_funcion . '.opcion')
            <div id="vista_para_opciones_{{$opciones[1]->id_opciones}}" class="tab-pane fade show  " role="tabpanel" aria-labelledby="opciones_{{$opciones[1]->id_opciones}}" >
                @livewire('facturacion.resumen-diario-sunat')
            </div>
        @endcan
        @can($opciones[2]->opciones_funcion . '.opcion')
            <div id="vista_para_opciones_{{$opciones[2]->id_opciones}}" class="tab-pane fade show  " role="tabpanel" aria-labelledby="opciones_{{$opciones[2]->id_opciones}}" >
                @livewire('facturacion.comunicacion-baja')
            </div>
        @endcan
    </div>
    <script src="{{asset('js/domain.js')}}"></script>
@endsection
