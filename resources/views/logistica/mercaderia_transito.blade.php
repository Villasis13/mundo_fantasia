@extends('layouts.plantilla')
@section('title','Mercadería en Tránsito')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('logistica.mercaderia-transito')
    </div>
</div>
@endsection
