@extends('layouts.plantilla')
@section('title','Venta de Servicios')
@section('content')

    <div class="tab-content">
        @can($opciones[0]->opciones_funcion . '.opcion')
        <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
             class="tab-pane fade show active"
             role="tabpanel"
             aria-labelledby="opciones_{{$opciones[0]->id_opciones}}">

            <livewire:gestion-ventas.venta-servicios />

        </div>
        @endcan
    </div>

    <script src="{{asset('js/domain.js')}}"></script>

@endsection
