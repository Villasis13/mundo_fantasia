@extends('layouts.plantilla')
@section('title','Gestión de Productos')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('logistica.gestion-productos')
    </div>
    @if(isset($opciones[1]))
    <div id="vista_para_opciones_{{$opciones[1]->id_opciones}}"
         class="tab-pane fade" role="tabpanel" tabindex="0">
        @livewire('logistica.ajuste-stock')
    </div>
    @endif
</div>
@endsection
