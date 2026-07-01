@extends('layouts.plantilla')
@section('title','Registro de Ventas')
@section('content')
<div class="tab-content">
    @can($opciones[0]->opciones_funcion . '.opcion')
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('gestion-ventas.registro-ventas')
    </div>
    @endcan
</div>
<script src="{{asset('js/domain.js')}}"></script>
@endsection
