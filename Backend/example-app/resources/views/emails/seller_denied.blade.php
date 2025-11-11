<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitud de vendedor denegada</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .wrapper {
            max-width: 640px;
            margin: 40px auto;
            padding: 24px 16px;
        }
        .header {
            background: #9f1239;
            color: #ffffff;
            padding: 32px 24px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
            padding: 32px 24px;
        }
        .greeting {
            font-size: 16px;
            color: #374151;
            margin: 0 0 24px;
        }
        .greeting strong {
            color: #111827;
        }
        .content {
            margin-bottom: 24px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
            font-size: 15px;
        }
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #9f1239;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .alert-box h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #881337;
            font-weight: 600;
        }
        .alert-box p {
            margin: 8px 0 0;
            color: #7f1d1d;
            font-size: 14px;
        }
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #6b7280;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #111827;
            font-weight: 600;
        }
        .info-box p {
            margin: 8px 0 0;
            color: #4b5563;
            font-size: 14px;
        }
        .info-box ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
            color: #4b5563;
            font-size: 14px;
        }
        .divider {
            border: 0;
            height: 1px;
            background: #e5e7eb;
            margin: 24px 0;
        }
        .support {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .support p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
        }
        .support strong {
            color: #1e3a8a;
        }
        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
        .footer p {
            margin: 8px 0;
        }
        .signature {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .signature p {
            margin: 4px 0;
            color: #4b5563;
            font-size: 14px;
        }
        .signature strong {
            color: #111827;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>❌ Solicitud Denegada</h1>
        <p>No se ha aprobado tu solicitud de vendedor</p>
    </div>

    <div class="card">
        <p class="greeting">Estimado/a <strong>{{ $user->name }} {{ $user->last_name }}</strong>,</p>

        <div class="content">
            <p>Lamentamos informarle que su solicitud para convertirse en vendedor en nuestra plataforma ha sido <strong>denegada</strong> en esta ocasión.</p>

            <p>Esta decisión ha sido tomada por nuestro equipo de administración tras revisar cuidadosamente su solicitud y la información proporcionada.</p>
        </div>

        <div class="alert-box">
            <h3>Estado de su solicitud</h3>
            <p>Su solicitud ha sido rechazada y no podrá acceder a las funcionalidades de vendedor en este momento.</p>
        </div>

        <hr class="divider">

        <div class="content">
            <p><strong>Posibles razones para la denegación:</strong></p>
            <ul style="margin: 8px 0 0; padding-left: 20px; color: #4b5563;">
                <li>Documentación incompleta o incorrecta</li>
                <li>Información del establecimiento insuficiente</li>
                <li>No cumplimiento de los requisitos mínimos</li>
                <li>Duplicación de establecimiento ya existente</li>
                <li>Tipo de establecimiento no compatible con la plataforma</li>
                <li>Información no verificable o inconsistente</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>¿Qué puede hacer ahora?</h3>
            <p>Si desea volver a aplicar en el futuro, le recomendamos:</p>
            <ul>
                <li>Revisar los requisitos para vendedores en nuestra plataforma</li>
                <li>Asegurarse de proporcionar toda la documentación necesaria</li>
                <li>Verificar que la información sea completa y precisa</li>
                <li>Contactar con soporte para aclarar dudas antes de volver a aplicar</li>
            </ul>
        </div>

        <div class="support">
            <p><strong>¿Tiene preguntas?</strong> Si desea conocer los motivos específicos de la denegación o necesita orientación para una futura solicitud, no dude en contactarnos. Nuestro equipo de soporte estará encantado de ayudarle.</p>
        </div>

        <div class="signature">
            <p>Atentamente,</p>
            <p><strong>El equipo de Tatelestai</strong></p>
            <p style="color: #9ca3af; font-size: 13px;">{{ config('app.name') }}</p>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático generado por el sistema.</p>
            <p>Por favor, no responda directamente a este correo electrónico.</p>
            <p style="margin-top: 16px; color: #9ca3af; font-size: 12px;">
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</div>
</body>
</html>

