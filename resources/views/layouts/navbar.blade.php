<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    @php
        $logoNavEmpresa    = null;
        $navSedeName       = null;
        $navRoleId         = null;
        $navEsPrivilegiado = false;
        $navEmpresas       = collect();
        $navRolNombre      = '';
        try {
            if (auth()->check()) {
                $navRoleId = (int) DB::table('model_has_roles')
                    ->where('model_id', auth()->user()->id_users)
                    ->value('role_id');

                // Roles 1 (Desarrollador), 2 (Superadmin), 3 (Administrador)
                $navEsPrivilegiado = in_array($navRoleId, [1, 2, 3]);
                $navRolNombre      = DB::table('roles')->where('id', $navRoleId)->value('name') ?? '';

                if ($navEsPrivilegiado) {
                    // Desarrollador, Superadmin y Administrador: todas las empresas activas
                    $navEmpresas = DB::table('empresa')
                        ->where('empresa_estado', '!=', '0')
                        ->orderBy('empresa_razon_social')
                        ->get(['id_empresa', 'empresa_nombrecomercial', 'empresa_foto']);
                } else {
                    $navTiendaRow = DB::table('user_tienda as ut')
                        ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
                        ->join('empresa as e', 'e.id_empresa', '=', 't.id_empresa')
                        ->where('ut.id_users', auth()->user()->id_users)
                        ->orderBy('ut.id_tienda')
                        ->select('e.empresa_foto', 't.tienda_nombre')
                        ->first();
                    $logoNavEmpresa = $navTiendaRow?->empresa_foto;
                    $navSedeName    = $navTiendaRow?->tienda_nombre;
                    // Preferir la sede activa de sesión si existe
                    $sesionSede = session('sucursal_activa_id');
                    if ($sesionSede) {
                        $navSedeName = DB::table('tiendas')->where('id_tienda', $sesionSede)->value('tienda_nombre') ?? $navSedeName;
                    }
                }
            }
        } catch (\Exception $e) {}

        // Badge por rol
        $navBadgeConfig = match($navRoleId) {
            1 => ['color' => '#7c3aed', 'icono' => 'fa-solid fa-code',        'label' => 'Desarrollador'],
            2 => ['color' => '#16a34a', 'icono' => 'fa-solid fa-globe',       'label' => 'Superadmin'],
            3 => ['color' => '#2563eb', 'icono' => 'fa-solid fa-user-shield', 'label' => 'Administrador'],
            default => null,
        };
    @endphp

    <div class="app-brand demo d-flex flex-column align-items-center pt-2 pb-1 px-2">

        {{-- Logo principal (solo no-privilegiados) --}}
        @if(!$navEsPrivilegiado)
        <a href="{{ route('admin') }}" class="app-brand-link mb-1">
            <span class="app-brand-logo demo">
                <img src="{{ ($logoNavEmpresa && $logoNavEmpresa !== 'sin-logo.png') ? asset($logoNavEmpresa) : asset('logo.png') }}" alt="" style="width:140px;">
            </span>
        </a>
        @if($navSedeName)
        <div class="d-flex align-items-center gap-1 rounded-pill px-2 py-1 mb-1"
             style="background:#f1f5f9; border:1px solid #e2e8f0; font-size:.73rem; max-width:155px;">
            <i class="fa-solid fa-store" style="color:#64748b; font-size:.68rem; flex-shrink:0;"></i>
            <span style="color:#334155; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                {{ $navSedeName }}
            </span>
        </div>
        @endif
        @endif

        {{-- Logos de empresas + badge (solo privilegiados) --}}
        @if($navEsPrivilegiado && $navBadgeConfig)
            @if($navEmpresas->count() > 0)
            <a href="{{ route('admin') }}" class="d-flex flex-wrap justify-content-center gap-1 mb-1 text-decoration-none" style="max-width:200px;">
                @foreach($navEmpresas as $emp)
                @php $fotoEmp = $emp->empresa_foto && $emp->empresa_foto !== 'sin-logo.png' ? asset($emp->empresa_foto) : asset('logo.png'); @endphp
                <div title="{{ $emp->empresa_nombrecomercial }}"
                     style="width:44px;height:44px;border-radius:8px;overflow:hidden;border:1.5px solid {{ $navBadgeConfig['color'] }}22;background:#fff;flex-shrink:0;">
                    <img src="{{ $fotoEmp }}" alt="{{ $emp->empresa_nombrecomercial }}"
                         style="width:100%;height:100%;object-fit:contain;">
                </div>
                @endforeach
            </a>
            @endif

            <a href="{{ route('admin') }}" class="d-flex align-items-center gap-1 rounded-pill px-3 py-1 mb-1 text-decoration-none"
                 style="background:{{ $navBadgeConfig['color'] }}18; border:1px solid {{ $navBadgeConfig['color'] }}44; font-size:.82rem;">
                <i class="{{ $navBadgeConfig['icono'] }}" style="color:{{ $navBadgeConfig['color'] }}; font-size:.75rem;"></i>
                <span style="color:{{ $navBadgeConfig['color'] }}; font-weight:600; letter-spacing:.03em;">
                    {{ $navBadgeConfig['label'] }}
                </span>
            </a>
        @endif

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large d-block d-xl-none align-self-end mt-n4">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Layouts -->
        @php
            $menu = app('menu');
        @endphp
        @foreach($menu as $m)
            @can($m->menu_controlador . '.menu')
                @php $menu = explode('.',Request::route()->getName()) @endphp
                <li class="menu-item {{ ($menu[0]==$m->menu_controlador)?'open':''  }}  ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle  ">
                        <i class="menu-icon {{$m->menu_icono}}"></i>
                        <div>{{$m->menu_nombre }}</div>
                    </a>
                    <ul class="quitar_hover_navbar menu-sub">
                        @foreach($m->submenu as $sub)
                            @can($sub->submenu_funcion . '.submenu')
                            @if($menu[0] != "admin")
                                <li class="menu-item {{ ($sub->submenu_funcion == $menu[1]) ? 'active' : '' }}">
                            @else
                                <li class="menu-item">
                            @endif
                                <a href="{{url($m->menu_controlador.'/'.$sub->submenu_funcion)}}" class="quitar_hover_op menu-link">
                                    <div>{{$sub->submenu_nombre}}</div>
                                </a>
                            </li>
                            @endcan
                        @endforeach
                    </ul>
                </li>
            @endcan
        @endforeach

    </ul>
</aside>
