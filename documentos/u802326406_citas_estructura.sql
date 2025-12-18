/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : u802326406_citas

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2025-12-18 10:18:41
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `j099_auditorias`
-- ----------------------------
DROP TABLE IF EXISTS `j099_auditorias`;
CREATE TABLE `j099_auditorias` (
  `id_audit` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `id_negocio` int(11) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_audit`),
  KEY `fk_auditorias_usuarios` (`id_usuario`),
  KEY `fk_auditorias_negocios` (`id_negocio`),
  CONSTRAINT `fk_auditorias_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_auditorias_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `j100_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j099_auditorias
-- ----------------------------

-- ----------------------------
-- Table structure for `j100_usuarios`
-- ----------------------------
DROP TABLE IF EXISTS `j100_usuarios`;
CREATE TABLE `j100_usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_negocio` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Guardar siempre el hash de la contraseña, nunca el texto plano',
  `correo_electronico` varchar(255) NOT NULL,
  `rol` enum('Admin','Propietario') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`),
  KEY `fk_usuarios_negocio` (`id_negocio`) USING BTREE,
  CONSTRAINT `fk_usuarios_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j100_usuarios
-- ----------------------------

-- ----------------------------
-- Table structure for `j102_negocios`
-- ----------------------------
DROP TABLE IF EXISTS `j102_negocios`;
CREATE TABLE `j102_negocios` (
  `id_negocio` int(11) NOT NULL AUTO_INCREMENT,
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
  `background_image_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_negocio`),
  KEY `nombre_negocio` (`nombre_negocio`),
  KEY `id_pais` (`id_pais`),
  KEY `id_estado` (`id_estado`),
  KEY `fk_negocio_categoria` (`id_categoria_negocio`),
  CONSTRAINT `fk_negocio_categoria` FOREIGN KEY (`id_categoria_negocio`) REFERENCES `j103_categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j102_negocios
-- ----------------------------

-- ----------------------------
-- Table structure for `j103_categorias`
-- ----------------------------
DROP TABLE IF EXISTS `j103_categorias`;
CREATE TABLE `j103_categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of j103_categorias
-- ----------------------------
INSERT INTO `j103_categorias` VALUES ('1', 'Peluquería', 'Servicios de corte, peinado y tratamiento capilar.', '1');
INSERT INTO `j103_categorias` VALUES ('2', 'Barbería', 'Servicios de corte de cabello masculino, afeitado y cuidado de la barba.', '1');
INSERT INTO `j103_categorias` VALUES ('3', 'Manicure y Pedicure', 'Cuidado y belleza de uñas, manos y pies.', '1');
INSERT INTO `j103_categorias` VALUES ('4', 'Estética y Spa', 'Tratamientos faciales, corporales y de relajación.', '1');
INSERT INTO `j103_categorias` VALUES ('5', 'Odontología', 'Servicios de salud dental y odontología general.', '1');
INSERT INTO `j103_categorias` VALUES ('6', 'Consultoría y Reuniones', 'Alquiler de espacios o servicios de consultoría profesional.', '1');
INSERT INTO `j103_categorias` VALUES ('7', 'Tatuajes y Piercings', 'Servicios de arte corporal.', '1');
INSERT INTO `j103_categorias` VALUES ('8', 'Clases y Tutorías', 'Clases particulares, talleres y tutorías personalizadas.', '1');
INSERT INTO `j103_categorias` VALUES ('9', 'Restaurantes y comidas', 'Elaboración de comida tradicionales', '1');
INSERT INTO `j103_categorias` VALUES ('11', 'Prueba', 'Prueba', '0');

-- ----------------------------
-- Table structure for `j104_servicios`
-- ----------------------------
DROP TABLE IF EXISTS `j104_servicios`;
CREATE TABLE `j104_servicios` (
  `id_servicio` int(11) NOT NULL AUTO_INCREMENT,
  `id_negocio` int(11) NOT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `duracion_valor` int(11) NOT NULL DEFAULT 30,
  `duracion_unidad` enum('Minutos','Horas','Dias') NOT NULL DEFAULT 'Minutos',
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  PRIMARY KEY (`id_servicio`),
  KEY `fk_servicios_negocio` (`id_negocio`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j104_servicios
-- ----------------------------

-- ----------------------------
-- Table structure for `j106_clientes`
-- ----------------------------
DROP TABLE IF EXISTS `j106_clientes`;
CREATE TABLE `j106_clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
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
  `notas_adicionales` text DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`),
  KEY `fk_clientes_negocio` (`id_negocio`) USING BTREE,
  KEY `id_pais` (`id_pais`),
  KEY `id_estado` (`id_estado`),
  CONSTRAINT `fk_clientes_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j106_clientes
