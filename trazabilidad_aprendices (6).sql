-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-10-2026 a las 05:24:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `trazabilidad_aprendices`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actualizaciones_caracterizacion`
--

CREATE TABLE `actualizaciones_caracterizacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `estado` enum('pendiente','actualizado') NOT NULL DEFAULT 'pendiente',
  `fecha_actualizacion` datetime DEFAULT NULL,
  `origen` varchar(100) NOT NULL DEFAULT 'Formulario de actualización',
  `campos_actualizados` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_acompanamiento`
--

CREATE TABLE `alertas_acompanamiento` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED DEFAULT NULL,
  `tipo_alerta` enum('academica','salud_fisica','salud_mental','nutricional','salud_sexual_reproductiva','actividad_fisica','apoyo_pedagogico','otra') NOT NULL,
  `nivel` enum('bajo','medio','alto') NOT NULL DEFAULT 'medio',
  `titulo` varchar(200) NOT NULL,
  `recomendacion_institucional` text NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `aplica_actividad_fisica` tinyint(1) NOT NULL DEFAULT 0,
  `aplica_aula` tinyint(1) NOT NULL DEFAULT 0,
  `aplica_laboratorio` tinyint(1) NOT NULL DEFAULT 0,
  `aplica_practica` tinyint(1) NOT NULL DEFAULT 0,
  `aplica_salida` tinyint(1) NOT NULL DEFAULT 0,
  `visible_instructor` tinyint(1) NOT NULL DEFAULT 0,
  `visible_lider_ficha` tinyint(1) NOT NULL DEFAULT 0,
  `visible_transversal` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('programada','vigente','vencida','cerrada') NOT NULL DEFAULT 'vigente',
  `observacion` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aprendices`
--

CREATE TABLE `aprendices` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_persona` int(10) UNSIGNED NOT NULL,
  `id_eps` int(10) UNSIGNED DEFAULT NULL,
  `contacto_emergencia_nombre` varchar(150) DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(30) DEFAULT NULL,
  `contacto_emergencia_parentesco` varchar(50) DEFAULT NULL,
  `estado` enum('EN FORMACION','CANCELADO','RETIRO VOLUNTARIO','APLAZADO') NOT NULL DEFAULT 'EN FORMACION',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aprendiz_poblacion`
--

CREATE TABLE `aprendiz_poblacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_tipo_poblacion` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `observacion` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_ficha`
--

CREATE TABLE `asignaciones_ficha` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `tipo_asignacion` enum('LIDER_FICHA','INSTRUCTOR_TECNICO','INSTRUCTOR_TRANSVERSAL','BIENESTAR','SALUD_FISICA','SALUD_MENTAL','NUTRICION','COORDINADOR') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','finalizada','cancelada') NOT NULL DEFAULT 'activa',
  `observaciones` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `modulo` varchar(100) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `registro_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `competencias`
--

