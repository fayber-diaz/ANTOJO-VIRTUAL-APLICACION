# ☕ ANTOJO VIRTUAL v3.0 — Aplicación Web 100% JAVA + MySQL (XAMPP) + Liquibase (Neiva, Huila)

Plataforma gastronómica integral de **Comidas Rápidas, Pizzas, Asados, Heladerías, Waffles y Postres en Neiva, Huila**, desarrollada **100% en JAVA** con arquitectura HTTP nativa (`com.sun.net.httpserver.HttpServer`), base de datos **MySQL / MariaDB en XAMPP** controlada con **Liquibase (`db.changelog-master.xml`)**, sistema relacional de **Toppings Dulces y Salados**, **Mapa de Rastreo GPS Satelital Multi-Vía Mejorado**, y diseño **Neumórfico Soft UI** 100% adaptable a **Móvil y PC en el navegador**.

---

## 🏗️ Arquitectura 100% Java + Liquibase + MySQL (XAMPP)

```text
ANTOJO-VIRTUAL-APLICACION/
├── pom.xml                                                 # Configuración Maven (Java + MySQL Connector/J + Liquibase)
├── liquibase.properties                                    # Propiedades de conexión Liquibase hacia MySQL en XAMPP
├── src/main/
│   ├── java/com/antojovirtual/
│   │   ├── AntojoVirtualServer.java                        # Servidor HTTP Principal (Puertos 8080 y 80)
│   │   ├── config/
│   │   │   ├── DatabaseManager.java                        # Motor Dual JDBC + XAMPP MySQL Bridge
│   │   │   ├── LiquibaseManager.java                       # Motor de Migraciones Liquibase (DATABASECHANGELOG)
│   │   │   └── JwtUtil.java                                # Motor JWT RFC 7519 (HMAC-SHA256) + Refresh Tokens
│   │   ├── handlers/
│   │   │   ├── RestaurantsHandler.java                     # API JSON + Directorio Visual de 40 sitios en Neiva
│   │   │   ├── MenuHandler.java                            # API JSON + Catálogo de 98+ Comidas Rápidas y Postres + Toppings
│   │   │   ├── OrdersHandler.java                          # Creación de pedidos, Toppings relacionales y Factura DIAN
│   │   │   ├── PaymentsHandler.java                        # Pasarela Multibanco (PSE 18 bancos, Nequi, Daviplata, Tarjetas)
│   │   │   ├── TrackingHandler.java                        # Telemetría GPS Satelital Multi-Punto por calles de Neiva
│   │   │   ├── AuthHandler.java                            # Login, Registro, Refresh Token y Logout JWT
│   │   │   ├── CouponsHandler.java                         # Validación de cupones promocionales en MySQL
│   │   │   ├── ReviewsHandler.java                         # API de reseñas verificadas de clientes
│   │   │   ├── AdminApiHandler.java                        # API de comandas de cocina y avance de estados
│   │   │   └── StaticFileHandler.java                      # Servidor de recursos estáticos (CSS, JS, PWA, SVG)
│   │   └── views/
│   │       ├── HomeViewHandler.java                        # Vista principal SPA Neumórfica (Móvil + PC)
│   │       ├── AdminViewHandler.java                       # Panel Administrativo y de Cocina (SSR Java)
│   │       ├── RiderViewHandler.java                       # Portal Móvil de Repartidores con GPS/Waze/WhatsApp
│   │       ├── ReviewsViewHandler.java                     # Muro Público de Reseñas de Clientes
│   │       └── InstallViewHandler.java                     # Panel de Control Liquibase (DATABASECHANGELOG) y MySQL
│   └── resources/
│       ├── liquibase.properties                            # Configuración Liquibase embebida
│       ├── db/changelog/
│       │   ├── db.changelog-master.xml                     # Master Changelog XML de Liquibase
│       │   └── changesets/
│       │       ├── 001-initial-schema.sql                  # Esquema base, Comunas de Neiva, Bancos PSE, JWT
│       │       ├── 002-toppings-and-modifiers.sql          # Catálogo relacional de Toppings, Salsas y Siropes
│       │       ├── 003-seed-fastfood-and-desserts.sql      # Semilla masiva de Comidas Rápidas y Postres/Dulces
│       │       └── 004-neiva-gps-routes-and-landmarks.sql  # Rutas viales GPS reales por las 10 comunas de Neiva
│       └── templates/
│           └── index.html                                  # Interfaz Neumórfica Responsiva (Móvil + PC)
└── assets/                                                 # Hojas de estilo Neumórficas (CSS) y cliente JS
```

---

## 🚀 Compilación, Liquibase y Ejecución con XAMPP

### 1. Compilar el proyecto Java
```powershell
$javaFiles = (Get-ChildItem -Recurse src/main/java -Filter *.java).FullName
& "C:\Program Files\Apache NetBeans\jdk\bin\javac.exe" -encoding UTF-8 -d target/classes $javaFiles
```

### 2. Iniciar el Servidor Java (Ejecuta Liquibase automáticamente sobre MySQL en XAMPP)
```powershell
& "C:\Program Files\Apache NetBeans\jdk\bin\java.exe" -cp "target/classes;C:\Users\diazf\.m2\repository\com\mysql\mysql-connector-j\8.4.0\mysql-connector-j-8.4.0.jar" com.antojovirtual.AntojoVirtualServer
```

---

## 🔗 Rutas y Enlaces Principales (Móvil y PC)

- **Aplicación Principal (Móvil & PC):** `http://localhost:8080/` (o `http://localhost/antojo-virtual/`)
- **Panel Liquibase & Estado MySQL XAMPP:** `http://localhost:8080/liquibase` (y `/api/liquibase`)
- **Directorio Visual de 40 Sitios en Neiva:** `http://localhost:8080/restaurantes`
- **Catálogo Visual de 98+ Comidas Rápidas y Postres:** `http://localhost:8080/menu`
- **Panel de Cocina y Despacho (Admin):** `http://localhost:8080/admin`
- **App Móvil del Repartidor (Rider):** `http://localhost:8080/rider`
- **Muro de Reseñas de Clientes:** `http://localhost:8080/reviews`
