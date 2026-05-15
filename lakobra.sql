-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-05-2026 a las 14:29:36
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
-- Estructura de tabla para la tabla `evento_tareas`
--

CREATE TABLE `evento_tareas` (
  `id` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `nombre_tarea` varchar(100) NOT NULL,
  `limite_usuarios` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `evento_tareas`
--

INSERT INTO `evento_tareas` (`id`, `id_evento`, `nombre_tarea`, `limite_usuarios`) VALUES
(13, 39, 'Taberna (Barra)', 4),
(14, 39, 'Sarrera (Atea)', 2),
(15, 39, 'Garbiketa', 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `evento_tareas`
--
ALTER TABLE `evento_tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_evento` (`id_evento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `evento_tareas`
--
ALTER TABLE `evento_tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `evento_tareas`
--
ALTER TABLE `evento_tareas`
  ADD CONSTRAINT `evento_tareas_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;