CREATE TABLE `competencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_programa` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(250) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compromisos`
--

CREATE TABLE `compromisos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `fecha_compromiso` date NOT NULL,
  `descripcion` text NOT NULL,
  `responsable` varchar(150) DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `estado` enum('pendiente','cumplido','incumplido','cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_cumplimiento` date DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuestionarios_bienestar`
--

CREATE TABLE `cuestionarios_bienestar` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `respuestas` longtext NOT NULL,
  `estado` enum('borrador','completado') NOT NULL DEFAULT 'borrador',
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_completado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enlaces_actualizacion_ficha`
--

CREATE TABLE `enlaces_actualizacion_ficha` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eps`
--

CREATE TABLE `eps` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etapas`
--

CREATE TABLE `etapas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etapas_aprendiz`
--

CREATE TABLE `etapas_aprendiz` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_matricula` int(10) UNSIGNED NOT NULL,
  `id_etapa` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','finalizada') NOT NULL DEFAULT 'activa',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fichas`
--

CREATE TABLE `fichas` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_ficha` varchar(30) NOT NULL,
  `id_programa` int(10) UNSIGNED NOT NULL,
  `version_programa` varchar(20) DEFAULT NULL,
  `modalidad` varchar(50) DEFAULT NULL,
  `regional` varchar(150) DEFAULT NULL,
  `centro` varchar(200) DEFAULT NULL,
  `id_jornada` int(10) UNSIGNED DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','finalizada','cancelada') NOT NULL DEFAULT 'activa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jornadas`
--

CREATE TABLE `jornadas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_area_responsables`
--

CREATE TABLE `materiales_area_responsables` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_proceso_area` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `id_usuario_designa` int(10) UNSIGNED DEFAULT NULL,
  `fecha_designacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `estado` enum('activo','reemplazado','retirado') NOT NULL DEFAULT 'activo',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_catalogo`
--

CREATE TABLE `materiales_catalogo` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo_unspsc` varchar(30) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_inicio_vigencia` date DEFAULT NULL,
  `fecha_fin_vigencia` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_distribuciones`
--

CREATE TABLE `materiales_distribuciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_distribucion_detalle`
--

CREATE TABLE `materiales_distribucion_detalle` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_distribucion` bigint(20) UNSIGNED NOT NULL,
  `id_detalle_solicitud` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `cantidad_distribuida` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_importaciones`
--

CREATE TABLE `materiales_importaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `anio_referencia` year(4) DEFAULT NULL,
  `total_filas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `hojas_procesadas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registros_nuevos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registros_modificados` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registros_sin_cambios` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registros_inactivados` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registros_con_error` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `estado` enum('lectura','validada','aplicada','cancelada','error') NOT NULL DEFAULT 'lectura',
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `applied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_importacion_detalle`
--

CREATE TABLE `materiales_importacion_detalle` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_importacion` int(10) UNSIGNED NOT NULL,
  `fila_excel` int(10) UNSIGNED NOT NULL,
  `codigo_unspsc` varchar(30) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(100) DEFAULT NULL,
  `accion` enum('nuevo','modificado','sin_cambios','inactivado','error') NOT NULL,
  `mensaje` text DEFAULT NULL,
  `id_material` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_procesos`
--

CREATE TABLE `materiales_procesos` (
  `id` int(10) UNSIGNED NOT NULL,
  `anio` year(4) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_cierre` date DEFAULT NULL,
  `estado` enum('borrador','abierto','en_solicitud','en_aval','en_compra','en_recepcion','en_distribucion','cerrado','cancelado') NOT NULL DEFAULT 'borrador',
  `id_coordinador` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_proceso_areas`
--

CREATE TABLE `materiales_proceso_areas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_proceso` int(10) UNSIGNED NOT NULL,
  `nombre_area` varchar(150) NOT NULL,
  `estado` enum('activa','inactiva','cerrada') NOT NULL DEFAULT 'activa',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_recepciones`
--

CREATE TABLE `materiales_recepciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `fecha_recepcion` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_recepcion_detalle`
--

CREATE TABLE `materiales_recepcion_detalle` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_recepcion` bigint(20) UNSIGNED NOT NULL,
  `id_detalle_solicitud` int(10) UNSIGNED NOT NULL,
  `cantidad_recibida` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_solicitudes`
--

CREATE TABLE `materiales_solicitudes` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_proceso_area` int(10) UNSIGNED NOT NULL,
  `id_profesional_solicitante` int(10) UNSIGNED NOT NULL,
  `numero_solicitud` varchar(50) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `justificacion` text DEFAULT NULL,
  `estado` enum('borrador','pendiente_aval','devuelta','avalada','rechazada','cancelada') NOT NULL DEFAULT 'borrador',
  `fecha_envio` datetime DEFAULT NULL,
  `id_usuario_revision` int(10) UNSIGNED DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `observacion_revision` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_solicitud_destinos`
--

CREATE TABLE `materiales_solicitud_destinos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_detalle_solicitud` int(10) UNSIGNED NOT NULL,
  `tipo_destino` enum('ficha','general_area') NOT NULL DEFAULT 'general_area',
  `id_ficha` int(10) UNSIGNED DEFAULT NULL,
  `cantidad_destinada` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_solicitud_detalle`
--

CREATE TABLE `materiales_solicitud_detalle` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `id_material` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `cantidad_solicitada` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `unidad_medida` varchar(50) NOT NULL DEFAULT 'Unidad',
  `descripcion_tecnica` varchar(2000) NOT NULL DEFAULT '',
  `es_consumo` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `es_devolutivo` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `es_software` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `es_epp` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales_solicitud_historial`
--

CREATE TABLE `materiales_solicitud_historial` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','finalizada','cancelada','retirada','aplazada') NOT NULL DEFAULT 'activa',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `documento` varchar(30) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesionales`
--

CREATE TABLE `profesionales` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_persona` int(10) UNSIGNED NOT NULL,
  `tipo_profesional` enum('Instructor','Psicologo','Enfermero','Nutricionista','Trabajador Social','Bienestar','Coordinador','Promotor de recreacion y deportes','Instructor artistico','Otro') DEFAULT NULL,
  `registro_profesional` varchar(100) DEFAULT NULL,
  `especialidad` varchar(150) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `nivel` varchar(100) DEFAULT NULL,
  `duracion_meses` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rap`
--

CREATE TABLE `rap` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_competencia` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `horas` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recomendaciones_nutricionales`
--

CREATE TABLE `recomendaciones_nutricionales` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_valoracion` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `recomendacion` text NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_seguimiento` date DEFAULT NULL,
  `estado` enum('pendiente','en_seguimiento','cumplida','cerrada') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remisiones`
--

CREATE TABLE `remisiones` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `fecha_remision` date NOT NULL,
  `area_origen` varchar(100) DEFAULT NULL,
  `motivo_general` varchar(255) DEFAULT NULL,
  `tipo_servicio` varchar(150) DEFAULT NULL,
  `institucion_destino` varchar(200) DEFAULT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `fecha_cita` date DEFAULT NULL,
  `estado` enum('pendiente','gestionada','atendida','no_asistio','en_seguimiento','cerrada') NOT NULL DEFAULT 'pendiente',
  `resultado` text DEFAULT NULL,
  `fecha_seguimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_cierre` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_aprendiz`
--

CREATE TABLE `resultados_aprendiz` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED DEFAULT NULL,
  `id_rap` int(10) UNSIGNED NOT NULL,
  `id_instructor` int(10) UNSIGNED DEFAULT NULL,
  `fecha_evaluacion` date DEFAULT NULL,
  `fecha_hora_evaluacion` datetime DEFAULT NULL,
  `juicio` enum('aprobado','no_aprobado','pendiente') NOT NULL DEFAULT 'pendiente',
  `juicio_sena` varchar(50) DEFAULT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `id_permiso` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos_fisicos`
--

CREATE TABLE `seguimientos_fisicos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_ficha` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fecha_toma` datetime NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `talla` decimal(4,2) NOT NULL,
  `imc` decimal(5,2) NOT NULL,
  `presion_sistolica` smallint(5) UNSIGNED DEFAULT NULL,
  `presion_diastolica` smallint(5) UNSIGNED DEFAULT NULL,
  `frecuencia_cardiaca` smallint(5) UNSIGNED DEFAULT NULL,
  `frecuencia_respiratoria` smallint(5) UNSIGNED DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `saturacion_oxigeno` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `proxima_fecha` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos_salud_mental`
--

CREATE TABLE `seguimientos_salud_mental` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `fecha_atencion` datetime NOT NULL,
  `tipo_atencion` varchar(100) NOT NULL,
  `motivo_atencion` varchar(255) DEFAULT NULL,
  `estado_general` varchar(100) DEFAULT NULL,
  `factores_riesgo` text DEFAULT NULL,
  `factores_protectores` text DEFAULT NULL,
  `intervencion_realizada` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_proximo_seguimiento` date DEFAULT NULL,
  `remision` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones_restringidas` text DEFAULT NULL,
  `estado` enum('abierto','seguimiento','cerrado') NOT NULL DEFAULT 'abierto',
  `fecha_cierre` date DEFAULT NULL,
  `observacion_cierre` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos_salud_sexual`
--

CREATE TABLE `seguimientos_salud_sexual` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `fecha_atencion` datetime NOT NULL,
  `tipo_atencion` varchar(100) DEFAULT NULL,
  `tema_orientacion` varchar(200) DEFAULT NULL,
  `orientacion_realizada` text DEFAULT NULL,
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_proximo_seguimiento` date DEFAULT NULL,
  `remision` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones_restringidas` text DEFAULT NULL,
  `estado` enum('abierto','seguimiento','cerrado') NOT NULL DEFAULT 'abierto',
  `fecha_cierre` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `signos_vitales`
--

CREATE TABLE `signos_vitales` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_valoracion` int(10) UNSIGNED NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` decimal(4,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `categoria_imc` varchar(50) DEFAULT NULL,
  `presion_sistolica` smallint(5) UNSIGNED DEFAULT NULL,
  `presion_diastolica` smallint(5) UNSIGNED DEFAULT NULL,
  `frecuencia_cardiaca` smallint(5) UNSIGNED DEFAULT NULL,
  `frecuencia_respiratoria` smallint(5) UNSIGNED DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `saturacion_oxigeno` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_atencion`
--

CREATE TABLE `solicitudes_atencion` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `tipo_atencion` enum('psicologia','enfermeria','nutricion','bienestar','otra') NOT NULL,
  `motivo_general` varchar(255) DEFAULT NULL,
  `modalidad` enum('presencial','virtual') DEFAULT NULL,
  `fecha_preferida` date DEFAULT NULL,
  `hora_preferida` time DEFAULT NULL,
  `estado` enum('solicitada','pendiente','programada','atendida','no_asistio','cancelada','reprogramada','cerrada') NOT NULL DEFAULT 'solicitada',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_poblacion`
--

CREATE TABLE `tipos_poblacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(250) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_persona` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_roles`
--

CREATE TABLE `usuario_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones_nutricionales`
--

CREATE TABLE `valoraciones_nutricionales` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `fecha_atencion` datetime NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` decimal(4,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `perimetro_abdominal` decimal(5,2) DEFAULT NULL,
  `habitos_alimentarios` text DEFAULT NULL,
  `consumo_agua` varchar(100) DEFAULT NULL,
  `actividad_fisica` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_proximo_seguimiento` date DEFAULT NULL,
  `estado` enum('abierta','seguimiento','cerrada') NOT NULL DEFAULT 'abierta',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones_salud_fisica`
--

CREATE TABLE `valoraciones_salud_fisica` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_aprendiz` int(10) UNSIGNED NOT NULL,
  `id_profesional` int(10) UNSIGNED NOT NULL,
  `fecha_atencion` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `estado_general` varchar(100) DEFAULT NULL,
  `presencia_dolor` tinyint(1) NOT NULL DEFAULT 0,
  `intensidad_dolor` tinyint(3) UNSIGNED DEFAULT NULL,
  `ubicacion_dolor` varchar(150) DEFAULT NULL,
  `actividad_fisica` varchar(100) DEFAULT NULL,
  `horas_sueno` decimal(4,1) DEFAULT NULL,
  `calidad_sueno` varchar(30) DEFAULT NULL,
  `antecedentes_relevantes` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_proximo_seguimiento` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actualizaciones_caracterizacion`
--
ALTER TABLE `actualizaciones_caracterizacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_actualizacion_aprendiz_ficha` (`id_aprendiz`,`id_ficha`),
  ADD KEY `idx_actualizacion_aprendiz` (`id_aprendiz`),
  ADD KEY `idx_actualizacion_ficha` (`id_ficha`),
  ADD KEY `idx_actualizacion_estado` (`estado`);

--
-- Indices de la tabla `alertas_acompanamiento`
--
ALTER TABLE `alertas_acompanamiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alertas_aprendiz` (`id_aprendiz`),
  ADD KEY `idx_alertas_vigencia` (`fecha_inicio`,`fecha_fin`,`estado`),
  ADD KEY `fk_alertas_profesional` (`id_profesional`);

--
-- Indices de la tabla `aprendices`
--
ALTER TABLE `aprendices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_aprendiz_persona` (`id_persona`),
  ADD KEY `fk_aprendices_eps` (`id_eps`);

--
-- Indices de la tabla `aprendiz_poblacion`
--
ALTER TABLE `aprendiz_poblacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aprendiz_poblacion_aprendiz` (`id_aprendiz`),
  ADD KEY `idx_aprendiz_poblacion_tipo` (`id_tipo_poblacion`);

--
-- Indices de la tabla `asignaciones_ficha`
--
ALTER TABLE `asignaciones_ficha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asignaciones_ficha` (`id_ficha`),
  ADD KEY `idx_asignaciones_profesional` (`id_profesional`),
  ADD KEY `idx_asignaciones_fechas` (`fecha_inicio`,`fecha_fin`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_usuario` (`id_usuario`),
  ADD KEY `idx_auditoria_fecha` (`fecha_hora`);

--
-- Indices de la tabla `competencias`
--
ALTER TABLE `competencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_competencia_programa_codigo` (`id_programa`,`codigo`);

--
-- Indices de la tabla `compromisos`
--
ALTER TABLE `compromisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_compromisos_aprendiz` (`id_aprendiz`);

--
-- Indices de la tabla `cuestionarios_bienestar`
--
ALTER TABLE `cuestionarios_bienestar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cuestionario_aprendiz_ficha` (`id_aprendiz`,`id_ficha`),
  ADD KEY `idx_cuestionario_ficha_estado` (`id_ficha`,`estado`);

--
-- Indices de la tabla `enlaces_actualizacion_ficha`
--
ALTER TABLE `enlaces_actualizacion_ficha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_enlace_ficha` (`id_ficha`),
  ADD UNIQUE KEY `uk_enlace_token` (`token`),
  ADD KEY `idx_enlace_estado` (`estado`);

--
-- Indices de la tabla `eps`
--
ALTER TABLE `eps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_eps_nombre` (`nombre`);

--
-- Indices de la tabla `etapas`
--
ALTER TABLE `etapas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_etapas_nombre` (`nombre`);

--
-- Indices de la tabla `etapas_aprendiz`
--
ALTER TABLE `etapas_aprendiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_etapas_aprendiz_matricula` (`id_matricula`),
  ADD KEY `fk_etapas_aprendiz_etapa` (`id_etapa`);

--
-- Indices de la tabla `fichas`
--
ALTER TABLE `fichas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fichas_numero` (`numero_ficha`),
  ADD KEY `fk_fichas_programa` (`id_programa`),
  ADD KEY `fk_fichas_jornada` (`id_jornada`);

--
-- Indices de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_jornadas_nombre` (`nombre`);

--
-- Indices de la tabla `materiales_area_responsables`
--
ALTER TABLE `materiales_area_responsables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_responsable_area` (`id_proceso_area`),
  ADD KEY `idx_responsable_profesional` (`id_profesional`),
  ADD KEY `idx_responsable_usuario` (`id_usuario_designa`),
  ADD KEY `idx_responsable_estado` (`estado`);

--
-- Indices de la tabla `materiales_catalogo`
--
ALTER TABLE `materiales_catalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_material_codigo_vigencia` (`codigo_unspsc`,`fecha_inicio_vigencia`),
  ADD KEY `idx_material_estado` (`estado`),
  ADD KEY `idx_material_codigo` (`codigo_unspsc`);

--
-- Indices de la tabla `materiales_distribuciones`
--
ALTER TABLE `materiales_distribuciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mat_dist_usuario` (`id_usuario`);

--
-- Indices de la tabla `materiales_distribucion_detalle`
--
ALTER TABLE `materiales_distribucion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dist_detalle_ficha` (`id_distribucion`,`id_detalle_solicitud`,`id_ficha`),
  ADD KEY `idx_dist_detalle_solicitud` (`id_detalle_solicitud`),
  ADD KEY `idx_dist_detalle_ficha` (`id_ficha`);

--
-- Indices de la tabla `materiales_importaciones`
--
ALTER TABLE `materiales_importaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_importacion_estado` (`estado`),
  ADD KEY `idx_importacion_anio` (`anio_referencia`),
  ADD KEY `idx_importacion_usuario` (`id_usuario`);

--
-- Indices de la tabla `materiales_importacion_detalle`
--
ALTER TABLE `materiales_importacion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_importacion_detalle_importacion` (`id_importacion`),
  ADD KEY `idx_importacion_detalle_codigo` (`codigo_unspsc`),
  ADD KEY `idx_importacion_detalle_accion` (`accion`),
  ADD KEY `idx_importacion_detalle_material` (`id_material`);

--
-- Indices de la tabla `materiales_procesos`
--
ALTER TABLE `materiales_procesos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proceso_anio` (`anio`),
  ADD KEY `idx_proceso_estado` (`estado`),
  ADD KEY `idx_proceso_coordinador` (`id_coordinador`);

--
-- Indices de la tabla `materiales_proceso_areas`
--
ALTER TABLE `materiales_proceso_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proceso_area_proceso` (`id_proceso`),
  ADD KEY `idx_proceso_area_nombre` (`nombre_area`),
  ADD KEY `idx_proceso_area_estado` (`estado`);

--
-- Indices de la tabla `materiales_recepciones`
--
ALTER TABLE `materiales_recepciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recepciones_solicitud` (`id_solicitud`),
  ADD KEY `idx_recepciones_usuario` (`id_usuario`);

--
-- Indices de la tabla `materiales_recepcion_detalle`
--
ALTER TABLE `materiales_recepcion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rec_det_recepcion` (`id_recepcion`),
  ADD KEY `idx_rec_det_solicitud` (`id_detalle_solicitud`);

--
-- Indices de la tabla `materiales_solicitudes`
--
ALTER TABLE `materiales_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_solicitud_numero` (`numero_solicitud`),
  ADD KEY `idx_solicitud_proceso_area` (`id_proceso_area`),
  ADD KEY `idx_solicitud_profesional` (`id_profesional_solicitante`),
  ADD KEY `idx_solicitud_estado` (`estado`),
  ADD KEY `idx_solicitud_revision` (`id_usuario_revision`);

--
-- Indices de la tabla `materiales_solicitud_destinos`
--
ALTER TABLE `materiales_solicitud_destinos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destino_detalle` (`id_detalle_solicitud`),
  ADD KEY `idx_destino_ficha` (`id_ficha`),
  ADD KEY `idx_destino_tipo` (`tipo_destino`);

--
-- Indices de la tabla `materiales_solicitud_detalle`
--
ALTER TABLE `materiales_solicitud_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_detalle_solicitud` (`id_solicitud`),
  ADD KEY `idx_detalle_material` (`id_material`),
  ADD KEY `idx_materiales_detalle_ficha` (`id_ficha`);

--
-- Indices de la tabla `materiales_solicitud_historial`
--
ALTER TABLE `materiales_solicitud_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historial_solicitud` (`id_solicitud`),
  ADD KEY `idx_historial_usuario` (`id_usuario`),
  ADD KEY `idx_historial_fecha` (`created_at`),
  ADD KEY `idx_historial_estado_nuevo` (`estado_nuevo`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_matriculas_aprendiz` (`id_aprendiz`),
  ADD KEY `idx_matriculas_ficha` (`id_ficha`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_permisos_nombre` (`nombre`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_personas_documento` (`tipo_documento`,`documento`),
  ADD KEY `idx_personas_nombre` (`apellidos`,`nombres`);

--
-- Indices de la tabla `profesionales`
--
ALTER TABLE `profesionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_profesional_persona` (`id_persona`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_programas_codigo` (`codigo`);

--
-- Indices de la tabla `rap`
--
ALTER TABLE `rap`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rap_competencia_codigo` (`id_competencia`,`codigo`);

--
-- Indices de la tabla `recomendaciones_nutricionales`
--
ALTER TABLE `recomendaciones_nutricionales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recomendaciones_valoracion` (`id_valoracion`),
  ADD KEY `fk_recomendaciones_profesional` (`id_profesional`);

--
-- Indices de la tabla `remisiones`
--
ALTER TABLE `remisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_remisiones_aprendiz` (`id_aprendiz`),
  ADD KEY `fk_remisiones_profesional` (`id_profesional`);

--
-- Indices de la tabla `resultados_aprendiz`
--
ALTER TABLE `resultados_aprendiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resultados_aprendiz` (`id_aprendiz`),
  ADD KEY `idx_resultados_rap` (`id_rap`),
  ADD KEY `fk_resultados_instructor` (`id_instructor`),
  ADD KEY `idx_resultados_ficha` (`id_ficha`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_roles_nombre` (`nombre`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rol_permiso` (`id_rol`,`id_permiso`),
  ADD KEY `fk_rol_permisos_permiso` (`id_permiso`);

--
-- Indices de la tabla `seguimientos_fisicos`
--
ALTER TABLE `seguimientos_fisicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_segfis_aprendiz_fecha` (`id_aprendiz`,`fecha_toma`),
  ADD KEY `idx_segfis_ficha_fecha` (`id_ficha`,`fecha_toma`),
  ADD KEY `idx_segfis_usuario` (`id_usuario`);

--
-- Indices de la tabla `seguimientos_salud_mental`
--
ALTER TABLE `seguimientos_salud_mental`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_salud_mental_aprendiz` (`id_aprendiz`),
  ADD KEY `fk_salud_mental_profesional` (`id_profesional`);

--
-- Indices de la tabla `seguimientos_salud_sexual`
--
ALTER TABLE `seguimientos_salud_sexual`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_salud_sexual_aprendiz` (`id_aprendiz`),
  ADD KEY `fk_salud_sexual_profesional` (`id_profesional`);

--
-- Indices de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_signos_valoracion` (`id_valoracion`);

--
-- Indices de la tabla `solicitudes_atencion`
--
ALTER TABLE `solicitudes_atencion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitudes_aprendiz` (`id_aprendiz`);

--
-- Indices de la tabla `tipos_poblacion`
--
ALTER TABLE `tipos_poblacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tipo_poblacion_nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_username` (`username`),
  ADD UNIQUE KEY `uk_usuarios_persona` (`id_persona`);

--
-- Indices de la tabla `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_rol` (`id_usuario`,`id_rol`),
  ADD KEY `fk_usuario_roles_rol` (`id_rol`);

--
-- Indices de la tabla `valoraciones_nutricionales`
--
ALTER TABLE `valoraciones_nutricionales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nutricion_aprendiz` (`id_aprendiz`),
  ADD KEY `fk_nutricion_profesional` (`id_profesional`);

--
-- Indices de la tabla `valoraciones_salud_fisica`
--
ALTER TABLE `valoraciones_salud_fisica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_salud_fisica_aprendiz` (`id_aprendiz`),
  ADD KEY `fk_salud_fisica_profesional` (`id_profesional`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actualizaciones_caracterizacion`
--
ALTER TABLE `actualizaciones_caracterizacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alertas_acompanamiento`
--
ALTER TABLE `alertas_acompanamiento`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aprendices`
--
ALTER TABLE `aprendices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aprendiz_poblacion`
--
ALTER TABLE `aprendiz_poblacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_ficha`
--
ALTER TABLE `asignaciones_ficha`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `competencias`
--
ALTER TABLE `competencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compromisos`
--
ALTER TABLE `compromisos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuestionarios_bienestar`
--
ALTER TABLE `cuestionarios_bienestar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `enlaces_actualizacion_ficha`
--
ALTER TABLE `enlaces_actualizacion_ficha`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eps`
--
ALTER TABLE `eps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etapas`
--
ALTER TABLE `etapas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etapas_aprendiz`
--
ALTER TABLE `etapas_aprendiz`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fichas`
--
ALTER TABLE `fichas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_area_responsables`
--
ALTER TABLE `materiales_area_responsables`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_catalogo`
--
ALTER TABLE `materiales_catalogo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_distribuciones`
--
ALTER TABLE `materiales_distribuciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_distribucion_detalle`
--
ALTER TABLE `materiales_distribucion_detalle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_importaciones`
--
ALTER TABLE `materiales_importaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_importacion_detalle`
--
ALTER TABLE `materiales_importacion_detalle`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_procesos`
--
ALTER TABLE `materiales_procesos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_proceso_areas`
--
ALTER TABLE `materiales_proceso_areas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_recepciones`
--
ALTER TABLE `materiales_recepciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_recepcion_detalle`
--
ALTER TABLE `materiales_recepcion_detalle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_solicitudes`
--
ALTER TABLE `materiales_solicitudes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_solicitud_destinos`
--
ALTER TABLE `materiales_solicitud_destinos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_solicitud_detalle`
--
ALTER TABLE `materiales_solicitud_detalle`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales_solicitud_historial`
--
ALTER TABLE `materiales_solicitud_historial`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesionales`
--
ALTER TABLE `profesionales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rap`
--
ALTER TABLE `rap`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recomendaciones_nutricionales`
--
ALTER TABLE `recomendaciones_nutricionales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `remisiones`
--
ALTER TABLE `remisiones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados_aprendiz`
--
ALTER TABLE `resultados_aprendiz`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimientos_fisicos`
--
ALTER TABLE `seguimientos_fisicos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimientos_salud_mental`
--
ALTER TABLE `seguimientos_salud_mental`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimientos_salud_sexual`
--
ALTER TABLE `seguimientos_salud_sexual`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_atencion`
--
ALTER TABLE `solicitudes_atencion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_poblacion`
--
ALTER TABLE `tipos_poblacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario_roles`
--
ALTER TABLE `usuario_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `valoraciones_nutricionales`
--
ALTER TABLE `valoraciones_nutricionales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `valoraciones_salud_fisica`
--
ALTER TABLE `valoraciones_salud_fisica`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actualizaciones_caracterizacion`
--
ALTER TABLE `actualizaciones_caracterizacion`
  ADD CONSTRAINT `fk_actualizacion_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_actualizacion_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `alertas_acompanamiento`
--
ALTER TABLE `alertas_acompanamiento`
  ADD CONSTRAINT `fk_alertas_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alertas_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `aprendices`
--
ALTER TABLE `aprendices`
  ADD CONSTRAINT `fk_aprendices_eps` FOREIGN KEY (`id_eps`) REFERENCES `eps` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aprendices_persona` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `aprendiz_poblacion`
--
ALTER TABLE `aprendiz_poblacion`
  ADD CONSTRAINT `fk_aprendiz_poblacion_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aprendiz_poblacion_tipo` FOREIGN KEY (`id_tipo_poblacion`) REFERENCES `tipos_poblacion` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaciones_ficha`
--
ALTER TABLE `asignaciones_ficha`
  ADD CONSTRAINT `fk_asignaciones_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asignaciones_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `competencias`
--
ALTER TABLE `competencias`
  ADD CONSTRAINT `fk_competencias_programa` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `compromisos`
--
ALTER TABLE `compromisos`
  ADD CONSTRAINT `fk_compromisos_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cuestionarios_bienestar`
--
ALTER TABLE `cuestionarios_bienestar`
  ADD CONSTRAINT `fk_cuestionario_bienestar_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`),
  ADD CONSTRAINT `fk_cuestionario_bienestar_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`);

--
-- Filtros para la tabla `enlaces_actualizacion_ficha`
--
ALTER TABLE `enlaces_actualizacion_ficha`
  ADD CONSTRAINT `fk_enlace_actualizacion_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `etapas_aprendiz`
--
ALTER TABLE `etapas_aprendiz`
  ADD CONSTRAINT `fk_etapas_aprendiz_etapa` FOREIGN KEY (`id_etapa`) REFERENCES `etapas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etapas_aprendiz_matricula` FOREIGN KEY (`id_matricula`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `fichas`
--
ALTER TABLE `fichas`
  ADD CONSTRAINT `fk_fichas_jornada` FOREIGN KEY (`id_jornada`) REFERENCES `jornadas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fichas_programa` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `materiales_distribuciones`
--
ALTER TABLE `materiales_distribuciones`
  ADD CONSTRAINT `fk_mat_dist_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `materiales_distribucion_detalle`
--
ALTER TABLE `materiales_distribucion_detalle`
  ADD CONSTRAINT `fk_mat_dist_det_cabecera` FOREIGN KEY (`id_distribucion`) REFERENCES `materiales_distribuciones` (`id`),
  ADD CONSTRAINT `fk_mat_dist_det_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`),
  ADD CONSTRAINT `fk_mat_dist_det_solicitud` FOREIGN KEY (`id_detalle_solicitud`) REFERENCES `materiales_solicitud_detalle` (`id`);

--
-- Filtros para la tabla `materiales_recepciones`
--
ALTER TABLE `materiales_recepciones`
  ADD CONSTRAINT `fk_mat_rec_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `materiales_solicitudes` (`id`),
  ADD CONSTRAINT `fk_mat_rec_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `materiales_recepcion_detalle`
--
ALTER TABLE `materiales_recepcion_detalle`
  ADD CONSTRAINT `fk_mat_rec_det_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `materiales_recepciones` (`id`),
  ADD CONSTRAINT `fk_mat_rec_det_solicitud` FOREIGN KEY (`id_detalle_solicitud`) REFERENCES `materiales_solicitud_detalle` (`id`);

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `fk_matriculas_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matriculas_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `profesionales`
--
ALTER TABLE `profesionales`
  ADD CONSTRAINT `fk_profesionales_persona` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `rap`
--
ALTER TABLE `rap`
  ADD CONSTRAINT `fk_rap_competencia` FOREIGN KEY (`id_competencia`) REFERENCES `competencias` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `recomendaciones_nutricionales`
--
ALTER TABLE `recomendaciones_nutricionales`
  ADD CONSTRAINT `fk_recomendaciones_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recomendaciones_valoracion` FOREIGN KEY (`id_valoracion`) REFERENCES `valoraciones_nutricionales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `remisiones`
--
ALTER TABLE `remisiones`
  ADD CONSTRAINT `fk_remisiones_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_remisiones_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `resultados_aprendiz`
--
ALTER TABLE `resultados_aprendiz`
  ADD CONSTRAINT `fk_resultados_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultados_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultados_instructor` FOREIGN KEY (`id_instructor`) REFERENCES `profesionales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultados_rap` FOREIGN KEY (`id_rap`) REFERENCES `rap` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `fk_rol_permisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_permisos_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimientos_fisicos`
--
ALTER TABLE `seguimientos_fisicos`
  ADD CONSTRAINT `fk_segfis_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_segfis_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_segfis_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimientos_salud_mental`
--
ALTER TABLE `seguimientos_salud_mental`
  ADD CONSTRAINT `fk_salud_mental_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salud_mental_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimientos_salud_sexual`
--
ALTER TABLE `seguimientos_salud_sexual`
  ADD CONSTRAINT `fk_salud_sexual_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salud_sexual_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  ADD CONSTRAINT `fk_signos_valoracion` FOREIGN KEY (`id_valoracion`) REFERENCES `valoraciones_salud_fisica` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_atencion`
--
ALTER TABLE `solicitudes_atencion`
  ADD CONSTRAINT `fk_solicitudes_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_persona` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD CONSTRAINT `fk_usuario_roles_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_roles_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `valoraciones_nutricionales`
--
ALTER TABLE `valoraciones_nutricionales`
  ADD CONSTRAINT `fk_nutricion_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nutricion_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `valoraciones_salud_fisica`
--
ALTER TABLE `valoraciones_salud_fisica`
  ADD CONSTRAINT `fk_salud_fisica_aprendiz` FOREIGN KEY (`id_aprendiz`) REFERENCES `aprendices` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salud_fisica_profesional` FOREIGN KEY (`id_profesional`) REFERENCES `profesionales` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
