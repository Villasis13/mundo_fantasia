@extends('layouts.plantilla')
@section('title','Stock por Establecimiento')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('logistica.stock-establecimiento')
    </div>
</div>
@endsection
