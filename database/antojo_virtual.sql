-- =========================================================
-- Base de Datos: Antojo Virtual (Neiva, Huila)
-- Compatible con XAMPP / MariaDB / MySQL 5.7+ / 8.0+
-- Arquitectura Completa: Catálogo, Pedidos, JWT, Pasarela
-- de Pagos Multibanca (PSE, Tarjetas, Billeteras), Facturación y Métricas
-- =========================================================

CREATE DATABASE IF NOT EXISTS `antojo_virtual` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `antojo_virtual`;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS `vw_sales_summary`;
DROP VIEW IF EXISTS `vw_payments_log`;
DROP VIEW IF EXISTS `vw_active_orders`;

DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_tracking_events`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `user_saved_cards`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `jwt_refresh_tokens`;
DROP TABLE IF EXISTS `jwt_blacklist`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `payment_methods`;
DROP TABLE IF EXISTS `banks`;
DROP TABLE IF EXISTS `comunas`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `item_options`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `riders`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- 1. Tabla: Comunas de Neiva y Tarifas de Domicilio
-- ---------------------------------------------------------
CREATE TABLE `comunas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `delivery_fee` INT NOT NULL DEFAULT 3500,
  `estimated_minutes` VARCHAR(30) DEFAULT '25-35 min',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `comunas` (`code`, `name`, `description`, `delivery_fee`, `estimated_minutes`) VALUES
('comuna_1',  'Comuna 1 - Cándido / Camilo Torres',      'Zona norte residencial y universitaria', 3800, '30-40 min'),
('comuna_2',  'Comuna 2 - Las Granjas / Los Mártires',    'Zona norte-centro de alta demanda',      3200, '20-30 min'),
('comuna_3',  'Comuna 3 - Quirinal / Santa Inés',         'Sector empresarial y comercial centro', 3000, '20-25 min'),
('comuna_4',  'Comuna 4 - Centro Histórico',              'Corazón comercial y Parque Santander',   2800, '15-25 min'),
('comuna_5',  'Comuna 5 - Altico / La Floresta',          'Zona oriente tradicional',               3200, '25-35 min'),
('comuna_6',  'Comuna 6 - Timanco / Puertas del Sol',     'Sur de Neiva y zonas residenciales',     4000, '35-45 min'),
('comuna_7',  'Comuna 7 - Ipanema / Las Brisas',          'Oriente alto residencial',               3800, '30-40 min'),
('comuna_8',  'Comuna 8 - Las Américas / Alfonso López',  'Oriente de la ciudad',                   3900, '30-45 min'),
('comuna_9',  'Comuna 9 - Luis Carlos Galán / Alberto G.', 'Norte alto de Neiva',                   4200, '35-50 min'),
('comuna_10', 'Comuna 10 - Las Palmas / El Caguán',       'Zona oriente externa y expansión',       4500, '40-55 min');

