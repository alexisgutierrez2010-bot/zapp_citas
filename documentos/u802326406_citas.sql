-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-12-2025 a las 23:30:37
-- Versión del servidor: 10.4.22-MariaDB
-- Versión de PHP: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u802326406_citas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audittrail`
--

CREATE TABLE `audittrail` (
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `script` varchar(80) DEFAULT NULL,
  `user` varchar(80) DEFAULT NULL,
  `action` varchar(80) DEFAULT NULL,
  `table` varchar(80) DEFAULT NULL,
  `field` varchar(80) DEFAULT NULL,
  `keyvalue` longtext DEFAULT NULL,
  `oldvalue` longtext DEFAULT NULL,
  `newvalue` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j099_auditorias`
--

CREATE TABLE `j099_auditorias` (
  `id_audit` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_negocio` int(11) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j100_usuarios`
--

CREATE TABLE `j100_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_negocio` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Guardar siempre el hash de la contraseña, nunca el texto plano',
  `correo_electronico` varchar(255) NOT NULL,
  `rol` enum('Admin','Propietario') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j102_negocios`
--

CREATE TABLE `j102_negocios` (
  `id_negocio` int(11) NOT NULL,
  `nombre_negocio` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `id_categoria_negocio` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 2=Suspendido, 3=Eliminado',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del negocio',
  `fecha_habilitacion` datetime DEFAULT NULL COMMENT 'Fecha de inicio del período de prueba',
  `dias_prueba` int(11) NOT NULL DEFAULT 30 COMMENT 'Días de prueba asignados',
  `fecha_desactivacion` datetime DEFAULT NULL COMMENT 'Fecha de fin del período de prueba',
  `direccion1` varchar(128) DEFAULT NULL,
  `direccion2` varchar(128) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `id_pais` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `descripcion_servicios` text DEFAULT NULL,
  `dias_trabajo` varchar(20) DEFAULT NULL COMMENT 'Ej: 1,2,3,4,5 para L-V',
  `hora_inicio` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `intervalo_minutos` int(11) NOT NULL COMMENT 'Tiempo entre citas en minutos',
  `background_image_data` longblob DEFAULT NULL,
  `background_image_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Disparadores `j102_negocios`
--
DELIMITER $$
CREATE TRIGGER `trg_negocios_before_insert` BEFORE INSERT ON `j102_negocios` FOR EACH ROW BEGIN
    -- Si la fecha de habilitación y los días de prueba no son nulos, calcula la fecha de desactivación.
    IF NEW.fecha_habilitacion IS NOT NULL AND NEW.dias_prueba IS NOT NULL THEN
        SET NEW.fecha_desactivacion = DATE_ADD(NEW.fecha_habilitacion, INTERVAL NEW.dias_prueba DAY);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_negocios_before_update` BEFORE UPDATE ON `j102_negocios` FOR EACH ROW BEGIN
    -- Condición: Recalcular solo si los días de prueba o la fecha de habilitación cambian,
    -- Y ADEMÁS, el usuario no está modificando manualmente la fecha de desactivación.
    IF (NEW.dias_prueba <> OLD.dias_prueba OR NEW.fecha_habilitacion <> OLD.fecha_habilitacion) 
       AND NEW.fecha_desactivacion = OLD.fecha_desactivacion THEN
        
        SET NEW.fecha_desactivacion = DATE_ADD(NEW.fecha_habilitacion, INTERVAL NEW.dias_prueba DAY);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j103_categorias`
--

CREATE TABLE `j103_categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j104_servicios`
--

CREATE TABLE `j104_servicios` (
  `id_servicio` int(11) NOT NULL,
  `id_negocio` int(11) NOT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `duracion_valor` int(11) NOT NULL DEFAULT 30,
  `duracion_unidad` enum('Minutos','Horas','Dias') NOT NULL DEFAULT 'Minutos',
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `foto_servicio` mediumblob DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_registro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j106_clientes`
--

CREATE TABLE `j106_clientes` (
  `id_cliente` int(11) NOT NULL,
  `id_negocio` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `foto_perfil_data` longblob DEFAULT NULL,
  `foto_perfil_tipo` varchar(50) DEFAULT NULL,
  `in_sms` tinyint(1) NOT NULL DEFAULT 1,
  `in_email` tinyint(1) NOT NULL DEFAULT 1,
  `in_whatsapp` tinyint(1) NOT NULL DEFAULT 1,
  `nombre_completo` varchar(255) NOT NULL,
  `numero_celular` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(255) NOT NULL,
  `direccion1` varchar(128) DEFAULT NULL,
  `direccion2` varchar(128) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `id_pais` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `notas_adicionales` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j108_citas`
--

CREATE TABLE `j108_citas` (
  `id_cita` int(11) NOT NULL,
  `id_negocio` int(11) NOT NULL,
  `tipo_cita` varchar(20) NOT NULL DEFAULT 'Servicio',
  `id_cliente` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL,
  `descripcion_trabajo` text DEFAULT NULL COMMENT 'Descripción específica del trabajo para esta cita',
  `in_sms` tinyint(4) NOT NULL DEFAULT 0,
  `in_email` tinyint(4) NOT NULL DEFAULT 0,
  `in_whatsapp` tinyint(4) NOT NULL DEFAULT 0,
  `estado_cita` enum('Pendiente','Confirmada','Cancelada','Completada','No Asistió') DEFAULT 'Pendiente',
  `id_evento_google` varchar(255) DEFAULT NULL COMMENT 'ID único del evento devuelto por la API de Google Calendar',
  `url_evento_google` varchar(500) DEFAULT NULL COMMENT 'URL para ver el evento directamente en Google Calendar',
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j109_invitados_cita`
--

CREATE TABLE `j109_invitados_cita` (
  `id_invitado` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `nombre_invitado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_celular_invitado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo_electronico_invitado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_invitacion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j110_paises`
--

CREATE TABLE `j110_paises` (
  `id_pais` int(11) NOT NULL,
  `nombre_pais` varchar(100) NOT NULL,
  `codigo_pais` varchar(4) NOT NULL,
  `codigo_telefono` varchar(10) NOT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j111_estados`
--

CREATE TABLE `j111_estados` (
  `id_estado` int(11) NOT NULL,
  `id_pais` int(11) NOT NULL,
  `nombre_estado` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `j112_resenas`
--

CREATE TABLE `j112_resenas` (
  `id_resena` int(11) NOT NULL,
  `id_negocio` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `puntuacion` decimal(2,1) NOT NULL COMMENT 'De 1.0 a 5.0',
  `comentario` text DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audittrail`
--
ALTER TABLE `audittrail`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `j099_auditorias`
--
ALTER TABLE `j099_auditorias`
  ADD PRIMARY KEY (`id_audit`),
  ADD KEY `fk_auditorias_usuarios` (`id_usuario`),
  ADD KEY `fk_auditorias_negocios` (`id_negocio`);

--
-- Indices de la tabla `j100_usuarios`
--
ALTER TABLE `j100_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `fk_usuarios_negocio` (`id_negocio`) USING BTREE;

--
-- Indices de la tabla `j102_negocios`
--
ALTER TABLE `j102_negocios`
  ADD PRIMARY KEY (`id_negocio`),
  ADD KEY `nombre_negocio` (`nombre_negocio`),
  ADD KEY `id_pais` (`id_pais`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `fk_negocio_categoria` (`id_categoria_negocio`);

--
-- Indices de la tabla `j103_categorias`
--
ALTER TABLE `j103_categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `j104_servicios`
--
ALTER TABLE `j104_servicios`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `fk_servicios_negocio` (`id_negocio`) USING BTREE;

--
-- Indices de la tabla `j106_clientes`
--
ALTER TABLE `j106_clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`),
  ADD UNIQUE KEY `idx_unique_email_negocio` (`id_negocio`,`correo_electronico`),
  ADD UNIQUE KEY `idx_unique_celular_negocio` (`id_negocio`,`numero_celular`),
  ADD KEY `fk_clientes_negocio` (`id_negocio`) USING BTREE,
  ADD KEY `id_pais` (`id_pais`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `j108_citas`
--
ALTER TABLE `j108_citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD UNIQUE KEY `id_evento_google` (`id_evento_google`),
  ADD UNIQUE KEY `fk_citas_unicas` (`id_negocio`,`tipo_cita`,`id_cliente`,`id_servicio`,`fecha_hora_inicio`),
  ADD KEY `fk_citas_negocio` (`id_negocio`) USING BTREE,
  ADD KEY `idx_tipo_cita` (`tipo_cita`),
  ADD KEY `fk_citas_clientes` (`id_cliente`),
  ADD KEY `fk_citas_servicios` (`id_servicio`);

--
-- Indices de la tabla `j109_invitados_cita`
--
ALTER TABLE `j109_invitados_cita`
  ADD PRIMARY KEY (`id_invitado`),
  ADD KEY `idx_id_cita` (`id_cita`);

--
-- Indices de la tabla `j110_paises`
--
ALTER TABLE `j110_paises`
  ADD PRIMARY KEY (`id_pais`),
  ADD UNIQUE KEY `nombre_pais` (`nombre_pais`);

--
-- Indices de la tabla `j111_estados`
--
ALTER TABLE `j111_estados`
  ADD PRIMARY KEY (`id_estado`),
  ADD KEY `id_pais` (`id_pais`);

--
-- Indices de la tabla `j112_resenas`
--
ALTER TABLE `j112_resenas`
  ADD PRIMARY KEY (`id_resena`),
  ADD KEY `id_negocio` (`id_negocio`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audittrail`
--
ALTER TABLE `audittrail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j099_auditorias`
--
ALTER TABLE `j099_auditorias`
  MODIFY `id_audit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j100_usuarios`
--
ALTER TABLE `j100_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j102_negocios`
--
ALTER TABLE `j102_negocios`
  MODIFY `id_negocio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j103_categorias`
--
ALTER TABLE `j103_categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j104_servicios`
--
ALTER TABLE `j104_servicios`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j106_clientes`
--
ALTER TABLE `j106_clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j108_citas`
--
ALTER TABLE `j108_citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j109_invitados_cita`
--
ALTER TABLE `j109_invitados_cita`
  MODIFY `id_invitado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j110_paises`
--
ALTER TABLE `j110_paises`
  MODIFY `id_pais` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j111_estados`
--
ALTER TABLE `j111_estados`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `j112_resenas`
--
ALTER TABLE `j112_resenas`
  MODIFY `id_resena` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `j099_auditorias`
--
ALTER TABLE `j099_auditorias`
  ADD CONSTRAINT `fk_auditorias_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditorias_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `j100_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `j100_usuarios`
--
ALTER TABLE `j100_usuarios`
  ADD CONSTRAINT `fk_usuarios_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `j102_negocios`
--
ALTER TABLE `j102_negocios`
  ADD CONSTRAINT `fk_negocio_categoria` FOREIGN KEY (`id_categoria_negocio`) REFERENCES `j103_categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `j106_clientes`
--
ALTER TABLE `j106_clientes`
  ADD CONSTRAINT `fk_clientes_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `j108_citas`
--
ALTER TABLE `j108_citas`
  ADD CONSTRAINT `fk_citas_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `j106_clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_servicios` FOREIGN KEY (`id_servicio`) REFERENCES `j104_servicios` (`id_servicio`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `j109_invitados_cita`
--
ALTER TABLE `j109_invitados_cita`
  ADD CONSTRAINT `fk_invitado_cita` FOREIGN KEY (`id_cita`) REFERENCES `j108_citas` (`id_cita`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `j111_estados`
--
ALTER TABLE `j111_estados`
  ADD CONSTRAINT `fk_estado_pais` FOREIGN KEY (`id_pais`) REFERENCES `j110_paises` (`id_pais`) ON DELETE CASCADE;

--
-- Filtros para la tabla `j112_resenas`
--
ALTER TABLE `j112_resenas`
  ADD CONSTRAINT `j112_resenas_ibfk_1` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`),
  ADD CONSTRAINT `j112_resenas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `j106_clientes` (`id_cliente`),
  ADD CONSTRAINT `j112_resenas_ibfk_3` FOREIGN KEY (`id_servicio`) REFERENCES `j104_servicios` (`id_servicio`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
