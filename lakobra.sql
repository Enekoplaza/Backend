-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-05-2026 a las 14:11:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lakobra`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_hora_entrada` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `id_evento`, `id_usuario`, `fecha_hora_entrada`) VALUES
(87, 27, 1, '2026-05-14 11:41:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `fecha_evento` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `aforo_max` int(11) DEFAULT 120,
  `estado` enum('pendiente','confirmado','cancelado') DEFAULT 'pendiente',
  `visible_publico` tinyint(1) DEFAULT 0,
  `txandalaris_max` int(11) DEFAULT 6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `fecha_evento`, `hora_inicio`, `aforo_max`, `estado`, `visible_publico`, `txandalaris_max`) VALUES
(27, 'GildosCup', '2026-05-14', '11:41:00', 120, 'confirmado', 1, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_artistas`
--

CREATE TABLE `solicitudes_artistas` (
  `id` int(11) NOT NULL,
  `nombre_artista` varchar(150) NOT NULL,
  `email_contacto` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `puesto` enum('barra','puerta','limpieza','otros') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `id_evento`, `id_usuario`, `puesto`) VALUES
(15, 27, 1, 'barra');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `qr_token` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `rol` enum('admin','txandalari','socio') DEFAULT 'socio',
  `solicitud_txandalari` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `dni`, `email`, `password`, `qr_token`, `direccion`, `rol`, `solicitud_txandalari`) VALUES
(1, 'Eneko', '12345678F', 'eneko@gmail.com', '$2y$10$eFD66D3MVokYPZkWSmZl1OpuvYoxLYOVDcNFHHtYnElU3fVUM5xjK', '5b57d94bf2a7c3532fa230aa4538e83a', 'aaa', 'admin', 0),
(12, 'Monica', '22222222S', 'monica@gmail.com', '$2y$10$V9.tYqlKeH46gJfcyp.LDe21svkFQRaNcwmrMTqtbx0dNCDJsNdkm', '07d8077f3c1b090b012391f0ad30e606', 'Lonbo kalea', 'txandalari', 0),
(14, 'plaza', '46366397F', 'enekoplazav9@gmail.com', '$2y$10$1y2IAojmODE5qK4R0r3SgOjC8d4a9Tnt7RZ7rwYAh9AYvK391hXTS', '8584f529c0fb8cd5e2d625c4fc25fc86', 'arrigorriaga', 'socio', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_evento` (`id_evento`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `solicitudes_artistas`
--
ALTER TABLE `solicitudes_artistas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_evento` (`id_evento`,`id_usuario`,`puesto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `qr_token` (`qr_token`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `solicitudes_artistas`
--
ALTER TABLE `solicitudes_artistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;