-- ---------------------------------------------------------
-- 2. Tabla: Entidades Bancarias y Financieras de Colombia (PSE / Transferencias)
-- ---------------------------------------------------------
CREATE TABLE `banks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(40) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `short_name` VARCHAR(50) NOT NULL,
  `type` ENUM('bank', 'neobank', 'wallet') DEFAULT 'bank',
  `icon_svg` VARCHAR(100) DEFAULT 'account_balance',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `banks` (`code`, `name`, `short_name`, `type`, `icon_svg`, `sort_order`) VALUES
('BANCOLOMBIA',     'Bancolombia S.A.',                        'Bancolombia',       'bank',    'account_balance',        1),
('NEQUI',           'Nequi (Bancolombia)',                     'Nequi',             'wallet',  'account_balance_wallet', 2),
('DAVIPLATA',       'Daviplata (Banco Davivienda)',            'Daviplata',         'wallet',  'phone_android',          3),
('DAVIVIENDA',      'Banco Davivienda S.A.',                   'Davivienda',        'bank',    'account_balance',        4),
('BANCO_BOGOTA',    'Banco de Bogotá (Grupo Aval)',            'Banco de Bogotá',   'bank',    'account_balance',        5),
('BBVA',            'BBVA Colombia S.A.',                      'BBVA',              'bank',    'account_balance',        6),
('BANCO_OCCIDENTE', 'Banco de Occidente (Grupo Aval)',         'Occidente',         'bank',    'account_balance',        7),
('COLPATRIA',       'Scotiabank Colpatria S.A.',               'Colpatria',         'bank',    'account_balance',        8),
('BANCO_POPULAR',   'Banco Popular S.A.',                      'Banco Popular',     'bank',    'account_balance',        9),
('AV_VILLAS',       'Banco AV Villas (Grupo Aval)',            'AV Villas',         'bank',    'account_balance',        10),
('CAJA_SOCIAL',     'Banco Caja Social BCSC',                  'Caja Social',       'bank',    'account_balance',        11),
('ITAU',            'Banco Itaú Colombia S.A.',                'Itaú',              'bank',    'account_balance',        12),
('NU_COLOMBIA',     'Nu Colombia Compañía de Financiamiento',  'Nu Bank',           'neobank', 'credit_card',            13),
('LULO_BANK',       'Lulo Bank S.A.',                          'Lulo Bank',         'neobank', 'savings',                14),
('DALE',            'Dale! (Grupo Aval)',                      'Dale!',             'wallet',  'wallet',                 15),
('RAPPIPAY',        'RappiPay Compañía de Financiamiento',     'RappiPay',          'neobank', 'local_mall',             16),
('FALABELLA',       'Banco Falabella S.A.',                    'Banco Falabella',   'bank',    'storefront',             17),
('PICHINCHA',       'Banco Pichincha S.A.',                    'Pichincha',         'bank',    'account_balance',        18);

-- ---------------------------------------------------------
-- 3. Tabla: Métodos de Pago Habilitados en la Pasarela
-- ---------------------------------------------------------
CREATE TABLE `payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `type` ENUM('online', 'wallet', 'cash', 'card') NOT NULL,
  `fee_percentage` DECIMAL(4,2) DEFAULT 0.00,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payment_methods` (`slug`, `name`, `description`, `icon`, `type`, `fee_percentage`, `sort_order`) VALUES
('pse',           'PSE - Pagos Seguros en Línea',   'Débito automático desde cualquier cuenta bancaria en Colombia', 'account_balance',        'online', 0.00, 1),
('card',          'Tarjeta de Crédito / Débito',     'Visa, Mastercard, American Express, Diners con hasta 36 cuotas', 'credit_card',            'card',   0.00, 2),
('nequi',         'Nequi Directo',                   'Pago instantáneo mediante notificación push o QR dinámico',    'account_balance_wallet', 'wallet', 0.00, 3),
('daviplata',     'Daviplata Directo',               'Aprobación rápida mediante push o clave dinámica OTP',         'phone_android',          'wallet', 0.00, 4),
('cash_efectivo', 'Efectivo contra Entrega',         'Paga en efectivo al recibir tu pedido en Neiva (con cambio)',  'attach_money',           'cash',   0.00, 5),
('cash_datafono', 'Datáfono contra Entrega',         'Paga con tarjeta física débito/crédito al repartidor',         'point_of_sale',          'cash',   0.00, 6);

-- ---------------------------------------------------------
-- 4. Tabla: Usuarios del Sistema (Clientes, Admins, Restaurantes)
-- ---------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(200) DEFAULT NULL,
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4 - Centro',
  `document_type` ENUM('CC', 'CE', 'NIT', 'PP', 'PPT') DEFAULT 'CC',
  `document_number` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('customer', 'admin', 'restaurant', 'rider') DEFAULT 'customer',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuarios de prueba
-- Contraseña de ambos: Admin2026!
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `comuna`, `document_type`, `document_number`, `role`) VALUES
('Administrador Neiva', 'admin@antojo.com', '$2y$12$eImiTXuWVxfM37uY4JANbeekhD.9oFBptunvd0YmNmzGyCRjTJkXi', '+57 310 000 0000', 'Cra 5 #10-00, Neiva',    'Comuna 4 - Centro', 'CC', '1075289900', 'admin'),
('Mateo Rodríguez',    'mateo@gmail.com',  '$2y$12$eImiTXuWVxfM37uY4JANbeekhD.9oFBptunvd0YmNmzGyCRjTJkXi', '+57 314 289 9012', 'Cra. 5 #18-42, Centro', 'Comuna 4 - Centro', 'CC', '1075304128', 'customer');

-- ---------------------------------------------------------
-- 5. Tabla: Direcciones Guardadas por Usuario
-- ---------------------------------------------------------
CREATE TABLE `user_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `label` VARCHAR(50) DEFAULT 'Casa',
  `street_address` VARCHAR(200) NOT NULL,
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4 - Centro',
  `neighborhood` VARCHAR(100) DEFAULT NULL,
  `delivery_notes` VARCHAR(255) DEFAULT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_addr` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user_addresses` (`user_id`, `label`, `street_address`, `comuna`, `neighborhood`, `delivery_notes`, `is_default`) VALUES
