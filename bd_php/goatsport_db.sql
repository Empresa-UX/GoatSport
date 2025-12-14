-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 07-12-2025 a las 01:41:15
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
  `ubicacion` varchar(200) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hora_apertura` time DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `duracion_turno` int(11) NOT NULL DEFAULT 60,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `canchas`
--

INSERT INTO `canchas` (`cancha_id`, `proveedor_id`, `nombre`, `descripcion`, `ubicacion`, `tipo`, `capacidad`, `precio`, `hora_apertura`, `hora_cierre`, `duracion_turno`, `activa`) VALUES
(7, 20, 'Cancha Parque 1', 'Cancha clásica al aire libre, ideal para partidos recreativos.', 'Av. Rivadavia 8900, Buenos Aires', 'clasica', 4, 5500.00, '08:00:00', '23:00:00', 60, 0),
(8, 20, 'Cancha Parque 2', 'Cancha clásica con iluminación nocturna.', 'Av. Rivadavia 9100, Buenos Aires', 'clasica', 4, 5500.00, '08:00:00', '23:00:00', 60, 1),
(10, 20, 'Cancha VIP 1', 'Cancha panorámica premium, césped sintético de alta calidad.', 'Av. Rivadavia 8900, Buenos Aires', 'panoramica', 4, 8000.00, '08:00:00', '23:00:00', 60, 1),
(11, 20, 'Cancha VIP 2', 'Cancha panorámica, excelente visibilidad y materiales pro.', 'Av. Rivadavia 9000, Buenos Aires', 'panoramica', 4, 8500.00, '08:00:00', '23:00:00', 60, 1),
(12, 20, 'Cancha VIP 3', 'Cancha panorámica premium con iluminación LED.', 'Av. Rivadavia 9100, Buenos Aires', 'panoramica', 4, 9000.00, '08:00:00', '23:00:00', 60, 1),
(13, 20, 'Clásica Norte A', 'Cancha clásica con césped sintético de última generación.', 'Buenos Aires - Núñez', 'clasica', 4, 6500.00, '08:00:00', '23:00:00', 60, 1),
(14, 20, 'Clásica Centro B', 'Iluminación LED y cerramiento lateral.', 'CABA - Microcentro', 'clasica', 4, 6800.00, '07:30:00', '23:30:00', 60, 1),
(15, 20, 'Cubierta Oeste 1', 'Techo parabólico, ideal días de lluvia.', 'Morón - Centro', 'cubierta', 4, 8200.00, '08:00:00', '00:00:00', 90, 1),
(16, 20, 'Cubierta Sur 2', 'Paredes de vidrio templado, drenaje óptimo.', 'Lanús - Remedios de Escalada', 'cubierta', 4, 7900.00, '09:00:00', '23:00:00', 60, 1),
(17, 20, 'Panorámica Río', 'Vista abierta 360°, vidrio perimetral.', 'Tigre - Paseo Costero', 'panoramica', 4, 9200.00, '08:00:00', '00:00:00', 90, 1),
(18, 20, 'Panorámica Parque', 'Césped 4G y postes anti-reflejo.', 'Palermo - Parque 3 de Febrero', 'panoramica', 4, 9800.00, '07:00:00', '23:59:00', 60, 1),
(19, 20, 'Clásica Sur C', 'Rebote uniforme y líneas renovadas.', 'Lomas de Zamora - Temperley', 'clasica', 4, 6400.00, '08:00:00', '23:00:00', 60, 1),
(20, 20, 'Cubierta Centro 3', 'Iluminación premium + vestuarios.', 'CABA - Balvanera', 'cubierta', 4, 8500.00, '07:30:00', '23:30:00', 90, 1),
(21, 20, 'Clásica Norte B', 'Césped con granos de caucho, alta tracción.', 'Vicente López - Olivos', 'clasica', 4, 7000.00, '08:00:00', '23:00:00', 60, 1),
(22, 20, 'Panorámica Centro X', 'Vidrios templados 12mm, postes laterales reducidos.', 'CABA - Recoleta', 'panoramica', 4, 9900.00, '08:00:00', '00:00:00', 90, 1);

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
  `dias_disponibles` set('1','2','3','4','5','6','7') DEFAULT NULL,
  `horario_pref` enum('maniana','tarde','noche') DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente_detalle`
