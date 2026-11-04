--liquibase formatted sql

--changeset fayber-diaz:003-seed-fastfood-and-desserts splitStatements:true endDelimiter:;
--comment Ampliación masiva del catálogo de productos: Comidas Rápidas (Hamburguesas, Perros, Salchipapas, Pizzas, Asados, Pollo, Mexicana) y Dulces/Postres (Helados, Waffles, Crepes, Malteadas, Tortas, Obleas, Fresas con Crema)

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 1 AS restaurant_id, 'Hamburguesa Doble Tocineta & Queso Paipa' AS name, 29500 AS price,
         'Doble carne de res seleccionada (320g) a la brasa, doble queso Paipa madurado fundido, mermelada de tocineta ahumada en panela, aros de cebolla crunch y pan brioche dorado.' AS description,
         'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=600&auto=format&fit=crop&q=80' AS image,
         'Doble Carne 320g, Queso Paipa, Mermelada Tocineta' AS tags, 'burgers' AS category, 'salado' AS dish_type, 1 AS is_featured, 1 AS is_popular
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Hamburguesa Doble Tocineta & Queso Paipa');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 2, 'Salchipapa Monster King con Costilla BBQ', 36900,
         'Cama de papas francesas y criollas crocantes con costillitas de cerdo deshuesadas en BBQ de guayaba, salchicha suiza, tocineta, maíz tierno, huevos de codorniz y gratinado triple de mozzarella.',
         'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=600&auto=format&fit=crop&q=80',
         'Costilla BBQ, Salchicha Suiza, Gratinada', 'salchipapas', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Salchipapa Monster King con Costilla BBQ');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 3, 'Patacón Relleno de Camarones & Pollo al Ajillo', 24900,
         'Canasta de plátano verde huilense frita al momento, rellena de pechuga grillada, camarones salteados en mantequilla de ajo y cilantro, guacamole fresco y queso costeño.',
         'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80',
         'Mar y Tierra, Patacón Canasta, Ajillo', 'arepas', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Patacón Relleno de Camarones & Pollo al Ajillo');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 4, 'Perro Caliente Mexicano con Chili & Guacamole', 18900,
         'Salchicha americana tipo Frankfurt a la parrilla, chili con carne casero, pico de gallo, guacamole rústico, nachos triturados, jalapeños y queso cheddar líquido.',
         'https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=600&auto=format&fit=crop&q=80',
         'Estilo Tex-Mex, Chili con Carne, Cheddar', 'perros', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Perro Caliente Mexicano con Chili & Guacamole');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 5, 'Mazorcada Gratinada con Chicharrón & Chorizo', 24500,
         'Cazuela hirviente de maíz tierno dulce desgranado en salsa bechamel casera, trozos de chicharrón carnudo, chorizo santarrosano, pollo desmechado y costra dorada de queso.',
         'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=600&auto=format&fit=crop&q=80',
         'Cazuela Gratinada, Chicharrón, Chorizo', 'desgranados', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Mazorcada Gratinada con Chicharrón & Chorizo');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 7, 'Sundae Volcánico de Brownie, Helado & Nutella', 19500,
         'Copa de cristal con dos bolas de gelato italiano (vainilla bourbon y chocolate belga), cubos de brownie caliente recién horneado, lluvia de avellanas, crema chantilly y cascada de Nutella.',
         'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=600&auto=format&fit=crop&q=80',
         'Sundae Caliente-Frío, Brownie, Nutella', 'helados', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Sundae Volcánico de Brownie, Helado & Nutella');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 7, 'Copa de Gelato Maracuyá & Mango Biche con Lechera', 16500,
         'Combinación cítrica y cremosa para el calor de Neiva: helado artesanal de maracuyá huilense y sorbete de mango biche con leche condensada, gomitas y galleta.',
         'https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=600&auto=format&fit=crop&q=80',
         'Cítrico & Dulce, Maracuyá, Leche Condensada', 'helados', 'dulce', 0, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Copa de Gelato Maracuyá & Mango Biche con Lechera');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 8, 'Cheesecake de Maracuyá & Chocolate Blanco', 17500,
         'Cheesecake cremoso sobre galleta crocante de macadamia, coronado con espejo brillante de maracuyá natural del Huila y virutas de chocolate blanco belga.',
         'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600&auto=format&fit=crop&q=80',
         'Maracuyá Huilense, Chocolate Blanco, Cremoso', 'postres', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Cheesecake de Maracuyá & Chocolate Blanco');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 8, 'Tiramisú Italiano con Café Especial de San Agustín', 18900,
         'Bizcochos savoiardi embebidos en espresso doble de café de origen San Agustín, crema suave de queso mascarpone y cacao alcalino espolvoreado.',
         'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=600&auto=format&fit=crop&q=80',
         'Café San Agustín, Mascarpone, Receta Italiana', 'postres', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Tiramisú Italiano con Café Especial de San Agustín');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 9, 'Waffle Belga Red Velvet con Fresas & Queso Crema', 22500,
         'Waffle artesanal de masa Red Velvet crujiente por fuera y esponjoso por dentro, cubierto de frosting de queso crema dulce, fresas frescas, helado de vainilla y sirope de frutos rojos.',
         'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&auto=format&fit=crop&q=80',
         'Masa Red Velvet, Frosting Queso Crema, Helado', 'waffles', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Waffle Belga Red Velvet con Fresas & Queso Crema');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 9, 'Oblea Monumental Reina del Huila (Doble Galleta)', 13900,
         'Oblea doble gigante rellena de una capa gruesa de arequipe de paila, queso campesino rallado en abundancia, crema chantilly casera, mora silvestre y lluvia de chispas de chocolate.',
         'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&auto=format&fit=crop&q=80',
         'Oblea Doble, Abundante Queso, Arequipe & Mora', 'waffles', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Oblea Monumental Reina del Huila (Doble Galleta)');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 10, 'Monster Shake Lotus Biscoff & Caramelo Salado', 19500,
         'Malteada ultra espesa de helado de vainilla y crema de galleta Lotus Biscoff, borde decorado con galleta triturada, crema batida alta, barquillos y salsa de caramelo salado.',
         'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80',
         'Lotus Biscoff, Caramelo Salado, Ed. Especial', 'malteadas', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Monster Shake Lotus Biscoff & Caramelo Salado');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 10, 'Monster Shake Fresas con Crema & Cheesecake', 18900,
         'Batido cremoso de fresas naturales con helado, coronado con una rebanada real de mini cheesecake, chantilly, fresas enteras y leche condensada.',
         'https://images.unsplash.com/photo-1577805947697-89e18249d767?w=600&auto=format&fit=crop&q=80',
         'Con Trozo de Cheesecake, Fresas Naturales', 'malteadas', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Monster Shake Fresas con Crema & Cheesecake');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 26, 'Mega Cookie New York Rellena de Pistacho & Chocolate Blanco', 14900,
         'Galleta estilo Levain Bakery de 180 gramos recién salida del horno, crocante por fuera y centro fundido de crema de pistacho siciliano y trozos de chocolate blanco.',
         'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=600&auto=format&fit=crop&q=80',
         '180g Estilo NY, Rellena de Pistacho, Tibia', 'postres', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Mega Cookie New York Rellena de Pistacho & Chocolate Blanco');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 28, 'Parfait Supremo de Fresas con Crema, Brownie & Nutella', 18500,
         'Vaso de 16oz en capas: fresas jugosas seleccionadas, crema de la casa batida, cubos de brownie melcochudo, queso campesino, Nutella tibia y lluvia de Oreo.',
         'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&auto=format&fit=crop&q=80',
         '16 Onzas, Capas de Brownie & Nutella, Fresas', 'postres', 'dulce', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Parfait Supremo de Fresas con Crema, Brownie & Nutella');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 29, 'Pizza Burrata, Prosciutto & Rúgula al Horno de Leña', 38500,
         'Masa de fermentación lenta horneada a la leña, salsa pomodoro San Marzano, queso burrata entero cremoso en el centro, jamón serrano prosciutto, tomates cherry y reducción balsámica.',
         'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80',
         'Burrata Entera, Prosciutto, Horno de Leña', 'pizzas', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Pizza Burrata, Prosciutto & Rúgula al Horno de Leña');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 32, 'Smash Burger Oklahoma Cebolla Crispy & Doble Cheddar', 26900,
         'Dos discos de carne angus aplastados sobre cama de cebolla blanca finamente laminada hasta caramelizar en la plancha, 4 lonjas de queso americano, pepinillos y salsa secreta.',
         'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
         'Estilo Oklahoma, Cebolla Planchada, 4 Quesos', 'burgers', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Smash Burger Oklahoma Cebolla Crispy & Doble Cheddar');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 35, 'Punta de Anca al Barril Ahumada (400g) con Papa Asada', 39900,
         'Corte grueso de punta de anca madurada asada lentamente al barril de acero con carbón y leña de guayabo, servida con papa en aluminio con crema agria y tocineta.',
         'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80',
         'Al Barril 400g, Leña de Guayabo, Papa Sour Cream', 'asados', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Punta de Anca al Barril Ahumada (400g) con Papa Asada');

INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `description`, `image`, `tags`, `category`, `dish_type`, `is_featured`, `is_popular`)
SELECT * FROM (
  SELECT 39, 'Quesabirrias Crujientes con Consomé Caliente (x4)', 25900,
         '4 tortillas de maíz doradas en la grasa del estofado, rellenas de res birria cocinada 6 horas y abundante queso fundido, con taza de consomé caliente con cebolla y cilantro.',
         'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&auto=format&fit=crop&q=80',
         '4 Quesabirrias, Con Consomé para Dipear, Quesudas', 'mexicana', 'salado', 1, 1
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `name` = 'Quesabirrias Crujientes con Consomé Caliente (x4)');
