@extends('layouts.plantilla')
@section('title','Cuentas por Cobrar')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}"
         class="tab-pane fade show active" role="tabpanel" tabindex="0">
        @livewire('cx-c.cuentas-cobrar')
    </div>
</div>
<script src="{{asset('js/domain.js')}}"></script>
@endsection
