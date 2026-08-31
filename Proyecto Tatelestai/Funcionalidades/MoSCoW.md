🔴 not implemented yet
🟡 implementing / problems
🟢 implemented
❓ I don't know
1. **M - Must Have** 
    
    - **Requisitos imprescindibles**: Sin ellos, el proyecto fracasa o no cumple objetivos. 
	    - Usuario general
		    - Puede registrarse con su correo 🟢
	    - Administrativas
			- Dar de alta un nuevo establecimiento 🟢
			- Aprobar un cambio de dirección de un establecimiento 🟢 
			- Aprobar el cambio de nombre de un establecimiento 🟢
			- Poder rechazar la solicitud de un vendedor 🟢 
			- Des-habilitar un customer/seller 🟢
			- Ver las compras que de un customer 🟢 
			- Ver las ventas de un seller 🟢
			- Ver las ofertas de un seller 🟢
			- Des-habilitar una oferta 🟢
				- El customer no debe poder cambiar de nuevo a activo 🟢
			- Ver las ventas que se hicieron  🟢
			-  Ser capaz de crear nuevos tipos de establecimientos 🟢
			- Agregar detalles a la petición de un nuevo local (Para que los usuarios administradores puedan comunicarse) 🔴
		- Compradores 
			- Ver las ofertas publicadas 🟢
			- Agregar una oferta al carrito 🟢
			- Comprar una serie de ofertas 🟢
			- Modificar cant de una oferta en el carrito 🟢
			- Quitar oferta del carrito 🟢
			- Quitar todas las ofertas del carrito 🟢
			- Hacer la compra de un carrito  🟢
			- Hacer la compra de una oferta 🟢
			- Que los detalles de las ofertas tengan la dirección del establecimiento 🟢 
			- Que el carrito tenga la información del establecimiento 🔴
			- El comprador recibe un mail con los datos para retirar su pedido una vez comprado 🔴
				- El pedido tiene un QR y un Codigo 🔴
				- Si la compra tiene varias ofertas el QR abarca todas 🔴
			- El usuario puede ver los codigos para retirar la compra 🔴
			- El usuario puede recibir notificación vie mail/app que su comra fue cancelada -> reembolso 🔴
		- Vendedores
			- Registrar su negocio 🟢
				- Registrar la dirección del local 🟢
				- Cambiar la dirección del establecimiento 🟢 
				- Cambiar el nombre del establecimiento 🟢
			- Recibir notificación vía mail que su negocio ya fue habilitado 🟢 
			- Mostrar las ventas que se hicieron  🟢
			- Puede agregar un producto 🟢
			- Crear oferta con uno o más productos 🟢
			- Des-habilitar una oferta 🟢
			- Confirmar la compra de un customer con el código/QR 🔴
			- Poder cancelar la compra -> Notificar al comprador 🔴
			- Los productos tienen fecha de caducidad 🔴
			- Una oferta puede ser Única o Reiterada 🔴
			- Una oferta Reiterada tiene Hora de Apertura,Cierre y Pick-Up 🔴
2. **S - Should Have** 🔴
    
    - **Requisitos importantes**: Añaden gran valor, pero el proyecto puede funcionar sin ellos a corto plazo.
	    - [[Motor de búsqueda]] 
			- Buscar una venta por numero de venta 
			- Buscador de ofertas 🟢 
	    - Administrativas 
		    - Ver reclamos
			    - Filtrar por tipo de reclamo
		- Vendedores
			- Confirmar y ocultar la venta 
			- Editar un producto
		- Compradores
			- Poder filtrar las ofertas por tipo de establecimiento 
			- Filtrar las ofertas por categoría 
			- timer de 10 min para hacer la compra por efectivo
			- Que los compradores tengan un % de compras concluidas
			- Notificar al vendedor de una nueva venta 
		- Abrir un reclamo 
			- Hacia un establecimiento 
			- Hacia un comprador 
			- En una oferta
			- En una compra/venta
		- Pasarela de Pago 
			- El usuario puede devolver el producto
		
3. **C - Could Have**  🔴
    
    - **Requisitos deseables**: Mejoras que no son esenciales. Se incluyen si hay recursos disponibles.  
	    - Administración 
		    - Poder elegir un motivo de sanción 
			- Crear motivos de sanción 
			- Editar motivos de sanción
			- Poder seleccionar motivo de des-habilitación de oferta
			- Agregar y quitar motivos  de des-habilitación de oferta
			- Registro de los establecimientos de un vendedor
			- A la hora de activar un establecimiento saber si es un cambio o un registro nuevo
	    - Geolocalización
		    - Filtrar las ofertas cercanas
		    - Ver locales registrados en mi localidad
		    - Ver la ubicación de una oferta
		- Ofertas
			- Las ofertas pueden tener una contabilización de cuantas personas las vieron
		- Usuarios
			- 
	    - Cacheo de solicitudes
4. **W - Won't Have** 🔴
    
    - **Requisitos descartados**: De bajo impacto o inviables en el ciclo actual. Pueden revisarse en el futuro.  
	    - Motor para recomendar ofertas
	    - Machine learning para detectar contenido no deseable
    