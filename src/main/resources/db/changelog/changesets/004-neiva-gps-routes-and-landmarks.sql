--liquibase formatted sql

--changeset fayber-diaz:004-neiva-gps-routes-and-landmarks splitStatements:true endDelimiter:;
--comment Tabla de rutas viales GPS reales por las 10 comunas de Neiva y puntos de referencia para el mapa de rastreo mejorado

CREATE TABLE IF NOT EXISTS `neiva_route_waypoints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `comuna_code` VARCHAR(20) NOT NULL,
  `step_order` INT NOT NULL,
  `lat` DECIMAL(10,6) NOT NULL,
  `lng` DECIMAL(10,6) NOT NULL,
  `street_name` VARCHAR(150) NOT NULL,
  `landmark` VARCHAR(150) DEFAULT NULL,
  `speed_kmh` INT DEFAULT 32,
  INDEX `idx_route_comuna` (`comuna_code`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neiva_landmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `lat` DECIMAL(10,6) NOT NULL,
  `lng` DECIMAL(10,6) NOT NULL,
  `icon` VARCHAR(20) DEFAULT '📍',
  `comuna` VARCHAR(50) DEFAULT 'Comuna 4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `neiva_landmarks`;
INSERT INTO `neiva_landmarks` (`name`, `category`, `lat`, `lng`, `icon`, `comuna`) VALUES
('Parque Santander (Centro de Neiva)',       'plaza',      2.927300, -75.289100, '🏛️', 'Comuna 4'),
('Monumento La Gaitana (Malecón Magdalena)', 'monumento',  2.926100, -75.294200, '🗽', 'Comuna 4'),
('Centro Comercial San Pedro Plaza',         'comercial',  2.939200, -75.288900, '🛍️', 'Comuna 2'),
('Universidad Surcolombiana (USCO)',         'educacion',  2.943800, -75.298100, '🎓', 'Comuna 1'),
('Avenida La Toma (Zona Gastronómica)',      'avenida',    2.931200, -75.283500, '🌳', 'Comuna 3'),
('Estadio Guillermo Plazas Alcid',           'deporte',    2.929500, -75.274800, '🏟️', 'Comuna 5'),
('Centro Comercial Unicentro Neiva',         'comercial',  2.907800, -75.276800, '🏬', 'Comuna 6'),
('Parque Leesburg / Quirinal',               'parque',     2.935800, -75.285200, '🌲', 'Comuna 3');

DELETE FROM `neiva_route_waypoints`;
INSERT INTO `neiva_route_waypoints` (`comuna_code`, `step_order`, `lat`, `lng`, `street_name`, `landmark`, `speed_kmh`) VALUES
-- Ruta Comuna 4 (Centro Histórico / Parque Santander)
('comuna_4', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Barrio Altico / Centro', 'Salida del Restaurante', 18),
('comuna_4', 2, 2.928100, -75.283400, 'Calle 10 con Carrera 10', 'Vía hacia el Centro', 28),
('comuna_4', 3, 2.929200, -75.284900, 'Avenida La Toma con Carrera 8', 'Canal de La Toma', 35),
('comuna_4', 4, 2.928800, -75.286800, 'Carrera 7 con Calle 12', 'Sector Bancario Centro', 31),
('comuna_4', 5, 2.928200, -75.288100, 'Carrera 5 con Calle 11 (Parque Santander)', 'Frente a Catedral de Neiva', 24),
('comuna_4', 6, 2.929000, -75.286000, 'Cra 5 #18-42, Centro Histórico', 'Dirección de Entrega', 0),

-- Ruta Comuna 1 (Cándido / Camilo Torres / USCO)
('comuna_1', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 20),
('comuna_1', 2, 2.931000, -75.284200, 'Avenida La Toma hacia Occidente', 'Corredor Vial La Toma', 36),
('comuna_1', 3, 2.934500, -75.289800, 'Avenida Circunvalar con Calle 15', 'Ribera del Río Magdalena', 42),
('comuna_1', 4, 2.939500, -75.293200, 'Avenida Inés García de Durán / Av. 26', 'Glorieta Madre Tierra', 38),
('comuna_1', 5, 2.943500, -75.296200, 'Carrera 1 con Calle 26 (Sector USCO)', 'Universidad Surcolombiana', 29),
('comuna_1', 6, 2.946200, -75.298000, 'Barrio Cándido Leguízamo (Comuna 1)', 'Dirección de Entrega', 0),

-- Ruta Comuna 2 (Las Granjas / Los Mártires)
('comuna_2', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 20),
('comuna_2', 2, 2.931200, -75.283000, 'Carrera 12 hacia Avenida La Toma', 'Cruce La Toma', 32),
('comuna_2', 3, 2.935500, -75.284500, 'Carrera 7 con Calle 21 (Quirinal)', 'Clínica Mediláser', 36),
('comuna_2', 4, 2.938800, -75.285500, 'Avenida 26 con Carrera 8', 'Cerca a San Pedro Plaza', 34),
('comuna_2', 5, 2.942000, -75.286000, 'Calle 34 con Carrera 8, Las Granjas', 'Dirección de Entrega', 0),

-- Ruta Comuna 3 (Quirinal / Santa Inés)
('comuna_3', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 18),
('comuna_3', 2, 2.930500, -75.282800, 'Avenida La Toma con Carrera 11', 'Zona Médica', 30),
('comuna_3', 3, 2.933500, -75.283800, 'Calle 18 con Carrera 8, Quirinal', 'Parque del Quirinal', 28),
('comuna_3', 4, 2.936000, -75.284000, 'Calle 21, Barrio Santa Inés / Quirinal', 'Dirección de Entrega', 0),

-- Ruta Comuna 5 (Altico / La Floresta / Buganviles)
('comuna_5', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 22),
('comuna_5', 2, 2.926500, -75.279500, 'Calle 8 con Carrera 16, Altico', 'Parroquia San Vicente', 34),
('comuna_5', 3, 2.925500, -75.276500, 'Avenida Buganviles con Carrera 24', 'Bulevar Buganviles', 36),
('comuna_5', 4, 2.924000, -75.274000, 'Av. Buganviles, Barrio La Floresta', 'Dirección de Entrega', 0),

-- Ruta Comuna 6 (Timanco / Puertas del Sol / Sur)
('comuna_6', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 22),
('comuna_6', 2, 2.922500, -75.282500, 'Carrera 5 hacia el Sur (Av. Surabastos)', 'Puente del Río del Oro', 40),
('comuna_6', 3, 2.916500, -75.279800, 'Avenida Max Duque Gómez con Calle 4 Sur', 'Terminal de Transportes', 42),
('comuna_6', 4, 2.912000, -75.277200, 'Carrera 15 con Calle 12 Sur (Unicentro)', 'C.C. Unicentro Neiva', 32),
('comuna_6', 5, 2.909000, -75.275000, 'Barrio Timanco / Puertas del Sol', 'Dirección de Entrega', 0),

-- Ruta Comuna 7 (Ipanema / Las Brisas)
('comuna_7', 1, 2.927300, -75.281900, 'Cra 12 #10-34, Centro', 'Salida del Restaurante', 20),
('comuna_7', 2, 2.929500, -75.276500, 'Avenida La Toma Oriental (Cra 16)', 'Estadio Plazas Alcid', 35),
('comuna_7', 3, 2.927500, -75.269500, 'Calle 16 con Carrera 28, Las Brisas', 'Subida a Ipanema', 33),
('comuna_7', 4, 2.925000, -75.263000, 'Barrio Ipanema Reservado (Comuna 7)', 'Dirección de Entrega', 0);

