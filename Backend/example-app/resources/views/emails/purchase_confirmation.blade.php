<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confirmación de Compra</title>
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
            background: #059669;
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
        .pickup-code-box {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 24px;
            border-radius: 10px;
            text-align: center;
            margin: 24px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .pickup-code-box h2 {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .pickup-code {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 8px;
            margin: 8px 0;
            font-family: 'Courier New', monospace;
        }
        .establishment-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin: 24px 0;
            border-radius: 8px;
        }
        .establishment-info h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #111827;
            font-weight: 600;
        }
        .establishment-info p {
            margin: 8px 0;
            color: #4b5563;
            font-size: 14px;
        }
        .establishment-info strong {
            color: #111827;
        }
        .offers-list {
            margin: 24px 0;
        }
        .offers-list h3 {
            margin: 0 0 16px;
            font-size: 16px;
            color: #111827;
            font-weight: 600;
        }
        .offer-item {
            background: #f9fafb;
            border-left: 4px solid #059669;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        .offer-item h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #111827;
            font-weight: 600;
        }
        .offer-item p {
            margin: 4px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .offer-item .quantity {
            color: #059669;
            font-weight: 600;
        }
        .offer-item .price {
            color: #111827;
            font-weight: 700;
            font-size: 16px;
        }
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
        }
        .info-box strong {
            color: #1e3a8a;
        }
        .divider {
            border: 0;
            height: 1px;
            background: #e5e7eb;
            margin: 24px 0;
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
        <h1>🎉 ¡Compra Exitosa!</h1>
        <p>Tu pedido ha sido confirmado</p>
    </div>

    <div class="card">
        <div class="greeting">
            ¡Hola <strong>{{ $sell->customer->name }}</strong>!
        </div>

        <div class="content">
            <p>
                Tu compra ha sido procesada exitosamente. A continuación encontrarás toda la información necesaria para retirar tu pedido.
            </p>
        </div>

        <div class="pickup-code-box">
            <h2>Código de Retiro</h2>
            <div class="pickup-code">{{ $sell->pickup_code }}</div>
            <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">
                Presenta este código al retirar tu pedido
            </p>
        </div>

        <div class="establishment-info">
            <h3>📍 Establecimiento</h3>
            <p><strong>Nombre:</strong> {{ $sell->foodEstablishment->name }}</p>
            <p><strong>Dirección:</strong> {{ $sell->foodEstablishment->address }}</p>
        </div>

        <div class="offers-list">
            <h3>📦 Detalles de tu compra</h3>
            @foreach($sell->sellDetails as $detail)
                <div class="offer-item">
                    <h4>{{ $detail->product_name }}</h4>
                    <p>{{ $detail->product_description }}</p>
                    <p>
                        <span class="quantity">Cantidad: {{ $detail->offer_quantity }} x {{ $detail->product_quantity }} unidades</span>
                    </p>
                    <p>
                        <span class="price">Precio: ${{ number_format($detail->product_price, 2) }}</span>
                    </p>
                </div>
            @endforeach
        </div>

        <hr class="divider" />

        <div class="info-box">
            <p>
                <strong>💡 Importante:</strong> Guarda este email y presenta el código de retiro al llegar al establecimiento. El personal te entregará tu pedido.
            </p>
        </div>

        <div class="signature">
            <p><strong>¡Gracias por tu compra!</strong></p>
            <p>El equipo de Tatelestai</p>
        </div>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Tatelestai. Todos los derechos reservados.</p>
        <p>Este es un email automático, por favor no respondas a este mensaje.</p>
    </div>
</div>
</body>
</html>