-- ----------------------------

-- ----------------------------
-- Table structure for `j108_citas`
-- ----------------------------
DROP TABLE IF EXISTS `j108_citas`;
CREATE TABLE `j108_citas` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `id_negocio` int(11) NOT NULL,
  `tipo_cita` varchar(20) NOT NULL DEFAULT 'Servicio',
  `id_cliente` int(11) NOT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL,
  `descripcion_trabajo` text DEFAULT NULL COMMENT 'Descripción específica del trabajo para esta cita',
  `IN_SMS` tinyint(4) NOT NULL DEFAULT 0,
  `IN_EMAIL` tinyint(4) NOT NULL DEFAULT 0,
  `estado_cita` enum('Pendiente','Confirmada','Cancelada','Completada','No Asistió') DEFAULT 'Pendiente',
  `id_evento_google` varchar(255) DEFAULT NULL COMMENT 'ID único del evento devuelto por la API de Google Calendar',
  `url_evento_google` varchar(500) DEFAULT NULL COMMENT 'URL para ver el evento directamente en Google Calendar',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cita`),
  UNIQUE KEY `id_evento_google` (`id_evento_google`),
  UNIQUE KEY `fk_citas_unicas` (`id_negocio`,`tipo_cita`,`id_cliente`,`id_servicio`,`fecha_hora_inicio`),
  KEY `fk_citas_negocio` (`id_negocio`) USING BTREE,
  KEY `idx_tipo_cita` (`tipo_cita`),
  KEY `fk_citas_clientes` (`id_cliente`),
  KEY `fk_citas_servicios` (`id_servicio`),
  CONSTRAINT `fk_citas_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `j106_clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_negocios` FOREIGN KEY (`id_negocio`) REFERENCES `j102_negocios` (`id_negocio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_servicios` FOREIGN KEY (`id_servicio`) REFERENCES `j104_servicios` (`id_servicio`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j108_citas
-- ----------------------------

-- ----------------------------
-- Table structure for `j109_invitados_cita`
-- ----------------------------
DROP TABLE IF EXISTS `j109_invitados_cita`;
CREATE TABLE `j109_invitados_cita` (
  `id_invitado` int(11) NOT NULL AUTO_INCREMENT,
  `id_cita` int(11) NOT NULL,
  `nombre_invitado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_celular_invitado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo_electronico_invitado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_invitacion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_invitado`),
  KEY `idx_id_cita` (`id_cita`),
  CONSTRAINT `fk_invitado_cita` FOREIGN KEY (`id_cita`) REFERENCES `j108_citas` (`id_cita`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of j109_invitados_cita
-- ----------------------------
INSERT INTO `j109_invitados_cita` VALUES ('5', '3', 'Xiomara Castro', '3465158749', 'xiomarabeatrizcastro2013@gmail.com', 'Pendiente', '2025-12-14 19:38:24');
INSERT INTO `j109_invitados_cita` VALUES ('6', '3', 'Alexandra Munnoz', '123456789', 'alexandra.mc2710@gmail.com', 'Pendiente', '2025-12-14 19:38:24');

-- ----------------------------
-- Table structure for `j110_paises`
-- ----------------------------
DROP TABLE IF EXISTS `j110_paises`;
CREATE TABLE `j110_paises` (
  `id_pais` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_pais` varchar(100) NOT NULL,
  `codigo_pais` varchar(4) NOT NULL,
  `codigo_telefono` varchar(10) NOT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`id_pais`),
  UNIQUE KEY `nombre_pais` (`nombre_pais`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j110_paises
-- ----------------------------
INSERT INTO `j110_paises` VALUES ('1', 'Estados Unidos', 'US', '+1', 'America/Chicago');
INSERT INTO `j110_paises` VALUES ('2', 'Venezuela', 'VE', '+58', 'America/Caracas');
INSERT INTO `j110_paises` VALUES ('3', 'México', 'MX', '+52', 'UTC');
INSERT INTO `j110_paises` VALUES ('4', 'España', 'ES', '+34', 'UTC');

-- ----------------------------
-- Table structure for `j111_estados`
-- ----------------------------
DROP TABLE IF EXISTS `j111_estados`;
CREATE TABLE `j111_estados` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `id_pais` int(11) NOT NULL,
  `nombre_estado` varchar(100) NOT NULL,
  PRIMARY KEY (`id_estado`),
  KEY `id_pais` (`id_pais`),
  CONSTRAINT `fk_estado_pais` FOREIGN KEY (`id_pais`) REFERENCES `j110_paises` (`id_pais`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of j111_estados
-- ----------------------------
INSERT INTO `j111_estados` VALUES ('1', '1', 'Alabama');
INSERT INTO `j111_estados` VALUES ('2', '1', 'Alaska');
INSERT INTO `j111_estados` VALUES ('3', '1', 'Arizona');
INSERT INTO `j111_estados` VALUES ('4', '1', 'Arkansas');
INSERT INTO `j111_estados` VALUES ('5', '1', 'California');
INSERT INTO `j111_estados` VALUES ('6', '1', 'Colorado');
INSERT INTO `j111_estados` VALUES ('7', '1', 'Connecticut');
INSERT INTO `j111_estados` VALUES ('8', '1', 'Delaware');
INSERT INTO `j111_estados` VALUES ('9', '1', 'Florida');
INSERT INTO `j111_estados` VALUES ('10', '1', 'Georgia');
INSERT INTO `j111_estados` VALUES ('11', '1', 'Hawaii');
INSERT INTO `j111_estados` VALUES ('12', '1', 'Idaho');
INSERT INTO `j111_estados` VALUES ('13', '1', 'Illinois');
INSERT INTO `j111_estados` VALUES ('14', '1', 'Indiana');
INSERT INTO `j111_estados` VALUES ('15', '1', 'Iowa');
INSERT INTO `j111_estados` VALUES ('16', '1', 'Kansas');
INSERT INTO `j111_estados` VALUES ('17', '1', 'Kentucky');
INSERT INTO `j111_estados` VALUES ('18', '1', 'Louisiana');
INSERT INTO `j111_estados` VALUES ('19', '1', 'Maine');
INSERT INTO `j111_estados` VALUES ('20', '1', 'Maryland');
INSERT INTO `j111_estados` VALUES ('21', '1', 'Massachusetts');
INSERT INTO `j111_estados` VALUES ('22', '1', 'Michigan');
INSERT INTO `j111_estados` VALUES ('23', '1', 'Minnesota');
INSERT INTO `j111_estados` VALUES ('24', '1', 'Mississippi');
INSERT INTO `j111_estados` VALUES ('25', '1', 'Missouri');
INSERT INTO `j111_estados` VALUES ('26', '1', 'Montana');
INSERT INTO `j111_estados` VALUES ('27', '1', 'Nebraska');
INSERT INTO `j111_estados` VALUES ('28', '1', 'Nevada');
INSERT INTO `j111_estados` VALUES ('29', '1', 'New Hampshire');
INSERT INTO `j111_estados` VALUES ('30', '1', 'New Jersey');
INSERT INTO `j111_estados` VALUES ('31', '1', 'New Mexico');
INSERT INTO `j111_estados` VALUES ('32', '1', 'New York');
INSERT INTO `j111_estados` VALUES ('33', '1', 'North Carolina');
INSERT INTO `j111_estados` VALUES ('34', '1', 'North Dakota');
INSERT INTO `j111_estados` VALUES ('35', '1', 'Ohio');
INSERT INTO `j111_estados` VALUES ('36', '1', 'Oklahoma');
INSERT INTO `j111_estados` VALUES ('37', '1', 'Oregon');
INSERT INTO `j111_estados` VALUES ('38', '1', 'Pennsylvania');
INSERT INTO `j111_estados` VALUES ('39', '1', 'Rhode Island');
INSERT INTO `j111_estados` VALUES ('40', '1', 'South Carolina');
INSERT INTO `j111_estados` VALUES ('41', '1', 'South Dakota');
INSERT INTO `j111_estados` VALUES ('42', '1', 'Tennessee');
INSERT INTO `j111_estados` VALUES ('43', '1', 'Texas');
INSERT INTO `j111_estados` VALUES ('44', '1', 'Utah');
INSERT INTO `j111_estados` VALUES ('45', '1', 'Vermont');
INSERT INTO `j111_estados` VALUES ('46', '1', 'Virginia');
INSERT INTO `j111_estados` VALUES ('47', '1', 'Washington');
INSERT INTO `j111_estados` VALUES ('48', '1', 'West Virginia');
INSERT INTO `j111_estados` VALUES ('49', '1', 'Wisconsin');
INSERT INTO `j111_estados` VALUES ('50', '1', 'Wyoming');
INSERT INTO `j111_estados` VALUES ('51', '2', 'Amazonas');
INSERT INTO `j111_estados` VALUES ('52', '2', 'Anzoátegui');
INSERT INTO `j111_estados` VALUES ('53', '2', 'Apure');
INSERT INTO `j111_estados` VALUES ('54', '2', 'Aragua');
INSERT INTO `j111_estados` VALUES ('55', '2', 'Barinas');
INSERT INTO `j111_estados` VALUES ('56', '2', 'Bolívar');
INSERT INTO `j111_estados` VALUES ('57', '2', 'Carabobo');
INSERT INTO `j111_estados` VALUES ('58', '2', 'Cojedes');
INSERT INTO `j111_estados` VALUES ('59', '2', 'Delta Amacuro');
INSERT INTO `j111_estados` VALUES ('60', '2', 'Distrito Capital');
INSERT INTO `j111_estados` VALUES ('61', '2', 'Falcón');
INSERT INTO `j111_estados` VALUES ('62', '2', 'Guárico');
INSERT INTO `j111_estados` VALUES ('63', '2', 'Lara');
INSERT INTO `j111_estados` VALUES ('64', '2', 'Mérida');
INSERT INTO `j111_estados` VALUES ('65', '2', 'Miranda');
INSERT INTO `j111_estados` VALUES ('66', '2', 'Monagas');
INSERT INTO `j111_estados` VALUES ('67', '2', 'Nueva Esparta');
INSERT INTO `j111_estados` VALUES ('68', '2', 'Portuguesa');
INSERT INTO `j111_estados` VALUES ('69', '2', 'Sucre');
INSERT INTO `j111_estados` VALUES ('70', '2', 'Táchira');
INSERT INTO `j111_estados` VALUES ('71', '2', 'Trujillo');
INSERT INTO `j111_estados` VALUES ('72', '2', 'Vargas');
INSERT INTO `j111_estados` VALUES ('73', '2', 'Yaracuy');
INSERT INTO `j111_estados` VALUES ('74', '2', 'Zulia');
INSERT INTO `j111_estados` VALUES ('75', '3', 'Aguascalientes');
INSERT INTO `j111_estados` VALUES ('76', '3', 'Baja California');
INSERT INTO `j111_estados` VALUES ('77', '3', 'Baja California Sur');
INSERT INTO `j111_estados` VALUES ('78', '3', 'Campeche');
INSERT INTO `j111_estados` VALUES ('79', '3', 'Chiapas');
INSERT INTO `j111_estados` VALUES ('80', '3', 'Chihuahua');
INSERT INTO `j111_estados` VALUES ('81', '3', 'Coahuila');
INSERT INTO `j111_estados` VALUES ('82', '3', 'Colima');
INSERT INTO `j111_estados` VALUES ('83', '3', 'Ciudad de México');
INSERT INTO `j111_estados` VALUES ('84', '3', 'Durango');
INSERT INTO `j111_estados` VALUES ('85', '3', 'Guanajuato');
INSERT INTO `j111_estados` VALUES ('86', '3', 'Guerrero');
INSERT INTO `j111_estados` VALUES ('87', '3', 'Hidalgo');
INSERT INTO `j111_estados` VALUES ('88', '3', 'Jalisco');
INSERT INTO `j111_estados` VALUES ('89', '3', 'México');
INSERT INTO `j111_estados` VALUES ('90', '3', 'Michoacán');
INSERT INTO `j111_estados` VALUES ('91', '3', 'Morelos');
INSERT INTO `j111_estados` VALUES ('92', '3', 'Nayarit');
INSERT INTO `j111_estados` VALUES ('93', '3', 'Nuevo León');
INSERT INTO `j111_estados` VALUES ('94', '3', 'Oaxaca');
INSERT INTO `j111_estados` VALUES ('95', '3', 'Puebla');
INSERT INTO `j111_estados` VALUES ('96', '3', 'Querétaro');
INSERT INTO `j111_estados` VALUES ('97', '3', 'Quintana Roo');
INSERT INTO `j111_estados` VALUES ('98', '3', 'San Luis Potosí');
INSERT INTO `j111_estados` VALUES ('99', '3', 'Sinaloa');
INSERT INTO `j111_estados` VALUES ('100', '3', 'Sonora');
INSERT INTO `j111_estados` VALUES ('101', '3', 'Tabasco');
INSERT INTO `j111_estados` VALUES ('102', '3', 'Tamaulipas');
INSERT INTO `j111_estados` VALUES ('103', '3', 'Tlaxcala');
INSERT INTO `j111_estados` VALUES ('104', '3', 'Veracruz');
INSERT INTO `j111_estados` VALUES ('105', '3', 'Yucatán');
INSERT INTO `j111_estados` VALUES ('106', '3', 'Zacatecas');
INSERT INTO `j111_estados` VALUES ('107', '4', 'Andalucía');
INSERT INTO `j111_estados` VALUES ('108', '4', 'Aragón');
INSERT INTO `j111_estados` VALUES ('109', '4', 'Asturias');
INSERT INTO `j111_estados` VALUES ('110', '4', 'Baleares');
INSERT INTO `j111_estados` VALUES ('111', '4', 'Canarias');
INSERT INTO `j111_estados` VALUES ('112', '4', 'Cantabria');
INSERT INTO `j111_estados` VALUES ('113', '4', 'Castilla-La Mancha');
INSERT INTO `j111_estados` VALUES ('114', '4', 'Castilla y León');
INSERT INTO `j111_estados` VALUES ('115', '4', 'Cataluña');
INSERT INTO `j111_estados` VALUES ('116', '4', 'Comunidad Valenciana');
INSERT INTO `j111_estados` VALUES ('117', '4', 'Extremadura');
INSERT INTO `j111_estados` VALUES ('118', '4', 'Galicia');
INSERT INTO `j111_estados` VALUES ('119', '4', 'La Rioja');
INSERT INTO `j111_estados` VALUES ('120', '4', 'Madrid');
INSERT INTO `j111_estados` VALUES ('121', '4', 'Murcia');
INSERT INTO `j111_estados` VALUES ('122', '4', 'Navarra');
INSERT INTO `j111_estados` VALUES ('123', '4', 'País Vasco');

-- ----------------------------
-- Procedure structure for `P1 - RECONSTRUIR_CLAVES_FORANEAS`
-- ----------------------------
DROP PROCEDURE IF EXISTS `P1 - RECONSTRUIR_CLAVES_FORANEAS`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `P1 - RECONSTRUIR_CLAVES_FORANEAS`()
BEGIN
	#Routine body goes here...

 ALTER TABLE `j100_usuarios` DROP FOREIGN KEY IF EXISTS `fk_usuarios_negocios`;

ALTER TABLE `j100_usuarios`
  ADD CONSTRAINT `fk_usuarios_negocios` 
  FOREIGN KEY (`id_negocio`) 
  REFERENCES `j102_negocios` (`id_negocio`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

ALTER TABLE `j106_clientes` DROP FOREIGN KEY IF EXISTS`fk_clientes_negocios`;

ALTER TABLE `j106_clientes`
  ADD CONSTRAINT `fk_clientes_negocios` 
  FOREIGN KEY (`id_negocio`) 
  REFERENCES `j102_negocios` (`id_negocio`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE;

ALTER TABLE `j108_citas` DROP FOREIGN KEY IF EXISTS`fk_citas_clientes` ;
ALTER TABLE `j108_citas` DROP FOREIGN KEY IF EXISTS`fk_citas_servicios`;
ALTER TABLE `j108_citas` DROP FOREIGN KEY IF EXISTS`fk_citas_negocios` ;


ALTER TABLE `j108_citas`
  ADD CONSTRAINT `fk_citas_clientes` 
  FOREIGN KEY (`id_cliente`) 
  REFERENCES `j106_clientes` (`id_cliente`) 
  ON DELETE CASCADE ON UPDATE CASCADE,
  
  ADD CONSTRAINT `fk_citas_servicios` 
  FOREIGN KEY (`id_servicio`) 
  REFERENCES `j104_servicios` (`id_servicio`) 
  ON DELETE RESTRICT ON UPDATE CASCADE,
  
  ADD CONSTRAINT `fk_citas_negocios` 
  FOREIGN KEY (`id_negocio`) 
  REFERENCES `j102_negocios` (`id_negocio`) 
  ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `j099_auditorias` DROP FOREIGN KEY IF EXISTS`fk_auditorias_usuarios`;
ALTER TABLE `j099_auditorias` DROP FOREIGN KEY IF EXISTS`fk_auditorias_negocios`;

ALTER TABLE `j099_auditorias`
  ADD CONSTRAINT `fk_auditorias_usuarios` 
  FOREIGN KEY (`id_usuario`) 
  REFERENCES `j100_usuarios` (`id_usuario`) 
  ON DELETE SET NULL ON UPDATE CASCADE,
  
  ADD CONSTRAINT `fk_auditorias_negocios` 
  FOREIGN KEY (`id_negocio`) 
  REFERENCES `j102_negocios` (`id_negocio`) 
  ON DELETE SET NULL ON UPDATE CASCADE;



END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `P2 - LIMPIAR LA BASE DE DATOS`
-- ----------------------------
DROP PROCEDURE IF EXISTS `P2 - LIMPIAR LA BASE DE DATOS`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `P2 - LIMPIAR LA BASE DE DATOS`()
BEGIN
	#Routine body goes here...

-- Desactiva temporalmente la protección para poder truncar en cualquier orden
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE j108_citas;
TRUNCATE TABLE j106_clientes;
TRUNCATE TABLE j104_servicios;
TRUNCATE TABLE j099_auditorias;
TRUNCATE TABLE j100_usuarios;
TRUNCATE TABLE j102_negocios;
TRUNCATE TABLE j109_invitados_cita;

-- ¡MUY IMPORTANTE! Vuelve a activar la protección
SET FOREIGN_KEY_CHECKS=1;


END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `P3 - Datos iniciales de arranque de la apliacion`
-- ----------------------------
DROP PROCEDURE IF EXISTS `P3 - Datos iniciales de arranque de la apliacion`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `P3 - Datos iniciales de arranque de la apliacion`()
BEGIN
	#Routine body goes here...

-- Crear el negocio principal
INSERT INTO `j102_negocios` (`id_negocio`, `nombre_negocio`, `email`) 
VALUES (1, 'Negocio Principal www.acticven.com', 'alexisgutierrez@acticven.com');

-- Crear el usuario Master (contraseña: master123)
INSERT INTO `j100_usuarios` (`id_usuario`, `nombre_usuario`, `correo_electronico`, `password_hash`, `rol`, `id_negocio`) 
VALUES (1, 'master', 'alexisgutierrez@acticven.com', '$2y$10$E.qR2.w/1.o.Z.qR2.w/1.o.Z.qR2.w/1.o.Z.qR2.w/1.o.Z.qR2.w', 'Master', 1);

UPDATE `j100_usuarios` 
SET `password_hash` = '$2y$10$1qA.i9.Q2.7bJ2.mY3.z9uO9.i2.o4.e6.f8.g0.h1.j3.k5.l7' 
WHERE `nombre_usuario` = 'master';


END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `P4- LIMPIAR LA TABLA DE CITAS`
-- ----------------------------
DROP PROCEDURE IF EXISTS `P4- LIMPIAR LA TABLA DE CITAS`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `P4- LIMPIAR LA TABLA DE CITAS`()
BEGIN
	#Routine body goes here...

-- Desactiva temporalmente la protección para poder truncar en cualquier orden
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE j108_citas;

-- ¡MUY IMPORTANTE! Vuelve a activar la protección
SET FOREIGN_KEY_CHECKS=1;


END
;;
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_negocios_before_insert`;
DELIMITER ;;
CREATE TRIGGER `trg_negocios_before_insert` BEFORE INSERT ON `j102_negocios` FOR EACH ROW BEGIN
    -- Si la fecha de habilitación y los días de prueba no son nulos, calcula la fecha de desactivación.
    IF NEW.fecha_habilitacion IS NOT NULL AND NEW.dias_prueba IS NOT NULL THEN
        SET NEW.fecha_desactivacion = DATE_ADD(NEW.fecha_habilitacion, INTERVAL NEW.dias_prueba DAY);
    END IF;
END
;;
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_negocios_before_update`;
DELIMITER ;;
CREATE TRIGGER `trg_negocios_before_update` BEFORE UPDATE ON `j102_negocios` FOR EACH ROW BEGIN
    -- Condición: Recalcular solo si los días de prueba o la fecha de habilitación cambian,
    -- Y ADEMÁS, el usuario no está modificando manualmente la fecha de desactivación.
    IF (NEW.dias_prueba <> OLD.dias_prueba OR NEW.fecha_habilitacion <> OLD.fecha_habilitacion) 
       AND NEW.fecha_desactivacion = OLD.fecha_desactivacion THEN
        
        SET NEW.fecha_desactivacion = DATE_ADD(NEW.fecha_habilitacion, INTERVAL NEW.dias_prueba DAY);
    END IF;
END
;;
DELIMITER ;
