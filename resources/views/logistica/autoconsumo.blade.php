@extends('layouts.plantilla')
@section('title','Autoconsumo')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('logistica.autoconsumo')
    </div>
</div>
@endsection
