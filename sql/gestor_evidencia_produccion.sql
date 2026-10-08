-- Instalacion limpia ODEA. Generado el 2026-10-08.
-- Solo para una base NUEVA en el servidor de despliegue; no importa datos de ejemplo.
-- Conserva estructura, restricciones, instrumentos y catalogos del sistema.
-- Cuenta inicial: superadmin@odea.local (rol Super Usuario).
-- La contrasena se entrega por separado; aqui solo se almacena su hash bcrypt.
-- Configurar GESTOR_DB_NAME=gestor_evidencia_produccion en el servidor PHP.

CREATE DATABASE gestor_evidencia_produccion CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE gestor_evidencia_produccion;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

/*!40101 SET NAMES utf8mb4 */;

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

CREATE TABLE `evidencias` (
  `id_evidencia` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_docente` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `ocultar` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `iconos` (
  `id_icono` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `imagen` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

INSERT INTO `instrumentos` (`id_instrumento`, `abreviatura`, `nombre_completo`, `tipo_calificacion`, `min_calificacion`, `max_calificacion`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'SNII', 'Sistema Nacional de Investigadores e Investigadoras', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-30 14:58:08'),
(2, 'PRODEP', 'Programa para el Desarrollo Profesional Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31'),
(3, 'ESDEPED', 'Estímulos al Desempeño del Personal Docente', 'APROBACION', NULL, NULL, 1, '2025-10-28 05:11:31', '2025-10-28 05:11:31');

CREATE TABLE `instrumento_tipo_evidencia` (
  `id_instrumento` int(11) NOT NULL,
  `id_tipo_evidencia` int(11) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `menu_pagina` (
  `id_mp` int(11) NOT NULL,
  `nombre_pagina` varchar(100) NOT NULL,
  `archivo` varchar(150) NOT NULL,
  `id_icono` int(11) NOT NULL,
  `ocultar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `menu_rol` (
  `id_rol` int(11) NOT NULL,
  `id_pagina` int(11) NOT NULL,
  `ocultar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(2, 'Administrador'),
(4, 'Docente'),
(3, 'Evaluador'),
(1, 'Super Usuario');

CREATE TABLE `tipos_atributo` (
  `id_tipo_atributo` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `grupo_storage` enum('texto_corto','texto_largo','entero','decimal','fecha','booleano','archivo','json') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `validador_regex` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `tipos_de_evidencia` (
  `id_tipo_evidencia` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellidop`, `apellidom`, `correo`, `password`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'Super', 'Usuario', 'superadmin@odea.local', '$2y$10$CA6D0orwg06HTXmVqMK/TuoXr6miGxSo1fgtvacYpRj47DWJHbuSW', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

ALTER TABLE `atributos_tipo_evidencia`
  ADD PRIMARY KEY (`id_ate`),
  ADD UNIQUE KEY `uq_ate_tipo_slug` (`id_tipo_evidencia`,`slug`),
  ADD KEY `idx_ate_tipo_evidencia` (`id_tipo_evidencia`),
  ADD KEY `idx_ate_tipo_atributo` (`id_tipo_atributo`);

ALTER TABLE `calificacion_evidencia`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uniq_evi_inst` (`id_evidencia`,`id_instrumento`),
  ADD KEY `idx_inst` (`id_instrumento`),
  ADD KEY `idx_evi` (`id_evidencia`),
  ADD KEY `fk_ce_eval` (`id_usuario_eval`);

ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `id_tipo_evidencia` (`id_tipo_evidencia`);

ALTER TABLE `evidencia_valores_atributo`
  ADD PRIMARY KEY (`id_eva`),
  ADD UNIQUE KEY `uq_eva_unico` (`id_evidencia`,`id_ate`,`indice`),
  ADD KEY `idx_eva_evidencia` (`id_evidencia`),
  ADD KEY `idx_eva_ate` (`id_ate`);

ALTER TABLE `iconos`
  ADD PRIMARY KEY (`id_icono`);

ALTER TABLE `instrumentos`
  ADD PRIMARY KEY (`id_instrumento`),
  ADD UNIQUE KEY `abreviatura` (`abreviatura`),
  ADD UNIQUE KEY `nombre_completo` (`nombre_completo`);

ALTER TABLE `instrumento_tipo_evidencia`
  ADD PRIMARY KEY (`id_instrumento`,`id_tipo_evidencia`),
  ADD KEY `idx_ite_tipo` (`id_tipo_evidencia`);

ALTER TABLE `menu_pagina`
  ADD PRIMARY KEY (`id_mp`),
  ADD KEY `fk_pagina_icono` (`id_icono`);

ALTER TABLE `menu_rol`
  ADD PRIMARY KEY (`id_rol`,`id_pagina`),
  ADD KEY `fk_rol_pagina` (`id_pagina`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `tipos_atributo`
  ADD PRIMARY KEY (`id_tipo_atributo`),
  ADD UNIQUE KEY `uq_tipos_atributo_slug` (`slug`),
  ADD UNIQUE KEY `uq_tipos_atributo_nombre` (`nombre_tipo`);

ALTER TABLE `tipos_de_evidencia`
  ADD PRIMARY KEY (`id_tipo_evidencia`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

ALTER TABLE `atributos_tipo_evidencia`
  MODIFY `id_ate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `calificacion_evidencia`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `evidencias`
  MODIFY `id_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `evidencia_valores_atributo`
  MODIFY `id_eva` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `iconos`
  MODIFY `id_icono` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

ALTER TABLE `instrumentos`
  MODIFY `id_instrumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `menu_pagina`
  MODIFY `id_mp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `tipos_atributo`
  MODIFY `id_tipo_atributo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `tipos_de_evidencia`
  MODIFY `id_tipo_evidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `atributos_tipo_evidencia`
  ADD CONSTRAINT `fk_ate_tipo_atributo` FOREIGN KEY (`id_tipo_atributo`) REFERENCES `tipos_atributo` (`id_tipo_atributo`),
  ADD CONSTRAINT `fk_ate_tipo_evidencia` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

ALTER TABLE `calificacion_evidencia`
  ADD CONSTRAINT `fk_ce_eval` FOREIGN KEY (`id_usuario_eval`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_evi` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_inst` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_ibfk_1` FOREIGN KEY (`id_docente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidencias_ibfk_2` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE;

ALTER TABLE `evidencia_valores_atributo`
  ADD CONSTRAINT `fk_eva_ate` FOREIGN KEY (`id_ate`) REFERENCES `atributos_tipo_evidencia` (`id_ate`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eva_evidencia` FOREIGN KEY (`id_evidencia`) REFERENCES `evidencias` (`id_evidencia`) ON DELETE CASCADE;

ALTER TABLE `instrumento_tipo_evidencia`
  ADD CONSTRAINT `fk_ite_instrumento` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos` (`id_instrumento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ite_tipo` FOREIGN KEY (`id_tipo_evidencia`) REFERENCES `tipos_de_evidencia` (`id_tipo_evidencia`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `menu_pagina`
  ADD CONSTRAINT `fk_pagina_icono` FOREIGN KEY (`id_icono`) REFERENCES `iconos` (`id_icono`);

ALTER TABLE `menu_rol`
  ADD CONSTRAINT `fk_rol_pagina` FOREIGN KEY (`id_pagina`) REFERENCES `menu_pagina` (`id_mp`);

CREATE TABLE evaluador_instrumento (
  id_evaluador int(11) NOT NULL,
  id_instrumento int(11) NOT NULL,
  asignado_en timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_evaluador, id_instrumento),
  KEY idx_ei_instrumento (id_instrumento),
  CONSTRAINT fk_ei_evaluador FOREIGN KEY (id_evaluador) REFERENCES usuarios (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ei_instrumento FOREIGN KEY (id_instrumento) REFERENCES instrumentos (id_instrumento) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;

/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
