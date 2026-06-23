@extends('layouts.plantilla')
@section('title','Gestión de Almacenes')
@section('content')
    @livewire('configuracion.almacenes', [
        'idTienda'     => $idTienda,
        'nombreTienda' => $tienda->tienda_nombre  ?? null,
        'idEmpresa'    => $tienda->id_empresa     ?? null,
        'nombreEmpresa'=> $empresa->empresa_razon_social ?? null,
    ])
@endsection
