@extends('layouts.plantilla')
@section('title','Alertas SUNAT')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('facturacion.alertas-sunat')
    </div>
</div>
@endsection
