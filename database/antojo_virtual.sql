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
('helados',     'Helados & Gelato',         'icecream',      2),
('postres',     'Postres & Tortas',         'cake',          3),
('waffles',     'Waffles & Crepes',         'cookie',        4),
('malteadas',   'Monster Shakes',           'local_cafe',    5),
('burgers',     'Hamburguesas al Carbón',   'lunch_dining',  6),
('salchipapas', 'Salchipapas Monumentales', 'fastfood',      7),
('arepas',      'Arepas & Patacones',       'bakery_dining', 8),
('perros',      'Perros Calientes',         'kebab_dining',  9),
('desgranados', 'Desgranados & Mazorca',    'grain',         10),
('bebidas',     'Bebidas & Postres',        'local_drink',   11);

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
  `site_type` ENUM('comidas_rapidas', 'dulces', 'mixto') DEFAULT 'comidas_rapidas',
  `is_open` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rest_category` (`category`),
  INDEX `idx_rest_site_type` (`site_type`),
  INDEX `idx_rest_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `restaurants` (`slug`, `name`, `address`, `comuna`, `phone`, `whatsapp`, `rating`, `reviews_count`, `delivery_time`, `delivery_fee`, `badge`, `badge_type`, `image`, `description`, `star_dish`, `star_price`, `star_tag`, `category`, `site_type`, `is_open`) VALUES