(2, 'Casa Principal', 'Cra. 5 #18-42, Apt 302', 'Comuna 4 - Centro', 'Centro', 'Dejar en recepción con portero don Hernando', 1),
(2, 'Oficina',        'Calle 21 #5-15, Edificio Empresarial', 'Comuna 3 - Quirinal / Santa Inés', 'Quirinal', 'Piso 4, oficina de sistemas', 0);

-- ---------------------------------------------------------
-- 6. Tabla: Tarjetas Guardadas de Clientes (Tokenizadas)
-- ---------------------------------------------------------
CREATE TABLE `user_saved_cards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `card_brand` ENUM('visa', 'mastercard', 'amex', 'diners') NOT NULL,
  `masked_number` VARCHAR(30) NOT NULL,          -- Ej: '•••• •••• •••• 4242'
  `cardholder_name` VARCHAR(100) NOT NULL,
  `exp_month` VARCHAR(2) NOT NULL,
  `exp_year` VARCHAR(4) NOT NULL,
  `token_id` VARCHAR(64) NOT NULL UNIQUE,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_saved_cards_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user_saved_cards` (`user_id`, `card_brand`, `masked_number`, `cardholder_name`, `exp_month`, `exp_year`, `token_id`, `is_default`) VALUES
(2, 'visa', '•••• •••• •••• 4242', 'MATEO RODRIGUEZ', '12', '2028', 'tok_test_visa_4242_neiva', 1);

