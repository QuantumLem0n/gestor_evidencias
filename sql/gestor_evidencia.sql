-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-04-2026 a las 07:43:54
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
-- Base de datos: `gestor_evidencia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atributos_tipo_evidencia`
--

CREATE TABLE `atributos_tipo_evidencia` (
  `id_ate` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `id_tipo_atributo` int(11) NOT NULL,
  `nombre_atributo` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `requerido` tinyint(1) NOT NULL DEFAULT 0,
  `unico_por_evidencia` tinyint(1) NOT NULL DEFAULT 0,
  `multiple` tinyint(1) NOT NULL DEFAULT 0,
  `min_longitud` int(11) DEFAULT NULL,
  `max_longitud` int(11) DEFAULT NULL,
  `min_valor` decimal(18,6) DEFAULT NULL,
  `max_valor` decimal(18,6) DEFAULT NULL,
  `opciones_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opciones_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `atributos_tipo_evidencia`
--

INSERT INTO `atributos_tipo_evidencia` (`id_ate`, `id_tipo_evidencia`, `id_tipo_atributo`, `nombre_atributo`, `slug`, `descripcion`, `orden`, `requerido`, `unico_por_evidencia`, `multiple`, `min_longitud`, `max_longitud`, `min_valor`, `max_valor`, `opciones_json`) VALUES
(1, 1, 2, 'Autores', 'autores', 'Lista de autores (Nombre Apellido; ...)', 1, 0, 0, 0, NULL, 2000, NULL, NULL, NULL),
(4, 1, 1, 'Título', 'titulo', 'Título de la obra', 2, 1, 1, 0, NULL, 500, NULL, NULL, NULL),
(5, 1, 3, 'Año de publicación', 'anio_publicacion', 'Ej. 2024', 3, 1, 0, 0, NULL, NULL, 1500.000000, 2100.000000, NULL),
(6, 1, 1, 'Editorial', 'editorial', 'Nombre de la editorial', 4, 1, 0, 0, NULL, 255, NULL, NULL, NULL),
(8, 1, 3, 'Número de páginas', 'paginas', 'Total de páginas', 6, 0, 0, 0, NULL, NULL, 1.000000, 20000.000000, NULL),
(9, 1, 12, 'URL', 'url', 'Enlace al recurso', 7, 0, 0, 0, NULL, 500, NULL, NULL, NULL),
(10, 2, 1, 'Nombre del evento', 'nombre_evento', 'Nombre del congreso / conferencia', 1, 1, 0, 0, NULL, 255, NULL, NULL, NULL),
(11, 2, 5, 'Fecha del evento', 'fecha_evento', 'Fecha principal del evento', 2, 1, 0, 0, NULL, NULL, NULL, NULL, NULL),
(12, 2, 1, 'Ciudad', 'ciudad', 'Ciudad sede', 3, 0, 0, 0, NULL, 120, NULL, NULL, NULL),
(13, 2, 8, 'País', 'pais', 'País sede', 4, 0, 0, 0, NULL, NULL, NULL, NULL, '[\"México\", \"Estados Unidos\", \"España\", \"Argentina\", \"Chile\"]'),
(14, 2, 3, 'Horas impartidas', 'horas', 'Duración en horas', 5, 0, 0, 0, NULL, NULL, 1.000000, 500.000000, NULL),
(15, 2, 7, 'Constancia (archivo)', 'constancia_archivo', 'Archivo adicional PDF/JPG', 6, 0, 0, 0, NULL, NULL, NULL, NULL, NULL),
(18, 1, 1, 'ISBN', 'isbn', 'ISBN del libro', 8, 1, 1, 0, 10, 15, NULL, NULL, NULL),
(19, 3, 2, 'Nombre del Diplomado', 'nombre_diplomado', 'Titulo del Diplomado impartido', 1, 1, 0, 0, 1, 500, NULL, NULL, NULL),
(20, 3, 1, 'duracion', 'duracion', 'Duración en meses, días u horas del curso', 2, 1, 0, 0, 5, 100, NULL, NULL, NULL),
(21, 3, 1, 'Programa de estudio', 'programa', 'Programa de estudio al que pertenece', 3, 1, 0, 0, 1, 200, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion_evidencia`
--

CREATE TABLE `calificacion_evidencia` (
  `id_calificacion` int(11) NOT NULL,
  `id_evidencia` int(11) NOT NULL,
  `id_instrumento` int(11) NOT NULL,
  `resultado` decimal(6,2) NOT NULL,
  `comentario` varchar(1000) DEFAULT NULL,
  `id_usuario_eval` int(11) DEFAULT NULL,
  `calificado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificacion_evidencia`
--

INSERT INTO `calificacion_evidencia` (`id_calificacion`, `id_evidencia`, `id_instrumento`, `resultado`, `comentario`, `id_usuario_eval`, `calificado_en`, `actualizado_en`) VALUES
(1, 9, 1, 1.00, 'Paso', 1, '2025-10-29 04:45:52', '2025-11-08 02:10:01'),
(2, 9, 2, 1.00, 'paso', 1, '2025-10-29 04:46:15', '2025-10-30 01:56:57'),
(3, 9, 3, 1.00, 'Completo', 1, '2025-10-29 04:46:23', '2025-11-08 02:10:05'),
(5, 10, 1, 1.00, '', 3, '2025-10-29 06:01:21', '2026-02-18 04:05:35'),
(9, 10, 2, 0.00, '', 3, '2025-10-30 01:57:27', '2026-02-18 04:05:33'),
(10, 10, 3, 1.00, '', 1, '2025-10-30 01:57:30', '2025-10-30 01:57:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias`
--

CREATE TABLE `evidencias` (
  `id_evidencia` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_docente` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `ocultar` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evidencias`
--

INSERT INTO `evidencias` (`id_evidencia`, `titulo`, `archivo`, `fecha_subida`, `id_docente`, `id_tipo_evidencia`, `ocultar`) VALUES
(9, 'Drácula de Bram Stocker', 'dr_cula_de_bram_stocker_20251023_061305_a41a82.pdf', '2025-10-23 04:13:05', 14, 1, 0),
(10, 'El color que surgió del espacio Editado', 'el_color_que_surgi_del_espacio_20251023_061546_924508.pdf', '2025-10-23 04:15:46', 14, 1, 0),
(11, '1986', 'diplomado_20251025_012625_f8368a.pdf', '2025-10-24 23:26:25', 14, 1, 0),
(12, 'Nueva evidencia', 'nueva_evidencia_20260218_051044_185055.pdf', '2026-02-18 04:10:44', 14, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencia_valores_atributo`
--

CREATE TABLE `evidencia_valores_atributo` (
  `id_eva` bigint(20) NOT NULL,
  `id_evidencia` int(11) NOT NULL,
  `id_ate` int(11) NOT NULL,
  `indice` int(11) NOT NULL DEFAULT 1,
  `valor_texto` varchar(500) DEFAULT NULL,
  `valor_largo` text DEFAULT NULL,
  `valor_int` int(11) DEFAULT NULL,
  `valor_decimal` decimal(18,6) DEFAULT NULL,
  `valor_fecha` date DEFAULT NULL,
  `valor_bool` tinyint(1) DEFAULT NULL,
  `valor_archivo` varchar(255) DEFAULT NULL,
  `valor_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evidencia_valores_atributo`
--

INSERT INTO `evidencia_valores_atributo` (`id_eva`, `id_evidencia`, `id_ate`, `indice`, `valor_texto`, `valor_largo`, `valor_int`, `valor_decimal`, `valor_fecha`, `valor_bool`, `valor_archivo`, `valor_json`, `created_at`, `updated_at`) VALUES
(42, 9, 1, 1, NULL, 'Juan Carlos Perez, Bram Stoker, Victor Limon', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(43, 9, 4, 1, 'Drácula', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(44, 9, 5, 1, NULL, NULL, 1800, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(45, 9, 6, 1, 'Alba', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(46, 9, 8, 1, NULL, NULL, 200, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(47, 9, 9, 1, 'https://www.suneo.mx/literatura/subidas/Bram%20Sttoker%20Dracula.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(48, 9, 18, 1, '1234567890', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 23:25:17', '2025-10-24 23:25:17'),
(49, 10, 1, 1, NULL, 'H. P. Lovecraft', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(50, 10, 4, 1, 'The Colour Out of Space', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(51, 10, 5, 1, NULL, NULL, 1927, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(52, 10, 6, 1, 'La revista Amazing Stories', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(53, 10, 8, 1, NULL, NULL, 80, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(54, 10, 9, 1, 'https://www.amazon.com.mx/color-que-cay%C3%B3-del-espacio/dp/8418067977', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(55, 10, 18, 1, '111111111111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-29 05:06:07', '2025-10-29 05:06:07'),
(66, 12, 1, 1, NULL, 'Juanito', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(67, 12, 4, 1, 'Nueva evidencia', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(68, 12, 5, 1, NULL, NULL, 2026, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(69, 12, 6, 1, 'BUAP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(70, 12, 8, 1, NULL, NULL, 300, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(71, 12, 9, 1, 'https://www.canva.com/design/DAG3Ouf5k_0/3JIWJvbPT4OjC7TCgOBV2A/edit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56'),
(72, 12, 18, 1, '123221333333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 04:11:56', '2026-02-18 04:11:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iconos`
--

CREATE TABLE `iconos` (
  `id_icono` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `imagen` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `iconos`
--

INSERT INTO `iconos` (`id_icono`, `descripcion`, `imagen`) VALUES
(1, 'Home', '<path d=\"M3 21V10l9-7 9 7v11\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/><path d=\"M9 21v-6h6v6\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/>'),
(2, 'Personas', '<path d=\"M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><circle cx=\"9\" cy=\"7\" r=\"4\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M22 21v-2a4 4 0 0 0-3-3.87\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(3, 'Llave', '<circle cx=\"7.5\" cy=\"15.5\" r=\"3.5\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M10.5 15.5H22M15 12v7\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(4, 'Calendario', '<rect x=\"3\" y=\"5\" width=\"18\" height=\"16\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M16 3v4M8 3v4M3 11h18\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(5, 'Libro', '<path d=\"M2 7a4 4 0 0 1 4-4h6v18H6a4 4 0 0 0-4 4V7Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/><path d=\"M22 7a4 4 0 0 0-4-4h-6v18h6a4 4 0 0 1 4 4V7Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/>'),
(6, 'Birrete', '<path d=\"M22 10L12 5 2 10l10 5 10-5Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/><path d=\"M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>'),
(7, 'Configuracion', '<path d=\"M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M19.4 15a1.8 1.8 0 0 0 .36 1.98l.07.07a2.1 2.1 0 1 1-2.97 2.97l-.07-.07A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1.5.8l-.04.06a2.1 2.1 0 0 1-3.92 0l-.04-.06A1.8 1.8 0 0 0 8 19.4a1.8 1.8 0 0 0-1.98.36l-.07.07a2.1 2.1 0 1 1-2.97-2.97l.07-.07A1.8 1.8 0 0 0 4.6 15c0-.42-.14-.83-.4-1.16l-.07-.07a2.1 2.1 0 1 1 2.97-2.97l.07.07c.33.26.74.4 1.16.4s.83-.14 1.16-.4l.07-.07a2.1 2.1 0 1 1 2.97 2.97l-.07.07c-.26.33-.4.74-.4 1.16Z\" stroke=\"currentColor\" stroke-width=\"1.3\" stroke-linecap=\"round\"/>'),
(8, 'Help', '<circle cx=\"12\" cy=\"12\" r=\"9\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.7.35-1 1-1 1.7V14\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><circle cx=\"12\" cy=\"17\" r=\"1\" fill=\"currentColor\"/>'),
(9, 'Check', '<path d=\"M9 5h6a2 2 0 0 1 2 2v2H7V7a2 2 0 0 1 2-2Z\" stroke=\"currentColor\" stroke-width=\"1.8\"/><rect x=\"7\" y=\"9\" width=\"10\" height=\"10\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M9.5 14l1.5 1.5L14.5 12\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>'),
(10, 'Documento', '<path d=\"M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M14 2v6h6\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M8 13h8M8 17h8M8 9h3\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(11, 'Uploads', '<path d=\"M12 16V8\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M8 12l4-4 4 4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2\" stroke=\"currentColor\" stroke-width=\"1.8\"/>'),
(12, 'Video', '<rect x=\"3\" y=\"6\" width=\"13\" height=\"12\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M16 10l5-3v10l-5-3v-4Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/>'),
(13, 'Perfil', '<circle cx=\"12\" cy=\"12\" r=\"9\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M6.5 18c1.6-2.3 4-3.5 5.5-3.5S15.9 15.7 17.5 18\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(14, 'Tareas', '<rect x=\"5\" y=\"3\" width=\"14\" height=\"18\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M9 3.5h6\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M8 10l2 2 4-4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>'),
(15, 'Asistencia', '<rect x=\"3\" y=\"6\" width=\"18\" height=\"14\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M7 4v4M17 4v4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M8 13l2 2 4-4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>'),
(16, 'Mensajes', '<path d=\"M4 6h10a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H9l-5 3V9a3 3 0 0 1 3-3Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/><path d=\"M14 8h6v8l-3-1.5L14 16V8Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/>'),
(17, 'Evaluacion', '<circle cx=\"12\" cy=\"12\" r=\"9\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M8.5 12.2l2 2 4-4\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M12 6l1.2 2.4 2.6.4-1.9 1.9.5 2.7L12 12.4 9.6 13.4l.5-2.7L8.2 8.8l2.6-.4L12 6Z\" stroke=\"currentColor\" stroke-width=\"1.3\"/>'),
(18, 'Buscar', '<circle cx=\"11\" cy=\"11\" r=\"6\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M16 16l5 5\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(19, 'Notificaciones', '<path d=\"M6 10a6 6 0 1 1 12 0v5l2 2H4l2-2v-5Z\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linejoin=\"round\"/><path d=\"M10 20a2 2 0 0 0 4 0\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(20, 'Horario', '<circle cx=\"12\" cy=\"12\" r=\"9\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M12 7v6l4 2\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>'),
(21, 'Aula', '<rect x=\"3\" y=\"4\" width=\"18\" height=\"12\" rx=\"2\" stroke=\"currentColor\" stroke-width=\"1.8\"/><path d=\"M3 18h18\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M8 9h6M8 12h3\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/>'),
(22, 'Descargas', '<path d=\"M12 4v9\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"/><path d=\"M8.5 9.5L12 13l3.5-3.5\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><rect x=\"4\" y=\"16\" width=\"16\" height=\"4\" rx=\"1.5\" stroke=\"currentColor\" stroke-width=\"1.8\"/>');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instrumentos`
--

CREATE TABLE `instrumentos` (
  `id_instrumento` int(11) NOT NULL,
  `abreviatura` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `tipo_calificacion` enum('APROBACION','NUMERICA') NOT NULL DEFAULT 'APROBACION',
  `min_calificacion` decimal(5,2) DEFAULT NULL,
  `max_calificacion` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instrumentos`
--

INSERT INTO `instrumentos` (`id_instrumento`, `abreviatura`, `nombre_completo`, `tipo_calificacion`, `min_calificacion`, `max_calificacion`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'SNII', 'Sistema Nacional de Investigadores e Investigadoras', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-30 14:58:08'),
(2, 'PRODEP', 'Programa para el Desarrollo Profesional Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31'),
(3, 'ESDEPED', 'Estímulos al Desempeño del Personal Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instrumento_tipo_evidencia`
--

CREATE TABLE `instrumento_tipo_evidencia` (
  `id_instrumento` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instrumento_tipo_evidencia`
--

INSERT INTO `instrumento_tipo_evidencia` (`id_instrumento`, `id_tipo_evidencia`, `asignado_en`) VALUES
(1, 1, '2025-11-08 07:57:11'),
(1, 2, '2025-10-28 05:24:13'),
(1, 3, '2025-10-29 04:56:19'),
(1, 6, '2025-10-28 05:42:49'),
(1, 7, '2025-10-28 05:24:13'),
(1, 8, '2025-10-28 05:24:13'),
(1, 9, '2025-10-28 05:24:13'),
(2, 1, '2025-11-08 07:57:11'),
(2, 2, '2025-10-28 05:24:13'),
(2, 3, '2025-10-29 04:56:19'),
(2, 4, '2025-10-28 05:24:13'),
(2, 5, '2025-10-28 05:24:13'),
(2, 6, '2025-10-28 05:42:49'),
(2, 7, '2025-10-28 05:24:13'),
(2, 8, '2025-10-28 05:24:13'),
(2, 9, '2025-10-28 05:24:13'),
(2, 10, '2025-10-28 05:24:13'),
(3, 1, '2025-11-08 07:57:11'),
(3, 3, '2025-10-29 04:56:19'),
(3, 4, '2025-10-28 05:24:13'),
(3, 5, '2025-10-28 05:24:13'),
(3, 6, '2025-10-28 05:42:49'),
(3, 7, '2025-10-28 05:24:13'),
(3, 8, '2025-10-28 05:24:13'),
(3, 10, '2025-10-28 05:24:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_pagina`
--

CREATE TABLE `menu_pagina` (
  `id_mp` int(11) NOT NULL,
  `nombre_pagina` varchar(100) NOT NULL,
  `archivo` varchar(150) NOT NULL,
  `id_icono` int(11) NOT NULL,
  `ocultar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_pagina`
--

INSERT INTO `menu_pagina` (`id_mp`, `nombre_pagina`, `archivo`, `id_icono`, `ocultar`) VALUES
(1, 'Resumen', 'index.php', 1, 0),
(2, 'Usuarios', 'gestion-usuarios.php', 2, 0),
(3, 'Tipos de Evidencias', 'tipos-evidencia.php', 14, 0),
(4, 'Evidencias', 'gestion-evidencias.php', 5, 0),
(5, 'Instrumentos', 'instrumentos.php', 15, 0),
(6, 'Evaluación', 'evaluacion.php', 9, 0),
(7, 'Configuración', 'gestion-menu.php', 7, 0),
(8, 'Calificaciones', 'calificaciones-recibidas.php', 19, 0),
(9, 'Docente Ventana Muestra', 'teacher_dashboard.php', 2, 1),
(18, 'Perfil', 'perfil.php', 13, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_rol`
--

CREATE TABLE `menu_rol` (
  `id_rol` int(11) NOT NULL,
  `id_pagina` int(11) NOT NULL,
  `ocultar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_rol`
--

INSERT INTO `menu_rol` (`id_rol`, `id_pagina`, `ocultar`) VALUES
(1, 1, 0),
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(1, 5, 0),
(1, 6, 0),
(1, 7, 0),
(1, 8, 0),
(1, 9, 0),
(1, 18, 0),
(2, 1, 0),
(2, 2, 0),
(2, 3, 0),
(2, 4, 0),
(2, 5, 0),
(2, 6, 0),
(2, 18, 0),
(3, 1, 0),
(3, 6, 0),
(3, 18, 0),
(4, 1, 0),
(4, 4, 0),
(4, 8, 0),
(4, 9, 0),
(4, 18, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(2, 'Administrador'),
(4, 'Docente'),
(3, 'Evaluador'),
(1, 'Super Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_atributo`
--

CREATE TABLE `tipos_atributo` (
  `id_tipo_atributo` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `grupo_storage` enum('texto_corto','texto_largo','entero','decimal','fecha','booleano','archivo','json') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `validador_regex` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_atributo`
--

INSERT INTO `tipos_atributo` (`id_tipo_atributo`, `nombre_tipo`, `slug`, `grupo_storage`, `descripcion`, `validador_regex`) VALUES
(1, 'Texto corto', 'texto_corto', 'texto_corto', 'Cadenas hasta ~500 caracteres', NULL),
(2, 'Texto largo', 'texto_largo', 'texto_largo', 'Cadenas largas (descripciones, autores en bloque)', NULL),
(3, 'Entero', 'entero', 'entero', 'Valores enteros (año, páginas, horas)', NULL),
(4, 'Decimal', 'decimal', 'decimal', 'Valores decimales (calificaciones, costos)', NULL),
(5, 'Fecha', 'fecha', 'fecha', 'Fechas YYYY-MM-DD', NULL),
(6, 'Booleano', 'booleano', 'booleano', 'Sí/No', NULL),
(7, 'Archivo', 'archivo', 'archivo', 'Ruta a archivo adicional', NULL),
(8, 'JSON / Lista', 'json', 'json', 'Estructuras complejas, multiselect o pares clave-valor', NULL),
(9, 'DOI', 'doi', 'texto_corto', 'Identificador DOI', '^10\\.\\d{4,9}/[-._;()/:A-Z0-9]+$'),
(10, 'ISBN', 'isbn', 'texto_corto', 'ISBN-10 o ISBN-13', '^(97(8|9))?\\d{9}(\\d|X)$'),
(11, 'ISSN', 'issn', 'texto_corto', 'ISSN con guion', '^\\d{4}-\\d{3}[\\dxX]$'),
(12, 'URL', 'url', 'texto_corto', 'Direcciones web', '^(https?:\\/\\/).+$'),
(13, 'Email', 'email', 'texto_corto', 'Correo electrónico', '^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_de_evidencia`
--

CREATE TABLE `tipos_de_evidencia` (
  `id_tipo_evidencia` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `SNI` tinyint(4) NOT NULL DEFAULT 0,
  `PRODEP` tinyint(4) NOT NULL DEFAULT 0,
  `ESDEPED` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_de_evidencia`
--

INSERT INTO `tipos_de_evidencia` (`id_tipo_evidencia`, `nombre_tipo`, `descripcion`, `SNI`, `PRODEP`, `ESDEPED`) VALUES
(1, 'Libro', 'Libro publicado por alguna editorial', 1, 1, 0),
(2, 'Conferencia / Ponencia', 'Exposición oral de un tema en un congreso o evento académico.', 1, 1, 0),
(3, 'Clase de diplomado', 'Sesión de enseñanza impartida en un programa de diplomado.', 1, 0, 1),
(4, 'Investigación', 'Trabajo académico o científico con el objetivo de generar conocimiento.', 0, 1, 1),
(5, 'Horas frente a grupo', 'Tiempo efectivo dedicado a la enseñanza en un aula.', 0, 1, 1),
(6, 'Constancia', 'Documento que certifica la participación o cumplimiento de una actividad.', 0, 1, 0),
(7, 'Diplomado', 'Curso especializado con reconocimiento académico o profesional.', 1, 1, 1),
(8, 'Conferencia', 'Evento donde se presenta información especializada sobre un tema.', 1, 1, 1),
(9, 'Articulo', 'Texto publicado en revistas científicas o académicas.', 1, 1, 0),
(10, 'Docencia', 'Actividad de enseñanza realizada en una institución educativa.', 0, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidop` varchar(100) NOT NULL,
  `apellidom` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellidop`, `apellidom`, `correo`, `password`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Victor Antonio', 'Limón', 'Vázquez', 'admin@admin.com', '$2y$10$N0j7cGKk6IKri92KDHRjt.WWpBpzQ8Og4/0Gd4x7LrFpzSOnrMJMa', 1, 1, '2025-03-21 00:44:49', '2025-03-26 18:15:23'),
(2, 'Ana Maria', 'López', 'Martínez', 'ana@gmail.com', '$2y$10$cxvxQ4/lXVb6gNQecxtvAezBht92/hEukdVFrjxSamynjyFQwtLVG', 2, 1, '2025-03-21 00:44:49', '2025-10-30 14:56:22'),
(3, 'Juan', 'Pérez', 'Gómez', 'juan@gmail.com', '$2y$10$xaaFeZcxo3c9qyuTy4LI7ep.SyRNcjhikd5oJDb7aJ9NjMEzPvYYm', 3, 1, '2025-03-21 01:09:33', '2026-02-18 04:04:30'),
(4, 'Angel', 'López', 'Martínez', 'angel@gmail.com', '$2y$10$nvjP2fzA1bDDbJAsnc3RhOKauz/Cu.heftjiWh9VQeaVPFFDT62Uy', 4, 1, '2025-03-21 01:09:33', '2025-03-26 18:15:23'),
(5, 'Juanita', 'Nuevo', 'Usuario', 'nuevo@gmail.com', '$2y$10$o0SjqJSX2XJTc6xI9WwuCOi2vS2jdqiWu8Yw0wwmqadqstY0qiC2u', 4, 1, '2025-03-22 01:31:24', '2025-03-26 18:15:23'),
(10, 'Lupe', 'Velazquez', 'Perez', 'lupe@gmail.com', '$2y$10$/G7bclC/O3RzJVQvQcnXSumHii87TGL/rc4OlBDRtqn.rg9CMBZqi', 3, 1, '2025-03-26 18:08:53', '2025-11-08 04:40:00'),
(11, 'Pedro', 'Bello', 'López', 'pb5pbello@gmail.com', '$2y$10$nNCSLOee3mBKBqVG6x3FQOWhE7JeepsAhVGxbVDlkvEc.lG0rQxe2', 4, 1, '2025-03-29 18:42:15', '2025-03-29 18:42:15'),
(12, 'Nuevo', 'Super', 'Usuario', 'superusuario@gmail.com', '$2y$10$1q/3U8nF1or/v/A/ckCGp.yWVpXxje8YIuJW01jftZFQdy6/GduIS', 1, 1, '2025-03-31 19:51:41', '2025-10-18 20:14:26'),
(14, 'Juan', 'Perez', 'Martinez', 'juanperez@gmail.com', '$2y$10$.wteluAkZs1OAVN26TZ0Se3jLN5RWw4Y8/h2x3bal69AgkX509EfC', 4, 1, '2025-06-30 00:17:55', '2026-02-18 04:06:37'),
(15, 'Lupe', 'Galindo', 'Barrientos', 'lupegalindo@gmail.com', '$2y$10$yBzJhpLAYSCis2/WzcKUruelH25gwrQhOYD9FjQS8DNAOmvtoVGKm', 3, 1, '2025-10-18 20:08:21', '2025-10-18 20:08:21'),
(16, 'Fulanito', 'Perezito', 'Lopez', 'fulano@gmail.com', '$2y$10$KNeq6ps68bkN8rV0pvj4kesAd1kjJIUg0YRaJL41IcKEXt/haeAbW', 4, 1, '2025-10-24 23:10:44', '2025-10-24 23:11:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  ADD PRIMARY KEY (`id_ate`),
  ADD UNIQUE KEY `uq_ate_tipo_slug` (`id_tipo_evidencia`,`slug`),
  ADD KEY `idx_ate_tipo_evidencia` (`id_tipo_evidencia`),
  ADD KEY `idx_ate_tipo_atributo` (`id_tipo_atributo`);

--
-- Indices de la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uniq_evi_inst` (`id_evidencia`,`id_instrumento`),
  ADD KEY `idx_inst` (`id_instrumento`),
  ADD KEY `idx_evi` (`id_evidencia`),
  ADD KEY `fk_ce_eval` (`id_usuario_eval`);

--
-- Indices de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `id_tipo_evidencia` (`id_tipo_evidencia`);

--
-- Indices de la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  ADD PRIMARY KEY (`id_eva`),
  ADD UNIQUE KEY `uq_eva_unico` (`id_evidencia`,`id_ate`,`indice`),
  ADD KEY `idx_eva_evidencia` (`id_evidencia`),
  ADD KEY `idx_eva_ate` (`id_ate`);

--
-- Indices de la tabla `iconos`
--
ALTER TABLE `iconos`
  ADD PRIMARY KEY (`id_icono`);

--
-- Indices de la tabla `instrumentos`
--
ALTER TABLE `instrumentos`
  ADD PRIMARY KEY (`id_instrumento`),
  ADD UNIQUE KEY `abreviatura` (`abreviatura`),
  ADD UNIQUE KEY `nombre_completo` (`nombre_completo`);

--
-- Indices de la tabla `instrumento_tipo_evidencia`
--
ALTER TABLE `instrumento_tipo_evidencia`
  ADD PRIMARY KEY (`id_instrumento`,`id_tipo_evidencia`),
  ADD KEY `idx_ite_tipo` (`id_tipo_evidencia`);

--
-- Indices de la tabla `menu_pagina`
--
ALTER TABLE `menu_pagina`
  ADD PRIMARY KEY (`id_mp`),
  ADD KEY `fk_pagina_icono` (`id_icono`);

--
-- Indices de la tabla `menu_rol`
--
ALTER TABLE `menu_rol`
  ADD PRIMARY KEY (`id_rol`,`id_pagina`),
  ADD KEY `fk_rol_pagina` (`id_pagina`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_atributo`
--
ALTER TABLE `tipos_atributo`
  ADD PRIMARY KEY (`id_tipo_atributo`),
  ADD UNIQUE KEY `uq_tipos_atributo_slug` (`slug`),
  ADD UNIQUE KEY `uq_tipos_atributo_nombre` (`nombre_tipo`);

--
-- Indices de la tabla `tipos_de_evidencia`
--
ALTER TABLE `tipos_de_evidencia`
  ADD PRIMARY KEY (`id_tipo_evidencia`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  MODIFY `id_ate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  MODIFY `id_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  MODIFY `id_eva` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `iconos`
--
ALTER TABLE `iconos`
  MODIFY `id_icono` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `instrumentos`
--
ALTER TABLE `instrumentos`
  MODIFY `id_instrumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `menu_pagina`
--
ALTER TABLE `menu_pagina`
  MODIFY `id_mp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipos_atributo`
--
ALTER TABLE `tipos_atributo`
  MODIFY `id_tipo_atributo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `tipos_de_evidencia`
--
ALTER TABLE `tipos_de_evidencia`
  MODIFY `id_tipo_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `atributos_tipo_evidencia`
--
ALTER TABLE `atributos_tipo_evidencia`
  ADD CONSTRAINT `fk_ate_tipo_atributo` FOREIGN KEY (`id_tipo_atributo`) REFERENCES `tipos_atributo` (`id_tipo_atributo`),
  ADD CONSTRAINT `fk_ate_tipo_evidencia` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificacion_evidencia`
--
ALTER TABLE `calificacion_evidencia`
  ADD CONSTRAINT `fk_ce_eval` FOREIGN KEY (`id_usuario_eval`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_evi` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_inst` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_ibfk_1` FOREIGN KEY (`id_docente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidencias_ibfk_2` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evidencia_valores_atributo`
--
ALTER TABLE `evidencia_valores_atributo`
  ADD CONSTRAINT `fk_eva_ate` FOREIGN KEY (`id_ate`) REFERENCES `atributos_tipo_evidencia` (`id_ate`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eva_evidencia` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `instrumento_tipo_evidencia`
--
ALTER TABLE `instrumento_tipo_evidencia`
  ADD CONSTRAINT `fk_ite_instrumento` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ite_tipo` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `menu_pagina`
--
ALTER TABLE `menu_pagina`
  ADD CONSTRAINT `fk_pagina_icono` FOREIGN KEY (`id_icono`) REFERENCES `iconos` (`id_icono`);

--
-- Filtros para la tabla `menu_rol`
--
ALTER TABLE `menu_rol`
  ADD CONSTRAINT `fk_rol_pagina` FOREIGN KEY (`id_pagina`) REFERENCES `menu_pagina` (`id_mp`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
