<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recuperación de contraseña</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f5fb; font-family: 'Inter', Arial, sans-serif; }
        .wrapper { max-width: 540px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(145deg, #1a3a6e 0%, #1a56db 60%, #38bdf8 100%); padding: 36px 40px; text-align: center; }
        .header h1 { margin: 0; color: #fff; font-size: 1.4rem; font-weight: 700; }
        .header p  { margin: 6px 0 0; color: rgba(255,255,255,.75); font-size: .9rem; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: .95rem; line-height: 1.7; margin: 0 0 16px; }
        .btn { display: inline-block; margin: 8px 0 24px; padding: 14px 32px; background: #1a56db; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: .95rem; }
        .notice { font-size: .82rem; color: #9ca3af; margin-top: 8px; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; font-size: .78rem; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>Recuperación de contraseña</p>
    </div>
    <div class="body">
        <p>Hemos recibido una solicitud para restablecer la contraseña asociada a tu cuenta. Si fuiste tú, haz clic en el siguiente botón:</p>
        <div style="text-align:center;color: white">
            <a href="{{ $resetUrl }}" style="color: white" class="btn">Restablecer contraseña</a>
        </div>
        <p>Este enlace expirará en <strong>60 minutos</strong>. Si no solicitaste este cambio, puedes ignorar este correo; tu contraseña no será modificada.</p>
        <p class="notice">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
            <a href="{{ $resetUrl }}" style="color:#1a56db;word-break:break-all">{{ $resetUrl }}</a>
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
    </div>
</div>
</body>
</html>