-- ---------------------------------------------------------
-- 7. Tablas: Seguridad JWT (Sesiones & Blacklist)
-- ---------------------------------------------------------
CREATE TABLE `jwt_refresh_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `jti` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `revoked` TINYINT(1) DEFAULT 0,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_jwt_rt_user` (`user_id`),
  INDEX `idx_jwt_rt_hash` (`token_hash`),
  INDEX `idx_jwt_rt_exp`  (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `jwt_blacklist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jti` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_jwt_bl_jti` (`jti`),
  INDEX `idx_jwt_bl_exp` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 8. Tabla: Categorías del Menú
-- ---------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('all',         'Todos los Antojos',        'restaurant',    1),
('burgers',     'Hamburguesas al Carbón',   'lunch_dining',  2),
('salchipapas', 'Salchipapas Monumentales', 'fastfood',      3),
('arepas',      'Arepas & Patacones',       'bakery_dining', 4),
('perros',      'Perros Calientes',         'kebab_dining',  5),
('desgranados', 'Desgranados & Mazorca',    'grain',         6),
('bebidas',     'Bebidas & Postres',        'local_drink',   7);

-- ---------------------------------------------------------
-- 9. Tabla: Repartidores Locales (Riders en Neiva)
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
  `is_active` TINYINT(1) DEFAULT 1,
  `total_deliveries` INT DEFAULT 240
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `riders` (`name`, `phone`, `vehicle`, `plate`, `rating`, `photo`, `current_street`, `total_deliveries`) VALUES
('Carlos Mendoza',       '+57 312 458 9210', 'Bajaj Pulsar 200 NS - Negra', 'HUI-84E', 4.9, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 'Av. Circunvalar con Calle 10', 412),
('Andrés Felipe Gómez',  '+57 315 889 1234', 'Yamaha FZ 2.0 - Roja',         'OPX-19F', 4.8, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80', 'Cra 5 frente a Parque Santander', 320),
('Laura Sofía Trujillo', '+57 318 765 4321', 'Scooter NMax 155 - Azul',    'NVA-72D', 5.0, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&auto=format&fit=crop&q=80', 'Calle 8 con Cra 15, Las Granjas', 295);

-- ---------------------------------------------------------
-- 10. Tabla: Restaurantes Aliados en Neiva
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
  `is_open` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rest_category` (`category`),
  INDEX `idx_rest_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `restaurants` (`slug`, `name`, `address`, `comuna`, `phone`, `whatsapp`, `rating`, `reviews_count`, `delivery_time`, `delivery_fee`, `badge`, `badge_type`, `image`, `description`, `star_dish`, `star_price`, `star_tag`, `category`, `is_open`) VALUES
('la-estacion-burger',       'La Estación Burger',        'Cra 12 #10-34, Centro',        'Comuna 4', '+57 314 289 9012', '573142899012', 4.9, 142, '25-35 min', 3500, 'Verificado Huila', 'verified',   'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Especialistas en cocción a la parrilla de carbón, salsas artesanales de la casa y pan brioche horneado a diario en Neiva.', 'Hamburguesa Monumental Huilense 200g', 24900, 'Listo en 20 min', 'burgers',     1),
('salchipaperia-la-8va',     'Salchipapería La 8va',      'Calle 8 #14-20, Las Granjas',  'Comuna 2', '+57 311 502 8844', '573115028844', 4.8, 98,  '20-30 min', 3000, 'Más Vendido',     'bestseller', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Porciones gigantescas con papas crujientes criollas y americanas, bañadas en tártara especial y queso costeño rallado al tope.', 'Súper Salchipapa Costeña-Opita', 22000, 'Para compartir', 'salchipapas', 1),
('el-patacon-callejero',     'El Patacón Callejero',      'Av. Circunvalar #18-05',       'Comuna 3', '+57 320 914 3321', '573209143321', 4.9, 115, '25-35 min', 3500, 'Verificado Huila', 'verified',   'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Patacones crocantes pisados al instante con plátano verde del Huila, carnes desmechadas jugosas y salsas artesanales frescas.', 'Patacón Monumental con Todo', 19500, '100% Crocante',   'arepas',      1),
('perros-grill-opita',       'Perros & Grill Opita',      'Cra 5 #24-10, Las Granjas',    'Comuna 2', '+57 318 642 9901', '573186429901', 4.7, 76,  '15-25 min', 2800, 'Súper Rápido',    'speed',      'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'El auténtico perro caliente colombiano: salchicha suiza a la plancha, ripio crujiente, tocineta y huevos de codorniz con salsa de piña.', 'Perro Caliente Especial Opita', 16000, 'Combo disponible', 'perros',     1),
('mazorcadas-del-magdalena', 'Mazorcadas del Magdalena',  'Av. 26 #38-12, Altico',        'Comuna 5', '+57 317 800 1122', '573178001122', 4.9, 88,  '25-35 min', 3200, 'Recomendado',     'verified',   'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Maíz tierno desgranado a la mantequilla derretida, lomo de res salteado, pollo campesino y una capa generosa de queso fundido.', 'Desgranado Mixto Supremo al Carbón', 21500, 'Súper Quesudo', 'desgranados', 1),
('arepas-don-huila',         'Arepas Don Huila',          'Cra 5 #21-15, Quirinal',       'Comuna 3', '+57 316 430 7799', '573164307799', 4.8, 104, '20-30 min', 3000, 'Tradición Neiva', 'bestseller', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Masa artesanal asada de maíz blanco rellena con generosa porción de carne deshebrada, pollo, chicharrón crocante y guacamole.', 'Arepa Rellena Trifásica Campesina', 17800, 'Maíz 100% Puro',  'arepas',      1);

-- ---------------------------------------------------------
-- 11. Tabla: Platos y Menú de Restaurantes
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
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_menu_restaurant` (`restaurant_id`),
  INDEX `idx_menu_category`   (`category`),
  INDEX `idx_menu_featured`   (`is_featured`)
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
-- 12. Tabla: Opciones de Personalización de Platos
-- ---------------------------------------------------------
CREATE TABLE `item_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('termino', 'pan', 'topping', 'salsa') NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` INT DEFAULT 0,
  `is_default` TINYINT(1) DEFAULT 0,
  `icon` VARCHAR(50) DEFAULT 'check',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `item_options` (`category`, `name`, `price`, `is_default`, `icon`) VALUES
('termino', 'Término Medio (Centro rojo y jugoso)', 0, 0, 'radio_button_unchecked'),
('termino', 'Tres Cuartos - 3/4 (Punto ideal de la casa)', 0, 1, 'radio_button_checked'),
('termino', 'Bien Asada (Completamente cocida a la brasa)', 0, 0, 'radio_button_unchecked'),

('pan', 'Pan Brioche Dorado Tradicional', 0, 1, 'bakery_dining'),
('pan', 'Pan Rústico con Ajonjolí Negro', 1500, 0, 'grain'),
('pan', 'Pan con Orégano y Queso Parmesano', 2000, 0, 'cookie'),
('pan', 'Envoltura de Lechuga Batavia (Sin gluten)', 0, 0, 'eco'),

('topping', 'Tocineta Ahumada Crunch (Extra)', 3500, 0, 'bacon'),
('topping', 'Huevo Frito Campesino Yema Tierna', 2000, 0, 'egg'),
('topping', 'Queso Campesino Asado de Algeciras', 3000, 0, 'cheese'),
('topping', 'Carne de Res Adicional 150g al Carbón', 7500, 0, 'kebab_dining'),
('topping', 'Cebolla Caramelizada en Panela', 1500, 0, 'scatter_plot'),
('topping', 'Chicharrón Crocante Picado', 4000, 0, 'restaurant'),
('topping', 'Papas Fosforito Crujientes', 1200, 0, 'lunch_dining'),

('salsa', 'Tártara Especial de la Abuela', 0, 1, 'check_box'),
('salsa', 'BBQ Artesanal con Guayaba Huilense', 0, 1, 'check_box'),
('salsa', 'Salsa de Ajo Suave & Perejil', 0, 0, 'check_box_outline_blank'),
('salsa', 'Piña Melada Colombiana', 0, 0, 'check_box_outline_blank'),
('salsa', 'Picante Casero Ají Opita (Medio)', 0, 0, 'check_box_outline_blank');

-- ---------------------------------------------------------
-- 13. Tabla: Cupones de Descuento
-- ---------------------------------------------------------
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_amount` INT NOT NULL,
  `min_purchase` INT DEFAULT 15000,
  `description` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `max_uses` INT DEFAULT 500,
  `used_count` INT DEFAULT 18,
  `expires_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `coupons` (`code`, `discount_amount`, `min_purchase`, `description`, `is_active`, `max_uses`, `used_count`) VALUES
('ANTOJONEIVA5K', 5000, 20000, 'Descuento de $5.000 COP para toda Neiva en pedidos superiores a $20.000', 1, 1000, 42),
('PARRILLA10',    10000, 45000, 'Descuento de $10.000 COP en parrilladas familiares superiores a $45.000', 1, 200, 15),
('OPITAFIRST',    4000, 15000, 'Cupón de bienvenida $4.000 COP para nuevos usuarios en su primera orden', 1, 500, 31);

-- ---------------------------------------------------------
-- 14. Tabla: Pedidos Principales (Órdenes de Compra)
-- ---------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `order_code` VARCHAR(30) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `customer_email` VARCHAR(150) DEFAULT NULL,
  `delivery_address` VARCHAR(200) NOT NULL,
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4 - Centro',
  `delivery_timing` ENUM('immediate', 'scheduled') DEFAULT 'immediate',
  `instructions` TEXT DEFAULT NULL,
  `payment_method` VARCHAR(30) NOT NULL DEFAULT 'pse',
  `payment_status` ENUM('pending', 'paid', 'declined', 'refunded') DEFAULT 'pending',
  `cash_change_for` VARCHAR(50) DEFAULT NULL,
  `subtotal` INT NOT NULL,
  `discount` INT DEFAULT 0,
  `delivery_fee` INT DEFAULT 3500,
  `tip` INT DEFAULT 1000,
  `tax_impoconsumo` INT DEFAULT 0,               -- Impuesto al Consumo (8% en Colombia)
  `total` INT NOT NULL,
  `status` ENUM('recibido', 'preparando', 'en_camino', 'entregado', 'cancelado') DEFAULT 'recibido',
  `rider_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`rider_id`) REFERENCES `riders`(`id`) ON DELETE SET NULL,
  INDEX `idx_orders_code`    (`order_code`),
  INDEX `idx_orders_user`    (`user_id`),
  INDEX `idx_orders_status`  (`status`),
  INDEX `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 15. Tabla: Ítems de la Orden
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
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 16. Tabla: Eventos de Rastreo en Vivo (Tracking Timeline)
-- ---------------------------------------------------------
CREATE TABLE `order_tracking_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `status` ENUM('recibido', 'preparando', 'en_camino', 'entregado', 'cancelado') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_tracking_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 17. Tabla: Transacciones y Pasarela de Pagos (Multibanco & Tarjetas)
-- ---------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `payment_method` VARCHAR(30) NOT NULL,         -- 'pse', 'card', 'nequi', 'daviplata', 'cash_efectivo', 'cash_datafono'
  `transaction_reference` VARCHAR(64) NOT NULL UNIQUE, -- Ej: 'PAY-NV-20261021-9842'
  `gateway` VARCHAR(40) DEFAULT 'antojo_pay_gateway',
  `amount` INT NOT NULL,
  `currency` VARCHAR(5) DEFAULT 'COP',
  `status` ENUM('pending', 'approved', 'declined', 'failed', 'refunded') DEFAULT 'pending',
  `authorization_code` VARCHAR(40) DEFAULT NULL, -- Código de autorización bancaria
  `bank_id` INT DEFAULT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,         -- Para PSE / Transferencia
  `payer_doc_type` VARCHAR(10) DEFAULT 'CC',
  `payer_doc_number` VARCHAR(30) DEFAULT NULL,
  `payer_person_type` ENUM('natural', 'juridica') DEFAULT 'natural',
  `payer_email` VARCHAR(150) DEFAULT NULL,
  `payer_phone` VARCHAR(30) DEFAULT NULL,
  `card_brand` VARCHAR(30) DEFAULT NULL,         -- 'visa', 'mastercard', 'amex', 'diners'
  `card_last_four` VARCHAR(4) DEFAULT NULL,      -- '4242'
  `cardholder_name` VARCHAR(100) DEFAULT NULL,
  `installments` TINYINT DEFAULT 1,              -- Cuotas seleccionadas (1 a 36)
  `gateway_response` TEXT DEFAULT NULL,          -- JSON con la respuesta y metadata técnica
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bank_id`) REFERENCES `banks`(`id`) ON DELETE SET NULL,
  INDEX `idx_pay_ref`    (`transaction_reference`),
  INDEX `idx_pay_status` (`status`),
  INDEX `idx_pay_method` (`payment_method`),
  INDEX `idx_pay_created`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 18. Tabla: Facturación Electrónica DIAN y Comprobantes
