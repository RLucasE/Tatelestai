Hay que mejorar
 Implementado
1. **M - Must Have** 
    
    - **Requisitos imprescindibles**: Sin ellos, el proyecto fracasa o no cumple objetivos. 
	    - Usuario general
		    - Puede registrarse con su correo 🟢
		    - Pedir confirmación de correo 🔴
		    - Recuperar contraseña 🔴
		    - Poder con google Auth 🔴
	    - Administrativas
			- Dar de alta un nuevo establecimiento 🟢
			- Aprobar un cambio de dirección de un establecimiento 🔴
			- Des-habilitar un customer/seller 🔴
			- Ver las compras que de un customer 🔴 
			- Ver las ventas de un seller 🔴
			- Des-habilitar una oferta 🔴
			- Ver las compras que se hicieron 🔴 
			- Ver las ventas que se hicieron  🔴
		- Compradores 
			- Ver las ofertas publicadas 🟢
			- Agregar una oferta al carrito 🟢
			- Comprar una serie de ofertas 🟢
			- Modificar cant de una oferta en el carrito 🟢
			- Quitar oferta del carrito 🟢
			- Quitar todas las ofertas del carrito 🟢
			- Hacer la compra de un carrito  🟢
			- Hacer la compra de una oferta 🔴
		- Vendedores
			- Registrar su negocio 🟢
			- Recibir notificación vía mail que su negocio ya fue habilitado 🔴
			- Notificar al vendedor de una nueva venta 🟡
			- Mostrar las ventas que se hicieron  🟢
			- Un vendedor puede cambiar de dirección 🔴
			- Puede agregar un producto 🟢
			- Crear oferta con uno o más productos 🟢
			- Des-habilitar una oferta ❓
2. **S - Should Have** 🔴
    
    - **Requisitos importantes**: Añaden gran valor, pero el proyecto puede funcionar sin ellos a corto plazo.
	    - [[Motor de búsqueda]] 
		    - Administración 
			    - Buscar una venta por numero de venta 
	    - Administrativas 
			
		- Vendedores
			- Confirmar y ocultar la venta 
			- Editar un producto
		- Compradores
			- Poder filtrar las ofertas por tipo de establecimiento 
			- Filtrar las ofertas por categoría 
		- Abrir un reclamo 
			- Hacia un establecimiento 
			- Hacia un comprador 
			- En una oferta
			- En una compra/venta
		- Pasarela de Pago 
			- El usuario puede reclamar 
		
3. **C - Could Have**  🔴
    
    - **Requisitos deseables**: Mejoras que no son esenciales. Se incluyen si hay recursos disponibles.  
	    - Administración 
		    - Poder elegir un motivo de sanción 
			- Crear motivos de sanción 
			- Editar motivos de sanción
			- Poder seleccionar motivo de des-habilitación de oferta
			- Agregar y quitar motivos  de des-habilitación de oferta
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
    