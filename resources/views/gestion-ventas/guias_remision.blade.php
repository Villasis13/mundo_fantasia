@extends('layouts.plantilla')
@section('title','Guías de Remisión')
@section('content')
<div class="tab-content">
    <div id="vista_para_opciones_{{ $opciones[0]->id_opciones }}" class="tab-pane fade show active" role="tabpanel" tabindex="0">
        <div class="container-fluid py-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark mb-1">Módulo de Guías de Remisión</h3>
                <p class="text-muted">¿Qué desea hacer?</p>
            </div>

            <div class="row justify-content-center g-4">
                {{-- Generar Guía (funcional) --}}
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('Gestionventas.generar_guia') }}" class="text-decoration-none">
                        <div class="card shadow-sm border-0 h-100 text-center p-4" style="border-radius:16px;transition:transform .15s,box-shadow .15s;"
                             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 30px rgba(0,0,0,.15)'"
                             onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <div class="mb-3"><span style="font-size:3rem;color:#4451B6;"><i class="fas fa-file-alt"></i></span></div>
                            <h5 class="fw-bold text-dark">Generar Guía</h5>
                            <p class="text-muted small mb-0">Crea una nueva guía de remisión (remitente o transportista).</p>
                        </div>
                    </a>
                </div>

                {{-- Pendientes de envío --}}
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('Gestionventas.pendientes_guia') }}" class="text-decoration-none">
                        <div class="card shadow-sm border-0 h-100 text-center p-4" style="border-radius:16px;transition:transform .15s,box-shadow .15s;"
                             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 30px rgba(0,0,0,.15)'"
                             onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <div class="mb-3"><span style="font-size:3rem;color:#e63946;"><i class="fas fa-clock"></i></span></div>
                            <h5 class="fw-bold text-dark">Pendientes de Envío</h5>
                            @if(($pendientes ?? 0) > 0)
                                <span class="badge bg-danger mb-2">{{ $pendientes }} pendiente{{ $pendientes > 1 ? 's' : '' }}</span>
                            @endif
                            <p class="text-muted small mb-0">Guías generadas. Consúltalas y descarga su PDF.</p>
                        </div>
                    </a>
                </div>

                {{-- Historial (no disponible aún) --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card shadow-sm border-0 h-100 text-center p-4 opacity-50" style="border-radius:16px;cursor:not-allowed;" title="Próximamente">
                        <div class="mb-3"><span style="font-size:3rem;color:#2a9d8f;"><i class="fas fa-history"></i></span></div>
                        <h5 class="fw-bold text-dark">Historial de Envíos</h5>
                        <p class="text-muted small mb-0">Disponible próximamente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/domain.js') }}"></script>
@endsection
