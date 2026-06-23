<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicado Interno</title>
    <link rel="icon" type="image/x-icon"  href="{{asset('isologo.ico') }}" />
    <style>
        /* Estilos generales y reset para clientes de correo */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f5f7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .containerBody{

        }
        .containerBody p{
            font-size: 16px;
            color: #333333;
            line-height: 1.6;
        }
        /* Utilidades responsivas */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: 0 !important; border-radius: 0 !important; }
            .fluid-img { width: 100% !important; max-width: 100% !important; height: auto !important; }
            .mobile-padding { padding: 20px !important; }
            .mobile-stack { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7;">

<!-- Contenedor Principal -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f5f7; padding: 40px 0;">
    <tr>
        <td align="center">

            <!-- Tabla del Correo -->
            <table border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">

                <!-- Barra de Acento Superior (Colores de la marca) -->
                <tr>
                    <td height="6" style="line-height: 6px; font-size: 6px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" height="6">
                            <tr>
                                <td width="33.3%" bgcolor="#DC00E2"></td>
                                <td width="33.3%" bgcolor="#BFFF00"></td>
                                <td width="33.4%" bgcolor="#000000"></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Cabecera -->
                <tr>
                    <td align="center" bgcolor="#0B1892" style="padding: 30px 40px;" class="mobile-padding">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <!-- Logo Placeholder -->
                                <td align="left" valign="middle" class="mobile-stack">
                                    <div style="color: #ffffff; width: 100px; font-weight: bold; letter-spacing: 1px;">
                                        <img src="{{asset('logo.png')}}" style="width: 100%" alt="">
                                    </div>
                                </td>
                                <!-- Etiqueta de Uso Interno -->
                                <td align="right" valign="middle" class="mobile-stack">
                                    <span style="display: inline-block; background-color: #BFFF00; color: #000000; font-size: 11px; font-weight: bold; padding: 6px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;">
                                        Comunicado Interno
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- Cuerpo del Correo -->
                <tr>
                    <td style="padding: 40px;" class="mobile-padding">

                        <h1 style="margin: 0 0 20px 0; font-size: 24px; color: #000000; font-weight: bold; line-height: 1.3;">
                            {{ $subject ?? 'Notificación' }}
                        </h1>

                        <div style="margin: 0 0 25px 0;" class="containerBody">
                            {!! $messageBody ?? '' !!}
                        </div>
                    </td>
                </tr>

                <!-- Firma / Despedida -->
                <tr>
                    <td style="padding: 0 40px 40px 40px;" class="mobile-padding">
                        <p style="margin: 0; font-size: 16px; color: #333333; line-height: 1.6;">
                            Atentamente,<br>
                            <strong style="color: #0B1892;">{{$remitente ?? 'ASSU'}}</strong>
                        </p>
                    </td>
                </tr>

                <!-- Pie de página (Footer) -->
                <tr>
                    <td bgcolor="#000000" style="padding: 30px 40px;" class="mobile-padding">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="center" style="font-size: 12px; color: #888888; line-height: 1.5; padding-bottom: 15px;">
                                    Este correo electrónico y cualquier archivo adjunto son confidenciales y están destinados únicamente para el uso de la empresa. Si usted no es el destinatario previsto, por favor elimine este correo.
                                </td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <span style="color: #ffffff; font-size: 12px; font-weight: bold;">
                                        &copy; {{date('Y')}} ASSU. Todos los derechos reservados.
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
