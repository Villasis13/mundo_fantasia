@extends('layouts.plantilla')
@section('title','Gestión de Grupos')
@section('content')
    <div class="tab-content">
        <div id="vista_para_opciones_{{$opciones[0]->id_opciones}}" class="tab-pane fade show active " role="tabpanel" aria-labelledby="opciones_{{$opciones[0]->id_opciones}}" tabindex="0">
            @livewire('configuracion.grupos')
        </div>
    </div>
    <script src="{{asset('js/domain.js')}}"></script>
@endsection
