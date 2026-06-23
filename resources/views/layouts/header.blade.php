<nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar"
>
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    {{-- ── Empresa / Tienda del usuario (izquierda, solo roles operativos) ── --}}
    @if(auth()->check())
    @php
        $roleIdTopbar     = (int) \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_id', auth()->user()->id_users)
            ->value('role_id');
        $infoTiendaTopbar = null;
        if ($roleIdTopbar > 3) {
            try {
                $infoTiendaTopbar = \Illuminate\Support\Facades\DB::table('user_tienda as ut')
                    ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
                    ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
                    ->where('ut.id_users', auth()->user()->id_users)
                    ->orderBy('ut.id_tienda')
                    ->select('e.empresa_nombrecomercial', 't.tienda_nombre')
                    ->first();
            } catch (\Exception $ex) {}
        }
    @endphp
    @if($infoTiendaTopbar)
    <div class="d-none d-md-flex align-items-center gap-2 border rounded-2 px-3 py-2 bg-light me-auto ms-2"
         style="font-size:.8rem; white-space:nowrap;">
        <i class="fa-solid fa-building text-primary opacity-75" style="font-size:.85rem;"></i>
        <span class="fw-bold text-dark">{{ $infoTiendaTopbar->empresa_nombrecomercial }}</span>
        <span class="text-muted">·</span>
        <i class="fa-solid fa-store text-muted" style="font-size:.75rem;"></i>
        <span class="text-muted">{{ $infoTiendaTopbar->tienda_nombre }}</span>
    </div>
    @endif
    @endif

    <div class="navbar-nav-right d-flex align-items-center {{ isset($infoTiendaTopbar) && $infoTiendaTopbar ? '' : 'ms-auto' }}" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">

            {{-- ── Selector global de empresa (solo superadmin) ──────── --}}
            @if(auth()->check() && auth()->user()->hasRole('superadmin'))
            @php
                $empresasActivas  = \Illuminate\Support\Facades\DB::table('empresa')
                    ->where('empresa_estado', '!=', '0')
                    ->orderBy('empresa_razon_social')
                    ->get(['id_empresa','empresa_razon_social','empresa_nombrecomercial']);
                $empresaActivaId  = session('empresa_activa_global_id');
                $empresaActivaNombre = $empresasActivas->firstWhere('id_empresa', $empresaActivaId)?->empresa_nombrecomercial
                    ?? $empresasActivas->firstWhere('id_empresa', $empresaActivaId)?->empresa_razon_social
                    ?? 'Todas las empresas';
            @endphp
            <li class="nav-item me-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                            style="font-size:.8rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <i class="fa-solid fa-building" style="font-size:.75rem;"></i>
                        <span class="text-truncate" style="max-width:140px;">{{ $empresaActivaNombre }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:220px; max-height:300px; overflow-y:auto;">
                        <li>
                            <form method="POST" action="{{ route('configuracion.empresa-activa') }}">
                                @csrf
                                <input type="hidden" name="id_empresa" value="0">
                                <button type="submit" class="dropdown-item small {{ !$empresaActivaId ? 'active fw-semibold' : '' }}">
                                    <i class="fa-solid fa-layer-group me-2"></i> Todas las empresas
                                </button>
                            </form>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        @foreach($empresasActivas as $emp)
                        <li>
                            <form method="POST" action="{{ route('configuracion.empresa-activa') }}">
                                @csrf
                                <input type="hidden" name="id_empresa" value="{{ $emp->id_empresa }}">
                                <button type="submit" class="dropdown-item small {{ $empresaActivaId == $emp->id_empresa ? 'active fw-semibold' : '' }}">
                                    <i class="fa-solid fa-building me-2 opacity-50"></i>
                                    {{ $emp->empresa_nombrecomercial ?: $emp->empresa_razon_social }}
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            @endif

            <li class="nav-item lh-1 me-3">
                <small class="d-block">
                    {{auth()->user()->nombre_users}}
                </small>
                <small class="text-end fw-bold text-dark"> {{ Auth::user()->roles()->first()->name }}</small>
            </li>
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{asset(auth()->user()->user_fotografia)}}" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" >
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{asset(auth()->user()->user_fotografia)}}" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">
                                        {{auth()->user()->nombre_users }}
                                    </span>
                                    <small class="text-muted">{{ Auth::user()->roles()->first()->name }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('admin.perfil')}}" >
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Perfil del Usuario</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('cerrar_session')}}">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
