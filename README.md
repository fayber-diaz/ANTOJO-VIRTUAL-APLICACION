# 🍔 ANTOJO VIRTUAL • Edición XAMPP (PHP & MySQL)
**El epicentro digital de comida rápida, sabor callejero y parrilladas auténticas para Neiva, Huila.**

---

## 🚀 Arquitectura del Proyecto

Este proyecto ha sido mejorado y transformado de un prototipo frontend a una **Aplicación Web Full-Stack completa**, lista para ejecutarse directamente en **XAMPP (Apache + PHP 8.2 + MariaDB/MySQL)** sin requerir Node.js ni pasos de compilación complejos.

```
antojo-virtual-php/
├── index.php             # Aplicación principal del cliente (Explorar, Checkout y Rastreo)
├── admin.php             # Panel de administración de pedidos y cocina en tiempo real
├── install.php           # Asistente de diagnóstico y reinicio de base de datos MySQL
├── config/
│   └── db.php            # Conexión PDO a MySQL con auto-instalación integrada
├── database/
│   └── antojo_virtual.sql# Esquema completo de MySQL con datos semilla de Neiva
├── api/
│   ├── restaurants.php   # Endpoint REST para consultar restaurantes y filtros
│   ├── menu.php          # Endpoint REST de platos y opciones de personalización
│   ├── coupons.php       # Endpoint REST de validación de cupones de descuento
│   ├── orders.php        # Endpoint REST de creación y consulta de pedidos en MySQL
│   ├── tracking.php      # Endpoint REST de rastreo en vivo y coordenadas de Neiva
│   └── admin.php         # Endpoint REST para cambiar estados y métricas de ventas
└── assets/
    ├── css/
    │   └── style.css     # Sistema de diseño, fuentes Epilogue & Plus Jakarta Sans
    └── js/
        └── app.js        # Lógica Vanilla ES6, carrito, modales y rastreo dinámico
```

---

## 🌐 URLs de Acceso Local (XAMPP)

Una vez iniciado Apache y MySQL en el panel de control de XAMPP:

| Módulo | URL Local | Descripción |
| :--- | :--- | :--- |
| **Aplicación Cliente** | [http://localhost/antojo-virtual/](http://localhost/antojo-virtual/) | Catálogo, personalizador de carnes/panes, checkout y rastreo en vivo |
| **Panel de Cocina / Admin** | [http://localhost/antojo-virtual/admin.php](http://localhost/antojo-virtual/admin.php) | Gestión de pedidos recibidos, avance de estados y métricas |
| **Diagnóstico de Base de Datos** | [http://localhost/antojo-virtual/install.php](http://localhost/antojo-virtual/install.php) | Comprobación de conexión MySQL y reinicio de datos semilla |
| **phpMyAdmin** | [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/) | Administrador visual de MySQL para la base de datos `antojo_virtual` |

---

## 🌟 Características y Mejoras Implementadas

1. **Base de Datos MySQL (`antojo_virtual`)**:
   - Tablas relacionales normalizadas: `restaurants`, `menu_items`, `item_options`, `categories`, `coupons`, `riders`, `orders`, `order_items`, `order_tracking_events`.
   - Restaurantes huilenses representativos: *La Estación Burger*, *Salchipapería La 8va*, *El Patacón Callejero*, *Perros & Grill Opita*, *Mazorcadas del Magdalena*, *Arepas Don Huila*.

2. **Personalizador Interactivo de Platos (Modal)**:
   - Término de carne al carbón (Término Medio, Tres Cuartos 3/4, Bien Asada).
   - Tipos de pan artesanal (Brioche tradicional, Ajonjolí negro, Parmesano, Lechuga).
   - Toppings adicionales con contador interactivo (+ / -) y suma de precio en tiempo real (Tocineta ahumada, Huevo campesino, Queso de Algeciras, Doble carne, Chicharrón crocante).
   - Salsas caseras (Tártara de la casa, BBQ con guayaba huilense, Ajo suave, Ají opita).

3. **Flujo Completo de Checkout**:
   - Validación de datos del cliente, teléfono celular y dirección con selección de Comuna de Neiva (Comunas 1 a 10).
   - Métodos de pago colombianos: **Nequi**, **Daviplata**, **PSE**, **Tarjeta Débito/Crédito**, **Efectivo** (con cálculo de billete para cambio $50k / $100k) y **Datáfono**.
   - Cupón de descuento funcional: código `ANTOJONEIVA5K` (-$5.000 COP).

4. **Rastreo en Vivo (Live Tracking)**:
   - Generación de código único `#NV-XXXX`.
   - Asignación de repartidores reales de Neiva con nombre, moto, placas y foto.
   - Stepper de 4 etapas: **Confirmado** ➔ **Al Carbón** ➔ **En Camino** ➔ **¡Entregado!**.
   - Mapa interactivo de Neiva que traza la ruta por la Av. Circunvalar y el Río Magdalena.
   - Botones de contacto directo vía WhatsApp y llamada telefónica con el rider.

5. **Panel de Cocina / Restaurante (`admin.php`)**:
   - Permite al restaurante ver pedidos entrantes y avanzar el estado de la orden con un solo clic.
   - El cliente ve cómo su pantalla de rastreo se actualiza en tiempo real automáticamente sin recargar la página.
