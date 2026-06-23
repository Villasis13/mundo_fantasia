@extends('layouts.plantilla')
@section('title','Gestión de Cajas')
@section('content')
    @livewire('configuracion.cajas', [
        'idTienda'    => isset($idTienda) ? (int) $idTienda : 0,
        'idSucursal'  => 0,
    ])
@endsection
