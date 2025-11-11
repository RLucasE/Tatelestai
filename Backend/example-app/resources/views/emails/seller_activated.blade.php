<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cuenta de vendedor activada</title>
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
            background: #111827;
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
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #111827;
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
        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
            color: #4b5563;
            font-size: 14px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0 24px;
        }
        .cta {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            background: #111827;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.2s;
        }
        .cta:hover {
            background: #1f2937;
        }
        .divider {
            border: 0;
            height: 1px;
            background: #e5e7eb;
            margin: 24px 0;
        }
        .support {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .support p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .support strong {
            color: #78350f;
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
        <h1>🎉 ¡Cuenta Activada Exitosamente!</h1>
        <p>Tu establecimiento ha sido aprobado</p>
    </div>

    <div class="card">
        <p class="greeting">Estimado/a <strong>{{ $user->name }} {{ $user->last_name }}</strong>,</p>

        <div class="content">
            <p>Nos complace informarle que su solicitud para convertirse en vendedor en nuestra plataforma ha sido <strong>aprobada exitosamente</strong>.</p>

            <p>Su cuenta de vendedor ha sido activada y ya puede comenzar a gestionar su establecimiento y publicar ofertas para sus clientes.</p>
        </div>

        <div class="info-box">
            <h3>¿Qué puede hacer ahora?</h3>
            <ul>
                <li>Acceder a su panel de vendedor</li>
                <li>Gestionar los productos de su establecimiento</li>
                <li>Crear y publicar ofertas atractivas</li>
                <li>Administrar sus ventas y pedidos</li>
                <li>Actualizar la información de su establecimiento</li>
            </ul>
        </div>

        <div class="cta-container">
            <a class="cta" href="{{ config('app.url') }}" target="_blank" rel="noopener">Acceder a mi Panel</a>
        </div>

        <hr class="divider">

        <div class="content">
            <p><strong>Información importante:</strong></p>
            <p>Todas las ofertas que publique deberán cumplir con nuestras políticas y estarán sujetas a revisión antes de ser visibles para los clientes. Le notificaremos cuando cada oferta sea aprobada.</p>
        </div>

        <div class="support">
            <p><strong>¿Necesita ayuda?</strong> Si tiene alguna pregunta o necesita asistencia, no dude en contactarnos. Estamos aquí para ayudarle a tener éxito en nuestra plataforma.</p>
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
