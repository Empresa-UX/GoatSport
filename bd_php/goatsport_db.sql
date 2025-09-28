-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 25-09-2025 a las 05:51:25
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
  `ubicacion` varchar(200) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `canchas`
--

INSERT INTO `canchas` (`cancha_id`, `proveedor_id`, `nombre`, `ubicacion`, `tipo`, `capacidad`, `precio`) VALUES
(2, 5, 'Cancha Norte', 'Av. Corrientes 123, Buenos Aires', 'cubierta', 4, 4500.00),
(7, 7, 'Cancha Parque 1', 'Av. Rivadavia 8900, Buenos Aires', 'clasica', 4, 5500.00),
(8, 7, 'Cancha Parque 2', 'Av. Rivadavia 9100, Buenos Aires', 'clasica', 4, 5500.00),
(10, 7, 'Cancha VIP 1', 'Av. Rivadavia 8900, Buenos Aires', 'paronamica', 4, 8000.00),
(11, 7, 'Cancha VIP 2', 'Av. Rivadavia 9000, Buenos Aires', 'panoramica', 4, 8500.00),
(12, 7, 'Cancha VIP 3', 'Av. Rivadavia 9100, Buenos Aires', 'panoramica', 4, 9000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `pago_id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  `fecha_pago` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`pago_id`, `reserva_id`, `jugador_id`, `monto`, `estado`, `fecha_pago`) VALUES
(2, 2, 2, 6500.00, 'pendiente', '2025-09-12 10:00:00'),
(7, 7, 1, 7000.00, 'pagado', '2025-09-12 10:00:00'),
(8, 8, 2, 7200.00, 'pagado', '2025-09-13 15:10:00'),
(9, 9, 3, 6800.00, 'pendiente', '2025-09-12 10:00:00'),
(10, 10, 4, 7500.00, 'pagado', '2025-09-14 19:15:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participaciones`
--

CREATE TABLE `participaciones` (
  `participacion_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `reserva_id` int(11) DEFAULT NULL,
  `torneo_id` int(11) DEFAULT NULL,
  `es_creador` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `participaciones`
--

INSERT INTO `participaciones` (`participacion_id`, `jugador_id`, `reserva_id`, `torneo_id`, `es_creador`) VALUES
(2, 2, 2, NULL, 1),
(7, 1, NULL, 1, 1),
(8, 2, NULL, 2, 1),
(9, 3, NULL, 3, 1),
(10, 4, NULL, 4, 1);

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
  `resultado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`partido_id`, `torneo_id`, `jugador1_id`, `jugador2_id`, `fecha`, `resultado`) VALUES
(1, 1, 9, 2, '2222-03-22 00:00:00', '6-4 6-3'),
(2, 1, 3, 4, '2025-09-15 11:30:00', '6-2 7-5'),
(3, 2, 2, 8, '2025-09-18 09:00:00', '6-3 6-4'),
(4, 2, 3, 9, '2025-09-18 10:30:00', '7-5 6-4'),
(5, 3, 1, 3, '2025-09-25 14:00:00', '6-4 4-6'),
(6, 3, 4, 8, '2025-09-25 15:30:00', '6-3 6-2'),
(7, 4, 2, 9, '2025-09-28 16:00:00', '6-1 6-0'),
(8, 4, 1, 8, '2025-09-28 17:30:00', '7-5 6-4'),
(9, 5, 3, 4, '2025-10-01 08:00:00', '6-2 6-3'),
(10, 6, 8, 9, '2025-10-05 09:30:00', '6-4 7-5'),
(11, 8, 9, 7, '2025-09-20 00:00:00', '3-1 2-1');

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
(31, 19, '765312', '2025-09-25 04:05:21', 0, '2025-09-25 01:55:21');

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
(1, 2, 1500, 30, 22, 8, '2025-09-20 20:35:55'),
(2, 3, 1400, 28, 18, 10, '2025-09-20 20:35:55'),
(3, 7, 800, 25, 15, 10, '2025-09-21 13:05:30'),
(4, 4, 1000, 20, 12, 8, '2025-09-20 20:35:55'),
(5, 5, 950, 18, 9, 9, '2025-09-20 20:35:55'),
(6, 4, 200, 10, 5, 0, '2025-09-21 01:46:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `nombre_reporte` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_reporte` date NOT NULL,
  `estado` enum('Pendiente','Resuelto') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `nombre_reporte`, `descripcion`, `usuario_id`, `fecha_reporte`, `estado`) VALUES
(1, 'Fallo en cancha', 'La iluminación de la cancha 2 no funciona.', 3, '2025-09-18', 'Pendiente'),
(2, 'Sugerencia', 'Agregar más turnos por la mañana.', 2, '2025-09-19', 'Resuelto'),
(4, 'Cambio de horario', 'Me cambiaron el horario y no me dijeron nada...', 7, '2025-09-23', 'Pendiente');

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
  `estado` enum('pendiente','confirmada','cancelada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`reserva_id`, `cancha_id`, `creador_id`, `fecha`, `hora_inicio`, `hora_fin`, `estado`) VALUES
(2, 2, 2, '2025-09-21', '11:00:00', '12:30:00', 'pendiente'),
(7, 2, 1, '2025-09-21', '10:00:00', '11:30:00', 'confirmada'),
(8, 2, 2, '2025-09-21', '15:00:00', '16:30:00', 'confirmada'),
(9, 2, 3, '2025-09-21', '09:00:00', '10:30:00', 'pendiente'),
(10, 2, 4, '2025-09-21', '19:00:00', '20:30:00', 'confirmada'),
(11, 2, 9, '2025-09-21', '12:00:00', '13:00:00', 'confirmada'),
(15, 2, 7, '2025-09-21', '20:30:00', '22:00:00', 'confirmada'),
(16, 12, 7, '2025-09-21', '10:00:00', '11:30:00', 'confirmada'),
(17, 7, 7, '2025-09-22', '20:00:00', '21:30:00', 'pendiente'),
(18, 7, 7, '2025-09-21', '08:00:00', '09:30:00', 'confirmada'),
(19, 7, 7, '2025-09-27', '09:30:00', '11:00:00', 'pendiente'),
(21, 2, 7, '2025-09-23', '08:00:00', '09:30:00', 'pendiente'),
(22, 2, 7, '2025-09-27', '08:00:00', '09:30:00', 'pendiente'),
(23, 8, 7, '2025-09-21', '10:30:00', '12:00:00', 'confirmada'),
(24, 7, 7, '2025-09-28', '08:30:00', '10:00:00', 'confirmada'),
(25, 7, 7, '2025-09-22', '10:00:00', '11:30:00', 'confirmada'),
(26, 12, 7, '2025-09-21', '08:00:00', '09:30:00', 'pendiente'),
(27, 2, 7, '2025-09-30', '08:00:00', '09:30:00', 'pendiente'),
(28, 2, 7, '2025-09-27', '10:00:00', '11:30:00', 'pendiente'),
(29, 7, 7, '2025-09-30', '09:30:00', '11:00:00', 'pendiente'),
(30, 7, 7, '2025-09-21', '09:30:00', '11:00:00', 'confirmada'),
(31, 7, 7, '2025-09-21', '18:30:00', '20:00:00', 'pendiente'),
(32, 7, 7, '2025-09-27', '16:00:00', '17:30:00', 'confirmada'),
(33, 12, 7, '2025-09-22', '08:30:00', '10:00:00', 'pendiente'),
(34, 7, 7, '2025-09-26', '09:30:00', '11:00:00', 'pendiente'),
(35, 7, 7, '2025-09-25', '09:30:00', '11:00:00', 'confirmada'),
(36, 7, 7, '2025-09-24', '08:00:00', '09:30:00', 'confirmada'),
(37, 7, 7, '2025-09-25', '08:00:00', '09:30:00', 'confirmada'),
(38, 7, 7, '2025-09-24', '09:30:00', '11:00:00', 'pendiente'),
(39, 7, 7, '2025-09-24', '13:30:00', '15:00:00', 'confirmada'),
(40, 7, 7, '2025-09-24', '11:00:00', '12:30:00', 'confirmada'),
(41, 7, 7, '2025-09-24', '18:00:00', '19:30:00', 'confirmada'),
(42, 7, 7, '2025-09-24', '16:30:00', '18:00:00', 'pendiente'),
(44, 7, 7, '2025-09-30', '08:00:00', '09:30:00', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `torneo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `creador_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('abierto','cerrado','finalizado') DEFAULT 'abierto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`torneo_id`, `nombre`, `creador_id`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
(1, 'Torneo Apertura Primavera', 1, '2025-09-15', '2025-09-20', 'abierto'),
(2, 'Copa Buenos Aires', 2, '2025-09-18', '2025-09-22', 'abierto'),
(3, 'Desafío del Río', 3, '2025-09-25', '2025-09-30', 'abierto'),
(4, 'Master Pádel 2025', 4, '2025-09-28', '2025-10-02', 'abierto'),
(5, 'Torneo Relámpago', 8, '2025-10-01', '2025-10-01', 'cerrado'),
(6, 'Copa Argentina', 9, '2025-10-05', '2025-10-10', 'abierto'),
(7, 'Open Primavera', 1, '2025-10-08', '2025-10-12', 'abierto'),
(8, 'Clásico del Sur', 2, '2025-10-15', '2025-10-18', 'cerrado'),
(9, 'Gran Torneo Nacional', 3, '2025-10-20', '2025-10-25', 'abierto'),
(10, 'Desafío Final', 4, '2025-10-28', '2025-10-31', 'abierto'),
(11, 'Viva la pobreza carajo', 7, '0000-00-00', '0012-03-12', 'abierto'),
(12, 'La masacre del 80 xd', 7, '2025-09-22', '2025-09-23', 'abierto');

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
(2, 'Juan Pérez', 'juan.perez@gmail.com', 'pass123', 'proveedor', 200, '2025-09-06 19:05:59'),
(3, 'María López', 'maria.lopez@gmail.com', 'pass456', 'proveedor', 120, '2025-09-06 19:05:59'),
(4, 'Carlos Gómez', 'carlos.gomez@gmail.com', 'pass789', 'cliente', 80, '2025-09-06 19:05:59'),
(5, 'Laura Fernández', 'laura.fernandez@gmail.com', 'pass321', 'cliente', 200, '2025-09-06 19:05:59'),
(7, 'Usuario', 'usuario@gmail.com', 'user123', 'cliente', 0, '2025-09-06 19:05:59'),
(8, 'Martín Rodríguez', 'martin.rodriguez@gmail.com', 'pass159', 'proveedor', 0, '2025-09-06 19:05:59'),
(9, 'Ana Torres', 'ana.torres@gmail.com', 'pass753', 'cliente', 300, '2025-09-06 19:05:59'),
(10, 'Luis Herrera', 'luis.herrera@gmail.com', 'pass852', 'cliente', 75, '2025-09-06 19:05:59'),
(16, 'El pibe Varela', 'varelaelpibe@gmail.com', '123123123', 'cliente', 0, '2025-09-25 20:53:05'),
(17, 'Juan Pérez', 'antonchejov45@gmail.com', '1231323', 'cliente', 0, '2025-09-25 02:01:36'),
(18, 'holanda', 'holanda@gmail.com', '11213222', 'cliente', 0, '2025-09-25 02:01:55'),
(19, 'Cristian', 'cristianchejo55@gmail.com', '123123123', 'cliente', 0, '2025-09-25 02:13:08');

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
  MODIFY `cancha_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `pago_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `participacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `partido_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `ranking`
--
ALTER TABLE `ranking`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `reserva_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `torneo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `canchas`
--
ALTER TABLE `canchas`
  ADD CONSTRAINT `canchas_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE;

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
