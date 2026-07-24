@extends('layouts.plantilla')
@section('title','Inventario Permanente')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('reporte.inventario-permanente')
    </div>
</div>
<script src="{{asset('js/domain.js')}}"></script>
@endsection
