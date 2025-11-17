-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2025 a las 07:42:55
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
-- Base de datos: `agenda_escolar`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad`
--

CREATE TABLE `actividad` (
  `id_actividad` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_tipo_actividad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_actividad` varchar(100) NOT NULL,
  `fecha_entrega` date NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `puntos_posibles` decimal(5,2) DEFAULT NULL,
  `puntos_obtenidos` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `actividad`
--

INSERT INTO `actividad` (`id_actividad`, `id_materia`, `id_tipo_actividad`, `id_usuario`, `nombre_actividad`, `fecha_entrega`, `estado`, `puntos_posibles`, `puntos_obtenidos`) VALUES
(1, 1, 5, 1, 'Ejercicio 1: Repaso de sintaxis', '2025-09-01', 'Listo', 0.00, 0.00),
(2, 1, 5, 1, 'Ejercicio 2: Clases y objetos', '2025-09-05', 'En curso', 0.00, 0.00),
(3, 1, 5, 1, 'Ejercicio 3: Herencia básica', '2025-09-10', 'Sin iniciar', 0.00, 0.00),
(4, 2, 1, 1, 'Tarea 1: Integrales básicas', '2025-09-10', 'Listo', 10.00, 10.00),
(5, 2, 1, 1, 'Tarea 2: Regla de sustitución', '2025-09-15', 'Listo', 10.00, 9.00),
(6, 2, 2, 1, 'Proyecto: Aplicaciones de integrales', '2025-09-25', 'Listo', 20.00, 18.00),
(7, 2, 3, 1, 'Examen Parcial', '2025-10-01', 'Listo', 25.00, 18.00),
(8, 2, 4, 1, 'Exposición: Integración numérica', '2025-10-10', 'En curso', 5.00, 0.00),
(9, 2, 5, 1, 'Ejercicio: Práctica rápida de integrales', '2025-10-12', 'Listo', 0.00, 0.00),
(10, 3, 1, 1, 'Tarea 1: Probabilidad condicional', '2025-09-12', 'Listo', 10.00, 10.00),
(11, 3, 4, 1, 'Exposición: Distribuciones', '2025-09-18', 'Listo', 10.00, 9.00),
(12, 3, 2, 1, 'Proyecto: Inferencia Bayesiana', '2025-10-05', 'Listo', 25.00, 24.00),
(13, 3, 3, 1, 'Examen Parcial', '2025-09-30', 'Listo', 38.00, 35.00),
(14, 3, 1, 1, 'Tarea Pendiente: Intervalos de confianza', '2025-10-15', 'En curso', 2.00, 0.00),
(15, 3, 5, 1, 'Ejercicio: Cálculo de probabilidades', '2025-10-18', 'Listo', 0.00, 0.00),
(16, 204, 1, 1, 'Tarea 1: Normalización', '2025-09-10', 'Listo', 10.00, 10.00),
(17, 204, 2, 1, 'Proyecto: Modelo ER', '2025-09-22', 'Listo', 30.00, 27.00),
(18, 204, 3, 1, 'Examen Parcial', '2025-09-29', 'Listo', 28.00, 23.00),
(19, 204, 4, 1, 'Exposición: Joins avanzados', '2025-10-12', 'En curso', 5.00, 0.00),
(20, 204, 3, 1, 'Examen Final', '2025-10-20', 'En curso', 7.00, 0.00),
(21, 204, 5, 1, 'Ejercicio: Consultas de práctica', '2025-10-22', 'Listo', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia`
--

CREATE TABLE `materia` (
  `id_materia` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL,
  `calif_minima` int(11) NOT NULL DEFAULT 70,
  `calificacion_actual` decimal(5,2) NOT NULL DEFAULT 0.00,
  `puntos_ganados` decimal(7,2) NOT NULL DEFAULT 0.00,
  `puntos_perdidos` decimal(7,2) NOT NULL DEFAULT 0.00,
  `puntos_pendientes` decimal(7,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `materia`
--

INSERT INTO `materia` (`id_materia`, `id_usuario`, `nombre_materia`, `calif_minima`, `calificacion_actual`, `puntos_ganados`, `puntos_perdidos`, `puntos_pendientes`) VALUES
(1, 1, 'POO', 70, 0.00, 0.00, 0.00, 0.00),
(2, 1, 'Cálculo Integral', 70, 85.00, 55.00, 10.00, 5.00),
(3, 1, 'Inferencia Estadística', 70, 92.00, 78.00, 5.00, 2.00),
(204, 1, 'BD', 70, 88.00, 60.00, 8.00, 12.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ponderacion`
--

CREATE TABLE `ponderacion` (
  `id_materia` int(11) NOT NULL,
  `id_tipo_actividad` int(11) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `ponderacion`
--

INSERT INTO `ponderacion` (`id_materia`, `id_tipo_actividad`, `porcentaje`) VALUES
(1, 1, 40.00),
(1, 2, 20.00),
(1, 3, 25.00),
(1, 4, 10.00),
(1, 5, 5.00),
(2, 1, 60.00),
(2, 2, 20.00),
(2, 3, 10.00),
(2, 4, 0.00),
(2, 5, 10.00),
(3, 1, 50.00),
(3, 2, 25.00),
(3, 3, 15.00),
(3, 4, 5.00),
(3, 5, 5.00),
(204, 1, 40.00),
(204, 2, 30.00),
(204, 3, 20.00),
(204, 4, 5.00),
(204, 5, 5.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_actividad`
--

CREATE TABLE `tipo_actividad` (
  `id_tipo_actividad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `tipo_actividad`
--

INSERT INTO `tipo_actividad` (`id_tipo_actividad`, `id_usuario`, `nombre_tipo`) VALUES
(1, 1, 'Tarea'),
(2, 1, 'Proyecto'),
(3, 1, 'Examen'),
(4, 1, 'Exposición'),
(5, 1, 'Ejercicio');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `correo`, `password`, `nombre_usuario`) VALUES
(1, 'admin@correo.com', 'admin123', 'Administrador');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividad`
--
ALTER TABLE `actividad`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_tipo_actividad` (`id_tipo_actividad`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`nombre_materia`);

--
-- Indices de la tabla `ponderacion`
--
ALTER TABLE `ponderacion`
  ADD PRIMARY KEY (`id_materia`,`id_tipo_actividad`),
  ADD KEY `id_tipo_actividad` (`id_tipo_actividad`);

--
-- Indices de la tabla `tipo_actividad`
--
ALTER TABLE `tipo_actividad`
  ADD PRIMARY KEY (`id_tipo_actividad`),
  ADD UNIQUE KEY `id_tipo_actividad` (`id_tipo_actividad`,`nombre_tipo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_usuario_2` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividad`
--
ALTER TABLE `actividad`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT de la tabla `tipo_actividad`
--
ALTER TABLE `tipo_actividad`
  MODIFY `id_tipo_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividad`
--
ALTER TABLE `actividad`
  ADD CONSTRAINT `ACTIVIDAD_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `ACTIVIDAD_ibfk_2` FOREIGN KEY (`id_tipo_actividad`) REFERENCES `tipo_actividad` (`id_tipo_actividad`),
  ADD CONSTRAINT `ACTIVIDAD_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `materia`
--
ALTER TABLE `materia`
  ADD CONSTRAINT `MATERIA_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ponderacion`
--
ALTER TABLE `ponderacion`
  ADD CONSTRAINT `PONDERACION_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `PONDERACION_ibfk_2` FOREIGN KEY (`id_tipo_actividad`) REFERENCES `tipo_actividad` (`id_tipo_actividad`);

--
-- Filtros para la tabla `tipo_actividad`
--
ALTER TABLE `tipo_actividad`
  ADD CONSTRAINT `TIPO_ACTIVIDAD_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
