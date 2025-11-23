-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-11-2025 a las 04:20:54
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
(4, 2, 1, 1, 'Tarea 1: Integrales básicas', '2025-09-10', 'Listo', 10.00, 10.00),
(5, 2, 1, 1, 'Tarea 2: Regla de sustitución', '2025-09-15', 'Listo', 10.00, 9.00),
(6, 2, 2, 1, 'Proyecto: Aplicaciones de integrales', '2025-09-25', 'Listo', 20.00, 18.00),
(7, 2, 3, 1, 'Examen Parcial', '2025-10-01', 'Listo', 25.00, 18.00),
(8, 2, 4, 1, 'Exposición: Integración numérica', '2025-10-10', 'En curso', NULL, NULL),
(9, 2, 5, 1, 'Ejercicio: Práctica rápida de integrales', '2025-10-12', 'Listo', 10.00, 0.00),
(10, 3, 1, 1, 'Tarea 1: Probabilidad condicional', '2025-09-12', 'Listo', 10.00, 10.00),
(11, 3, 4, 1, 'Exposición: Distribuciones', '2025-09-18', 'Listo', 10.00, 9.00),
(12, 3, 2, 1, 'Proyecto: Inferencia Bayesiana', '2025-10-05', 'Listo', 25.00, 24.00),
(13, 3, 3, 1, 'Examen Parcial', '2025-09-30', 'Listo', 38.00, 35.00),
(14, 3, 1, 1, 'Tarea Pendiente: Intervalos de confianza', '2025-10-15', 'En curso', 2.00, NULL),
(15, 3, 5, 1, 'Ejercicio: Cálculo de probabilidades', '2025-10-18', 'Listo', 5.00, 5.00),
(22, 5, 1, 1, 'Tarea 1: Cinemática', '2025-09-05', 'Listo', 10.00, 5.00),
(23, 5, 1, 1, 'Tarea 2: Leyes de Newton', '2025-09-12', 'Listo', 10.00, 5.00),
(24, 5, 2, 1, 'Proyecto: Análisis de fuerzas', '2025-09-25', 'Listo', 20.00, 10.00),
(25, 5, 3, 1, 'Examen Parcial I', '2025-10-03', 'Listo', 40.00, 20.00),
(26, 5, 4, 1, 'Exposición: Movimiento circular', '2025-10-10', 'Listo', 20.00, 10.00),
(27, 6, 1, 1, 'Tarea 1: Fundamentos HTML', '2025-09-03', 'Listo', 10.00, 6.00),
(28, 6, 1, 1, 'Tarea 2: CSS responsivo', '2025-09-12', 'Listo', 10.00, 6.00),
(29, 6, 2, 1, 'Proyecto: Mini sitio web', '2025-09-28', 'Listo', 20.00, 12.00),
(30, 6, 3, 1, 'Examen Parcial I', '2025-10-04', 'Listo', 40.00, 24.00),
(31, 6, 4, 1, 'Exposición: Accesibilidad Web', '2025-10-11', 'Listo', 20.00, 12.00);

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
(2, 1, 'Cálculo Integral', 70, 82.20, 44.20, 15.80, 40.00),
(3, 1, 'Inferencia Estadística', 70, 97.32, 57.32, 2.68, 40.00),
(5, 1, 'Física General I', 70, 50.00, 40.00, 40.00, 20.00),
(6, 1, 'Programación Web', 70, 60.00, 48.00, 32.00, 20.00);

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
(5, 1, 40.00),
(5, 2, 20.00),
(5, 3, 30.00),
(5, 4, 10.00),
(5, 5, 0.00),
(6, 1, 40.00),
(6, 2, 20.00),
(6, 3, 30.00),
(6, 4, 10.00),
(6, 5, 0.00);

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
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
