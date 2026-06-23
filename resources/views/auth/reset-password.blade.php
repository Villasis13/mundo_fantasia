@extends('layouts.auth.auth')

@section('title', 'Nueva Contraseña')

@section('content')
    @livewire('auth.reset-password', ['token' => $token])
@endsection
