--liquibase formatted sql

--changeset fayber-diaz:002-toppings-and-modifiers-schema splitStatements:true endDelimiter:;
--comment Arquitectura relacional completa de Toppings, Salsas, Siropes, Sabores de Helado y Adiciones para Comidas Rápidas y Postres

CREATE TABLE IF NOT EXISTS `item_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(40) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `price` INT NOT NULL DEFAULT 0,
  `is_default` TINYINT(1) DEFAULT 0,
  `icon` VARCHAR(40) DEFAULT 'star',
  `applies_to` VARCHAR(40) DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `toppings_catalog` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(120) NOT NULL,
  `topping_group` ENUM('quesos', 'carnes_proteinas', 'crocantes_vegetales', 'salsas_saladas', 'frutas_frescas', 'chocolates_galletas', 'helados_cremas', 'siropes_dulces') NOT NULL,
  `dish_type` ENUM('salado', 'dulce', 'ambos') NOT NULL DEFAULT 'salado',
  `price` INT NOT NULL DEFAULT 0,
  `calories` INT DEFAULT 80,
  `is_vegan` TINYINT(1) DEFAULT 0,
  `is_popular` TINYINT(1) DEFAULT 1,
  `max_quantity` INT DEFAULT 5,
  `is_available` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_item_toppings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_item_id` INT NOT NULL,
  `topping_name` VARCHAR(120) NOT NULL,
  `topping_group` VARCHAR(50) DEFAULT 'extra',
  `unit_price` INT NOT NULL DEFAULT 0,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `item_options`;

INSERT INTO `item_options` (`category`, `name`, `price`, `is_default`) VALUES
-- 1. Términos de la Carne (Comidas Rápidas & Asados)
('termino', 'Tres Cuartos - 3/4 (Punto ideal jugoso de la casa)', 0, 1),
('termino', 'Término Medio - 1/2 (Centro rojo tierno y jugoso)', 0, 0),
('termino', 'Bien Asado (100% cocido y doradito al carbón)', 0, 0),
('termino', 'Costra Smash Extra Crocante (Caramelizado a la plancha)', 0, 0),

-- 2. Panes Artesanales y Bases Saladas
('pan', 'Pan Brioche Dorado en Mantequilla (Tradicional)', 0, 1),
('pan', 'Pan de Papa Artesanal Suave con Ajonjolí', 0, 0),
('pan', 'Pan Parmesano & Orégano Tostado', 1500, 0),
('pan', 'Base de Patacón Pisado de Plátano Verde Huilense', 2500, 0),
('pan', 'Arepa de Maíz Choclo Dulce con Mantequilla', 2500, 0),
('pan', 'Pan Pretzel Alemán Artesanal', 2000, 0),

-- 3. Toppings Salados & Adiciones para Comidas Rápidas (Con Precio)
('topping', 'Tocineta Ahumada Crunch en Tiras (Extra)', 3500, 0),
('topping', 'Queso Campesino Asado de Algeciras (Lonja Gruesa)', 3000, 0),
('topping', 'Queso Cheddar Americano Fundido en Cascada', 3200, 0),
('topping', 'Queso Mozzarella Gratinado Extra', 2800, 0),
('topping', 'Queso Costeño Rallado en Lluvia', 2500, 0),
('topping', 'Huevo Frito Campesino Yema Tierna', 2000, 0),
('topping', 'Huevos de Codorniz (Porción x4 unidades)', 2500, 0),
('topping', 'Carne de Res Extra 150g a la Parrilla', 7500, 0),
('topping', 'Disco Smash Angus Extra 110g con Cheddar', 6800, 0),
('topping', 'Pollo Desmechado en Hogao Huilense', 4500, 0),
('topping', 'Carne Desmechada a la Criolla', 4800, 0),
('topping', 'Pulled Pork Ahumado en BBQ de Whisky', 5500, 0),
('topping', 'Chicharrón Carnudo Crocante en Cubos', 4800, 0),
('topping', 'Chorizo Santarrosano Artesanal a la Brasa', 4200, 0),
('topping', 'Salchicha Ranchera Premium Picada', 3800, 0),
('topping', 'Aros de Cebolla Apanados Extra Crunch (x4)', 3500, 0),
('topping', 'Cebolla Caramelizada en Panela y Ron', 2200, 0),
('topping', 'Maíz Tierno Dulce Salteado en Mantequilla', 2500, 0),
('topping', 'Guacamole Fresco Machacado con Cilantro', 3000, 0),
('topping', 'Jalapeños Mexicanos Encurtidos Picantes', 1800, 0),
('topping', 'Pepinillos Dill Agridulces en Rodajas', 1800, 0),
('topping', 'Piña Calada Dulce Asada a la Parrilla', 2000, 0),
('topping', 'Maduritos Guayaberos con Queso', 3200, 0),
('topping', 'Papas Francesas / Criollas Adicionales', 4500, 0),