-- ---------------------------------------------------------
CREATE TABLE `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL UNIQUE,
  `payment_id` INT DEFAULT NULL,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,  -- Ej: 'FE-ANTOJO-2026-0084'
  `cufe_code` VARCHAR(96) NOT NULL,              -- Código Único de Facturación Electrónica DIAN
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_document` VARCHAR(30) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) DEFAULT NULL,
  `customer_address` VARCHAR(200) NOT NULL,
  `subtotal` INT NOT NULL,
  `discount` INT DEFAULT 0,
  `tax_impoconsumo` INT DEFAULT 0,               -- 8%
  `delivery_fee` INT DEFAULT 3500,
  `tip` INT DEFAULT 1000,
  `total` INT NOT NULL,
  `payment_method_label` VARCHAR(100) NOT NULL,
  `transaction_reference` VARCHAR(64) DEFAULT NULL,
  `qr_string` TEXT DEFAULT NULL,
  `status` ENUM('issued', 'cancelled') DEFAULT 'issued',
  `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL,
  INDEX `idx_inv_number` (`invoice_number`),
  INDEX `idx_inv_issued` (`issued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 19. Tabla: Reseñas y Calificaciones de Clientes
-- ---------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `restaurant_id` INT NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `rating` DECIMAL(2,1) NOT NULL,                -- 1.0 a 5.0
  `comment` TEXT NOT NULL,
  `is_approved` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_review_restaurant` (`restaurant_id`),
  INDEX `idx_review_rating`     (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reviews` (`restaurant_id`, `customer_name`, `rating`, `comment`) VALUES