-- 14 Comidas Rápidas & Parrillas
('la-estacion-burger',         'La Estación Burger',                   'Cra 12 #10-34, Centro',             'Comuna 4', '+57 314 289 9012', '573142899012', 4.9, 142, '25-35 min', 3500, 'Verificado Huila',  'verified',   'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Especialistas en cocción a la parrilla de carbón, salsas artesanales de la casa y pan brioche horneado a diario en Neiva.', 'Hamburguesa Monumental Huilense 200g', 24900, 'Listo en 20 min', 'burgers',     'comidas_rapidas', 1),
('salchipaperia-la-8va',       'Salchipapería La 8va',                 'Calle 8 #14-20, Las Granjas',       'Comuna 2', '+57 311 502 8844', '573115028844', 4.8, 98,  '20-30 min', 3000, 'Más Vendido',      'bestseller', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Porciones gigantescas con papas crujientes criollas y americanas, bañadas en tártara especial y queso costeño rallado al tope.', 'Súper Salchipapa Costeña-Opita', 22000, 'Para compartir', 'salchipapas', 'comidas_rapidas', 1),
('el-patacon-callejero',       'El Patacón Callejero',                 'Av. Circunvalar #18-05',            'Comuna 3', '+57 320 914 3321', '573209143321', 4.9, 115, '25-35 min', 3500, 'Verificado Huila',  'verified',   'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Patacones crocantes pisados al instante con plátano verde del Huila, carnes desmechadas jugosas y salsas artesanales frescas.', 'Patacón Monumental con Todo', 19500, '100% Crocante',   'arepas',      'comidas_rapidas', 1),
('perros-grill-opita',         'Perros & Grill Opita',                 'Cra 5 #24-10, Las Granjas',         'Comuna 2', '+57 318 642 9901', '573186429901', 4.7, 76,  '15-25 min', 2800, 'Súper Rápido',     'speed',      'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'El auténtico perro caliente colombiano: salchicha suiza a la plancha, ripio crujiente, tocineta y huevos de codorniz con salsa de piña.', 'Perro Caliente Especial Opita', 16000, 'Combo disponible', 'perros',     'comidas_rapidas', 1),
('mazorcadas-del-magdalena',   'Mazorcadas del Magdalena',             'Av. 26 #38-12, Altico',             'Comuna 5', '+57 317 800 1122', '573178001122', 4.9, 88,  '25-35 min', 3200, 'Recomendado',      'verified',   'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Maíz tierno desgranado a la mantequilla derretida, lomo de res salteado, pollo campesino y una capa generosa de queso fundido.', 'Desgranado Mixto Supremo al Carbón', 21500, 'Súper Quesudo', 'desgranados', 'comidas_rapidas', 1),
('arepas-don-huila',           'Arepas Don Huila',                     'Cra 5 #21-15, Quirinal',            'Comuna 3', '+57 316 430 7799', '573164307799', 4.8, 104, '20-30 min', 3000, 'Tradición Neiva',  'bestseller', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Masa artesanal asada de maíz blanco rellena con generosa porción de carne deshebrada, pollo, chicharrón crocante y guacamole.', 'Arepa Rellena Trifásica Campesina', 17800, 'Maíz 100% Puro',  'arepas',      'comidas_rapidas', 1),
('asados-el-mohan-neiva',       'Asados & Parrilla El Mohán',           'Av. Pastrana Borrero #1-45, Cándido','Comuna 1', '+57 310 445 8899', '573104458899', 4.9, 165, '30-40 min', 3500, 'Parrilla al Carbón','bestseller', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Especialistas en cortes finos al carbón de leña, costillas de cerdo en salsa BBQ de panela huilense y arepas de choclo campesinas.', 'Costillas BBQ Ahumadas al Mohán (500g)', 34900, 'Especialidad Brasa', 'burgers', 'comidas_rapidas', 1),
('la-fritangueria-opita',       'La Fritanguería Opita',                'Calle 7 #18-20, Centro',            'Comuna 4', '+57 312 334 1122', '573123341122', 4.8, 230, '20-30 min', 3000, 'Auténtico Huila',   'verified',   'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=600&auto=format&fit=crop&q=80', 'La auténtica picada huilense: chicharrón carnudo crocante, costillitas doradas, morcilla casera, papa criolla frita y plátano maduro con ají.', 'Picada Fritanga Monumental para 2', 29900, 'Tradición Neiva', 'salchipapas', 'comidas_rapidas', 1),
('burger-house-neiva',         'Burger House Neiva',                   'Cra 15 #18-30, Quirinal',           'Comuna 3', '+57 316 778 9900', '573167789900', 4.9, 198, '25-35 min', 3200, 'Burger Master',     'bestseller', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&auto=format&fit=crop&q=80', 'Hamburguesas gourmet de autor, blend de carne de res madurada, mermelada de tocineta ahumada, queso brie fundido y salsas exclusivas.', 'Hamburguesa Trufada Burger Master 220g', 28900, 'Premio de Oro', 'burgers', 'comidas_rapidas', 1),
('el-chuzo-desgranado',         'El Chuzo Desgranado Costeño & Huilense','Cra 5 #30-15, Las Granjas',        'Comuna 2', '+57 314 556 7788', '573145567788', 4.8, 140, '20-30 min', 3000, 'Súper Quesudo',     'speed',      'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Desgranados gigantes con base de maíz tierno dulce, pollo, lomo de res a la plancha, butifarra, tocineta y lluvia de queso costeño.', 'Chuzo Desgranado Trifásico Salvaje', 23500, 'Más Pedido', 'desgranados', 'comidas_rapidas', 1),
('perros-el-paisita',          'Perros Calientes El Paisita',          'Calle 19 #5-40, Altico',            'Comuna 5', '+57 318 998 2211', '573189982211', 4.7, 112, '15-25 min', 2800, 'Gigante 35cm',      'speed',      'https://images.unsplash.com/photo-1627059312527-2c976939e6a9?w=600&auto=format&fit=crop&q=80', 'Perros calientes extra largos con salchicha zenú a la parrilla, queso fundido, tocineta crujiente, ripio casero y 4 huevos de codorniz.', 'Perro Caliente Mega Paisita 35cm', 18500, 'Mega Combo', 'perros', 'comidas_rapidas', 1),
('arepas-cuadradas-neiva',     'Arepas Cuadradas & Parrilla',          'Calle 8 #22-10, Las Américas',      'Comuna 8', '+57 317 444 3322', '573174443322', 4.8, 135, '20-30 min', 3200, '100% Maíz Choclo', 'verified',   'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Arepas doraditas de maíz tierno de choclo rellenas con abundante queso campesino asado, carne desmechada en hogao y pollo a la brasa.', 'Arepa de Choclo con Queso Campesino & Carne', 16500, 'Sabor Casero', 'arepas', 'comidas_rapidas', 1),
('wings-capital-neiva',        'Alitas & Wings Capital Neiva',         'Cra 6W #28-40, Cándido',            'Comuna 1', '+57 315 221 4455', '573152214455', 4.8, 178, '25-35 min', 3500, 'Alitas Extra Crunch','bestseller', 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=600&auto=format&fit=crop&q=80', 'Alitas apanadas extra crujientes bañadas en salsas artesanales: maracuyá picante, miel mostaza, BBQ ahumada, búfalo y queso azul.', 'Combo 12 Alitas Crujientes con Papas', 27900, 'Favorito Grupal', 'burgers', 'comidas_rapidas', 1),
('salchipapas-monstruo-sur',   'Salchipapas El Monstruo del Sur',      'Cra 19 #2-50, Timanco',             'Comuna 6', '+57 313 889 0011', '573138890011', 4.9, 250, '25-40 min', 3800, 'Porción Familiar',   'bestseller', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Las salchipapas más gigantes del sur de Neiva: capa triple de queso mozzarella derretido, salchicha ranchera, pollo, lomo fino y tocineta.', 'Salchipapa Volcánica Familiar (4 Personas)', 42000, 'Reto Familiar', 'salchipapas', 'comidas_rapidas', 1),

-- 14 Dulces, Helados, Postres & Waffles
('dolce-gelato-neiva',         'Dolce Gelato • Heladería Artesanal',   'Cra 6 #14-22, La Toma',             'Comuna 4', '+57 315 789 4433', '573157894433', 4.9, 185, '15-25 min', 3000, 'Gelato Italiano',   'bestseller', 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=600&auto=format&fit=crop&q=80', 'Auténtico gelato italiano artesanal elaborado a diario con frutas frescas del Huila, chocolate belga al 70% y conos horneados al momento.', 'Copa Suprema Dolce Gelato (3 Bolas)', 18500, 'Más Pedido', 'helados', 'dulces', 1),
('tentacion-dulce-reposteria', 'Tentación Dulce • Repostería & Café',  'Cl 18 #7-45, Barrio Ipanema',       'Comuna 7', '+57 318 642 1199', '573186421199', 4.9, 210, '20-30 min', 3500, 'Repostería Fina',  'verified',   'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600&auto=format&fit=crop&q=80', 'Cheesecakes estilo New York, tortas húmedas de chocolate belga, brownies melcochudos y postres con frutos de la región huilense.', 'Cheesecake Artesanal Frutos Rojos', 16900, 'Chef Pastelero', 'postres', 'dulces', 1),
('waffle-crepe-station',       'Waffle & Crepe Station Neiva',         'Av. Buganviles Cra 39 #19-15',      'Comuna 5', '+57 312 884 9010', '573128849010', 4.8, 142, '20-35 min', 3500, 'Waffles Calientes', 'speed',      'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80', 'Waffles burbuja crocantes, crepes dulces parisinos bañados en Nutella caliente, frutas frescas recién cortadas y bolas de helado artesanal.', 'Bubble Waffle Nutella & Helado', 21900, 'Favorito Jóvenes', 'waffles', 'dulces', 1),
('monster-shakes-neiva',       'Monster Shakes & Bebidas Dulces',      'Calle 8 #12-40, Altico',            'Comuna 3', '+57 311 502 8377', '573115028377', 4.8, 98,  '15-25 min', 3000, 'Malteadas Monstruo', 'verified',   'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Malteadas monstruo super cargadas con borde escarchado en Nutella y maní, galleta Oreo gigante, barquillos, crema chantilly y chocolates.', 'Monster Shake Choco-Oreo Suprema', 17500, 'Sensación Instagram', 'malteadas', 'dulces', 1),
('cholados-el-bambuco',        'Heladería & Cholados El Bambuco',      'Malecón del Río Magdalena',         'Comuna 4', '+57 312 778 1234', '573127781234', 4.9, 320, '15-25 min', 2800, 'Ícono Malecón',     'bestseller', 'https://images.unsplash.com/photo-1505394033641-40c6ad1178d7?w=600&auto=format&fit=crop&q=80', 'El auténtico cholado huilense frente al Río Magdalena: hielo finamente granizado, pulpa pura de frutas huilenses, leche condensada y helado.', 'Gran Cholado Huilense con Helado', 14500, 'Tradición Opita', 'helados', 'dulces', 1),
('churreria-tradicion-neiva',  'Churrería Tradición Española & Café',   'Cra 5 #11-22, Parque Santander',    'Comuna 4', '+57 315 667 8899', '573156678899', 4.8, 145, '15-25 min', 3000, 'Churros Calientes', 'speed',      'https://images.unsplash.com/photo-1624300629298-e9de39c13be5?w=600&auto=format&fit=crop&q=80', 'Churros crujientes fritos al instante con azúcar y canela, rellenos de Nutella caliente, arequipe de café o chocolate espeso español.', 'Cesta 8 Churros Rellenos Nutella', 15900, 'Recién Salidos', 'postres', 'dulces', 1),
('donut-factory-neiva',        'Donut Factory Neiva',                  'Av. Buganviles Calle 20 #38-10',    'Comuna 5', '+57 318 445 6677', '573184456677', 4.9, 180, '20-30 min', 3200, 'Donas Gourmet',     'bestseller', 'https://images.unsplash.com/photo-1527515862127-a4fc05baf7a5?w=600&auto=format&fit=crop&q=80', 'Donas artesanales esponjosas glaseadas a mano, rellenas de crema pastelera de vainilla bourbon, Nutella, Lotus Biscoff y pistacho.', 'Caja 4 Donas Gourmet Rellenas', 21000, 'Caja Regalo', 'postres', 'dulces', 1),
('crepes-valle-laboyos',       'Crepes & Waffles del Valle de Laboyos','Calle 14 #6-30, Centro',             'Comuna 4', '+57 316 223 9988', '573162239988', 4.8, 160, '20-35 min', 3000, 'Crepes Franceses',  'verified',   'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 'Crepes dulces elaborados a la plancha francesa con masa ligera, banano caramelizado, helado de canela, salsa de caramelo salado y almendras.', 'Crepe Suizo de Banano Flameado', 18900, 'Especialidad Chef', 'waffles', 'dulces', 1),
('gelateria-bella-italia',     'Gelateria Bella Italia Neiva',         'Cra 7 #21-15, Santa Inés',          'Comuna 3', '+57 314 889 3344', '573148893344', 4.9, 215, '15-25 min', 3000, 'Gelato 100% Puro',  'bestseller', 'https://images.unsplash.com/photo-1560008581-09826d1de69e?w=600&auto=format&fit=crop&q=80', 'Tradición heladera italiana con leche entera fresca, sabores de stracciatella, amarena silvestre, pistacho de Bronte y chocolate fondente.', 'Copa Tulipán de Gelato Italiano', 17900, 'Pura Calidad', 'helados', 'dulces', 1),
('panaderia-la-reina-huila',   'Cafetería & Panadería La Reina Huila', 'Cra 2 #9-45, Centro',               'Comuna 4', '+57 311 223 3445', '573112233445', 4.8, 190, '20-30 min', 2800, 'Tradición 40 Años', 'verified',   'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', 'La pastelería más tradicional del centro de Neiva: milhojas crujientes de arequipe, torta de guanábana, brazo de reina y postres caseros.', 'Milhoja Monumental de Arequipe', 12500, 'Más Vendido', 'postres', 'dulces', 1),
('granizados-tropicales-neiva','Nieve Raspada & Granizados Neiva',      'Cra 15 #6-20, Altico',              'Comuna 5', '+57 317 334 5566', '573173345566', 4.8, 105, '15-25 min', 3000, '100% Refrescante',  'speed',      'https://images.unsplash.com/photo-1517256064527-09c73fc73e38?w=600&auto=format&fit=crop&q=80', 'Granizados ultra refrescantes para el clima cálido de Neiva: café especial del Huila, frappé de maracuyá y lulo con toques agridulces.', 'Granizado de Café Huilense Especial', 13500, 'Especial Calor', 'helados', 'dulces', 1),
('brownie-cookie-lab',         'Brownie & Cookie Lab Neiva',           'Calle 16 #8-50, Ipanema',           'Comuna 7', '+57 318 776 5432', '573187765432', 4.9, 175, '20-30 min', 3500, 'Cookies Rellenas',  'bestseller', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=600&auto=format&fit=crop&q=80', 'Galletas artesanales gigantes estilo New York rellenas de Nutella o Red Velvet, y sartencitas de brownie caliente melcochudo con helado.', 'Skillet de Brownie con Helado', 17500, 'Puro Chocolate', 'postres', 'dulces', 1),
('shakes-frappes-huila',       'Shakes & Frappes del Huila',           'Cra 8 #25-10, Las Granjas',         'Comuna 2', '+57 313 445 6789', '573134456789', 4.8, 120, '15-25 min', 3000, 'Frappés Cremosos',  'speed',      'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Bebidas frías batidas al momento con base de helado artesanal, espresso de café origen Huila, crema batida, salsa de caramelo y chocolate.', 'Frappuccino Moca-Arequipe', 16500, 'Favorito Café', 'malteadas', 'dulces', 1),
('fresas-con-crema-neiva',     'Fresas con Crema La Cabaña',           'Av. 26 frente a Jardín Botánico',   'Comuna 5', '+57 310 998 8776', '573109988776', 4.9, 280, '20-30 min', 3200, 'Fresas Gigantes',   'bestseller', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&auto=format&fit=crop&q=80', 'Vasos monumentales de fresas seleccionadas bien rojas y jugosas, bañadas en crema de leche espesa de la casa, leche condensada y queso.', 'Vaso Monumental de Fresas con Crema', 15500, 'Súper Ventas', 'postres', 'dulces', 1);

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
  `dish_type` ENUM('salado', 'dulce') DEFAULT 'salado',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_popular` TINYINT(1) DEFAULT 0,
  `is_available` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  INDEX `idx_menu_restaurant` (`restaurant_id`),
  INDEX `idx_menu_category`   (`category`),
  INDEX `idx_menu_dish_type`  (`dish_type`),
  INDEX `idx_menu_featured`   (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`) VALUES
-- 1. La Estación Burger
(1, 'Hamburguesa Monumental Huilense 200g', 24900, 'Carne de res seleccionada 200g a la parrilla de carbón, queso campesino asado de Algeciras, cebolla caramelizada en panela huilense, tocineta ahumada crujiente y salsa tártara artesanal.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Res 200g, Queso Algeciras, Pan Brioche', 'burgers', 'salado', 1, 1),
(1, 'Hamburguesa Doble Smash Criolla', 28500, 'Doble disco de 110g de carne angus aplastada con costra crocante, triple queso cheddar fundido, pepinillos encurtidos y mayonesa de ajo asado.', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Doble Carne, Queso Cheddar, Smash', 'burgers', 'salado', 1, 0),
(1, 'Papas Rústicas Romero & Paprika', 7500, 'Papas naturales en cascos cortadas a mano, sazonadas con romero fresco, sal marina y paprika ahumada.', 'https://images.unsplash.com/photo-1576107232684-1279f3908594?w=600&auto=format&fit=crop&q=80', 'Artesanales, Romero, Cascos', 'burgers', 'salado', 0, 1),

-- 2. Salchipapería La 8va
(2, 'Súper Salchipapa Costeña-Opita', 22000, 'Base abundante de papas a la francesa y criollitas, salchicha manguera doradita, lomo picado, tocineta, queso costeño rallado en cascada y salsa rosada de la abuela.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Para compartir, Queso Costeño, Tocineta', 'salchipapas', 'salado', 1, 1),
(2, 'Salchipapa Salvaje 4 Carnes', 32000, 'Papas rústicas con chorizo santarrosano, lomo de res, pollo a la plancha, butifarra, ripio de papa y maíz dulce desgranado con doble queso fundido.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', '4 Carnes, Familiar, Doble Queso', 'salchipapas', 'salado', 1, 0),

-- 3. El Patacón Callejero
(3, 'Patacón Monumental con Todo', 19500, 'Plátano verde gigante prensado y frito al instante bien tostado, con base de guacamole campesino, pollo desmechado, carne en tiras, hogao caliente y queso rallado.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', '100% Crocante, Plátano Verde, Hogao', 'arepas', 'salado', 1, 1),

-- 4. Perros & Grill Opita
(4, 'Perro Caliente Especial Opita', 16000, 'Salchicha suiza premium sellada a la plancha, tocineta caramelizada, ripio crujiente casero, tres huevos de codorniz, salsa tártara y piña calada dulce.', 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'Salchicha Suiza, Huevos Codorniz, Piña Calada', 'perros', 'salado', 1, 1),

-- 5. Mazorcadas del Magdalena
(5, 'Desgranado Mixto Supremo al Carbón', 21500, 'Maíz tierno cocido y salteado a la mantequilla, tiernas tiras de lomo de res, pechuga de pollo marinada, fosforitos de papa criolla y manto fundido de mozzarella.', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Maíz Tierno, Pollo & Lomo, Queso Fundido', 'desgranados', 'salado', 1, 1),

-- 6. Arepas Don Huila
(6, 'Arepa Rellena Trifásica Campesina', 17800, 'Masa 100% maíz peto blanco huilense asada en parrilla, rellena al tope de chicharrón carnudo bien tostado, carne deshebrada, pollo mechado y guacamole.', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Maíz 100%, Chicharrón, Guacamole', 'arepas', 'salado', 1, 1),

-- 7. Dolce Gelato • Heladería Artesanal
(7, 'Copa Suprema Dolce Gelato (3 Bolas)', 18500, 'Tres generosas bolas de helado artesanal a tu elección, con base de canasta crocante o vaso, bañado en salsa tibia y toppings abundantes.', 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=600&auto=format&fit=crop&q=80', 'Gelato Artesanal, 3 Bolas, Frutas del Huila, Gourmet', 'helados', 'dulce', 1, 1),
(7, 'Cono Waffle Doble Sabor Premium', 12000, 'Dos bolas de gelato italiano en cono artesanal horneado con esencia de vainilla y borde cubierto de chocolate o arequipe.', 'https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=600&auto=format&fit=crop&q=80', 'Cono Waffle Crocante, 2 Bolas, Doble Sabor', 'helados', 'dulce', 0, 1),
(7, 'Banana Split Especial San Agustín', 20500, 'Tradicional y monumental banana split con 3 sabores de helado, banano fresco, crema chantilly, fresas y cereza marrasquino.', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=600&auto=format&fit=crop&q=80', 'Banana Split, 3 Sabores, Chantilly, Cerezas', 'helados', 'dulce', 1, 0),

-- 8. Tentación Dulce • Repostería & Café
(8, 'Cheesecake Artesanal de Frutos Rojos Huilenses', 16900, 'Cremoso cheesecake horneado sobre base crocante de galleta mantequilla, cubierto con coulis artesanal de moras y fresas del Huila.', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600&auto=format&fit=crop&q=80', 'Cheesecake, Frutos Rojos, Coulis Artesanal, Más Vendido', 'postres', 'dulce', 1, 1),
(8, 'Torta Melcochuda de Chocolate Belga & Arequipe', 15500, 'Porción generosa de torta de chocolate al 70%, centro melcochudo con hilos de arequipe y opción de bola de helado de vainilla.', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80', 'Chocolate Belga 70%, Centro Melcochudo, Arequipe', 'postres', 'dulce', 1, 1),
(8, 'Postre Tres Leches Huilense con Canela & Arequipe', 13500, 'Bizcocho esponjoso bañado en infusión de tres leches frescas, decorado con merengue suizo tostado y canela en polvo.', 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=600&auto=format&fit=crop&q=80', 'Tres Leches Tradicional, Arequipe Colombiano, Suave', 'postres', 'dulce', 0, 1),

-- 9. Waffle & Crepe Station Neiva
(9, 'Bubble Waffle Explosión Nutella & Helado', 21900, 'Waffle burbuja recién horneado, relleno con fresas frescas, banano, Nutella original caliente, bola de helado artesanal y barquillo.', 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80', 'Bubble Waffle, Nutella Original, Fresas Frescas, Favorito', 'waffles', 'dulce', 1, 1),
(9, 'Crepe Francés de Arequipe, Queso & Almendras', 17000, 'Fino crepe artesanal relleno de abundante arequipe colombiano, queso campesino fundido y lluvia de almendras tostadas fileteadas.', 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 'Crepe Parisino, Queso Fundido, Almendras Tostadas', 'waffles', 'dulce', 1, 1),

-- 10. Monster Shakes & Bebidas Dulces
(10, 'Monster Shake Choco-Oreo Suprema', 17500, 'Malteada ultra cremosa a base de helado de chocolate y vainilla, borde escarchado con Nutella y maní, galleta Oreo y crema chantilly.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Monster Shake, Galleta Oreo, Nutella, Chantilly', 'malteadas', 'dulce', 1, 1),
(10, 'Malteada Frutos Amarillos & Maracuyá Silvestre', 15900, 'Refrescante y cremosa malteada elaborada con pulpa pura de maracuyá huilense, helado artesanal y salsa de frutos amarillos.', 'https://images.unsplash.com/photo-1553787499-6f9133860278?w=600&auto=format&fit=crop&q=80', 'Maracuyá Huilense, Refrescante, Helado Artesanal', 'malteadas', 'dulce', 0, 1),

-- 11. Asados & Parrilla El Mohán
(11, 'Costillas BBQ Ahumadas al Mohán (500g)', 34900, 'Costillar de cerdo carnudo ahumado a fuego lento durante 6 horas en leña de guayabo, glaseado con salsa BBQ de panela y papas rústicas.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Costillas 500g, Ahumado Lento, BBQ Panela', 'burgers', 'salado', 1, 1),
(11, 'Picaña Angus Asada al Carbón con Papas Criollas', 38000, '350g de corte picaña madurada con su capa de grasa crujiente a la sal marina, acompañada de papitas criollas doradas y chimichurri artesanal.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop&q=80', 'Corte Premium 350g, Angus, Chimichurri', 'burgers', 'salado', 0, 1),

-- 12. La Fritanguería Opita
(12, 'Picada Fritanga Monumental para 2 Personas', 29900, 'Bandeja completa con chicharrón carnudo tostado, costillitas de cerdo, morcilla campesina, longaniza, papa criolla frita, plátano maduro y ají.', 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=600&auto=format&fit=crop&q=80', 'Para 2 Personas, Chicharrón, Papa Criolla', 'salchipapas', 'salado', 1, 1),
(12, 'Porción de Chicharrón Carnudo Crocante con Yuca', 16500, 'Tiras de tocino carnudo con corte en diamante bien tostado y crocante, servido con yuca al vapor y hogao casero caliente.', 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=600&auto=format&fit=crop&q=80', 'Chicharrón Crocante, Yuca al Vapor, Hogao', 'salchipapas', 'salado', 0, 1),

-- 13. Burger House Neiva
(13, 'Hamburguesa Trufada Burger Master 220g', 28900, 'Carne de res madurada 220g, queso brie fundido, mayonesa de trufa negra, cebollas caramelizadas en vino tinto y tocineta crocante.', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&auto=format&fit=crop&q=80', 'Burger Master, Queso Brie, Trufa Negra', 'burgers', 'salado', 1, 1),
(13, 'Hamburguesa Volcán de Queso Cheddar Líquido', 27500, 'Doble carne smash bañada en mesa con una cascada hirviente de queso cheddar fundido de la casa y tocineta en cubos.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Cascada de Cheddar, Doble Carne, Tocineta', 'burgers', 'salado', 0, 1),

-- 14. El Chuzo Desgranado Costeño & Huilense
(14, 'Chuzo Desgranado Trifásico Salvaje', 23500, 'Maíz dulce tierno a la plancha con lomo de res en tiras, pechuga de pollo, chorizo, tocineta, butifarra y una capa densa de queso costeño.', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Trifásico, Maíz Tierno, Doble Queso', 'desgranados', 'salado', 1, 1),
(14, 'Desgranado de Pollo Grill con Queso Costeño Rallado', 19800, 'Pechuga marinada en finas hierbas a la plancha, desgranada sobre maíz tierno a la mantequilla y queso costeño salpicado.', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80', 'Pollo Grill, Queso Costeño, Mantequilla', 'desgranados', 'salado', 0, 1),

-- 15. Perros Calientes El Paisita
(15, 'Perro Caliente Mega Paisita 35cm', 18500, 'Salchicha suiza de 35 centímetros a la parrilla, queso derretido, tocineta ahumada, ripio casero crujiente, cuatro huevos de codorniz y salsa de piña.', 'https://images.unsplash.com/photo-1627059312527-2c976939e6a9?w=600&auto=format&fit=crop&q=80', 'Mega 35cm, 4 Huevos Codorniz, Tocineta', 'perros', 'salado', 1, 1),
(15, 'Perro Caliente Hawaiano Gratinado', 15000, 'Salchicha tradicional con piña caramelizada calada, jamón seleccionado, tocineta y gratín de queso campesino.', 'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80', 'Hawaiano, Piña Calada, Gratinado', 'perros', 'salado', 0, 1),

-- 16. Arepas Cuadradas & Parrilla
(16, 'Arepa de Choclo con Queso Campesino & Carne', 16500, 'Arepa artesanal de choclo tierno y dulce, dorada en mantequilla con abundante queso campesino asado de Algeciras y carne desmechada.', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Choclo Dulce, Queso Algeciras, Carne Desmechada', 'arepas', 'salado', 1, 1),
(16, 'Arepa Cuadrada Mixta Chicharrón & Guacamole', 17900, 'Masa de maíz blanco asada rellena de chicharrón crocante en trocitos, pechuga mechada y generosa bola de guacamole fresco.', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Chicharrón, Guacamole Fresco, Maíz Puro', 'arepas', 'salado', 0, 1),

-- 17. Alitas & Wings Capital Neiva
(17, 'Combo 12 Alitas Crujientes con Papas Cascos', 27900, '12 alitas enteras apanadas con textura extra crunch, acompañadas de papas cascos sazonadas y 2 salsas a elección (BBQ, Maracuyá, Búfalo).', 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=600&auto=format&fit=crop&q=80', '12 Alitas, Extra Crunch, 2 Salsas', 'burgers', 'salado', 1, 1),
(17, 'Combo 6 Alitas BBQ Miel con Aros de Cebolla', 16900, '6 alitas jugosas bañadas en BBQ artesanal con miel de abejas y aros de cebolla crocantes.', 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=600&auto=format&fit=crop&q=80', '6 Alitas, BBQ Miel, Aros de Cebolla', 'burgers', 'salado', 0, 1),

-- 18. Salchipapas El Monstruo del Sur
(18, 'Salchipapa Volcánica Familiar (4 Personas)', 42000, 'Bandeja gigante para compartir: 1kg de papas francesas y criollas, lomo fino, pollo a la plancha, tocineta, salchicha ranchera y queso mozzarella fundido.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Familiar 4 Personas, 1kg Papas, Volcán Queso', 'salchipapas', 'salado', 1, 1),
(18, 'Salchipapa Personal Mixta Ranchera & Mozzarella', 18900, 'Porción individual generosa con salchicha ranchera, lomo de res picadito, tocineta y abundante queso costeño y tártara.', 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80', 'Personal, Salchicha Ranchera, Mozzarella', 'salchipapas', 'salado', 0, 1),

-- 19. Heladería & Cholados El Bambuco
(19, 'Gran Cholado Huilense con Helado & Frutas Frescas', 14500, 'Hielo triturado a mano con jarabe artesanal de frutas, guanábana, lulo, maracuyá, papaya, fresas, barquillo crocante y bola de helado de vainilla.', 'https://images.unsplash.com/photo-1505394033641-40c6ad1178d7?w=600&auto=format&fit=crop&q=80', 'Cholado del Malecón, Helado, 5 Frutas', 'helados', 'dulce', 1, 1),
(19, 'Lulada Huilense Helada con Leche Condensada', 9500, 'Bebida espesa con pulpa de lulo natural machacada, hielo picado, limón y una corona generosa de leche condensada.', 'https://images.unsplash.com/photo-1505394033641-40c6ad1178d7?w=600&auto=format&fit=crop&q=80', 'Lulada Natural, Refrescante, Leche Condensada', 'helados', 'dulce', 0, 1),

-- 20. Churrería Tradición Española & Café
(20, 'Cesta de 8 Churros Rellenos de Nutella & Arequipe', 15900, '8 churros artesanales fritos al momento bien crocantes y doraditos, rellenos al tope con Nutella tibia y arequipe colombiano.', 'https://images.unsplash.com/photo-1624300629298-e9de39c13be5?w=600&auto=format&fit=crop&q=80', '8 Churros, Rellenos Nutella, Arequipe', 'postres', 'dulce', 1, 1),
(20, 'Churros Clásicos con Chocolate Caliente Espeso', 13500, 'Churros tradicionales espolvoreados con azúcar y canela, acompañados de pocillo de chocolate español espeso para dipear.', 'https://images.unsplash.com/photo-1624300629298-e9de39c13be5?w=600&auto=format&fit=crop&q=80', 'Churros Tradicionales, Chocolate Espeso', 'postres', 'dulce', 0, 1),

-- 21. Donut Factory Neiva
(21, 'Caja Degustación 4 Donas Gourmet Rellenas', 21000, 'Caja de 4 donas artesanales: 1 Nutella & Avellana, 1 Crema Lotus Biscoff, 1 Frutos Rojos con Glaseado Rosa y 1 Pistacho Siciliano.', 'https://images.unsplash.com/photo-1527515862127-a4fc05baf7a5?w=600&auto=format&fit=crop&q=80', 'Caja 4 Donas, Gourmet, Rellenas', 'postres', 'dulce', 1, 1),
(21, 'Dona Bomba Rellena de Nutella & Fresas Frescas', 6900, 'Dona redonda sin agujero rellena a reventar con Nutella cremosa y cubierta con trozos de fresas frescas.', 'https://images.unsplash.com/photo-1527515862127-a4fc05baf7a5?w=600&auto=format&fit=crop&q=80', 'Bomba Nutella, Fresas, Glaseada', 'postres', 'dulce', 0, 1),

-- 22. Crepes & Waffles del Valle de Laboyos
(22, 'Crepe Suizo de Banano Flameado con Helado & Toffee', 18900, 'Fino crepe francés relleno de banano salteado con mantequilla y canela, bañado en salsa toffee caliente y bola de helado artesanal.', 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 'Banano Flameado, Toffee Caliente, Helado', 'waffles', 'dulce', 1, 1),
(22, 'Waffle Belga Crujiente con Frutas del Bosque', 19500, 'Waffle de masa belga con azúcar perlado crujiente, moras, arándanos y frambuesas frescas con salsa de frutos rojos.', 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80', 'Waffle Belga, Frutas del Bosque, Azúcar Perlado', 'waffles', 'dulce', 0, 1),

-- 23. Gelateria Bella Italia Neiva
(23, 'Copa Tulipán de Gelato Stracciatella & Pistacho', 17900, 'Dos bolas de auténtico gelato italiano sobre tulipán de galleta horneada, bañado en chocolate amargo y pistachos triturados.', 'https://images.unsplash.com/photo-1560008581-09826d1de69e?w=600&auto=format&fit=crop&q=80', 'Gelato Italiano, Stracciatella, Pistacho', 'helados', 'dulce', 1, 1),
(23, 'Affogato al Café Espresso Huilense con Helado', 11900, 'Shot doble de café espresso caliente recién extraído vertido sobre una bola generosa de gelato de vainilla bourbon.', 'https://images.unsplash.com/photo-1560008581-09826d1de69e?w=600&auto=format&fit=crop&q=80', 'Affogato, Espresso Huilense, Gelato Vainilla', 'helados', 'dulce', 0, 1),

-- 24. Cafetería & Panadería La Reina del Huila
(24, 'Milhoja Monumental de Arequipe Casero & Crema', 12500, 'Capas de hojaldre crujiente horneadas a la perfección, rellenas con arequipe de paila tradicional y crema chantilly ligera.', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', 'Milhoja Tradicional, Hojaldre Crocante, Arequipe', 'postres', 'dulce', 1, 1),
(24, 'Porción de Torta de Guanábana & Chantilly', 13900, 'Torta esponjosa humedecida en néctar de guanábana fresca del Huila, cubierta con merengue y crema batida suave.', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', 'Guanábana Huilense, Esponjosa, Chantilly', 'postres', 'dulce', 0, 1),

-- 25. Nieve Raspada & Granizados Tropicales
(25, 'Granizado de Café Huilense Especial con Chantilly', 13500, 'Granizado cremoso preparado con café arábica especial de altura de San Agustín, decorado con salsa de caramelo y chantilly.', 'https://images.unsplash.com/photo-1517256064527-09c73fc73e38?w=600&auto=format&fit=crop&q=80', 'Café San Agustín, Granizado, Chantilly', 'helados', 'dulce', 1, 1),
(25, 'Granizado Tropical de Maracuyá & Mango Biche', 11900, 'Hielo frappé con pulpa ácida de maracuyá y mango biche, escarchado con sal y toquecito agridulce.', 'https://images.unsplash.com/photo-1517256064527-09c73fc73e38?w=600&auto=format&fit=crop&q=80', 'Maracuyá & Mango Biche, Refrescante, Ácido', 'helados', 'dulce', 0, 1),

-- 26. Brownie & Cookie Lab Neiva
(26, 'Skillet de Brownie Caliente con Bola de Helado', 17500, 'Sartencita de hierro con brownie de chocolate melcochudo recién horneado, coronado con bola de helado de vainilla y Nutella caliente.', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=600&auto=format&fit=crop&q=80', 'Skillet de Brownie, Melcochudo, Nutella', 'postres', 'dulce', 1, 1),
(26, 'Galleta Gigante New York Rellena de Nutella Caliente', 8500, '150g de galleta con chispas de chocolate belga, centro líquido de Nutella que brota al partirla caliente.', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=600&auto=format&fit=crop&q=80', 'Cookie New York 150g, Nutella Líquida, Belga', 'postres', 'dulce', 0, 1),

-- 27. Shakes & Frappes del Huila
(27, 'Frappuccino Moca-Arequipe Supremo', 16500, 'Frappé de café espresso huilense con helado de chocolate, salsa de arequipe en las paredes del vaso y lluvia de virutas de chocolate.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Frappuccino, Espresso Huila, Moca Arequipe', 'malteadas', 'dulce', 1, 1),
(27, 'Malteada de Vainilla Bourbon con Chispas de Colores', 14000, 'Cremosa y clásica malteada con helado de vainilla de Madagascar, chantilly artesanal y chispas crocantes de colores.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Vainilla Bourbon, Malteada Cremosa, Chantilly', 'malteadas', 'dulce', 0, 1),

-- 28. Fresas con Crema La Cabaña
(28, 'Vaso Monumental de Fresas con Crema & Queso', 15500, 'Capas alternadas de fresas frescas seleccionadas, crema de leche batida artesanal, queso campesino rallado y salsa de fresa casera.', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&auto=format&fit=crop&q=80', 'Fresas con Crema, Queso Rallado, Monumental', 'postres', 'dulce', 1, 1),
(28, 'Copa de Fresas con Nutella Caliente & Barquillo', 16500, 'Fresas frescas en láminas bañadas en abundante Nutella caliente, barquillos crocantes de chocolate y crema chantilly.', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&auto=format&fit=crop&q=80', 'Fresas con Nutella, Barquillos, Chantilly', 'postres', 'dulce', 0, 1);

-- ---------------------------------------------------------
-- 12. Tabla: Opciones de Personalización de Platos
-- ---------------------------------------------------------
CREATE TABLE `item_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(50) NOT NULL,
  `item_type` ENUM('salado', 'dulce', 'ambos') DEFAULT 'salado',
  `name` VARCHAR(100) NOT NULL,
  `price` INT DEFAULT 0,
  `is_default` TINYINT(1) DEFAULT 0,
  `icon` VARCHAR(50) DEFAULT 'check',
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `item_options` (`category`, `item_type`, `name`, `price`, `is_default`, `icon`) VALUES
-- Salados
('termino', 'salado', 'Término Medio (Centro rojo y jugoso)', 0, 0, 'radio_button_unchecked'),
('termino', 'salado', 'Tres Cuartos - 3/4 (Punto ideal de la casa)', 0, 1, 'radio_button_checked'),
('termino', 'salado', 'Bien Asada (Completamente cocida a la brasa)', 0, 0, 'radio_button_unchecked'),

('pan', 'salado', 'Pan Brioche Dorado Tradicional', 0, 1, 'bakery_dining'),
('pan', 'salado', 'Pan Rústico con Ajonjolí Negro', 1500, 0, 'grain'),
('pan', 'salado', 'Pan con Orégano y Queso Parmesano', 2000, 0, 'cookie'),
('pan', 'salado', 'Envoltura de Lechuga Batavia (Sin gluten)', 0, 0, 'eco'),

('topping', 'salado', 'Tocineta Ahumada Crunch (Extra)', 3500, 0, 'bacon'),
('topping', 'salado', 'Huevo Frito Campesino Yema Tierna', 2000, 0, 'egg'),
('topping', 'salado', 'Queso Campesino Asado de Algeciras', 3000, 0, 'cheese'),
('topping', 'salado', 'Carne de Res Adicional 150g al Carbón', 7500, 0, 'kebab_dining'),
('topping', 'salado', 'Cebolla Caramelizada en Panela', 1500, 0, 'scatter_plot'),
('topping', 'salado', 'Chicharrón Crocante Picado', 4000, 0, 'restaurant'),
('topping', 'salado', 'Papas Fosforito Crujientes', 1200, 0, 'lunch_dining'),

('salsa', 'salado', 'Tártara Especial de la Abuela', 0, 1, 'check_box'),
('salsa', 'salado', 'BBQ Artesanal con Guayaba Huilense', 0, 1, 'check_box'),
('salsa', 'salado', 'Salsa de Ajo Suave & Perejil', 0, 0, 'check_box_outline_blank'),
('salsa', 'salado', 'Piña Melada Colombiana', 0, 0, 'check_box_outline_blank'),
('salsa', 'salado', 'Picante Casero Ají Opita (Medio)', 0, 0, 'check_box_outline_blank'),

-- Dulces: Sabores de Helado / Base
('sabor_helado', 'dulce', 'Vainilla Francesa Bourbon (Artesanal)', 0, 1, 'icecream'),
('sabor_helado', 'dulce', 'Chocolate Belga Intenso 70%', 0, 0, 'icecream'),
('sabor_helado', 'dulce', 'Frutos Rojos Silvestres Huilenses', 0, 0, 'icecream'),
('sabor_helado', 'dulce', 'Arequipe Colombiano Caramelizado', 0, 0, 'icecream'),
('sabor_helado', 'dulce', 'Maracuyá Tropical del Huila', 0, 0, 'icecream'),
('sabor_helado', 'dulce', 'Pistacho Siciliano Tostado Real', 3000, 0, 'icecream'),
('sabor_helado', 'dulce', 'Mora Silvestre en Leche Fresca', 0, 0, 'icecream'),
('sabor_helado', 'dulce', 'Brownie Fudge & Helado Vainilla', 1500, 0, 'icecream'),

-- Dulces: Base de Presentación
('base_dulce', 'dulce', 'Vaso Ecológico Especial para Postre', 0, 1, 'local_cafe'),
('base_dulce', 'dulce', 'Cono Waffle Crocante de Vainilla y Canela', 1500, 0, 'bakery_dining'),
('base_dulce', 'dulce', 'Canasta de Galleta Mantequilla Horneada', 2500, 0, 'cookie'),
('base_dulce', 'dulce', 'Masa de Waffle Tradicional Dorada', 0, 0, 'waffle'),
('base_dulce', 'dulce', 'Masa Red Velvet con Toque de Vainilla', 2000, 0, 'cake'),

-- Dulces: Siropes y Salsas
('sirope_dulce', 'dulce', 'Nutella Original Caliente y Fundida', 0, 1, 'water_drop'),
('sirope_dulce', 'dulce', 'Arequipe Artesanal Tibio de la Casa', 0, 1, 'water_drop'),
('sirope_dulce', 'dulce', 'Coulis Casero de Frutos Rojos y Moras', 0, 0, 'water_drop'),
('sirope_dulce', 'dulce', 'Caramelo Salado (Salted Caramel Gourmet)', 0, 0, 'water_drop'),
('sirope_dulce', 'dulce', 'Chocolate Blanco Derretido Suave', 0, 0, 'water_drop'),
('sirope_dulce', 'dulce', 'Miel de Maple Pura de Canadá', 0, 0, 'water_drop'),
('sirope_dulce', 'dulce', 'Salsa Exótica de Maracuyá Silvestre', 0, 0, 'water_drop'),

-- Dulces: Toppings Crujientes
('topping_dulce', 'dulce', 'Galleta Oreo Original Triturada', 2500, 0, 'cookie'),
('topping_dulce', 'dulce', 'Almendras Tostadas Fileteadas', 3000, 0, 'grain'),
('topping_dulce', 'dulce', 'Chispas de Chocolate Belga Semi-Amargo', 2000, 0, 'scatter_plot'),
('topping_dulce', 'dulce', 'Fresas Frescas Picadas al Instante', 3500, 0, 'nutrition'),
('topping_dulce', 'dulce', 'Rodajas de Banano Fresco Criollo', 1500, 0, 'nutrition'),
('topping_dulce', 'dulce', 'Crema Chantilly Casera Ultra Espesa', 2500, 0, 'cloud'),
('topping_dulce', 'dulce', 'Barquillos Crocantes Rellenos de Chocolate', 2000, 0, 'bakery_dining'),
('topping_dulce', 'dulce', 'Lluvia de M&Ms de Chocolate Crujiente', 2500, 0, 'stars'),
('topping_dulce', 'dulce', 'Gomitas Dulces & Ácidas Frutales', 2000, 0, 'bubble_chart'),
('topping_dulce', 'dulce', 'Bola Adicional de Gelato a Elección', 4500, 0, 'icecream'),

-- Dulces: Nivel de Dulzura
('dulzura', 'dulce', 'Dulzura Tradicional de la Casa (Equilibrado Perfecto)', 0, 1, 'check_circle'),
('dulzura', 'dulce', 'Dulzura Ligera / Bajo en Azúcar', 0, 0, 'check_circle'),
('dulzura', 'dulce', 'Toque Extra Dulce para Fanáticos', 0, 0, 'check_circle');

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
  `restaurant_id` INT DEFAULT 1,
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
