-- Migración para incorporar Dulces, Postres, Helados y su Personalización en Antojo Virtual Neiva

-- 1. Insertar Restaurantes Dulces
INSERT INTO `restaurants` (`slug`, `name`, `address`, `comuna`, `phone`, `whatsapp`, `rating`, `reviews_count`, `delivery_time`, `delivery_fee`, `badge`, `badge_type`, `image`, `description`, `star_dish`, `star_price`, `star_tag`, `category`, `site_type`, `is_open`, `created_at`) VALUES
('dolce-gelato-neiva', 'Dolce Gelato • Heladería Artesanal', 'Cra 6 #14-22, La Toma, Neiva', 'Comuna 4 - Centro', '+57 315 789 4433', '+57 315 789 4433', 4.9, 185, '15-25 min', 3000, 'Gelato Italiano', 'bestseller', 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=600&auto=format&fit=crop&q=80', 'Auténtico gelato italiano artesanal elaborado a diario con frutas frescas del Huila, chocolate belga al 70% y conos horneados al momento.', 'Copa Suprema Dolce Gelato (3 Bolas)', 18500, 'Más Pedido en Neiva', 'helados', 'dulces', 1, NOW()),

('tentacion-dulce-reposteria', 'Tentación Dulce • Repostería & Café', 'Cl 18 #7-45, Barrio Ipanema, Neiva', 'Comuna 7 - Ipanema', '+57 318 642 1199', '+57 318 642 1199', 4.9, 210, '20-30 min', 3500, 'Repostería Gourmet', 'verified', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600&auto=format&fit=crop&q=80', 'Cheesecakes estilo New York, tortas húmedas de chocolate belga, brownies melcochudos y postres con frutos de la región huilense.', 'Cheesecake Artesanal de Frutos Rojos Huilenses', 16900, 'Chef Pastelero', 'postres', 'dulces', 1, NOW()),

('waffle-crepe-station', 'Waffle & Crepe Station Neiva', 'Av. Buganviles Cra 39 #19-15, Neiva', 'Comuna 5 - Buganviles', '+57 312 884 9010', '+57 312 884 9010', 4.8, 142, '20-35 min', 3500, 'Waffles Calientes', 'speed', 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80', 'Waffles burbuja crocantes, crepes dulces parisinos bañados en Nutella caliente, frutas frescas recién cortadas y bolas de helado artesanal.', 'Bubble Waffle Explosión Nutella & Helado', 21900, 'Favorito Jóvenes', 'waffles', 'dulces', 1, NOW()),

('monster-shakes-neiva', 'Monster Shakes & Bebidas Dulces', 'Calle 8 #12-40, Altico, Neiva', 'Comuna 3 - El Altico', '+57 311 502 8377', '+57 311 502 8377', 4.8, 98, '15-25 min', 3000, 'Malteadas Monstruo', 'verified', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Malteadas monstruo super cargadas con borde escarchado en Nutella y maní, galleta Oreo gigante, barquillos, crema chantilly y lluvia de chocolates.', 'Monster Shake Choco-Oreo Suprema', 17500, 'Sensación Instagram', 'malteadas', 'dulces', 1, NOW());

-- Obtener IDs de los nuevos restaurantes
SET @id_gelato = (SELECT id FROM `restaurants` WHERE slug = 'dolce-gelato-neiva' LIMIT 1);
SET @id_reposteria = (SELECT id FROM `restaurants` WHERE slug = 'tentacion-dulce-reposteria' LIMIT 1);
SET @id_waffles = (SELECT id FROM `restaurants` WHERE slug = 'waffle-crepe-station' LIMIT 1);
SET @id_shakes = (SELECT id FROM `restaurants` WHERE slug = 'monster-shakes-neiva' LIMIT 1);

-- 2. Insertar Platos Dulces
INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`, `is_available`, `created_at`) VALUES
(@id_gelato, 'Copa Suprema Dolce Gelato (3 Bolas)', 18500, 'Tres generosas bolas de helado artesanal a tu elección, con base de canasta crocante o vaso, bañado en salsa tibia y toppings abundantes.', 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=600&auto=format&fit=crop&q=80', 'Gelato Artesanal, 3 Bolas, Frutas del Huila, Gourmet', 'helados', 'dulce', 1, 1, 1, NOW()),
(@id_gelato, 'Cono Waffle Doble Sabor Premium', 12000, 'Dos bolas de gelato italiano en cono artesanal horneado con esencia de vainilla y borde cubierto de chocolate o arequipe.', 'https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=600&auto=format&fit=crop&q=80', 'Cono Waffle Crocante, 2 Bolas, Doble Sabor', 'helados', 'dulce', 0, 1, 1, NOW()),
(@id_gelato, 'Banana Split Especial San Agustín', 20500, 'Tradicional y monumental banana split con 3 sabores de helado, banano fresco, crema chantilly, fresas y cereza marrasquino.', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=600&auto=format&fit=crop&q=80', 'Banana Split, 3 Sabores, Chantilly, Cerezas', 'helados', 'dulce', 1, 0, 1, NOW()),

(@id_reposteria, 'Cheesecake Artesanal de Frutos Rojos Huilenses', 16900, 'Cremoso cheesecake horneado sobre base crocante de galleta mantequilla, cubierto con coulis artesanal de moras y fresas del Huila.', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600&auto=format&fit=crop&q=80', 'Cheesecake, Frutos Rojos, Coulis Artesanal, Más Vendido', 'postres', 'dulce', 1, 1, 1, NOW()),
(@id_reposteria, 'Torta Melcochuda de Chocolate Belga & Arequipe', 15500, 'Porción generosa de torta de chocolate al 70%, centro melcochudo con hilos de arequipe y opción de bola de helado de vainilla.', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80', 'Chocolate Belga 70%, Centro Melcochudo, Arequipe', 'postres', 'dulce', 1, 1, 1, NOW()),
(@id_reposteria, 'Postre Tres Leches Huilense con Canela & Arequipe', 13500, 'Bizcocho esponjoso bañado en infusión de tres leches frescas, decorado con merengue suizo tostado y canela en polvo.', 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=600&auto=format&fit=crop&q=80', 'Tres Leches Tradicional, Arequipe Colombiano, Suave', 'postres', 'dulce', 0, 1, 1, NOW()),

(@id_waffles, 'Bubble Waffle Explosión Nutella & Helado', 21900, 'Waffle burbuja recién horneado, relleno con fresas frescas, banano, Nutella original caliente, bola de helado artesanal y barquillo.', 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80', 'Bubble Waffle, Nutella Original, Fresas Frescas, Favorito', 'waffles', 'dulce', 1, 1, 1, NOW()),
(@id_waffles, 'Crepe Francés de Arequipe, Queso & Almendras', 17000, 'Fino crepe artesanal relleno de abundante arequipe colombiano, queso campesino fundido y lluvia de almendras tostadas fileteadas.', 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80', 'Crepe Parisino, Queso Fundido, Almendras Tostadas', 'waffles', 'dulce', 1, 1, 1, NOW()),

(@id_shakes, 'Monster Shake Choco-Oreo Suprema', 17500, 'Malteada ultra cremosa a base de helado de chocolate y vainilla, borde escarchado con Nutella y maní, galleta Oreo y crema chantilly.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80', 'Monster Shake, Galleta Oreo, Nutella, Chantilly', 'malteadas', 'dulce', 1, 1, 1, NOW()),
(@id_shakes, 'Malteada Frutos Amarillos & Maracuyá Silvestre', 15900, 'Refrescante y cremosa malteada elaborada con pulpa pura de maracuyá huilense, helado artesanal y salsa de frutos amarillos.', 'https://images.unsplash.com/photo-1553787499-6f9133860278?w=600&auto=format&fit=crop&q=80', 'Maracuyá Huilense, Refrescante, Helado Artesanal', 'malteadas', 'dulce', 0, 1, 1, NOW());

-- 3. Insertar Opciones de Personalización Dulces
INSERT INTO `item_options` (`category`, `item_type`, `name`, `price`, `is_default`, `icon`, `is_active`) VALUES
-- Sabores de Helado / Base
('sabor_helado', 'dulce', 'Vainilla Francesa Bourbon (Artesanal)', 0, 1, 'icecream', 1),
('sabor_helado', 'dulce', 'Chocolate Belga Intenso 70%', 0, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Frutos Rojos Silvestres Huilenses', 0, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Arequipe Colombiano Caramelizado', 0, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Maracuyá Tropical del Huila', 0, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Pistacho Siciliano Tostado Real', 3000, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Mora Silvestre en Leche Fresca', 0, 0, 'icecream', 1),
('sabor_helado', 'dulce', 'Brownie Fudge & Helado Vainilla', 1500, 0, 'icecream', 1),

-- Base de Presentación Dulce
('base_dulce', 'dulce', 'Vaso Ecológico Especial para Postre', 0, 1, 'local_cafe', 1),
('base_dulce', 'dulce', 'Cono Waffle Crocante de Vainilla y Canela', 1500, 0, 'bakery_dining', 1),
('base_dulce', 'dulce', 'Canasta de Galleta Mantequilla Horneada', 2500, 0, 'cookie', 1),
('base_dulce', 'dulce', 'Masa de Waffle Tradicional Dorada', 0, 0, 'waffle', 1),
('base_dulce', 'dulce', 'Masa Red Velvet con Toque de Vainilla', 2000, 0, 'cake', 1),

-- Siropes y Salsas Dulces
('sirope_dulce', 'dulce', 'Nutella Original Caliente y Fundida', 0, 1, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Arequipe Artesanal Tibio de la Casa', 0, 1, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Coulis Casero de Frutos Rojos y Moras', 0, 0, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Caramelo Salado (Salted Caramel Gourmet)', 0, 0, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Chocolate Blanco Derretido Suave', 0, 0, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Miel de Maple Pura de Canadá', 0, 0, 'water_drop', 1),
('sirope_dulce', 'dulce', 'Salsa Exótica de Maracuyá Silvestre', 0, 0, 'water_drop', 1),

-- Toppings Dulces y Crujientes
('topping_dulce', 'dulce', 'Galleta Oreo Original Triturada', 2500, 0, 'cookie', 1),
('topping_dulce', 'dulce', 'Almendras Tostadas Fileteadas', 3000, 0, 'grain', 1),
('topping_dulce', 'dulce', 'Chispas de Chocolate Belga Semi-Amargo', 2000, 0, 'scatter_plot', 1),
('topping_dulce', 'dulce', 'Fresas Frescas Picadas al Instante', 3500, 0, 'nutrition', 1),
('topping_dulce', 'dulce', 'Rodajas de Banano Fresco Criollo', 1500, 0, 'nutrition', 1),
('topping_dulce', 'dulce', 'Crema Chantilly Casera Ultra Espesa', 2500, 0, 'cloud', 1),
('topping_dulce', 'dulce', 'Barquillos Crocantes Rellenos de Chocolate', 2000, 0, 'bakery_dining', 1),
('topping_dulce', 'dulce', 'Lluvia de M&Ms de Chocolate Crujiente', 2500, 0, 'stars', 1),
('topping_dulce', 'dulce', 'Gomitas Dulces & Ácidas Frutales', 2000, 0, 'bubble_chart', 1),
('topping_dulce', 'dulce', 'Bola Adicional de Gelato a Elección', 4500, 0, 'icecream', 1),

-- Nivel de Dulzura
('dulzura', 'dulce', 'Dulzura Tradicional de la Casa (Equilibrado Perfecto)', 0, 1, 'check_circle', 1),
('dulzura', 'dulce', 'Dulzura Ligera / Bajo en Azúcar', 0, 0, 'check_circle', 1),
('dulzura', 'dulce', 'Toque Extra Dulce para Fanáticos', 0, 0, 'check_circle', 1);