(1, 'Mateo Rodríguez', 5.0, 'La carne de 200g es sensacional, el queso campesino asado de Algeciras le da un toque único y llegó calientita.'),
(1, 'Carolina Perdomo', 4.9, 'Excelente servicio y presentación. El pan brioche estaba esponjoso y la salsa tártara de la casa es la mejor de Neiva.'),
(2, 'Juan David Losada', 4.8, 'La salchipapa costeña-opita es gigante, perfecta para compartir entre tres personas. Llegó en menos de 25 minutos.'),
(3, 'Angélica Charry', 5.0, 'El patacón estaba extra crocante y el hogao calientito. El domiciliario Carlos muy educado y rápido.');

-- ---------------------------------------------------------
-- 20. Datos de Demostración Iniciales: Pedido #NV-8492 con Pago y Factura
-- ---------------------------------------------------------
INSERT INTO `orders` (
  `id`, `user_id`, `order_code`, `customer_name`, `customer_phone`, `customer_email`,
  `delivery_address`, `comuna`, `delivery_timing`, `instructions`,
  `payment_method`, `payment_status`, `subtotal`, `discount`, `delivery_fee`, `tip`, `tax_impoconsumo`, `total`,
  `status`, `rider_id`, `created_at`
) VALUES (
  1, 2, '#NV-8492', 'Mateo Rodríguez', '+57 314 289 9012', 'mateo@gmail.com',
  'Cra. 5 #18-42, Centro, Neiva', 'Comuna 4 - Centro', 'immediate', 'Dejar en recepción con el portero don Hernando',
  'pse', 'paid', 40900, 5000, 3500, 1000, 2872, 40400,
  'en_camino', 1, NOW() - INTERVAL 18 MINUTE
);

