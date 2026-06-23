<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title','Ventas') - Intranet - {{config('app.name')}}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"  href="{{asset('isologo.ico') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href=" https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    {{-- ICONOS    --}}
    <link rel="stylesheet" href="{{asset('fontawasone/css/all.css')}}">
    <link rel="stylesheet" href="{{asset('css/bootstrap-icons.css')}}">
    {{-- FIN DE ICONOS    --}}
    <link rel="stylesheet" href="{{asset('css/admin_global.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css')}}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css')}}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css')}}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css')}}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
    <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/helpers.js')}}"></script>
    <script src="{{ asset('assets/js/config.js')}}"></script>
    <script src="{{asset('js/jquery-3.6.3.min.js')}}"></script>
    <style>
        p{
            margin: 0px;
        }
        .autocomplete-container {
            position: relative;
        }
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #f7f7f7;
            max-height: 200px;
            overflow-y: auto;
        }
        .autocomplete-items div,
        .autocomplete-items a {
            padding: 10px;
            cursor: pointer;
            background-color: #f7f7f7;
            border-bottom: 1px solid #d4d4d4;
            color: #333;
            text-decoration: none;
            display: block;
        }

        .autocomplete-items div:hover {
            background-color: #e9e9e9;
        }

        .autocomplete-items div.active-suggestion {
            background-color: #aad6fb !important;
        }
    </style>

    @livewireStyles
</head>

<body>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
       @include('layouts.navbar')
        <div class="layout-page">
            @include('layouts.header')
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="row">
                        @if(isset($opciones) && count($opciones) > 0)
                            <div class="col-lg-12 col-md-12 col-sm-12 mb-3">
                                <div class="card">
                                    <div class="row nav nav-tabs" id="myTab" role="tablist">
                                        @php $a = 1 @endphp
                                        @foreach($opciones as $op)
                                            @if($a === 1)
                                                @can($op->opciones_funcion . '.opcion')
                                                    <div class="col-lg-3 col-md-6 col-sm-12 nav-item d-flex align-items-center justify-content-center">
                                                        <a class="btn btn-sm w-100 m-2 nav-link  active" id="opciones_{{$op->id_opciones}}" data-bs-toggle="tab"   href="#vista_para_opciones_{{$op->id_opciones}}"  role="tab" aria-controls="#vista_para_opciones_{{$op->id_opciones}}" aria-selected="true"  style="font-size: 14px;color: black;border-right: 20px!important;">
                                                            {{ $op->opciones_nombre }}
                                                        </a>
                                                    </div>
                                                @endcan
                                                @php
                                                    $a++;
                                                @endphp
                                            @else
                                                @can($op->opciones_funcion . '.opcion')
                                                    <div class="col-lg-3 col-md-6 col-sm-12 nav-item d-flex align-items-center justify-content-center">
                                                        <a class="btn btn-sm  w-100 m-2 nav-link " id="opciones_{{$op->id_opciones}}" data-bs-toggle="tab" href="#vista_para_opciones_{{$op->id_opciones}}"  role="tab" aria-controls="#vista_para_opciones_{{$op->id_opciones}}" aria-selected="false"  style="font-size: 14px;color: black;border-right: 20px!important;">
                                                            {{ $op->opciones_nombre }}
                                                        </a>
                                                    </div>
                                                @endcan
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                        @yield('content')
                    </div>
                </div>
                <div class="content-backdrop fade"></div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
</div>

<script src="{{asset('js/jquery-3.6.3.min.js')}}"></script>

<script src="{{asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>
<script src="{{asset('assets/vendor/js/menu.js')}}"></script>

<script src="{{asset('assets/js/main.js')}}"></script>

<script src="{{asset('js/domain.js')}}"></script>
<script src="{{asset('js/tours.js')}}"></script>
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>


    <script >
       $(document).ready(function() {
           var activeTab = localStorage.getItem('activeTab') || 'opciones_{{ ($opciones != null && count($opciones) > 0) ? $opciones[0]->id_opciones : '' }}';
           $('#myTab a[href="#' + activeTab + '"]').tab('show');
           $('#myTab a').on('shown.bs.tab', function (e) {
               var tabId = e.target.getAttribute('href').substr(1);
               localStorage.setItem('activeTab', tabId);
           });

           const tooltipTriggerList = document.querySelectorAll('[data-bs-tooltip="tooltip"]')
           const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
        })
    </script>
@livewireScripts
</body>
</html>
