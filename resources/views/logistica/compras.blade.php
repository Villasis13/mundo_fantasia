@extends('layouts.plantilla')
@section('title','Compras')
@section('content')

    {{-- Tabs (mismo diseño que Gestión de Productos) --}}
    <div class="col-lg-12 col-md-12 col-sm-12 mb-3">
        <div class="card">
            <div class="row nav nav-tabs" id="tabsCompras" role="tablist">
                <div class="col-lg-3 col-md-6 col-sm-12 nav-item d-flex align-items-center justify-content-center">
                    <a class="btn btn-sm w-100 m-2 nav-link active" id="tab_registro_ingresos" data-bs-toggle="tab"
                       href="#vista_registro_ingresos" role="tab" aria-controls="vista_registro_ingresos"
                       aria-selected="true" style="font-size: 14px;color: black;border-right: 20px!important;">
                        Registro de Ingresos - Compras
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 nav-item d-flex align-items-center justify-content-center">
                    <a class="btn btn-sm w-100 m-2 nav-link" id="tab_listar_ingresos" data-bs-toggle="tab"
                       href="#vista_listar_ingresos" role="tab" aria-controls="vista_listar_ingresos"
                       aria-selected="false" style="font-size: 14px;color: black;border-right: 20px!important;">
                        Listar ingresos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content">
        <div id="vista_registro_ingresos" class="tab-pane fade show active" role="tabpanel" tabindex="0">
            @livewire('logistica.compras')
        </div>
        <div id="vista_listar_ingresos" class="tab-pane fade" role="tabpanel" tabindex="0">
            @livewire('logistica.listar-ingresos')
        </div>
    </div>

    <script>
        (function () {
            var STORAGE_KEY = 'tabsComprasActivo';
            function initTabs() {
                var guardado = localStorage.getItem(STORAGE_KEY);
                if (guardado) {
                    var link = document.querySelector('#tabsCompras a[href="' + guardado + '"]');
                    if (link) { bootstrap.Tab.getOrCreateInstance(link).show(); }
                }
                document.querySelectorAll('#tabsCompras a[data-bs-toggle="tab"]').forEach(function (a) {
                    a.addEventListener('shown.bs.tab', function (e) {
                        localStorage.setItem(STORAGE_KEY, e.target.getAttribute('href'));
                    });
                });
            }
            if (document.readyState !== 'loading') { initTabs(); }
            else { document.addEventListener('DOMContentLoaded', initTabs); }
        })();
    </script>

@endsection