INSERT INTO `order_items` (`order_id`, `menu_item_id`, `item_name`, `restaurant_name`, `base_price`, `quantity`, `doneness`, `bread`, `toppings_json`, `sauces_json`, `notes`, `subtotal`) VALUES
(1, 1, 'Hamburguesa Monumental Huilense 200g', 'La Estación Burger', 24900, 1, 'Tres Cuartos - 3/4', 'Pan Brioche Dorado Tradicional', '[{"name":"Tocineta Ahumada Crunch","price":3500,"count":1},{"name":"Huevo Frito Campesino","price":2000},{"name":"Queso Campesino Asado","price":3000}]', '["Tártara Especial de la Abuela","BBQ Artesanal con Guayaba Huilense"]', 'Cebolla bien caramelizada por favor', 33400),
(1, 3, 'Papas Rústicas Romero & Paprika',       'La Estación Burger', 7500,  1, NULL, NULL, '[]', '["Tártara Especial de la Abuela"]', '', 7500);

INSERT INTO `order_tracking_events` (`order_id`, `status`, `title`, `description`, `created_at`) VALUES
(1, 'recibido',   'Orden y Pago Confirmados',     'Transacción aprobada por Bancolombia (PSE). Tu pedido fue recibido por La Estación Burger.', NOW() - INTERVAL 18 MINUTE),
(1, 'preparando', 'Carbón al Rojo Vivo & Cocción', 'El maestro parrillero está asando la carne de res 200g al punto 3/4.',                     NOW() - INTERVAL 12 MINUTE),
(1, 'en_camino',  'Repartidor en Ruta por Neiva',  'Carlos Mendoza va en su Pulsar negra por la Av. Circunvalar hacia tu ubicación.',             NOW() - INTERVAL 4 MINUTE);

-- Pago inicial asociado a la orden 1
INSERT INTO `payments` (
  `id`, `order_id`, `payment_method`, `transaction_reference`, `gateway`, `amount`, `currency`, `status`,
  `authorization_code`, `bank_id`, `bank_name`, `payer_doc_type`, `payer_doc_number`, `payer_person_type`,
  `payer_email`, `payer_phone`, `gateway_response`, `paid_at`, `created_at`
) VALUES (
  1, 1, 'pse', 'PAY-NV-20261021-8492', 'antojo_pay_gateway', 40400, 'COP', 'approved',
  'AUT-849201', 1, 'Bancolombia S.A.', 'CC', '1075304128', 'natural',
  'mateo@gmail.com', '+57 314 289 9012',
  '{"status":"APPROVED","provider":"PSE","bank":"Bancolombia","trace_number":"TRX-99412","message":"Transacción aprobada exitosamente por la entidad financiera"}',
  NOW() - INTERVAL 18 MINUTE, NOW() - INTERVAL 18 MINUTE
);