-- 4. Salsas Saladas de la Casa (Incluidas / Gratis)
('salsa', 'Tártara Especial de la Abuela (Receta Secreta)', 0, 1),
('salsa', 'BBQ Artesanal Ahumada con Panela Huilense', 0, 1),
('salsa', 'Salsa de Piña Dulce Calada Natural', 0, 0),
('salsa', 'Mayonesa de Ajo Rostizado y Cilantro', 0, 0),
('salsa', 'Salsa Rosada Colombiana Clásica', 0, 0),
('salsa', 'Miel Mostaza Antigua Dulce', 0, 0),
('salsa', 'Suero Costeño Cremoso con Toque de Ajo', 0, 0),
('salsa', 'Ají Criollo Huilense Picante con Aguacate', 0, 0),
('salsa', 'Salsa Chipotle Ahumado Mexicano', 0, 0),
('salsa', 'Chimichurri Argentino de Perejil y Ajo', 0, 0),

-- 5. Sabores de Helado / Gelato Artesanal (Dulces y Postres)
('sabor_helado', 'Vainilla Francesa Bourbon (Artesanal)', 0, 1),
('sabor_helado', 'Chocolate Belga Oscuro al 70%', 0, 0),
('sabor_helado', 'Frutos Rojos Silvestres del Huila (Mora & Fresa)', 0, 0),
('sabor_helado', 'Maracuyá Cremoso del Magdalena', 0, 0),
('sabor_helado', 'Arequipe de Paila con Almendras', 0, 0),
('sabor_helado', 'Cookies & Cream con Galleta Oreo', 0, 0),
('sabor_helado', 'Pistacho Siciliano Tostado Premium', 1500, 0),
('sabor_helado', 'Café Especial de San Agustín (Huila)', 0, 0),
('sabor_helado', 'Guanábana Cremosa Natural', 0, 0),
('sabor_helado', 'Macadamia & Caramelo Salado', 1000, 0),

-- 6. Bases de Presentación para Dulces y Postres
('base_dulce', 'Vaso Térmico Ecológico Especial para Postre', 0, 1),
('base_dulce', 'Canasta de Galleta Waffle Horneada Crocante', 2000, 0),
('base_dulce', 'Cono Waffle Cubierto de Chocolate y Maní', 2500, 0),
('base_dulce', 'Base de Brownie Tibio Melcochudo', 4000, 0),
('base_dulce', 'Oblea Gigante Doble Galleta Crocante', 1500, 0),

-- 7. Siropes y Salsas Dulces Calientes
('sirope_dulce', 'Nutella Original Caliente y Fundida', 0, 1),
('sirope_dulce', 'Arequipe Artesanal Tibio de la Casa', 0, 1),
('sirope_dulce', 'Chocolate Fudge Español Espeso', 0, 0),
('sirope_dulce', 'Leche Condensada Cremosa en Cascada', 0, 0),
('sirope_dulce', 'Coulis Artesanal de Mora y Frutos Rojos', 0, 0),
('sirope_dulce', 'Salsa de Maracuyá Dulce-Ácida', 0, 0),
('sirope_dulce', 'Caramelo Salado (Salted Caramel) Tibio', 0, 0),
('sirope_dulce', 'Crema de Galleta Lotus Biscoff Fundida', 0, 0),

-- 8. Toppings Dulces, Frutas, Chocolates y Adiciones de Postres
('topping_dulce', 'Fresas Frescas Seleccionadas en Trozos', 3000, 0),
('topping_dulce', 'Banano Fresco en Rodajas Caramelizadas', 2000, 0),
('topping_dulce', 'Bola Extra de Gelato Artesanal (90g)', 4500, 0),
('topping_dulce', 'Lluvia de Galleta Oreo Triturada', 2500, 0),
('topping_dulce', 'Trozos de Brownie Melcochudo con Nueces', 3500, 0),
('topping_dulce', 'Chocolatinas M&Ms / Chocoramo en Trozos', 3000, 0),
('topping_dulce', 'Queso Campesino Rallado (Contraste Dulce-Salado)', 2800, 0),
('topping_dulce', 'Crema Chantilly Batida Extra con Cereza', 2200, 0),
('topping_dulce', 'Almendras Fileteadas Tostadas & Maní Crocante', 2500, 0),
('topping_dulce', 'Barquillos Rellenos de Chocolate Piazza (x2)', 2000, 0),
('topping_dulce', 'Masmelos (Marshmallows) Mini Tostados', 2000, 0),
('topping_dulce', 'Arándanos y Moras Frescas del Huila', 3500, 0),
('topping_dulce', 'Inyección Extra de Nutella Pura (30ml)', 3800, 0),
('topping_dulce', 'Galleta Lotus Biscoff Entera + Triturada', 3200, 0),

