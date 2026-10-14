-- =========================================================
-- Base de Datos: Antojo Virtual (Neiva, Huila)
-- Compatible con XAMPP / MariaDB / MySQL 5.7+ / 8.0+
-- =========================================================

CREATE DATABASE IF NOT EXISTS `antojo_virtual` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `antojo_virtual`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_tracking_events`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `item_options`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `riders`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Tabla: Categorías
-- ---------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('all', 'Todos los Antojos', 'restaurant', 1),
('burgers', 'Hamburguesas al Carbón', 'lunch_dining', 2),
('salchipapas', 'Salchipapas Monumentales', 'fastfood', 3),
('arepas', 'Arepas & Patacones', 'bakery_dining', 4),
('perros', 'Perros Calientes', 'kebab_dining', 5),
('desgranados', 'Desgranados & Mazorca', 'grain', 6),
('bebidas', 'Bebidas & Postres', 'local_drink', 7);

-- ---------------------------------------------------------
-- Tabla: Repartidores (Riders en Neiva)
-- ---------------------------------------------------------
CREATE TABLE `riders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `vehicle` VARCHAR(100) NOT NULL,
  `plate` VARCHAR(20) NOT NULL,
  `rating` DECIMAL(2,1) DEFAULT 4.9,
  `photo` VARCHAR(255) NOT NULL,
  `current_street` VARCHAR(150) DEFAULT 'Av. Circunvalar con Calle 10',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `riders` (`name`, `phone`, `vehicle`, `plate`, `rating`, `photo`, `current_street`) VALUES
