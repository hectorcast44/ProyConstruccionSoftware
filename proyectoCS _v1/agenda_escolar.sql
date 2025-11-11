-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 11-11-2025 a las 05:51:44
-- Versión del servidor: 8.0.43-0ubuntu0.24.04.2
-- Versión de PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `agenda_escolar`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ACTIVIDAD`
--

CREATE TABLE `ACTIVIDAD` (
  `id_actividad` int NOT NULL,
  `id_materia` int NOT NULL,
  `id_tipo_actividad` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre_actividad` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `fecha_entrega` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'pendiente',
  `puntos_posibles` decimal(5,2) DEFAULT NULL,
  `puntos_obtenidos` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MATERIA`
--

CREATE TABLE `MATERIA` (
  `id_materia` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre_materia` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `calif_minima` int NOT NULL DEFAULT '70',
  `calificacion_actual` decimal(5,2) NOT NULL DEFAULT '0.00',
  `puntos_ganados` decimal(7,2) NOT NULL DEFAULT '0.00',
  `puntos_perdidos` decimal(7,2) NOT NULL DEFAULT '0.00',
  `puntos_pendientes` decimal(7,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `PONDERACION`
--

CREATE TABLE `PONDERACION` (
  `id_materia` int NOT NULL,
  `id_tipo_actividad` int NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `TIPO_ACTIVIDAD`
--

CREATE TABLE `TIPO_ACTIVIDAD` (
  `id_tipo_actividad` int NOT NULL,
  `id_usuario` int NOT NULL,
  `nombre_tipo` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `USUARIO`
--

CREATE TABLE `USUARIO` (
  `id_usuario` int NOT NULL,
  `correo` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre_usuario` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ACTIVIDAD`
--
ALTER TABLE `ACTIVIDAD`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_tipo_actividad` (`id_tipo_actividad`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `MATERIA`
--
ALTER TABLE `MATERIA`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`nombre_materia`);

--
-- Indices de la tabla `PONDERACION`
--
ALTER TABLE `PONDERACION`
  ADD PRIMARY KEY (`id_materia`,`id_tipo_actividad`),
  ADD KEY `id_tipo_actividad` (`id_tipo_actividad`);

--
-- Indices de la tabla `TIPO_ACTIVIDAD`
--
ALTER TABLE `TIPO_ACTIVIDAD`
  ADD PRIMARY KEY (`id_tipo_actividad`),
  ADD UNIQUE KEY `id_tipo_actividad` (`id_tipo_actividad`,`nombre_tipo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `USUARIO`
--
ALTER TABLE `USUARIO`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_usuario_2` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ACTIVIDAD`
--
ALTER TABLE `ACTIVIDAD`
  MODIFY `id_actividad` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `MATERIA`
--
ALTER TABLE `MATERIA`
  MODIFY `id_materia` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `TIPO_ACTIVIDAD`
--
ALTER TABLE `TIPO_ACTIVIDAD`
  MODIFY `id_tipo_actividad` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `USUARIO`
--
ALTER TABLE `USUARIO`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ACTIVIDAD`
--
ALTER TABLE `ACTIVIDAD`
  ADD CONSTRAINT `ACTIVIDAD_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `MATERIA` (`id_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `ACTIVIDAD_ibfk_2` FOREIGN KEY (`id_tipo_actividad`) REFERENCES `TIPO_ACTIVIDAD` (`id_tipo_actividad`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ACTIVIDAD_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `MATERIA`
--
ALTER TABLE `MATERIA`
  ADD CONSTRAINT `MATERIA_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Filtros para la tabla `PONDERACION`
--
ALTER TABLE `PONDERACION`
  ADD CONSTRAINT `PONDERACION_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `MATERIA` (`id_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `PONDERACION_ibfk_2` FOREIGN KEY (`id_tipo_actividad`) REFERENCES `TIPO_ACTIVIDAD` (`id_tipo_actividad`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `TIPO_ACTIVIDAD`
--
ALTER TABLE `TIPO_ACTIVIDAD`
  ADD CONSTRAINT `TIPO_ACTIVIDAD_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIO` (`id_usuario`) ON DELETE CASCADE ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