--

INSERT INTO `cliente_detalle` (`cliente_id`, `telefono`, `fecha_nacimiento`, `ciudad`, `barrio`, `prefer_contacto`, `bio`, `genero`, `mano_habil`, `nivel_padel`, `posicion_pref`, `estilo_juego`, `pala_marca`, `pala_modelo`, `frecuencia_juego`, `dias_disponibles`, `horario_pref`, `actualizado_en`) VALUES
(7, '+54 11 5555-1234', '2006-06-15', 'Buenos Aires', 'Parque Avellaneda', 'whatsapp', 'Jugador entusiasta de pádel. Busco partidos nocturnos y torneos locales.', 'prefiero_no_decir', 'izquierda', 1, 'revés', 'defensivo,voleador', 'Nox', 'Vertex 04', 'ocasional', NULL, 'noche', '2025-12-04 21:25:11');

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
(3, 10, 20, 'Clínica especial', 'Clase con profesor invitado', '2025-12-15 10:00:00', '2025-12-15 12:00:00', 'otro', '#FF0000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `notificacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`notificacion_id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `creada_en`, `leida`) VALUES
(1, 20, 'reserva_estado', 'Reserva confirmada', 'Tu reserva para la cancha Parque 1 el 2025-12-05 de 18:00 a 19:30 ha sido confirmada.', '2025-12-01 17:29:23', 1),
(2, 20, 'torneo_inscripcion', 'Inscripción a torneo', 'Te has unido al torneo \"Torneo Apertura Primavera\" en el club Proveedor.', '2025-12-01 17:29:23', 1),
(3, 20, 'torneo_inscripcion', 'Inscripción a torneo', 'Te has unido al torneo \"Open Primavera\" en el club Proveedor.', '2025-12-01 17:29:23', 1),
(4, 20, 'reporte_resuelto', 'Tu reporte ha sido resuelto', 'Tu reporte sobre la cancha VIP 1 ha sido marcado como Resuelto por el club.', '2025-12-01 17:29:23', 1),
(5, 20, 'torneo_inscripcion', 'Nuevo inscripto en torneo', 'El jugador Usuario se ha inscrito en tu torneo \"Torneo Apertura Primavera\".', '2025-12-01 17:29:23', 1),
(6, 20, 'torneo_inscripcion', 'Nuevo inscripto en torneo', 'El jugador Cristian se ha inscrito en tu torneo \"Open Primavera\".', '2025-12-01 17:29:23', 1),
(7, 20, 'reporte_nuevo', 'Nuevo reporte recibido', 'Se ha creado un nuevo reporte sobre la cancha Parque 1. Revisa la sección de reportes.', '2025-12-01 17:29:23', 1),
(8, 20, 'reserva_nueva', 'Nueva reserva en tu club', 'Un jugador ha realizado una nueva reserva para la cancha VIP 2 el 2025-12-06.', '2025-12-01 17:29:23', 1),
(9, 7, 'reporte_resuelto', 'Tu reporte ha sido resuelto', 'Tu reporte \"Turno ocupado al llegar\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:06:29', 0),
(10, 7, 'reporte_resuelto', 'Tu reporte ha sido resuelto', 'Tu reporte \"Rival no se presentó\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:11:51', 0),
(11, 7, 'reporte_resuelto', 'Tu reporte ha sido resuelto', 'Tu reporte \"Turno ocupado al llegar\" fue marcado como Resuelto. ¡Gracias por avisar!', '2025-12-01 20:16:31', 0),
(12, 7, 'torneo', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:44:35', 0),
(13, 20, 'torneo', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:44:35', 1),
(14, 7, 'torneo', 'Inscripción confirmada', 'Te uniste al torneo \"Tilin\" en el club Proveedor.', '2025-12-03 20:44:46', 0),
(15, 20, 'torneo', 'Nuevo inscripto en \"Tilin\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:44:46', 1),
(16, 7, 'torneo', 'Saliste del torneo', 'Has salido del torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:48:50', 0),
(17, 20, 'torneo', 'Un jugador salió de \"Viva la pobreza carajo\"', 'Usuario se dio de baja del torneo.', '2025-12-03 20:48:50', 0),
(18, 7, 'torneo', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:50:18', 0),
(19, 20, 'torneo', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-03 20:50:18', 0),
(20, 7, 'torneo', 'Saliste del torneo', 'Has salido del torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-03 20:55:06', 0),
(21, 20, 'torneo', 'Un jugador salió de \"Viva la pobreza carajo\"', 'Usuario se dio de baja del torneo.', '2025-12-03 20:55:06', 0),
(22, 7, 'torneo', 'Inscripción confirmada', 'Te uniste al torneo \"Viva la pobreza carajo\" en el club Proveedor.', '2025-12-06 20:57:52', 0),
(23, 20, 'torneo', 'Nuevo inscripto en \"Viva la pobreza carajo\"', 'Usuario se inscribió en tu torneo.', '2025-12-06 20:57:52', 0);

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
(12, 46, 7, 5500.00, 'club', NULL, NULL, 'pagado', '2025-09-27 23:52:27'),
(13, 47, 7, 5500.00, 'club', NULL, NULL, 'pagado', '2025-09-27 23:54:57'),
(14, 48, 7, 5500.00, 'club', NULL, NULL, 'pagado', '2025-09-28 01:21:16'),
(17, 51, 7, 5500.00, 'club', NULL, NULL, 'pagado', '2025-09-28 09:25:28'),
(18, 52, 7, 5500.00, 'club', NULL, NULL, 'pagado', '2025-09-28 11:45:06'),
(20, 61, 7, 5500.00, 'club', NULL, NULL, 'cancelado', NULL);

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
(7, 1, NULL, 1, 1, 'aceptada'),
(11, 7, NULL, 1, 0, 'aceptada'),
(12, 19, NULL, 1, 0, 'pendiente'),
(13, 7, NULL, 7, 1, 'aceptada'),
(14, 19, NULL, 7, 0, 'aceptada'),
(15, 7, NULL, 12, 0, 'pendiente'),
(16, 19, NULL, 12, 0, 'pendiente'),
(18, 7, NULL, 13, 0, 'aceptada'),
(20, 7, NULL, 11, 0, 'aceptada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

CREATE TABLE `partidos` (
  `partido_id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `jugador1_id` int(11) NOT NULL,
  `jugador2_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `resultado` varchar(50) DEFAULT NULL,
  `ganador_id` int(11) DEFAULT NULL,
  `reserva_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`partido_id`, `torneo_id`, `jugador1_id`, `jugador2_id`, `fecha`, `resultado`, `ganador_id`, `reserva_id`) VALUES
(22, 1, 19, 7, '2025-01-10 18:00:00', '6-4 6-3 J1', 19, NULL),
(23, 1, 7, 19, '2025-01-12 20:00:00', '6-2 6-1 J2', 19, NULL),
(24, 7, 19, 7, '2025-02-05 17:30:00', NULL, NULL, NULL),
(25, 7, 7, 19, '2025-02-06 19:00:00', '7-5 3-6 10-8 J1', 7, NULL),
(26, 11, 19, 7, '2025-03-01 16:00:00', '6-0 6-0 J1', 19, NULL);

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

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `codigo`, `expira`, `usado`, `creado`) VALUES
(6, 19, '943116', '2025-09-25 02:39:02', 0, '2025-09-25 00:29:02'),
(7, 19, '899259', '2025-09-25 02:39:15', 0, '2025-09-25 00:29:15'),
(8, 19, '350222', '2025-09-25 03:01:24', 0, '2025-09-25 00:51:24'),
(9, 19, '497852', '2025-09-25 03:01:41', 0, '2025-09-25 00:51:41'),
(10, 19, '845577', '2025-09-25 03:03:13', 0, '2025-09-25 00:53:13'),
(11, 19, '175084', '2025-09-25 03:03:14', 0, '2025-09-25 00:53:14'),
(12, 19, '631240', '2025-09-25 03:03:31', 0, '2025-09-25 00:53:31'),
(13, 19, '105417', '2025-09-25 03:04:01', 0, '2025-09-25 00:54:01'),
(14, 19, '172434', '2025-09-25 03:05:27', 0, '2025-09-25 00:55:27'),
(15, 19, '457793', '2025-09-25 03:05:36', 0, '2025-09-25 00:55:36'),
(16, 19, '963795', '2025-09-25 03:19:14', 0, '2025-09-25 01:09:14'),
(17, 19, '682445', '2025-09-25 03:19:22', 0, '2025-09-25 01:09:22'),
(18, 19, '258777', '2025-09-25 03:23:06', 0, '2025-09-25 01:13:06'),
(19, 19, '452993', '2025-09-25 03:23:17', 1, '2025-09-25 01:13:17'),
(20, 19, '731369', '2025-09-25 03:29:01', 0, '2025-09-25 01:19:01'),
(21, 19, '864380', '2025-09-25 03:30:40', 0, '2025-09-25 01:20:40'),
(22, 19, '469007', '2025-09-25 03:32:28', 0, '2025-09-25 01:22:28'),
(23, 19, '852369', '2025-09-25 03:34:29', 1, '2025-09-25 01:24:29'),
(24, 19, '510364', '2025-09-25 03:36:07', 0, '2025-09-25 01:26:07'),
(25, 19, '329251', '2025-09-25 03:38:03', 0, '2025-09-25 01:28:03'),
(26, 19, '328455', '2025-09-25 03:40:27', 1, '2025-09-25 01:30:27'),
(27, 19, '642712', '2025-09-25 03:44:52', 0, '2025-09-25 01:34:52'),
(28, 19, '632624', '2025-09-25 03:47:02', 0, '2025-09-25 01:37:02'),
(29, 19, '365387', '2025-09-25 03:55:47', 0, '2025-09-25 01:45:47'),
(30, 19, '835715', '2025-09-25 03:59:57', 0, '2025-09-25 01:49:57'),
(31, 19, '765312', '2025-09-25 04:05:21', 0, '2025-09-25 01:55:21'),
(32, 19, '265570', '2025-09-25 06:17:05', 1, '2025-09-25 04:07:05');

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
(1, 20, 7, 'Happy Hour - 20% OFF', 'Descuento especial por la tarde para aumentar la demanda.', 20.00, '2025-12-01', '2025-12-31', '14:00:00', '17:00:00', '1,2,3,4,5', 0, 1),
(2, 20, NULL, 'Mañanas Activas - 15% OFF', 'Promoción para incentivar las reservas a primera hora.', 15.00, '2025-12-01', '2026-01-31', '08:00:00', '11:00:00', '1,2,3,4,5,6', 0, 1),
(3, 20, 12, 'Fin de Semana - 30% OFF', 'Descuento fuerte para completar cupo los sábados y domingos.', 30.00, '2025-12-10', '2026-02-10', NULL, NULL, '6,7', 0, 1),
(4, 20, NULL, 'Jugadores Frecuentes - 10% OFF', 'Aplica solo para quienes ya reservaron 5 veces o más.', 10.00, '2025-12-01', '2026-03-01', NULL, NULL, NULL, 5, 1),
(5, 20, 11, 'Promo Octubre', 'Promoción anterior, ya expirada.', 25.00, '2025-10-01', '2025-10-31', NULL, NULL, NULL, 0, 0);

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
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores_detalle`
--

INSERT INTO `proveedores_detalle` (`proveedor_id`, `nombre_club`, `telefono`, `direccion`, `ciudad`, `descripcion`) VALUES
(20, 'Larrazabal', '1155792821', 'Laguna 448', 'Buenos Aires', '1231312312313');

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
(3, 7, 800, 25, 15, 10, '2025-09-21 13:05:30');

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
  `estado` enum('Pendiente','Resuelto') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `nombre_reporte`, `descripcion`, `respuesta_proveedor`, `usuario_id`, `cancha_id`, `reserva_id`, `fecha_reporte`, `estado`) VALUES
(4, 'Cambio de horario', 'Me cambiaron el horario y no me dijeron nada...', NULL, 7, NULL, NULL, '2025-09-23', 'Pendiente'),
(5, 'Luz fallando en la cancha', 'La iluminación parpadea y dificulta el juego.', NULL, 7, 7, 31, '2025-12-01', 'Pendiente'),
(6, 'Puerta rota', 'La puerta de la cancha 2 no cierra bien.', NULL, 19, 8, NULL, '2025-12-02', 'Pendiente'),
(7, 'Rival no se presentó', 'Mi rival no se presentó a la reserva.', NULL, 7, NULL, 33, '2025-12-03', 'Resuelto'),
(8, 'Césped desgastado', 'Hay partes del césped sintético levantadas.', NULL, 19, 10, NULL, '2025-12-04', 'Pendiente'),
(9, 'Turno ocupado al llegar', 'Había gente usando la cancha que yo había reservado.', NULL, 7, NULL, 30, '2025-12-04', 'Pendiente');

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
(16, 12, 7, '2025-09-21', '10:00:00', '11:30:00', 0.00, 'equipo', 'confirmada'),
(17, 7, 7, '2025-09-22', '20:00:00', '21:30:00', 0.00, 'equipo', 'pendiente'),
(18, 7, 7, '2025-09-21', '08:00:00', '09:30:00', 0.00, 'equipo', 'confirmada'),
(19, 7, 7, '2025-09-27', '09:30:00', '11:00:00', 0.00, 'equipo', 'pendiente'),
(23, 8, 7, '2025-09-21', '10:30:00', '12:00:00', 0.00, 'equipo', 'confirmada'),
(24, 7, 7, '2025-09-28', '08:30:00', '10:00:00', 0.00, 'equipo', 'confirmada'),
(25, 7, 7, '2025-09-22', '10:00:00', '11:30:00', 0.00, 'equipo', 'confirmada'),
(26, 12, 7, '2025-09-21', '08:00:00', '09:30:00', 0.00, 'equipo', 'pendiente'),
(29, 7, 7, '2025-09-30', '09:30:00', '11:00:00', 0.00, 'equipo', 'pendiente'),
(30, 7, 7, '2025-09-21', '09:30:00', '11:00:00', 0.00, 'equipo', 'confirmada'),
(31, 7, 7, '2025-09-21', '18:30:00', '20:00:00', 0.00, 'equipo', 'pendiente'),
(32, 7, 7, '2025-09-27', '16:00:00', '17:30:00', 0.00, 'equipo', 'confirmada'),
(33, 12, 7, '2025-09-22', '08:30:00', '10:00:00', 0.00, 'equipo', 'pendiente'),
(34, 7, 20, '2025-09-26', '09:30:00', '11:00:00', 0.00, 'equipo', 'pendiente'),
(35, 7, 7, '2025-12-01', '09:30:00', '11:00:00', 0.00, 'equipo', 'confirmada'),
(36, 7, 7, '2025-12-01', '08:00:00', '09:30:00', 0.00, 'equipo', 'confirmada'),
(37, 7, 7, '2025-12-01', '08:00:00', '09:30:00', 0.00, 'equipo', 'confirmada'),
(38, 7, 7, '2025-09-24', '09:30:00', '11:00:00', 0.00, 'equipo', 'pendiente'),
(39, 7, 7, '2025-09-24', '13:30:00', '15:00:00', 0.00, 'equipo', 'confirmada'),
(40, 7, 7, '2025-09-24', '11:00:00', '12:30:00', 0.00, 'equipo', 'confirmada'),
(41, 7, 7, '2025-09-24', '18:00:00', '19:30:00', 0.00, 'equipo', 'confirmada'),
(42, 7, 7, '2025-09-24', '16:30:00', '18:00:00', 0.00, 'equipo', 'pendiente'),
(44, 7, 7, '2025-09-30', '08:00:00', '09:30:00', 0.00, 'equipo', 'pendiente'),
(46, 7, 7, '2025-09-27', '08:00:00', '09:30:00', 0.00, 'equipo', 'confirmada'),
(47, 7, 7, '2025-09-28', '16:00:00', '17:30:00', 0.00, 'equipo', 'confirmada'),
(48, 8, 7, '2025-09-28', '08:30:00', '10:00:00', 0.00, 'equipo', 'confirmada'),
(51, 7, 7, '2025-09-28', '13:00:00', '14:30:00', 0.00, 'equipo', 'confirmada'),
(52, 7, 7, '2025-12-01', '11:00:00', '12:30:00', 0.00, 'equipo', 'confirmada'),
(61, 7, 7, '2025-12-03', '21:30:00', '23:00:00', 0.00, 'equipo', 'cancelada');

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
  `estado` enum('abierto','cerrado','finalizado') DEFAULT 'abierto',
  `puntos_ganador` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`torneo_id`, `nombre`, `creador_id`, `proveedor_id`, `fecha_inicio`, `fecha_fin`, `estado`, `puntos_ganador`) VALUES
(1, 'Torneo Apertura Primavera', 1, 20, '2025-09-15', '2025-09-20', 'abierto', 0),
(7, 'Open Primavera', 1, 20, '2025-10-08', '2025-10-12', 'abierto', 0),
(11, 'Viva la pobreza carajo', 7, 20, '0000-00-00', '0012-03-12', 'abierto', 0),
(12, 'La masacre del 80 xd', 7, 20, '2025-09-22', '2025-09-23', 'abierto', 0),
(13, 'Tilin', 20, 20, '2006-12-01', '2006-12-02', 'abierto', 1000),
(14, 'Liga Express 1', 1, 20, '2025-12-05', '2025-12-06', 'abierto', 100),
(15, 'Liga Express 2', 1, 20, '2025-12-08', '2025-12-09', 'abierto', 120),
(16, 'Copa Nocturna', 1, 20, '2025-12-10', '2025-12-11', 'abierto', 150),
(17, 'Master 250', 1, 20, '2025-12-13', '2025-12-14', 'abierto', 250),
(18, 'Open Ciudad', 1, 20, '2025-12-15', '2025-12-16', 'abierto', 180),
(19, 'Challenger Verde', 1, 20, '2025-12-17', '2025-12-18', 'abierto', 130),
(20, 'ProAm Weekend', 1, 20, '2025-12-20', '2025-12-21', 'abierto', 160),
(21, 'Circuito Regional', 1, 20, '2025-12-23', '2025-12-24', 'abierto', 200),
(22, 'Cierre de Temporada', 1, 20, '2025-11-23', '2025-11-24', 'cerrado', 220),
(23, 'Finales Anuales', 1, 20, '2025-11-03', '2025-11-04', 'finalizado', 300);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `user_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `rol` enum('cliente','proveedor','admin') NOT NULL,
  `puntos` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`user_id`, `nombre`, `email`, `contrasenia`, `rol`, `puntos`, `fecha_registro`) VALUES
(1, 'Admin', 'admin@goatsport.com', 'admin123', 'admin', 0, '2025-09-06 15:40:22'),
(7, 'Usuario', 'usuario@gmail.com', 'user123', 'cliente', 0, '2025-09-06 19:05:59'),
(19, 'Cristian', 'cristianchejo55@gmail.com', '123123', 'cliente', 0, '2025-09-25 02:13:08'),
(20, 'Proveedor', 'proveedor@gmail.com', 'proveedor123', 'proveedor', 0, '2025-12-01 15:02:35');

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
  ADD KEY `torneo_id` (`torneo_id`),
  ADD KEY `jugador1_id` (`jugador1_id`),
  ADD KEY `jugador2_id` (`jugador2_id`);

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
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`torneo_id`),
  ADD KEY `creador_id` (`creador_id`);

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
  MODIFY `cancha_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `eventos_especiales`
--
ALTER TABLE `eventos_especiales`
  MODIFY `evento_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `notificacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `pago_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `participacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `partido_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `promocion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `puntos_historial`
--
ALTER TABLE `puntos_historial`
  MODIFY `puntos_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `reserva_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `torneo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
