# Cómo Realizar la Verificación Manual de Códigos QR de Retiro

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Proveer una lista de chequeo (*checklist*) paso a paso para la verificación manual y de control de calidad (QA) del sistema de códigos QR en dispositivos reales, validando hardware de cámara, condiciones ópticas, clientes de correo y eventos en tiempo real.

---

## Prerrequisitos

* Contenedores de Tatelestai en ejecución (`docker compose up -d`).
* Servidor Vite de Frontend activo (`http://localhost:3000` o la IP de red local para acceso móvil).
* Un dispositivo móvil o dos pestañas de navegador (una con sesión de **Cliente** y otra con sesión de **Vendedor**).
* Acceso a Mailpit (`http://localhost:8025`) para inspeccionar correos generados localmente.

---

## 1. Dispositivos Físicos y Control de Hardware de Cámara

Al escanear códigos QR en mostrador intervienen factores de hardware, permisos de navegador y condiciones ópticas ambientales:

- [ ] **Permisos de Cámara en el Navegador**:
  - Al presionar **"Escanear QR"** por primera vez en el Dashboard del Vendedor o en `VerifyCode.vue`, el navegador debe solicitar acceso a la cámara.
  - Comprobar que si el usuario rechaza el permiso, la UI muestra un mensaje amigable indicando habilitar el permiso en la configuración del navegador en vez de un fallo silencioso.
- [ ] **Selector de Cámaras y Cámara Trasera por Defecto**:
  - En smartphones (Android e iOS), el visor debe inicializar con la lente trasera (`facingMode: "environment"`).
  - En dispositivos con múltiples lentes, el selector desplegable debe listar las opciones disponibles y permitir alternar sin reiniciar la página.
- [ ] **Liberación de la Cámara al Salir**:
  - Al cerrar el modal con el botón **✕** o presionar la tecla `Escape`, la luz/indicador de la cámara debe apagarse de inmediato, garantizando que todos los *tracks* del `MediaStream` se detuvieron (`stream.getTracks().forEach(t => t.stop())`).
- [ ] **Lectura bajo Diferentes Condiciones Ópticas**:
  - **Desde otra pantalla**: Escanear el QR exhibido en el celular del comprador (con brillo al 50% y al 100%).
  - **Reflejos y brillo**: Verificar que el marco de encuadre detecte el QR incluso con reflejos moderados de luces de salón.
  - **Papel impreso**: Imprimir el comprobante y verificar la legibilidad a una distancia de 15 a 30 cm.

---

## 2. Flujo de Mostrador del Vendedor (Validación en 2 Pasos)

- [ ] **Paso 1: Detección y Presentación de Datos**:
  - Al enfocar un QR válido (`TAT:XXXX-XXXX-XXXX:ID`), el escáner debe emitir el sonido de confirmación (*beep* auditivo) y cerrarse automáticamente.
  - El formulario debe poblarse instantáneamente con el código y mostrar la tarjeta de detalle:
    - Nombre y correo del cliente.
    - Packs sorpresa adquiridos y cantidades.
    - Monto total a cobrar/entregar.
- [ ] **Paso 2: Confirmación y Cierre de Reserva**:
  - Al presionar **"Confirmar Entrega"**:
    - El botón debe entrar en estado de carga (*loading spinner*).
    - Debe mostrarse el toast de éxito: *"Venta completada exitosamente"*.
    - El pedido debe desaparecer de las "Recolecciones Pendientes" del día y pasar a "Retiradas".
- [ ] **Prevención de Doble Canje (*Replay Attack*)**:
  - Si el vendedor vuelve a escanear inmediatamente el mismo QR, la aplicación debe mostrar el error: *"El pedido no se encuentra disponible para ser retirado"* sin generar cambios en la base de datos.
- [ ] **Entrada Manual de Contingencia**:
  - Simular un cliente con la pantalla del teléfono rota o sin batería:
  - Ingresar manualmente el código alfanumérico en minúsculas y sin guiones (ej. `2x3y4z5a6b7c`) y pulsar "Verificar".
  - Comprobar que la normalización automática agrega las mayúsculas/guiones y encuentra el pedido sin inconvenientes.

---

## 3. Experiencia y Visualización del Comprador (Customer)

- [ ] **Acceso al QR desde Retorno de Pago**:
  - Tras completar un pago simulado en Mercado Pago (`/payment/return?collection_status=approved`), la pantalla debe desplegar el modal del QR de retiro de forma automática y destacada.
- [ ] **Acceso al QR desde Mis Reservas**:
  - En `/customer/reservations` o en el historial, cada tarjeta de pedido pendiente debe tener visible el botón con ícono de QR **"Ver QR"**.
- [ ] **Diseño de Alto Contraste para Lectura Rápida**:
  - En el modal `PickupQrModal.vue`, comprobar que el recuadro que rodea el QR tenga fondo blanco sólido (`#FFFFFF`) con margen de silencio (*quiet zone*) adecuado, evitando interferencias con el fondo oscuro general de la app (`#1A1625`).
- [ ] **Portapapeles y Compartir**:
  - Pulsar el botón **"Copiar código"** junto al código alfanumérico y verificar que copie la cadena formateada al portapapeles con feedback en pantalla (*"Copiado"*).

---

## 4. Visualización en Clientes de Correo Electrónico

Abrir Mailpit (`http://localhost:8025`) o enviar un correo de prueba a bandejas reales:

- [ ] **Gmail (Web y App Móvil)**:
  - Comprobar que el código QR aparezca visible de manera inmediata sin el aviso *"Mostrar imágenes externas"*, gracias a la incrustación inline `cid:pickup-qr.png`.
- [ ] **Outlook / Apple Mail**:
  - Verificar que el diseño mantenga la alineación centrada y que el código numérico de contingencia sea legible debajo de la imagen.
- [ ] **Plan de Contingencia sin Imágenes**:
  - Si el cliente de correo tiene las imágenes completamente bloqueadas por política corporativa, confirmar que el código alfanumérico en texto (`XXXX-XXXX-XXXX`) y los datos del establecimiento son completamente legibles.

---

## 5. Notificaciones y Sincronización en Tiempo Real (Reverb WebSockets)

- [ ] **Aviso al Comprador en Tiempo Real**:
  - Mantener la sesión del comprador abierta en una ventana de navegador.
  - En otra ventana o dispositivo, completar el canje como vendedor.
  - Comprobar que en menos de 1 segundo:
    - El cliente recibe la notificación flotante (*toast*): *"¡Pack retirado con éxito!"*.
    - Se incrementa el contador de la campana de notificaciones.
    - La tarjeta del pedido en el historial del cliente se actualiza automáticamente al estado *"Retirado"*.