-- Factura electrónica asociada a la orden 1
INSERT INTO `invoices` (
  `id`, `order_id`, `payment_id`, `invoice_number`, `cufe_code`,
  `customer_name`, `customer_document`, `customer_email`, `customer_phone`, `customer_address`,
  `subtotal`, `discount`, `tax_impoconsumo`, `delivery_fee`, `tip`, `total`,
  `payment_method_label`, `transaction_reference`, `qr_string`, `status`, `issued_at`
) VALUES (
  1, 1, 1, 'FE-ANTOJO-2026-0001', '8f89c4501b87a9d04ef124803923a1e948c27940182390abcef182903810293849102839401829384910293849102938',
  'Mateo Rodríguez', 'CC 1075304128', 'mateo@gmail.com', '+57 314 289 9012', 'Cra. 5 #18-42, Centro, Neiva',
  40900, 5000, 2872, 3500, 1000, 40400,
  'PSE (Bancolombia S.A.)', 'PAY-NV-20261021-8492', 'NumFac=FE-ANTOJO-2026-0001&FecFac=2026-10-21&NitFac=901458921-4&ValFac=40400&CUFE=8f89c4501b87',
  'issued', NOW() - INTERVAL 18 MINUTE
);

-- ---------------------------------------------------------
-- 21. Vistas Analíticas y Operativas (Dashboards y Reportes)
-- ---------------------------------------------------------

-- Vista 1: Pedidos Activos en Tiempo Real con Detalles Completos
CREATE OR REPLACE VIEW `vw_active_orders` AS
SELECT 
  o.id,
  o.order_code,
  o.customer_name,
  o.customer_phone,
  o.delivery_address,
  o.comuna,
  o.subtotal,
  o.discount,
  o.delivery_fee,
  o.tip,
  o.total,
  o.status as order_status,
  o.payment_method,
  o.payment_status,
  p.transaction_reference,
  p.authorization_code,
  p.bank_name,
  p.card_brand,
  p.card_last_four,
  r.name as rider_name,
  r.phone as rider_phone,
  r.plate as rider_plate,
  r.vehicle as rider_vehicle,
  COUNT(i.id) as total_items,
  o.created_at,
  o.updated_at
FROM `orders` o
LEFT JOIN `payments` p ON o.id = p.order_id
LEFT JOIN `riders` r ON o.rider_id = r.id
LEFT JOIN `order_items` i ON o.id = i.order_id
GROUP BY o.id
ORDER BY o.created_at DESC;

-- Vista 2: Log Completo de Transacciones de Pago y Facturación
CREATE OR REPLACE VIEW `vw_payments_log` AS
SELECT 
  p.id as payment_id,
  p.transaction_reference,
  p.order_id,
  o.order_code,
  o.customer_name,
  o.customer_phone,
  p.payment_method,
  p.bank_name,
  p.card_brand,
  p.card_last_four,
  p.installments,
  p.amount,
  p.currency,
  p.status as payment_status,
  p.authorization_code,
  inv.invoice_number,
  inv.cufe_code,
  p.paid_at,
  p.created_at
FROM `payments` p
INNER JOIN `orders` o ON p.order_id = o.id
LEFT JOIN `invoices` inv ON o.id = inv.order_id
ORDER BY p.created_at DESC;

-- Vista 3: Resumen de Ventas por Método de Pago y Fecha
CREATE OR REPLACE VIEW `vw_sales_summary` AS
SELECT 
  DATE(p.created_at) as sale_date,
  p.payment_method,
  COUNT(p.id) as total_transactions,
  SUM(CASE WHEN p.status = 'approved' THEN p.amount ELSE 0 END) as approved_volume,
  SUM(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
  SUM(CASE WHEN p.status = 'declined' THEN 1 ELSE 0 END) as declined_count
FROM `payments` p
GROUP BY DATE(p.created_at), p.payment_method
ORDER BY sale_date DESC;