-- 9. Nivel de Dulzura
('dulzura', 'Dulzura Tradicional de la Casa (Equilibrio Perfecto)', 0, 1),
('dulzura', 'Ligero en Dulce (Salsas suaves al 50%)', 0, 0),
('dulzura', 'Extra Goloso (Bañado abundante en salsas)', 0, 0);

INSERT IGNORE INTO `toppings_catalog` (`code`, `name`, `topping_group`, `dish_type`, `price`, `calories`, `is_popular`) VALUES
('TOP_TOCINETA',   'Tocineta Ahumada Crunch en Tiras',            'carnes_proteinas',    'salado', 3500, 140, 1),
('TOP_Q_ALGECIRAS','Queso Campesino Asado de Algeciras',          'quesos',              'salado', 3000, 120, 1),
('TOP_Q_CHEDDAR',  'Queso Cheddar Americano Fundido',             'quesos',              'salado', 3200, 130, 1),
('TOP_Q_COSTENO',  'Queso Costeño Rallado en Lluvia',             'quesos',              'salado', 2500, 110, 1),
('TOP_HUEVO_FRITO','Huevo Frito Campesino Yema Tierna',           'carnes_proteinas',    'salado', 2000, 90,  1),
('TOP_CODORNIZ',   'Huevos de Codorniz (x4 unidades)',            'carnes_proteinas',    'salado', 2500, 85,  1),
('TOP_CARNE_150',  'Carne de Res Extra 150g a la Parrilla',       'carnes_proteinas',    'salado', 7500, 290, 1),
('TOP_SMASH_110',  'Disco Smash Angus Extra 110g con Cheddar',    'carnes_proteinas',    'salado', 6800, 250, 1),
('TOP_PULLED_PORK','Pulled Pork Ahumado en BBQ de Whisky',        'carnes_proteinas',    'salado', 5500, 220, 1),
('TOP_CHICHARRON', 'Chicharrón Carnudo Crocante en Cubos',        'carnes_proteinas',    'salado', 4800, 260, 1),
('TOP_CHORIZO',    'Chorizo Santarrosano Artesanal a la Brasa',   'carnes_proteinas',    'salado', 4200, 210, 1),
('TOP_AROS_CEB',   'Aros de Cebolla Apanados Extra Crunch (x4)',  'crocantes_vegetales', 'salado', 3500, 150, 1),
('TOP_CEB_CARAM',  'Cebolla Caramelizada en Panela y Ron',        'crocantes_vegetales', 'salado', 2200, 75,  1),
('TOP_MAIZ_DULCE', 'Maíz Tierno Dulce Salteado en Mantequilla',   'crocantes_vegetales', 'salado', 2500, 95,  1),
('TOP_GUACAMOLE',  'Guacamole Fresco Machacado con Cilantro',     'crocantes_vegetales', 'salado', 3000, 110, 1),
('TOP_JALAPENOS',  'Jalapeños Mexicanos Encurtidos Picantes',     'crocantes_vegetales', 'salado', 1800, 20,  0),
('TOP_FRESAS',     'Fresas Frescas Seleccionadas en Trozos',      'frutas_frescas',      'dulce',  3000, 45,  1),
('TOP_BANANO',     'Banano Fresco en Rodajas Caramelizadas',      'frutas_frescas',      'dulce',  2000, 85,  1),
('TOP_BOLA_GELATO','Bola Extra de Gelato Artesanal (90g)',        'helados_cremas',      'dulce',  4500, 160, 1),
('TOP_OREO',       'Lluvia de Galleta Oreo Triturada',            'chocolates_galletas', 'dulce',  2500, 140, 1),
('TOP_BROWNIE',    'Trozos de Brownie Melcochudo con Nueces',     'chocolates_galletas', 'dulce',  3500, 180, 1),
('TOP_MMS',        'Chocolatinas M&Ms / Chocoramo en Trozos',     'chocolates_galletas', 'dulce',  3000, 165, 1),
('TOP_Q_DULCE',    'Queso Campesino Rallado para Postre',         'quesos',              'dulce',  2800, 110, 1),
('TOP_CHANTILLY',  'Crema Chantilly Batida Extra con Cereza',     'helados_cremas',      'dulce',  2200, 95,  1),
('TOP_ALMENDRAS',  'Almendras Fileteadas Tostadas & Maní',        'chocolates_galletas', 'dulce',  2500, 130, 1),
('TOP_NUTELLA_EX', 'Inyección Extra de Nutella Pura (30ml)',      'siropes_dulces',      'dulce',  3800, 190, 1),
('TOP_LOTUS',      'Galleta Lotus Biscoff Entera + Triturada',    'chocolates_galletas', 'dulce',  3200, 150, 1);
