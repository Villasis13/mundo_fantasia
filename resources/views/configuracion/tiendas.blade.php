@extends('layouts.plantilla')
@section('title','Gestión de Tiendas')
@section('content')
    @livewire('configuracion.tiendas', [
        'idEmpresa'    => $idEmpresa,
        'nombreEmpresa'=> $empresa->empresa_razon_social ?? null,
        'idGrupo'      => $empresa->id_grupo ?? null,
    ])
@endsection
