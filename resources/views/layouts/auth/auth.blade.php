<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="ltr"
      data-assets-path="{{ asset('assets/') }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Acceso') · {{ config('app.name', 'ERP') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('isologo.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('fontawasone/css/all.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    @livewireStyles
    @stack('styles')

    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        html, body {
            height: 100%; margin: 0; padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(37, 99, 235, 0.18) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(56, 189, 248, 0.15) 0%, transparent 55%),
                radial-gradient(ellipse at 60% 85%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }

        /* Puntos decorativos de fondo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        /* Orbes de luz */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
        .bg-orb-1 {
            width: 500px; height: 500px;
            top: -150px; left: -150px;
            background: rgba(37, 99, 235, 0.25);
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            bottom: -100px; right: -100px;
            background: rgba(56, 189, 248, 0.2);
        }
        .bg-orb-3 {
            width: 300px; height: 300px;
            top: 40%; left: 55%;
            background: rgba(99, 102, 241, 0.15);
        }

        /* Card */
        .auth-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 340px;
            margin: 1rem;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            padding: 2rem 1.75rem 1.75rem;
            box-shadow:
                0 0 0 1px rgba(255,255,255,.08),
                0 24px 64px rgba(0, 0, 0, 0.45),
                0 4px 16px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
        }

        /* Logo dentro del card */
        .auth-card-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .auth-card-logo img {
            max-width: 180px;
            max-height: 80px;
            object-fit: contain;
        }
        .auth-card-logo-placeholder {
            width: 52px; height: 52px;
            border-radius: 14px;
            background: #1a56db;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.3rem;
        }

        /* Footer */
        .auth-footer {
            position: fixed;
            bottom: 1.25rem;
            left: 0; right: 0;
            text-align: center;
            font-size: .72rem;
            color: rgba(255,255,255,.25);
            z-index: 1;
        }
    </style>
</head>

<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <div class="auth-card">

        <div class="auth-card-logo">
            @if(file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}">
            @else
                <div class="auth-card-logo-placeholder">
                    <i class="fa-solid fa-store"></i>
                </div>
            @endif
        </div>

        @yield('content')

    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'ERP') }}. Todos los derechos reservados.
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('js/domain.js') }}"></script>

    @livewireScripts
    @stack('scripts')
</body>
</html>
