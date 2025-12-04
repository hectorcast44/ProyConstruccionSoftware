-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2025 a las 01:38:36
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
(31, 6, 4, 1, 'Exposición: Accesibilidad Web', '2025-10-11', 'Listo', 20.00, 12.00),
(32, 2, 3, 1, 'Examen 1', '2025-12-01', 'pendiente', 20.00, 15.00),
(52, 223, 11, 7, 'Quizz 1', '2025-11-29', 'pendiente', 30.00, 28.20),
(54, 224, 1, 7, 'ADA I', '2025-12-03', 'pendiente', 10.00, 10.00),
(55, 224, 5, 7, 'ADA II', '2025-12-03', 'pendiente', 10.00, 8.00),
(56, 224, 1, 7, 'ADA III', '2025-12-03', 'pendiente', 5.00, 5.00),
(57, 224, 3, 7, 'PD I AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', '2025-12-03', 'pendiente', 5.00, 5.00),
(208, 235, 17, 8, 'Tarea 1: Tema 1', '2025-12-13', 'En curso', 7.60, NULL),
(209, 235, 17, 8, 'Tarea 2: Tema 5', '2025-12-15', 'pendiente', 3.06, NULL),
(210, 235, 17, 8, 'Tarea 3: Tema 5', '2025-10-14', 'pendiente', 6.04, NULL),
(211, 235, 17, 8, 'Tarea 4: Tema 1', '2025-12-07', 'Listo', 8.30, 7.52),
(212, 235, 18, 8, 'Proyecto 1: Tema 2', '2025-10-03', 'Listo', 6.58, 1.42),
(213, 235, 18, 8, 'Proyecto 2: Tema 5', '2025-11-04', 'En curso', 1.85, NULL),
(214, 235, 18, 8, 'Proyecto 3: Tema 5', '2025-09-09', 'Listo', 5.03, 1.10),
(215, 235, 18, 8, 'Proyecto 4: Tema 2', '2025-12-15', 'Listo', 6.54, 2.94),
(216, 235, 19, 8, 'Examen 1: Tema 1', '2025-11-14', 'Listo', 25.00, 17.66),
(217, 235, 20, 8, 'Participación 1: Tema 1', '2025-10-13', 'pendiente', 6.56, NULL),
(218, 235, 20, 8, 'Participación 2: Tema 2', '2025-10-22', 'Listo', 8.44, 4.52),
(219, 235, 21, 8, 'Laboratorio 1: Tema 5', '2025-09-10', 'En curso', 7.69, NULL),
(220, 235, 21, 8, 'Laboratorio 2: Tema 5', '2025-09-03', 'pendiente', 2.69, NULL),
(221, 235, 21, 8, 'Laboratorio 3: Tema 2', '2025-09-20', 'En curso', 1.34, NULL),
(222, 235, 21, 8, 'Laboratorio 4: Tema 1', '2025-11-11', 'En curso', 3.28, NULL),
(223, 236, 17, 8, 'Tarea 1: Tema 3', '2025-12-15', 'pendiente', 24.22, NULL),
(224, 236, 17, 8, 'Tarea 2: Tema 3', '2025-09-14', 'En curso', 4.62, NULL),
(225, 236, 17, 8, 'Tarea 3: Tema 2', '2025-09-25', 'En curso', 21.16, NULL),
(226, 236, 18, 8, 'Proyecto 1: Tema 2', '2025-11-20', 'pendiente', 1.68, NULL),
(227, 236, 18, 8, 'Proyecto 2: Tema 4', '2025-12-15', 'pendiente', 2.58, NULL),
(228, 236, 18, 8, 'Proyecto 3: Tema 2', '2025-11-21', 'En curso', 5.74, NULL),
(229, 236, 19, 8, 'Examen 1: Tema 5', '2025-09-21', 'pendiente', 6.71, NULL),
(230, 236, 19, 8, 'Examen 2: Tema 1', '2025-10-12', 'Listo', 2.26, 0.65),
(231, 236, 19, 8, 'Examen 3: Tema 5', '2025-10-24', 'En curso', 4.96, NULL),
(232, 236, 19, 8, 'Examen 4: Tema 5', '2025-12-14', 'Listo', 6.07, 3.10),
(233, 236, 20, 8, 'Participación 1: Tema 4', '2025-10-24', 'En curso', 1.87, NULL),
(234, 236, 20, 8, 'Participación 2: Tema 3', '2025-10-16', 'En curso', 3.87, NULL),
(235, 236, 20, 8, 'Participación 3: Tema 4', '2025-09-04', 'pendiente', 4.26, NULL),
(236, 236, 21, 8, 'Laboratorio 1: Tema 4', '2025-12-03', 'En curso', 2.35, NULL),
(237, 236, 21, 8, 'Laboratorio 2: Tema 2', '2025-11-22', 'pendiente', 7.65, NULL),
(238, 237, 17, 8, 'Tarea 1: Tema 3', '2025-11-16', 'En curso', 4.10, NULL),
(239, 237, 17, 8, 'Tarea 2: Tema 4', '2025-11-07', 'En curso', 5.90, NULL),
(240, 237, 18, 8, 'Proyecto 1: Tema 4', '2025-10-18', 'En curso', 1.00, NULL),
(241, 237, 18, 8, 'Proyecto 2: Tema 1', '2025-11-21', 'Listo', 4.79, 1.60),
(242, 237, 18, 8, 'Proyecto 3: Tema 4', '2025-09-15', 'Listo', 4.21, 2.57),
(243, 237, 19, 8, 'Examen 1: Tema 3', '2025-10-22', 'En curso', 50.00, NULL),
(244, 237, 20, 8, 'Participación 1: Tema 3', '2025-09-30', 'pendiente', 1.62, NULL),
(245, 237, 20, 8, 'Participación 2: Tema 5', '2025-10-01', 'Listo', 2.99, 1.99),
(246, 237, 20, 8, 'Participación 3: Tema 2', '2025-10-27', 'Listo', 1.09, 0.20),
(247, 237, 20, 8, 'Participación 4: Tema 3', '2025-10-11', 'pendiente', 2.49, NULL),
(248, 237, 20, 8, 'Participación 5: Tema 1', '2025-09-27', 'Listo', 1.81, 1.39),
(249, 237, 21, 8, 'Laboratorio 1: Tema 4', '2025-12-12', 'Listo', 6.80, 6.41),
(250, 237, 21, 8, 'Laboratorio 2: Tema 5', '2025-10-07', 'pendiente', 0.88, NULL),
(251, 237, 21, 8, 'Laboratorio 3: Tema 3', '2025-10-03', 'Listo', 5.49, 1.64),
(252, 237, 21, 8, 'Laboratorio 4: Tema 4', '2025-10-24', 'En curso', 6.83, NULL),
(253, 238, 17, 8, 'Tarea 1: Tema 2', '2025-10-02', 'Listo', 5.01, 2.43),
(254, 238, 17, 8, 'Tarea 2: Tema 4', '2025-09-29', 'Listo', 10.76, 5.40),
(255, 238, 17, 8, 'Tarea 3: Tema 1', '2025-11-15', 'En curso', 4.23, NULL),
(256, 238, 18, 8, 'Proyecto 1: Tema 1', '2025-10-15', 'Listo', 1.48, 0.00),
(257, 238, 18, 8, 'Proyecto 2: Tema 3', '2025-10-08', 'En curso', 5.21, NULL),
(258, 238, 18, 8, 'Proyecto 3: Tema 4', '2025-12-11', 'pendiente', 3.31, NULL),
(259, 238, 19, 8, 'Examen 1: Tema 5', '2025-11-17', 'Listo', 7.50, 1.97),
(260, 238, 19, 8, 'Examen 2: Tema 4', '2025-10-11', 'En curso', 5.52, NULL),
(261, 238, 19, 8, 'Examen 3: Tema 4', '2025-09-05', 'Listo', 6.98, 3.68),
(262, 238, 20, 8, 'Participación 1: Tema 3', '2025-09-01', 'pendiente', 4.70, NULL),
(263, 238, 20, 8, 'Participación 2: Tema 5', '2025-10-23', 'Listo', 2.16, 2.01),
(264, 238, 20, 8, 'Participación 3: Tema 5', '2025-09-29', 'pendiente', 3.14, NULL),
(265, 238, 21, 8, 'Laboratorio 1: Tema 1', '2025-10-24', 'pendiente', 10.46, NULL),
(266, 238, 21, 8, 'Laboratorio 2: Tema 3', '2025-11-05', 'Listo', 16.09, 15.81),
(267, 238, 21, 8, 'Laboratorio 3: Tema 2', '2025-11-22', 'En curso', 13.45, NULL),
(268, 239, 17, 8, 'Tarea 1: Tema 2', '2025-12-11', 'Listo', 4.38, 3.99),
(269, 239, 17, 8, 'Tarea 2: Tema 3', '2025-12-07', 'pendiente', 4.35, NULL),
(270, 239, 17, 8, 'Tarea 3: Tema 5', '2025-09-12', 'En curso', 11.27, NULL),
(271, 239, 18, 8, 'Proyecto 1: Tema 4', '2025-11-17', 'Listo', 17.17, 13.66),
(272, 239, 18, 8, 'Proyecto 2: Tema 3', '2025-10-04', 'En curso', 2.83, NULL),
(273, 239, 19, 8, 'Examen 1: Tema 3', '2025-11-11', 'En curso', 10.00, NULL),
(274, 239, 20, 8, 'Participación 1: Tema 4', '2025-11-24', 'Listo', 2.04, 0.33),
(275, 239, 20, 8, 'Participación 2: Tema 1', '2025-09-30', 'pendiente', 2.93, NULL),
(276, 239, 20, 8, 'Participación 3: Tema 5', '2025-09-30', 'Listo', 3.23, 0.97),
(277, 239, 20, 8, 'Participación 4: Tema 2', '2025-11-30', 'pendiente', 1.10, NULL),
(278, 239, 20, 8, 'Participación 5: Tema 4', '2025-12-11', 'pendiente', 0.70, NULL),
(279, 239, 21, 8, 'Laboratorio 1: Tema 5', '2025-11-05', 'Listo', 10.49, 3.17),
(280, 239, 21, 8, 'Laboratorio 2: Tema 5', '2025-10-23', 'Listo', 5.63, 0.49),
(281, 239, 21, 8, 'Laboratorio 3: Tema 2', '2025-10-02', 'Listo', 12.00, 3.68),
(282, 239, 21, 8, 'Laboratorio 4: Tema 3', '2025-10-01', 'En curso', 11.88, NULL),
(283, 240, 17, 8, 'Tarea 1: Tema 5', '2025-10-18', 'Listo', 4.11, 0.90),
(284, 240, 17, 8, 'Tarea 2: Tema 3', '2025-09-29', 'En curso', 5.16, NULL),
(285, 240, 17, 8, 'Tarea 3: Tema 2', '2025-11-12', 'Listo', 0.73, 0.20),
(286, 240, 18, 8, 'Proyecto 1: Tema 5', '2025-09-18', 'En curso', 2.35, NULL),
(287, 240, 18, 8, 'Proyecto 2: Tema 3', '2025-10-15', 'En curso', 3.21, NULL),
(288, 240, 18, 8, 'Proyecto 3: Tema 2', '2025-09-25', 'Listo', 5.00, 0.80),
(289, 240, 18, 8, 'Proyecto 4: Tema 4', '2025-09-25', 'pendiente', 4.87, NULL),
(290, 240, 18, 8, 'Proyecto 5: Tema 1', '2025-09-03', 'En curso', 4.57, NULL),
(291, 240, 19, 8, 'Examen 1: Tema 4', '2025-10-11', 'En curso', 21.13, NULL),
(292, 240, 19, 8, 'Examen 2: Tema 4', '2025-12-04', 'Listo', 18.87, 3.20),
(293, 240, 20, 8, 'Participación 1: Tema 4', '2025-11-24', 'Listo', 13.46, 8.70),
(294, 240, 20, 8, 'Participación 2: Tema 3', '2025-09-07', 'En curso', 6.54, NULL),
(295, 240, 21, 8, 'Laboratorio 1: Tema 2', '2025-11-13', 'Listo', 3.87, 1.99),
(296, 240, 21, 8, 'Laboratorio 2: Tema 2', '2025-10-24', 'pendiente', 2.82, NULL),
(297, 240, 21, 8, 'Laboratorio 3: Tema 5', '2025-12-14', 'pendiente', 3.31, NULL),
(298, 241, 17, 8, 'Tarea 1: Tema 2', '2025-10-09', 'En curso', 4.35, NULL),
(299, 241, 17, 8, 'Tarea 2: Tema 4', '2025-09-14', 'pendiente', 10.65, NULL),
(300, 241, 18, 8, 'Proyecto 1: Tema 5', '2025-12-13', 'pendiente', 9.09, NULL),
(301, 241, 18, 8, 'Proyecto 2: Tema 2', '2025-11-19', 'pendiente', 10.91, NULL),
(302, 241, 19, 8, 'Examen 1: Tema 5', '2025-12-14', 'En curso', 3.41, NULL),
(303, 241, 19, 8, 'Examen 2: Tema 2', '2025-12-02', 'pendiente', 4.00, NULL),
(304, 241, 19, 8, 'Examen 3: Tema 1', '2025-09-27', 'pendiente', 3.56, NULL),
(305, 241, 19, 8, 'Examen 4: Tema 4', '2025-09-12', 'pendiente', 2.64, NULL),
(306, 241, 19, 8, 'Examen 5: Tema 3', '2025-09-15', 'En curso', 1.39, NULL),
(307, 241, 20, 8, 'Participación 1: Tema 4', '2025-10-26', 'Listo', 25.00, 3.38),
(308, 241, 21, 8, 'Laboratorio 1: Tema 1', '2025-11-18', 'En curso', 2.09, NULL),
(309, 241, 21, 8, 'Laboratorio 2: Tema 1', '2025-11-29', 'pendiente', 4.88, NULL),
(310, 241, 21, 8, 'Laboratorio 3: Tema 2', '2025-09-21', 'En curso', 4.94, NULL),
(311, 241, 21, 8, 'Laboratorio 4: Tema 5', '2025-12-14', 'Listo', 10.38, 4.05),
(312, 241, 21, 8, 'Laboratorio 5: Tema 4', '2025-10-22', 'pendiente', 2.71, NULL),
(313, 242, 17, 8, 'Tarea 1: Tema 3', '2025-12-08', 'En curso', 2.65, NULL),
(314, 242, 17, 8, 'Tarea 2: Tema 1', '2025-09-11', 'Listo', 2.43, 2.36),
(315, 242, 17, 8, 'Tarea 3: Tema 4', '2025-10-23', 'Listo', 4.13, 3.89),
(316, 242, 17, 8, 'Tarea 4: Tema 1', '2025-12-13', 'pendiente', 0.79, NULL),
(317, 242, 18, 8, 'Proyecto 1: Tema 5', '2025-09-18', 'En curso', 3.38, NULL),
(318, 242, 18, 8, 'Proyecto 2: Tema 5', '2025-10-08', 'pendiente', 3.01, NULL),
(319, 242, 18, 8, 'Proyecto 3: Tema 3', '2025-11-22', 'En curso', 3.61, NULL),
(320, 242, 19, 8, 'Examen 1: Tema 3', '2025-09-30', 'Listo', 3.28, 2.62),
(321, 242, 19, 8, 'Examen 2: Tema 4', '2025-12-15', 'Listo', 3.04, 2.12),
(322, 242, 19, 8, 'Examen 3: Tema 4', '2025-10-10', 'Listo', 0.71, 0.10),
(323, 242, 19, 8, 'Examen 4: Tema 4', '2025-09-01', 'Listo', 2.97, 2.75),
(324, 242, 20, 8, 'Participación 1: Tema 1', '2025-10-25', 'Listo', 8.94, 3.02),
(325, 242, 20, 8, 'Participación 2: Tema 3', '2025-11-02', 'Listo', 11.06, 5.74),
(326, 242, 21, 8, 'Laboratorio 1: Tema 1', '2025-09-27', 'pendiente', 12.24, NULL),
(327, 242, 21, 8, 'Laboratorio 2: Tema 5', '2025-12-06', 'En curso', 37.76, NULL),
(328, 243, 17, 8, 'Tarea 1: Tema 3', '2025-12-15', 'pendiente', 7.42, NULL),
(329, 243, 17, 8, 'Tarea 2: Tema 2', '2025-09-09', 'pendiente', 12.58, NULL),
(330, 243, 18, 8, 'Proyecto 1: Tema 4', '2025-10-25', 'En curso', 4.97, NULL),
(331, 243, 18, 8, 'Proyecto 2: Tema 1', '2025-12-10', 'Listo', 13.21, 8.21),
(332, 243, 18, 8, 'Proyecto 3: Tema 1', '2025-10-19', 'Listo', 1.82, 0.03),
(333, 243, 19, 8, 'Examen 1: Tema 2', '2025-11-08', 'pendiente', 9.53, NULL),
(334, 243, 19, 8, 'Examen 2: Tema 5', '2025-10-23', 'En curso', 10.47, NULL),
(335, 243, 20, 8, 'Participación 1: Tema 2', '2025-12-13', 'pendiente', 2.03, NULL),
(336, 243, 20, 8, 'Participación 2: Tema 1', '2025-10-17', 'pendiente', 3.64, NULL),
(337, 243, 20, 8, 'Participación 3: Tema 3', '2025-10-01', 'pendiente', 2.21, NULL),
(338, 243, 20, 8, 'Participación 4: Tema 2', '2025-11-26', 'pendiente', 3.53, NULL),
(339, 243, 20, 8, 'Participación 5: Tema 2', '2025-09-10', 'En curso', 2.97, NULL),
(340, 243, 20, 8, 'Participación 6: Tema 1', '2025-09-08', 'En curso', 1.02, NULL),
(341, 243, 20, 8, 'Participación 7: Tema 3', '2025-11-20', 'Listo', 4.60, 3.03),
(342, 243, 21, 8, 'Laboratorio 1: Tema 4', '2025-11-24', 'pendiente', 20.00, NULL),
(343, 244, 17, 8, 'Tarea 1: Tema 2', '2025-11-13', 'Listo', 20.00, 14.01),
(344, 244, 18, 8, 'Proyecto 1: Tema 2', '2025-11-29', 'Listo', 1.15, 1.06),
(345, 244, 18, 8, 'Proyecto 2: Tema 1', '2025-12-07', 'pendiente', 2.63, NULL),
(346, 244, 18, 8, 'Proyecto 3: Tema 3', '2025-09-27', 'pendiente', 3.67, NULL),
(347, 244, 18, 8, 'Proyecto 4: Tema 3', '2025-12-14', 'En curso', 2.55, NULL),
(348, 244, 19, 8, 'Examen 1: Tema 5', '2025-12-11', 'pendiente', 17.34, NULL),
(349, 244, 19, 8, 'Examen 2: Tema 1', '2025-12-01', 'Listo', 22.66, 18.96),
(350, 244, 20, 8, 'Participación 1: Tema 1', '2025-10-09', 'pendiente', 3.65, NULL),
(351, 244, 20, 8, 'Participación 2: Tema 1', '2025-12-13', 'Listo', 6.51, 2.06),
(352, 244, 20, 8, 'Participación 3: Tema 1', '2025-11-17', 'Listo', 1.79, 0.25),
(353, 244, 20, 8, 'Participación 4: Tema 1', '2025-09-30', 'pendiente', 8.05, NULL),
(354, 244, 21, 8, 'Laboratorio 1: Tema 1', '2025-10-12', 'En curso', 1.83, NULL),
(355, 244, 21, 8, 'Laboratorio 2: Tema 4', '2025-10-24', 'En curso', 3.99, NULL),
(356, 244, 21, 8, 'Laboratorio 3: Tema 5', '2025-09-12', 'En curso', 1.60, NULL),
(357, 244, 21, 8, 'Laboratorio 4: Tema 3', '2025-12-04', 'pendiente', 2.58, NULL);

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
(2, 1, 'Cálculo Integral', 70, 82.33, 44.33, 15.67, 40.00),
(3, 1, 'Inferencia Estadística', 70, 97.32, 57.32, 2.68, 40.00),
(5, 1, 'Física General I', 70, 50.00, 40.00, 40.00, 20.00),
(6, 1, 'Programación Web', 70, 60.00, 48.00, 32.00, 20.00),
(223, 7, 'Advanced II', 70, 28.20, 28.20, 1.80, 70.00),
(224, 7, 'Inferencia Estadística', 70, 28.00, 28.00, 2.00, 70.00),
(235, 8, 'Matemáticas Avanzadas', 70, 35.16, 35.16, 24.73, 40.11),
(236, 8, 'Historia Universal', 70, 3.75, 3.75, 4.58, 91.67),
(237, 8, 'Ciencias Naturales', 70, 15.80, 15.80, 11.38, 72.82),
(238, 8, 'Literatura Clásica', 70, 31.30, 31.30, 18.68, 50.02),
(239, 8, 'Arte y Diseño', 70, 26.29, 26.29, 28.65, 45.06),
(240, 8, 'Educación Física', 70, 15.79, 15.79, 30.25, 53.96),
(241, 8, 'Computación Básica', 70, 7.43, 7.43, 27.95, 64.62),
(242, 8, 'Inglés Intermedio', 70, 22.60, 22.60, 13.96, 63.44),
(243, 8, 'Física Aplicada', 70, 11.27, 11.27, 8.36, 80.37),
(244, 8, 'Química Orgánica', 70, 36.34, 36.34, 15.77, 47.89);

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
(6, 5, 0.00),
(223, 10, 40.00),
(223, 11, 60.00),
(224, 1, 25.00),
(224, 3, 65.00),
(224, 5, 10.00),
(235, 17, 25.00),
(235, 18, 20.00),
(235, 19, 25.00),
(235, 20, 15.00),
(235, 21, 15.00),
(236, 17, 50.00),
(236, 18, 10.00),
(236, 19, 20.00),
(236, 20, 10.00),
(236, 21, 10.00),
(237, 17, 10.00),
(237, 18, 10.00),
(237, 19, 50.00),
(237, 20, 10.00),
(237, 21, 20.00),
(238, 17, 20.00),
(238, 18, 10.00),
(238, 19, 20.00),
(238, 20, 10.00),
(238, 21, 40.00),
(239, 17, 20.00),
(239, 18, 20.00),
(239, 19, 10.00),
(239, 20, 10.00),
(239, 21, 40.00),
(240, 17, 10.00),
(240, 18, 20.00),
(240, 19, 40.00),
(240, 20, 20.00),
(240, 21, 10.00),
(241, 17, 15.00),
(241, 18, 20.00),
(241, 19, 15.00),
(241, 20, 25.00),
(241, 21, 25.00),
(242, 17, 10.00),
(242, 18, 10.00),
(242, 19, 10.00),
(242, 20, 20.00),
(242, 21, 50.00),
(243, 17, 20.00),
(243, 18, 20.00),
(243, 19, 20.00),
(243, 20, 20.00),
(243, 21, 20.00),
(244, 17, 20.00),
(244, 18, 10.00),
(244, 19, 40.00),
(244, 20, 20.00),
(244, 21, 10.00);

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
(5, 1, 'Ejercicio'),
(10, 7, 'Final Exam'),
(11, 7, 'Quizz'),
(17, 8, 'Tarea'),
(18, 8, 'Proyecto'),
(19, 8, 'Examen'),
(20, 8, 'Participación'),
(21, 8, 'Laboratorio');

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
(1, 'admin@correo.com', '$2y$10$7JQZOtMLLVFFCSIlWB94TugCng2ifAoMlynRIIoQlQ7rHdAdQrKsO', 'Administrador'),
(3, 'elika234@hotmail.com', '$2y$10$Hm2bvHnVSyzHJBSvA0tfJOH/itHladpHV/FxWptbKvL1GQG8v/kLm', 'Karen'),
(4, 'a23216420@alumnos.uady.mx', '$2y$10$tTxOxTYJuxsm.PR8BcgrcegPnzb23T8Iq4jqgUQr3BbBrrzI1G49G', 'Joseph Garcia'),
(6, 'juan.perez@hotmail.com', '$2y$10$1NVtID27jMN6c8O6QdlaAeU0SARIPVek60laDx67MpOJWweVIdsJ6', 'juan perez'),
(7, 'joseph.antonio.garcia@gmail.com', '$2y$10$jMmNJHFWoqtb51LmfBm0P.jPYorvjXlVmLt1aJC.IMloFFNW.BhDS', 'Joseph Garcia'),
(8, 'josefo.antoni@gmail.com', '$2y$10$jZ.tPeh1vrUkKVtWLB8nKOICAdunJ4OjsI0sGpht.8idppKwxsbuG', 'Josefo Antoni');

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
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT de la tabla `tipo_actividad`
--
ALTER TABLE `tipo_actividad`
  MODIFY `id_tipo_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
