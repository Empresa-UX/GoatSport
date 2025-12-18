-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 19-12-2025 a las 00:04:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `goatsport_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canchas`
--

CREATE TABLE `canchas` (
  `cancha_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hora_apertura` time DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `duracion_turno` int(11) NOT NULL DEFAULT 60,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `estado` enum('pendiente','aprobado','denegado') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `canchas`
--

INSERT INTO `canchas` (`cancha_id`, `proveedor_id`, `nombre`, `descripcion`, `tipo`, `capacidad`, `precio`, `hora_apertura`, `hora_cierre`, `duracion_turno`, `activa`, `estado`) VALUES
(206, 86, 'Bombonera Clásica 1', 'Cancha clásica con rebote parejo y muy buen mantenimiento. Ideal para partidos equilibrados.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(207, 86, 'Bombonera Clásica 2', 'Piso tradicional y paredes firmes. Excelente para entrenamientos y juego regular.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(208, 86, 'Bombonera Clásica 3', 'Cancha clásica cómoda, con red reglamentaria y buena iluminación nocturna.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(209, 86, 'Bombonera Cubierta 1', 'Cancha cubierta con iluminación uniforme y ambiente controlado. Se juega perfecto todo el año.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(210, 86, 'Bombonera Cubierta 2', 'Cubierta premium, ideal para turnos nocturnos y días de lluvia.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(211, 86, 'Bombonera Cubierta 3', 'Excelente visibilidad y rebote consistente. Recomendada para partidos intensos.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(212, 86, 'Bombonera Panorámica 1', 'Panorámica de vidrio templado con visibilidad total. Experiencia premium.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(213, 86, 'Bombonera Panorámica 2', 'Cristales impecables y sensación profesional. Ideal para torneos.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(214, 86, 'Bombonera Panorámica 3', 'Panorámica amplia, cómoda y perfecta para partidos con público.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(215, 87, 'Monumental Clásica 1', 'Clásica con piso estable y rebote controlado. Ideal para juego técnico.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(216, 87, 'Monumental Clásica 2', 'Cancha tradicional muy pareja, recomendada para dobles de ritmo alto.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(217, 87, 'Monumental Clásica 3', 'Excelente para entrenamientos: buena iluminación y paredes firmes.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(218, 87, 'Monumental Cubierta 1', 'Cubierta cómoda con iluminación LED uniforme. Perfecta para noche y lluvia.', 'cubierta', 4, 15000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(219, 87, 'Monumental Cubierta 2', 'Ambiente controlado y rebote consistente. Ideal para clases y prácticas.', 'cubierta', 4, 15000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(220, 87, 'Monumental Cubierta 3', 'Cancha cubierta premium con excelente agarre y visibilidad.', 'cubierta', 4, 15000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(221, 87, 'Monumental Panorámica 1', 'Panorámica con vidrio templado y visibilidad total. Sensación profesional.', 'panoramica', 4, 17500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(222, 87, 'Monumental Panorámica 2', 'Panorámica premium: cristales y mallas en estado impecable.', 'panoramica', 4, 17500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(223, 87, 'Monumental Panorámica 3', 'Ideal para eventos y torneos: gran experiencia para jugar y mirar.', 'panoramica', 4, 17500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(224, 88, 'Cilindro Clásica 1', 'Clásica con rebote parejo y piso estable. Excelente para partidos largos.', 'clasica', 4, 11800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(225, 88, 'Cilindro Clásica 2', 'Cancha tradicional, cómoda y bien mantenida. Ideal para juego regular.', 'clasica', 4, 11800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(226, 88, 'Cilindro Clásica 3', 'Perfecta para entrenar control: paredes firmes y buena iluminación.', 'clasica', 4, 11800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(227, 88, 'Cilindro Cubierta 1', 'Cubierta con iluminación uniforme y ambiente controlado para máximo confort.', 'cubierta', 4, 14000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(228, 88, 'Cilindro Cubierta 2', 'Ideal para nocturnos: excelente visibilidad y rebote consistente.', 'cubierta', 4, 14000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(229, 88, 'Cilindro Cubierta 3', 'Cubierta premium para prácticas y partidos competitivos.', 'cubierta', 4, 14000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(230, 88, 'Cilindro Panorámica 1', 'Panorámica de vidrio templado con gran visibilidad. Ideal para torneos.', 'panoramica', 4, 15800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(231, 88, 'Cilindro Panorámica 2', 'Experiencia premium: cristales impecables y entorno cómodo.', 'panoramica', 4, 15800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(232, 88, 'Cilindro Panorámica 3', 'Panorámica amplia y elegante, perfecta para finales y eventos.', 'panoramica', 4, 15800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(233, 89, 'Boedo Clásica 1', 'Clásica bien mantenida, rebote parejo y entorno cómodo. Ideal para dobles.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(234, 89, 'Boedo Clásica 2', 'Superficie tradicional estable, perfecta para entrenar y competir.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(235, 89, 'Boedo Clásica 3', 'Cancha clásica con iluminación nocturna y red reglamentaria.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(236, 89, 'Boedo Cubierta 1', 'Cubierta con ambiente controlado, ideal para jugar sin depender del clima.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(237, 89, 'Boedo Cubierta 2', 'Excelente visibilidad y rebote constante. Ideal para partidos intensos.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(238, 89, 'Boedo Cubierta 3', 'Cubierta premium para clases, torneos y turnos nocturnos.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(239, 89, 'Boedo Panorámica 1', 'Panorámica premium con vidrio templado y visibilidad total.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(240, 89, 'Boedo Panorámica 2', 'Ideal para torneos: cristales impecables y sensación profesional.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(241, 89, 'Boedo Panorámica 3', 'Panorámica amplia y cómoda, perfecta para eventos.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(242, 90, 'Ducó Clásica 1', 'Clásica con piso estable y rebote controlado. Ideal para juego técnico.', 'clasica', 4, 11500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(243, 90, 'Ducó Clásica 2', 'Cancha tradicional muy cómoda, recomendada para partidos parejos.', 'clasica', 4, 11500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(244, 90, 'Ducó Clásica 3', 'Perfecta para entrenamientos: buena iluminación y mantenimiento.', 'clasica', 4, 11500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(245, 90, 'Ducó Cubierta 1', 'Cubierta con iluminación LED y ambiente controlado. Ideal para noches.', 'cubierta', 4, 13800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(246, 90, 'Ducó Cubierta 2', 'Excelente para lluvia y frío: visibilidad y rebote consistentes.', 'cubierta', 4, 13800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(247, 90, 'Ducó Cubierta 3', 'Cubierta premium con gran confort para partidos intensos.', 'cubierta', 4, 13800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(248, 90, 'Ducó Panorámica 1', 'Panorámica de vidrio templado con visibilidad total. Experiencia premium.', 'panoramica', 4, 15500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(249, 90, 'Ducó Panorámica 2', 'Cristales impecables y entorno cómodo. Ideal para torneos.', 'panoramica', 4, 15500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(250, 90, 'Ducó Panorámica 3', 'Panorámica elegante para eventos y partidos con público.', 'panoramica', 4, 15500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(269, 20, 'Cancha Clásica A1', 'Cancha clásica con rebote parejo y piso estable. Ideal para partidos equilibrados.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(270, 20, 'Cancha Clásica A2', 'Superficie tradicional bien mantenida, perfecta para entrenamientos y juego regular.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(271, 20, 'Cancha Clásica A3', 'Paredes firmes y buena iluminación nocturna. Excelente para peloteos largos.', 'clasica', 4, 12000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(272, 20, 'Cancha Clásica B1', 'Cancha cómoda y estable, con red reglamentaria y entorno amplio para jugar fluido.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(273, 20, 'Cancha Clásica B2', 'Clásica de ritmo medio, ideal para partidos técnicos y dobles mixtos.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(274, 20, 'Cancha Clásica B3', 'Excelente mantenimiento y rebote consistente. Recomendada para torneos internos.', 'clasica', 4, 12500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(275, 20, 'Cancha Cubierta C1', 'Cancha cubierta con iluminación uniforme y ambiente controlado. Se juega perfecto todo el año.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(276, 20, 'Cancha Cubierta C2', 'Cubierta premium, ideal para jugar sin depender del clima y con gran visibilidad.', 'cubierta', 4, 14500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(277, 20, 'Cancha Cubierta C3', 'Ambiente cómodo y rebote consistente. Perfecta para clases y partidos nocturnos.', 'cubierta', 4, 14800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(278, 20, 'Cancha Cubierta D1', 'Cubierta con excelente agarre y paredes en muy buen estado. Ideal para juego rápido.', 'cubierta', 4, 15000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(279, 20, 'Cancha Cubierta D2', 'Iluminación LED pareja y entorno silencioso. Recomendada para partidos parejos.', 'cubierta', 4, 15000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(280, 20, 'Cancha Cubierta D3', 'Cubierta amplia y confortable, ideal para entrenamientos intensivos y torneos.', 'cubierta', 4, 15200.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(281, 20, 'Cancha Panorámica P1', 'Panorámica de vidrio templado con visibilidad total. Experiencia premium.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(282, 20, 'Cancha Panorámica P2', 'Cristales impecables y sensación profesional. Ideal para torneos y eventos.', 'panoramica', 4, 16500.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(283, 20, 'Cancha Panorámica P3', 'Panorámica amplia, cómoda y con excelente iluminación. Perfecta para finales.', 'panoramica', 4, 16800.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(284, 20, 'Cancha Panorámica Q1', 'Visibilidad total y rebote consistente. Ideal para partidos de alto ritmo.', 'panoramica', 4, 17000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(285, 20, 'Cancha Panorámica Q2', 'Panorámica premium para jugar y mirar: vidrio templado y entorno cómodo.', 'panoramica', 4, 17000.00, '08:00:00', '23:00:00', 60, 1, 'aprobado'),
(286, 20, 'Cancha Panorámica Q3', 'Panorámica elegante con gran experiencia de juego. Recomendada para eventos.', 'panoramica', 4, 17200.00, '08:00:00', '23:00:00', 60, 1, 'aprobado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_detalle`
--

CREATE TABLE `cliente_detalle` (
  `cliente_id` int(11) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `prefer_contacto` enum('whatsapp','llamada','email') DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `genero` enum('masculino','femenino','otro','prefiero_no_decir') DEFAULT NULL,
  `mano_habil` enum('derecha','izquierda') DEFAULT NULL,
  `nivel_padel` tinyint(3) UNSIGNED DEFAULT NULL,
  `posicion_pref` enum('drive','revés','mixto') DEFAULT NULL,
  `estilo_juego` set('ofensivo','defensivo','regular','globero','voleador','counter') DEFAULT NULL,
  `pala_marca` varchar(80) DEFAULT NULL,
  `pala_modelo` varchar(120) DEFAULT NULL,
  `frecuencia_juego` enum('ocasional','semanal','varias_por_semana','diaria') DEFAULT NULL,
  `horario_pref` enum('maniana','tarde','noche') DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente_detalle`
--

INSERT INTO `cliente_detalle` (`cliente_id`, `telefono`, `fecha_nacimiento`, `ciudad`, `barrio`, `prefer_contacto`, `bio`, `genero`, `mano_habil`, `nivel_padel`, `posicion_pref`, `estilo_juego`, `pala_marca`, `pala_modelo`, `frecuencia_juego`, `horario_pref`, `actualizado_en`) VALUES
(57, '1155792821', '2006-11-12', 'Buenos Aires', 'Parque Avellaneda', 'llamada', 'Soy un pibe que juega re piola.', 'masculino', 'izquierda', 5, 'revés', 'ofensivo', 'Bullpadel', 'Vertex 04', 'ocasional', 'tarde', '2025-12-14 15:55:29'),
(71, '11-4301-1020', '1992-04-18', 'Buenos Aires', 'Palermo', 'whatsapp', 'Juego padel para despejar y competir sanamente.', 'masculino', 'derecha', 6, 'drive', 'ofensivo,regular', 'Bullpadel', 'Vertex 03', 'varias_por_semana', 'noche', '2025-12-17 17:13:56'),
(72, '11-4301-1021', '1996-11-02', 'Buenos Aires', 'Belgrano', 'email', 'Me gusta jugar en doble mixto y mejorar técnica.', 'femenino', 'derecha', 5, 'mixto', 'defensivo,globero', 'Nox', 'AT10 Genius', 'semanal', 'tarde', '2025-12-17 17:13:56'),
(73, '11-4301-1022', '1989-07-21', 'Buenos Aires', 'Caballito', 'llamada', 'Busco partidos parejos y ranking.', 'masculino', 'izquierda', 7, 'revés', 'ofensivo,defensivo', 'Adidas', 'Metalbone', 'varias_por_semana', 'noche', '2025-12-17 17:13:56'),
(74, '11-4301-1023', '1998-01-13', 'Buenos Aires', 'Almagro', 'whatsapp', 'Me sumo a torneos amateur.', 'femenino', 'derecha', 4, 'drive', 'regular,globero', 'Head', 'Alpha Motion', 'semanal', 'tarde', '2025-12-17 17:13:56'),
(75, '11-4301-1024', '1994-09-30', 'Buenos Aires', 'Villa Urquiza', 'email', 'Juego por diversión, pero me gusta mejorar.', 'masculino', 'derecha', 3, 'mixto', 'defensivo,regular', 'Wilson', 'Bela Pro', 'ocasional', 'noche', '2025-12-17 17:13:56'),
(76, '11-4301-1025', '1993-05-07', 'Buenos Aires', 'Recoleta', 'whatsapp', 'Prefiero partidos intensos y buena onda.', 'femenino', 'izquierda', 6, 'revés', 'ofensivo', 'Babolat', 'Air Veron', 'varias_por_semana', 'maniana', '2025-12-17 17:13:56'),
(77, '11-4301-1026', '1987-12-19', 'Buenos Aires', 'Flores', 'llamada', 'Me gusta jugar de revés y sostener el punto.', 'masculino', 'derecha', 5, 'revés', 'defensivo,globero', 'Siux', 'Electra ST2', 'semanal', 'tarde', '2025-12-17 17:13:56'),
(78, '11-4301-1027', '1999-08-25', 'Buenos Aires', 'Nuñez', 'whatsapp', 'Busco compañeros para jugar fijo.', 'femenino', 'derecha', 4, 'drive', 'regular', 'Drop Shot', 'Explorer Pro', 'varias_por_semana', 'noche', '2025-12-17 17:13:56'),
(79, '11-4301-1028', '1991-02-10', 'Buenos Aires', 'San Telmo', 'email', 'Me prendo a desafíos y ladder.', 'masculino', 'izquierda', 7, 'mixto', 'ofensivo,regular', 'StarVie', 'Triton', 'varias_por_semana', 'tarde', '2025-12-17 17:13:56'),
(80, '11-4301-1029', '1997-06-04', 'Buenos Aires', 'Boedo', 'whatsapp', 'Juego para mantenerme activa y competir.', 'femenino', 'derecha', 5, 'revés', 'defensivo,regular', 'Kuikma', 'PR 990', 'semanal', 'maniana', '2025-12-17 17:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_especiales`
--

CREATE TABLE `eventos_especiales` (
  `evento_id` int(11) NOT NULL,
  `cancha_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `tipo` enum('bloqueo','torneo','promocion','otro') NOT NULL DEFAULT 'bloqueo',
  `color` varchar(20) DEFAULT '#FF0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos_especiales`
--

INSERT INTO `eventos_especiales` (`evento_id`, `cancha_id`, `proveedor_id`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `tipo`, `color`) VALUES
(1, 7, 20, 'Mantenimiento general', 'Cambio de red y limpieza', '2025-12-05 08:00:00', '2025-12-05 22:00:00', 'bloqueo', '#FF0000'),
(2, 8, 20, 'Torneo interno', 'Semifinales', '2025-12-10 18:00:00', '2025-12-10 22:00:00', 'bloqueo', '#FF0000'),
(3, 10, 20, 'Clínica especial', 'Clase con profesor invitado', '2025-12-15 10:00:00', '2025-12-15 12:00:00', 'otro', '#FF0000'),
(5, 17, 20, 'Fiesta en la cancha', '2131231231313213123123213', '2025-12-15 13:00:00', '2025-12-15 14:20:00', 'bloqueo', '#FF0000'),
(7, 270, 20, 'Clínica de Saque A2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(8, 275, 20, 'Mini Torneo C1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(9, 281, 20, 'Promo Day P1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(10, 286, 20, 'Cierre Anticipado Q3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(11, 272, 20, 'Americano B1', 'Americano amistoso, rotación cada set.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(12, 278, 20, 'Torneo Nocturno D1', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(13, 284, 20, 'Bloqueo Q1 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(14, 283, 20, 'Clase Técnica P3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(15, 279, 20, 'Promo Flash D2', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71'),
(16, 206, 86, 'Mantenimiento Express Clásica 1', 'Bloqueo por mantenimiento (1 día).', '2025-12-17 08:00:00', '2025-12-17 12:00:00', 'bloqueo', '#FF0000'),
(17, 207, 86, 'Clínica de Saque Clásica 2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(18, 209, 86, 'Mini Torneo Cubierta 1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(19, 212, 86, 'Promo Day Panorámica 1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(20, 214, 86, 'Cierre Anticipado Panorámica 3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(21, 208, 86, 'Americano Clásica 3', 'Americano amistoso.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(22, 210, 86, 'Torneo Nocturno Cubierta 2', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(23, 213, 86, 'Bloqueo Panorámica 2 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(24, 211, 86, 'Clase Técnica Cubierta 3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(25, 206, 86, 'Promo Flash Clásica 1', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71'),
(26, 215, 87, 'Mantenimiento Express Clásica 1', 'Bloqueo por mantenimiento (1 día).', '2025-12-17 08:00:00', '2025-12-17 12:00:00', 'bloqueo', '#FF0000'),
(27, 216, 87, 'Clínica de Saque Clásica 2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(28, 218, 87, 'Mini Torneo Cubierta 1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(29, 221, 87, 'Promo Day Panorámica 1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(30, 223, 87, 'Cierre Anticipado Panorámica 3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(31, 217, 87, 'Americano Clásica 3', 'Americano amistoso.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(32, 219, 87, 'Torneo Nocturno Cubierta 2', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(33, 222, 87, 'Bloqueo Panorámica 2 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(34, 220, 87, 'Clase Técnica Cubierta 3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(35, 215, 87, 'Promo Flash Clásica 1', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71'),
(36, 224, 88, 'Mantenimiento Express Clásica 1', 'Bloqueo por mantenimiento (1 día).', '2025-12-17 08:00:00', '2025-12-17 12:00:00', 'bloqueo', '#FF0000'),
(37, 225, 88, 'Clínica de Saque Clásica 2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(38, 227, 88, 'Mini Torneo Cubierta 1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(39, 230, 88, 'Promo Day Panorámica 1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(40, 232, 88, 'Cierre Anticipado Panorámica 3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(41, 226, 88, 'Americano Clásica 3', 'Americano amistoso.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(42, 228, 88, 'Torneo Nocturno Cubierta 2', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(43, 231, 88, 'Bloqueo Panorámica 2 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(44, 229, 88, 'Clase Técnica Cubierta 3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(45, 224, 88, 'Promo Flash Clásica 1', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71'),
(46, 233, 89, 'Mantenimiento Express Clásica 1', 'Bloqueo por mantenimiento (1 día).', '2025-12-17 08:00:00', '2025-12-17 12:00:00', 'bloqueo', '#FF0000'),
(47, 234, 89, 'Clínica de Saque Clásica 2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(48, 236, 89, 'Mini Torneo Cubierta 1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(49, 239, 89, 'Promo Day Panorámica 1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(50, 241, 89, 'Cierre Anticipado Panorámica 3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(51, 235, 89, 'Americano Clásica 3', 'Americano amistoso.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(52, 237, 89, 'Torneo Nocturno Cubierta 2', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(53, 240, 89, 'Bloqueo Panorámica 2 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(54, 238, 89, 'Clase Técnica Cubierta 3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(55, 233, 89, 'Promo Flash Clásica 1', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71'),
(56, 242, 90, 'Mantenimiento Express Clásica 1', 'Bloqueo por mantenimiento (1 día).', '2025-12-17 08:00:00', '2025-12-17 12:00:00', 'bloqueo', '#FF0000'),
(57, 243, 90, 'Clínica de Saque Clásica 2', 'Evento de entrenamiento (cupo limitado).', '2025-12-18 10:00:00', '2025-12-18 13:00:00', 'otro', '#8E44AD'),
(58, 245, 90, 'Mini Torneo Cubierta 1', 'Torneo relámpago por categoría.', '2025-12-19 17:00:00', '2025-12-19 22:30:00', 'torneo', '#1F77B4'),
(59, 248, 90, 'Promo Day Panorámica 1', 'Bloque promocional para generar demanda.', '2025-12-20 09:00:00', '2025-12-20 23:00:00', 'promocion', '#2ECC71'),
(60, 250, 90, 'Cierre Anticipado Panorámica 3', 'Bloqueo por evento interno.', '2025-12-21 20:00:00', '2025-12-21 23:00:00', 'bloqueo', '#FF0000'),
(61, 244, 90, 'Americano Clásica 3', 'Americano amistoso.', '2025-12-22 18:00:00', '2025-12-22 22:00:00', 'otro', '#8E44AD'),
(62, 246, 90, 'Torneo Nocturno Cubierta 2', 'Formato eliminación directa.', '2025-12-23 19:00:00', '2025-12-23 23:00:00', 'torneo', '#1F77B4'),
(63, 249, 90, 'Bloqueo Panorámica 2 Limpieza', 'Limpieza profunda de cristales.', '2025-12-24 08:00:00', '2025-12-24 12:00:00', 'bloqueo', '#FF0000'),
(64, 247, 90, 'Clase Técnica Cubierta 3', 'Correcciones y táctica.', '2025-12-25 10:00:00', '2025-12-25 13:00:00', 'otro', '#8E44AD'),
(65, 242, 90, 'Promo Flash Clásica 1', 'Ventana promo de última hora.', '2025-12-18 20:00:00', '2025-12-18 23:00:00', 'promocion', '#2ECC71');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invitados`
--

CREATE TABLE `invitados` (
  `user_id` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_intentos`
--

CREATE TABLE `login_intentos` (
  `id` int(11) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `exito` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `login_intentos`
--

INSERT INTO `login_intentos` (`id`, `email`, `ip`, `creado_en`, `exito`) VALUES
(267, 'emilianoperez@gmail.com', '::1', '2025-12-18 12:13:21', 0),
(268, 'emilianoperez@gmail.com', '::1', '2025-12-18 12:13:29', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `notificacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `origen` enum('sistema','app','recepcion','proveedor','cliente') NOT NULL DEFAULT 'sistema',
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`notificacion_id`, `usuario_id`, `tipo`, `origen`, `titulo`, `mensaje`, `creada_en`, `leida`) VALUES
(1, 20, 'reserva_estado', 'sistema', 'Reserva confirmada', 'Tu reserva para la cancha Parque 1 el 2025-12-05 de 18:00 a 19:30 ha sido confirmada.', '2025-12-01 17:29:23', 1),
(2, 20, 'torneo_inscripcion', 'sistema', 'Inscripción a torneo', 'Te has unido al torneo \"Torneo Apertura Primavera\" en el club Proveedor.', '2025-12-01 17:29:23', 1),
(3, 20, 'torneo_inscripcion', 'sistema', 'Inscripción a torneo', 'Te has unido al torneo \"Open Primavera\" en el club Proveedor.', '2025-12-01 17:29:23', 1),
(4, 20, 'reporte_resuelto', 'sistema', 'Tu reporte ha sido resuelto', 'Tu reporte sobre la cancha VIP 1 ha sido marcado como Resuelto por el club.', '2025-12-01 17:29:23', 1),
(5, 20, 'torneo_inscripcion', 'sistema', 'Nuevo inscripto en torneo', 'El jugador Usuario se ha inscrito en tu torneo \"Torneo Apertura Primavera\".', '2025-12-01 17:29:23', 1),
(6, 20, 'torneo_inscripcion', 'sistema', 'Nuevo inscripto en torneo', 'El jugador Cristian se ha inscrito en tu torneo \"Open Primavera\".', '2025-12-01 17:29:23', 1),
(7, 20, 'reporte_nuevo', 'sistema', 'Nuevo reporte recibido', 'Se ha creado un nuevo reporte sobre la cancha Parque 1. Revisa la sección de reportes.', '2025-12-01 17:29:23', 1),
(8, 20, 'reserva_nueva', 'sistema', 'Nueva reserva en tu club', 'Un jugador ha realizado una nueva reserva para la cancha VIP 2 el 2025-12-06.', '2025-12-01 17:29:23', 1),
(9, 7, 'reporte_resuelto', 'sistema', 'Tu reporte ha sido resuelto', 'Tu reporte \"Turno ocupado al llegar\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:06:29', 0),
(10, 7, 'reporte_resuelto', 'sistema', 'Tu reporte ha sido resuelto', 'Tu reporte \"Rival no se presentó\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:11:51', 0),
(11, 7, 'reporte_resuelto', 'sistema', 'Tu reporte ha sido resuelto', 'Tu reporte \"Turno ocupado al llegar\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:16:31', 0),
(12, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:44:35', 0),
(13, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:44:35', 1),
(14, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Tilin\" en el club Proveedor.', '2025-12-03 20:44:46', 0),
(15, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Tilin\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:44:46', 0),
(16, 7, 'torneo', 'sistema', 'Saliste del torneo', 'Has salido del torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:48:50', 0),
(17, 20, 'torneo', 'sistema', 'Un jugador salió de \"Viva la pobreza carajo\"', 'Usuario se dio de baja del torneo.', '2025-12-03 20:48:50', 0),
(18, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:50:18', 0),
(19, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:50:18', 0),
(20, 7, 'torneo', 'sistema', 'Saliste del torneo', 'Has salido del torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:55:06', 0),
(21, 20, 'torneo', 'sistema', 'Un jugador salió de \"Viva la pobreza carajo\"', 'Usuario se dio de baja del torneo.', '2025-12-03 20:55:06', 0),
(22, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-06 20:57:52', 0),
(23, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-06 20:57:52', 0),
(25, 1, 'cliente_alta', 'sistema', 'Nuevo cliente #24', 'Un nuevo cliente se ha registrado en GoatSport.', '2025-12-07 10:44:02', 0),
(26, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #62', 'Reserva creada desde recepción.', '2025-12-07 11:31:49', 0),
(27, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #62', 'Reserva en 2025-12-07 14:30-15:30:00.', '2025-12-07 11:31:49', 1),
(28, 1, 'pago_club_pendiente', 'recepcion', 'Pago en club pendiente (#22)', 'Método: club. Pendiente de cobrar. Reserva #62 (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:32:44', 0),
(29, 20, 'pago_club_pendiente', 'recepcion', 'Pago en club pendiente (#22)', 'Método: club. Pendiente de cobrar. Reserva #62 (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:32:44', 1),
(30, 1, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#21)', 'Reserva #62 confirmada en recepción (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:35:47', 0),
(31, 20, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#21)', 'Reserva #62 confirmada en recepción (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:35:47', 1),
(32, 1, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#22)', 'Reserva #62 confirmada en recepción (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:35:52', 0),
(33, 20, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#22)', 'Reserva #62 confirmada en recepción (2025-12-07 14:30:00-15:30:00).', '2025-12-07 11:35:52', 1),
(34, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #63', 'Reserva creada desde recepción.', '2025-12-07 11:37:16', 0),
(35, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #63', 'Reserva en 2025-12-07 16:00-17:00:00.', '2025-12-07 11:37:16', 1),
(36, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #64', 'Reserva creada desde recepción.', '2025-12-07 11:37:59', 0),
(37, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #64', 'Reserva en 2025-12-07 15:00-16:00:00.', '2025-12-07 11:37:59', 1),
(38, 1, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#24)', 'Reserva #64 confirmada en recepción (2025-12-07 15:00:00-16:00:00).', '2025-12-07 11:53:51', 0),
(39, 20, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#24)', 'Reserva #64 confirmada en recepción (2025-12-07 15:00:00-16:00:00).', '2025-12-07 11:53:51', 1),
(40, 1, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#25)', 'Reserva #65 confirmada en recepción (2025-12-07 21:30:00-23:00:00).', '2025-12-07 11:58:01', 0),
(41, 20, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#25)', 'Reserva #65 confirmada en recepción (2025-12-07 21:30:00-23:00:00).', '2025-12-07 11:58:01', 1),
(42, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #66', 'Reserva creada desde recepción.', '2025-12-07 12:20:25', 0),
(43, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #66', 'Reserva en 2025-12-07 12:00-13:00:00.', '2025-12-07 12:20:25', 1),
(44, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #67', 'Reserva creada desde recepción.', '2025-12-07 12:26:48', 0),
(45, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #67', 'Reserva en 2025-12-07 12:00-13:00:00.', '2025-12-07 12:26:48', 1),
(46, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #68', 'Reserva creada desde recepción.', '2025-12-07 13:12:09', 0),
(47, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #68', 'Reserva en 2025-12-07 13:00-14:00:00.', '2025-12-07 13:12:09', 1),
(48, 1, 'perfil_actualizado', 'recepcion', 'Recepcionista actualizó su nombre', 'El recepcionista (ID 21) cambió su nombre de \'Recepcionista\' a \'Josué Matías\'.', '2025-12-07 20:27:07', 0),
(49, 20, 'perfil_actualizado', 'recepcion', 'Recepcionista actualizó su nombre', 'El recepcionista (ID 21) cambió su nombre de \'Recepcionista\' a \'Josué Matías\'.', '2025-12-07 20:27:07', 1),
(50, 1, 'perfil_actualizado', 'recepcion', 'Recepcionista actualizó su nombre', 'El recepcionista (ID 21) cambió su nombre de \'Josué Matías\' a \'Recepcionista\'.', '2025-12-07 20:33:32', 0),
(51, 20, 'perfil_actualizado', 'recepcion', 'Recepcionista actualizó su nombre', 'El recepcionista (ID 21) cambió su nombre de \'Josué Matías\' a \'Recepcionista\'.', '2025-12-07 20:33:32', 1),
(52, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #69', 'Reserva creada desde recepción.', '2025-12-07 21:53:05', 0),
(53, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #69', 'Reserva en 2025-12-08 12:00-13:00:00.', '2025-12-07 21:53:05', 0),
(54, 1, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#29)', 'Reserva #69 confirmada en recepción (2025-12-08 12:00:00-13:00:00).', '2025-12-07 21:59:13', 0),
(55, 20, 'pago_club_confirmado', 'recepcion', 'Pago en club confirmado (#29)', 'Reserva #69 confirmada en recepción (2025-12-08 12:00:00-13:00:00).', '2025-12-07 21:59:13', 0),
(56, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #70', 'Reserva creada desde recepción.', '2025-12-07 22:43:50', 0),
(57, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #70', 'Reserva en 2025-12-08 14:00-15:00:00.', '2025-12-07 22:43:50', 0),
(58, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #71', 'Reserva creada desde recepción.', '2025-12-07 23:27:24', 0),
(59, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #71', 'Reserva en 2025-12-08 13:00-15:00:00.', '2025-12-07 23:27:24', 0),
(60, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #72', 'Reserva creada desde recepción.', '2025-12-08 11:11:51', 0),
(61, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #72', 'Reserva en 2025-12-08 12:00-13:00:00.', '2025-12-08 11:11:51', 0),
(62, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #73', 'Reserva creada desde recepción.', '2025-12-08 11:32:31', 0),
(63, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #73', 'Reserva en 2025-12-08 07:00-08:00:00.', '2025-12-08 11:32:31', 0),
(64, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #74', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:31:10', 0),
(65, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #74', 'Reserva en 2025-12-09 17:00-20:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:31:10', 0),
(66, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #75', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:33:02', 0),
(67, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #75', 'Reserva en 2025-12-09 16:00-17:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:33:02', 0),
(68, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #76', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:49:19', 0),
(69, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #76', 'Reserva en 2025-12-08 09:00-10:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:49:19', 0),
(70, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #77', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:50:30', 0),
(71, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #77', 'Reserva en 2025-12-08 17:00-18:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:50:30', 0),
(72, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #78', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:51:00', 1),
(73, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #78', 'Reserva en 2025-12-08 19:00-20:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:51:00', 0),
(74, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #79', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:51:52', 0),
(75, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #79', 'Reserva en 2025-12-08 20:00-21:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:51:52', 0),
(76, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #80', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:52:52', 0),
(77, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #80', 'Reserva en 2025-12-08 22:00-23:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:52:52', 0),
(78, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #81', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 12:53:45', 1),
(79, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #81', 'Reserva en 2025-12-08 15:00-16:00:00. Cliente: Cristian Chejo.', '2025-12-08 12:53:45', 0),
(80, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #82', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 13:11:09', 1),
(81, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #82', 'Reserva en 2025-12-08 07:00-08:00:00. Cliente: Cristian Chejo.', '2025-12-08 13:11:09', 1),
(82, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #83', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:09:19', 1),
(83, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #83', 'Reserva en 2025-12-08 12:00-13:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:09:19', 1),
(84, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#38)', 'Reserva #79 confirmada (mercado_pago) en 2025-12-08 20:00:00-21:00:00.', '2025-12-08 14:11:29', 1),
(85, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#38)', 'Reserva #79 confirmada (mercado_pago) en 2025-12-08 20:00:00-21:00:00.', '2025-12-08 14:11:29', 1),
(86, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #84', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:15:57', 1),
(87, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #84', 'Reserva en 2025-12-08 10:00-11:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:15:57', 1),
(88, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #88', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:35:47', 1),
(89, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #88', 'Reserva en 2025-12-08 07:00-08:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:35:47', 1),
(90, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #89', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:37:18', 1),
(91, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #89', 'Reserva en 2025-12-08 07:00-08:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:37:18', 1),
(92, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #90', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:38:07', 1),
(93, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #90', 'Reserva en 2025-12-08 11:00-12:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:38:07', 1),
(94, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #91', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:39:14', 1),
(95, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #91', 'Reserva en 2025-12-08 13:00-14:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:39:14', 0),
(96, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #92', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:48:00', 1),
(97, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #92', 'Reserva en 2025-12-08 13:00-15:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:48:00', 1),
(98, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #93', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 14:49:27', 1),
(99, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #93', 'Reserva en 2025-12-08 17:00-18:00:00. Cliente: Cristian Chejo.', '2025-12-08 14:49:27', 0),
(100, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #94', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-08 16:08:52', 1),
(101, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #94', 'Reserva en 2025-12-08 10:00-11:00:00. Cliente: Cristian Chejo.', '2025-12-08 16:08:52', 0),
(102, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#48)', 'Reserva #89 confirmada (tarjeta) en 2025-12-08 07:00:00-08:00:00.', '2025-12-08 17:41:18', 1),
(103, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#48)', 'Reserva #89 confirmada (tarjeta) en 2025-12-08 07:00:00-08:00:00.', '2025-12-08 17:41:18', 0),
(104, 7, 'reporte_resuelto', 'sistema', 'Tu reporte ha sido resuelto', 'Tu reporte \"Luz fallando en la cancha\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-13 01:37:16', 0),
(105, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Master 250\" en el club Proveedor.', '2025-12-13 10:51:56', 0),
(106, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Master 250\"', 'Usuario se inscribió en tu torneo.', '2025-12-13 10:51:56', 0),
(107, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Open Ciudad\" en el club Proveedor.', '2025-12-13 10:51:59', 0),
(108, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Open Ciudad\"', 'Usuario se inscribió en tu torneo.', '2025-12-13 10:51:59', 0),
(109, 7, 'torneo', 'sistema', 'Saliste del torneo', 'Has salido del torneo \"Master 250\" en el club Proveedor.', '2025-12-13 10:58:27', 0),
(110, 20, 'torneo', 'sistema', 'Un jugador salió de \"Master 250\"', 'Usuario se dio de baja del torneo.', '2025-12-13 10:58:27', 0),
(111, 7, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Master 250\" en el club Proveedor.', '2025-12-13 11:01:30', 0),
(112, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Master 250\"', 'Usuario se inscribió en tu torneo.', '2025-12-13 11:01:30', 0),
(113, 7, 'torneo', 'sistema', 'Saliste del torneo', 'Has salido del torneo \"Master 250\" en el club Proveedor.', '2025-12-13 11:53:12', 0),
(114, 20, 'torneo', 'sistema', 'Un jugador salió de \"Master 250\"', 'Usuario se dio de baja del torneo.', '2025-12-13 11:53:12', 0),
(115, 20, 'cancha_denegada', 'sistema', 'Cancha denegada', 'Tu cancha «Panorámica Río» fue denegada. Podés revisar y reenviar.', '2025-12-13 16:35:21', 0),
(116, 20, 'cancha_eliminada', 'sistema', 'Cancha eliminada', 'Tu cancha «Cubierta Sur 2» fue eliminada por el administrador.', '2025-12-13 16:35:47', 0),
(117, 20, 'cancha_aprobada', 'sistema', 'Cancha aprobada', 'Tu cancha «Panorámica Centro X» fue aprobada y ya está visible.', '2025-12-13 16:36:22', 0),
(118, 7, 'reporte_resuelto', 'sistema', 'Reporte resuelto', 'Tu reporte «Problema de iluminación» fue marcado como Resuelto.', '2025-12-13 17:09:27', 0),
(119, 20, 'torneo_eliminado', 'sistema', 'Torneo eliminado: Tilin', 'El torneo \"Tilin\" (del 01/12 al 02/12) ha sido eliminado.', '2025-12-13 17:43:27', 0),
(120, 21, 'torneo_eliminado', 'sistema', 'Torneo eliminado: Tilin', 'El torneo \"Tilin\" (del 01/12 al 02/12) ha sido eliminado.', '2025-12-13 17:43:27', 0),
(121, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #96', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-13 19:47:47', 0),
(122, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #96', 'Reserva en 2025-12-13 08:10-09:30:00. Cliente: Cristian Chejo.', '2025-12-13 19:47:47', 0),
(127, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #96', 'Se eliminó la reserva #96 programada para 2025-12-13 08:10:00-09:30:00.', '2025-12-13 19:51:30', 0),
(128, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #96', 'Se eliminó la reserva #96 programada para 2025-12-13 08:10:00-09:30:00.', '2025-12-13 19:51:30', 0),
(129, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#54)', 'Reserva #95 confirmada (club) en 2025-12-13 18:00:00-19:30:00.', '2025-12-13 19:51:58', 0),
(130, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#54)', 'Reserva #95 confirmada (club) en 2025-12-13 18:00:00-19:30:00.', '2025-12-13 19:51:58', 1),
(131, 1, 'reserva_editada', 'recepcion', 'Reserva editada #95', 'Nueva fecha/horario: 2025-12-13 18:00-19:30:00.', '2025-12-13 19:57:15', 0),
(132, 20, 'reserva_editada', 'recepcion', 'Reserva editada #95', 'Nueva fecha/horario: 2025-12-13 18:00-19:30:00.', '2025-12-13 19:57:15', 0),
(133, 7, 'reserva_editada', 'recepcion', 'Reserva editada #95', 'Su reserva fue actualizada: Nueva fecha/horario: 2025-12-13 18:00-19:30:00.', '2025-12-13 19:57:15', 0),
(134, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #95', 'Se eliminó la reserva #95 programada para 2025-12-13 18:00:00-19:30:00.', '2025-12-13 19:57:23', 0),
(135, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #95', 'Se eliminó la reserva #95 programada para 2025-12-13 18:00:00-19:30:00.', '2025-12-13 19:57:23', 0),
(136, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #97', 'Reserva creada desde recepción para Cristian Chejoido.', '2025-12-13 19:57:49', 0),
(137, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #97', 'Reserva en 2025-12-13 11:00-12:00:00. Cliente: Cristian Chejoido.', '2025-12-13 19:57:49', 0),
(138, 1, 'reserva_editada', 'recepcion', 'Reserva editada #97', 'Nueva fecha/horario: 2025-12-13 11:00-12:00:00.', '2025-12-13 19:57:57', 0),
(139, 20, 'reserva_editada', 'recepcion', 'Reserva editada #97', 'Nueva fecha/horario: 2025-12-13 11:00-12:00:00.', '2025-12-13 19:57:57', 0),
(140, 34, 'reserva_editada', 'recepcion', 'Reserva editada #97', 'Su reserva fue actualizada: Nueva fecha/horario: 2025-12-13 11:00-12:00:00.', '2025-12-13 19:57:57', 0),
(141, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #98', 'Reserva creada desde recepción para Cristian Chejito.', '2025-12-13 19:58:54', 0),
(142, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #98', 'Reserva en 2025-12-13 12:00-13:10:00. Cliente: Cristian Chejito.', '2025-12-13 19:58:54', 0),
(143, 1, 'partido_nuevo', 'recepcion', 'Partido creado #44 (reserva #98)', 'Se creó un partido a partir de una reserva sin cita previa para 2025-12-13 12:00-13:10:00.', '2025-12-13 19:58:54', 0),
(144, 20, 'partido_nuevo', 'recepcion', 'Partido creado #44 (reserva #98)', 'Se creó un partido a partir de una reserva sin cita previa para 2025-12-13 12:00-13:10:00.', '2025-12-13 19:58:54', 0),
(145, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #98', 'Se eliminó la reserva #98 programada para 2025-12-13 12:00:00-13:10:00.', '2025-12-13 19:59:34', 0),
(146, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #98', 'Se eliminó la reserva #98 programada para 2025-12-13 12:00:00-13:10:00.', '2025-12-13 19:59:34', 0),
(147, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #97', 'Se eliminó la reserva #97 programada para 2025-12-13 11:00:00-12:00:00.', '2025-12-13 19:59:36', 0),
(148, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #97', 'Se eliminó la reserva #97 programada para 2025-12-13 11:00:00-12:00:00.', '2025-12-13 19:59:36', 0),
(149, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #99', 'Reserva creada desde recepción para Cristian Chejito.', '2025-12-13 20:00:02', 0),
(150, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #99', 'Reserva en 2025-12-13 16:30-18:00:00. Cliente: Cristian Chejito.', '2025-12-13 20:00:02', 0),
(151, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #100', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-13 20:00:42', 0),
(152, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #100', 'Reserva en 2025-12-13 18:00-19:10:00. Cliente: Cristian Chejo.', '2025-12-13 20:00:42', 0),
(153, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#59)', 'Reserva #100 confirmada (tarjeta) en 2025-12-13 18:00:00-19:10:00.', '2025-12-13 20:01:08', 0),
(154, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#59)', 'Reserva #100 confirmada (tarjeta) en 2025-12-13 18:00:00-19:10:00.', '2025-12-13 20:01:08', 0),
(155, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #101', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-13 20:26:55', 0),
(156, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #101', 'Reserva en 2025-12-14 20:30-22:00:00. Cliente: Cristian Chejo.', '2025-12-13 20:26:55', 0),
(157, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #102', 'Reserva creada desde recepción para Cristian Chejoido.', '2025-12-13 20:27:22', 0),
(158, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #102', 'Reserva en 2025-12-14 14:20-15:50:00. Cliente: Cristian Chejoido.', '2025-12-13 20:27:22', 0),
(159, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #103', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-13 20:27:59', 0),
(160, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #103', 'Reserva en 2025-12-14 18:40-20:20:00. Cliente: Cristian Chejo.', '2025-12-13 20:27:59', 0),
(161, 1, 'reserva_editada', 'recepcion', 'Reserva editada #102', 'Nueva fecha/horario: 2025-12-14 14:20-15:50:00.', '2025-12-13 20:30:16', 0),
(162, 20, 'reserva_editada', 'recepcion', 'Reserva editada #102', 'Nueva fecha/horario: 2025-12-14 14:20-15:50:00.', '2025-12-13 20:30:16', 0),
(163, 43, 'reserva_editada', 'recepcion', 'Reserva editada #102', 'Su reserva fue actualizada: Nueva fecha/horario: 2025-12-14 14:20-15:50:00.', '2025-12-13 20:30:16', 0),
(164, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #104', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-13 20:31:04', 0),
(165, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #104', 'Reserva en 2025-12-14 16:50-18:40:00. Cliente: Cristian Chejo.', '2025-12-13 20:31:04', 0),
(166, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#63)', 'Reserva #104 confirmada (mercado_pago) en 2025-12-14 16:50:00-18:40:00.', '2025-12-13 20:31:18', 0),
(167, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#63)', 'Reserva #104 confirmada (mercado_pago) en 2025-12-14 16:50:00-18:40:00.', '2025-12-13 20:31:18', 0),
(168, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #102', 'Se eliminó la reserva #102 programada para 2025-12-14 14:20:00-15:50:00.', '2025-12-13 20:31:21', 0),
(169, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #102', 'Se eliminó la reserva #102 programada para 2025-12-14 14:20:00-15:50:00.', '2025-12-13 20:31:21', 0),
(170, 1, 'reserva_editada', 'recepcion', 'Reserva editada #104', 'Nueva fecha/horario: 2025-12-14 16:50-18:40:00.', '2025-12-13 20:31:28', 0),
(171, 20, 'reserva_editada', 'recepcion', 'Reserva editada #104', 'Nueva fecha/horario: 2025-12-14 16:50-18:40:00.', '2025-12-13 20:31:28', 0),
(172, 44, 'reserva_editada', 'recepcion', 'Reserva editada #104', 'Su reserva fue actualizada: Nueva fecha/horario: 2025-12-14 16:50-18:40:00.', '2025-12-13 20:31:28', 0),
(173, 1, 'cliente_alta', 'recepcion', 'Nuevo cliente #45', 'Un nuevo cliente se ha registrado en GoatSport.', '2025-12-13 20:50:46', 0),
(174, 1, 'cliente_alta', 'recepcion', 'Nuevo cliente #46', 'Un nuevo cliente se ha registrado en GoatSport.', '2025-12-13 20:55:49', 0),
(175, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:31:51', 0),
(176, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:31:51', 0),
(177, 1, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-1.', '2025-12-13 22:32:03', 0),
(178, 20, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-1.', '2025-12-13 22:32:03', 0),
(179, 1, 'resultado_eliminado', 'recepcion', 'Resultado eliminado (#40)', 'Se eliminó el resultado del partido.', '2025-12-13 22:32:09', 0),
(180, 20, 'resultado_eliminado', 'recepcion', 'Resultado eliminado (#40)', 'Se eliminó el resultado del partido.', '2025-12-13 22:32:09', 0),
(181, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:37:09', 0),
(182, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:37:09', 0),
(183, 1, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-1.', '2025-12-13 22:37:25', 0),
(184, 20, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-1.', '2025-12-13 22:37:25', 0),
(185, 1, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:37:36', 0),
(186, 20, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:37:36', 1),
(187, 1, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:37:43', 0),
(188, 20, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:37:43', 0),
(189, 1, 'resultado_eliminado', 'recepcion', 'Resultado eliminado (#40)', 'Se eliminó el resultado del partido.', '2025-12-13 22:47:08', 0),
(190, 20, 'resultado_eliminado', 'recepcion', 'Resultado eliminado (#40)', 'Se eliminó el resultado del partido.', '2025-12-13 22:47:08', 1),
(191, 1, 'partido_eliminado', 'recepcion', 'Partido eliminado (#41)', 'Se eliminó el partido de la agenda.', '2025-12-13 22:47:27', 1),
(192, 20, 'partido_eliminado', 'recepcion', 'Partido eliminado (#41)', 'Se eliminó el partido de la agenda.', '2025-12-13 22:47:27', 1),
(193, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:48:55', 1),
(194, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#40)', 'Resultado: 4-3 4-2.', '2025-12-13 22:48:55', 1),
(195, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #105', 'Reserva creada desde recepción para Cristian Chejoido.', '2025-12-13 22:52:03', 1),
(196, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #105', 'Reserva en 2025-12-14 22:00-22:40:00. Cliente: Cristian Chejoido.', '2025-12-13 22:52:03', 1),
(197, 1, 'partido_nuevo', 'recepcion', 'Partido creado #45 (reserva #105)', 'Se creó un partido a partir de una reserva sin cita previa para 2025-12-14 22:00-22:40:00.', '2025-12-13 22:52:03', 1),
(198, 20, 'partido_nuevo', 'recepcion', 'Partido creado #45 (reserva #105)', 'Se creó un partido a partir de una reserva sin cita previa para 2025-12-14 22:00-22:40:00.', '2025-12-13 22:52:03', 1),
(199, 1, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:55:14', 1),
(200, 20, 'resultado_editado', 'recepcion', 'Resultado editado (#40)', 'Resultado: 4-3 4-0.', '2025-12-13 22:55:14', 1),
(201, 1, 'partido_eliminado', 'recepcion', 'Partido eliminado (#43)', 'Se eliminó el partido de la agenda.', '2025-12-13 22:55:30', 1),
(202, 20, 'partido_eliminado', 'recepcion', 'Partido eliminado (#43)', 'Se eliminó el partido de la agenda.', '2025-12-13 22:55:30', 1),
(203, 1, 'reserva_editada', 'recepcion', 'Reserva editada #101', 'Nueva fecha/horario: 2025-12-14 20:30-23:00:00.', '2025-12-14 00:48:58', 0),
(204, 20, 'reserva_editada', 'recepcion', 'Reserva editada #101', 'Nueva fecha/horario: 2025-12-14 20:30-23:00:00.', '2025-12-14 00:48:58', 0),
(205, 40, 'reserva_editada', 'recepcion', 'Reserva editada #101', 'Su reserva fue actualizada: Nueva fecha/horario: 2025-12-14 20:30-23:00:00.', '2025-12-14 00:48:58', 0),
(206, 1, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #101', 'Se eliminó la reserva #101 programada para 2025-12-14 20:30:00-23:00:00.', '2025-12-14 00:49:06', 0),
(207, 20, 'reserva_eliminada', 'recepcion', 'Reserva eliminada #101', 'Se eliminó la reserva #101 programada para 2025-12-14 20:30:00-23:00:00.', '2025-12-14 00:49:06', 0),
(208, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#64)', 'Reserva #105 confirmada (mercado_pago) en 2025-12-14 22:00:00-22:40:00.', '2025-12-14 00:49:10', 0),
(209, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#64)', 'Reserva #105 confirmada (mercado_pago) en 2025-12-14 22:00:00-22:40:00.', '2025-12-14 00:49:10', 1),
(210, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El proveedor \'Larrazabal\' envió una solicitud de registro.', '2025-12-14 01:32:22', 0),
(211, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El proveedor \'Larrazabal\' envió una solicitud de registro.', '2025-12-14 01:35:39', 0),
(212, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El proveedor \'Larrazabal\' envió una solicitud de registro.', '2025-12-14 01:37:27', 0),
(213, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Larrazabal\' envió una solicitud (#1).', '2025-12-14 02:22:12', 0),
(214, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Belen Chejo\' envió una solicitud (#2).', '2025-12-14 02:26:29', 0),
(215, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Belen Chejo\' envió una solicitud (#3).', '2025-12-14 02:28:58', 0),
(216, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Zubizarreta Asociación\' envió una solicitud (#4).', '2025-12-14 02:46:31', 0),
(217, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'America del Sud\' envió una solicitud (#5).', '2025-12-14 02:54:44', 0),
(218, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Belen Chejo\' envió una solicitud (#6).', '2025-12-14 03:03:00', 0),
(219, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Larrazabal\' envió una solicitud (#7).', '2025-12-14 03:08:41', 0),
(220, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Larrazabal\' envió una solicitud (#8).', '2025-12-14 03:12:19', 0),
(221, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Zubizarreta Asociación\' envió una solicitud (#9).', '2025-12-14 03:21:27', 0),
(222, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Zubizarreta Asociación\' envió una solicitud (#10).', '2025-12-14 03:28:59', 0),
(223, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Larrazabal\' envió una solicitud (#11).', '2025-12-14 09:48:06', 0),
(224, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Larrazabal\' envió una solicitud (#12).', '2025-12-14 10:04:42', 0),
(225, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Av Ecalada\' envió una solicitud (#13).', '2025-12-14 10:07:06', 0),
(226, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Zubizarreta Asociación\' envió una solicitud (#14).', '2025-12-14 10:17:48', 0),
(227, 1, 'cliente_alta', '', 'Nuevo cliente #56', 'Se registró un nuevo cliente desde el formulario público.', '2025-12-14 12:44:30', 0),
(228, 1, 'cliente_alta', '', 'Nuevo cliente #57', 'Se registró un nuevo cliente desde el formulario público.', '2025-12-14 12:48:38', 0),
(229, 20, 'cancha_denegada', 'sistema', 'Cancha denegada', 'Tu cancha «Cancha Nueva Esperanza» fue denegada. Podés revisar y reenviar.', '2025-12-14 16:05:12', 1),
(230, 1, 'cancha_nueva', 'sistema', 'Nueva cancha creada', 'El proveedor #20 creó la cancha «Cancha Ultima Generación». Pendiente de aprobación.', '2025-12-14 16:25:07', 0),
(231, 1, 'cancha_nueva', 'sistema', 'Nueva cancha creada', 'El proveedor #20 creó la cancha «Cancha Sud 8». Pendiente de aprobación.', '2025-12-14 16:38:17', 0),
(232, 1, 'cancha_editada', 'sistema', 'Cancha editada', 'El proveedor #20 editó la cancha «Cancha Sud 7».', '2025-12-14 16:38:43', 0),
(233, 1, 'cancha_editada', 'sistema', 'Cancha editada', 'El proveedor #20 editó la cancha «Cancha Parque 2».', '2025-12-14 16:39:41', 0),
(234, 1, 'cancha_editada', 'sistema', 'Cancha editada', 'El proveedor #20 editó la cancha «Clásica Norte A».', '2025-12-14 16:49:31', 0),
(235, 1, 'cancha_eliminada', 'sistema', 'Cancha eliminada por proveedor', 'El proveedor #20 eliminó la cancha aprobada «Cubierta Oeste 1».', '2025-12-14 16:50:32', 0),
(236, 1, 'cancha_pendiente_cancelada', 'sistema', 'Cancha pendiente cancelada por proveedor', 'El proveedor #20 canceló la cancha pendiente «Panorámica Parque».', '2025-12-14 16:50:37', 0),
(237, 1, 'cancha_editada', 'sistema', 'Cancha editada', 'El proveedor #20 editó la cancha «Cancha Parque 1».', '2025-12-14 16:50:41', 0),
(238, 1, 'cancha_editada', 'sistema', 'Cancha editada', 'El proveedor #20 editó la cancha «Cancha Parque 1».', '2025-12-14 17:12:01', 0),
(239, 1, 'evento_creado', 'sistema', 'Evento especial creado', 'El proveedor #20 creó el evento \"Arreglo del baño\" (cancha: Cancha Parque 2) para 15/12/2025 16:00 — 15/12/2025 19:00.', '2025-12-15 10:30:31', 0),
(240, 21, 'evento_creado', 'sistema', 'Evento especial creado', 'El proveedor #20 creó el evento \"Arreglo del baño\" (cancha: Cancha Parque 2) para 15/12/2025 16:00 — 15/12/2025 19:00.', '2025-12-15 10:30:31', 0),
(241, 1, 'evento_eliminado', 'sistema', 'Evento especial eliminado', 'El proveedor #20 eliminó el evento \"Arreglo del baño\" (cancha: Cancha Parque 2) que estaba programado para 15/12/2025 16:00 — 15/12/2025 19:00.', '2025-12-15 10:30:40', 0),
(242, 21, 'evento_eliminado', 'sistema', 'Evento especial eliminado', 'El proveedor #20 eliminó el evento \"Arreglo del baño\" (cancha: Cancha Parque 2) que estaba programado para 15/12/2025 16:00 — 15/12/2025 19:00.', '2025-12-15 10:30:40', 0),
(243, 1, 'evento_creado', 'sistema', 'Evento especial creado', 'El proveedor #20 creó el evento \"Fiesta en la cancha\" (cancha: Panorámica Río) para 15/12/2025 13:00 — 15/12/2025 14:20.', '2025-12-15 10:34:03', 0),
(244, 21, 'evento_creado', 'sistema', 'Evento especial creado', 'El proveedor #20 creó el evento \"Fiesta en la cancha\" (cancha: Panorámica Río) para 15/12/2025 13:00 — 15/12/2025 14:20.', '2025-12-15 10:34:03', 0),
(245, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Viernes negro\" para el proveedor #20 (vigencia 2025-12-15 a 2025-12-24).', '2025-12-15 11:43:20', 0),
(246, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Viernes negro\" para el proveedor #20 (vigencia 2025-12-15 a 2025-12-24).', '2025-12-15 11:43:20', 0),
(247, 1, 'proveedor_password_cambiada', 'proveedor', 'Cambio de contraseña de proveedor', 'El proveedor #20 (Proveedor - proveedor@gmail.com) cambió su contraseña.', '2025-12-15 13:03:38', 0),
(248, 1, 'proveedor_perfil_actualizado', 'proveedor', 'Perfil de proveedor actualizado', 'El proveedor #20 actualizó su perfil.', '2025-12-15 13:06:39', 0),
(249, 1, 'proveedor_perfil_actualizado', 'proveedor', 'Perfil de proveedor actualizado', 'El proveedor #20 actualizó su perfil.', '2025-12-15 13:08:15', 0),
(250, 1, 'proveedor_password_cambiada', 'proveedor', 'Cambio de contraseña de proveedor', 'El proveedor #20 (Proveedor123 - proveedor@gmail.com) cambió su contraseña.', '2025-12-15 13:37:02', 0),
(251, 1, 'perfil_actualizado', 'recepcion', 'Cambio de contraseña', 'Un recepcionista actualizó su contraseña.', '2025-12-15 13:38:00', 0),
(252, 20, 'perfil_actualizado', 'recepcion', 'Cambio de contraseña', 'Un recepcionista actualizó su contraseña.', '2025-12-15 13:38:00', 1),
(253, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-0.', '2025-12-15 15:33:30', 0),
(254, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-0.', '2025-12-15 15:33:30', 1),
(255, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-15 15:33:48', 0),
(256, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-15 15:33:48', 1),
(257, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-2.', '2025-12-15 15:45:32', 0),
(258, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-2.', '2025-12-15 15:45:32', 1),
(259, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-15 15:45:46', 0),
(260, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-15 15:45:46', 1),
(261, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#58)', 'Resultado: 4-3 4-1.', '2025-12-15 15:45:56', 0),
(262, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#58)', 'Resultado: 4-3 4-1.', '2025-12-15 15:45:56', 1),
(263, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-2.', '2025-12-19 16:54:19', 1),
(264, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#56)', 'Resultado: 4-3 4-2.', '2025-12-19 16:54:19', 1),
(265, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-20 16:54:46', 1),
(266, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#57)', 'Resultado: 4-3 4-2.', '2025-12-20 16:54:46', 1),
(267, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#58)', 'Resultado: 4-3 4-2.', '2025-12-21 16:55:26', 1),
(268, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#58)', 'Resultado: 4-3 4-2.', '2025-12-21 16:55:26', 1),
(269, 1, 'reporte_nuevo', 'recepcion', 'Nuevo reporte (#33)', 'Tipo: sistema. Título: Problema de iluminación.', '2025-12-15 17:32:44', 0),
(270, 1, 'reporte_nuevo', 'recepcion', 'Nuevo reporte (#34)', 'Tipo: cancha. Título: Problema de iluminación.', '2025-12-15 17:33:36', 0),
(271, 46, 'pago_pendiente', 'sistema', 'Pago pendiente de reserva #123', 'Tenés un pago pendiente de $ 5,00 para la reserva #123 (2025-12-16 22:00:00 - 23:00:00).', '2025-12-16 17:15:32', 0),
(272, 46, 'pago_pendiente', 'sistema', 'Pago pendiente de reserva #128', 'Tenés un pago pendiente de $ 3.200,00 para la reserva #128 (2025-12-16 22:00:00 - 23:00:00).', '2025-12-16 20:31:11', 0),
(273, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #135', 'Reserva creada desde recepción para Cristian Chejo.', '2025-12-17 00:04:43', 0),
(274, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #135', 'Reserva en 2025-12-17 08:00-09:00:00. Cliente: Cristian Chejo.', '2025-12-17 00:04:43', 0),
(275, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#83)', 'Reserva #135 confirmada (tarjeta) en 2025-12-17 08:00:00-09:00:00.', '2025-12-17 00:08:10', 0),
(276, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#83)', 'Reserva #135 confirmada (tarjeta) en 2025-12-17 08:00:00-09:00:00.', '2025-12-17 00:08:10', 0),
(277, 1, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #136', 'Reserva creada desde recepción para Belen Chejo.', '2025-12-17 00:10:07', 0),
(278, 20, 'reserva_nueva', 'recepcion', 'Nueva reserva walk-in #136', 'Reserva en 2025-12-17 12:00-13:00:00. Cliente: Belen Chejo.', '2025-12-17 00:10:07', 0),
(279, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#45)', 'Resultado: 4-3 4-0.', '2025-12-17 00:15:21', 0),
(280, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#45)', 'Resultado: 4-3 4-0.', '2025-12-17 00:15:21', 0),
(281, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#80)', 'Reserva #132 confirmada (club) en 2025-12-17 10:00:00-11:00:00.', '2025-12-17 00:29:27', 0),
(282, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#80)', 'Reserva #132 confirmada (club) en 2025-12-17 10:00:00-11:00:00.', '2025-12-17 00:29:27', 0),
(283, 1, 'pago_confirmado', 'recepcion', 'Pago confirmado (#81)', 'Reserva #133 confirmada (club) en 2025-12-17 08:00:00-10:00:00.', '2025-12-17 00:29:33', 0),
(284, 20, 'pago_confirmado', 'recepcion', 'Pago confirmado (#81)', 'Reserva #133 confirmada (club) en 2025-12-17 08:00:00-10:00:00.', '2025-12-17 00:29:33', 0),
(285, 57, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Tilin\" en el club Proveedor123.', '2025-12-17 13:56:27', 0),
(286, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Tilin\"', 'Usuario se inscribió en tu torneo.', '2025-12-17 13:56:27', 0),
(287, 57, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"xd\" en el club Proveedor123.', '2025-12-17 13:56:51', 0),
(288, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"xd\"', 'Usuario se inscribió en tu torneo.', '2025-12-17 13:56:51', 0),
(289, 57, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Tilin\" en el club Proveedor123.', '2025-12-17 13:56:53', 0),
(290, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Tilin\"', 'Usuario se inscribió en tu torneo.', '2025-12-17 13:56:53', 0),
(291, 57, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Torneo del Naval\" en el club Proveedor123.', '2025-12-17 13:56:55', 0),
(292, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Torneo del Naval\"', 'Usuario se inscribió en tu torneo.', '2025-12-17 13:56:55', 0),
(293, 57, 'torneo', 'sistema', 'Inscripción confirmada', 'Te uniste al torneo \"Amistosos (auto)\" en el club Proveedor123.', '2025-12-17 13:59:15', 0),
(294, 20, 'torneo', 'sistema', 'Nuevo inscripto en \"Amistosos (auto)\"', 'Usuario se inscribió en tu torneo.', '2025-12-17 13:59:15', 0),
(295, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#66)', 'Resultado: 4-3 4-2.', '2025-12-19 20:28:21', 1),
(296, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#66)', 'Resultado: 4-3 4-2.', '2025-12-19 20:28:21', 0),
(297, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Belen Chejo\' envió una solicitud (#15).', '2025-12-17 20:58:58', 0),
(298, 1, 'solicitud_proveedor', 'sistema', 'Nueva solicitud de proveedor', 'El club \'Belen Chejo\' envió una solicitud (#16).', '2025-12-17 21:00:47', 0),
(299, 1, 'recepcionista_creado', 'sistema', 'Recepcionista creado', 'El proveedor #20 creó el recepcionista #92 (Tilin, tilin@gmail.com).', '2025-12-17 21:33:32', 0),
(300, 1, 'recepcionista_eliminado', 'sistema', 'Recepcionista eliminado', 'El proveedor #20 eliminó el recepcionista #92 (Tilin, tilin@gmail.com).', '2025-12-17 21:33:49', 0),
(301, 1, 'recepcionista_creado', 'sistema', 'Recepcionista creado', 'El proveedor #20 creó el recepcionista #93 (Tilin, tilin@gmail.com).', '2025-12-17 21:37:26', 0),
(302, 1, 'test', 'sistema', 'Test', 'Probando insert manual', '2025-12-17 21:41:32', 0),
(303, 1, 'recepcionista_eliminado', 'proveedor', 'Recepcionista eliminado', 'El proveedor #20 eliminó el recepcionista #93 (Tilin, tilin@gmail.com).', '2025-12-17 21:47:43', 0),
(304, 1, 'recepcionista_creado', 'proveedor', 'Recepcionista creado', 'El proveedor #20 creó el recepcionista #94 (Tilin, gerson@gmail.com).', '2025-12-17 21:48:29', 0),
(305, 1, 'recepcionista_eliminado', 'proveedor', 'Recepcionista eliminado', 'El proveedor #20 eliminó el recepcionista #94 (Tilin, gerson@gmail.com).', '2025-12-17 21:48:41', 0),
(306, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"xd\" para el proveedor #20 (vigencia 2025-12-20 a 2025-12-27).', '2025-12-17 23:37:06', 0),
(307, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"xd\" para el proveedor #20 (vigencia 2025-12-20 a 2025-12-27).', '2025-12-17 23:37:06', 0),
(308, 1, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"xd\" del proveedor #20.', '2025-12-17 23:38:46', 0),
(309, 21, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"xd\" del proveedor #20.', '2025-12-17 23:38:46', 0),
(310, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Cristian\" para el proveedor #20 (vigencia 2025-12-25 a 2025-12-31).', '2025-12-17 23:39:09', 0),
(311, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Cristian\" para el proveedor #20 (vigencia 2025-12-25 a 2025-12-31).', '2025-12-17 23:39:09', 0),
(312, 1, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"Cristian\" del proveedor #20.', '2025-12-17 23:39:53', 0),
(313, 21, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"Cristian\" del proveedor #20.', '2025-12-17 23:39:53', 0),
(314, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Cristian Ronald\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:41:38', 0),
(315, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Cristian Ronald\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:41:38', 0),
(316, 1, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"Cristian Ronald\" del proveedor #20.', '2025-12-17 23:49:10', 0),
(317, 21, 'promocion', 'proveedor', 'Promoción eliminada', 'Se eliminó la promoción \"Cristian Ronald\" del proveedor #20.', '2025-12-17 23:49:10', 0),
(318, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:49:33', 0),
(319, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:49:33', 0),
(320, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias2\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:51:52', 0),
(321, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias2\" para el proveedor #20 (vigencia 2025-12-18 a 2025-12-25).', '2025-12-17 23:51:52', 0),
(322, 1, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias3\" para el proveedor #20 (vigencia 2025-12-19 a 2025-12-26).', '2025-12-17 23:52:56', 0),
(323, 21, 'promocion', 'proveedor', 'Nueva promoción creada', 'Se creó la promoción \"Matias3\" para el proveedor #20 (vigencia 2025-12-19 a 2025-12-26).', '2025-12-17 23:52:56', 0),
(324, 77, 'torneo_partido', 'sistema', 'Partido programado de torneo', 'Tu partido del torneo \"Torneo Individual - Navidad 2025 (P20)\" fue programado para el 2025-12-20 de 16:00 a 17:00.', '2025-12-18 08:47:51', 0),
(325, 80, 'torneo_partido', 'sistema', 'Partido programado de torneo', 'Tu partido del torneo \"Torneo Individual - Navidad 2025 (P20)\" fue programado para el 2025-12-20 de 16:00 a 17:00.', '2025-12-18 08:47:51', 0),
(326, 79, 'torneo_partido', 'sistema', 'Partido programado de torneo', 'Tu partido del torneo \"Torneo Individual - Navidad 2025 (P20)\" fue programado para el 2025-12-21 de 16:00 a 17:00.', '2025-12-18 08:47:51', 0),
(327, 78, 'torneo_partido', 'sistema', 'Partido programado de torneo', 'Tu partido del torneo \"Torneo Individual - Navidad 2025 (P20)\" fue programado para el 2025-12-21 de 16:00 a 17:00.', '2025-12-18 08:47:51', 0),
(328, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#108)', 'Resultado: 4-3 4-2.', '2025-12-30 22:11:10', 0),
(329, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#108)', 'Resultado: 4-3 4-2.', '2025-12-30 22:11:10', 0),
(330, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#109)', 'Resultado: 4-3 4-2.', '2025-12-31 22:11:52', 0),
(331, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#109)', 'Resultado: 4-3 4-2.', '2025-12-31 22:11:52', 0);
INSERT INTO `notificaciones` (`notificacion_id`, `usuario_id`, `tipo`, `origen`, `titulo`, `mensaje`, `creada_en`, `leida`) VALUES
(332, 1, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#110)', 'Resultado: 4-3 4-2.', '2026-01-04 23:12:32', 0),
(333, 20, 'resultado_nuevo', 'recepcion', 'Resultado cargado (#110)', 'Resultado: 4-3 4-2.', '2026-01-04 23:12:32', 0),
(334, 71, 'torneo_ganado', 'recepcion', '🏆 ¡Ganaste Fin de Año 2025 (P20)!', 'Felicitaciones, ganaste el torneo y recibiste 200 puntos.', '2026-01-04 23:12:32', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `pago_id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('mercado_pago','tarjeta','club') NOT NULL DEFAULT 'club',
  `referencia_gateway` varchar(100) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `estado` enum('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  `fecha_pago` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`pago_id`, `reserva_id`, `jugador_id`, `monto`, `metodo`, `referencia_gateway`, `detalle`, `estado`, `fecha_pago`) VALUES
(85, 144, 57, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 15:00:00'),
(86, 145, 57, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-24 16:00:00'),
(87, 146, 57, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-25 17:00:00'),
(88, 147, 57, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-26 18:00:00'),
(89, 148, 57, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-27 19:00:00'),
(90, 149, 57, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-28 20:00:00'),
(91, 150, 57, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-29 09:00:00'),
(92, 151, 57, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 10:00:00'),
(93, 152, 71, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 09:00:00'),
(94, 153, 57, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 11:00:00'),
(95, 154, 72, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 09:00:00'),
(96, 155, 71, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-18 10:00:00'),
(97, 156, 57, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-19 12:00:00'),
(98, 157, 73, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 09:00:00'),
(99, 158, 72, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-18 10:00:00'),
(100, 159, 71, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-19 11:00:00'),
(101, 160, 57, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-20 13:00:00'),
(102, 161, 74, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 09:00:00'),
(103, 162, 73, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 10:00:00'),
(104, 163, 72, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(105, 164, 71, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(106, 165, 57, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 14:00:00'),
(107, 166, 75, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 09:00:00'),
(108, 167, 74, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-18 10:00:00'),
(109, 168, 73, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-19 11:00:00'),
(110, 169, 72, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-20 12:00:00'),
(111, 170, 71, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-21 13:00:00'),
(112, 171, 57, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-22 15:00:00'),
(113, 172, 76, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 09:00:00'),
(114, 173, 75, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-18 10:00:00'),
(115, 174, 74, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-19 11:00:00'),
(116, 175, 73, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-20 12:00:00'),
(117, 176, 72, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-21 13:00:00'),
(118, 177, 71, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-22 14:00:00'),
(119, 178, 57, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-23 16:00:00'),
(120, 179, 77, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 09:00:00'),
(121, 180, 76, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 10:00:00'),
(122, 181, 75, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(123, 182, 74, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(124, 183, 73, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(125, 184, 72, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(126, 185, 71, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(127, 186, 78, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 09:00:00'),
(128, 187, 77, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 10:00:00'),
(129, 188, 76, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 11:00:00'),
(130, 189, 75, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 12:00:00'),
(131, 190, 74, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 13:00:00'),
(132, 191, 73, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 14:00:00'),
(133, 192, 72, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 15:00:00'),
(134, 193, 71, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-24 16:00:00'),
(135, 194, 79, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 09:00:00'),
(136, 195, 78, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 10:00:00'),
(137, 196, 77, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(138, 197, 76, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(139, 198, 75, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(140, 199, 74, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(141, 200, 73, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(142, 201, 72, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(143, 202, 71, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(144, 203, 80, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 09:00:00'),
(145, 204, 79, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 10:00:00'),
(146, 205, 78, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(147, 206, 77, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(148, 207, 76, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(149, 208, 75, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(150, 209, 74, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(151, 210, 73, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(152, 211, 72, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(153, 212, 71, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(154, 213, 80, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 10:00:00'),
(155, 214, 79, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(156, 215, 78, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(157, 216, 77, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(158, 217, 76, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(159, 218, 75, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(160, 219, 74, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(161, 220, 73, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(162, 221, 72, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(163, 222, 71, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(164, 223, 80, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 11:00:00'),
(165, 224, 79, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(166, 225, 78, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(167, 226, 77, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(168, 227, 76, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(169, 228, 75, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(170, 229, 74, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(171, 230, 73, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(172, 231, 72, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(173, 232, 71, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(174, 233, 80, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 12:00:00'),
(175, 234, 79, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(176, 235, 78, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(177, 236, 77, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(178, 237, 76, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(179, 238, 75, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(180, 239, 74, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(181, 240, 73, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(182, 241, 72, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(183, 242, 71, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(184, 243, 80, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 13:00:00'),
(185, 244, 79, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(186, 245, 78, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(187, 246, 77, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(188, 247, 76, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(189, 248, 75, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(190, 249, 74, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(191, 250, 73, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(192, 251, 72, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(193, 252, 71, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(194, 253, 80, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 14:00:00'),
(195, 254, 79, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(196, 255, 78, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(197, 256, 77, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(198, 257, 76, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(199, 258, 75, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(200, 259, 74, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(201, 260, 73, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(202, 261, 72, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(203, 262, 71, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(204, 263, 80, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 15:00:00'),
(205, 264, 79, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(206, 265, 78, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(207, 266, 77, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(208, 267, 76, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(209, 268, 75, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(210, 269, 74, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(211, 270, 73, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(212, 271, 72, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(213, 272, 71, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(214, 273, 80, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-24 16:00:00'),
(215, 274, 79, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(216, 275, 78, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(217, 276, 77, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(218, 277, 76, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(219, 278, 75, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(220, 279, 74, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(221, 280, 73, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(222, 281, 72, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(223, 282, 71, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(224, 283, 80, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-25 17:00:00'),
(225, 284, 79, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(226, 285, 78, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(227, 286, 77, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(228, 287, 76, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(229, 288, 75, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(230, 289, 74, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(231, 290, 73, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(232, 291, 72, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(233, 292, 71, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(234, 293, 80, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-26 18:00:00'),
(235, 294, 79, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(236, 295, 78, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(237, 296, 77, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(238, 297, 76, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(239, 298, 75, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(240, 299, 74, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(241, 300, 73, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(242, 301, 72, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(243, 302, 71, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(244, 303, 80, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-27 19:00:00'),
(245, 304, 79, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(246, 305, 78, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(247, 306, 77, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(248, 307, 76, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(249, 308, 75, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(250, 309, 74, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(251, 310, 73, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(252, 311, 72, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(253, 312, 71, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(254, 313, 80, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-28 20:00:00'),
(255, 314, 79, 5000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-29 09:00:00'),
(256, 315, 78, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(257, 316, 77, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(258, 317, 76, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(259, 318, 75, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(260, 319, 74, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(261, 320, 73, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(262, 321, 72, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(263, 322, 80, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-29 09:00:00'),
(264, 323, 79, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-17 10:00:00'),
(265, 324, 78, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 11:00:00'),
(266, 325, 77, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 12:00:00'),
(267, 326, 76, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 13:00:00'),
(268, 327, 75, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 14:00:00'),
(269, 328, 74, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 15:00:00'),
(270, 329, 73, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 16:00:00'),
(271, 330, 80, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-17 10:00:00'),
(272, 331, 79, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(273, 332, 78, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(274, 333, 77, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(275, 334, 76, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(276, 335, 75, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(277, 336, 74, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(278, 337, 80, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-18 11:00:00'),
(279, 338, 79, 4000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-19 12:00:00'),
(280, 339, 78, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(281, 340, 77, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(282, 341, 76, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(283, 342, 75, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(284, 343, 80, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 12:00:00'),
(285, 344, 79, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 13:00:00'),
(286, 345, 78, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-21 14:00:00'),
(287, 346, 77, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 15:00:00'),
(288, 347, 76, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 16:00:00'),
(289, 348, 80, 4500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-20 13:00:00'),
(290, 349, 79, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(291, 350, 78, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(292, 351, 77, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(293, 352, 80, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-21 14:00:00'),
(294, 353, 79, 5500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-22 15:00:00'),
(295, 354, 78, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(296, 355, 80, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-22 15:00:00'),
(297, 356, 79, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-23 16:00:00'),
(298, 357, 80, 6000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-23 16:00:00'),
(299, 358, 57, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-17 09:00:00'),
(300, 359, 57, 4500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-18 10:00:00'),
(301, 360, 57, 5000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-19 11:00:00'),
(302, 361, 57, 5500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-20 12:00:00'),
(303, 362, 57, 6000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-21 13:00:00'),
(304, 363, 57, 4000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-22 14:00:00'),
(340, 462, 57, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 09:00:00'),
(341, 480, 77, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 15:00:00'),
(342, 498, 73, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 09:00:00'),
(343, 463, 71, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 10:00:00'),
(344, 481, 78, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 16:00:00'),
(345, 499, 74, 12000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 10:00:00'),
(346, 464, 72, 12000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 11:00:00'),
(347, 482, 79, 12000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 17:00:00'),
(348, 500, 75, 12000.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 11:00:00'),
(349, 465, 73, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 12:00:00'),
(350, 483, 80, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 18:00:00'),
(351, 501, 76, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 12:00:00'),
(352, 466, 74, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 13:00:00'),
(353, 484, 57, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 19:00:00'),
(354, 502, 77, 12500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 13:00:00'),
(355, 467, 75, 12500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 14:00:00'),
(356, 485, 71, 12500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 20:00:00'),
(357, 503, 78, 12500.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 14:00:00'),
(358, 468, 76, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 15:00:00'),
(359, 486, 72, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 09:00:00'),
(360, 504, 79, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 15:00:00'),
(361, 469, 77, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 16:00:00'),
(362, 487, 73, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 10:00:00'),
(363, 505, 80, 14500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 16:00:00'),
(364, 470, 78, 14800.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 17:00:00'),
(365, 488, 74, 14800.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 11:00:00'),
(366, 506, 57, 14800.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 17:00:00'),
(367, 471, 79, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 18:00:00'),
(368, 489, 75, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 12:00:00'),
(369, 507, 71, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 18:00:00'),
(370, 472, 80, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 19:00:00'),
(371, 490, 76, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 13:00:00'),
(372, 508, 72, 15000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 19:00:00'),
(373, 473, 57, 15200.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 20:00:00'),
(374, 491, 77, 15200.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 14:00:00'),
(375, 509, 73, 15200.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 20:00:00'),
(376, 474, 71, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 09:00:00'),
(377, 492, 78, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 15:00:00'),
(378, 510, 74, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 09:00:00'),
(379, 475, 72, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 10:00:00'),
(380, 493, 79, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 16:00:00'),
(381, 511, 75, 16500.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 10:00:00'),
(382, 476, 73, 16800.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 11:00:00'),
(383, 494, 80, 16800.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 17:00:00'),
(384, 477, 74, 17000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 12:00:00'),
(385, 495, 57, 17000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa, Mauricio Vargas', 'pagado', '2025-12-18 18:00:00'),
(386, 478, 75, 17000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 13:00:00'),
(387, 496, 71, 17000.00, 'club', NULL, 'Cristian Chejo, Matias Sirpa', 'pagado', '2025-12-19 19:00:00'),
(388, 479, 76, 17200.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 14:00:00'),
(389, 497, 72, 17200.00, 'club', NULL, 'Cristian Chejo', 'pagado', '2025-12-20 20:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participaciones`
--

CREATE TABLE `participaciones` (
  `participacion_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `reserva_id` int(11) DEFAULT NULL,
  `torneo_id` int(11) DEFAULT NULL,
  `es_creador` tinyint(1) DEFAULT 0,
  `estado` enum('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'aceptada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `participaciones`
--

INSERT INTO `participaciones` (`participacion_id`, `jugador_id`, `reserva_id`, `torneo_id`, `es_creador`, `estado`) VALUES
(54, 71, NULL, 31, 0, 'aceptada'),
(55, 72, NULL, 31, 0, 'aceptada'),
(56, 73, NULL, 31, 0, 'aceptada'),
(57, 74, NULL, 31, 0, 'aceptada'),
(58, 75, NULL, 32, 0, 'aceptada'),
(59, 76, NULL, 32, 0, 'aceptada'),
(60, 77, NULL, 32, 0, 'aceptada'),
(61, 78, NULL, 32, 0, 'aceptada'),
(62, 79, NULL, 33, 0, 'aceptada'),
(63, 80, NULL, 33, 0, 'aceptada'),
(64, 57, NULL, 33, 0, 'aceptada'),
(65, 71, NULL, 33, 0, 'aceptada'),
(66, 72, NULL, 34, 0, 'aceptada'),
(67, 73, NULL, 34, 0, 'aceptada'),
(68, 74, NULL, 34, 0, 'aceptada'),
(69, 75, NULL, 34, 0, 'aceptada'),
(70, 76, NULL, 35, 0, 'aceptada'),
(71, 77, NULL, 35, 0, 'aceptada'),
(72, 78, NULL, 35, 0, 'aceptada'),
(73, 79, NULL, 35, 0, 'aceptada'),
(78, 71, NULL, 47, 0, 'aceptada'),
(79, 72, NULL, 47, 0, 'aceptada'),
(80, 73, NULL, 47, 0, 'aceptada'),
(81, 74, NULL, 47, 0, 'aceptada'),
(82, 75, NULL, 48, 0, 'aceptada'),
(83, 76, NULL, 48, 0, 'aceptada'),
(84, 77, NULL, 48, 0, 'aceptada'),
(85, 78, NULL, 48, 0, 'aceptada'),
(86, 79, NULL, 49, 0, 'aceptada'),
(87, 80, NULL, 49, 0, 'aceptada'),
(88, 57, NULL, 49, 0, 'aceptada'),
(89, 71, NULL, 49, 0, 'aceptada'),
(90, 72, NULL, 50, 0, 'aceptada'),
(91, 73, NULL, 50, 0, 'aceptada'),
(92, 74, NULL, 50, 0, 'aceptada'),
(93, 75, NULL, 50, 0, 'aceptada'),
(94, 76, NULL, 51, 0, 'aceptada'),
(95, 77, NULL, 51, 0, 'aceptada'),
(96, 78, NULL, 51, 0, 'aceptada'),
(97, 79, NULL, 51, 0, 'aceptada'),
(98, 80, NULL, 52, 0, 'aceptada'),
(99, 57, NULL, 52, 0, 'aceptada'),
(100, 71, NULL, 52, 0, 'aceptada'),
(101, 72, NULL, 52, 0, 'aceptada'),
(102, 73, NULL, 53, 0, 'aceptada'),
(103, 74, NULL, 53, 0, 'aceptada'),
(104, 75, NULL, 53, 0, 'aceptada'),
(105, 76, NULL, 53, 0, 'aceptada'),
(110, 57, NULL, 55, 0, 'aceptada'),
(111, 71, NULL, 55, 0, 'aceptada'),
(112, 72, NULL, 55, 0, 'aceptada'),
(113, 73, NULL, 55, 0, 'aceptada'),
(114, 74, NULL, 56, 0, 'aceptada'),
(115, 75, NULL, 56, 0, 'aceptada'),
(116, 76, NULL, 56, 0, 'aceptada'),
(117, 77, NULL, 56, 0, 'aceptada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

CREATE TABLE `partidos` (
  `partido_id` int(11) NOT NULL,
  `torneo_id` int(11) DEFAULT NULL,
  `ronda` int(11) NOT NULL DEFAULT 1,
  `idx_ronda` int(11) NOT NULL DEFAULT 0,
  `next_partido_id` int(11) DEFAULT NULL,
  `next_pos` enum('j1','j2') DEFAULT NULL,
  `jugador1_id` int(11) DEFAULT NULL,
  `jugador2_id` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `resultado` varchar(50) DEFAULT NULL,
  `ganador_id` int(11) DEFAULT NULL,
  `reserva_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`partido_id`, `torneo_id`, `ronda`, `idx_ronda`, `next_partido_id`, `next_pos`, `jugador1_id`, `jugador2_id`, `fecha`, `resultado`, `ganador_id`, `reserva_id`) VALUES
(66, 31, 1, 1, 68, 'j1', 71, 72, '2025-12-19 19:00:00', '4-3 4-2', 71, NULL),
(67, 31, 1, 2, 68, 'j2', 73, 74, '2025-12-20 19:00:00', NULL, NULL, NULL),
(68, 31, 2, 1, NULL, NULL, 71, NULL, '2025-12-23 20:00:00', NULL, NULL, NULL),
(69, 32, 1, 1, 71, 'j1', 75, 76, '2025-12-19 20:00:00', NULL, NULL, NULL),
(70, 32, 1, 2, 71, 'j2', 77, 78, '2025-12-20 20:00:00', NULL, NULL, NULL),
(71, 32, 2, 1, NULL, NULL, NULL, NULL, '2025-12-23 21:00:00', NULL, NULL, NULL),
(72, 33, 1, 1, 74, 'j1', 79, 80, '2025-12-19 18:30:00', NULL, NULL, NULL),
(73, 33, 1, 2, 74, 'j2', 57, 71, '2025-12-20 18:30:00', NULL, NULL, NULL),
(74, 33, 2, 1, NULL, NULL, NULL, NULL, '2025-12-23 19:30:00', NULL, NULL, NULL),
(75, 34, 1, 1, 77, 'j1', 72, 73, '2025-12-19 21:00:00', NULL, NULL, NULL),
(76, 34, 1, 2, 77, 'j2', 74, 75, '2025-12-20 21:00:00', NULL, NULL, NULL),
(77, 34, 2, 1, NULL, NULL, NULL, NULL, '2025-12-23 22:00:00', NULL, NULL, NULL),
(78, 35, 1, 1, 80, 'j1', 76, 77, '2025-12-19 19:30:00', NULL, NULL, NULL),
(79, 35, 1, 2, 80, 'j2', 78, 79, '2025-12-20 19:30:00', NULL, NULL, NULL),
(80, 35, 2, 1, NULL, NULL, NULL, NULL, '2025-12-23 20:30:00', NULL, NULL, NULL),
(84, 47, 1, 1, 86, 'j1', 71, 72, '2025-09-03 20:00:00', '6-3 6-4', 71, NULL),
(85, 47, 1, 2, 86, 'j2', 73, 74, '2025-09-04 20:00:00', '4-6 6-3 6-4', 74, NULL),
(86, 47, 2, 1, NULL, NULL, 71, 74, '2025-09-07 20:30:00', '6-2 6-2', 71, NULL),
(87, 48, 1, 1, 89, 'j1', 75, 76, '2025-09-17 19:30:00', '6-4 6-4', 75, NULL),
(88, 48, 1, 2, 89, 'j2', 77, 78, '2025-09-18 19:30:00', '3-6 6-3 7-5', 78, NULL),
(89, 48, 2, 1, NULL, NULL, 75, 78, '2025-09-21 20:00:00', '6-7 6-3 6-1', 75, NULL),
(90, 49, 1, 1, 92, 'j1', 79, 80, '2025-10-08 20:00:00', '6-2 6-3', 79, NULL),
(91, 49, 1, 2, 92, 'j2', 57, 71, '2025-10-09 20:00:00', '6-4 3-6 6-4', 57, NULL),
(92, 49, 2, 1, NULL, NULL, 79, 57, '2025-10-12 20:30:00', '4-6 6-4 6-3', 57, NULL),
(93, 50, 1, 1, 95, 'j1', 72, 73, '2025-10-22 19:00:00', '6-1 6-2', 72, NULL),
(94, 50, 1, 2, 95, 'j2', 74, 75, '2025-10-23 19:00:00', '5-7 6-4 6-2', 74, NULL),
(95, 50, 2, 1, NULL, NULL, 72, 74, '2025-10-26 20:00:00', '6-4 6-4', 72, NULL),
(96, 51, 1, 1, 98, 'j1', 76, 77, '2025-11-05 20:00:00', '6-4 6-7 6-3', 76, NULL),
(97, 51, 1, 2, 98, 'j2', 78, 79, '2025-11-06 20:00:00', '6-3 6-3', 78, NULL),
(98, 51, 2, 1, NULL, NULL, 76, 78, '2025-11-09 20:30:00', '6-2 3-6 6-4', 76, NULL),
(99, 52, 1, 1, 101, 'j1', 80, 57, '2025-11-19 19:00:00', '6-3 6-4', 80, NULL),
(100, 52, 1, 2, 101, 'j2', 71, 72, '2025-11-20 19:00:00', '2-6 6-3 6-4', 71, NULL),
(101, 52, 2, 1, NULL, NULL, 80, 71, '2025-11-23 20:00:00', '7-5 6-4', 80, NULL),
(102, 53, 1, 1, 104, 'j1', 73, 74, '2025-12-14 20:00:00', '6-4 6-2', 73, NULL),
(103, 53, 1, 2, 104, 'j2', 75, 76, '2025-12-15 20:00:00', '4-6 6-4 6-3', 75, NULL),
(104, 53, 2, 1, NULL, NULL, 73, 75, '2025-12-19 21:00:00', NULL, NULL, NULL),
(108, 55, 1, 1, 110, 'j1', 57, 71, '2025-12-30 20:00:00', '4-3 4-2', 71, NULL),
(109, 55, 1, 2, 110, 'j2', 72, 73, '2025-12-31 20:00:00', '4-3 4-2', 73, NULL),
(110, 55, 2, 1, NULL, NULL, 71, 73, '2026-01-03 21:00:00', '4-3 4-2', 71, NULL),
(111, 56, 1, 1, 113, 'j1', 74, 75, '2026-01-07 20:00:00', NULL, NULL, NULL),
(112, 56, 1, 2, 113, 'j2', 76, 77, '2026-01-08 20:00:00', NULL, NULL, NULL),
(113, 56, 2, 1, NULL, NULL, NULL, NULL, '2026-01-11 21:00:00', NULL, NULL, NULL),
(114, 57, 1, 0, 118, 'j1', NULL, NULL, '2025-12-21 18:00:00', NULL, NULL, 512),
(115, 57, 1, 1, 118, 'j2', NULL, NULL, '2025-12-22 18:00:00', NULL, NULL, 513),
(116, 57, 1, 2, 119, 'j1', NULL, NULL, '2025-12-23 18:00:00', NULL, NULL, 514),
(117, 57, 1, 3, 119, 'j2', NULL, NULL, '2025-12-24 18:00:00', NULL, NULL, 515),
(118, 57, 2, 0, 120, 'j1', NULL, NULL, '2025-12-25 18:00:00', NULL, NULL, 516),
(119, 57, 2, 1, 120, 'j2', NULL, NULL, '2025-12-26 18:00:00', NULL, NULL, 517),
(120, 57, 3, 0, NULL, NULL, NULL, NULL, '2025-12-27 18:00:00', NULL, NULL, 518);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `codigo` varchar(6) NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `creado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `promocion_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `dias_semana` set('1','2','3','4','5','6','7') DEFAULT NULL,
  `minima_reservas` int(11) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`promocion_id`, `proveedor_id`, `cancha_id`, `nombre`, `descripcion`, `porcentaje_descuento`, `fecha_inicio`, `fecha_fin`, `hora_inicio`, `hora_fin`, `dias_semana`, `minima_reservas`, `activa`) VALUES
(1, 20, 7, 'Happy Hour', 'Descuento especial por la tarde para aumentar la demanda.', 20.00, '2025-12-01', '2025-12-31', '14:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(2, 20, 7, 'Mañanas Activas', 'Promoción para incentivar las reservas a primera hora.', 15.00, '2025-12-01', '2026-01-31', '08:00:00', '11:00:00', '1,2,3,4,5,6', 0, 1),
(3, 62, 12, 'Fin de Semana', 'Descuento fuerte para completar cupo los sábados y domingos.', 30.00, '2025-12-10', '2026-02-10', NULL, NULL, '6,7', 0, 1),
(4, 20, NULL, 'Jugadores Frecuentes', 'Aplica solo para quienes ya reservaron 5 veces o más.', 10.00, '2025-12-01', '2026-03-01', NULL, NULL, NULL, 5, 1),
(5, 20, 11, 'Promo Octubre', 'Promoción anterior, ya expirada.', 25.00, '2025-10-01', '2025-10-31', NULL, NULL, NULL, 0, 1),
(6, 20, 7, 'Lunes del Club', 'Descuento exclusivo de los lunes en la cancha 7 (aplica todo el día).', 10.00, '2025-12-01', '2026-02-28', NULL, NULL, '1', 0, 1),
(7, 20, NULL, 'Madrugón', 'Beneficio por reservar bien temprano (06:00–10:00), de lunes a sábado.', 5.00, '2025-12-01', '2026-01-31', '06:00:00', '10:00:00', '1,2,3,4,5,6', 0, 1),
(8, 20, 7, 'Tarde Plus', 'Extra en la tarde para la cancha 7 (15:30–18:30, lun a vie).', 10.00, '2025-12-01', '2025-12-31', '15:30:00', '18:30:00', '1,2,3,4,5', 0, 1),
(9, 20, NULL, 'Habituales', 'Descuento para clientes con 3 o más reservas previas.', 12.00, '2025-12-01', '2026-03-01', NULL, NULL, NULL, 3, 1),
(10, 20, 12, 'Sábado Family', 'Descuento sábado por la mañana en la cancha 12 (09:00–13:00).', 5.00, '2025-12-01', '2026-02-28', '09:00:00', '13:00:00', '6', 0, 1),
(11, 20, NULL, 'Viernes negro', 'Como sabemos, los viernes ahora son viernes de promoción', 10.00, '2025-12-15', '2025-12-24', '12:40:00', '12:36:00', '1', 10000, 1),
(12, 20, 7, 'Happy Hour 20', 'Descuento tarde para mover demanda (14:00–17:00, Lun–Vie).', 18.00, '2025-12-16', '2026-03-31', '14:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(13, 20, 8, 'Mañanas Activas 20', 'Promo mañanas (08:00–11:00, Lun–Sáb).', 12.00, '2025-12-16', '2026-03-31', '08:00:00', '11:00:00', '1,2,3,4,5,6', 0, 1),
(14, 20, 11, 'Panorámica Prime 20', 'Promo media tarde panorámica (16:00–19:00, Mar–Jue).', 10.00, '2025-12-16', '2026-03-31', '16:00:00', '19:00:00', '2,3,4', 0, 1),
(15, 20, 12, 'Sábado Family 20', 'Sábados mañana (09:00–13:00).', 7.00, '2025-12-16', '2026-03-31', '09:00:00', '13:00:00', '6', 0, 1),
(16, 20, NULL, 'Habituales 20', 'Descuento para clientes con 3+ reservas previas.', 10.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 3, 1),
(17, 20, NULL, 'Frecuentes 20', 'Descuento para clientes con 5+ reservas previas.', 12.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 5, 1),
(18, 62, 27, 'After Office 62', 'Promo post-laburo (18:00–20:00, Lun–Vie).', 8.00, '2025-12-16', '2026-03-31', '18:00:00', '20:00:00', '1,2,3,4,5', 0, 1),
(19, 62, 28, 'Tempranito 62', 'Promo mañana (08:00–10:30, Lun–Vie).', 10.00, '2025-12-16', '2026-03-31', '08:00:00', '10:30:00', '1,2,3,4,5', 0, 1),
(20, 62, 27, 'Miércoles -12%', 'Descuento fijo miércoles (todo el día).', 12.00, '2025-12-16', '2026-03-31', NULL, NULL, '3', 0, 1),
(21, 62, 28, 'Domingo Relax', 'Domingo tarde (16:00–22:00).', 9.00, '2025-12-16', '2026-03-31', '16:00:00', '22:00:00', '7', 0, 1),
(22, 62, NULL, 'Habituales 62', 'Descuento para clientes con 2+ reservas previas.', 8.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 2, 1),
(23, 62, NULL, 'VIP 62', 'Descuento para clientes con 6+ reservas previas.', 14.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 6, 1),
(24, 63, 38, 'Mañana Pro 63', '08:00–11:00 (Lun–Vie).', 10.00, '2025-12-16', '2026-03-31', '08:00:00', '11:00:00', '1,2,3,4,5', 0, 1),
(25, 63, 39, 'Siesta 63', '13:00–16:00 (Lun–Vie).', 12.00, '2025-12-16', '2026-03-31', '13:00:00', '16:00:00', '1,2,3,4,5', 0, 1),
(26, 63, 40, 'Noche Cubierta 63', '20:00–23:00 (Mar–Jue).', 8.00, '2025-12-16', '2026-03-31', '20:00:00', '23:00:00', '2,3,4', 0, 1),
(27, 63, 41, 'Panorámica Weekend', 'Fin de semana (todo el día).', 9.00, '2025-12-16', '2026-03-31', NULL, NULL, '6,7', 0, 1),
(28, 63, NULL, 'Habituales 63', 'Descuento para clientes con 3+ reservas previas.', 9.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 3, 1),
(29, 63, NULL, 'Frecuentes 63', 'Descuento para clientes con 7+ reservas previas.', 13.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 7, 1),
(30, 64, 44, 'Lunes del Club 64', 'Lunes (todo el día) para Cancha 1.', 10.00, '2025-12-16', '2026-03-31', NULL, NULL, '1', 0, 1),
(31, 64, 45, 'Mañanas 64', '08:00–11:00 (Lun–Sáb).', 8.00, '2025-12-16', '2026-03-31', '08:00:00', '11:00:00', '1,2,3,4,5,6', 0, 1),
(32, 64, 47, 'Cubierta OffPeak', '12:00–16:00 (Lun–Vie).', 11.00, '2025-12-16', '2026-03-31', '12:00:00', '16:00:00', '1,2,3,4,5', 0, 1),
(33, 64, 50, 'Panorámica Prime', '18:30–21:30 (Lun–Vie).', 6.00, '2025-12-16', '2026-03-31', '18:30:00', '21:30:00', '1,2,3,4,5', 0, 1),
(34, 64, NULL, 'Habituales 64', 'Descuento para clientes con 4+ reservas previas.', 9.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 4, 1),
(35, 64, NULL, 'Frecuentes 64', 'Descuento para clientes con 8+ reservas previas.', 14.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 8, 1),
(36, 65, 55, 'Clásica Mañana 65', '08:00–11:00 (Lun–Vie).', 10.00, '2025-12-16', '2026-03-31', '08:00:00', '11:00:00', '1,2,3,4,5', 0, 1),
(37, 65, 57, 'Cubierta Siesta 65', '12:00–16:00 (Lun–Vie).', 12.00, '2025-12-16', '2026-03-31', '12:00:00', '16:00:00', '1,2,3,4,5', 0, 1),
(38, 65, 58, 'Viernes 2x', 'Viernes tarde (17:00–20:00).', 9.00, '2025-12-16', '2026-03-31', '17:00:00', '20:00:00', '5', 0, 1),
(39, 65, 59, 'Panorámica Night', '20:00–22:00 (Mar–Jue).', 7.00, '2025-12-16', '2026-03-31', '20:00:00', '22:00:00', '2,3,4', 0, 1),
(40, 65, NULL, 'Habituales 65', 'Descuento para clientes con 2+ reservas previas.', 8.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 2, 1),
(41, 65, NULL, 'Frecuentes 65', 'Descuento para clientes con 6+ reservas previas.', 13.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 6, 1),
(42, 66, 62, 'Mañanas Urban', '09:00–12:00 (Lun–Vie).', 9.00, '2025-12-16', '2026-03-31', '09:00:00', '12:00:00', '1,2,3,4,5', 0, 1),
(43, 66, 63, 'After Office Urban', '18:00–21:00 (Lun–Vie).', 7.00, '2025-12-16', '2026-03-31', '18:00:00', '21:00:00', '1,2,3,4,5', 0, 1),
(44, 66, 62, 'Sábado Full', 'Sábados (todo el día).', 6.00, '2025-12-16', '2026-03-31', NULL, NULL, '6', 0, 1),
(45, 66, 63, 'Domingo Soft', 'Domingos (16:00–23:00).', 8.00, '2025-12-16', '2026-03-31', '16:00:00', '23:00:00', '7', 0, 1),
(46, 66, NULL, 'Habituales 66', 'Descuento para clientes con 3+ reservas previas.', 9.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 3, 1),
(47, 66, NULL, 'Frecuentes 66', 'Descuento para clientes con 7+ reservas previas.', 13.00, '2025-12-16', '2026-03-31', NULL, NULL, NULL, 7, 1),
(48, 20, 269, 'Semana Pre-Navidad A1', 'Descuento por semana completa (turnos diurnos).', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(49, 20, 270, 'After Office A2', 'Promo nocturna de lunes a jueves.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(50, 20, 271, 'Madrugadores A3', 'Promo mañana (arranca temprano).', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(51, 20, 275, 'Cubierta C1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(52, 20, 281, 'Panorámica P1 Prime', 'Descuento en horario prime.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(53, 20, 286, 'Panorámica Q3 Pack 2', 'Activación por mínima de reservas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(54, 20, NULL, 'Promo Club Navidad', 'Promo global del club (todas las canchas).', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(55, 20, 272, 'Clásica B1 Tarde', 'Promo tarde para llenar huecos.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(56, 20, 278, 'Cubierta D1 Últimos Cupos', 'Promo corta semanal, todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(57, 20, 284, 'Panorámica Q1 Pre-Fiestas', 'Promo semanal en panorámica.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(58, 86, 206, 'Semana Bombonera Clásica 1', 'Promo semanal diurna.', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(59, 86, 207, 'After Office Clásica 2', 'Promo nocturna Lun-Jue.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(60, 86, 208, 'Mañanas Clásica 3', 'Descuento por turnos tempranos.', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(61, 86, 209, 'Cubierta 1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(62, 86, 210, 'Cubierta 2 Prime', 'Horario prime Vie-Dom.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(63, 86, 211, 'Cubierta 3 Pack 2', 'Requiere 2 reservas mínimas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(64, 86, NULL, 'Promo Club Bombonera', 'Promo global del proveedor.', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(65, 86, 212, 'Panorámica 1 Tarde', 'Promo tarde Lun-Vie.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(66, 86, 213, 'Panorámica 2 Últimos Cupos', 'Promo semanal todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(67, 86, 214, 'Panorámica 3 Pre-Fiestas', 'Semana completa con descuento.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(68, 87, 215, 'Semana Monumental Clásica 1', 'Promo semanal diurna.', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(69, 87, 216, 'After Office Clásica 2', 'Promo nocturna Lun-Jue.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(70, 87, 217, 'Mañanas Clásica 3', 'Descuento por turnos tempranos.', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(71, 87, 218, 'Cubierta 1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(72, 87, 219, 'Cubierta 2 Prime', 'Horario prime Vie-Dom.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(73, 87, 220, 'Cubierta 3 Pack 2', 'Requiere 2 reservas mínimas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(74, 87, NULL, 'Promo Club Monumental', 'Promo global del proveedor.', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(75, 87, 221, 'Panorámica 1 Tarde', 'Promo tarde Lun-Vie.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(76, 87, 222, 'Panorámica 2 Últimos Cupos', 'Promo semanal todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(77, 87, 223, 'Panorámica 3 Pre-Fiestas', 'Semana completa con descuento.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(78, 88, 224, 'Semana Cilindro Clásica 1', 'Promo semanal diurna.', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(79, 88, 225, 'After Office Clásica 2', 'Promo nocturna Lun-Jue.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(80, 88, 226, 'Mañanas Clásica 3', 'Descuento por turnos tempranos.', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(81, 88, 227, 'Cubierta 1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(82, 88, 228, 'Cubierta 2 Prime', 'Horario prime Vie-Dom.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(83, 88, 229, 'Cubierta 3 Pack 2', 'Requiere 2 reservas mínimas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(84, 88, NULL, 'Promo Club Cilindro', 'Promo global del proveedor.', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(85, 88, 230, 'Panorámica 1 Tarde', 'Promo tarde Lun-Vie.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(86, 88, 231, 'Panorámica 2 Últimos Cupos', 'Promo semanal todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(87, 88, 232, 'Panorámica 3 Pre-Fiestas', 'Semana completa con descuento.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(88, 89, 233, 'Semana Boedo Clásica 1', 'Promo semanal diurna.', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(89, 89, 234, 'After Office Clásica 2', 'Promo nocturna Lun-Jue.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(90, 89, 235, 'Mañanas Clásica 3', 'Descuento por turnos tempranos.', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(91, 89, 236, 'Cubierta 1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(92, 89, 237, 'Cubierta 2 Prime', 'Horario prime Vie-Dom.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(93, 89, 238, 'Cubierta 3 Pack 2', 'Requiere 2 reservas mínimas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(94, 89, NULL, 'Promo Club Boedo', 'Promo global del proveedor.', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(95, 89, 239, 'Panorámica 1 Tarde', 'Promo tarde Lun-Vie.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(96, 89, 240, 'Panorámica 2 Últimos Cupos', 'Promo semanal todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(97, 89, 241, 'Panorámica 3 Pre-Fiestas', 'Semana completa con descuento.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(98, 90, 242, 'Semana Ducó Clásica 1', 'Promo semanal diurna.', 15.00, '2025-12-17', '2025-12-23', '09:00:00', '18:00:00', '1,2,3,4,5', 0, 1),
(99, 90, 243, 'After Office Clásica 2', 'Promo nocturna Lun-Jue.', 20.00, '2025-12-18', '2025-12-24', '19:00:00', '22:00:00', '1,2,3,4', 0, 1),
(100, 90, 244, 'Mañanas Clásica 3', 'Descuento por turnos tempranos.', 18.00, '2025-12-19', '2025-12-25', '08:00:00', '11:00:00', '1,2,3,4,5,6,7', 0, 1),
(101, 90, 245, 'Cubierta 1 Full Week', 'Semana completa en cubierta.', 12.50, '2025-12-17', '2025-12-23', NULL, NULL, NULL, 0, 1),
(102, 90, 246, 'Cubierta 2 Prime', 'Horario prime Vie-Dom.', 10.00, '2025-12-18', '2025-12-24', '18:00:00', '23:00:00', '5,6,7', 0, 1),
(103, 90, 247, 'Cubierta 3 Pack 2', 'Requiere 2 reservas mínimas.', 22.00, '2025-12-19', '2025-12-25', '10:00:00', '16:00:00', '6,7', 2, 1),
(104, 90, NULL, 'Promo Club Ducó', 'Promo global del proveedor.', 8.00, '2025-12-17', '2025-12-23', NULL, NULL, '1,2,3,4,5,6,7', 0, 1),
(105, 90, 248, 'Panorámica 1 Tarde', 'Promo tarde Lun-Vie.', 14.00, '2025-12-18', '2025-12-24', '13:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(106, 90, 249, 'Panorámica 2 Últimos Cupos', 'Promo semanal todo horario.', 11.00, '2025-12-19', '2025-12-25', NULL, NULL, NULL, 0, 1),
(107, 90, 250, 'Panorámica 3 Pre-Fiestas', 'Semana completa con descuento.', 9.50, '2025-12-17', '2025-12-23', '08:00:00', '23:00:00', '1,2,3,4,5,6,7', 0, 1),
(111, 20, NULL, 'Matias', '123123123', 5.00, '2025-12-18', '2025-12-25', NULL, NULL, '2,5', 0, 1),
(112, 20, NULL, 'Matias2', '123123123', 4.00, '2025-12-18', '2025-12-25', NULL, NULL, '2,3,4,5', 0, 1),
(113, 20, 269, 'Matias3', '123123', 5.00, '2025-12-19', '2025-12-26', NULL, NULL, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores_detalle`
--

CREATE TABLE `proveedores_detalle` (
  `proveedor_id` int(11) NOT NULL,
  `nombre_club` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores_detalle`
--

INSERT INTO `proveedores_detalle` (`proveedor_id`, `nombre_club`, `telefono`, `direccion`, `ciudad`, `descripcion`, `barrio`, `estado`) VALUES
(20, 'Nombre del club XD', '11 5555-4232', 'Av Rivadavia 1223', 'Buenos Aires', '12313123123133123', 'Parque Avellaneda', 'aprobado'),
(86, 'Boca Juniors', '11-5000-2001', 'Brandsen 805', 'Buenos Aires', 'Proveedor de canchas y turnos para entrenamientos y amistosos.', 'La Boca', 'aprobado'),
(87, 'River Plate', '11-5000-2002', 'Av. Figueroa Alcorta 7597', 'Buenos Aires', 'Complejo deportivo con disponibilidad de canchas y eventos.', 'Nuñez', 'aprobado'),
(88, 'Racing', '11-5000-2003', 'Italia 646', 'Buenos Aires', 'Sede con turnos y actividades deportivas.', 'Centro', 'aprobado'),
(89, 'San Lorenzo', '11-5000-2004', 'Av. La Plata 1702', 'Buenos Aires', 'Turnos y organización de partidos. Atención a socios y público.', 'Boedo', 'aprobado'),
(90, 'Huracan', '11-5000-2005', 'Av. Amancio Alcorta 2570', 'Buenos Aires', 'Proveedor con canchas y torneos internos.', 'Parque Patricios', 'aprobado'),
(91, 'Belen Chejo', '1232131', 'Laguna 448', 'Buenos Aires', '', '', 'aprobado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_historial`
--

CREATE TABLE `puntos_historial` (
  `puntos_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `origen` enum('partido','torneo','promocion','manual') NOT NULL DEFAULT 'manual',
  `referencia_id` int(11) DEFAULT NULL,
  `puntos` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puntos_historial`
--

INSERT INTO `puntos_historial` (`puntos_id`, `usuario_id`, `origen`, `referencia_id`, `puntos`, `descripcion`, `creado_en`) VALUES
(1, 58, 'torneo', 30, 200, 'Ganador torneo #30', '2025-12-19 16:54:19'),
(2, 60, 'torneo', 30, 200, 'Ganador torneo #30', '2025-12-20 16:54:46'),
(3, 61, 'torneo', 30, 200, 'Ganador torneo #30', '2025-12-21 16:55:25'),
(4, 49, 'torneo', 26, 1000, 'Ganador torneo #26', '2025-12-17 00:15:21'),
(5, 71, 'torneo', 55, 200, 'Ganador torneo #55', '2026-01-04 23:12:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ranking`
--

CREATE TABLE `ranking` (
  `ranking_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `puntos` int(11) DEFAULT 0,
  `partidos` int(11) DEFAULT 0,
  `victorias` int(11) DEFAULT 0,
  `derrotas` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ranking`
--

INSERT INTO `ranking` (`ranking_id`, `usuario_id`, `puntos`, `partidos`, `victorias`, `derrotas`, `updated_at`) VALUES
(11, 57, 0, 0, 0, 0, '2025-12-14 15:48:38'),
(16, 71, 320, 18, 11, 7, '2026-01-05 02:12:32'),
(17, 72, 80, 12, 6, 6, '2025-12-17 17:13:56'),
(18, 73, 150, 22, 14, 8, '2025-12-17 17:13:56'),
(19, 74, 60, 10, 4, 6, '2025-12-17 17:13:56'),
(20, 75, 40, 7, 3, 4, '2025-12-17 17:13:56'),
(21, 76, 110, 16, 10, 6, '2025-12-17 17:13:56'),
(22, 77, 90, 14, 7, 7, '2025-12-17 17:13:56'),
(23, 78, 70, 11, 5, 6, '2025-12-17 17:13:56'),
(24, 79, 160, 24, 16, 8, '2025-12-17 17:13:56'),
(25, 80, 85, 13, 7, 6, '2025-12-17 17:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcionista_detalle`
--

CREATE TABLE `recepcionista_detalle` (
  `recepcionista_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recepcionista_detalle`
--

INSERT INTO `recepcionista_detalle` (`recepcionista_id`, `proveedor_id`, `fecha_asignacion`) VALUES
(21, 20, '2025-12-07 11:29:34'),
(81, 86, '2025-12-17 14:16:10'),
(82, 87, '2025-12-17 14:16:10'),
(83, 88, '2025-12-17 14:16:10'),
(84, 89, '2025-12-17 14:16:10'),
(85, 90, '2025-12-17 14:16:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `nombre_reporte` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `respuesta_proveedor` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `reserva_id` int(11) DEFAULT NULL,
  `fecha_reporte` date NOT NULL,
  `estado` enum('Pendiente','Resuelto') DEFAULT 'Pendiente',
  `tipo_falla` enum('sistema','cancha') NOT NULL DEFAULT 'cancha'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `nombre_reporte`, `descripcion`, `respuesta_proveedor`, `usuario_id`, `cancha_id`, `reserva_id`, `fecha_reporte`, `estado`, `tipo_falla`) VALUES
(23, 'Error al cargar disponibilidad', 'La página de calendario se queda cargando indefinidamente al intentar ver horarios para el próximo fin de semana.', NULL, 57, NULL, NULL, '2025-12-14', 'Pendiente', 'sistema'),
(24, 'Cancha 6: Puntos de penal mal señalizados', 'Las marcas del punto de penal en la cancha de fútbol 7 (Cancha 6) están casi borradas, generando confusión.', NULL, 57, 6, 911, '2025-12-14', 'Pendiente', 'cancha'),
(25, 'Cobro Duplicado de Reserva', 'Se me realizó un doble cobro por la reserva ID 788. Solicito la devolución inmediata de uno de los cargos.', 'Reembolso procesado y confirmado. Revisando la causa raíz del error.', 57, NULL, 788, '2025-12-13', 'Resuelto', 'sistema'),
(26, 'Duchas sin agua caliente - Vestuario principal', 'Las duchas del vestuario asociado a las canchas principales no tienen agua caliente. Es un problema recurrente.', NULL, 57, 4, 850, '2025-12-12', 'Pendiente', 'cancha'),
(27, 'No puedo actualizar mi perfil', 'Al intentar cambiar mi número de teléfono en la configuración de mi perfil, el botón de guardar no responde.', NULL, 57, NULL, NULL, '2025-12-11', 'Pendiente', 'sistema'),
(28, 'Red de Voleibol muy baja - Cancha 10', 'La red de la cancha de voleibol (Cancha 10) estaba visiblemente más baja de la altura reglamentaria.', 'Personal ajustó la altura de la red. Problema resuelto.', 57, 10, 630, '2025-12-11', 'Resuelto', 'cancha'),
(29, 'Recuperación de contraseña no funciona', 'Solicité el enlace de recuperación de contraseña varias veces, pero nunca llegó a mi correo electrónico.', NULL, 57, NULL, NULL, '2025-12-10', 'Pendiente', 'sistema'),
(30, 'Basura acumulada cerca de la banca', 'En la Cancha 1, se encontró una gran cantidad de botellas y envoltorios de comida al iniciar la reserva.', NULL, 57, 1, 440, '2025-12-09', 'Pendiente', 'cancha'),
(31, 'Horario de 11 PM se muestra mal', 'El horario de las 23:00 en la tarde se muestra como 11:00 AM en el resumen de la reserva.', 'Corrección de formato de 24 horas aplicada en el frontend.', 57, NULL, 305, '2025-12-08', 'Resuelto', 'sistema'),
(32, 'Tablero de básquet inestable - Cancha 7', 'El tablero de una de las canastas en la Cancha 7 de básquet se mueve excesivamente. Riesgo de seguridad.', NULL, 57, 7, NULL, '2025-12-07', 'Pendiente', 'cancha'),
(33, 'Problema de iluminación', '123123312133', NULL, 21, NULL, NULL, '2025-12-15', 'Pendiente', 'sistema'),
(34, 'Problema de iluminación', '1232131', NULL, 21, 22, 6, '2025-12-15', 'Pendiente', 'cancha');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `reserva_id` int(11) NOT NULL,
  `cancha_id` int(11) NOT NULL,
  `creador_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `precio_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo_reserva` enum('individual','equipo') NOT NULL DEFAULT 'equipo',
  `estado` enum('pendiente','confirmada','cancelada','no_show') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`reserva_id`, `cancha_id`, `creador_id`, `fecha`, `hora_inicio`, `hora_fin`, `precio_total`, `tipo_reserva`, `estado`) VALUES
(144, 206, 57, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(145, 207, 57, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(146, 208, 57, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(147, 209, 57, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(148, 210, 57, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(149, 211, 57, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(150, 212, 57, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(151, 213, 57, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(152, 214, 71, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(153, 214, 57, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(154, 215, 72, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(155, 215, 71, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(156, 215, 57, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(157, 216, 73, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(158, 216, 72, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(159, 216, 71, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(160, 216, 57, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(161, 217, 74, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(162, 217, 73, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(163, 217, 72, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(164, 217, 71, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(165, 217, 57, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(166, 218, 75, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(167, 218, 74, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(168, 218, 73, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(169, 218, 72, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(170, 218, 71, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(171, 218, 57, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(172, 219, 76, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(173, 219, 75, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(174, 219, 74, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(175, 219, 73, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(176, 219, 72, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(177, 219, 71, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(178, 219, 57, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(179, 220, 77, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(180, 220, 76, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(181, 220, 75, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(182, 220, 74, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(183, 220, 73, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(184, 220, 72, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(185, 220, 71, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(186, 221, 78, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(187, 221, 77, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(188, 221, 76, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(189, 221, 75, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(190, 221, 74, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(191, 221, 73, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(192, 221, 72, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(193, 221, 71, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(194, 222, 79, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(195, 222, 78, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(196, 222, 77, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(197, 222, 76, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(198, 222, 75, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(199, 222, 74, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(200, 222, 73, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(201, 222, 72, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(202, 222, 71, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(203, 223, 80, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(204, 223, 79, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(205, 223, 78, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(206, 223, 77, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(207, 223, 76, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(208, 223, 75, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(209, 223, 74, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(210, 223, 73, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(211, 223, 72, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(212, 223, 71, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(213, 224, 80, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(214, 224, 79, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(215, 224, 78, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(216, 224, 77, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(217, 224, 76, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(218, 224, 75, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(219, 224, 74, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(220, 224, 73, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(221, 224, 72, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(222, 224, 71, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(223, 225, 80, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(224, 225, 79, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(225, 225, 78, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(226, 225, 77, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(227, 225, 76, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(228, 225, 75, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(229, 225, 74, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(230, 225, 73, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(231, 225, 72, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(232, 225, 71, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(233, 226, 80, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(234, 226, 79, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(235, 226, 78, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(236, 226, 77, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(237, 226, 76, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(238, 226, 75, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(239, 226, 74, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(240, 226, 73, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(241, 226, 72, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(242, 226, 71, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(243, 227, 80, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(244, 227, 79, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(245, 227, 78, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(246, 227, 77, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(247, 227, 76, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(248, 227, 75, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(249, 227, 74, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(250, 227, 73, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(251, 227, 72, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(252, 227, 71, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(253, 228, 80, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(254, 228, 79, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(255, 228, 78, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(256, 228, 77, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(257, 228, 76, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(258, 228, 75, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(259, 228, 74, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(260, 228, 73, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(261, 228, 72, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(262, 228, 71, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(263, 229, 80, '2025-12-23', '15:00:00', '16:00:00', 4500.00, 'equipo', 'confirmada'),
(264, 229, 79, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(265, 229, 78, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(266, 229, 77, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(267, 229, 76, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(268, 229, 75, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(269, 229, 74, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(270, 229, 73, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(271, 229, 72, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(272, 229, 71, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(273, 230, 80, '2025-12-24', '16:00:00', '17:00:00', 5000.00, 'equipo', 'confirmada'),
(274, 230, 79, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(275, 230, 78, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(276, 230, 77, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(277, 230, 76, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(278, 230, 75, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(279, 230, 74, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(280, 230, 73, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(281, 230, 72, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(282, 230, 71, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(283, 231, 80, '2025-12-25', '17:00:00', '18:00:00', 5500.00, 'equipo', 'confirmada'),
(284, 231, 79, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(285, 231, 78, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(286, 231, 77, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(287, 231, 76, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(288, 231, 75, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(289, 231, 74, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(290, 231, 73, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(291, 231, 72, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(292, 231, 71, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(293, 232, 80, '2025-12-26', '18:00:00', '19:00:00', 6000.00, 'equipo', 'confirmada'),
(294, 232, 79, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(295, 232, 78, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(296, 232, 77, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(297, 232, 76, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(298, 232, 75, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(299, 232, 74, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(300, 232, 73, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(301, 232, 72, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(302, 232, 71, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(303, 233, 80, '2025-12-27', '19:00:00', '20:00:00', 4000.00, 'equipo', 'confirmada'),
(304, 233, 79, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(305, 233, 78, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(306, 233, 77, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(307, 233, 76, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(308, 233, 75, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(309, 233, 74, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(310, 233, 73, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(311, 233, 72, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(312, 233, 71, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(313, 234, 80, '2025-12-28', '20:00:00', '21:00:00', 4500.00, 'equipo', 'confirmada'),
(314, 234, 79, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(315, 234, 78, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(316, 234, 77, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(317, 234, 76, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(318, 234, 75, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(319, 234, 74, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(320, 234, 73, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(321, 234, 72, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(322, 235, 80, '2025-12-29', '09:00:00', '10:00:00', 5000.00, 'equipo', 'confirmada'),
(323, 235, 79, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(324, 235, 78, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(325, 235, 77, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(326, 235, 76, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(327, 235, 75, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(328, 235, 74, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(329, 235, 73, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(330, 236, 80, '2025-12-17', '10:00:00', '11:00:00', 5500.00, 'equipo', 'confirmada'),
(331, 236, 79, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(332, 236, 78, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(333, 236, 77, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(334, 236, 76, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(335, 236, 75, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(336, 236, 74, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(337, 237, 80, '2025-12-18', '11:00:00', '12:00:00', 6000.00, 'equipo', 'confirmada'),
(338, 237, 79, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(339, 237, 78, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(340, 237, 77, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(341, 237, 76, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(342, 237, 75, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(343, 238, 80, '2025-12-19', '12:00:00', '13:00:00', 4000.00, 'equipo', 'confirmada'),
(344, 238, 79, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(345, 238, 78, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(346, 238, 77, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(347, 238, 76, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(348, 239, 80, '2025-12-20', '13:00:00', '14:00:00', 4500.00, 'equipo', 'confirmada'),
(349, 239, 79, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(350, 239, 78, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(351, 239, 77, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(352, 240, 80, '2025-12-21', '14:00:00', '15:00:00', 5000.00, 'equipo', 'confirmada'),
(353, 240, 79, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(354, 240, 78, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(355, 241, 80, '2025-12-22', '15:00:00', '16:00:00', 5500.00, 'equipo', 'confirmada'),
(356, 241, 79, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(357, 242, 80, '2025-12-23', '16:00:00', '17:00:00', 6000.00, 'equipo', 'confirmada'),
(358, 281, 57, '2025-12-17', '09:00:00', '10:00:00', 4000.00, 'equipo', 'confirmada'),
(359, 282, 57, '2025-12-18', '10:00:00', '11:00:00', 4500.00, 'equipo', 'confirmada'),
(360, 283, 57, '2025-12-19', '11:00:00', '12:00:00', 5000.00, 'equipo', 'confirmada'),
(361, 284, 57, '2025-12-20', '12:00:00', '13:00:00', 5500.00, 'equipo', 'confirmada'),
(362, 285, 57, '2025-12-21', '13:00:00', '14:00:00', 6000.00, 'equipo', 'confirmada'),
(363, 286, 57, '2025-12-22', '14:00:00', '15:00:00', 4000.00, 'equipo', 'confirmada'),
(399, 269, 57, '2025-12-17', '09:00:00', '10:00:00', 12000.00, 'equipo', 'confirmada'),
(400, 270, 71, '2025-12-18', '10:00:00', '11:00:00', 12000.00, 'equipo', 'confirmada'),
(401, 271, 72, '2025-12-19', '11:00:00', '12:00:00', 12000.00, 'equipo', 'confirmada'),
(402, 272, 73, '2025-12-20', '12:00:00', '13:00:00', 12500.00, 'equipo', 'confirmada'),
(403, 273, 74, '2025-12-21', '13:00:00', '14:00:00', 12500.00, 'equipo', 'confirmada'),
(404, 274, 75, '2025-12-22', '14:00:00', '15:00:00', 12500.00, 'equipo', 'confirmada'),
(405, 275, 76, '2025-12-23', '15:00:00', '16:00:00', 14500.00, 'equipo', 'confirmada'),
(406, 276, 77, '2025-12-24', '16:00:00', '17:00:00', 14500.00, 'equipo', 'confirmada'),
(407, 277, 78, '2025-12-25', '17:00:00', '18:00:00', 14800.00, 'equipo', 'confirmada'),
(408, 278, 79, '2025-12-26', '18:00:00', '19:00:00', 15000.00, 'equipo', 'confirmada'),
(409, 279, 80, '2025-12-27', '19:00:00', '20:00:00', 15000.00, 'equipo', 'confirmada'),
(410, 280, 57, '2025-12-28', '20:00:00', '21:00:00', 15200.00, 'equipo', 'confirmada'),
(411, 281, 71, '2025-12-29', '09:00:00', '10:00:00', 16500.00, 'equipo', 'confirmada'),
(412, 282, 72, '2025-12-17', '10:00:00', '11:00:00', 16500.00, 'equipo', 'confirmada'),
(413, 283, 73, '2025-12-18', '11:00:00', '12:00:00', 16800.00, 'equipo', 'confirmada'),
(414, 284, 74, '2025-12-19', '12:00:00', '13:00:00', 17000.00, 'equipo', 'confirmada'),
(415, 285, 75, '2025-12-20', '13:00:00', '14:00:00', 17000.00, 'equipo', 'confirmada'),
(416, 286, 76, '2025-12-21', '14:00:00', '15:00:00', 17200.00, 'equipo', 'confirmada'),
(417, 269, 77, '2025-12-22', '15:00:00', '16:00:00', 12000.00, 'equipo', 'confirmada'),
(418, 270, 78, '2025-12-23', '16:00:00', '17:00:00', 12000.00, 'equipo', 'confirmada'),
(419, 271, 79, '2025-12-24', '17:00:00', '18:00:00', 12000.00, 'equipo', 'confirmada'),
(420, 272, 80, '2025-12-25', '18:00:00', '19:00:00', 12500.00, 'equipo', 'confirmada'),
(421, 273, 57, '2025-12-26', '19:00:00', '20:00:00', 12500.00, 'equipo', 'confirmada'),
(422, 274, 71, '2025-12-27', '20:00:00', '21:00:00', 12500.00, 'equipo', 'confirmada'),
(423, 275, 72, '2025-12-28', '09:00:00', '10:00:00', 14500.00, 'equipo', 'confirmada'),
(424, 276, 73, '2025-12-29', '10:00:00', '11:00:00', 14500.00, 'equipo', 'confirmada'),
(425, 277, 74, '2025-12-17', '11:00:00', '12:00:00', 14800.00, 'equipo', 'confirmada'),
(426, 278, 75, '2025-12-18', '12:00:00', '13:00:00', 15000.00, 'equipo', 'confirmada'),
(427, 279, 76, '2025-12-19', '13:00:00', '14:00:00', 15000.00, 'equipo', 'confirmada'),
(428, 280, 77, '2025-12-20', '14:00:00', '15:00:00', 15200.00, 'equipo', 'confirmada'),
(429, 281, 78, '2025-12-21', '15:00:00', '16:00:00', 16500.00, 'equipo', 'confirmada'),
(430, 282, 79, '2025-12-22', '16:00:00', '17:00:00', 16500.00, 'equipo', 'confirmada'),
(431, 283, 80, '2025-12-23', '17:00:00', '18:00:00', 16800.00, 'equipo', 'confirmada'),
(432, 284, 57, '2025-12-24', '18:00:00', '19:00:00', 17000.00, 'equipo', 'confirmada'),
(433, 285, 71, '2025-12-25', '19:00:00', '20:00:00', 17000.00, 'equipo', 'confirmada'),
(434, 286, 72, '2025-12-26', '20:00:00', '21:00:00', 17200.00, 'equipo', 'confirmada'),
(435, 269, 73, '2025-12-27', '09:00:00', '10:00:00', 12000.00, 'equipo', 'confirmada'),
(436, 270, 74, '2025-12-28', '10:00:00', '11:00:00', 12000.00, 'equipo', 'confirmada'),
(437, 271, 75, '2025-12-29', '11:00:00', '12:00:00', 12000.00, 'equipo', 'confirmada'),
(438, 272, 76, '2025-12-17', '12:00:00', '13:00:00', 12500.00, 'equipo', 'confirmada'),
(439, 273, 77, '2025-12-18', '13:00:00', '14:00:00', 12500.00, 'equipo', 'confirmada'),
(440, 274, 78, '2025-12-19', '14:00:00', '15:00:00', 12500.00, 'equipo', 'confirmada'),
(441, 275, 79, '2025-12-20', '15:00:00', '16:00:00', 14500.00, 'equipo', 'confirmada'),
(442, 276, 80, '2025-12-21', '16:00:00', '17:00:00', 14500.00, 'equipo', 'confirmada'),
(443, 277, 57, '2025-12-22', '17:00:00', '18:00:00', 14800.00, 'equipo', 'confirmada'),
(444, 278, 71, '2025-12-23', '18:00:00', '19:00:00', 15000.00, 'equipo', 'confirmada'),
(445, 279, 72, '2025-12-24', '19:00:00', '20:00:00', 15000.00, 'equipo', 'confirmada'),
(446, 280, 73, '2025-12-25', '20:00:00', '21:00:00', 15200.00, 'equipo', 'confirmada'),
(447, 281, 74, '2025-12-26', '09:00:00', '10:00:00', 16500.00, 'equipo', 'confirmada'),
(448, 282, 75, '2025-12-27', '10:00:00', '11:00:00', 16500.00, 'equipo', 'confirmada'),
(462, 269, 57, '2025-12-18', '09:00:00', '10:00:00', 12000.00, 'equipo', 'confirmada'),
(463, 270, 71, '2025-12-19', '10:00:00', '11:00:00', 12000.00, 'equipo', 'confirmada'),
(464, 271, 72, '2025-12-20', '11:00:00', '12:00:00', 12000.00, 'equipo', 'confirmada'),
(465, 272, 73, '2025-12-18', '12:00:00', '13:00:00', 12500.00, 'equipo', 'confirmada'),
(466, 273, 74, '2025-12-19', '13:00:00', '14:00:00', 12500.00, 'equipo', 'confirmada'),
(467, 274, 75, '2025-12-20', '14:00:00', '15:00:00', 12500.00, 'equipo', 'confirmada'),
(468, 275, 76, '2025-12-18', '15:00:00', '16:00:00', 14500.00, 'equipo', 'confirmada'),
(469, 276, 77, '2025-12-19', '16:00:00', '17:00:00', 14500.00, 'equipo', 'confirmada'),
(470, 277, 78, '2025-12-20', '17:00:00', '18:00:00', 14800.00, 'equipo', 'confirmada'),
(471, 278, 79, '2025-12-18', '18:00:00', '19:00:00', 15000.00, 'equipo', 'confirmada'),
(472, 279, 80, '2025-12-19', '19:00:00', '20:00:00', 15000.00, 'equipo', 'confirmada'),
(473, 280, 57, '2025-12-20', '20:00:00', '21:00:00', 15200.00, 'equipo', 'confirmada'),
(474, 281, 71, '2025-12-18', '09:00:00', '10:00:00', 16500.00, 'equipo', 'confirmada'),
(475, 282, 72, '2025-12-19', '10:00:00', '11:00:00', 16500.00, 'equipo', 'confirmada'),
(476, 283, 73, '2025-12-20', '11:00:00', '12:00:00', 16800.00, 'equipo', 'confirmada'),
(477, 284, 74, '2025-12-18', '12:00:00', '13:00:00', 17000.00, 'equipo', 'confirmada'),
(478, 285, 75, '2025-12-19', '13:00:00', '14:00:00', 17000.00, 'equipo', 'confirmada'),
(479, 286, 76, '2025-12-20', '14:00:00', '15:00:00', 17200.00, 'equipo', 'confirmada'),
(480, 269, 77, '2025-12-18', '15:00:00', '16:00:00', 12000.00, 'equipo', 'confirmada'),
(481, 270, 78, '2025-12-19', '16:00:00', '17:00:00', 12000.00, 'equipo', 'confirmada'),
(482, 271, 79, '2025-12-20', '17:00:00', '18:00:00', 12000.00, 'equipo', 'confirmada'),
(483, 272, 80, '2025-12-18', '18:00:00', '19:00:00', 12500.00, 'equipo', 'confirmada'),
(484, 273, 57, '2025-12-19', '19:00:00', '20:00:00', 12500.00, 'equipo', 'confirmada'),
(485, 274, 71, '2025-12-20', '20:00:00', '21:00:00', 12500.00, 'equipo', 'confirmada'),
(486, 275, 72, '2025-12-18', '09:00:00', '10:00:00', 14500.00, 'equipo', 'confirmada'),
(487, 276, 73, '2025-12-19', '10:00:00', '11:00:00', 14500.00, 'equipo', 'confirmada'),
(488, 277, 74, '2025-12-20', '11:00:00', '12:00:00', 14800.00, 'equipo', 'confirmada'),
(489, 278, 75, '2025-12-18', '12:00:00', '13:00:00', 15000.00, 'equipo', 'confirmada'),
(490, 279, 76, '2025-12-19', '13:00:00', '14:00:00', 15000.00, 'equipo', 'confirmada'),
(491, 280, 77, '2025-12-20', '14:00:00', '15:00:00', 15200.00, 'equipo', 'confirmada'),
(492, 281, 78, '2025-12-18', '15:00:00', '16:00:00', 16500.00, 'equipo', 'confirmada'),
(493, 282, 79, '2025-12-19', '16:00:00', '17:00:00', 16500.00, 'equipo', 'confirmada'),
(494, 283, 80, '2025-12-20', '17:00:00', '18:00:00', 16800.00, 'equipo', 'confirmada'),
(495, 284, 57, '2025-12-18', '18:00:00', '19:00:00', 17000.00, 'equipo', 'confirmada'),
(496, 285, 71, '2025-12-19', '19:00:00', '20:00:00', 17000.00, 'equipo', 'confirmada'),
(497, 286, 72, '2025-12-20', '20:00:00', '21:00:00', 17200.00, 'equipo', 'confirmada'),
(498, 269, 73, '2025-12-18', '09:00:00', '10:00:00', 12000.00, 'equipo', 'confirmada'),
(499, 270, 74, '2025-12-19', '10:00:00', '11:00:00', 12000.00, 'equipo', 'confirmada'),
(500, 271, 75, '2025-12-20', '11:00:00', '12:00:00', 12000.00, 'equipo', 'confirmada'),
(501, 272, 76, '2025-12-18', '12:00:00', '13:00:00', 12500.00, 'equipo', 'confirmada'),
(502, 273, 77, '2025-12-19', '13:00:00', '14:00:00', 12500.00, 'equipo', 'confirmada'),
(503, 274, 78, '2025-12-20', '14:00:00', '15:00:00', 12500.00, 'equipo', 'confirmada'),
(504, 275, 79, '2025-12-18', '15:00:00', '16:00:00', 14500.00, 'equipo', 'confirmada'),
(505, 276, 80, '2025-12-19', '16:00:00', '17:00:00', 14500.00, 'equipo', 'confirmada'),
(506, 277, 57, '2025-12-20', '17:00:00', '18:00:00', 14800.00, 'equipo', 'confirmada'),
(507, 278, 71, '2025-12-18', '18:00:00', '19:00:00', 15000.00, 'equipo', 'confirmada'),
(508, 279, 72, '2025-12-19', '19:00:00', '20:00:00', 15000.00, 'equipo', 'confirmada'),
(509, 280, 73, '2025-12-20', '20:00:00', '21:00:00', 15200.00, 'equipo', 'confirmada'),
(510, 281, 74, '2025-12-18', '09:00:00', '10:00:00', 16500.00, 'equipo', 'confirmada'),
(511, 282, 75, '2025-12-19', '10:00:00', '11:00:00', 16500.00, 'equipo', 'confirmada'),
(512, 269, 20, '2025-12-21', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(513, 270, 20, '2025-12-22', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(514, 271, 20, '2025-12-23', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(515, 272, 20, '2025-12-24', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(516, 273, 20, '2025-12-25', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(517, 274, 20, '2025-12-26', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(518, 275, 20, '2025-12-27', '18:00:00', '19:00:00', 0.00, 'individual', 'confirmada'),
(519, 269, 20, '2025-12-20', '16:00:00', '17:00:00', 0.00, 'individual', 'confirmada'),
(520, 270, 20, '2025-12-21', '16:00:00', '17:00:00', 0.00, 'individual', 'confirmada'),
(521, 271, 20, '2025-12-22', '16:00:00', '17:00:00', 0.00, 'individual', 'confirmada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_proveedores`
--

CREATE TABLE `solicitudes_proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nombre_club` varchar(120) DEFAULT NULL,
  `telefono` varchar(60) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes_proveedores`
--

INSERT INTO `solicitudes_proveedores` (`id`, `nombre`, `email`, `password`, `nombre_club`, `telefono`, `direccion`, `barrio`, `ciudad`, `descripcion`, `estado`, `fecha_solicitud`) VALUES
(14, 'Cristian Chejo', 'cristianchejo55@gmail.com', NULL, 'Zubizarreta Asociación', '11 5555-1234', 'Laguna 448', NULL, 'Buenos Aires', NULL, 'aprobado', '2025-12-14 13:17:48'),
(15, 'Belen Chejo', 'usuario@gmail.com', NULL, 'Belen Chejo', '1232131', 'Laguna 448', NULL, 'Buenos Aires', NULL, 'rechazado', '2025-12-17 23:58:58'),
(16, 'Belen Chejo', 'cristianwn123@gmail.com', NULL, 'Belen Chejo', '1232131', 'Laguna 448', NULL, 'Buenos Aires', NULL, 'aprobado', '2025-12-18 00:00:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `torneo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `creador_id` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('abierto','en curso','cerrado','finalizado') DEFAULT 'abierto',
  `tipo` enum('individual','equipo') NOT NULL DEFAULT 'equipo',
  `capacidad` int(11) NOT NULL DEFAULT 0,
  `puntos_ganador` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`torneo_id`, `nombre`, `creador_id`, `proveedor_id`, `fecha_inicio`, `fecha_fin`, `estado`, `tipo`, `capacidad`, `puntos_ganador`) VALUES
(31, 'Pre-Navidad - Proveedor 20', 20, 20, '2025-12-17', '2025-12-24', 'abierto', 'individual', 4, 150),
(32, 'Pre-Navidad - Boca Juniors (86)', 86, 86, '2025-12-17', '2025-12-24', 'abierto', 'individual', 4, 155),
(33, 'Pre-Navidad - River Plate (87)', 87, 87, '2025-12-17', '2025-12-24', 'abierto', 'individual', 4, 160),
(34, 'Pre-Navidad - Racing (88)', 88, 88, '2025-12-17', '2025-12-24', 'abierto', 'individual', 4, 165),
(35, 'Pre-Navidad - San Lorenzo (89)', 89, 89, '2025-12-17', '2025-12-24', 'abierto', 'individual', 4, 170),
(47, 'Apertura Primavera 2025 (P20)', 20, 20, '2025-09-01', '2025-09-08', 'finalizado', 'individual', 4, 140),
(48, 'Copa Septiembre 2025 (P20)', 20, 20, '2025-09-15', '2025-09-22', 'finalizado', 'individual', 4, 150),
(49, 'Challenger Octubre 2025 (P20)', 20, 20, '2025-10-06', '2025-10-13', 'finalizado', 'individual', 4, 160),
(50, 'Copa Octubre II 2025 (P20)', 20, 20, '2025-10-20', '2025-10-27', 'finalizado', 'individual', 4, 155),
(51, 'Master Noviembre 2025 (P20)', 20, 20, '2025-11-03', '2025-11-10', 'finalizado', 'individual', 4, 170),
(52, 'Clausura Noviembre 2025 (P20)', 20, 20, '2025-11-17', '2025-11-24', 'finalizado', 'individual', 4, 165),
(53, 'Pre-Navidad (En curso) (P20)', 20, 20, '2025-12-12', '2025-12-19', 'en curso', 'individual', 4, 180),
(55, 'Fin de Año 2025 (P20)', 20, 20, '2025-12-28', '2026-01-04', 'finalizado', 'individual', 4, 200),
(56, 'Apertura Enero 2026 (P20)', 20, 20, '2026-01-05', '2026-01-12', 'abierto', 'individual', 4, 175),
(57, 'Tilin', 20, 20, '2025-12-21', '2025-12-28', 'abierto', 'individual', 8, 300);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `user_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `rol` enum('cliente','proveedor','admin','recepcionista') NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`user_id`, `nombre`, `email`, `contrasenia`, `rol`, `fecha_registro`) VALUES
(1, 'Admin', 'admin@goatsport.com', '$2y$10$EdE7yIasyYMvWXoo9IkO6O/0ekZMTsBSP1wasUpqZMkVrqMjYnu0C', 'admin', '2025-09-06 15:40:22'),
(20, 'Proveedor', 'proveedor@gmail.com', '$2y$10$T2S.XfdYY1gdWiOJZMwZIOl.8Xe0lAoP03sgcRnvwqpU5IwvW4KfG', 'proveedor', '2025-12-01 15:02:35'),
(21, 'Recepcionista', 'recepcionista@gmail.com', '$2y$10$sAQO0CIvo5bt4WuQTPTMoOv16o8RtvKRALE/tTlezNob8X/L6IKMe', 'recepcionista', '2025-12-07 01:25:11'),
(57, 'Usuario', 'usuario@gmail.com', '$2y$10$2/hpI4.yPG.Zq/DLZj1obOYW7YaMFlwTHhZkjWD/17FwMR1DFTWYa', 'cliente', '2025-12-14 12:48:38'),
(71, 'Emiliano Perez', 'emilianoperez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(72, 'Lucia Fernandez', 'luciafernandez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(73, 'Matias Gonzalez', 'matiasgonzalez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(74, 'Camila Romero', 'camilaromero@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(75, 'Nicolas Ruiz', 'nicolasruiz@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(76, 'Sofia Martinez', 'sofiamartinez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(77, 'Federico Alvarez', 'federicoalvarez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(78, 'Valentina Torres', 'valentinatorres@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(79, 'Juan Ignacio Lopez', 'juanignaciolopez@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(80, 'Agustina Diaz', 'agustinadiaz@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'cliente', '2025-12-17 14:13:56'),
(81, 'Mariana Silva', 'marianasilva@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'recepcionista', '2025-12-17 14:13:56'),
(82, 'Pablo Herrera', 'pabloherrera@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'recepcionista', '2025-12-17 14:13:56'),
(83, 'Florencia Costa', 'florenciacosta@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'recepcionista', '2025-12-17 14:13:56'),
(84, 'Gaston Medina', 'gastonmedina@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'recepcionista', '2025-12-17 14:13:56'),
(85, 'Carolina Rivas', 'carolinarivas@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'recepcionista', '2025-12-17 14:13:56'),
(86, 'Boca Juniors', 'bocajuniors@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'proveedor', '2025-12-17 14:13:56'),
(87, 'River Plate', 'riverplate@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'proveedor', '2025-12-17 14:13:56'),
(88, 'Racing', 'racing@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'proveedor', '2025-12-17 14:13:56'),
(89, 'San Lorenzo', 'sanlorenzo@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'proveedor', '2025-12-17 14:13:56'),
(90, 'Huracan', 'huracan@gmail.com', '388003ceef5e8bd105d00e4acf71368cff8cc7951d0b1165f9b65b80a48264b4', 'proveedor', '2025-12-17 14:13:56'),
(91, 'Belen Chejo', 'cristianwn123@gmail.com', '$2y$10$nIwLNEhNd7qRR2D03decH.Je80MOz9fCb8K0mp8vOIAvESj67osri', 'proveedor', '2025-12-17 21:01:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `canchas`
--
ALTER TABLE `canchas`
  ADD PRIMARY KEY (`cancha_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `cliente_detalle`
--
ALTER TABLE `cliente_detalle`
  ADD PRIMARY KEY (`cliente_id`);

--
-- Indices de la tabla `eventos_especiales`
--
ALTER TABLE `eventos_especiales`
  ADD PRIMARY KEY (`evento_id`),
  ADD KEY `idx_eventos_cancha` (`cancha_id`),
  ADD KEY `idx_eventos_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `invitados`
--
ALTER TABLE `invitados`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`notificacion_id`),
  ADD KEY `idx_notificaciones_usuario` (`usuario_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`pago_id`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indices de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD PRIMARY KEY (`participacion_id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `torneo_id` (`torneo_id`);

--
-- Indices de la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`partido_id`),
  ADD KEY `jugador1_id` (`jugador1_id`),
  ADD KEY `jugador2_id` (`jugador2_id`),
  ADD KEY `idx_torneo_ronda` (`torneo_id`,`ronda`,`idx_ronda`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`promocion_id`),
  ADD KEY `idx_promociones_proveedor` (`proveedor_id`),
  ADD KEY `idx_promociones_cancha` (`cancha_id`);

--
-- Indices de la tabla `proveedores_detalle`
--
ALTER TABLE `proveedores_detalle`
  ADD PRIMARY KEY (`proveedor_id`);

--
-- Indices de la tabla `puntos_historial`
--
ALTER TABLE `puntos_historial`
  ADD PRIMARY KEY (`puntos_id`),
  ADD KEY `idx_puntos_usuario` (`usuario_id`);

--
-- Indices de la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`ranking_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `recepcionista_detalle`
--
ALTER TABLE `recepcionista_detalle`
  ADD PRIMARY KEY (`recepcionista_id`),
  ADD KEY `idx_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`reserva_id`),
  ADD KEY `cancha_id` (`cancha_id`),
  ADD KEY `creador_id` (`creador_id`);

--
-- Indices de la tabla `solicitudes_proveedores`
--
ALTER TABLE `solicitudes_proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`torneo_id`),
  ADD KEY `creador_id` (`creador_id`),
  ADD KEY `idx_torneos_estado` (`estado`),
  ADD KEY `idx_torneos_tipo` (`tipo`),
  ADD KEY `idx_torneos_proveedor` (`proveedor_id`),
  ADD KEY `idx_torneos_fechas` (`fecha_inicio`,`fecha_fin`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `cancha_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT de la tabla `eventos_especiales`
--
ALTER TABLE `eventos_especiales`
  MODIFY `evento_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `notificacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=335;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `pago_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=390;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `participacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `partido_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `promocion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de la tabla `puntos_historial`
--
ALTER TABLE `puntos_historial`
  MODIFY `puntos_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `reserva_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=522;

--
-- AUTO_INCREMENT de la tabla `solicitudes_proveedores`
--
ALTER TABLE `solicitudes_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `torneo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `canchas`
--
ALTER TABLE `canchas`
  ADD CONSTRAINT `canchas_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cliente_detalle`
--
ALTER TABLE `cliente_detalle`
  ADD CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `invitados`
--
ALTER TABLE `invitados`
  ADD CONSTRAINT `fk_invitado_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`reserva_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD CONSTRAINT `participaciones_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participaciones_ibfk_2` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`reserva_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participaciones_ibfk_3` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`torneo_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`torneo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`jugador1_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`jugador2_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `proveedores_detalle`
--
ALTER TABLE `proveedores_detalle`
  ADD CONSTRAINT `fk_proveedor_usuario` FOREIGN KEY (`proveedor_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `ranking_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recepcionista_detalle`
--
ALTER TABLE `recepcionista_detalle`
  ADD CONSTRAINT `fk_recep_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recep_user` FOREIGN KEY (`recepcionista_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`cancha_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD CONSTRAINT `torneos_ibfk_1` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
