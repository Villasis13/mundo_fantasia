@extends('layouts.plantilla')
@section('title','Compras')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('logistica.compras')
    </div>

    @if(isset($opciones[1]))
    <div id="vista_para_opciones_{{$opciones[1]->id_opciones}}"
         class="tab-pane fade" role="tabpanel" tabindex="0">
        @livewire('logistica.recepcion-compras')
    </div>
    @endif
</div>
@endsection
