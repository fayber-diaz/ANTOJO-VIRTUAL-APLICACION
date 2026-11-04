-- =========================================================
-- Migración: Ampliación a 40 Sitios Gastronómicos en Neiva
-- Antojo Virtual: 26 Comidas Rápidas, Pizzas, Asados, Burgers & Tex-Mex
-- + 14 Sitios Dulces, Helados y Postres (Total: 40 Sitios y 79 Platos)
-- =========================================================

USE `antojo_virtual`;

-- 1. Actualizar y Agregar Nuevas Categorías
INSERT INTO `categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('pizzas',   'Pizzas & Calzones',          'local_pizza',   2),
('asados',   'Asados & Parrilla al Carbón','outdoor_grill', 3),
('pollo',    'Pollo Crispy & Broaster',    'restaurant',    6),
('mexicana', 'Tacos & Comida Tex-Mex',     'lunch_dining',  7),
('helados',  'Helados & Gelato',           'icecream',      11),
('postres',  'Postres & Tortas',           'cake',          12),
('waffles',  'Waffles & Crepes',           'cookie',        13),
('malteadas','Monster Shakes',             'local_cafe',    14)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`);

-- 2. Insertar 12 Nuevos Restaurantes de Comidas Rápidas, Pizzas, Asados y Tex-Mex
INSERT IGNORE INTO `restaurants` (`slug`, `name`, `address`, `comuna`, `phone`, `whatsapp`, `rating`, `reviews_count`, `delivery_time`, `delivery_fee`, `badge`, `badge_type`, `image`, `description`, `star_dish`, `star_price`, `star_tag`, `category`, `site_type`, `is_open`, `created_at`) VALUES
-- Pizzas Artesanales & Calzones (3 sitios)
('pizzeria-napoles-neiva', 'Pizzería Nápoles Neiva', 'Cra 15 #18-40, Quirinal', 'Comuna 3 - Quirinal', '+57 312 990 4455', '573129904455', 4.9, 188, '25-35 min', 3200, 'Horno de Leña', 'bestseller', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80', 'Auténtica pizza napolitana horneada a la leña a 450°C, masa fermentada 48 horas, pomodoro italiano y mozzarella fior di latte.', 'Pizza Napolitana Diávola al Horno de Leña', 32000, 'Horno de Leña', 'pizzas', 'comidas_rapidas', 1, NOW()),

('pizza-nostra-huila', 'Pizza Nostra • Masa Madre & Birra', 'Cra 5 #14-22, La Toma', 'Comuna 4 - Centro', '+57 314 332 5566', '573143325566', 4.8, 142, '20-35 min', 3000, 'Masa Madre', 'verified', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=600&auto=format&fit=crop&q=80', 'Pizzas estilo Deep Dish y tradicionales con masa madre crocante, quesos artesanales fundidos y embutidos premium.', 'Pizza Deep Dish Chicago Style Huilense', 36900, 'Estilo Chicago', 'pizzas', 'comidas_rapidas', 1, NOW()),

('pizzas-el-padrino-neiva', 'Pizzas El Padrino Neiva', 'Av. Buganviles Cra 40 #18-12', 'Comuna 5 - Buganviles', '+57 316 889 0022', '573168890022', 4.9, 210, '25-40 min', 3500, 'Familiar Gigante', 'bestseller', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&auto=format&fit=crop&q=80', 'Pizzas monumentales de 8 y 12 porciones con borde relleno de queso mozzarella o bocadillo, tocineta crocante y maíz tierno.', 'Pizza Familiar Campesina con Tocineta & Maíz', 35000, 'Para Compartir', 'pizzas', 'comidas_rapidas', 1, NOW()),

-- Hamburguesas Smash & Gourmet (3 sitios)
('smash-burger-lab', 'Smash Burger Lab Neiva', 'Cra 6 #22-15, Santa Inés', 'Comuna 3 - Santa Inés', '+57 318 440 1199', '573184401199', 4.9, 275, '20-30 min', 3000, 'Smash Auténtico', 'bestseller', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Discos de carne smash ultrafinos con costra caramelizada al máximo, pan brioche de papa dorado y queso americano derretido.', 'Triple Smash Burger con Costra Caramelizada', 27900, 'Más Pedida', 'burgers', 'comidas_rapidas', 1, NOW()),

('black-angus-gourmet', 'Black Angus Gourmet Burger', 'Calle 18 #8-20, Ipanema', 'Comuna 7 - Ipanema', '+57 315 778 3321', '573157783321', 4.9, 160, '25-35 min', 3500, 'Carne Angus 100%', 'verified', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Hamburguesas de autor con carne Black Angus certificada de 220g, cebolla confitada en ron Flor de Caña y queso gouda madurado.', 'Hamburguesa Angus 220g Cebolla al Ron', 31500, 'Gourmet Master', 'burgers', 'comidas_rapidas', 1, NOW()),

('toro-bravo-burger-ribs', 'Toro Bravo Burger & Ribs', 'Cra 12 #6-45, Altico', 'Comuna 5 - Altico', '+57 311 665 9988', '573116659988', 4.8, 135, '20-35 min', 3200, 'Pulled Pork', 'speed', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&auto=format&fit=crop&q=80', 'La combinación explosiva: carne de res parrillera coronada con pulled pork ahumado 8 horas en salsa BBQ artesanal con whisky.', 'Hamburguesa Toro Salvaje con Pulled Pork', 29900, 'Reto Parrillero', 'burgers', 'comidas_rapidas', 1, NOW()),

-- Asados al Carbón & Parrilladas (3 sitios)
('parrilla-la-huilense', 'Parrillada & Brasa La Huilense', 'Av. Circunvalar con Calle 10', 'Comuna 3 - Quirinal', '+57 313 554 8877', '573135548877', 4.9, 310, '30-45 min', 3500, 'Parrillada Huilense', 'bestseller', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Las mejores parrilladas familiares de Neiva: churrasco jugoso, pechuga parrillera, costillas de cerdo, chunchullo crocante y arepas campesinas.', 'Gran Parrillada Huilense para 3 Personas', 58000, 'Familiar Suprema', 'asados', 'comidas_rapidas', 1, NOW()),

('fogata-llanera-neiva', 'Asados & Fogata Llanera Neiva', 'Cra 5 #2-30, Timanco', 'Comuna 6 - Timanco', '+57 317 223 8811', '573172238811', 4.8, 225, '25-40 min', 3800, 'Asado en Varas', 'verified', 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop&q=80', 'Tradición de carne a la llanera asada lentamente en varas de leña al aire libre, servida con plátano maduro con queso y guacamole.', 'Carne a la Llanera en Varas de Leña (400g)', 32900, 'Tradición Fuego', 'asados', 'comidas_rapidas', 1, NOW()),

('asados-malecon-rio', 'Asados Del Malecón del Río', 'Malecón Turístico Cra 1D #8-20', 'Comuna 4 - Centro', '+57 310 889 4433', '573108894433', 4.9, 195, '25-35 min', 3000, 'Vista al Río', 'bestseller', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Cortes gruesos de res al carbón de leña aromatizada con romero, acompañados de papas al vapor con hogao caliente y arepa de choclo.', 'Bife de Chorizo al Carbón con Arepa de Choclo', 34000, 'Corte Premium', 'asados', 'comidas_rapidas', 1, NOW()),

-- Otras Comidas Rápidas: Pollo Crispy & Tex-Mex (3 sitios)
('crunchy-chicken-neiva', 'Crunchy Chicken & Tenders Neiva', 'Cra 7 #12-40, Centro', 'Comuna 4 - Centro', '+57 318 990 1234', '573189901234', 4.8, 165, '20-30 min', 3000, 'Extra Crujiente', 'speed', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop&q=80', 'Pollo estilo broaster y tenders marinados con 11 especias secretas, corteza ultra crujiente y salsas especiales de miel mostaza y BBQ.', 'Balde 8 Presas Pollo Broaster Extra Crujiente', 34900, 'Balde Familiar', 'pollo', 'comidas_rapidas', 1, NOW()),

('taqueria-el-mariachi-opita', 'Taquería El Mariachi Opita', 'Calle 11 #5-18, Centro', 'Comuna 4 - Centro', '+57 312 445 7799', '573124457799', 4.9, 180, '15-25 min', 2800, 'Tacos & Birria', 'bestseller', 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&auto=format&fit=crop&q=80', 'Auténticos tacos mexicanos en tortilla de maíz nixtamalizado: pastor con piña asada, carnitas michoacanas y birria de res con consomé.', 'Trío de Tacos al Pastor con Piña & Birria', 21900, 'Sabor Mexicano', 'mexicana', 'comidas_rapidas', 1, NOW()),

('burritos-nachos-texmex', 'Burritos & Nachos Tex-Mex Neiva', 'Av. 26 #35-10, Las Granjas', 'Comuna 2 - Las Granjas', '+57 314 667 8822', '573146678822', 4.8, 150, '20-30 min', 3000, 'Burrito 1 Libra', 'speed', 'https://images.unsplash.com/photo-1584031036380-3fb6f2d51880?w=600&auto=format&fit=crop&q=80', 'Burritos gigantes de 1 libra envueltos en tortilla de trigo doradita, rellenos de carne asada, frijol refrito, queso fundido y pico de gallo.', 'Burrito Monumental 1 Libra de Carne Asada', 22900, '1 Libra Completa', 'mexicana', 'comidas_rapidas', 1, NOW());

-- 3. Asignar Platos a los Nuevos Restaurantes
SET @r29 = (SELECT id FROM `restaurants` WHERE slug = 'pizzeria-napoles-neiva' LIMIT 1);
SET @r30 = (SELECT id FROM `restaurants` WHERE slug = 'pizza-nostra-huila' LIMIT 1);
SET @r31 = (SELECT id FROM `restaurants` WHERE slug = 'pizzas-el-padrino-neiva' LIMIT 1);
SET @r32 = (SELECT id FROM `restaurants` WHERE slug = 'smash-burger-lab' LIMIT 1);
SET @r33 = (SELECT id FROM `restaurants` WHERE slug = 'black-angus-gourmet' LIMIT 1);
SET @r34 = (SELECT id FROM `restaurants` WHERE slug = 'toro-bravo-burger-ribs' LIMIT 1);
SET @r35 = (SELECT id FROM `restaurants` WHERE slug = 'parrilla-la-huilense' LIMIT 1);
SET @r36 = (SELECT id FROM `restaurants` WHERE slug = 'fogata-llanera-neiva' LIMIT 1);
SET @r37 = (SELECT id FROM `restaurants` WHERE slug = 'asados-malecon-rio' LIMIT 1);
SET @r38 = (SELECT id FROM `restaurants` WHERE slug = 'crunchy-chicken-neiva' LIMIT 1);
SET @r39 = (SELECT id FROM `restaurants` WHERE slug = 'taqueria-el-mariachi-opita' LIMIT 1);
SET @r40 = (SELECT id FROM `restaurants` WHERE slug = 'burritos-nachos-texmex' LIMIT 1);

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`, `is_available`, `created_at`) VALUES
-- Pizzería Nápoles (2 platos)
(@r29, 'Pizza Napolitana Diávola al Horno de Leña', 32000, 'Masa fermentada 48 horas con bordes aireados y quemaditos al horno de leña, salami picante italiano, pomodoro San Marzano y queso mozzarella fior di latte.', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80', 'Horno de Leña, Masa Madre 48h, Salami Picante', 'pizzas', 'salado', 1, 1, 1, NOW()),
(@r29, 'Pizza Cuatro Quesos & Miel Trufada', 34000, 'Combinación de mozzarella fior di latte, queso gorgonzola, parmesano reggiano madurado y queso provolone, terminada con un hilo de miel trufada.', 'https://images.unsplash.com/photo-1573821663912-569905455b1c?w=600&auto=format&fit=crop&q=80', '4 Quesos, Miel Trufada, Gourmet', 'pizzas', 'salado', 0, 1, 1, NOW()),

-- Pizza Nostra (2 platos)
(@r30, 'Pizza Deep Dish Chicago Style Huilense', 36900, 'Masa gruesa horneada en molde hondo rellena con 400g de queso mozzarella derretido, trozos de tocineta ahumada, pepperoni y salsa de tomate casera al tope.', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=600&auto=format&fit=crop&q=80', 'Deep Dish, 400g Queso, Chicago Style', 'pizzas', 'salado', 1, 1, 1, NOW()),
(@r30, 'Pizza Artesanal Serrana con Jamón & Rúcula', 29000, 'Fina masa crocante con base de salsa blanca cremosa, jamón curado tipo serrano, hojas frescas de rúcula, lascas de parmesano y reducción balsámica.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&auto=format&fit=crop&q=80', 'Jamón Serrano, Rúcula, Parmesano', 'pizzas', 'salado', 0, 1, 1, NOW()),

-- Pizzas El Padrino (2 platos)
(@r31, 'Pizza Familiar Campesina con Tocineta & Maíz', 35000, 'Pizza gigante de 8 porciones con abundante pollo desmechado, tocineta crujiente, maíz tierno dulce, doble queso y borde horneado con queso bocadillo.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&auto=format&fit=crop&q=80', 'Familiar 8 Porciones, Borde Queso, Tocineta', 'pizzas', 'salado', 1, 1, 1, NOW()),
(@r31, 'Pan de Ajo Gratinado con Doble Queso', 12000, 'Pan baguette tostado al horno con mantequilla de ajo asado, perejil fresco y una cubierta gratinada de queso mozzarella dorado.', 'https://images.unsplash.com/photo-1573821663912-569905455b1c?w=600&auto=format&fit=crop&q=80', 'Pan de Ajo, Gratinado, Entrada', 'pizzas', 'salado', 0, 1, 1, NOW()),

-- Smash Burger Lab (2 platos)
(@r32, 'Triple Smash Burger con Costra Caramelizada', 27900, 'Tres discos de carne angus de 80g aplastados a máxima plancha con costra ultra crujiente, triple queso cheddar americano y salsa especial de la casa.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Triple Smash 240g, Costra Crujiente, Cheddar', 'burgers', 'salado', 1, 1, 1, NOW()),
(@r32, 'Smash Bacon Monster con Salsa Shack', 26500, 'Doble disco smash de carne angus, 4 tiras de tocineta ahumada crujiente, pepinillos dulces encurtidos y salsa cremosa Shack.', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Doble Carne, 4 Tiras Tocineta, Shack Sauce', 'burgers', 'salado', 0, 1, 1, NOW()),

-- Black Angus Gourmet Burger (2 platos)
(@r33, 'Hamburguesa Angus 220g Cebolla al Ron', 31500, 'Carne de res 100% Black Angus 220g a la parrilla, queso gouda fundido, cebollas caramelizadas en reducción de ron y mayonesa de trufa.', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&auto=format&fit=crop&q=80', 'Angus 220g, Queso Gouda, Cebolla al Ron', 'burgers', 'salado', 1, 1, 1, NOW()),
(@r33, 'Hamburguesa Crunch BBQ con Aros de Cebolla', 28000, 'Carne angus 180g, tocineta crujiente, queso cheddar blanco, salsa BBQ ahumada artesanal y dos aros de cebolla crocantes adentro.', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&auto=format&fit=crop&q=80', 'Aros de Cebolla, BBQ Ahumada, Cheddar Blanco', 'burgers', 'salado', 0, 1, 1, NOW()),

-- Toro Bravo Burger & Ribs (2 platos)
(@r34, 'Hamburguesa Toro Salvaje con Pulled Pork', 29900, 'Carne de res parrillera 180g coronada con generosa porción de pulled pork ahumado 8 horas, queso cheddar derretido y salsa BBQ de whisky.', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&auto=format&fit=crop&q=80', 'Pulled Pork 8 Horas, BBQ Whisky, Cheddar', 'burgers', 'salado', 1, 1, 1, NOW()),
(@r34, 'Alitas Ahumadas al Carbón con Whisky BBQ', 24000, '8 alitas enteras selladas al carbón de leña y glaseadas con salsa barbacoa de panela y whisky, con bastones de apio y salsa tártara.', 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=600&auto=format&fit=crop&q=80', '8 Alitas, Ahumadas al Carbón, BBQ Whisky', 'burgers', 'salado', 0, 1, 1, NOW()),

-- Parrillada La Huilense (2 platos)
(@r35, 'Gran Parrillada Huilense para 3 Personas', 58000, 'Bandeja monumental con 300g de churrasco de res, 300g de pechuga parrillera, costillas de cerdo, chunchullo crocante, papas saladas, arepas y chimichurri.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Para 3 Personas, Churrasco, Costillas, Chunchullo', 'asados', 'salado', 1, 1, 1, NOW()),
(@r35, 'Churrasco de Res 350g al Carbón con Chimichurri', 33500, 'Corte grueso de lomo de res 350g sellado a fuego vivo al carbón, con papas criollas doradas y chimichurri artesanal de ajo y perejil.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop&q=80', 'Churrasco 350g, Carbón de Leña, Chimichurri', 'asados', 'salado', 0, 1, 1, NOW()),

-- Fogata Llanera Neiva (2 platos)
(@r36, 'Carne a la Llanera en Varas de Leña (400g)', 32900, 'Auténtica carne a la llanera asada lentamente en varas verticales sobre brasa de madera de guayabo, servida con plátano maduro con queso y yuca.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop&q=80', '400g Carne Llanera, Varas de Leña, Plátano Queso', 'asados', 'salado', 1, 1, 1, NOW()),
(@r36, 'Costillitas de Cerdo a la Brasa con Plátano Asado', 29500, 'Costillas de cerdo tiernas marinadas con cerveza y especias huilenses, asadas a fuego lento y servidas con plátano maduro asado con queso.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Costillas Cerdo, Brasa Lenta, Plátano Asado', 'asados', 'salado', 0, 1, 1, NOW()),

-- Asados Del Malecón del Río (2 platos)
(@r37, 'Bife de Chorizo al Carbón con Arepa de Choclo', 34000, '350g de corte bife de chorizo jugoso con su borde crocante de grasa dorada al carbón, servido con arepa dulce de choclo y ensalada fresca.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 'Bife 350g, Arepa Choclo, Brasa Viva', 'asados', 'salado', 1, 1, 1, NOW()),
(@r37, 'Punta de Anca Madurada al Carbón (350g)', 35500, 'Corte premium de punta de anca madurada durante 21 días, sazonada con sal marina gruesa y asada al término exacto de su preferencia.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=600&auto=format&fit=crop&q=80', 'Punta de Anca 350g, Madurada 21 Días, Sal Marina', 'asados', 'salado', 0, 1, 1, NOW()),

-- Crunchy Chicken & Tenders (2 platos)
(@r38, 'Balde 8 Presas Pollo Broaster Extra Crujiente', 34900, '8 presas grandes de pollo fresco marinado durante 12 horas, apanado con receta especial y frito a presión para máxima terneza y crocancia, con papas francesas.', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop&q=80', 'Balde 8 Presas, Broaster Crujiente, Papas', 'pollo', 'salado', 1, 1, 1, NOW()),
(@r38, 'Combo Tenders de Pollo Crunch con Papas Cascos', 19500, '6 tiras de pechuga de pollo apanadas extra crunch con salsa tártara casera, salsa BBQ de miel y porción de papas cascos sazonadas.', 'https://images.unsplash.com/photo-1562967914-608f82629710?w=600&auto=format&fit=crop&q=80', '6 Tenders Pechuga, Extra Crunch, 2 Salsas', 'pollo', 'salado', 0, 1, 1, NOW()),

-- Taquería El Mariachi Opita (2 platos)
(@r39, 'Trío de Tacos al Pastor con Piña & Birria', 21900, '3 tacos en tortilla de maíz suave: 2 al pastor con piña asada, cebollita y cilantro, y 1 taco de birria con queso fundido y consomé caliente para sopear.', 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&auto=format&fit=crop&q=80', '3 Tacos, Pastor con Piña, Birria con Consomé', 'mexicana', 'salado', 1, 1, 1, NOW()),
(@r39, 'Quesadilla Gigante de Birria con Queso Oaxaca', 23500, 'Tortilla de trigo de 30cm doblada y dorada a la plancha rellena de carne de birria jugosa, abundante queso fundido, crema agria y guacamole.', 'https://images.unsplash.com/photo-1618040996337-56904b7850b9?w=600&auto=format&fit=crop&q=80', 'Quesadilla 30cm, Queso Fundido, Birria Jugosa', 'mexicana', 'salado', 0, 1, 1, NOW()),

-- Burritos & Nachos Tex-Mex Neiva (2 platos)
(@r40, 'Burrito Monumental 1 Libra de Carne Asada', 22900, 'Burrito gigante de 450g relleno con carne de res asada en tiras, arroz a la mexicana, frijoles refritos, queso fundido, guacamole y crema fresca.', 'https://images.unsplash.com/photo-1584031036380-3fb6f2d51880?w=600&auto=format&fit=crop&q=80', 'Burrito 1 Libra (450g), Carne Asada, Guacamole', 'mexicana', 'salado', 1, 1, 1, NOW()),
(@r40, 'Nachos Supremos con Guacamole & Queso Fundido', 24500, 'Montaña de totopos de maíz crujientes cubiertos con chili con carne, salsa de queso cheddar caliente, jalapeños, pico de gallo y sour cream.', 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=600&auto=format&fit=crop&q=80', 'Nachos Supremos, Queso Caliente, Chili con Carne', 'mexicana', 'salado', 0, 1, 1, NOW());
