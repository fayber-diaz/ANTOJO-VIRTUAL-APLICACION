--liquibase formatted sql

--changeset fayber-diaz:001-core-tables-and-neiva-config splitStatements:true endDelimiter:;
--comment Creación del esquema base MySQL para Antojo Virtual Neiva (Comunas, Bancos PSE, Usuarios, JWT, Pedidos, Facturas DIAN y Reseñas)

CREATE TABLE IF NOT EXISTS `comunas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `delivery_fee` INT NOT NULL DEFAULT 3500,
  `estimated_minutes` VARCHAR(30) DEFAULT '25-35 min',
  `center_lat` DECIMAL(10,6) DEFAULT 2.927300,
  `center_lng` DECIMAL(10,6) DEFAULT -75.281900,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `comunas` (`code`, `name`, `description`, `delivery_fee`, `estimated_minutes`) VALUES
('comuna_1',  'Comuna 1 - Cándido / Camilo Torres',      'Zona norte residencial y universitaria (USCO)', 3800, '25-35 min'),
('comuna_2',  'Comuna 2 - Las Granjas / Los Mártires',    'Zona norte-centro de alta demanda gastronómica', 3200, '20-30 min'),
('comuna_3',  'Comuna 3 - Quirinal / Santa Inés',         'Sector empresarial, clínicas y parques',         3000, '18-25 min'),
('comuna_4',  'Comuna 4 - Centro Histórico / Malecón',    'Corazón comercial, Parque Santander y Río Magdalena', 2800, '15-22 min'),
('comuna_5',  'Comuna 5 - Altico / Buganviles',           'Zona oriente tradicional y Av. Buganviles',      3200, '20-30 min'),
('comuna_6',  'Comuna 6 - Timanco / Puertas del Sol',     'Sur de Neiva, centros comerciales y residencial', 4000, '30-40 min'),
('comuna_7',  'Comuna 7 - Ipanema / Las Brisas',          'Oriente alto residencial y gastronómico',        3600, '25-35 min'),
('comuna_8',  'Comuna 8 - Las Américas / Alfonso López',  'Oriente tradicional de la ciudad',               3900, '30-40 min'),
('comuna_9',  'Comuna 9 - Luis Carlos Galán / Norte',     'Norte extremo de Neiva',                         4200, '35-45 min'),
('comuna_10', 'Comuna 10 - Las Palmas / Oriente Alto',    'Zona oriente alta de expansión urbana',          4500, '35-50 min');

CREATE TABLE IF NOT EXISTS `banks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(40) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `short_name` VARCHAR(50) NOT NULL,
  `type` ENUM('bank', 'neobank', 'wallet') DEFAULT 'bank',
  `icon_svg` VARCHAR(100) DEFAULT 'account_balance',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `banks` (`code`, `name`, `short_name`, `type`, `icon_svg`, `sort_order`) VALUES
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

CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `type` ENUM('online', 'wallet', 'cash', 'card') NOT NULL,
  `fee_percentage` DECIMAL(4,2) DEFAULT 0.00,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `payment_methods` (`slug`, `name`, `description`, `icon`, `type`, `fee_percentage`, `sort_order`) VALUES
('pse',           'PSE - Pagos Seguros en Línea',   'Débito automático desde cualquier cuenta bancaria en Colombia', 'account_balance',        'online', 0.00, 1),
('card',          'Tarjeta de Crédito / Débito',     'Visa, Mastercard, American Express, Diners con hasta 36 cuotas', 'credit_card',            'card',   0.00, 2),
('nequi',         'Nequi Directo',                   'Pago instantáneo mediante notificación push o QR dinámico',    'account_balance_wallet', 'wallet', 0.00, 3),
('daviplata',     'Daviplata Directo',               'Aprobación rápida mediante push o clave dinámica OTP',         'phone_android',          'wallet', 0.00, 4),
('cash_efectivo', 'Efectivo contra Entrega',         'Paga en efectivo al recibir tu pedido en Neiva (con cambio)',  'attach_money',           'cash',   0.00, 5),
('cash_datafono', 'Datáfono contra Entrega',         'Paga con tarjeta física débito/crédito al repartidor',         'point_of_sale',          'cash',   0.00, 6);

CREATE TABLE IF NOT EXISTS `users` (
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
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jwt_refresh_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `jti` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `revoked` TINYINT(1) DEFAULT 0,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jwt_blacklist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jti` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('all',         'Todos los Antojos',        'restaurant',    1),
('pizzas',      'Pizzas & Calzones',        'local_pizza',   2),
('asados',      'Asados & Parrilla Brasa',  'outdoor_grill', 3),
('burgers',     'Hamburguesas & Smash',     'lunch_dining',  4),
('salchipapas', 'Salchipapas Monumentales', 'fastfood',      5),
('pollo',       'Pollo Crispy & Alitas',    'restaurant',    6),
('mexicana',    'Tacos & Comida Tex-Mex',   'lunch_dining',  7),
('arepas',      'Arepas & Patacones',       'bakery_dining', 8),
('perros',      'Perros Calientes',         'kebab_dining',  9),
('desgranados', 'Desgranados & Mazorca',    'grain',         10),
('helados',     'Helados & Gelato',         'icecream',      11),
('postres',     'Postres, Tortas & Fresas', 'cake',          12),
('waffles',     'Waffles, Crepes & Obleas', 'cookie',        13),
('malteadas',   'Monster Shakes & Frappés', 'local_cafe',    14);

CREATE TABLE IF NOT EXISTS `riders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `discount_amount` INT NOT NULL,
  `min_purchase` INT DEFAULT 15000,
  `description` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `used_count` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `coupons` (`code`, `discount_amount`, `min_purchase`, `description`, `is_active`) VALUES
('ANTOJONEIVA5K', 5000, 20000, 'Descuento especial de $5.000 COP en tu antojo opita', 1),
('SANPEDRO10K',   10000, 45000, 'Bono festivalero de $10.000 COP para pedidos familiares', 1),
('DULCEHUILA4K',  4000, 18000, 'Descuento de $4.000 COP en helados, waffles y postres', 1),
('ENVIOGRATIS',   3500, 25000, 'Domicilio completamente gratis en las 10 comunas de Neiva', 1);