('Carlos Mendoza', '+57 312 458 9210', 'Bajaj Pulsar 200 NS - Negra', 'HUI-84E', 4.9, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 'Av. Circunvalar con Calle 10'),
('Andrés Felipe Gómez', '+57 315 889 1234', 'Yamaha FZ 2.0 - Roja', 'OPX-19F', 4.8, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80', 'Cra 5 frente a Parque Santander'),
('Laura Sofía Trujillo', '+57 318 765 4321', 'Scooter NMax 155 - Azul', 'NVA-72D', 5.0, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&auto=format&fit=crop&q=80', 'Calle 8 con Cra 15, Las Granjas');

-- ---------------------------------------------------------
-- Tabla: Restaurantes aliados en Neiva
-- ---------------------------------------------------------
CREATE TABLE `restaurants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(80) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `address` VARCHAR(200) NOT NULL,
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4',
  `phone` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `rating` DECIMAL(2,1) DEFAULT 4.8,
  `reviews_count` INT DEFAULT 120,
  `delivery_time` VARCHAR(30) DEFAULT '25-35 min',
  `delivery_fee` INT DEFAULT 3500,
  `badge` VARCHAR(50) DEFAULT 'Verificado Huila',
  `badge_type` ENUM('verified', 'bestseller', 'speed') DEFAULT 'verified',
  `image` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `star_dish` VARCHAR(150) NOT NULL,
  `star_price` INT NOT NULL,
  `star_tag` VARCHAR(50) DEFAULT 'Especialidad',
  `category` VARCHAR(50) NOT NULL,
  `is_open` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `restaurants` (`slug`, `name`, `address`, `comuna`, `phone`, `whatsapp`, `rating`, `reviews_count`, `delivery_time`, `delivery_fee`, `badge`, `badge_type`, `image`, `description`, `star_dish`, `star_price`, `star_tag`, `category`, `is_open`) VALUES
('la-estacion-burger', 'La Estación Burger', 'Cra 12 #10-34, Centro', 'Comuna 4', '+57 314 289 9012', '573142899012', 4.9, 142, '25-35 min', 3500, 'Verificado Huila', 'verified', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Especialistas en cocción a la parrilla de carbón, salsas artesanales de la casa y pan brioche horneado a diario en Neiva.', 'Hamburguesa Monumental Huilense 200g', 24900, 'Listo en 20 min', 'burgers', 1),
('salchipaperia-la-8va', 'Salchipapería La 8va', 'Calle 8 #14-20, Las Granjas', 'Comuna 2', '+57 311 502 8844', '573115028844', 4.8, 98, '20-30 min', 3000, 'Más Vendido', 'bestseller', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Porciones gigantescas con papas crujientes criollas y americanas, bañadas en tártara especial y queso costeño rallado al tope.', 'Súper Salchipapa Costeña-Opita', 22000, 'Para compartir', 'salchipapas', 1),
('el-patacon-callejero', 'El Patacón Callejero', 'Av. Circunvalar #18-05', 'Comuna 3', '+57 320 914 3321', '573209143321', 4.9, 115, '25-35 min', 3500, 'Verificado Huila', 'verified', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Patacones crocantes pisados al instante con plátano verde del Huila, carnes desmechadas jugosas y salsas artesanales frescas.', 'Patacón Monumental con Todo', 19500, '100% Crocante', 'arepas', 1),
('perros-grill-opita', 'Perros & Grill Opita', 'Cra 5 #24-10, Las Granjas', 'Comuna 2', '+57 318 642 9901', '573186429901', 4.7, 76, '15-25 min', 2800, 'Súper Rápido', 'speed', 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'El auténtico perro caliente colombiano: salchicha suiza a la plancha, ripio crujiente, tocineta y huevos de codorniz con salsa de piña.', 'Perro Caliente Especial Opita', 16000, 'Combo disponible', 'perros', 1),
('mazorcadas-del-magdalena', 'Mazorcadas del Magdalena', 'Av. 26 #38-12, Altico', 'Comuna 5', '+57 317 800 1122', '573178001122', 4.9, 88, '25-35 min', 3200, 'Recomendado', 'verified', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Maíz tierno desgranado a la mantequilla derretida, lomo de res salteado, pollo campesino y una capa generosa de queso fundido.', 'Desgranado Mixto Supremo al Carbón', 21500, 'Súper Quesudo', 'desgranados', 1),
('arepas-don-huila', 'Arepas Don Huila', 'Cra 5 #21-15, Quirinal', 'Comuna 3', '+57 316 430 7799', '573164307799', 4.8, 104, '20-30 min', 3000, 'Tradición Neiva', 'bestseller', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Masa artesanal asada de maíz blanco rellena con generosa porción de carne deshebrada, pollo, chicharrón crocante y guacamole.', 'Arepa Rellena Trifásica Campesina', 17800, 'Maíz 100% Puro', 'arepas', 1);

-- ---------------------------------------------------------
-- Tabla: Platos y Menú
-- ---------------------------------------------------------
CREATE TABLE `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` INT NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `tags` VARCHAR(200) DEFAULT '',
  `category` VARCHAR(50) NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_popular` TINYINT(1) DEFAULT 0,
  `is_available` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `is_featured`, `is_popular`) VALUES
(1, 'Hamburguesa Monumental Huilense 200g', 24900, 'Carne de res seleccionada 200g a la parrilla de carbón, queso campesino asado de Algeciras, cebolla caramelizada en panela huilense, tocineta ahumada crujiente y salsa tártara artesanal.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Res 200g, Queso Algeciras, Pan Brioche', 'burgers', 1, 1),
(1, 'Hamburguesa Doble Smash Criolla', 28500, 'Doble disco de 110g de carne angus aplastada con costra crocante, triple queso cheddar fundido, pepinillos encurtidos y mayonesa de ajo asado.', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Doble Carne, Queso Cheddar, Smash', 'burgers', 1, 0),
(1, 'Papas Rústicas Romero & Paprika', 7500, 'Papas naturales en cascos cortadas a mano, sazonadas con romero fresco, sal marina y paprika ahumada.', 'https://images.unsplash.com/photo-1576107232684-1279f3908594?w=600&auto=format&fit=crop&q=80', 'Artesanales, Romero, Cascos', 'burgers', 0, 1),

(2, 'Súper Salchipapa Costeña-Opita', 22000, 'Base abundante de papas a la francesa y criollitas, salchicha manguera doradita, lomo picado, tocineta, queso costeño rallado en cascada y salsa rosada de la abuela.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Para compartir, Queso Costeño, Tocineta', 'salchipapas', 1, 1),
(2, 'Salchipapa Salvaje 4 Carnes', 32000, 'Papas rústicas con chorizo santarrosano, lomo de res, pollo a la plancha, butifarra, ripio de papa y maíz dulce desgranado con doble queso fundido.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', '4 Carnes, Familiar, Doble Queso', 'salchipapas', 1, 0),

(3, 'Patacón Monumental con Todo', 19500, 'Plátano verde gigante prensado y frito al instante bien tostado, con base de guacamole campesino, pollo desmechado, carne en tiras, hogao caliente y queso rallado.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', '100% Crocante, Plátano Verde, Hogao', 'arepas', 1, 1),

(4, 'Perro Caliente Especial Opita', 16000, 'Salchicha suiza premium sellada a la plancha, tocineta caramelizada, ripio crujiente casero, tres huevos de codorniz, salsa tártara y piña calada dulce.', 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'Salchicha Suiza, Huevos Codorniz, Piña Calada', 'perros', 1, 1),

(5, 'Desgranado Mixto Supremo al Carbón', 21500, 'Maíz tierno cocido y salteado a la mantequilla, tiernas tiras de lomo de res, pechuga de pollo marinada, fosforitos de papa criolla y manto fundido de mozzarella.', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Maíz Tierno, Pollo & Lomo, Queso Fundido', 'desgranados', 1, 1),

(6, 'Arepa Rellena Trifásica Campesina', 17800, 'Masa 100% maíz peto blanco huilense asada en parrilla, rellena al tope de chicharrón carnudo bien tostado, carne deshebrada, pollo mechado y guacamole.', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Maíz 100%, Chicharrón, Guacamole', 'arepas', 1, 1);

-- ---------------------------------------------------------
-- Tabla: Opciones de personalización (Toppings, Panes, Términos, Salsas)
-- ---------------------------------------------------------
CREATE TABLE `item_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('termino', 'pan', 'topping', 'salsa') NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` INT DEFAULT 0,
  `is_default` TINYINT(1) DEFAULT 0,
  `icon` VARCHAR(50) DEFAULT 'check'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `item_options` (`category`, `name`, `price`, `is_default`, `icon`) VALUES
-- Términos de carne
('termino', 'Término Medio (Centro rojo y jugoso)', 0, 0, 'radio_button_unchecked'),
('termino', 'Tres Cuartos - 3/4 (Punto ideal de la casa)', 0, 1, 'radio_button_checked'),
('termino', 'Bien Asada (Completamente cocida a la brasa)', 0, 0, 'radio_button_unchecked'),

-- Tipos de Pan
('pan', 'Pan Brioche Dorado Tradicional', 0, 1, 'bakery_dining'),
('pan', 'Pan Rústico con Ajonjolí Negro', 1500, 0, 'grain'),
('pan', 'Pan con Orégano y Queso Parmesano', 2000, 0, 'cookie'),
('pan', 'Envoltura de Lechuga Batavia (Sin gluten)', 0, 0, 'eco'),

-- Toppings adicionales
('topping', 'Tocineta Ahumada Crunch (Extra)', 3500, 0, 'bacon'),
('topping', 'Huevo Frito Campesino Yema Tierna', 2000, 0, 'egg'),
('topping', 'Queso Campesino Asado de Algeciras', 3000, 0, 'cheese'),
('topping', 'Carne de Res Adicional 150g al Carbón', 7500, 0, 'kebab_dining'),
('topping', 'Cebolla Caramelizada en Panela', 1500, 0, 'scatter_plot'),
('topping', 'Chicharrón Crocante Picado', 4000, 0, 'restaurant'),
('topping', 'Papas Fosforito Crujientes', 1200, 0, 'lunch_dining'),

-- Salsas colombianas artesanales
('salsa', 'Tártara Especial de la Abuela', 0, 1, 'check_box'),
('salsa', 'BBQ Artesanal con Guayaba Huilense', 0, 1, 'check_box'),
('salsa', 'Salsa de Ajo Suave & Perejil', 0, 0, 'check_box_outline_blank'),
('salsa', 'Piña Melada Colombiana', 0, 0, 'check_box_outline_blank'),
('salsa', 'Picante Casero Ají Opita (Medio)', 0, 0, 'check_box_outline_blank');

-- ---------------------------------------------------------
-- Tabla: Cupones de descuento
-- ---------------------------------------------------------
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_amount` INT NOT NULL,
  `min_purchase` INT DEFAULT 15000,
  `description` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `max_uses` INT DEFAULT 500,
  `used_count` INT DEFAULT 18
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `coupons` (`code`, `discount_amount`, `min_purchase`, `description`, `is_active`, `max_uses`, `used_count`) VALUES
('ANTOJONEIVA5K', 5000, 20000, 'Descuento de $5.000 COP para toda Neiva en pedidos superiores a $20.000', 1, 1000, 42),
('PARRILLA10', 10000, 45000, 'Descuento de $10.000 COP en parrilladas familiares superiores a $45.000', 1, 200, 15),
('OPITAFIRST', 4000, 15000, 'Cupón de bienvenida $4.000 COP para nuevos usuarios', 1, 500, 31);

-- ---------------------------------------------------------
-- Tabla: Pedidos (Órdenes)
-- ---------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `delivery_address` VARCHAR(200) NOT NULL,
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4 - Centro',
  `delivery_timing` ENUM('immediate', 'scheduled') DEFAULT 'immediate',
  `instructions` TEXT DEFAULT NULL,
  `payment_method` ENUM('nequi', 'daviplata', 'pse', 'card', 'cash_efectivo', 'cash_datafono') DEFAULT 'nequi',
  `cash_change_for` VARCHAR(50) DEFAULT NULL,
  `subtotal` INT NOT NULL,
  `discount` INT DEFAULT 0,
  `delivery_fee` INT DEFAULT 3500,
  `tip` INT DEFAULT 1000,
  `total` INT NOT NULL,
  `status` ENUM('recibido', 'preparando', 'en_camino', 'entregado', 'cancelado') DEFAULT 'recibido',
  `rider_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`rider_id`) REFERENCES `riders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: Ítems de la orden
-- ---------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `menu_item_id` INT DEFAULT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `restaurant_name` VARCHAR(150) DEFAULT 'La Estación Burger',
  `base_price` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `doneness` VARCHAR(100) DEFAULT NULL,
  `bread` VARCHAR(100) DEFAULT NULL,
  `toppings_json` TEXT DEFAULT NULL,
  `sauces_json` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `subtotal` INT NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: Eventos de rastreo en vivo de la orden
-- ---------------------------------------------------------
CREATE TABLE `order_tracking_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `status` ENUM('recibido', 'preparando', 'en_camino', 'entregado') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Datos de prueba para un pedido inicial realista
-- ---------------------------------------------------------
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_phone`, `delivery_address`, `comuna`, `delivery_timing`, `instructions`, `payment_method`, `subtotal`, `discount`, `delivery_fee`, `tip`, `total`, `status`, `rider_id`, `created_at`) VALUES
('#NV-8492', 'Mateo Rodríguez', '+57 314 289 9012', 'Cra. 5 #18-42, Centro, Neiva', 'Comuna 4 - Centro', 'immediate', 'Dejar en recepción con el portero don Hernando', 'nequi', 40900, 5000, 3500, 1000, 40400, 'en_camino', 1, NOW() - INTERVAL 18 MINUTE);

INSERT INTO `order_items` (`order_id`, `item_name`, `restaurant_name`, `base_price`, `quantity`, `doneness`, `bread`, `toppings_json`, `sauces_json`, `notes`, `subtotal`) VALUES
(1, 'Hamburguesa Monumental Huilense 200g', 'La Estación Burger', 24900, 1, 'Tres Cuartos - 3/4', 'Pan Brioche Dorado Tradicional', '[{"name":"Tocineta Ahumada Crunch","price":3500,"count":1},{"name":"Huevo Frito Campesino","price":2000},{"name":"Queso Campesino Asado","price":3000}]', '["Tártara Especial de la Abuela","BBQ Artesanal con Guayaba Huilense"]', 'Cebolla bien caramelizada por favor', 33400),
(1, 'Papas Rústicas Romero & Paprika', 'La Estación Burger', 7500, 1, NULL, NULL, '[]', '["Tártara Especial de la Abuela"]', '', 7500);

INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`, `created_at`) VALUES
(1, 'recibido', 'Orden Recibida y Confirmada', 'Tu pedido fue recibido con éxito por La Estación Burger.', NOW() - INTERVAL 18 MINUTE),
(1, 'preparando', 'Carbón al Rojo Vivo & Cocción', 'El maestro parrillero está asando la carne de res 200g al punto 3/4.', NOW() - INTERVAL 12 MINUTE),
(1, 'en_camino', 'Repartidor en Ruta por Neiva', 'Carlos Mendoza va en su Pulsar negra por la Av. Circunvalar hacia tu ubicación.', NOW() - INTERVAL 4 MINUTE);
