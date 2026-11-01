# Deluxe Car Agency (LUXE MOTORS MX)

Sistema web completo de agencia de autos de lujo desarrollado en PHP + MySQL.

---

## Descripción / Description

### Español
**Deluxe Car Agency** (también conocido como **LUXE MOTORS MX**) es una plataforma web para una agencia de vehículos de alto lujo. Permite a los clientes explorar un catálogo exclusivo de autos (Ferrari, Lamborghini, Porsche, Bentley, Rolls-Royce, Mercedes-Maybach, Tesla, Lucid, Range Rover, entre otros), configurar unidades, solicitar tasaciones, realizar operaciones de compraventa y gestionar planes de pago.

Incluye un panel de administración completo para la gestión de clientes, ventas, tasaciones y dashboard con estadísticas.

### English
**Deluxe Car Agency** (also known as **LUXE MOTORS MX**) is a complete web platform for a high-end luxury car dealership. Customers can browse an exclusive catalog of vehicles (Ferrari, Lamborghini, Porsche, Bentley, Rolls-Royce, Mercedes-Maybach, Tesla, Lucid, Range Rover, and more), configure units, request appraisals, handle buy/sell operations, and manage payment plans.

It includes a full administration panel for managing clients, sales, appraisals, and a statistics dashboard.

---

## Características principales / Main Features

- Catálogo de autos de lujo con imágenes por color
- Configurador de vehículos
- Sistema de compraventa y tasaciones
- Generación de facturas
- Planes de pago
- Panel de administración (clientes, ventas, tasaciones, dashboard)
- Diseño moderno y elegante (tema oscuro con acentos dorados)

---

## Tecnologías / Tech Stack

- PHP (PDO)
- MySQL
- HTML5 / CSS3 / JavaScript
- Bootstrap 5
- Font Awesome
- Google Fonts (Cormorant Garamond + DM Sans)

---

## Estructura del proyecto / Project Structure
agencia_v3/
├── admin/              # Panel de administración
│   ├── clientes.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── tasaciones.php
│   └── ventas.php
├── config/
│   └── db.php          # Conexión a la base de datos
├── imgs/autos/         # Imágenes de los vehículos
├── compraventa.php
├── config.php          # Configurador
├── factura.php
├── get_autos.php
├── index.php           # Página principal
├── info.php
├── navbar.php
└── plan_pagos.php

---

## Instalación / Installation

1. Clona el repositorio:
   ```bash
   git clone https://github.com/TU_USUARIO/deluxe_car_agency.git
2. Configura la base de datos en config/db.php (host, nombre de BD, usuario y contraseña).
3. Importa la base de datos agencia_db (estructura y datos según tu entorno).
4. Sirve el proyecto con un servidor local (XAMPP, Laragon, WAMP, etc.) apuntando a la carpeta del proyecto.


---

## Futuras mejoras / Future Improvements
Español:
Este proyecto se encuentra en desarrollo activo. Se planean las siguientes mejoras:
1. Sistema de autenticación de clientes (registro e inicio de sesión)
2. Carrito de compras y proceso de reserva online
3. Integración con pasarelas de pago
4. Filtros avanzados y búsqueda en el catálogo
5. Versión responsive mejorada y optimización móvil
6. Notificaciones por correo electrónico
7. Reportes avanzados en el panel de administración
8. Multidioma (Español / Inglés)
9. Mejoras de seguridad y validaciones

English:
This project is under active development. Planned future improvements include:
1. Client authentication system (registration and login)
2. Shopping cart and online reservation process
3. Payment gateway integration
4. Advanced filters and search in the catalog
5. Improved responsive design and mobile optimization
6. Email notifications
7. Advanced reports in the admin panel
8. Multilingual support (Spanish / English)
9. Security enhancements and validations
