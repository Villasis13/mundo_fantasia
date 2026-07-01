@extends('layouts.plantilla')
@section('title','Envío y Recepción de Guías SUNAT')
@section('content')
<div class="tab-content">
    @can($opciones[0]->opciones_funcion . '.opcion')
    <div id="vista_para_opciones_{{ $opciones[0]->id_opciones }}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('facturacion.envio-guias-sunat')
    </div>
    @endcan
</div>
<script src="{{asset('js/domain.js')}}"></script>
@endsection
